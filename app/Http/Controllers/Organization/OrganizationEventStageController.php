<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationEvent;
use App\Models\Organization\OrganizationEventStage;
use App\Models\Organization\OrganizationEventStageResult;
use App\Models\Organization\OrganizationMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Image;

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

    private function savePreviewImage(Request $request, Organization $organization, OrganizationEvent $event, OrganizationEventStage $stage): void
    {
        if (! $request->filled('preview_image') || ! $stage->route) {
            return;
        }

        $path = storage_path('app/public/org/'.$organization->route.'/event/'.$event->route.'/stage/'.$stage->route);

        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true, true);
        }

        Image::make($request->preview_image)
            ->fit(1200, 675)
            ->encode('webp', 90)
            ->save($path.'/preview.webp');
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
            'route' => 'required|string|max:100|regex:/^[a-z0-9\-]+$/',
            'stage_type' => 'required|string|in:points,bracket',
            'description' => 'nullable|string',
            'start_at' => 'nullable|date',
            'config' => 'nullable|array',
            'preview_image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->messages()->toArray());
        }

        $routeInUse = OrganizationEventStage::where('organization_event_id', $event->id)
            ->where('route', $request->input('route'))
            ->exists();

        if ($routeInUse) {
            return response()->json(['message' => 'route_in_use'], 409);
        }

        $maxOrder = OrganizationEventStage::where('organization_event_id', $event->id)
            ->max('stage_order') ?? 0;

        $stage = OrganizationEventStage::create([
            'organization_event_id' => $event->id,
            'name' => $request->input('name'),
            'route' => $request->input('route'),
            'description' => $request->input('description'),
            'stage_type' => $request->input('stage_type'),
            'config' => $request->input('config', []),
            'stage_order' => $maxOrder + 1,
            'start_at' => $request->input('start_at'),
        ]);

        $this->savePreviewImage($request, $organization, $event, $stage);

        return response()->json(['message' => $this->formatStage($stage, $organization, $event)], 201);
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

        $stageKey = $request->route('stageRoute');
        $stage = OrganizationEventStage::where('organization_event_id', $event->id)
            ->where('route', $stageKey)
            ->first()
            ?? OrganizationEventStage::where('organization_event_id', $event->id)
            ->where('id', $stageKey)
            ->first();

        if (! $stage) {
            return response()->json(['message' => 'stage_not_found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'route' => 'sometimes|string|max:100|regex:/^[a-z0-9\-]+$/',
            'stage_type' => 'sometimes|string|in:points,bracket',
            'description' => 'nullable|string',
            'start_at' => 'nullable|date',
            'config' => 'nullable|array',
            'preview_image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->messages()->toArray());
        }

        if ($request->filled('route') && $request->input('route') !== $stage->route) {
            $routeInUse = OrganizationEventStage::where('organization_event_id', $event->id)
                ->where('route', $request->input('route'))
                ->where('id', '!=', $stage->id)
                ->exists();

            if ($routeInUse) {
                return response()->json(['message' => 'route_in_use'], 409);
            }
        }

        $stage->update($request->only(['name', 'route', 'description', 'stage_type', 'config', 'start_at']));

        $this->savePreviewImage($request, $organization, $event, $stage->fresh());

        return response()->json(['message' => $this->formatStage($stage->fresh(), $organization, $event)], 200);
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
            ->where('route', $request->route('stageRoute'))
            ->first();

        if (! $stage) {
            return response()->json(['message' => 'stage_not_found'], 404);
        }

        $stage->delete();

        return response()->json(['message' => 'stage_deleted'], 200);
    }

    public function control(Request $request)
    {
        [$organization, $event, $err] = $this->resolveEventAndOrg($request);
        if ($err) {
            return $err;
        }

        if (! $event->initialized) {
            return response()->json(['message' => 'event_not_initialized'], 422);
        }

        $stage = OrganizationEventStage::where('organization_event_id', $event->id)
            ->where('route', $request->route('stageRoute'))
            ->first();

        if (! $stage) {
            return response()->json(['message' => 'stage_not_found'], 404);
        }

        $action = $request->input('action');

        if ($action === 'start') {
            if ($stage->initialized) {
                return response()->json(['message' => 'stage_already_started'], 422);
            }

            $prevStage = OrganizationEventStage::where('organization_event_id', $event->id)
                ->where('stage_order', $stage->stage_order - 1)
                ->first();

            if ($prevStage && ! $prevStage->finished) {
                return response()->json(['message' => 'previous_stage_not_finished'], 422);
            }

            $stage->update(['initialized' => true, 'in_progress' => true]);
        } elseif ($action === 'finish') {
            if (! $stage->in_progress) {
                return response()->json(['message' => 'stage_not_in_progress'], 422);
            }

            $stage->update(['finished' => true, 'in_progress' => false]);
        } else {
            return response()->json(['message' => 'invalid_action'], 422);
        }

        return response()->json(['message' => $this->formatStage($stage->fresh(), $organization, $event)], 200);
    }

    public function setResults(Request $request)
    {
        [$organization, $event, $err] = $this->resolveEventAndOrg($request);
        if ($err) {
            return $err;
        }

        if (! $event->initialized) {
            return response()->json(['message' => 'event_not_initialized'], 422);
        }

        $stage = OrganizationEventStage::where('organization_event_id', $event->id)
            ->where('route', $request->route('stageRoute'))
            ->first();

        if (! $stage) {
            return response()->json(['message' => 'stage_not_found'], 404);
        }

        if ($stage->finished) {
            return response()->json(['message' => 'stage_already_finished'], 422);
        }

        $validator = Validator::make($request->all(), [
            'results' => 'required|array',
            'results.*.registration_id' => 'required|uuid',
            'results.*.position' => 'required|integer|min:1',
            'results.*.score' => 'nullable|numeric',
            'results.*.qualified' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->messages()->toArray());
        }

        OrganizationEventStageResult::where('organization_event_stage_id', $stage->id)->delete();

        foreach ($request->input('results') as $r) {
            OrganizationEventStageResult::create([
                'organization_event_stage_id' => $stage->id,
                'registration_id' => $r['registration_id'],
                'position' => $r['position'],
                'score' => $r['score'] ?? null,
                'qualified' => $r['qualified'] ?? false,
            ]);
        }

        return response()->json(['message' => 'results_saved'], 200);
    }

    private function formatStage(OrganizationEventStage $stage, Organization $organization = null, OrganizationEvent $event = null): array
    {
        $previewImage = null;
        if ($stage->route && $organization && $event) {
            $filePath = storage_path('app/public/org/'.$organization->route.'/event/'.$event->route.'/stage/'.$stage->route.'/preview.webp');
            if (file_exists($filePath)) {
                $previewImage = 'org/'.$organization->route.'/event/'.$event->route.'/stage/'.$stage->route.'/preview.webp';
            }
        }

        return [
            'id' => $stage->id,
            'name' => $stage->name,
            'route' => $stage->route,
            'preview_image' => $previewImage,
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
