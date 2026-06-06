<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventFormSchemaSeeder extends Seeder
{
    public function run(): void
    {
        $irl    = DB::table('runmodes')->where('key', 'irl')->value('id');
        $online = DB::table('runmodes')->where('key', 'online')->value('id');

        $catId = fn(string $route) => DB::table('categories')->where('route', $route)->value('id');

        $schemas = [
            // SimRacing — online only
            ['category' => 'simracing', 'runmode' => $online, 'form' => $this->simracingOnline()],

            // E-Sports — online only
            ['category' => 'esports-fps',      'runmode' => $online, 'form' => $this->esportsBase(['cs2','valorant','rainbow-six','other'])],
            ['category' => 'esports-moba',     'runmode' => $online, 'form' => $this->esportsBase(['lol','dota2','other'])],
            ['category' => 'esports-fighting', 'runmode' => $online, 'form' => $this->esportsBase(['sf6','tekken8','mortal-kombat','other'])],
            ['category' => 'esports-strategy', 'runmode' => $online, 'form' => $this->esportsBase(['starcraft2','age-of-empires','other'])],
            ['category' => 'esports-sports',   'runmode' => $online, 'form' => $this->esportsBase(['fc25','nba2k','other'])],

            // IRL sports
            ['category' => 'motorsport', 'runmode' => $irl, 'form' => $this->motorsportIrl()],
            ['category' => 'motorbike',  'runmode' => $irl, 'form' => $this->irlBase()],
            ['category' => 'cycling',    'runmode' => $irl, 'form' => $this->cyclingIrl()],
            ['category' => 'running',    'runmode' => $irl, 'form' => $this->runningIrl()],
            ['category' => 'swimming',   'runmode' => $irl, 'form' => $this->irlBase()],
            ['category' => 'triathlon',  'runmode' => $irl, 'form' => $this->irlBase()],
            ['category' => 'hiking',     'runmode' => $irl, 'form' => $this->irlBase()],
            ['category' => 'crossfit',   'runmode' => $irl, 'form' => $this->irlBase()],
            ['category' => 'rowing',     'runmode' => $irl, 'form' => $this->irlBase()],
            ['category' => 'archery',    'runmode' => $irl, 'form' => $this->irlBase()],

            // Both modes
            ['category' => 'chess',        'runmode' => $irl,    'form' => $this->chess()],
            ['category' => 'chess',        'runmode' => $online, 'form' => $this->chess()],
            ['category' => 'drone-racing', 'runmode' => $irl,    'form' => $this->irlBase()],
            ['category' => 'drone-racing', 'runmode' => $online, 'form' => $this->esportsBase(['freestyle','racing','longrange','other'])],
        ];

        foreach ($schemas as $s) {
            $id = $catId($s['category']);
            if (!$id) continue;

            DB::table('event_form_schemas')->insert([
                'id'             => Str::uuid()->toString(),
                'category_id'    => $id,
                'subcategory_id' => null,
                'runmode_id'     => $s['runmode'],
                'form_json'      => json_encode($s['form']),
                'created_at'     => now(),
            ]);
        }
    }

    // ─── Basic form (universal step 1, stored separately) ────────────────────

    public static function basicForm(): array
    {
        return [
            'form' => [[
                [
                    'independentRow' => true,
                    'sizes'          => ['xs' => 12],
                    'inputs'         => [
                        ['name' => 'event-name',              'type' => 'text',     'eventValue' => '', 'validate' => ['regex' => '^[\s\S]{5,100}$',       'rewrite' => true, 'onBlur' => true]],
                        ['name' => 'event-endpoint',          'type' => 'text',     'eventValue' => '', 'validate' => ['regex' => '^[a-z0-9\-]{3,60}$',    'rewrite' => true, 'onBlur' => true]],
                        ['name' => 'event-short-description', 'type' => 'text',     'eventValue' => '', 'validate' => ['regex' => '^[\s\S]{0,180}$',        'rewrite' => true, 'onBlur' => true]],
                        ['name' => 'event-description',       'type' => 'textarea', 'eventValue' => '', 'validate' => ['regex' => '^[\s\S]{0,2000}$',       'rewrite' => true, 'onBlur' => true]],
                        ['name' => 'event-currency',          'type' => 'list',     'eventValue' => '', 'inputValue' => ['values' => ['free','brl','usd','eur']], 'validate' => ['regex' => '^[\s\S]{1,10}$', 'rewrite' => true, 'onBlur' => true]],
                        ['name' => 'event-register-fee',      'type' => 'number',   'eventValue' => '', 'validate' => ['regex' => '^\d+(\.\d{1,2})?$',    'rewrite' => true, 'onBlur' => true]],
                    ],
                ],
            ]],
            'data' => [],
        ];
    }

    // ─── Advanced forms ───────────────────────────────────────────────────────

    private function simracingOnline(): array
    {
        $games = ['assetto-corsa','assetto-corsa-competizione','assetto-corsa-evo','automobilista','automobilista-2','forza-motorsport','forza-horizon-5','gran-turismo-7','iracing','rfactor','rfactor-2','live-for-speed','project-cars-2','project-cars-3','race-room-experience','f1-24','f1-23','f1-22','f1-2021','f1-2020','dirt-rally-2-0','dirt-rally','ea-sports-wrc','wrc-generations','beamng-drive','kartkraft','kart-racing-pro','drift21','gp-bikes','mx-bikes','trackmania-2020','trackmania-nations','the-crew-motorfest','other'];

        return ['form' => [[
            ['independentRow' => true, 'sizes' => ['xs' => 12], 'inputs' => [
                ['name' => 'game', 'type' => 'title',       'eventValue' => []],
                ['name' => 'game', 'type' => 'description', 'eventValue' => []],
                ['type' => 'separator'],
            ]],
            ['independentRow' => true, 'sizes' => ['xs' => 12, 'md' => 6, 'lg' => 6], 'offsets' => ['md' => 3, 'lg' => 3], 'inputs' => [
                ['name' => 'game', 'type' => 'list', 'inputValue' => ['values' => $games], 'eventValue' => ''],
            ]],
            ['independentRow' => true, 'sizes' => ['xs' => 12], 'inputs' => [
                ['name' => 'event-settings', 'type' => 'title',       'eventValue' => []],
                ['name' => 'event-settings', 'type' => 'description', 'eventValue' => []],
                ['type' => 'separator'],
            ]],
            ['independentRow' => false, 'sizes' => ['xs' => 12, 'md' => 12, 'lg' => 6], 'inputs' => [
                ['name' => 'consume-percent', 'type' => 'number', 'eventValue' => ''],
                ['name' => 'wear-percent',    'type' => 'number', 'eventValue' => ''],
                ['name' => 'damage-percent',  'type' => 'number', 'eventValue' => ''],
                ['name' => 'real-weather',    'type' => 'switch', 'eventValue' => false],
                ['name' => 'livestream',      'type' => 'switch', 'eventValue' => false],
            ]],
        ]], 'data' => []];
    }

    private function motorsportIrl(): array
    {
        return ['form' => [[
            ['independentRow' => false, 'sizes' => ['xs' => 12, 'md' => 6], 'inputs' => [
                ['name' => 'location',        'type' => 'text',   'eventValue' => ''],
                ['name' => 'track-name',      'type' => 'text',   'eventValue' => ''],
                ['name' => 'livestream',      'type' => 'switch', 'eventValue' => false],
                ['name' => 'tech-inspection', 'type' => 'switch', 'eventValue' => false],
            ]],
        ]], 'data' => []];
    }

    private function runningIrl(): array
    {
        return ['form' => [[
            ['independentRow' => false, 'sizes' => ['xs' => 12, 'md' => 6], 'inputs' => [
                ['name' => 'distance',    'type' => 'list',   'inputValue' => ['values' => ['5km','10km','half-marathon','marathon','ultra-trail','custom']], 'eventValue' => ''],
                ['name' => 'location',    'type' => 'text',   'eventValue' => ''],
                ['name' => 'chip-timing', 'type' => 'switch', 'eventValue' => false],
                ['name' => 'livestream',  'type' => 'switch', 'eventValue' => false],
            ]],
        ]], 'data' => []];
    }

    private function cyclingIrl(): array
    {
        return ['form' => [[
            ['independentRow' => false, 'sizes' => ['xs' => 12, 'md' => 6], 'inputs' => [
                ['name' => 'discipline',  'type' => 'list',   'inputValue' => ['values' => ['road','mtb','bmx','track','gravel','other']], 'eventValue' => ''],
                ['name' => 'location',    'type' => 'text',   'eventValue' => ''],
                ['name' => 'distance-km', 'type' => 'number', 'eventValue' => ''],
                ['name' => 'livestream',  'type' => 'switch', 'eventValue' => false],
            ]],
        ]], 'data' => []];
    }

    private function chess(): array
    {
        return ['form' => [[
            ['independentRow' => false, 'sizes' => ['xs' => 12, 'md' => 6], 'inputs' => [
                ['name' => 'format',       'type' => 'list',   'inputValue' => ['values' => ['classical','rapid','blitz','bullet','other']], 'eventValue' => ''],
                ['name' => 'total-rounds', 'type' => 'number', 'eventValue' => ''],
                ['name' => 'livestream',   'type' => 'switch', 'eventValue' => false],
            ]],
        ]], 'data' => []];
    }

    private function irlBase(): array
    {
        return ['form' => [[
            ['independentRow' => false, 'sizes' => ['xs' => 12, 'md' => 6], 'inputs' => [
                ['name' => 'location',   'type' => 'text',   'eventValue' => ''],
                ['name' => 'livestream', 'type' => 'switch', 'eventValue' => false],
            ]],
        ]], 'data' => []];
    }

    private function esportsBase(array $games): array
    {
        return ['form' => [[
            ['independentRow' => true, 'sizes' => ['xs' => 12, 'md' => 6, 'lg' => 6], 'offsets' => ['md' => 3, 'lg' => 3], 'inputs' => [
                ['name' => 'game', 'type' => 'list', 'inputValue' => ['values' => $games], 'eventValue' => ''],
            ]],
            ['independentRow' => false, 'sizes' => ['xs' => 12, 'md' => 6], 'inputs' => [
                ['name' => 'platform',   'type' => 'list',   'inputValue' => ['values' => ['pc','console','mobile','crossplay']], 'eventValue' => ''],
                ['name' => 'team-size',  'type' => 'number', 'eventValue' => ''],
                ['name' => 'livestream', 'type' => 'switch', 'eventValue' => false],
            ]],
        ]], 'data' => []];
    }
}
