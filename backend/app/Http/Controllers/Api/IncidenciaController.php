<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoIncidencia;
use App\Enums\PrioridadIncidencia;
use App\Events\IncidenciaActualizada;
use App\Events\IncidenciaCambioEstado;
use App\Events\IncidenciaCreada;
use App\Events\ReclamoCambiado;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarIncidenciaRequest;
use App\Http\Requests\ArchivarIncidenciaRequest;
use App\Http\Requests\CambiarEstadoRequest;
use App\Http\Requests\CrearIncidenciaRequest;
use App\Http\Requests\EliminarIncidenciaRequest;
use App\Http\Requests\LiberarReclamoRequest;
use App\Http\Requests\RechazarReaperturaRequest;
use App\Http\Requests\ReclamarIncidenciaRequest;
use App\Http\Requests\SolicitarReaperturaRequest;
use App\Http\Resources\HistorialEstadoResource;
use App\Http\Resources\IncidenciaResource;
use App\Models\BitacoraError;
use App\Models\Incidencia;
use App\Models\User;
use App\Notifications\IncidenciaNotification;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class IncidenciaController extends Controller
{
    // Listar las incidencias aplicando filtros y permisos de visibilidad.
    public function listadoIncidencias(Request $request)
    {
        // Resueltas al final; dentro de cada bloque, las más recientes primero.
        $query = Incidencia::with(['usuario', 'subtipo.tipo', 'ciudad', 'adminAtiende'])
            ->orderByRaw('(estado_incidencia = ?) ASC', [EstadoIncidencia::Resuelto->value])
            ->orderBy('created_at', 'desc');

        if ($request->filled('estado')) {
            $query->where('estado_incidencia', $request->estado);
        } else {
            // CERRADO (archivo) sale del listado activo por defecto; se ve pidiendo ?estado=CERRADO explícito.
            $query->activas();
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

        return $query->paginate($this->perPage($request))
            ->through(fn ($incidencia) => new IncidenciaResource($incidencia));
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
                if ($incidencia->estado_incidencia === EstadoIncidencia::EnProceso) {
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

            // Ya commiteada: avisa a quienes gestionan. Si la notificación falla, se bitácoriza pero no rompe la creación.
            $this->notificarSinRomper(
                fn () => $this->notificarNuevaIncidencia($incidencia, $request->user()->id),
                $request->user(),
                'IncidenciaController@crearIncidencia (notificación)'
            );

            // Aparece sola en el tablero de gestión de los admins (aparte de la campana, que es la notificación).
            broadcast(new IncidenciaCreada($incidencia));

            return response()->json(
                new IncidenciaResource($incidencia->load([
                    'usuario',
                    'subtipo.tipo',
                    'ciudad',
                    'evidencias',
                ])),
                201
            );
        } catch (\Throwable $e) {
            return $this->errorControlado($e, $request->user(), 'IncidenciaController@crearIncidencia', [
                'ARCHIVO' => 'No se pudieron guardar las fotos. Intenta de nuevo.',
                'default' => 'Error al crear la incidencia',
            ], fn () => Storage::disk('public')->delete($rutasGuardadas));
        }
    }

    // Avisa a quienes pueden gestionar (nunca al propio creador) que hay una incidencia nueva.
    private function notificarNuevaIncidencia(Incidencia $incidencia, int $creadorId): void
    {
        $destinatarios = User::conPermiso('incidencias.gestionar')
            ->where('id', '!=', $creadorId)
            ->get();

        Notification::send(
            $destinatarios,
            new IncidenciaNotification('NUEVA_INCIDENCIA', 'Nueva incidencia reportada: '.$incidencia->nombre_incidencia, $incidencia->id_incidencia)
        );
    }

    // Obtener el detalle completo de una incidencia.
    public function verIncidencia(Request $request, Incidencia $incidencia)
    {
        $this->authorize('ver', $incidencia);

        return new IncidenciaResource(
            $incidencia->load(['usuario', 'subtipo.tipo', 'ciudad.provincia', 'evidencias', 'adminAtiende'])
        );
    }

    // Actualizar datos básicos de la incidencia (solo autor si está PENDIENTE).
    public function actualizarIncidencia(ActualizarIncidenciaRequest $request, Incidencia $incidencia)
    {
        $prioridadAnterior = $incidencia->prioridad_incidencia;

        $incidencia->update($request->validated());

        // La prioridad es lo único de esta ruta que se ve en vivo; el estado va por su propio evento de dominio.
        if ($incidencia->prioridad_incidencia !== $prioridadAnterior) {
            broadcast(new IncidenciaActualizada($incidencia));
        }

        return new IncidenciaResource($incidencia->load(Incidencia::RELACIONES_DETALLE));
    }

    // Si la borra alguien más (el admin), se notifica el motivo al dueño con id_incidencia null (la fila está por desaparecer).
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

                // Reemplaza el ON DELETE CASCADE que tenía la tabla propia: borra las notificaciones de esta incidencia.
                DatabaseNotification::where('data->id_incidencia', (string) $incidencia->id_incidencia)->delete();

                $incidencia->delete();

                if (! $esPropia) {
                    // id_incidencia null: la fila ya no existe, la notificación debe sobrevivir sin apuntar a ella.
                    User::find($idReportador)?->notify(
                        new IncidenciaNotification('INCIDENCIA_ELIMINADA', 'Tu incidencia "'.$nombreIncidencia.'" fue eliminada. Motivo: '.$motivo, null)
                    );
                }
            });

            foreach ($rutasEvidencias as $ruta) {
                if (! Storage::disk('public')->delete($ruta)) {
                    BitacoraError::registrar($request->user(), 'ARCHIVO', 'IncidenciaController@eliminarIncidencia', 'no se pudo borrar '.$ruta);
                }
            }

            return ['message' => 'Incidencia eliminada'];
        } catch (\Throwable $e) {
            return $this->errorControlado($e, $request->user(), 'IncidenciaController@eliminarIncidencia', [
                'default' => 'Error al eliminar la incidencia',
            ]);
        }
    }

    // Ver el historial de cambios de estado de una incidencia.
    public function historialIncidencia(Request $request, Incidencia $incidencia)
    {
        $this->authorize('verHistorial', $incidencia);

        return HistorialEstadoResource::collection(
            $incidencia->historialEstados()->with('usuario')->orderBy('created_at', 'desc')->get()
        );
    }

    // Cambiar el estado (flujo de trabajo): admin o técnico asignado.
    public function cambiarEstado(CambiarEstadoRequest $request, Incidencia $incidencia)
    {
        $nuevo = EstadoIncidencia::from($request->estado_incidencia);
        $actual = $incidencia->estado_incidencia;

        // CERRADO es terminal sin excepciones: se sale del archivo con /archivar o el job, nunca desde acá.
        if ($actual === EstadoIncidencia::Cerrado) {
            return response()->json(['message' => 'No se puede cambiar el estado de una incidencia archivada'], 422);
        }

        // Única excepción al "RESUELTO es terminal": el admin reabre a EN_PROCESO solo si el reportador lo pidió (bandera reapertura_solicitada).
        $esReaperturaDeAdmin = $actual === EstadoIncidencia::Resuelto
            && $nuevo === EstadoIncidencia::EnProceso
            && $request->user()->esAdmin()
            && $incidencia->reapertura_solicitada;

        if ($actual === EstadoIncidencia::Resuelto && ! $esReaperturaDeAdmin) {
            return response()->json(['message' => 'No se puede cambiar el estado de una incidencia ya resuelta'], 422);
        }

        // Guarda estructural (aplica a todos): rechaza saltos que no existen en el grafo del flujo.
        if (! $actual->puedeTransicionarA($nuevo)) {
            return response()->json(['message' => 'Transición de estado no permitida'], 422);
        }

        // Regla de rol (no de la máquina): el técnico responsable solo cierra EN_PROCESO→RESUELTO.
        if (! $request->user()->esAdmin()
            && ! ($actual === EstadoIncidencia::EnProceso && $nuevo === EstadoIncidencia::Resuelto)) {
            return response()->json(['message' => 'Tu rol no puede realizar ese cambio de estado'], 422);
        }

        if ($nuevo === EstadoIncidencia::Resuelto && $actual !== EstadoIncidencia::Resuelto) {
            DB::statement('CALL resolver_incidencia(?, ?)', [$incidencia->id_incidencia, $request->user()->id]);
            $incidencia->refresh();
        } else {
            DB::transaction(function () use ($incidencia, $nuevo, $request, $esReaperturaDeAdmin) {
                DB::statement("SELECT set_config('app.actor_id', ?, true)", [(string) $request->user()->id]);
                $datos = ['estado_incidencia' => $nuevo];
                // Al reabrir se apaga la bandera: recién ahí el reportador podría volver a pedirla.
                if ($esReaperturaDeAdmin) {
                    $datos['reapertura_solicitada'] = false;
                }
                $incidencia->update($datos);
            });
        }

        // Ya se atendió la solicitud: se marcan leídas las de los admins ANTES de emitir el evento,
        // para que el aviso "reabierta" que crea el listener a continuación llegue sin leer.
        if ($esReaperturaDeAdmin) {
            DatabaseNotification::whereNull('read_at')
                ->where('data->tipo', 'SOLICITUD_REAPERTURA')
                ->where('data->id_incidencia', (string) $incidencia->id_incidencia)
                ->update(['read_at' => now()]);
        }

        // Dispara los listeners: notificar (BD + broadcast) e invalidar la caché del dashboard
        // (cubre la rama del SP, que al ser SQL crudo no pasa por el observer de Eloquent).
        event(new IncidenciaCambioEstado($incidencia, $actual->value, $nuevo->value, $request->user()->id));

        return new IncidenciaResource($incidencia->load(Incidencia::RELACIONES_DETALLE));
    }

    // No cambia el estado: solo enciende la bandera y avisa a los admins; solo reabrir (no leer) libera el cupo para otra solicitud.
    public function solicitarReapertura(SolicitarReaperturaRequest $request, Incidencia $incidencia)
    {
        if ($incidencia->reapertura_solicitada) {
            return response()->json(['message' => 'Ya tienes una solicitud de reapertura pendiente de revisión.'], 422);
        }

        $motivo = $request->validated()['motivo'];

        $incidencia->update(['reapertura_solicitada' => true]);

        $admins = User::conPermiso('incidencias.gestionar')->get();
        Notification::send(
            $admins,
            new IncidenciaNotification('SOLICITUD_REAPERTURA', 'Piden reabrir "'.$incidencia->nombre_incidencia.'". Motivo: '.$motivo, $incidencia->id_incidencia)
        );

        return response()->json(['message' => 'Solicitud enviada. Un administrador la revisará.']);
    }

    // El admin decide no reabrir: apaga la bandera (sin tocar el estado) y avisa al reportador por qué se queda como estaba.
    public function rechazarReapertura(RechazarReaperturaRequest $request, Incidencia $incidencia)
    {
        $incidencia->update(['reapertura_solicitada' => false]);

        if ($reportador = User::find($incidencia->id_usuario)) {
            $reportador->notify(new IncidenciaNotification(
                'REAPERTURA_RECHAZADA',
                'Revisamos tu solicitud de reapertura: la incidencia se mantiene resuelta: '.$incidencia->nombre_incidencia,
                $incidencia->id_incidencia,
                correo: true,
            ));
        }

        return new IncidenciaResource($incidencia->load(Incidencia::RELACIONES_DETALLE));
    }

    // "Reclamar" v2: el candado es un lease con latido. El primer admin que reclama queda como dueño,
    // pero si su latido caduca (cerró la app), otro admin puede tomarlo. El UPDATE es atómico:
    // ante dos reclamos simultáneos, solo uno gana la fila.
    public function reclamarIncidencia(ReclamarIncidenciaRequest $request, Incidencia $incidencia)
    {
        $userId = $request->user()->id;

        // Ya lo tengo yo y el lease sigue vivo: nada que reclamar.
        if ($incidencia->id_admin_atiende === $userId && ! $incidencia->reclamoVencido()) {
            return response()->json(['message' => 'Ya reclamaste esta incidencia.'], 422);
        }

        // Otro admin lo atiende y su lease sigue activo: no se puede tomar.
        if ($incidencia->id_admin_atiende !== null
            && $incidencia->id_admin_atiende !== $userId
            && ! $incidencia->reclamoVencido()) {
            return response()->json(['message' => 'Esta incidencia ya fue reclamada por otro administrador.'], 422);
        }

        // Toma la fila si está libre o si el reclamo anterior venció (sin latido reciente).
        $limite = now()->subSeconds(Incidencia::RECLAMO_TTL_SEGUNDOS);
        $reclamada = Incidencia::where('id_incidencia', $incidencia->id_incidencia)
            ->where(function ($q) use ($limite) {
                $q->whereNull('id_admin_atiende')
                    ->orWhereNull('reclamo_visto_en')
                    ->orWhere('reclamo_visto_en', '<', $limite);
            })
            ->update(['id_admin_atiende' => $userId, 'reclamo_visto_en' => now()]);

        if ($reclamada === 0) {
            return response()->json(['message' => 'Esta incidencia ya fue reclamada por otro administrador.'], 422);
        }

        $incidencia = $incidencia->fresh()->load(Incidencia::RELACIONES_DETALLE);
        broadcast(new ReclamoCambiado($incidencia));

        return new IncidenciaResource($incidencia);
    }

    // Liberar el candado: el dueño puede soltar el suyo cuando quiera; super_admin siempre;
    // otro admin solo si el lease venció (el dueño abandonó la app).
    public function liberarReclamo(LiberarReclamoRequest $request, Incidencia $incidencia)
    {
        if ($incidencia->id_admin_atiende === null) {
            return response()->json(['message' => 'Esta incidencia no está reclamada.'], 422);
        }

        $user = $request->user();
        $puede = $incidencia->id_admin_atiende === $user->id
            || $user->esSuperAdmin()
            || $incidencia->reclamoVencido();

        if (! $puede) {
            return response()->json(['message' => 'El administrador sigue atendiendo esta incidencia.'], 422);
        }

        $incidencia->update(['id_admin_atiende' => null, 'reclamo_visto_en' => null]);

        $incidencia = $incidencia->fresh()->load(Incidencia::RELACIONES_DETALLE);
        broadcast(new ReclamoCambiado($incidencia));

        return new IncidenciaResource($incidencia);
    }

    // Latido del candado: mientras el admin tiene la app abierta refresca el lease de todos sus reclamos.
    // Silencioso (sin broadcast); solo evita que sus reclamos venzan.
    public function heartbeatReclamo(Request $request)
    {
        Incidencia::where('id_admin_atiende', $request->user()->id)
            ->update(['reclamo_visto_en' => now()]);

        return response()->noContent();
    }

    // Cerrar/archivar: solo el admin dueño (id_admin_atiende), y solo desde RESUELTO.
    public function archivarIncidencia(ArchivarIncidenciaRequest $request, Incidencia $incidencia)
    {
        if (! $incidencia->estaResuelta()) {
            return response()->json(['message' => 'Solo se pueden archivar incidencias resueltas.'], 422);
        }

        DB::transaction(function () use ($incidencia, $request) {
            DB::statement("SELECT set_config('app.actor_id', ?, true)", [(string) $request->user()->id]);
            $incidencia->update(['estado_incidencia' => EstadoIncidencia::Cerrado->value]);
        });

        // Notifica el archivado (RESUELTO -> CERRADO) e invalida la caché vía listeners.
        event(new IncidenciaCambioEstado($incidencia, EstadoIncidencia::Resuelto->value, EstadoIncidencia::Cerrado->value, $request->user()->id));

        return new IncidenciaResource($incidencia->load(Incidencia::RELACIONES_DETALLE));
    }
}
