<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AsignacionIncidencia;
use App\Models\BitacoraError;
use App\Models\Incidencia;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsignacionController extends Controller
{
    // Listar las asignaciones (responsable + apoyo) de una incidencia.
    public function listado(Incidencia $incidencia)
    {
        return response()->json(
            $incidencia->asignaciones()->with('usuario')->get()
        );
    }

    // Asignar un técnico a una incidencia (llama al procedimiento asignar_tecnico).
    public function asignar(Request $request, Incidencia $incidencia)
    {
        $datos = $request->validate([
            'id_usuario' => 'required|exists:users,id',
            'rol_asignado' => 'required|in:RESPONSABLE,APOYO',
        ]);

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
                'tipo_error' => 'AsignacionController@asignar',
                'descripcion_error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'No se pudo asignar el técnico'], 422);
        }
    }

    // Quitar una asignación.
    public function quitar(AsignacionIncidencia $asignacion)
    {
        $asignacion->delete();

        return response()->json(['message' => 'Asignación eliminada']);
    }
}
