<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CrearComentarioRequest;
use App\Http\Requests\EditarComentarioRequest;
use App\Models\BitacoraError;
use App\Models\Comentario;
use App\Models\Incidencia;
use Illuminate\Http\Request;

class ComentarioController extends Controller
{
    // Listar los comentarios de una incidencia (orden cronológico).
    public function listadoComentarios(Request $request, Incidencia $incidencia)
    {
        $this->authorize('verChat', $incidencia);

        return response()->json(
            $incidencia->comentarios()->with('usuario.rol')->orderBy('created_at', 'asc')->get()
        );
    }

    // Crear un comentario en una incidencia.
    public function crearComentario(CrearComentarioRequest $request, Incidencia $incidencia)
    {
        $datos = $request->validated();

        try {
            $comentario = $incidencia->comentarios()->create([
                'id_usuario' => $request->user()->id,
                'comentario' => $datos['comentario'],
            ]);

            return response()->json($comentario->load('usuario.rol'), 201);
        } catch (\Exception $e) {
            BitacoraError::create([
                'id_usuario' => $request->user()->id,
                'tipo_error' => 'SERVIDOR',
                'descripcion_error' => 'ComentarioController@crearComentario: '.$e->getMessage(),
            ]);

            return response()->json(['message' => 'Error al crear el comentario'], 500);
        }
    }

    // Editar un comentario propio (la autorización la resuelve el FormRequest).
    public function actualizarComentario(EditarComentarioRequest $request, Comentario $comentario)
    {
        $comentario->update($request->validated());

        return response()->json($comentario->load('usuario.rol'));
    }
}
