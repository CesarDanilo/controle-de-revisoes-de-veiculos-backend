<?php

namespace App\Providers;

use App\Models\People;
use App\Models\Revisions;
use App\Models\Vehicle;
use App\Observers\ReportCacheInvalidationObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra os serviços da aplicação.
     */
    public function register(): void
    {
        //
    }

    /**
     * Executa após todos os serviços serem registrados.
     */
    public function boot(): void
    {
        // ... seu código existente do boot() continua aqui em cima ...
    
        // Qualquer mudança nesses models invalida o cache de exports de relatório,
        // forçando a próxima solicitação a gerar um PDF atualizado.
        Revisions::observe(ReportCacheInvalidationObserver::class);
        Vehicle::observe(ReportCacheInvalidationObserver::class);
        People::observe(ReportCacheInvalidationObserver::class);
    }
}