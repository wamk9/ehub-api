<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreateCategoriesValuesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'name' => 'IRL',
                'description' => 'Buscando eventos no mundo real? O lugar é aqui!',
                'route' => 'irl',
            ],
            [
                'name' => 'PC',
                'description' => 'Competições para aqueles que são do time \'PC Master Race!\'',
                'route' => 'pc',
            ],
            [
                'name' => 'PlayStation',
                'description' => 'Aqui você encontrará competições específicas para jogos de PlayStation!',
                'route' => 'playstation',
            ],
            [
                'name' => 'Xbox',
                'description' => 'Aqui você encontrará competições específicas para jogos de Xbox!',
                'route' => 'xbox',
            ],
        ];

        DB::table('categories')->insert($data);

    }
}
