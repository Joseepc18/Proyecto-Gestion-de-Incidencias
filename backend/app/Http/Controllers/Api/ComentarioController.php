<?php

namespace App\Http\Controllers\Api;

use App\Events\ComentarioCreado;
use App\Http\Controllers\Controller;
use App\Http\Requests\CrearComentarioRequest;
use App\Http\Requests\EditarComentarioRequest;
use App\Http\Resources\ComentarioResource;
use App\Models\Comentario;
use App\Models\Incidencia;
use Illuminate\Http\Request;

class ComentarioController extends Controller
{
    // Listar los comentarios de una incidencia (orden cronológico).
    public function listadoComentarios(Request $request, Incidencia $incidencia)
    {
        $this->authorize('verChat', $incidencia);

        return ComentarioResource::collection(
            $incidencia->comentarios()->with('usuario.rol')->orderBy('created_at', 'asc')->get()
        );
    }

    // Crear un comentario en una incidencia.
    public function crearComentario(CrearComentarioRequest $request, Incidencia $incidencia)
    {
        $datos = $request->validated();

        $comentario = $incidencia->comentarios()->create([
            'id_usuario' => $request->user()->id,
            'comentario' => $datos['comentario'],
        ]);

        event(new ComentarioCreado($comentario));

        return (new ComentarioResource($comentario->load('usuario.rol')))
            ->response()
            ->setStatusCode(201);
    }

    // Editar un comentario propio (la autorización la resuelve el FormRequest).
    public function actualizarComentario(EditarComentarioRequest $request, Comentario $comentario)
    {
        $comentario->update($request->validated());

        return new ComentarioResource($comentario->load('usuario.rol'));
    }
}
