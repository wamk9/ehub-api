<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateDemoValuesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $data = [
            [
                'name' => 'Administrator',
                'email' => 'admin@demo.com.br',
                'steam_id' => '00000000000000001',
                'password' => Hash::make('123456'),
            ],
            [
                'name' => 'Usuário Demonstração',
                'email' => 'user@demo.com.br',
                'steam_id' => '00000000000000002',
                'password' => Hash::make('123456'),
            ],
        ];

        DB::table('users')->insert($data);

        $data = [
            'name' => 'JohnJohn 3D Motorsports',
            'description' => 'Descrição de testes direto do DB.',
        ];

        DB::table('teams')->insert($data);

        $data = [
            'team_id' => '1',
            'member_id' => '3',
            'is_admin' => true,
        ];

        DB::table('teams_members')->insert($data);

        $data = [
            [
                'user_id' => '3',
                'title' => 'Bem-vindo!',
                'description' => 'Estamos felizes que você chegou!',
                'route' => '#',
                'created_at' => now()->toDateString(),
            ],
            [
                'user_id' => '3',
                'title' => 'Inscrição realizada!',
                'description' => 'Você se inscreveu em um campeonato.',
                'route' => '#',
                'created_at' => now()->toDateString(),
            ],
        ];

        DB::table('notifications')->insert($data);
    }
}
