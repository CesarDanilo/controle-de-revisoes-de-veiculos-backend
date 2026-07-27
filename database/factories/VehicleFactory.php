<?php

namespace Database\Factories;

use App\Models\Brands;
use App\Models\Colors;
use App\Models\People;
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
    public function definition(): array
    {
        return [
            // Sem recycle() no seeder, isso cria brand/color/people novos (sem user_id).
            // Com recycle(), o Laravel reaproveita os já criados pro mesmo usuário.
            'brand_id' => Brands::factory(),
            'color_id' => Colors::factory(), // 🔴 AQUI — era 'color' => fake()->safeColorName(), corrigido pra FK
            'people_id' => People::factory(),

            'model' => fake()->randomElement([
                'Civic', 'Corolla', 'Onix', 'Argo', 'HB20',
                'Compass', 'Polo', 'Tracker', 'Renegade',
            ]),

            'year' => fake()->numberBetween(2014, 2026),
            'license_plate' => strtoupper(fake()->bothify('???#?##')),
        ];
    }
}