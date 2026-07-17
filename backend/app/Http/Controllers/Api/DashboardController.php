<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoIncidencia;
use App\Enums\PrioridadIncidencia;
use App\Enums\RolAsignacion;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    // Métricas para el panel del admin, cacheadas 60s en Redis para no recalcular en cada carga.
    public function metricas()
    {
        $datos = Cache::remember('dashboard_metricas', 60, function () {
            $totales = DB::table('incidencias')
                ->whereNull('deleted_at')
                ->selectRaw('COUNT(*) AS total')
                ->selectRaw('COUNT(*) FILTER (WHERE estado_incidencia = ?) AS pendientes', [EstadoIncidencia::Pendiente->value])
                ->selectRaw('COUNT(*) FILTER (WHERE estado_incidencia = ?) AS en_proceso', [EstadoIncidencia::EnProceso->value])
                ->selectRaw('COUNT(*) FILTER (WHERE estado_incidencia = ?) AS resueltas', [EstadoIncidencia::Resuelto->value])
                ->first();

            $promedioGlobal = DB::table('incidencias')
                ->whereNull('deleted_at')
                ->whereNotNull('fecha_resolucion')
                ->selectRaw('ROUND(AVG(EXTRACT(EPOCH FROM (fecha_resolucion - created_at)) / 86400)::numeric, 1) AS dias')
                ->value('dias');

            $porPrioridad = DB::table('incidencias')
                ->whereNull('deleted_at')
                ->selectRaw('COUNT(*) FILTER (WHERE prioridad_incidencia = ?) AS alta', [PrioridadIncidencia::Alta->value])
                ->selectRaw('COUNT(*) FILTER (WHERE prioridad_incidencia = ?) AS media', [PrioridadIncidencia::Media->value])
                ->selectRaw('COUNT(*) FILTER (WHERE prioridad_incidencia = ?) AS baja', [PrioridadIncidencia::Baja->value])
                ->selectRaw('COUNT(*) FILTER (WHERE prioridad_incidencia = ?) AS sin_asignar', [PrioridadIncidencia::SinAsignar->value])
                ->first();

            $porTipo = DB::table('v_metricas_por_tipo')->get();

            $porUbicacion = DB::table('v_metricas_por_ubicacion')->get();

            $porProvincia = DB::table('incidencias as i')
                ->whereNull('i.deleted_at')
                ->join('ciudades as c', 'i.id_ciudad', '=', 'c.id_ciudad')
                ->join('provincias as p', 'c.id_provincia', '=', 'p.id_provincia')
                ->groupBy('p.id_provincia', 'p.nombre_provincia')
                ->orderByDesc('total')
                ->selectRaw('p.nombre_provincia, COUNT(i.id_incidencia) AS total')
                ->get();

            // created_at es TIMESTAMP sin zona pero guarda instantes UTC (config/app.php timezone=UTC): el doble
            // AT TIME ZONE primero lo ancla a UTC y luego lo convierte a hora local de Ecuador antes de agrupar,
            // así una incidencia creada a las 23:30 en Guayaquil no cae en el mes/semana siguiente (ya en UTC).
            $porMes = DB::table('incidencias')
                ->whereNull('deleted_at')
                ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
                ->groupByRaw("date_trunc('month', created_at AT TIME ZONE 'UTC' AT TIME ZONE 'America/Guayaquil')")
                ->orderByRaw("date_trunc('month', created_at AT TIME ZONE 'UTC' AT TIME ZONE 'America/Guayaquil')")
                ->selectRaw("to_char(date_trunc('month', created_at AT TIME ZONE 'UTC' AT TIME ZONE 'America/Guayaquil'), 'YYYY-MM') AS mes, COUNT(*) AS total")
                ->get();

            // Se castea a array plano antes de cachear: un Collection/stdClass cacheado no se rehidrata
            // al deserializar en prod (llega como __PHP_Incomplete_Class) y rompe el dashboard al volver (#24).
            return [
                'totales' => (array) $totales,
                'promedio_dias' => $promedioGlobal,
                'por_prioridad' => (array) $porPrioridad,
                'por_tipo' => $porTipo->map(fn ($f) => (array) $f)->all(),
                'por_ubicacion' => $porUbicacion->map(fn ($f) => (array) $f)->all(),
                'por_provincia' => $porProvincia->map(fn ($f) => (array) $f)->all(),
                'por_mes' => $porMes->map(fn ($f) => (array) $f)->all(),
            ];
        });

        return response()->json($datos);
    }

    // Métricas personales del panel del técnico (solo sus asignaciones); sin caché para reflejar al instante lo resuelto.
    public function metricasTecnico(Request $request)
    {
        Gate::authorize('ver-metricas-tecnico');
        $user = $request->user();

        // Base reutilizable: incidencias donde este técnico tiene alguna asignación.
        $asignadas = fn () => DB::table('incidencias as i')
            ->whereNull('i.deleted_at')
            ->join('asignaciones_incidencia as a', 'a.id_incidencia', '=', 'i.id_incidencia')
            ->where('a.id_usuario', $user->id);

        // Conteos como RESPONSABLE (KPIs y dona) más las activas donde solo es APOYO.
        $totales = $asignadas()
            ->selectRaw('COUNT(*) FILTER (WHERE a.rol_asignado = ? AND i.estado_incidencia = ?) AS pendientes', [RolAsignacion::Responsable->value, EstadoIncidencia::Pendiente->value])
            ->selectRaw('COUNT(*) FILTER (WHERE a.rol_asignado = ? AND i.estado_incidencia = ?) AS en_proceso', [RolAsignacion::Responsable->value, EstadoIncidencia::EnProceso->value])
            ->selectRaw('COUNT(*) FILTER (WHERE a.rol_asignado = ? AND i.estado_incidencia = ?) AS resueltas', [RolAsignacion::Responsable->value, EstadoIncidencia::Resuelto->value])
            ->selectRaw('COUNT(*) FILTER (WHERE a.rol_asignado = ? AND i.estado_incidencia = ? AND i.fecha_resolucion >= ?) AS resueltas_mes', [RolAsignacion::Responsable->value, EstadoIncidencia::Resuelto->value, now()->startOfMonth()])
            ->selectRaw('COUNT(*) FILTER (WHERE a.rol_asignado = ? AND i.estado_incidencia NOT IN (?, ?)) AS apoyo_activas', [RolAsignacion::Apoyo->value, EstadoIncidencia::Resuelto->value, EstadoIncidencia::Cerrado->value])
            ->first();

        // Resueltas por semana (últimas 8), rellenando con 0 las semanas sin cierres.
        $inicioSemanas = now()->startOfWeek()->subWeeks(7);
        // Mismo ajuste de zona horaria que en metricas(): fecha_resolucion guarda instantes UTC.
        $cierres = $asignadas()
            ->where('a.rol_asignado', RolAsignacion::Responsable->value)
            ->whereNotNull('i.fecha_resolucion')
            ->where('i.fecha_resolucion', '>=', $inicioSemanas)
            ->groupByRaw("date_trunc('week', i.fecha_resolucion AT TIME ZONE 'UTC' AT TIME ZONE 'America/Guayaquil')")
            ->selectRaw("to_char(date_trunc('week', i.fecha_resolucion AT TIME ZONE 'UTC' AT TIME ZONE 'America/Guayaquil'), 'YYYY-MM-DD') AS semana, COUNT(*) AS total")
            ->pluck('total', 'semana');

        $porSemana = [];
        for ($i = 0; $i < 8; $i++) {
            $semana = $inicioSemanas->copy()->addWeeks($i)->toDateString();
            $porSemana[] = ['semana' => $semana, 'total' => (int) ($cierres[$semana] ?? 0)];
        }

        // Sus incidencias sin resolver (mapa y lista): prioridad ALTA primero y las más viejas arriba.
        $activas = $asignadas()
            ->whereNotIn('i.estado_incidencia', [EstadoIncidencia::Resuelto->value, EstadoIncidencia::Cerrado->value])
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
