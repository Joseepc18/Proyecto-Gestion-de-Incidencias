<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AlmacenamientoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarIncidenciaRequest;
use App\Http\Requests\CambiarEstadoRequest;
use App\Http\Requests\CrearIncidenciaRequest;
use App\Http\Resources\IncidenciaResource;
use App\Models\BitacoraError;
use App\Models\Incidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IncidenciaController extends Controller
{
    // Listar las incidencias aplicando filtros y permisos de visibilidad.
    public function listadoIncidencias(Request $request)
    {
        // Resueltas al final; dentro de cada bloque, las más recientes primero.
        $query = Incidencia::with(['usuario', 'subtipo.tipo', 'ciudad'])
            ->orderByRaw("(estado_incidencia = 'RESUELTO') ASC")
            ->orderBy('created_at', 'desc');

        if ($request->filled('estado')) {
            $query->where('estado_incidencia', $request->estado);
        }
        if ($request->filled('prioridad')) {
            $query->where('prioridad_incidencia', $request->prioridad);
        }
        if ($request->filled('busqueda')) {
            $query->where('nombre_incidencia', 'ilike', '%'.$request->busqueda.'%');
        }
        if ($request->filled('ciudad_id')) {
            $query->where('id_ciudad', $request->ciudad_id);
        }
        if ($request->filled('tipo_id')) {
            $query->whereHas('subtipo', fn ($q) => $q->where('id_tipo_incidencia', $request->tipo_id));
        }

        $user = $request->user();

        if ($user->esNormal()) {
            $query->where('id_usuario', $user->id);
        } elseif ($user->esTecnico()) {
            $query->whereHas('asignaciones', fn ($q) => $q->where('id_usuario', $user->id));
        }

        return $query->paginate((int) $request->input('per_page', 10));
    }

    // Crear una nueva incidencia adjuntando opcionalmente fotos (evidencias).
    public function crearIncidencia(CrearIncidenciaRequest $request)
    {
        $datos = $request->validated();
        $datos['id_usuario'] = $request->user()->id;
        $datos['prioridad_incidencia'] = $datos['prioridad_incidencia'] ?? 'MEDIA';
        unset($datos['fotos']);

        $rutasGuardadas = [];

        try {
            $incidencia = DB::transaction(function () use ($request, $datos, &$rutasGuardadas) {
                $incidencia = Incidencia::create($datos);

                if ($request->hasFile('fotos')) {
                    $incidencia->guardarEvidencias($request->file('fotos'), $request->user()->id, null, $rutasGuardadas);
                }

                return $incidencia;
            });

            return response()->json(
                new IncidenciaResource($incidencia->load([
                    'usuario',
                    'subtipo.tipo',
                    'ciudad',
                    'evidencias',
                    'historialEstados.usuario',
                    'asignaciones.usuario',
                ])),
                201
            );
        } catch (AlmacenamientoException $e) {
            Storage::disk('public')->delete($rutasGuardadas);
            BitacoraError::registrar($request->user(), 'ARCHIVO', 'IncidenciaController@crearIncidencia', $e->getMessage());

            return response()->json(['message' => 'No se pudieron guardar las fotos. Intenta de nuevo.'], 500);
        } catch (\Exception $e) {
            Storage::disk('public')->delete($rutasGuardadas);
            BitacoraError::registrar($request->user(), 'SERVIDOR', 'IncidenciaController@crearIncidencia', $e->getMessage());

            return response()->json(['message' => 'Error al crear la incidencia'], 500);
        }
    }

    // Obtener el detalle completo de una incidencia.
    public function verIncidencia(Request $request, Incidencia $incidencia)
    {
        $this->authorize('ver', $incidencia);

        return new IncidenciaResource($incidencia->load(['usuario', 'subtipo.tipo', 'ciudad', 'evidencias']));
    }

    // Actualizar datos básicos de la incidencia (solo autor si está PENDIENTE).
    public function actualizarIncidencia(ActualizarIncidenciaRequest $request, Incidencia $incidencia)
    {
        $incidencia->update($request->validated());

        return new IncidenciaResource($incidencia->load(['usuario', 'subtipo.tipo', 'ciudad']));
    }

    // Eliminar una incidencia junto con todas sus relaciones y fotos físicas.
    public function eliminarIncidencia(Request $request, Incidencia $incidencia)
    {
        $this->authorize('eliminar', $incidencia);

        try {
            $rutasEvidencias = $incidencia->evidencias->pluck('url_evidencia');

            DB::transaction(function () use ($incidencia) {
                $incidencia->comentarios()->delete();
                $incidencia->historialEstados()->delete();
                $incidencia->asignaciones()->delete();
                $incidencia->delete();
            });

            foreach ($rutasEvidencias as $ruta) {
                if (! Storage::disk('public')->delete($ruta)) {
                    BitacoraError::registrar($request->user(), 'ARCHIVO', 'IncidenciaController@eliminarIncidencia', 'no se pudo borrar '.$ruta);
                }
            }

            return ['message' => 'Incidencia eliminada'];
        } catch (\Exception $e) {
            BitacoraError::registrar($request->user(), 'SERVIDOR', 'IncidenciaController@eliminarIncidencia', $e->getMessage());

            return response()->json(['message' => 'Error al eliminar la incidencia'], 500);
        }
    }

    // Ver el historial de cambios de estado de una incidencia.
    public function historialIncidencia(Request $request, Incidencia $incidencia)
    {
        $this->authorize('verHistorial', $incidencia);

        return $incidencia->historialEstados()->with('usuario')->orderBy('created_at', 'desc')->get();
    }

    // Cambiar el estado (flujo de trabajo): admin o técnico asignado.
    public function cambiarEstado(CambiarEstadoRequest $request, Incidencia $incidencia)
    {
        $nuevo = $request->estado_incidencia;
        $actual = $incidencia->estado_incidencia;

        if ($actual === 'RESUELTO') {
            return response()->json(['message' => 'No se puede cambiar el estado de una incidencia ya resuelta'], 422);
        }

        if (! $request->user()->esAdmin()) {
            $siguientePermitido = [
                'EN_PROCESO' => 'RESUELTO',
            ];
            if (($siguientePermitido[$actual] ?? null) !== $nuevo) {
                return response()->json(['message' => 'Transición de estado no permitida'], 422);
            }
        }

        if ($nuevo === 'RESUELTO' && $actual !== 'RESUELTO') {
            DB::statement('CALL resolver_incidencia(?, ?)', [$incidencia->id_incidencia, $request->user()->id]);
            $incidencia->refresh();
        } else {
            DB::transaction(function () use ($incidencia, $nuevo, $request) {
                DB::statement("SELECT set_config('app.actor_id', ?, true)", [(string) $request->user()->id]);
                $incidencia->update(['estado_incidencia' => $nuevo]);
            });
        }

        // El observer invalida la caché en el update Eloquent, pero la rama del SP (resolver_incidencia)
        // es SQL crudo y no dispara eventos: aquí la invalidamos a mano.
        Cache::forget('dashboard_metricas');

        return new IncidenciaResource($incidencia->load(['usuario', 'subtipo.tipo', 'ciudad']));
    }
}
