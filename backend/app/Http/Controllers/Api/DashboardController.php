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

            // Promedio global de días de resolución (solo las ya resueltas)
            $promedioGlobal = DB::table('incidencias')
                ->whereNotNull('fecha_resolucion')
                ->selectRaw('ROUND(AVG(EXTRACT(EPOCH FROM (fecha_resolucion - created_at)) / 86400)::numeric, 1) AS dias')
                ->value('dias');

            // Conteo por prioridad (ALTA/MEDIA/BAJA)
            $porPrioridad = DB::table('incidencias')
                ->selectRaw("COUNT(*) FILTER (WHERE prioridad_incidencia = 'ALTA') AS alta")
                ->selectRaw("COUNT(*) FILTER (WHERE prioridad_incidencia = 'MEDIA') AS media")
                ->selectRaw("COUNT(*) FILTER (WHERE prioridad_incidencia = 'BAJA') AS baja")
                ->first();

            // Métricas agrupadas por tipo (vista de BD)
            $porTipo = DB::table('v_metricas_por_tipo')->get();

            // Métricas agrupadas por ubicación/ciudad (vista de BD)
            $porUbicacion = DB::table('v_metricas_por_ubicacion')->get();

            // Conteo por provincia (alimenta el mapa de coropletas del Ecuador)
            $porProvincia = DB::table('incidencias as i')
                ->join('ciudades as c', 'i.id_ciudad', '=', 'c.id_ciudad')
                ->join('provincias as p', 'c.id_provincia', '=', 'p.id_provincia')
                ->groupBy('p.id_provincia', 'p.nombre_provincia')
                ->orderByDesc('total')
                ->selectRaw('p.nombre_provincia, COUNT(i.id_incidencia) AS total')
                ->get();

            return [
                'totales' => $totales,
                'promedio_dias' => $promedioGlobal,
                'por_prioridad' => $porPrioridad,
                'por_tipo' => $porTipo,
                'por_ubicacion' => $porUbicacion,
                'por_provincia' => $porProvincia,
            ];
        });

        return response()->json($datos);
    }
}
