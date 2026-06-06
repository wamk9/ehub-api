<?php

namespace App\Http\Controllers;

use App\Models\User\Notification as UserNotification;
use App\Models\User\PersonalAccessToken;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationEvent;
use App\Models\Organization\OrganizationEventStage;
use App\Models\Organization\Article;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LiveController extends Controller
{
    public function stream(Request $request): StreamedResponse
    {
        // EventSource cannot send headers — authenticate via token query param
        $user  = null;
        $token = $request->query('token');
        if ($token) {
            $pat  = PersonalAccessToken::findToken($token);
            $user = $pat?->tokenable;
        }

        $module = $request->query('module', 'notifications');
        $params = $request->query();

        return response()->stream(function () use ($user, $module, $params) {
            @set_time_limit(0);
            @ignore_user_abort(true);

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $startTime    = time();
            $maxDuration  = 20;
            $pollInterval = 3;
            $lastHash     = null;

            echo ": connected\n\n";
            flush();

            while ((time() - $startTime) < $maxDuration) {
                if (connection_aborted()) break;

                $data = $this->fetchModule($module, $user, $params);

                if ($data !== null) {
                    $hash = md5(json_encode($data));
                    if ($hash !== $lastHash) {
                        $lastHash = $hash;
                        echo 'data: ' . json_encode(['type' => $module, 'payload' => $data]) . "\n\n";
                        flush();
                    }
                }

                echo ": ping\n\n";
                flush();

                sleep($pollInterval);
            }

            // Stream closes — EventSource auto-reconnects after ~3 s
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function fetchModule(string $module, $user, array $params): mixed
    {
        return match ($module) {
            'notifications' => $user ? $this->fetchNotifications($user) : null,
            'event-stages'  => $this->fetchEventStages($params),
            'org-content'   => $this->fetchOrgContent($params),
            default         => null,
        };
    }

    private function fetchNotifications($user): array
    {
        return UserNotification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(30)
            ->get(['id', 'title', 'description', 'route', 'read_at', 'created_at'])
            ->toArray();
    }

    private function fetchEventStages(array $params): ?array
    {
        $orgRoute   = $params['orgRoute']   ?? null;
        $eventRoute = $params['eventRoute'] ?? null;
        if (!$orgRoute || !$eventRoute) return null;

        $org = Organization::where('route', $orgRoute)->first();
        if (!$org) return null;

        $event = OrganizationEvent::where('organization_id', $org->id)
            ->where('route', $eventRoute)
            ->first();
        if (!$event) return null;

        $stages = OrganizationEventStage::where('organization_event_id', $event->id)
            ->orderBy('stage_order')
            ->get()
            ->map(fn($s) => [
                'id'          => $s->id,
                'name'        => $s->name,
                'stage_type'  => $s->stage_type,
                'stage_order' => $s->stage_order,
                'initialized' => (bool) $s->initialized,
                'in_progress' => (bool) $s->in_progress,
                'finished'    => (bool) $s->finished,
                'start_at'    => $s->start_at,
            ])
            ->toArray();

        return [
            'initialized' => (bool) $event->initialized,
            'finished'    => (bool) $event->finished,
            'stages'      => $stages,
        ];
    }

    private function fetchOrgContent(array $params): ?array
    {
        $orgRoute = $params['orgRoute'] ?? null;
        if (!$orgRoute) return null;

        $org = Organization::where('route', $orgRoute)->first();
        if (!$org) return null;

        $events = OrganizationEvent::where('organization_id', $org->id)
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'route', 'image', 'fee', 'currency', 'finished', 'category', 'short_description', 'updated_at'])
            ->toArray();

        $articles = Article::where('organization_id', $org->id)
            ->orderByDesc('published_at')
            ->get(['id', 'title', 'slug', 'excerpt', 'cover_image', 'published_at', 'updated_at'])
            ->toArray();

        return compact('events', 'articles');
    }
}
