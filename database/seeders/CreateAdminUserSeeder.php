<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $data = [
            'name' => env('SUPPORT_NAME'),
            'email' => env('SUPPORT_MAIL'),
            'steam_id' => env('SUPPORT_STEAMID'),
            'password' => Hash::make(env('SUPPORT_PASS')),
        ];

        DB::table('users')->insert($data);
    }
}
