<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreatePlansValuesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            // BRL
            [
                'name' => 'Grátis',
                'price' => 0,
                'recurrence' => 'monthly',
                'currency_id' => 1,
                'max_tournament' => 1,
                'max_event' => 4,
                'recurring_payment' => false,
                'protests' => false,
            ],
            [
                'name' => 'Plano 1',
                'price' => 30,
                'recurrence' => 'monthly',
                'currency_id' => 1,
                'max_tournament' => 2,
                'max_event' => 6,
                'recurring_payment' => false,
                'protests' => true,
            ],
            [
                'name' => 'Plano 2',
                'price' => 45,
                'recurrence' => 'monthly',
                'currency_id' => 1,
                'max_tournament' => 4,
                'max_event' => 8,
                'recurring_payment' => false,
                'protests' => true,
            ],
            [
                'name' => 'Plano 3',
                'price' => 60,
                'recurrence' => 'monthly',
                'currency_id' => 1,
                'max_tournament' => 8,
                'max_event' => 12,
                'recurring_payment' => true,
                'protests' => true,
            ],

            //USD
            [
                'name' => 'Free',
                'price' => 0,
                'recurrence' => 'monthly',
                'currency_id' => 2,
                'max_tournament' => 1,
                'max_event' => 4,
                'recurring_payment' => false,
                'protests' => false,
            ],
            [
                'name' => 'Plan 1',
                'price' => 15,
                'recurrence' => 'monthly',
                'currency_id' => 2,
                'max_tournament' => 2,
                'max_event' => 6,
                'recurring_payment' => false,
                'protests' => true,
            ],
            [
                'name' => 'Plan 2',
                'price' => 20,
                'recurrence' => 'monthly',
                'currency_id' => 2,
                'max_tournament' => 4,
                'max_event' => 8,
                'recurring_payment' => false,
                'protests' => true,
            ],
            [
                'name' => 'Plan 3',
                'price' => 30,
                'recurrence' => 'monthly',
                'currency_id' => 2,
                'max_tournament' => 8,
                'max_event' => 12,
                'recurring_payment' => true,
                'protests' => true,
            ],
        ];

        DB::table('plans')->insert($data);
    }
}
