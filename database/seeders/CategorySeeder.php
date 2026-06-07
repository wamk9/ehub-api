<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $irl = DB::table('runmodes')->where('key', 'irl')->value('id');
        $online = DB::table('runmodes')->where('key', 'online')->value('id');

        $categories = [
            'simracing' => ['runmodes' => [$online], 'subcategories' => ['gt3', 'gt4', 'formula', 'kart', 'touring-car', 'rally', 'drift', 'oval', 'motorbike-sim', 'other']],
            'esports-fps' => ['runmodes' => [$online], 'subcategories' => ['cs2', 'valorant', 'rainbow-six', 'other']],
            'esports-moba' => ['runmodes' => [$online], 'subcategories' => ['lol', 'dota2', 'other']],
            'esports-fighting' => ['runmodes' => [$online], 'subcategories' => ['sf6', 'tekken8', 'mortal-kombat', 'other']],
            'esports-strategy' => ['runmodes' => [$online], 'subcategories' => ['starcraft2', 'age-of-empires', 'other']],
            'esports-sports' => ['runmodes' => [$online], 'subcategories' => ['fifa', 'nba2k', 'other']],
            'motorsport' => ['runmodes' => [$irl],    'subcategories' => ['kart', 'formula', 'gt', 'stock-car', 'rally', 'rally-cross', 'drift', 'touring-car', 'other']],
            'motorbike' => ['runmodes' => [$irl],    'subcategories' => ['superbike', 'motocross', 'enduro', 'trial', 'other']],
            'cycling' => ['runmodes' => [$irl],    'subcategories' => ['road', 'mtb', 'bmx', 'track', 'gravel', 'other']],
            'running' => ['runmodes' => [$irl],    'subcategories' => ['5km', '10km', 'half-marathon', 'marathon', 'ultra-trail', 'other']],
            'swimming' => ['runmodes' => [$irl],    'subcategories' => ['pool-25m', 'pool-50m', 'open-water', 'other']],
            'triathlon' => ['runmodes' => [$irl],    'subcategories' => ['sprint', 'olympic', 'half-ironman', 'ironman', 'other']],
            'hiking' => ['runmodes' => [$irl],    'subcategories' => ['trail', 'trekking', 'via-ferrata', 'other']],
            'crossfit' => ['runmodes' => [$irl],    'subcategories' => ['individual', 'team-3', 'team-6', 'other']],
            'rowing' => ['runmodes' => [$irl],    'subcategories' => ['sculling', 'sweep', 'coastal', 'other']],
            'archery' => ['runmodes' => [$irl],    'subcategories' => ['recurve', 'compound', 'barebow', 'other']],
            'chess' => ['runmodes' => [$irl, $online], 'subcategories' => ['classical', 'rapid', 'blitz', 'bullet', 'other']],
            'drone-racing' => ['runmodes' => [$irl, $online], 'subcategories' => ['freestyle', 'racing', 'longrange', 'other']],
        ];

        foreach ($categories as $route => $data) {
            $categoryId = Str::uuid()->toString();
            DB::table('categories')->insert(['id' => $categoryId, 'name' => $route, 'route' => $route]);
            foreach ($data['runmodes'] as $runmodeId) {
                DB::table('category_runmode')->insert(['category_id' => $categoryId, 'runmode_id' => $runmodeId]);
            }
            foreach ($data['subcategories'] as $subRoute) {
                DB::table('subcategories')->insert(['id' => Str::uuid()->toString(), 'name' => $subRoute, 'route' => $subRoute, 'category_id' => $categoryId]);
            }
        }
    }
}
