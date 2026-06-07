<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['country_iso_code' => 'BR', 'currency_iso_code' => 'BRL'],
            ['country_iso_code' => 'US', 'currency_iso_code' => 'USD'],
        ];
        foreach ($currencies as $c) {
            DB::table('currencies')->insert(['id' => Str::uuid(), ...$c]);
        }
    }
}
