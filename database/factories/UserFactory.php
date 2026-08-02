<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            // 🟢 explícito — a coluna tem default(true) no banco, mas
            // deixamos aqui pra ficar claro que todo usuário criado pela
            // factory nasce ativo, e pra permitir sobrescrever com
            // ->inactive() sem depender do default da migration.
            'status' => true,
            // google_id fica de fora de propósito: é nullable/unique e
            // só é preenchido no login via Google.
            // 🔴 REMOVIDO — a tabela users não tem coluna email_verified_at
            // (confirmado pelo erro "column does not exist"). Se um dia
            // você adicionar essa coluna via migration, pode voltar a
            // usar 'email_verified_at' => now() aqui e reativar o método
            // unverified() abaixo.
        ];
    }

    /**
     * Indicate that the user is inactive (status = false).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }
}