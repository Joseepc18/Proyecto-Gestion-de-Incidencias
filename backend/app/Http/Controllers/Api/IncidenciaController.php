<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarIncidenciaRequest;
use App\Http\Requests\CambiarEstadoRequest;
use App\Http\Requests\CrearIncidenciaRequest;
use App\Models\BitacoraError;
use App\Models\Incidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IncidenciaController extends Controller
{
    public function listadoIncidencias(Request $request)
    {
        $query = Incidencia::with(['usuario', 'subtipo.tipo', 'ciudad'])
            ->orderBy('created_at', 'desc');

        // Filtros opcionales
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

        // Visibilidad por rol: normal ve solo las suyas; técnico solo donde está asignado
        $user = $request->user();

        if ($user->esNormal()) {
            $query->where('id_usuario', $user->id);
        } elseif ($user->esTecnico()) {
            $query->whereHas('asignaciones', fn ($q) => $q->where('id_usuario', $user->id));
        }

        return response()->json($query->paginate(10));
    }

    public function crearIncidencia(CrearIncidenciaRequest $request)
    {
        $datos = $request->validated();
        $datos['id_usuario'] = $request->user()->id;
        // Si el ciudadano no eligió prioridad, entra como MEDIA (el admin la ajusta luego).
        $datos['prioridad_incidencia'] = $datos['prioridad_incidencia'] ?? 'MEDIA';
        unset($datos['fotos']);

        try {
            $incidencia = Incidencia::create($datos);

            // Cada foto se guarda como una evidencia ligada a la incidencia
            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $foto) {
                    $incidencia->evidencias()->create([
                        'url_evidencia' => $foto->store('incidencias', 'public'),
                        'id_usuario' => $request->user()->id,
                    ]);
                }
            }

            return response()->json(
                $incidencia->load(['usuario', 'subtipo.tipo', 'ciudad', 'evidencias']),
                201
            );
        } catch (\Exception $e) {
            BitacoraError::create([
                'id_usuario' => $request->user()->id,
                'tipo_error' => 'SERVIDOR',
                'descripcion_error' => 'IncidenciaController@crearIncidencia: '.$e->getMessage(),
            ]);

            return response()->json(['message' => 'Error al crear la incidencia'], 500);
        }
    }

    public function verIncidencia(Request $request, Incidencia $incidencia)
    {
        $this->authorize('ver', $incidencia);

        return response()->json($incidencia->load(['usuario', 'subtipo.tipo', 'ciudad', 'evidencias']));
    }

    public function actualizarIncidencia(ActualizarIncidenciaRequest $request, Incidencia $incidencia)
    {
        // La autorización (IncidenciaPolicy) la resuelve el FormRequest antes de validar.
        $incidencia->update($request->validated());

        return response()->json($incidencia->load(['usuario', 'subtipo.tipo', 'ciudad']));
    }

    public function eliminarIncidencia(Request $request, Incidencia $incidencia)
    {
        $this->authorize('eliminar', $incidencia);

        try {
            // Guardamos las rutas antes de borrar (las evidencias se van por CASCADE)
            $rutasEvidencias = $incidencia->evidencias->pluck('url_evidencia');

            // comentarios/historial/asignaciones son FK RESTRICT: se borran a mano
            DB::transaction(function () use ($incidencia) {
                $incidencia->comentarios()->delete();
                $incidencia->historialEstados()->delete();
                $incidencia->asignaciones()->delete();
                $incidencia->delete();
            });

            // Los archivos del disco solo si la BD confirmó el borrado
            foreach ($rutasEvidencias as $ruta) {
                Storage::disk('public')->delete($ruta);
            }

            return response()->json(['message' => 'Incidencia eliminada']);
        } catch (\Exception $e) {
            BitacoraError::create([
                'id_usuario' => $request->user()->id,
                'tipo_error' => 'SERVIDOR',
                'descripcion_error' => 'IncidenciaController@eliminarIncidencia: '.$e->getMessage(),
            ]);

            return response()->json(['message' => 'Error al eliminar la incidencia'], 500);
        }
    }

    public function historialIncidencia(Request $request, Incidencia $incidencia)
    {
        $this->authorize('verHistorial', $incidencia);

        return response()->json(
            $incidencia->historialEstados()->with('usuario')->orderBy('created_at', 'desc')->get()
        );
    }

    // Cambiar el estado (flujo de trabajo): admin o técnico asignado.
    public function cambiarEstado(CambiarEstadoRequest $request, Incidencia $incidencia)
    {
        // La autorización (solo admin o técnico asignado) la resuelve el FormRequest.
        $nuevo = $request->estado_incidencia;
        $actual = $incidencia->estado_incidencia;

        // El admin cambia libremente; el técnico solo puede avanzar al estado siguiente.
        if (! $request->user()->esAdmin()) {
            $siguientePermitido = [
                'PENDIENTE' => 'EN_PROCESO',
                'EN_PROCESO' => 'RESUELTO',
            ];
            if (($siguientePermitido[$actual] ?? null) !== $nuevo) {
                return response()->json(['message' => 'Transición de estado no permitida'], 422);
            }
        }

        // RESUELTO usa el procedimiento (notifica a reportador y técnicos); el resto es update directo (triggers hacen fecha e historial).
        if ($nuevo === 'RESUELTO' && $actual !== 'RESUELTO') {
            DB::statement('CALL resolver_incidencia(?, ?)', [$incidencia->id_incidencia, $request->user()->id]);
            $incidencia->refresh();
        } else {
            // Publica el actor para que el trigger de historial registre quién ejecuta (no el dueño).
            DB::transaction(function () use ($incidencia, $nuevo, $request) {
                DB::statement("SELECT set_config('app.actor_id', ?, true)", [(string) $request->user()->id]);
                $incidencia->update(['estado_incidencia' => $nuevo]);
            });
        }

        return response()->json($incidencia->load(['usuario', 'subtipo.tipo', 'ciudad']));
    }
}
