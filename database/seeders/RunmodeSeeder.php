<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RunmodeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['irl', 'online'] as $key) {
            DB::table('runmodes')->insert(['id' => Str::uuid(), 'key' => $key]);
        }
    }
}
