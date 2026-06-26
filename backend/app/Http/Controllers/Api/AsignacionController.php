<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AsignarTecnicoRequest;
use App\Models\AsignacionIncidencia;
use App\Models\BitacoraError;
use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AsignacionController extends Controller
{
    // Listar los técnicos disponibles (para el desplegable de asignación).
    public function tecnicos()
    {
        return response()->json(
            User::whereHas('rol', fn ($q) => $q->where('nombre_rol', 'tecnico'))
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get()
        );
    }

    // Listar las asignaciones (responsable + apoyo) de una incidencia.
    public function listado(Incidencia $incidencia)
    {
        return response()->json(
            $incidencia->asignaciones()->with('usuario')->get()
        );
    }

    // Asignar un técnico a una incidencia (llama al procedimiento asignar_tecnico).
    public function asignar(AsignarTecnicoRequest $request, Incidencia $incidencia)
    {
        $datos = $request->validated();

        // Validaciones con mensajes claros (la BD las repite como red de seguridad).
        if ($incidencia->asignaciones()->where('id_usuario', $datos['id_usuario'])->exists()) {
            return response()->json(['message' => 'Este técnico ya está asignado a esta incidencia.'], 422);
        }

        if ($datos['rol_asignado'] === 'RESPONSABLE'
            && $incidencia->asignaciones()->where('rol_asignado', 'RESPONSABLE')->exists()) {
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

            // Cargar asignaciones y retornar
            return response()->json($incidencia->load('asignaciones.usuario'), 201);
        } catch (QueryException $e) {
            BitacoraError::create([
                'id_usuario' => $request->user()->id,
                'tipo_error' => 'BASE_DATOS',
                'descripcion_error' => 'AsignacionController@asignar: '.$e->getMessage(),
            ]);

            return response()->json(['message' => 'No se pudo asignar el técnico. Inténtalo de nuevo.'], 422);
        }
    }

    // Quitar una asignación.
    public function quitar(AsignacionIncidencia $asignacion)
    {
        $asignacion->delete();

        return response()->json(['message' => 'Asignación eliminada']);
    }
}
