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

        // 🟢 valores travados na mesma lista das CHECK constraints do banco
        // (migration add_status_to_revisions_table). Se você renomear ou
        // adicionar um status no Enum PHP, atualize aqui também.
        $status = fake()->randomElement([
            'aberto', 'em_andamento', 'aguardando_pagamento', 'concluido', 'cancelado',
        ]);
        $statusPagamento = fake()->randomElement(['pendente', 'pago']);

        return [
            // vehicle_id NÃO tem user_id próprio aqui — quando usado com
            // ->recycle($vehicles) no seeder, o Laravel reaproveita
            // veículos já vinculados ao usuário certo. Se chamar a
            // factory isolada (sem recycle), vai criar um Vehicle novo
            // (sem user_id definido), então sempre passe
            // ->recycle() ou ['vehicle_id' => ...] explicitamente em
            // contextos onde o isolamento por usuário importa.
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
            // next_revision_km sempre maior que o km atual
            'next_revision_km' => fake()->optional(0.5)->numberBetween($km + 5000, $km + 20000),
            'next_revision_date' => fake()->optional(0.5)->dateTimeBetween('+3 months', '+12 months')?->format('Y-m-d'),

            // 🟢 status/status_pagamento explícitos — a coluna tem default
            // no banco ('aberto'/'pendente'), mas deixamos variado aqui pra
            // o Kanban e os filtros por status terem dados realistas nos
            // seeds/testes, em vez de tudo nascer com o mesmo status.
            'status' => $status,
            'status_pagamento' => $statusPagamento,
        ];
    }

    /**
     * Estado explícito pra revisão paga e concluída — útil em testes que
     * precisam de um cenário "fechado" sem depender do sorteio aleatório.
     */
    public function concluida(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'concluido',
            'status_pagamento' => 'pago',
        ]);
    }
}