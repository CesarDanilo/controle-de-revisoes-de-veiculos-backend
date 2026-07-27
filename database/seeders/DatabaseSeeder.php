<?php

namespace Database\Seeders;

use App\Models\Brands;
use App\Models\Colors;
use App\Models\People;
use App\Models\Revisions;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Cesar',
            'email' => 'cesar@example.com',
        ]);

        $brands = Brands::factory(20)->create([
            'user_id' => $user->id,
        ]);

        // colors é uma tabela global, sem user_id
        $colors = Colors::factory(12)->create();

        $people = People::factory(100)->create([
            'user_id' => $user->id,
        ]);

        // recycle() faz o Vehicle::factory() reaproveitar as brands/colors/people
        // já criadas acima, em vez de gerar novas (e sem user_id)
        $vehicles = Vehicle::factory(250)
            ->recycle($brands)
            ->recycle($colors)
            ->recycle($people)
            ->create([
                'user_id' => $user->id,
            ]);

        Revisions::factory(1000)
            ->recycle($vehicles)
            ->create([
                'user_id' => $user->id,
            ]);
    }
}