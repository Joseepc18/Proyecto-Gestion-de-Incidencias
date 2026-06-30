<?php

namespace App\Observers;

use App\Models\Incidencia;
use Illuminate\Support\Facades\Cache;

// Invalida las métricas cacheadas del dashboard ante cualquier alta, cambio o baja de incidencia.
class IncidenciaObserver
{
    public function saved(Incidencia $incidencia): void
    {
        Cache::forget('dashboard_metricas');
    }

    public function deleted(Incidencia $incidencia): void
    {
        Cache::forget('dashboard_metricas');
    }
}
