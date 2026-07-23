<?php

namespace Database\Factories;

use App\Models\Brands;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brands>
 */
class BrandsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
// BrandsFactory.php
    public function definition(): array
    {
        return [
            // Atenção: unique() aqui só funciona porque a lista tem
            // exatamente 20 itens e o seeder cria exatamente 20 brands.
            // Se aumentar a quantidade no seeder, isso vai estourar.
            'name' => fake()->unique()->randomElement([
                'Honda', 'Toyota', 'Volkswagen', 'Fiat', 'Chevrolet',
                'Hyundai', 'Renault', 'Jeep', 'Nissan', 'Ford',
                'BMW', 'Audi', 'Mercedes', 'Peugeot', 'Citroen',
                'Kia', 'Mitsubishi', 'Volvo', 'BYD', 'GWM',
            ]),
        ];
    }
}
