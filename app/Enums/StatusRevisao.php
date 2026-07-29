<?php

namespace App\Enums;

enum StatusRevisao: string
{
    case Aberto = 'aberto';
    case EmAndamento = 'em_andamento';
    case AguardandoPagamento = 'aguardando_pagamento';
    case Concluido = 'concluido';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Aberto => 'Aberto',
            self::EmAndamento => 'Em andamento',
            self::AguardandoPagamento => 'Aguardando pagamento',
            self::Concluido => 'Concluído',
            self::Cancelado => 'Cancelado',
        };
    }

    // Usado pelo select do front e pra validar o PATCH /revisoes/{id}/status
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }

    // Pra alimentar direto um <select> no front: [{ value, label }, ...]
    public static function options(): array
    {
        return array_map(
            fn (self $s) => ['value' => $s->value, 'label' => $s->label()],
            self::cases(),
        );
    }
}