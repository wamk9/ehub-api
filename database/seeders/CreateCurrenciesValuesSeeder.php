<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreateCurrenciesValuesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'country_iso_code' => 'BR',
                'currency_iso_code' => 'BRL',
            ],
            [
                'country_iso_code' => 'US',
                'currency_iso_code' => 'USD',
            ],
        ];

        DB::table('currencies')->insert($data);
    }
}
