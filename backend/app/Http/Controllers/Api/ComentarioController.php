<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BitacoraError;
use App\Models\Incidencia;
use Illuminate\Http\Request;

class ComentarioController extends Controller
{
    // Helpers de permisos

    private function esAdmin(Request $request): bool
    {
        $user = $request->user();

        return $user->rol && $user->rol->nombre_rol === 'admin';
    }

    private function esAutor(Request $request, Incidencia $incidencia): bool
    {
        return $incidencia->id_usuario === $request->user()->id;
    }

    private function esTecnicoAsignado(Request $request, Incidencia $incidencia): bool
    {
        return $incidencia->asignaciones()->where('id_usuario', $request->user()->id)->exists();
    }

    // Listar los comentarios de una incidencia (orden cronológico).
    public function listadoComentarios(Request $request, Incidencia $incidencia)
    {
        // El "normal" solo ve los de sus propias incidencias
        $user = $request->user();
        if ($user->rol && $user->rol->nombre_rol === 'normal' && ! $this->esAutor($request, $incidencia)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json(
            $incidencia->comentarios()->with('usuario')->orderBy('created_at', 'asc')->get()
        );
    }

    // Crear un comentario en una incidencia.
    public function crearComentario(Request $request, Incidencia $incidencia)
    {
        // Pueden comentar: admin, autor o técnico asignado
        $puede = $this->esAdmin($request)
            || $this->esAutor($request, $incidencia)
            || $this->esTecnicoAsignado($request, $incidencia);

        if (! $puede) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $datos = $request->validate([
            'comentario' => 'required|string|min:1|max:1000',
        ]);

        try {
            // El trigger tr_notificar_nuevo_comentario avisa al reportador automáticamente
            $comentario = $incidencia->comentarios()->create([
                'id_usuario' => $request->user()->id,
                'comentario' => $datos['comentario'],
            ]);

            return response()->json($comentario->load('usuario'), 201);
        } catch (\Exception $e) {
            BitacoraError::create([
                'id_usuario' => $request->user()->id,
                'tipo_error' => 'SERVIDOR',
                'descripcion_error' => 'ComentarioController@crearComentario: '.$e->getMessage(),
            ]);

            return response()->json(['message' => 'Error al crear el comentario'], 500);
        }
    }
}
