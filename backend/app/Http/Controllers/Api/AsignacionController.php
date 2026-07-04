<?php

namespace App\Http\Controllers\Api;

use App\Enums\RolAsignacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\AsignarTecnicoRequest;
use App\Models\AsignacionIncidencia;
use App\Models\BitacoraError;
use App\Models\Incidencia;
use App\Models\User;
use App\Notifications\IncidenciaNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AsignacionController extends Controller
{
    // Listar los técnicos disponibles (para el desplegable de asignación).
    public function tecnicos()
    {
        return User::conRol('tecnico')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();
    }

    // Listar las asignaciones (responsable + apoyo) de una incidencia.
    public function listado(Incidencia $incidencia)
    {
        return $incidencia->asignaciones()->with('usuario')->get();
    }

    // Asignar un técnico a una incidencia (llama al procedimiento asignar_tecnico).
    public function asignar(AsignarTecnicoRequest $request, Incidencia $incidencia)
    {
        $this->authorize('asignarTecnico', $incidencia);

        if ($incidencia->esTerminal()) {
            return response()->json(['message' => 'La incidencia está resuelta; no se pueden cambiar las asignaciones.'], 422);
        }

        $datos = $request->validated();

        if ($incidencia->asignaciones()->where('id_usuario', $datos['id_usuario'])->exists()) {
            return response()->json(['message' => 'Este técnico ya está asignado a esta incidencia.'], 422);
        }

        if ($datos['rol_asignado'] === RolAsignacion::Responsable->value
            && $incidencia->asignaciones()->where('rol_asignado', RolAsignacion::Responsable->value)->exists()) {
            return response()->json([
                'message' => 'Esta incidencia ya tiene un responsable. Quita el actual antes de asignar otro.',
            ], 422);
        }

        try {
            DB::statement('CALL asignar_tecnico(?, ?, ?)', [
                $incidencia->id_incidencia,
                $datos['id_usuario'],
                $datos['rol_asignado'],
            ]);

            // Ya asignado: si la notificación falla, se bitácoriza pero no rompe la asignación.
            try {
                $this->notificarAsignacion($incidencia, (int) $datos['id_usuario'], $datos['rol_asignado']);
            } catch (\Throwable $e) {
                BitacoraError::registrar($request->user(), 'SERVIDOR', 'AsignacionController@asignar (notificación)', $e->getMessage());
            }

            return response()->json($incidencia->load('asignaciones.usuario'), 201);
        } catch (QueryException $e) {
            BitacoraError::registrar($request->user(), 'BASE_DATOS', 'AsignacionController@asignar', $e->getMessage(), $e);

            return response()->json(['message' => 'No se pudo asignar el técnico. Inténtalo de nuevo.'], 422);
        }
    }

    // Avisa al técnico asignado y, si es RESPONSABLE, también al reportador (reemplaza al trigger fn_notificar_asignacion).
    private function notificarAsignacion(Incidencia $incidencia, int $idTecnico, string $rol): void
    {
        $nombre = $incidencia->nombre_incidencia;

        // correo: true → al técnico también le llega el aviso por email.
        if ($tecnico = User::find($idTecnico)) {
            $tecnico->notify(new IncidenciaNotification('ASIGNACION', 'Te asignaron a una incidencia ('.$rol.'): '.$nombre, $incidencia->id_incidencia, correo: true));
        }

        if ($rol === RolAsignacion::Responsable->value && $incidencia->id_usuario !== $idTecnico) {
            if ($reportador = User::find($incidencia->id_usuario)) {
                $reportador->notify(new IncidenciaNotification('ASIGNACION', 'Tu incidencia ya tiene un responsable asignado: '.$nombre, $incidencia->id_incidencia));
            }
        }
    }

    // Quitar una asignación.
    public function quitar(AsignacionIncidencia $asignacion)
    {
        $this->authorize('quitar', $asignacion);

        if ($asignacion->incidencia && $asignacion->incidencia->esTerminal()) {
            return response()->json(['message' => 'La incidencia está resuelta; no se pueden cambiar las asignaciones.'], 422);
        }

        $asignacion->delete();

        return ['message' => 'Asignación eliminada'];
    }
}
