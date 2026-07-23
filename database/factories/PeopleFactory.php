<?php

namespace Database\Factories;

use App\Models\People;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<People>
 */
class PeopleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    // PeopleFactory.php
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'document' => fake()->unique()->numerify('###.###.###-##'),
            'phone' => fake()->numerify('(##) 9####-####'),
            // idade adulta realista (18 a 80 anos), evita gente "nascida" essa semana
            'birth_date' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['M', 'F', 'O']),
        ];
    }
}
