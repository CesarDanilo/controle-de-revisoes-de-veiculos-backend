<?php

namespace App\Enums;

enum StatusPagamento: string
{
    case Pendente = 'pendente';
    case Pago = 'pago';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Pago => 'Pago',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }

    public static function options(): array
    {
        return array_map(
            fn (self $s) => ['value' => $s->value, 'label' => $s->label()],
            self::cases(),
        );
    }
}