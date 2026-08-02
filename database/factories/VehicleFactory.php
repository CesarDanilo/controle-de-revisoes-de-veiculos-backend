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
            'color_id' => Colors::factory(),
            'people_id' => People::factory(),

            // Lista fixa: garante que 'model' NUNCA sai vazio (é NOT NULL
            // no banco e obrigatório na validação — o frontend depende
            // disso pra renderizar o card do veículo).
            'model' => fake()->randomElement([
                'Civic', 'Corolla', 'Onix', 'Argo', 'HB20',
                'Compass', 'Polo', 'Tracker', 'Renegade',
            ]),

            'year' => fake()->numberBetween(2014, 2026),

            // 🔧 CORRIGIDO — faltava ->unique() aqui. Sem isso, em uma
            // massa grande de registros (ex: 250+ veículos no seeder),
            // existe uma chance real de duas placas geradas serem iguais,
            // o que quebraria a constraint unique(user_id, license_plate)
            // e derrubaria o seeder inteiro no meio da execução.
            'license_plate' => strtoupper(fake()->unique()->bothify('???#?##')),
        ];
    }
}