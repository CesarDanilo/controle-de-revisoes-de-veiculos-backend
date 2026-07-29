<?php

namespace App\Models;

use App\Enums\StatusPagamento;
use App\Enums\StatusRevisao;
use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\PodeSerMovidoParaLixeira;

class Revisions extends Model
{
    use HasUuidPrimaryKey;
    use HasFactory;
    use PodeSerMovidoParaLixeira;

    protected $fillable = [
        'vehicle_id',
        'description',
        'revision_date',
        'cost',
        'next_revision_date',
        'next_revision_km',
        'km',
        'user_id',
    ];

    // status e status_pagamento ficam de fora do $fillable de propósito:
    // só devem mudar através do RevisionsController::updateStatus(),
    // que registra a transição em revision_status_log. O update() normal
    // do CRUD continua livre pros outros campos.
    protected $casts = [
        'status' => StatusRevisao::class,
        'status_pagamento' => StatusPagamento::class,
        'revision_date' => 'date',
    ];

    // 🟢 NOVO — usado no index() pra eager-load do dono do veículo
    // (with('vehicle.people')), evitando N+1 query no Kanban/relatórios.
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}