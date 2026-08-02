<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Colors>
 */
class ColorsFactory extends Factory
{
    /**
     * Mesma lógica de pool do BrandsFactory — veja os comentários lá.
     */
    private static ?array $pool = null;

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
                'Branco', 'Preto', 'Prata', 'Cinza', 'Vermelho',
                'Azul', 'Verde', 'Amarelo', 'Marrom', 'Bege',
                'Laranja', 'Dourado',
            ])->shuffle()->values()->all();
        }

        if (empty(self::$pool)) {
            // 🔴 AQUI — falha alto e claro em vez de gravar name vazio/null.
            throw new \RuntimeException(
                'ColorsFactory: lista de cores esgotada (só 12 nomes disponíveis). '
                . 'Adicione mais nomes na lista ou reduza a quantidade solicitada.'
            );
        }

        return [
            'name' => array_shift(self::$pool),
        ];
    }
}