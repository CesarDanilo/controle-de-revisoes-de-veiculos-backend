<?php

namespace Database\Factories;

use App\Models\Revisions;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Revisions>
 */
class RevisionsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $revisionDate = fake()->dateTimeBetween('-2 years', 'now');
        $km = fake()->numberBetween(5000, 150000);

        return [
            'vehicle_id' => Vehicle::factory(),

            'description' => fake()->randomElement([
                'Troca de óleo', 'Troca de pneus', 'Alinhamento', 'Balanceamento',
                'Revisão completa', 'Filtro de ar', 'Filtro de combustível',
                'Pastilhas de freio', 'Troca da bateria',
            ]),

            'revision_date' => $revisionDate->format('Y-m-d'),
            'cost' => fake()->randomFloat(2, 0, 2500),
            'km' => $km,

            // ~50% nulos de propósito, pra exercitar a lógica de ESTIMATIVA
            // do upcomingRevisions() (que só estima quando esses campos são null)
            // 🔴 AQUI — next_revision_km agora é sempre maior que o km atual
            'next_revision_km' => fake()->optional(0.5)->numberBetween($km + 5000, $km + 20000),
            'next_revision_date' => fake()->optional(0.5)->dateTimeBetween('+3 months', '+12 months')?->format('Y-m-d'),
        ];
    }
}