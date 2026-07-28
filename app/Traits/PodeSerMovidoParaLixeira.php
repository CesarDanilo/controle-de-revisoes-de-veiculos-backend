<?php

namespace App\Traits;

use App\Models\Lixeira;
use Illuminate\Support\Facades\DB;

trait PodeSerMovidoParaLixeira
{
    public function moverParaLixeira(): void
    {
        DB::transaction(function () {
            Lixeira::create([
                'tabela_origem' => $this->getTable(),
                'registro_id'   => $this->id,
                'dados'         => $this->toArray(),
                'excluido_por'  => auth()->id(),
                'excluido_em'   => now(),
            ]);

            $this->delete();
        });
    }
}