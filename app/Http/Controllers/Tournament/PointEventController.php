<?php

namespace App\Http\Controllers\Tournament;

use App\Http\Controllers\Controller;
use App\Models\League\League;
use App\Models\Tournament\Point\PointEvent;
use App\Models\Tournament\Point\PointReference;
use App\Models\Tournament\Point\PointRound;
use App\Models\Tournament\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PointEventController extends Controller
{
    public function create(Request $request)
    {
        if (! $this->verifyLeagueUserAuthorization($request->user('sanctum')->id, $request->route('leagueRoute'), 'config', 'create_league_tournaments')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        DB::transaction(function () use ($request) {
            $league = League::where('route', $request->route('leagueRoute'))->first();

            if (! $league) {
                throw ValidationException::withMessages(['message' => 'League not found']);
            }

            $tournament = Tournament::where([
                ['route', '=', $request->route('tournamentRoute')],
                ['league_id', '=', $league->id],
            ])->first();

            if (! $tournament) {
                throw ValidationException::withMessages(['message' => 'Tournament not found']);
            }

            if ($request->filled('events')) {
                $requestEventData = [
                    'name',
                    'init_at',
                    'duration',
                    'description',
                    'can_discard',
                    'route',
                ];

                $requestRoundData = [
                    'name',
                    'references',
                ];

                $requestReferencesData = [
                    'num_order',
                    'points',
                ];

                foreach ($request->only('events')['events'] as $event) {
                    $eventData = [];
                    $roundData = [];
                    $referenceData = [];

                    foreach ($event as $key => $value) {
                        if (in_array($key, $requestEventData)) {
                            $eventData[$key] = $value;
                        }
                    }

                    $eventData['tournament_id'] = $tournament->id;

                    $pointEvent = new PointEvent($eventData);
                    $pointEvent->save();

                    foreach ($event['rounds'] as $round) {
                        foreach ($round as $key => $value) {
                            if (in_array($key, $requestRoundData)) {
                                $roundData[$key] = $value;
                            }
                        }

                        $roundData['point_event_id'] = $pointEvent->id;

                        $pointRound = new PointRound($roundData);
                        $pointRound->save();
                    }

                    foreach ($roundData['references'] as $reference) {
                        foreach ($reference as $key => $value) {
                            if (in_array($key, $requestReferencesData)) {
                                $referenceData[$key] = $value;
                            }
                        }

                        $referenceData['point_round_id'] = $pointRound->id;

                        $pointReference = new PointReference($referenceData);
                        $pointReference->save();
                    }
                }
            }
        });

        return response()->json(['message' => 'Event Created'], 200);
    }

    public function show(Request $request)
    {
        $league = League::where('route', $request->route('leagueRoute'))->first();

        if (! $league) {
            throw ValidationException::withMessages(['message' => 'League not found']);
        }

        $tournament = Tournament::where([
            ['route', '=', $request->route('tournamentRoute')],
            ['league_id', '=', $league->id],
        ])->first();

        if (! $tournament) {
            throw ValidationException::withMessages(['message' => 'Tournament not found']);
        }

        $event = PointEvent::where([
            ['route', '=', $request->route('eventRoute')],
            ['tournament_id', '=', $tournament->id],
        ])->first();

        if (! $event) {
            throw ValidationException::withMessages(['message' => 'Event not found']);
        }

        return response()->json(['message' => $event], 200);
    }
}
