<?php

namespace Database\Seeders;

use App\Models\Brands;
use App\Models\Colors;
use App\Models\People;
use App\Models\Revisions;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    // 🟢 Ajuste esses números pra controlar o volume total gerado
    private const TOTAL_PEOPLE = 500;
    private const VEHICLES_PER_PERSON_MIN = 1;
    private const VEHICLES_PER_PERSON_MAX = 6;
    private const REVISIONS_PER_VEHICLE_MIN = 1;
    private const REVISIONS_PER_VEHICLE_MAX = 12;

    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Cesar',
            'email' => 'cesar@example.com',
        ]);

        // Pool fixo de marcas/cores, reaproveitado por todos os veículos.
        $brands = Brands::factory(20)->create([
            'user_id' => $user->id,
        ]);

        $colors = Colors::factory(12)->create();

        // 🟢 NOVO — cria as pessoas em lote (rápido: 1 insert em massa),
        // depois processa em chunks pra não estourar memória com uma
        // Collection gigante de milhares de registros carregados de vez.
        People::factory(self::TOTAL_PEOPLE)
            ->create(['user_id' => $user->id]);

        People::where('user_id', $user->id)
            ->orderBy('id')
            ->chunk(50, function ($peopleChunk) use ($user, $brands, $colors) {
                foreach ($peopleChunk as $person) {
                    $vehicleCount = fake()->numberBetween(
                        self::VEHICLES_PER_PERSON_MIN,
                        self::VEHICLES_PER_PERSON_MAX
                    );

                    $vehicles = Vehicle::factory($vehicleCount)
                        ->recycle($brands)
                        ->recycle($colors)
                        ->create([
                            'user_id' => $user->id,
                            'people_id' => $person->id,
                        ]);

                    foreach ($vehicles as $vehicle) {
                        $revisionCount = fake()->numberBetween(
                            self::REVISIONS_PER_VEHICLE_MIN,
                            self::REVISIONS_PER_VEHICLE_MAX
                        );

                        Revisions::factory($revisionCount)->create([
                            'user_id' => $user->id,
                            'vehicle_id' => $vehicle->id,
                        ]);
                    }
                }
            });
    }
}