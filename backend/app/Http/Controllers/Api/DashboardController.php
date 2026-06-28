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
            $totales = DB::table('incidencias')
                ->selectRaw('COUNT(*) AS total')
                ->selectRaw("COUNT(*) FILTER (WHERE estado_incidencia = 'PENDIENTE') AS pendientes")
                ->selectRaw("COUNT(*) FILTER (WHERE estado_incidencia = 'EN_PROCESO') AS en_proceso")
                ->selectRaw("COUNT(*) FILTER (WHERE estado_incidencia = 'RESUELTO') AS resueltas")
                ->first();

            $promedioGlobal = DB::table('incidencias')
                ->whereNotNull('fecha_resolucion')
                ->selectRaw('ROUND(AVG(EXTRACT(EPOCH FROM (fecha_resolucion - created_at)) / 86400)::numeric, 1) AS dias')
                ->value('dias');

            $porPrioridad = DB::table('incidencias')
                ->selectRaw("COUNT(*) FILTER (WHERE prioridad_incidencia = 'ALTA') AS alta")
                ->selectRaw("COUNT(*) FILTER (WHERE prioridad_incidencia = 'MEDIA') AS media")
                ->selectRaw("COUNT(*) FILTER (WHERE prioridad_incidencia = 'BAJA') AS baja")
                ->first();

            $porTipo = DB::table('v_metricas_por_tipo')->get();

            $porUbicacion = DB::table('v_metricas_por_ubicacion')->get();

            $porProvincia = DB::table('incidencias as i')
                ->join('ciudades as c', 'i.id_ciudad', '=', 'c.id_ciudad')
                ->join('provincias as p', 'c.id_provincia', '=', 'p.id_provincia')
                ->groupBy('p.id_provincia', 'p.nombre_provincia')
                ->orderByDesc('total')
                ->selectRaw('p.nombre_provincia, COUNT(i.id_incidencia) AS total')
                ->get();

            $porMes = DB::table('incidencias')
                ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
                ->groupByRaw("date_trunc('month', created_at)")
                ->orderByRaw("date_trunc('month', created_at)")
                ->selectRaw("to_char(date_trunc('month', created_at), 'YYYY-MM') AS mes, COUNT(*) AS total")
                ->get();

            return [
                'totales' => $totales,
                'promedio_dias' => $promedioGlobal,
                'por_prioridad' => $porPrioridad,
                'por_tipo' => $porTipo,
                'por_ubicacion' => $porUbicacion,
                'por_provincia' => $porProvincia,
                'por_mes' => $porMes,
            ];
        });

        return response()->json($datos);
    }
}
