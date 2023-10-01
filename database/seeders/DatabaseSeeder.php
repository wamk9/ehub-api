<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $seedersToCall = [
            CreateAdminUserSeeder::class,
            CreateCurrenciesValuesSeeder::class,
            CreatePlansValuesSeeder::class,
            CreateSubCategoriesValuesSeeder::class,
            CreateCategoriesValuesSeeder::class,
            CreateDemoValuesSeeder::class
        ];

        $this->call($seedersToCall);
    }
}
