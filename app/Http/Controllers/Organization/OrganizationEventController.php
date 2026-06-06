<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationEvent;
use App\Models\Organization\OrganizationEventRegistration;
use App\Models\Organization\OrganizationEventStage;
use App\Models\Organization\OrganizationMember;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Image;

class OrganizationEventController extends Controller
{
    private function getMemberRole(string $organizationId, string $userId): ?string
    {
        $member = OrganizationMember::where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->first();

        return $member?->role;
    }

    private function canManageEvents(string $role): bool
    {
        return in_array($role, ['owner', 'admin', 'event_manager']);
    }

    public function index(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (! $organization) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        $showFinished = $request->boolean('finished', false);

        $events = OrganizationEvent::where('organization_id', $organization->id)
            ->when(! $showFinished, fn ($q) => $q->where('finished', false))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($e) => $this->formatEvent($e));

        return response()->json(['message' => $events], 200);
    }

    public function show(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (! $organization) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        $event = OrganizationEvent::where('organization_id', $organization->id)
            ->where('route', $request->route('eventRoute'))
            ->first();

        if (! $event) {
            return response()->json(['message' => 'event_not_found'], 404);
        }

        $stages = OrganizationEventStage::where('organization_event_id', $event->id)
            ->orderBy('stage_order')
            ->with([
                'rounds' => fn ($q) => $q->orderBy('round_order'),
                'results' => fn ($q) => $q->orderBy('position')->with([
                    'registration' => fn ($q) => $q->with('user:id,name,username,avatar'),
                ]),
            ])
            ->get()
            ->map(fn ($s) => $this->formatStage($s));

        $registrationsCount = OrganizationEventRegistration::where('organization_event_id', $event->id)
            ->whereIn('payment_status', ['free', 'confirmed'])
            ->count();

        $userRegistration = null;
        $authUser = $request->user('sanctum');
        if ($authUser) {
            $reg = OrganizationEventRegistration::where('organization_event_id', $event->id)
                ->where('user_id', $authUser->id)
                ->first();
            if ($reg) {
                $userRegistration = [
                    'id' => $reg->id,
                    'payment_status' => $reg->payment_status,
                    'confirmed_at' => $reg->confirmed_at,
                ];
            }
        }

        $data = $this->formatEvent($event);
        $data['stages'] = $stages;
        $data['registrations_count'] = $registrationsCount;
        $data['user_registration'] = $userRegistration;

        return response()->json(['message' => $data], 200);
    }

    public function store(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (! $organization) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        $role = $this->getMemberRole($organization->id, $request->user('sanctum')->id);

        if (! $role || ! $this->canManageEvents($role)) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'route' => 'required|string|max:100|regex:/^[a-z0-9\-]+$/',
            'category' => 'required|string|max:100',
            'runmode' => 'required|string|max:50',
            'subcategory' => 'nullable|string|max:100',
            'form_schema_id' => 'nullable|uuid|exists:event_form_schemas,id',
            'event_data' => 'nullable|array',
            'registration_form_template' => 'nullable|array',
            'registration_form_template.*.type' => 'required_with:registration_form_template|string|in:text,number,select,switch,checkbox',
            'registration_form_template.*.label' => 'required_with:registration_form_template|string|max:120',
            'registration_form_template.*.name' => 'required_with:registration_form_template|string|max:120',
            'registration_form_template.*.required' => 'boolean',
            'registration_form_template.*.values' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->messages()->toArray());
        }

        $routeInUse = OrganizationEvent::where('organization_id', $organization->id)
            ->where('route', $request->input('route'))
            ->exists();

        if ($routeInUse) {
            return response()->json(['message' => 'route_in_use'], 409);
        }

        $createdEvent = null;
        DB::transaction(function () use ($request, $organization, &$createdEvent) {
            $event = new OrganizationEvent($request->only([
                'name', 'short_description', 'description',
                'fee', 'currency', 'max_registrations', 'start_at',
                'category', 'subcategory', 'runmode', 'form_schema_id',
                'event_data', 'registration_form_template',
            ]));

            $event->organization_id = $organization->id;
            $event->route = $request->input('route');

            $path = storage_path('app/public/org/'.$organization->route.'/events/'.$event->route);

            if ($request->filled('logo_image')) {
                if (! File::isDirectory($path)) {
                    File::makeDirectory($path, 0755, true, true);
                }

                Image::make($request->logo_image)
                    ->fit(400, 400)
                    ->encode('webp', 90)
                    ->save($path.'/logo.webp');

                $event->logo_image = 'org/'.$organization->route.'/events/'.$event->route.'/logo.webp';
            }

            if ($request->filled('cover_image')) {
                if (! File::isDirectory($path)) {
                    File::makeDirectory($path, 0755, true, true);
                }

                Image::make($request->cover_image)
                    ->fit(1200, 675)
                    ->encode('webp', 90)
                    ->save($path.'/preview.webp');

                $event->cover_image = 'org/'.$organization->route.'/events/'.$event->route.'/preview.webp';
            }

            $event->save();
            $createdEvent = $event;
        });

        if ($createdEvent) {
            NotificationService::sendToFollowers(
                $organization->id,
                'notification.event_created',
                ['org' => $organization->name, 'name' => $createdEvent->name],
                '/org/'.$organization->route.'/event/'.$createdEvent->route
            );
        }

        return response()->json(['message' => 'Event created'], 201);
    }

    public function update(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (! $organization) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        $role = $this->getMemberRole($organization->id, $request->user('sanctum')->id);

        if (! $role || ! $this->canManageEvents($role)) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $event = OrganizationEvent::where('organization_id', $organization->id)
            ->where('route', $request->route('eventRoute'))
            ->first();

        if (! $event) {
            return response()->json(['message' => 'event_not_found'], 404);
        }

        DB::transaction(function () use ($request, $organization, $event) {
            $fields = $request->only([
                'name', 'short_description', 'description',
                'fee', 'currency', 'max_registrations',
                'initialized', 'finished', 'start_at',
            ]);
            $updated = [];

            foreach ($event->toArray() as $key => $current) {
                if (array_key_exists($key, $fields) && $current !== $fields[$key]) {
                    $updated[$key] = $fields[$key];
                }
            }

            if (! empty($updated)) {
                $event->update($updated);
            }

            $path = storage_path('app/public/org/'.$organization->route.'/events/'.$event->route);

            if ($request->filled('logo_image')) {
                if (! File::isDirectory($path)) {
                    File::makeDirectory($path, 0755, true, true);
                }

                Image::make($request->logo_image)
                    ->fit(400, 400)
                    ->encode('webp', 90)
                    ->save($path.'/logo.webp');

                $event->update(['logo_image' => 'org/'.$organization->route.'/events/'.$event->route.'/logo.webp']);
            }

            if ($request->filled('cover_image')) {
                if (! File::isDirectory($path)) {
                    File::makeDirectory($path, 0755, true, true);
                }

                Image::make($request->cover_image)
                    ->fit(1200, 675)
                    ->encode('webp', 90)
                    ->save($path.'/preview.webp');

                $event->update(['cover_image' => 'org/'.$organization->route.'/events/'.$event->route.'/preview.webp']);
            }
        });

        return response()->json(['message' => 'Event updated'], 200);
    }

    public function destroy(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();

        if (! $organization) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        $role = $this->getMemberRole($organization->id, $request->user('sanctum')->id);

        if (! $role || ! in_array($role, ['owner', 'admin'])) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $event = OrganizationEvent::where('organization_id', $organization->id)
            ->where('route', $request->route('eventRoute'))
            ->first();

        if (! $event) {
            return response()->json(['message' => 'event_not_found'], 404);
        }

        $event->delete();

        return response()->json(['message' => 'Event deleted'], 200);
    }

    private function formatStage(OrganizationEventStage $stage): array
    {
        $data = [
            'id' => $stage->id,
            'name' => $stage->name,
            'description' => $stage->description,
            'stage_type' => $stage->stage_type,
            'config' => $stage->config,
            'stage_order' => $stage->stage_order,
            'initialized' => $stage->initialized,
            'in_progress' => $stage->in_progress,
            'finished' => $stage->finished,
            'start_at' => $stage->start_at,
            'rounds' => $stage->rounds->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'config' => $r->config,
                'round_order' => $r->round_order,
                'initialized' => $r->initialized,
                'in_progress' => $r->in_progress,
                'finished' => $r->finished,
                'start_at' => $r->start_at,
            ])->values(),
        ];

        if ($stage->finished) {
            $data['results'] = $stage->results->map(fn ($r) => [
                'position' => $r->position,
                'score' => $r->score,
                'qualified' => $r->qualified,
                'user' => $r->registration?->user ? [
                    'id' => $r->registration->user->id,
                    'name' => $r->registration->user->name,
                    'username' => $r->registration->user->username,
                    'avatar' => $r->registration->user->avatar,
                ] : null,
            ])->values();
        }

        return $data;
    }

    private function formatEvent(OrganizationEvent $event): array
    {
        return [
            'id' => $event->id,
            'name' => $event->name,
            'route' => $event->route,
            'short_description' => $event->short_description,
            'description' => $event->description,
            'logo_image' => $event->logo_image,
            'cover_image' => $event->cover_image,
            'fee' => (float) $event->fee,
            'currency' => $event->currency,
            'max_registrations' => $event->max_registrations,
            'initialized' => $event->initialized,
            'finished' => $event->finished,
            'start_at' => $event->start_at,
            'category' => $event->category,
            'subcategory' => $event->subcategory,
            'runmode' => $event->runmode,
            'form_schema_id' => $event->form_schema_id,
            'event_data' => $event->event_data,
            'registration_form_template' => $event->registration_form_template,
            'created_at' => $event->created_at,
        ];
    }
}
