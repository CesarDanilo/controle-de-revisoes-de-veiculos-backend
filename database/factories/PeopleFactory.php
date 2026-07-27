<?php

namespace Database\Factories;

use App\Models\People;
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
    public function definition(): array
    {
        // ~85% Pessoa Física (CPF, com gênero e data de nascimento)
        // ~15% Pessoa Jurídica (CNPJ, sem gênero/nascimento — igual ao PersonFormModal faz)
        $isPJ = fake()->boolean(15);

        if ($isPJ) {
            return [
                'name' => fake()->company(),
                'email' => fake()->unique()->safeEmail(),
                // 🔴 AQUI — só dígitos, sem pontuação (é assim que o PersonFormModal envia)
                'document' => fake()->unique()->numerify('##############'), // 14 dígitos = CNPJ
                'phone' => fake()->numerify('###########'), // 11 dígitos
                'person_type' => 'PJ', // remova esta linha se a coluna não existir na sua tabela
                'gender' => null,
                'birth_date' => null,
            ];
        }

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            // 🔴 AQUI — só dígitos, sem pontuação
            'document' => fake()->unique()->numerify('###########'), // 11 dígitos = CPF
            'phone' => fake()->numerify('###########'), // 11 dígitos
            'person_type' => 'PF', // remova esta linha se a coluna não existir na sua tabela
            // idade adulta realista (18 a 80 anos), evita gente "nascida" essa semana
            'birth_date' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['M', 'F', 'O']),
        ];
    }
}