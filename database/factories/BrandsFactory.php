<?php

namespace Database\Factories;

use App\Models\Brands;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Brands>
 */
class BrandsFactory extends Factory
{
    /**
     * Lista fixa de marcas, embaralhada uma vez e consumida a cada chamada
     * de definition(). Evita o erro do fake()->unique()->randomElement(),
     * que quebra (ou repete às cegas) se algum dia pedirem mais marcas do
     * que itens na lista.
     */
    private static ?array $pool = null;

    /**
     * Reseta o pool — chame isso no início de testes/seeders que rodam em
     * sequência na mesma execução do PHP, senão o pool de uma chamada
     * anterior pode continuar consumido (ex: RefreshDatabase entre testes).
     */
    public static function resetPool(): void
    {
        self::$pool = null;
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        if (self::$pool === null) {
            self::$pool = collect([
                'Honda', 'Toyota', 'Volkswagen', 'Fiat', 'Chevrolet',
                'Hyundai', 'Renault', 'Jeep', 'Nissan', 'Ford',
                'BMW', 'Audi', 'Mercedes', 'Peugeot', 'Citroen',
                'Kia', 'Mitsubishi', 'Volvo', 'BYD', 'GWM',
            ])->shuffle()->values()->all();
        }

        if (empty(self::$pool)) {
            // 🔴 AQUI — falha alto e claro em vez de gravar name vazio/null.
            // Se precisar de mais de 20 marcas, adicione mais nomes na
            // lista acima, ou gere menos registros no seeder.
            throw new \RuntimeException(
                'BrandsFactory: lista de marcas esgotada (só 20 nomes disponíveis). '
                . 'Adicione mais nomes na lista ou reduza a quantidade solicitada.'
            );
        }

        return [
            'name' => array_shift(self::$pool),
        ];
    }
}