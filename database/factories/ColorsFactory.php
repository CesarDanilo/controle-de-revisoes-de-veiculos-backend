<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Colors>
 */
class ColorsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Atenção: unique() aqui só funciona porque a lista tem
            // exatamente 12 itens — se o seeder pedir mais de 12 cores, estoura.
            'name' => fake()->unique()->randomElement([
                'Branco', 'Preto', 'Prata', 'Cinza', 'Vermelho',
                'Azul', 'Verde', 'Amarelo', 'Marrom', 'Bege',
                'Laranja', 'Dourado',
            ]),
        ];
    }
}