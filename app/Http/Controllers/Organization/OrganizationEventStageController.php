<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationEvent;
use App\Models\Organization\OrganizationEventStage;
use App\Models\Organization\OrganizationMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OrganizationEventStageController extends Controller
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

    private function resolveEventAndOrg(Request $request): array
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();
        if (! $organization) {
            return [null, null, response()->json(['message' => 'org_not_found'], 404)];
        }

        $event = OrganizationEvent::where('organization_id', $organization->id)
            ->where('route', $request->route('eventRoute'))
            ->first();
        if (! $event) {
            return [null, null, response()->json(['message' => 'event_not_found'], 404)];
        }

        $role = $this->getMemberRole($organization->id, $request->user('sanctum')->id);
        if (! $role || ! $this->canManageEvents($role)) {
            return [null, null, response()->json(['message' => 'unauthorized'], 401)];
        }

        return [$organization, $event, null];
    }

    public function store(Request $request)
    {
        [$organization, $event, $err] = $this->resolveEventAndOrg($request);
        if ($err) {
            return $err;
        }

        if ($event->initialized) {
            return response()->json(['message' => 'event_already_initialized'], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'stage_type' => 'required|string|in:points,bracket',
            'description' => 'nullable|string',
            'start_at' => 'nullable|date',
            'config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->messages()->toArray());
        }

        $maxOrder = OrganizationEventStage::where('organization_event_id', $event->id)
            ->max('stage_order') ?? 0;

        $stage = OrganizationEventStage::create([
            'organization_event_id' => $event->id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'stage_type' => $request->input('stage_type'),
            'config' => $request->input('config', []),
            'stage_order' => $maxOrder + 1,
            'start_at' => $request->input('start_at'),
        ]);

        return response()->json(['message' => $this->formatStage($stage)], 201);
    }

    public function update(Request $request)
    {
        [$organization, $event, $err] = $this->resolveEventAndOrg($request);
        if ($err) {
            return $err;
        }

        if ($event->initialized) {
            return response()->json(['message' => 'event_already_initialized'], 422);
        }

        $stage = OrganizationEventStage::where('organization_event_id', $event->id)
            ->where('id', $request->route('stageId'))
            ->first();

        if (! $stage) {
            return response()->json(['message' => 'stage_not_found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'stage_type' => 'sometimes|string|in:points,bracket',
            'description' => 'nullable|string',
            'start_at' => 'nullable|date',
            'config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->messages()->toArray());
        }

        $stage->update($request->only(['name', 'description', 'stage_type', 'config', 'start_at']));

        return response()->json(['message' => $this->formatStage($stage->fresh())], 200);
    }

    public function destroy(Request $request)
    {
        [$organization, $event, $err] = $this->resolveEventAndOrg($request);
        if ($err) {
            return $err;
        }

        if ($event->initialized) {
            return response()->json(['message' => 'event_already_initialized'], 422);
        }

        $stage = OrganizationEventStage::where('organization_event_id', $event->id)
            ->where('id', $request->route('stageId'))
            ->first();

        if (! $stage) {
            return response()->json(['message' => 'stage_not_found'], 404);
        }

        $stage->delete();

        return response()->json(['message' => 'stage_deleted'], 200);
    }

    private function formatStage(OrganizationEventStage $stage): array
    {
        return [
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
        ];
    }
}
