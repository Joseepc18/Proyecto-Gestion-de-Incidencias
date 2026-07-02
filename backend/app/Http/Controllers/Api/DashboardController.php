<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoIncidencia;
use App\Enums\PrioridadIncidencia;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
                ->selectRaw('COUNT(*) FILTER (WHERE estado_incidencia = ?) AS pendientes', [EstadoIncidencia::Pendiente->value])
                ->selectRaw('COUNT(*) FILTER (WHERE estado_incidencia = ?) AS en_proceso', [EstadoIncidencia::EnProceso->value])
                ->selectRaw('COUNT(*) FILTER (WHERE estado_incidencia = ?) AS resueltas', [EstadoIncidencia::Resuelto->value])
                ->first();

            $promedioGlobal = DB::table('incidencias')
                ->whereNotNull('fecha_resolucion')
                ->selectRaw('ROUND(AVG(EXTRACT(EPOCH FROM (fecha_resolucion - created_at)) / 86400)::numeric, 1) AS dias')
                ->value('dias');

            $porPrioridad = DB::table('incidencias')
                ->selectRaw('COUNT(*) FILTER (WHERE prioridad_incidencia = ?) AS alta', [PrioridadIncidencia::Alta->value])
                ->selectRaw('COUNT(*) FILTER (WHERE prioridad_incidencia = ?) AS media', [PrioridadIncidencia::Media->value])
                ->selectRaw('COUNT(*) FILTER (WHERE prioridad_incidencia = ?) AS baja', [PrioridadIncidencia::Baja->value])
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

    // Métricas personales del panel del técnico: solo cuentan sus asignaciones.
    // Sin caché a propósito: son consultas chicas por usuario y así el panel refleja al instante lo que resuelve.
    public function metricasTecnico(Request $request)
    {
        $user = $request->user();
        if (! $user->esTecnico()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        // Base reutilizable: incidencias donde este técnico tiene alguna asignación.
        $asignadas = fn () => DB::table('incidencias as i')
            ->join('asignaciones_incidencia as a', 'a.id_incidencia', '=', 'i.id_incidencia')
            ->where('a.id_usuario', $user->id);

        // Conteos como RESPONSABLE (KPIs y dona) más las activas donde solo es APOYO.
        $totales = $asignadas()
            ->selectRaw("COUNT(*) FILTER (WHERE a.rol_asignado = 'RESPONSABLE' AND i.estado_incidencia = ?) AS pendientes", [EstadoIncidencia::Pendiente->value])
            ->selectRaw("COUNT(*) FILTER (WHERE a.rol_asignado = 'RESPONSABLE' AND i.estado_incidencia = ?) AS en_proceso", [EstadoIncidencia::EnProceso->value])
            ->selectRaw("COUNT(*) FILTER (WHERE a.rol_asignado = 'RESPONSABLE' AND i.estado_incidencia = ?) AS resueltas", [EstadoIncidencia::Resuelto->value])
            ->selectRaw("COUNT(*) FILTER (WHERE a.rol_asignado = 'RESPONSABLE' AND i.estado_incidencia = ? AND i.fecha_resolucion >= ?) AS resueltas_mes", [EstadoIncidencia::Resuelto->value, now()->startOfMonth()])
            ->selectRaw("COUNT(*) FILTER (WHERE a.rol_asignado = 'APOYO' AND i.estado_incidencia <> ?) AS apoyo_activas", [EstadoIncidencia::Resuelto->value])
            ->first();

        // Resueltas por semana (últimas 8), rellenando con 0 las semanas sin cierres.
        $inicioSemanas = now()->startOfWeek()->subWeeks(7);
        $cierres = $asignadas()
            ->where('a.rol_asignado', 'RESPONSABLE')
            ->whereNotNull('i.fecha_resolucion')
            ->where('i.fecha_resolucion', '>=', $inicioSemanas)
            ->groupByRaw("date_trunc('week', i.fecha_resolucion)")
            ->selectRaw("to_char(date_trunc('week', i.fecha_resolucion), 'YYYY-MM-DD') AS semana, COUNT(*) AS total")
            ->pluck('total', 'semana');

        $porSemana = [];
        for ($i = 0; $i < 8; $i++) {
            $semana = $inicioSemanas->copy()->addWeeks($i)->toDateString();
            $porSemana[] = ['semana' => $semana, 'total' => (int) ($cierres[$semana] ?? 0)];
        }

        // Sus incidencias sin resolver (mapa y lista): prioridad ALTA primero y las más viejas arriba.
        $activas = $asignadas()
            ->where('i.estado_incidencia', '<>', EstadoIncidencia::Resuelto->value)
            ->orderByRaw('CASE i.prioridad_incidencia WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END', [PrioridadIncidencia::Alta->value, PrioridadIncidencia::Media->value])
            ->orderBy('i.created_at')
            ->select('i.id_incidencia', 'i.nombre_incidencia', 'i.prioridad_incidencia', 'i.estado_incidencia',
                'i.latitud_incidencia', 'i.longitud_incidencia', 'i.created_at', 'a.rol_asignado')
            ->get();

        return response()->json([
            'totales' => $totales,
            'por_semana' => $porSemana,
            'activas' => $activas,
        ]);
    }
}
