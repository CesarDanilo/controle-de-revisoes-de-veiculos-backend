<?php

namespace Database\Factories;

use App\Models\Brands;
use App\Models\People;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    // VehicleFactory.php
    public function definition(): array
    {
        return [
            // Sem recycle() no seeder, isso cria brand/people novos (sem user_id).
            // Com recycle(), o Laravel reaproveita os já criados pro mesmo usuário.
            'brand_id' => Brands::factory(),
            'people_id' => People::factory(),

            'model' => fake()->randomElement([
                'Civic', 'Corolla', 'Onix', 'Argo', 'HB20',
                'Compass', 'Polo', 'Tracker', 'Renegade',
            ]),

            'year' => fake()->numberBetween(2014, 2026),
            'color' => fake()->safeColorName(),
            'license_plate' => strtoupper(fake()->bothify('???#?##')),
        ];
    }
}
