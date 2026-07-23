<?php

namespace Database\Seeders;

use App\Models\Brands;
use App\Models\People;
use App\Models\Revisions;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Cesar',
            'email' => 'cesar@example.com',
        ]);

        $brands = Brands::factory(20)->create([
            'user_id' => $user->id,
        ]);

        $people = People::factory(100)->create([
            'user_id' => $user->id,
        ]);

        // recycle() faz o Vehicle::factory() reaproveitar as brands/people
        // já criadas acima, em vez de gerar novas (e sem user_id)
        $vehicles = Vehicle::factory(250)
            ->recycle($brands)
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