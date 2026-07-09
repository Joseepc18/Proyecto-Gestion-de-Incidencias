<?php

namespace App\Listeners;

use App\Events\IncidenciaCambioEstado;
use Illuminate\Support\Facades\Cache;

// Segundo listener del mismo evento: invalida las métricas cacheadas del dashboard.
// Cubre la rama del SP resolver_incidencia (UPDATE por SQL crudo que no dispara el observer de Eloquent).
class InvalidarCacheDashboard
{
    public function handle(IncidenciaCambioEstado $evento): void
    {
        Cache::forget('dashboard_metricas');
    }
}
