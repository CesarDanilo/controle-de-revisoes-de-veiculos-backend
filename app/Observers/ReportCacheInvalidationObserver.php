<?php

namespace App\Observers;

use App\Services\ReportExportCacheService;

class ReportCacheInvalidationObserver
{
    protected ReportExportCacheService $cacheService;

    public function __construct(ReportExportCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Disparado quando um registro é criado ou atualizado
     * (Model::create, $model->save(), $model->update()).
     */
    public function saved($model): void
    {
        $this->cacheService->invalidarCache();
    }

    /**
     * Disparado quando um registro é excluído (hard delete).
     */
    public function deleted($model): void
    {
        $this->cacheService->invalidarCache();
    }

    /**
     * Disparado quando um registro com SoftDeletes é restaurado.
     */
    public function restored($model): void
    {
        $this->cacheService->invalidarCache();
    }
}