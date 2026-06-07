<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreateSubCategoriesValuesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'name' => 'Kart',
                'route' => 'kart',
                'category_id' => 1,
            ],
            [
                'name' => 'Assetto Corsa: Competizione',
                'route' => 'acc',
                'category_id' => 2,
            ],
            [
                'name' => 'F1 2023',
                'route' => 'f1-2023',
                'category_id' => 3,
            ],
            [
                'name' => 'Forza Motorsport',
                'route' => 'forza-motorsport',
                'category_id' => 4,
            ],
        ];

        DB::table('subcategories')->insert($data);
    }
}
