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
                // user_id NÃO é definido aqui de propósito — sempre deve
                // ser passado explicitamente no ->create(['user_id' => ...])
                // pra garantir o isolamento por usuário (mesmo padrão de
                // Brands/Vehicle). Sem isso o insert falha (coluna NOT NULL
                // com foreign key).
                'name' => fake()->company(),
                'email' => fake()->unique()->safeEmail(),
                // só dígitos, sem pontuação (é assim que o PersonFormModal envia)
                'document' => fake()->unique()->numerify('##############'), // 14 dígitos = CNPJ
                'phone' => fake()->numerify('###########'), // 11 dígitos
                'person_type' => 'PJ',
                'gender' => null,
                'birth_date' => null,
            ];
        }

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            // só dígitos, sem pontuação
            'document' => fake()->unique()->numerify('###########'), // 11 dígitos = CPF
            'phone' => fake()->numerify('###########'), // 11 dígitos
            'person_type' => 'PF',
            // idade adulta realista (18 a 80 anos), evita gente "nascida" essa semana
            'birth_date' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['M', 'F', 'O']),
        ];
    }

    /**
     * Estado explícito para gerar Pessoa Jurídica sob demanda,
     * ex: People::factory()->pj()->create([...]).
     */
    public function pj(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->company(),
            'document' => fake()->unique()->numerify('##############'),
            'person_type' => 'PJ',
            'gender' => null,
            'birth_date' => null,
        ]);
    }

    /**
     * Estado explícito para gerar Pessoa Física sob demanda,
     * ex: People::factory()->pf()->create([...]).
     */
    public function pf(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->name(),
            'document' => fake()->unique()->numerify('###########'),
            'person_type' => 'PF',
            'birth_date' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['M', 'F', 'O']),
        ]);
    }
}