<?php

namespace App\Http\Controllers\Tournament;

use App\Http\Controllers\Controller;
use App\Models\League\League;
use App\Models\Tournament\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Image;

class TournamentController extends Controller
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
                ['route', '=', $request->only('route')],
                ['league_id', '=', $league->id],
            ])->first();

            if ((bool) $tournament) {
                throw ValidationException::withMessages(['message' => 'Tournament route in use.']);
            }

            $requestData = [
                'name',
                'description',
                'route',
                'currency_id',
                'price',
                'subscription_limit',
                'subcategory_id',
                'runmode_id',
            ];

            $tournament = new Tournament($request->only($requestData));
            $tournament->league_id = $league->id;

            $tournament->save();

            if ($request->filled('logo_image')) {
                $path = storage_path('app/public/league/'.$league->route.'/tournament//'.$tournament->route);

                if (! File::isDirectory($path)) {
                    File::makeDirectory($path, 0755, true, true);
                }

                Image::make($request->only('logo_image')['logo_image'])->encode('webp', 90)->resize(250, 250, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($path.'/logo.webp');
            }
        });

        return response()->json(['message' => 'Tournament created'], 200);
    }

    public function updateProfile(Request $request)
    {
        if (! $this->verifyLeagueUserAuthorization($request->user('sanctum')->id, $request->route('leagueRoute'), 'config', 'edit_league_tournaments')) {
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
                throw ValidationException::withMessages(['message' => 'Tournament not found.']);
            }

            $requestData = [
                'name',
                'description',
                'route',
                'currency_id',
                'price',
                'subscription_limit',
                'subcategory_id',
                'runmode_id',
            ];

            $updatedData = [];

            foreach ($tournament->toArray() as $key => $actualInfo) {
                if (array_key_exists($key, $requestData) && ($actualInfo != $requestData[$key])) {
                    $updatedData[$key] = $requestData[$key];
                }
            }

            if (array_key_exists('route', $updatedData)) {
                $whereCondition = [
                    ['route', '=', $updatedData['route']],
                    ['league_id', '=', $league->id],
                    ['id', '!=', $tournament->id],
                ];

                if (count(Tournament::where($whereCondition)->get()->toArray()) > 0) {
                    throw ValidationException::withMessages(['message' => 'Tournament route in use']);
                }
            }

            $tournament->update($updatedData);

            if ($request->filled('logo_image')) {
                $path = storage_path('app/public/league/'.$league->route.'/tournament//'.$tournament->route);

                if (! File::isDirectory($path)) {
                    File::makeDirectory($path, 0755, true, true);
                }

                Image::make($request->only('logo_image')['logo_image'])->encode('webp', 90)->resize(250, 250, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($path.'/logo.webp');
            }
        });

        return response()->json(['message' => 'Tournament edited'], 200);
    }

    public function showPeriods(Request $request)
    {
        if ((bool) $request->route('leagueRoute')) {
            $league = League::where('route', $request->route('leagueRoute'))->first();

            if (! $league) {
                throw ValidationException::withMessages(['message' => 'League not found']);
            }

            $concat = 'CONCAT(YEAR(MIN(point_events.init_at)), \'/\', IF (MONTH(MIN(point_events.init_at)) BETWEEN 1 AND 6, \'1\', \'2\'))';
            $ifCondition = 'YEAR(MIN(point_events.init_at)) IS NOT NULL';

            $periods = Tournament::selectRaw('IF('.$ifCondition.', '.$concat.', NULL) AS period')
                ->distinct()
                ->where([['tournaments.league_id', '=', $league->id]])
                ->leftJoin('point_events', 'point_events.tournament_id', '=', 'tournaments.id');

            $periodList = [];
            $periodTmp = [];

            foreach ($periods->get() as $period) {
                $periodTmp['value'] = $period->period == null ? '' : $period->period;
                $periodTmp['title'] = $period->period == null ? 'Sem data definida' : $period->period;
                $periodList[] = $periodTmp;
                $periodTmp = [];
            }

            return response()->json(['message' => $periodList], 200);
        }

        throw ValidationException::withMessages(['message' => 'League not found']);
    }

    public function showOnLeagueDashboard(Request $request)
    {
        if (! $this->verifyLeagueUserAuthorization($request->user('sanctum')->id, $request->route('leagueRoute'), 'config', 'edit_league_tournaments')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ((bool) $request->route('leagueRoute')) {
            $league = League::where('route', $request->route('leagueRoute'))->first();

            if (! $league) {
                $org = \App\Models\Organization\Organization::where('route', $request->route('leagueRoute'))->first();
                if ($org) {
                    return response()->json(['message' => []], 200);
                }
                throw ValidationException::withMessages(['message' => 'League not found']);
            }

            $tournaments = Tournament::select([
                'tournaments.*',
                'categories.name AS category',
                'subcategories.name AS subcategory',
                DB::raw('COUNT(point_events.id) AS events_qtd'),
                DB::raw('COUNT(tournament_subscriptions.id) AS subscriptions_qtd'),
            ])
                ->distinct()
                ->leftJoin('subcategories', 'subcategories.id', '=', 'tournaments.subcategory_id')
                ->leftJoin('categories', 'categories.id', '=', 'subcategories.category_id')
                ->leftJoin('point_events', 'point_events.tournament_id', '=', 'tournaments.id')
                ->leftJoin('tournament_subscriptions', 'tournament_subscriptions.tournament_id', '=', 'tournaments.id')
                ->where([['tournaments.league_id', '=', $league->id]])
                ->groupby([
                    'tournaments.id',
                    'categories.name',
                    'subcategories.name',
                ]);

            if (! $tournaments) {
                throw ValidationException::withMessages(['message' => 'Tournament not found']);
            }

            if ($request->filled('not_started')) {
                $tournaments = $tournaments->whereRaw('MIN(point_events.init_at) < NOW()');
            }

            if ($request->filled('period')) {
                $validatedPeriod = Validator::make($request->only('period'), [
                    'period' => 'required|regex:/^[0-9]{4}[\'\/\'][1-2]{1}$/',
                ]);

                if ($validatedPeriod->fails()) {
                    return response()->json(['message' => 'Period invalid']);
                }

                $period = explode('/', $request->only('period')['period']);
                $year = $period[0];
                $month = $period[1] == '1' ? ['1', '6'] : ['7', '12'];

                $tournaments = $tournaments->whereYear('point_events.init_at', '=', $year)
                    ->whereRaw('MONTH(`point_events`.`init_at`) BETWEEN \''.$month[0].'\' AND \''.$month[1].'\'');
            } else {
                $tournaments = $tournaments->whereRaw('YEAR(point_events.init_at) IS NULL');
            }

            return response()->json(['message' => $tournaments->get()], 200);
        }
    }

    public function search(Request $request)
    {
        $query = Tournament::select([
            'tournaments.id',
            'tournaments.name',
            'tournaments.description',
            'tournaments.route',
            'tournaments.price',
            'tournaments.subscription_limit',
            'runmodes.key AS runmode',
            'leagues.name AS league_name',
            'leagues.route AS league_route',
            'categories.name AS category_name',
            'categories.route AS category_route',
            'subcategories.name AS subcategory_name',
            'subcategories.route AS subcategory_route',
            'currencies.iso_code AS currency_iso',
            DB::raw('COUNT(DISTINCT ts.id) AS subscription_count'),
            DB::raw('MIN(pe.init_at) AS start_at'),
        ])
            ->join('leagues', 'leagues.id', '=', 'tournaments.league_id')
            ->join('subcategories', 'subcategories.id', '=', 'tournaments.subcategory_id')
            ->join('categories', 'categories.id', '=', 'subcategories.category_id')
            ->join('currencies', 'currencies.id', '=', 'tournaments.currency_id')
            ->leftJoin('runmodes', 'runmodes.id', '=', 'tournaments.runmode_id')
            ->leftJoin('tournaments_subscriptions AS ts', 'ts.tournament_id', '=', 'tournaments.id')
            ->leftJoin('point_events AS pe', 'pe.tournament_id', '=', 'tournaments.id')
            ->groupBy([
                'tournaments.id', 'tournaments.name', 'tournaments.description',
                'tournaments.route', 'tournaments.price', 'tournaments.subscription_limit',
                'runmodes.key',
                'leagues.name', 'leagues.route',
                'categories.name', 'categories.route',
                'subcategories.name', 'subcategories.route',
                'currencies.iso_code',
            ]);

        if ($request->filled('name')) {
            $keywords = preg_split('/\s+/', trim($request->name));
            foreach ($keywords as $kw) {
                $query->where(function ($q) use ($kw) {
                    $q->where('tournaments.name', 'LIKE', "%{$kw}%")
                        ->orWhere('tournaments.description', 'LIKE', "%{$kw}%")
                        ->orWhere('leagues.name', 'LIKE', "%{$kw}%");
                });
            }
        }

        if ($request->filled('category')) {
            $categories = (array) $request->category;
            $query->whereIn('categories.route', $categories);
        }

        if ($request->filled('subcategory')) {
            $subcategories = (array) $request->subcategory;
            $query->whereIn('subcategories.route', $subcategories);
        }

        if ($request->filled('runmode')) {
            $query->where('runmodes.key', $request->runmode);
        }

        if ($request->filled('price_min')) {
            $query->where('tournaments.price', '>=', (float) $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('tournaments.price', '<=', (float) $request->price_max);
        }

        if ($request->filled('date_from')) {
            $query->havingRaw('MIN(pe.init_at) >= ?', [$request->date_from]);
        }

        if ($request->filled('date_to')) {
            $query->havingRaw('MIN(pe.init_at) <= ?', [$request->date_to]);
        }

        $fillMin = $request->filled('fill_min') ? (int) $request->fill_min : null;
        $fillMax = $request->filled('fill_max') ? (int) $request->fill_max : null;

        if ($fillMin !== null) {
            $query->havingRaw(
                '(COUNT(ts.id) / NULLIF(tournaments.subscription_limit, 0)) * 100 >= ?',
                [$fillMin]
            );
        }

        if ($fillMax !== null) {
            $query->havingRaw(
                '(COUNT(ts.id) / NULLIF(tournaments.subscription_limit, 0)) * 100 <= ?',
                [$fillMax]
            );
        }

        $perPage = min((int) ($request->per_page ?? 12), 50);
        $results = $query->paginate($perPage);

        $results->getCollection()->transform(function ($t) {
            $logoPath = storage_path("app/public/league/{$t->league_route}/tournament/{$t->route}/logo.webp");
            $t->logo_url = file_exists($logoPath)
                ? "/storage/league/{$t->league_route}/tournament/{$t->route}/logo.webp"
                : null;

            return $t;
        });

        return response()->json(['message' => $results], 200);
    }

    public function showDetails(Request $request)
    {
        if ($request->filled('leagueRoute')) {
            $league = League::where('route', $request->route('leagueRoute'))->first();

            if (! $league) {
                throw ValidationException::withMessages(['message' => 'League not found']);
            }

            if ($request->filled('tournamentRoute')) {
                $tournament = Tournament::where([
                    ['route', '=', $request->route('tournamentRoute')],
                    ['league_id', '=', $league->id],
                ])->first();

                if (! $tournament) {
                    throw ValidationException::withMessages(['message' => 'Tournament not found']);
                }

                return response()->json(['message' => $tournament], 200);
            }
            $where = [];
            $whereBetween = [];

            if ($request->filled('not_started')) {
                $sqlResult = $sqlResult->whereRaw('MIN(pe.init_at) < NOW()');
            }

            if ($request->filled('period')) {
                $period = explode('-', $request->only('period')['period']);
                $year = $period[0];
                $month = $period[1] == '1' ? ['1', '6'] : ['7', '12'];

                $sqlResult = $sqlResult->whereYear('pe.init_at', '=', $year)
                    ->whereRaw('MONTH(pe.init_at) BETWEEN \''.$month[0].'\' AND \''.$month[1].'\'');
            }

            return response()->json(['message' => $sqlResult->get()], 200);
        }
    }
}
