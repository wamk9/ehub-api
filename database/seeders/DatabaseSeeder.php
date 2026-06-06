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
            RunmodeSeeder::class,
            CurrencySeeder::class,
            CategorySeeder::class,
            DemoSeeder::class,
        ];

        $this->call($seedersToCall);
    }
}
