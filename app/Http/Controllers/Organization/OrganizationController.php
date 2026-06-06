<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Mail\OrgInviteExistingUser;
use App\Mail\OrgInviteNewUser;
use App\Models\Organization\OrgFollow;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationInvite;
use App\Models\Organization\OrganizationMember;
use App\Models\User\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Image;

class OrganizationController extends Controller
{
    private function getMemberRole(string $organizationId, string $userId): ?string
    {
        $member = OrganizationMember::where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->first();

        return $member?->role;
    }

    private function canManage(string $role): bool
    {
        return in_array($role, ['owner', 'admin']);
    }

    public function create(Request $request)
    {
        DB::transaction(function () use ($request) {
            $validator = Validator::make($request->only(['name', 'route', 'description']), [
                'name'  => 'required|unique:organizations,name',
                'route' => 'required|unique:organizations,route',
            ]);

            if ($validator->fails())
                throw ValidationException::withMessages($validator->messages()->toArray());

            $organization = new Organization($request->only(['name', 'route', 'description']));

            if ($request->filled('logo_image')) {
                $path = storage_path('app/public/org/' . $request->route);

                if (!File::isDirectory($path))
                    File::makeDirectory($path, 0755, true, true);

                Image::make($request->logo_image)
                    ->resize(250, 250, fn($c) => $c->aspectRatio())
                    ->encode('webp', 90)
                    ->save($path . '/logo.webp');

                $organization->logo_image = 'org/' . $request->route . '/logo.webp';
            }

            $organization->save();

            $userId = $request->user('sanctum')->id;
            OrganizationMember::create([
                'organization_id' => $organization->id,
                'user_id'         => $userId,
                'role'            => 'owner',
            ]);
            OrgFollow::firstOrCreate([
                'organization_id' => $organization->id,
                'user_id'         => $userId,
            ]);
        });

        return response()->json(['message' => 'Organization created'], 201);
    }

    public function getMine(Request $request)
    {
        $userId = $request->user('sanctum')->id;

        $organizations = Organization::whereHas('members', fn($q) => $q->where('user_id', $userId))
            ->with(['members' => fn($q) => $q->where('user_id', $userId)])
            ->get()
            ->map(function ($org) {
                return [
                    'id'          => $org->id,
                    'name'        => $org->name,
                    'route'       => $org->route,
                    'description' => $org->description,
                    'logo_image'  => $org->logo_image,
                    'role'        => $org->members->first()?->role,
                    'created_at'  => $org->created_at,
                ];
            });

        return response()->json(['message' => $organizations], 200);
    }

    public function show(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (!$organization)
            return response()->json(['message' => 'Organization not found'], 404);

        $data = $organization->toArray();

        if ($request->user('sanctum')) {
            $userId              = $request->user('sanctum')->id;
            $role                = $this->getMemberRole($organization->id, $userId);
            $data['role']        = $role;
            $data['my_user_id']  = $userId;
            $data['is_following'] = OrgFollow::where('organization_id', $organization->id)
                ->where('user_id', $userId)
                ->exists();
        }

        return response()->json(['message' => $data], 200);
    }

    public function updateProfile(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (!$organization)
            return response()->json(['message' => 'Organization not found'], 404);

        $role = $this->getMemberRole($organization->id, $request->user('sanctum')->id);

        if (!$role || !$this->canManage($role))
            return response()->json(['message' => 'Unauthorized'], 401);

        DB::transaction(function () use ($request, $organization) {
            $fields = $request->only([
                'name', 'description', 'about',
                'instagram', 'facebook', 'x_twitter', 'website', 'phone', 'contact_email',
            ]);
            $updated = [];

            foreach ($organization->toArray() as $key => $current) {
                if (array_key_exists($key, $fields) && $current !== $fields[$key])
                    $updated[$key] = $fields[$key];
            }

            if (isset($updated['name'])) {
                $inUse = Organization::where('name', $updated['name'])
                    ->where('id', '!=', $organization->id)
                    ->exists();

                if ($inUse)
                    throw ValidationException::withMessages(['name' => 'Organization name in use']);
            }

            if (!empty($updated))
                $organization->update($updated);

            if ($request->filled('logo_image')) {
                $path = storage_path('app/public/org/' . $organization->route);

                if (!File::isDirectory($path))
                    File::makeDirectory($path, 0755, true, true);

                Image::make($request->logo_image)
                    ->resize(250, 250, fn($c) => $c->aspectRatio())
                    ->encode('webp', 90)
                    ->save($path . '/logo.webp');

                $organization->update(['logo_image' => 'org/' . $organization->route . '/logo.webp']);
            }
        });

        return response()->json(['message' => 'Organization updated'], 200);
    }

    public function delete(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (!$organization)
            return response()->json(['message' => 'Organization not found'], 404);

        $role = $this->getMemberRole($organization->id, $request->user('sanctum')->id);

        if ($role !== 'owner')
            return response()->json(['message' => 'Only owner can delete organization'], 401);

        $organization->delete();

        return response()->json(['message' => 'Organization deleted'], 200);
    }

    public function getMembers(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (!$organization)
            return response()->json(['message' => 'Organization not found'], 404);

        $role = $this->getMemberRole($organization->id, $request->user('sanctum')->id);

        if (!$role)
            return response()->json(['message' => 'Unauthorized'], 401);

        $members = OrganizationMember::where('organization_id', $organization->id)
            ->with('user:id,name,surname,username,mail')
            ->get()
            ->map(fn($m) => [
                'id'         => $m->id,
                'role'       => $m->role,
                'joined_at'  => $m->created_at,
                'user'       => array_merge($m->user->only(['id', 'name', 'surname', 'username', 'mail']), [
                    'image' => 'users/' . $m->user->username . '/profile.webp',
                ]),
            ]);

        return response()->json(['message' => $members], 200);
    }

    public function addMember(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (!$organization)
            return response()->json(['message' => 'org_not_found'], 404);

        $actorRole = $this->getMemberRole($organization->id, $request->user('sanctum')->id);

        if (!$actorRole || !$this->canManage($actorRole))
            return response()->json(['message' => 'unauthorized'], 401);

        $validator = Validator::make($request->only(['email', 'role']), [
            'email' => 'required|email',
            'role'  => 'required|in:admin,financial,event_manager,marketing',
        ]);

        if ($validator->fails())
            throw ValidationException::withMessages($validator->messages()->toArray());

        $email    = strtolower(trim($request->email));
        $actor    = $request->user('sanctum');
        $roleName = $this->roleLabel($request->role);

        $targetUser = User::where('mail', $email)->first();

        if ($targetUser) {
            $alreadyMember = OrganizationMember::where('organization_id', $organization->id)
                ->where('user_id', $targetUser->id)
                ->exists();

            if ($alreadyMember)
                return response()->json(['message' => 'already_member'], 409);

            OrganizationMember::create([
                'organization_id' => $organization->id,
                'user_id'         => $targetUser->id,
                'role'            => $request->role,
            ]);
            OrgFollow::firstOrCreate([
                'organization_id' => $organization->id,
                'user_id'         => $targetUser->id,
            ]);

            NotificationService::send(
                $targetUser->id,
                'notification.member_added',
                ['org' => $organization->name, 'role' => $roleName],
                '/org/' . $organization->route
            );

            Mail::to($email)->send(new OrgInviteExistingUser(
                orgName:     $organization->name,
                orgRoute:    $organization->route,
                inviterName: $actor->name . ' ' . $actor->surname,
                role:        $roleName,
                inviteToken: '',
            ));

            return response()->json(['message' => 'Member added'], 201);
        }

        // No account — create pending invite
        $pendingExists = OrganizationInvite::where('organization_id', $organization->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($pendingExists)
            return response()->json(['message' => 'invite_already_sent'], 409);

        $token = Str::random(64);

        OrganizationInvite::create([
            'organization_id' => $organization->id,
            'email'           => $email,
            'role'            => $request->role,
            'token'           => $token,
            'expires_at'      => now()->addDays(7),
        ]);

        Mail::to($email)->send(new OrgInviteNewUser(
            orgName:      $organization->name,
            orgRoute:     $organization->route,
            inviterName:  $actor->name . ' ' . $actor->surname,
            role:         $roleName,
            inviteToken:  $token,
            invitedEmail: $email,
        ));

        return response()->json(['message' => 'Invite sent'], 201);
    }

    public function transferOwnership(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (!$organization)
            return response()->json(['message' => 'org_not_found'], 404);

        $actorRole = $this->getMemberRole($organization->id, $request->user('sanctum')->id);

        if ($actorRole !== 'owner')
            return response()->json(['message' => 'owner_only'], 403);

        $validator = Validator::make($request->only(['email']), [
            'email' => 'required|email',
        ]);

        if ($validator->fails())
            throw ValidationException::withMessages($validator->messages()->toArray());

        $email      = strtolower(trim($request->email));
        $targetUser = User::where('mail', $email)->first();

        if (!$targetUser)
            return response()->json(['message' => 'transfer_user_not_found'], 404);

        if ($targetUser->id === $request->user('sanctum')->id)
            return response()->json(['message' => 'already_owner'], 422);

        DB::transaction(function () use ($organization, $request, $targetUser) {
            // Demote current owner to admin
            OrganizationMember::where('organization_id', $organization->id)
                ->where('user_id', $request->user('sanctum')->id)
                ->update(['role' => 'admin']);

            // Upsert new owner
            OrganizationMember::updateOrCreate(
                ['organization_id' => $organization->id, 'user_id' => $targetUser->id],
                ['role' => 'owner'],
            );
        });

        return response()->json(['message' => 'Ownership transferred'], 200);
    }

    public function getInvites(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (!$organization)
            return response()->json(['message' => 'org_not_found'], 404);

        $role = $this->getMemberRole($organization->id, $request->user('sanctum')->id);

        if (!$role || !$this->canManage($role))
            return response()->json(['message' => 'unauthorized'], 401);

        $invites = OrganizationInvite::where('organization_id', $organization->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($i) => [
                'id'         => $i->id,
                'email'      => $i->email,
                'role'       => $i->role,
                'expires_at' => $i->expires_at,
                'created_at' => $i->created_at,
            ]);

        return response()->json(['message' => $invites], 200);
    }

    public function resendInvite(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (!$organization)
            return response()->json(['message' => 'org_not_found'], 404);

        $actorRole = $this->getMemberRole($organization->id, $request->user('sanctum')->id);

        if (!$actorRole || !$this->canManage($actorRole))
            return response()->json(['message' => 'unauthorized'], 401);

        $invite = OrganizationInvite::where('id', $request->route('inviteId'))
            ->where('organization_id', $organization->id)
            ->whereNull('accepted_at')
            ->first();

        if (!$invite)
            return response()->json(['message' => 'invite_invalid'], 404);

        $actor      = $request->user('sanctum');
        $targetUser = User::where('mail', $invite->email)->first();

        if ($targetUser) {
            $alreadyMember = OrganizationMember::where('organization_id', $organization->id)
                ->where('user_id', $targetUser->id)
                ->exists();

            if (!$alreadyMember) {
                OrganizationMember::create([
                    'organization_id' => $organization->id,
                    'user_id'         => $targetUser->id,
                    'role'            => $invite->role,
                ]);
            }
            OrgFollow::firstOrCreate([
                'organization_id' => $organization->id,
                'user_id'         => $targetUser->id,
            ]);

            $invite->update(['accepted_at' => now()]);

            Mail::to($invite->email)->send(new OrgInviteExistingUser(
                orgName:     $organization->name,
                orgRoute:    $organization->route,
                inviterName: $actor->name . ' ' . $actor->surname,
                role:        $this->roleLabel($invite->role),
                inviteToken: '',
            ));

            return response()->json(['message' => 'Member added'], 200);
        }

        $invite->update([
            'token'      => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($invite->email)->send(new OrgInviteNewUser(
            orgName:      $organization->name,
            orgRoute:     $organization->route,
            inviterName:  $actor->name . ' ' . $actor->surname,
            role:         $this->roleLabel($invite->role),
            inviteToken:  $invite->token,
            invitedEmail: $invite->email,
        ));

        return response()->json(['message' => 'Invite resent'], 200);
    }

    public function removeInvite(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (!$organization)
            return response()->json(['message' => 'org_not_found'], 404);

        $actorRole = $this->getMemberRole($organization->id, $request->user('sanctum')->id);

        if (!$actorRole || !$this->canManage($actorRole))
            return response()->json(['message' => 'unauthorized'], 401);

        $invite = OrganizationInvite::where('id', $request->route('inviteId'))
            ->where('organization_id', $organization->id)
            ->first();

        if (!$invite)
            return response()->json(['message' => 'invite_invalid'], 404);

        $invite->delete();

        return response()->json(['message' => 'Invite removed'], 200);
    }

    public function acceptInvite(Request $request)
    {
        $invite = OrganizationInvite::where('token', $request->route('token'))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$invite)
            return response()->json(['message' => 'invite_invalid'], 404);

        $user = $request->user('sanctum');

        if (strtolower($user->mail) !== $invite->email)
            return response()->json(['message' => 'invite_email_mismatch'], 403);

        $alreadyMember = OrganizationMember::where('organization_id', $invite->organization_id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyMember) {
            $invite->update(['accepted_at' => now()]);
            return response()->json(['message' => 'Already a member'], 200);
        }

        DB::transaction(function () use ($invite, $user) {
            OrganizationMember::create([
                'organization_id' => $invite->organization_id,
                'user_id'         => $user->id,
                'role'            => $invite->role,
            ]);
            OrgFollow::firstOrCreate([
                'organization_id' => $invite->organization_id,
                'user_id'         => $user->id,
            ]);

            $invite->update(['accepted_at' => now()]);
        });

        $org = Organization::find($invite->organization_id);
        if ($org) {
            NotificationService::send(
                $user->id,
                'notification.invite_accepted',
                ['org' => $org->name, 'role' => $this->roleLabel($invite->role)],
                '/org/' . $org->route
            );
        }

        return response()->json(['message' => 'Invite accepted'], 200);
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'admin'         => 'Admin',
            'financial'     => 'Financial',
            'event_manager' => 'Event Manager',
            'marketing'     => 'Marketing',
            default         => $role,
        };
    }

    public function updateMemberRole(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (!$organization)
            return response()->json(['message' => 'Organization not found'], 404);

        $actorRole = $this->getMemberRole($organization->id, $request->user('sanctum')->id);

        if (!$actorRole || !$this->canManage($actorRole))
            return response()->json(['message' => 'Unauthorized'], 401);

        $validator = Validator::make($request->only(['role']), [
            'role' => 'required|in:admin,financial,event_manager,marketing',
        ]);

        if ($validator->fails())
            throw ValidationException::withMessages($validator->messages()->toArray());

        $member = OrganizationMember::where('organization_id', $organization->id)
            ->where('user_id', $request->route('userId'))
            ->first();

        if (!$member)
            return response()->json(['message' => 'Member not found'], 404);

        if ($member->role === 'owner')
            return response()->json(['message' => 'Cannot change owner role'], 403);

        $member->update(['role' => $request->role]);

        return response()->json(['message' => 'Member role updated'], 200);
    }

    public function follow(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();
        if (!$organization) return response()->json(['message' => 'org_not_found'], 404);

        OrgFollow::firstOrCreate([
            'organization_id' => $organization->id,
            'user_id'         => $request->user('sanctum')->id,
        ]);

        return response()->json(['message' => 'following'], 200);
    }

    public function unfollow(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();
        if (!$organization) return response()->json(['message' => 'org_not_found'], 404);

        OrgFollow::where('organization_id', $organization->id)
            ->where('user_id', $request->user('sanctum')->id)
            ->delete();

        return response()->json(['message' => 'unfollowed'], 200);
    }

    public function leaveOrganization(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (!$organization)
            return response()->json(['message' => 'Organization not found'], 404);

        $member = OrganizationMember::where('organization_id', $organization->id)
            ->where('user_id', $request->user('sanctum')->id)
            ->first();

        if (!$member)
            return response()->json(['message' => 'Not a member'], 404);

        if ($member->role === 'owner')
            return response()->json(['message' => 'owner_cannot_leave'], 403);

        $member->delete();

        return response()->json(['message' => 'Left organization'], 200);
    }

    public function removeMember(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (!$organization)
            return response()->json(['message' => 'Organization not found'], 404);

        $actorRole = $this->getMemberRole($organization->id, $request->user('sanctum')->id);

        if (!$actorRole || !$this->canManage($actorRole))
            return response()->json(['message' => 'Unauthorized'], 401);

        $member = OrganizationMember::where('organization_id', $organization->id)
            ->where('user_id', $request->route('userId'))
            ->first();

        if (!$member)
            return response()->json(['message' => 'Member not found'], 404);

        if ($member->role === 'owner')
            return response()->json(['message' => 'Cannot remove owner'], 403);

        if ($member->user_id === $request->user('sanctum')->id)
            return response()->json(['message' => 'Cannot remove yourself'], 403);

        $member->delete();

        return response()->json(['message' => 'Member removed'], 200);
    }
}
