<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoIncidencia;
use App\Enums\PrioridadIncidencia;
use App\Exceptions\AlmacenamientoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarIncidenciaRequest;
use App\Http\Requests\CambiarEstadoRequest;
use App\Http\Requests\CrearIncidenciaRequest;
use App\Http\Requests\EliminarIncidenciaRequest;
use App\Http\Requests\SolicitarReaperturaRequest;
use App\Http\Resources\IncidenciaResource;
use App\Models\BitacoraError;
use App\Models\Incidencia;
use App\Models\Notificacion;
use App\Models\User;
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
            ->orderByRaw('(estado_incidencia = ?) ASC', [EstadoIncidencia::Resuelto->value])
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

        return $query->paginate($this->perPage($request));
    }

    // Crear una nueva incidencia adjuntando opcionalmente fotos (evidencias).
    public function crearIncidencia(CrearIncidenciaRequest $request)
    {
        $datos = $request->validated();
        $datos['id_usuario'] = $request->user()->id;
        $datos['prioridad_incidencia'] = $datos['prioridad_incidencia'] ?? PrioridadIncidencia::Media->value;
        unset($datos['fotos']);

        $rutasGuardadas = [];

        try {
            $incidencia = DB::transaction(function () use ($request, $datos, &$rutasGuardadas) {
                $incidencia = Incidencia::create($datos);

                // Nacer en EN_PROCESO se salta el flujo PENDIENTE→EN_PROCESO, que el trigger AFTER UPDATE nunca vería.
                if ($incidencia->estado_incidencia === EstadoIncidencia::EnProceso->value) {
                    $incidencia->historialEstados()->create([
                        'id_usuario' => $request->user()->id,
                        'estado_anterior' => EstadoIncidencia::Pendiente->value,
                        'estado_nuevo' => EstadoIncidencia::EnProceso->value,
                    ]);
                }

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

        $incidencia->load(['usuario', 'subtipo.tipo', 'ciudad.provincia', 'evidencias']);
        $incidencia->reapertura_pendiente = $this->tieneSolicitudReaperturaPendiente($incidencia);

        return new IncidenciaResource($incidencia);
    }

    // ¿Hay una solicitud de reapertura del reportador sin revisar? RESUELTO queda de solo lectura
    // para TODOS (incluido el admin) hasta que exista una: solo entonces se habilita el botón "Reabrir".
    private function tieneSolicitudReaperturaPendiente(Incidencia $incidencia): bool
    {
        return Notificacion::where('id_incidencia', $incidencia->id_incidencia)
            ->where('tipo_notificacion', 'SOLICITUD_REAPERTURA')
            ->where('estado_lectura', false)
            ->exists();
    }

    // Actualizar datos básicos de la incidencia (solo autor si está PENDIENTE).
    public function actualizarIncidencia(ActualizarIncidenciaRequest $request, Incidencia $incidencia)
    {
        $incidencia->update($request->validated());

        return new IncidenciaResource($incidencia->load(['usuario', 'subtipo.tipo', 'ciudad.provincia']));
    }

    // Eliminar una incidencia junto con todas sus relaciones y fotos físicas.
    // Si la borra alguien más (el admin), se le notifica el motivo al dueño ANTES de borrar:
    // la notificación se crea con id_incidencia null para no depender de una fila que está por desaparecer.
    public function eliminarIncidencia(EliminarIncidenciaRequest $request, Incidencia $incidencia)
    {
        $esPropia = $incidencia->id_usuario === $request->user()->id;
        $nombreIncidencia = $incidencia->nombre_incidencia;
        $idReportador = $incidencia->id_usuario;
        $motivo = $request->validated()['motivo'] ?? null;

        try {
            $rutasEvidencias = $incidencia->evidencias->pluck('url_evidencia');

            DB::transaction(function () use ($incidencia, $esPropia, $nombreIncidencia, $idReportador, $motivo) {
                $incidencia->comentarios()->delete();
                $incidencia->historialEstados()->delete();
                $incidencia->asignaciones()->delete();
                $incidencia->delete();

                if (! $esPropia) {
                    Notificacion::create([
                        'id_incidencia' => null,
                        'id_usuario' => $idReportador,
                        'tipo_notificacion' => 'INCIDENCIA_ELIMINADA',
                        'mensaje_notificacion' => 'Tu incidencia "'.$nombreIncidencia.'" fue eliminada. Motivo: '.$motivo,
                    ]);
                }
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

        // Única excepción al "RESUELTO es terminal": el admin puede reabrir a EN_PROCESO,
        // pero SOLO si el reportador lo pidió (RESUELTO queda cerrado para todos, admin
        // incluido, hasta que exista una solicitud sin revisar). El técnico nunca puede.
        $esReaperturaDeAdmin = $actual === EstadoIncidencia::Resuelto->value
            && $nuevo === EstadoIncidencia::EnProceso->value
            && $request->user()->esAdmin()
            && $this->tieneSolicitudReaperturaPendiente($incidencia);

        if ($actual === EstadoIncidencia::Resuelto->value && ! $esReaperturaDeAdmin) {
            return response()->json(['message' => 'No se puede cambiar el estado de una incidencia ya resuelta'], 422);
        }

        if (! $request->user()->esAdmin()) {
            $siguientePermitido = [
                EstadoIncidencia::EnProceso->value => EstadoIncidencia::Resuelto->value,
            ];
            if (($siguientePermitido[$actual] ?? null) !== $nuevo) {
                return response()->json(['message' => 'Transición de estado no permitida'], 422);
            }
        }

        if ($nuevo === EstadoIncidencia::Resuelto->value && $actual !== EstadoIncidencia::Resuelto->value) {
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

        // Ya se atendió la solicitud: se marca leída para todos los admins (libera el botón
        // "Reabrir" y el cupo de "1 solicitud pendiente" para una futura reapertura).
        if ($esReaperturaDeAdmin) {
            Notificacion::where('id_incidencia', $incidencia->id_incidencia)
                ->where('tipo_notificacion', 'SOLICITUD_REAPERTURA')
                ->where('estado_lectura', false)
                ->update(['estado_lectura' => true, 'fecha_lectura' => now()]);
        }

        $incidencia->reapertura_pendiente = $this->tieneSolicitudReaperturaPendiente($incidencia);

        return new IncidenciaResource($incidencia->load(['usuario', 'subtipo.tipo', 'ciudad']));
    }

    // El reportador pide reabrir una incidencia ya resuelta: NO cambia el estado, solo avisa
    // a los admins con el motivo para que decidan si la reabren (vía cambiarEstado).
    // Solo 1 solicitud pendiente a la vez: si ya hay una SIN LEER para esta incidencia,
    // se rechaza (422) hasta que un admin la revise y la marque leída al abrir el detalle.
    public function solicitarReapertura(SolicitarReaperturaRequest $request, Incidencia $incidencia)
    {
        if ($this->tieneSolicitudReaperturaPendiente($incidencia)) {
            return response()->json(['message' => 'Ya tienes una solicitud de reapertura pendiente de revisión.'], 422);
        }

        $motivo = $request->validated()['motivo'];

        User::whereHas('rol', fn ($q) => $q->where('nombre_rol', 'admin'))
            ->get()
            ->each(function (User $admin) use ($incidencia, $motivo) {
                Notificacion::create([
                    'id_incidencia' => $incidencia->id_incidencia,
                    'id_usuario' => $admin->id,
                    'tipo_notificacion' => 'SOLICITUD_REAPERTURA',
                    'mensaje_notificacion' => 'Piden reabrir "'.$incidencia->nombre_incidencia.'". Motivo: '.$motivo,
                ]);
            });

        return response()->json(['message' => 'Solicitud enviada. Un administrador la revisará.']);
    }
}
