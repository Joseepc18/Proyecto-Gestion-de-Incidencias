<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Métricas para el panel del admin. Se cachean 60s en Redis para no recalcular
    // los conteos y promedios en cada carga.
    public function metricas()
    {
        $datos = Cache::remember('dashboard_metricas', 60, function () {
            // Conteos globales por estado
            $totales = DB::table('incidencias')
                ->selectRaw('COUNT(*) AS total')
                ->selectRaw("COUNT(*) FILTER (WHERE estado_incidencia = 'PENDIENTE') AS pendientes")
                ->selectRaw("COUNT(*) FILTER (WHERE estado_incidencia = 'EN_PROCESO') AS en_proceso")
                ->selectRaw("COUNT(*) FILTER (WHERE estado_incidencia = 'RESUELTO') AS resueltas")
                ->first();

            // Métricas agrupadas por tipo (vista de BD)
            $porTipo = DB::table('v_metricas_por_tipo')->get();

            return [
                'totales' => $totales,
                'por_tipo' => $porTipo,
            ];
        });

        return response()->json($datos);
    }
}
