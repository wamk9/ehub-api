<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $irl = DB::table('runmodes')->where('key', 'irl')->value('id');
        $online = DB::table('runmodes')->where('key', 'online')->value('id');
        $brl = DB::table('currencies')->where('currency_iso_code', 'BRL')->value('id');
        $usd = DB::table('currencies')->where('currency_iso_code', 'USD')->value('id');
        $sub = fn (string $r) => DB::table('subcategories')->where('route', $r)->value('id');

        $leagues = [
            'simracing-brasil' => 'SimRacing Brasil',
            'iracers-br' => 'iRacers BR',
            'ams2-racing-club' => 'AMS2 Racing Club',
            'rally-virtual-br' => 'Rally Virtual BR',
            'console-racers-br' => 'Console Racers BR',
            'atletismo-poa' => 'Atletismo POA',
            'pedal-sp' => 'Pedal SP',
            'sc-triathlon' => 'SC Triathlon',
            'run-recife' => 'Run Recife',
            'motorsport-litoral' => 'Motorsport Litoral',
        ];
        $lids = [];
        foreach ($leagues as $route => $name) {
            $id = Str::uuid()->toString();
            DB::table('leagues')->insert(['id' => $id, 'name' => $name, 'route' => $route]);
            $lids[$route] = $id;
        }

        $tournaments = [
            ['l' => 'simracing-brasil',  'r' => 'copa-assetto-s6',        'sub' => 'gt3',         'mode' => $online, 'price' => 50.0,  'limit' => 20,  'cur' => $brl],
            ['l' => 'iracers-br',        'r' => 'iracing-copa-brasil-s2', 'sub' => 'formula',     'mode' => $online, 'price' => 0,     'limit' => 24,  'cur' => $brl],
            ['l' => 'ams2-racing-club',  'r' => 'ams2-gt3-cup-2026',      'sub' => 'gt3',         'mode' => $online, 'price' => 0,     'limit' => 20,  'cur' => $brl],
            ['l' => 'rally-virtual-br',  'r' => 'dirt-rally-nacional-2026', 'sub' => 'rally',      'mode' => $online, 'price' => 0,     'limit' => 18,  'cur' => $brl],
            ['l' => 'console-racers-br', 'r' => 'forza-liga-br-s3',       'sub' => 'touring-car', 'mode' => $online, 'price' => 25.0,  'limit' => 16,  'cur' => $usd],
            ['l' => 'simracing-brasil',  'r' => 'copa-gt4-temporada-1',   'sub' => 'gt4',         'mode' => $online, 'price' => 35.0,  'limit' => 24,  'cur' => $brl],
            ['l' => 'atletismo-poa',     'r' => 'maratona-poa-2026',      'sub' => 'marathon',    'mode' => $irl,    'price' => 80.0,  'limit' => 200, 'cur' => $brl],
            ['l' => 'run-recife',        'r' => '5km-noturno-recife',     'sub' => '5km',         'mode' => $irl,    'price' => 0,     'limit' => 100, 'cur' => $brl],
            ['l' => 'pedal-sp',          'r' => 'gran-ride-sp-2026',      'sub' => 'road',        'mode' => $irl,    'price' => 30.0,  'limit' => 60,  'cur' => $brl],
            ['l' => 'sc-triathlon',      'r' => 'triathlon-floripa-2026', 'sub' => 'olympic',     'mode' => $irl,    'price' => 150.0, 'limit' => 100, 'cur' => $brl],
            ['l' => 'motorsport-litoral', 'r' => 'rally-costa-verde-2026', 'sub' => 'rally-cross', 'mode' => $irl,    'price' => 200.0, 'limit' => 15,  'cur' => $brl],
        ];
        $tids = [];
        foreach ($tournaments as $t) {
            $id = Str::uuid()->toString();
            DB::table('tournaments')->insert([
                'id' => $id, 'name' => $t['r'], 'description' => 'desc.'.$t['r'],
                'route' => $t['r'], 'price' => $t['price'], 'subscription_limit' => $t['limit'],
                'subcategory_id' => $sub($t['sub']), 'league_id' => $lids[$t['l']],
                'currency_id' => $t['cur'], 'runmode_id' => $t['mode'],
            ]);
            $tids[$t['r']] = $id;
        }

        $events = [
            ['t' => 'copa-assetto-s6',         'n' => 'round-1',   'd' => '45', 'i' => '2026-02-01 20:00:00'],
            ['t' => 'copa-assetto-s6',         'n' => 'round-2',   'd' => '45', 'i' => '2026-02-15 20:00:00'],
            ['t' => 'copa-assetto-s6',         'n' => 'round-3',   'd' => '45', 'i' => '2026-03-01 20:00:00'],
            ['t' => 'copa-assetto-s6',         'n' => 'round-4',   'd' => '45', 'i' => '2026-03-15 20:00:00'],
            ['t' => 'copa-assetto-s6',         'n' => 'round-5',   'd' => '45', 'i' => '2026-04-05 20:00:00'],
            ['t' => 'iracing-copa-brasil-s2',  'n' => 'round-1',   'd' => '60', 'i' => '2026-03-05 21:00:00'],
            ['t' => 'iracing-copa-brasil-s2',  'n' => 'round-2',   'd' => '60', 'i' => '2026-03-19 21:00:00'],
            ['t' => 'iracing-copa-brasil-s2',  'n' => 'round-3',   'd' => '60', 'i' => '2026-04-02 21:00:00'],
            ['t' => 'iracing-copa-brasil-s2',  'n' => 'round-4',   'd' => '60', 'i' => '2026-04-16 21:00:00'],
            ['t' => 'ams2-gt3-cup-2026',       'n' => 'round-1',   'd' => '40', 'i' => '2026-02-10 20:00:00'],
            ['t' => 'ams2-gt3-cup-2026',       'n' => 'round-2',   'd' => '40', 'i' => '2026-03-10 20:00:00'],
            ['t' => 'ams2-gt3-cup-2026',       'n' => 'round-3',   'd' => '40', 'i' => '2026-04-10 20:00:00'],
            ['t' => 'dirt-rally-nacional-2026', 'n' => 'stage-1',   'd' => '30', 'i' => '2026-03-07 19:00:00'],
            ['t' => 'dirt-rally-nacional-2026', 'n' => 'stage-2',   'd' => '30', 'i' => '2026-03-14 19:00:00'],
            ['t' => 'dirt-rally-nacional-2026', 'n' => 'stage-3',   'd' => '30', 'i' => '2026-03-21 19:00:00'],
            ['t' => 'dirt-rally-nacional-2026', 'n' => 'stage-4',   'd' => '30', 'i' => '2026-03-28 19:00:00'],
            ['t' => 'dirt-rally-nacional-2026', 'n' => 'stage-5',   'd' => '30', 'i' => '2026-04-04 19:00:00'],
            ['t' => 'dirt-rally-nacional-2026', 'n' => 'stage-6',   'd' => '30', 'i' => '2026-04-11 19:00:00'],
            ['t' => 'forza-liga-br-s3',        'n' => 'round-1',   'd' => '35', 'i' => '2026-03-15 20:00:00'],
            ['t' => 'forza-liga-br-s3',        'n' => 'round-2',   'd' => '35', 'i' => '2026-03-29 20:00:00'],
            ['t' => 'forza-liga-br-s3',        'n' => 'round-3',   'd' => '35', 'i' => '2026-04-12 20:00:00'],
            ['t' => 'forza-liga-br-s3',        'n' => 'round-4',   'd' => '35', 'i' => '2026-04-26 20:00:00'],
            ['t' => 'copa-gt4-temporada-1',    'n' => 'round-1',   'd' => '40', 'i' => '2026-02-05 20:30:00'],
            ['t' => 'copa-gt4-temporada-1',    'n' => 'round-2',   'd' => '40', 'i' => '2026-02-19 20:30:00'],
            ['t' => 'copa-gt4-temporada-1',    'n' => 'round-3',   'd' => '40', 'i' => '2026-03-05 20:30:00'],
            ['t' => 'copa-gt4-temporada-1',    'n' => 'round-4',   'd' => '40', 'i' => '2026-03-19 20:30:00'],
            ['t' => 'copa-gt4-temporada-1',    'n' => 'round-5',   'd' => '40', 'i' => '2026-04-02 20:30:00'],
            ['t' => 'maratona-poa-2026',       'n' => 'largada',   'd' => '360', 'i' => '2026-04-05 07:00:00'],
            ['t' => '5km-noturno-recife',      'n' => 'largada',   'd' => '60', 'i' => '2026-02-28 19:00:00'],
            ['t' => 'gran-ride-sp-2026',       'n' => 'largada',   'd' => '180', 'i' => '2026-04-12 07:30:00'],
            ['t' => 'triathlon-floripa-2026',  'n' => 'largada',   'd' => '120', 'i' => '2026-03-22 06:30:00'],
            ['t' => 'rally-costa-verde-2026',  'n' => 'especial-1', 'd' => '30', 'i' => '2026-05-10 08:00:00'],
            ['t' => 'rally-costa-verde-2026',  'n' => 'especial-2', 'd' => '30', 'i' => '2026-05-10 11:00:00'],
            ['t' => 'rally-costa-verde-2026',  'n' => 'especial-3', 'd' => '30', 'i' => '2026-05-10 14:00:00'],
        ];
        foreach ($events as $e) {
            DB::table('point_events')->insert([
                'id' => Str::uuid(), 'name' => $e['n'], 'route' => $e['n'],
                'duration' => $e['d'], 'init_at' => $e['i'], 'tournament_id' => $tids[$e['t']],
            ]);
        }
    }
}
