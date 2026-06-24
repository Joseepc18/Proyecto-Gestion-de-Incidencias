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

    // Solo el técnico RESPONSABLE participa del chat (el apoyo queda fuera).
    private function esResponsable(Request $request, Incidencia $incidencia): bool
    {
        return $incidencia->asignaciones()
            ->where('id_usuario', $request->user()->id)
            ->where('rol_asignado', 'RESPONSABLE')
            ->exists();
    }

    // El chat es entre el reportador, el admin y el técnico responsable.
    private function puedeVerChat(Request $request, Incidencia $incidencia): bool
    {
        return $this->esAdmin($request)
            || $this->esAutor($request, $incidencia)
            || $this->esResponsable($request, $incidencia);
    }

    // Listar los comentarios de una incidencia (orden cronológico).
    public function listadoComentarios(Request $request, Incidencia $incidencia)
    {
        if (! $this->puedeVerChat($request, $incidencia)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Carga el rol del autor para etiquetar cada burbuja del chat.
        return response()->json(
            $incidencia->comentarios()->with('usuario.rol')->orderBy('created_at', 'asc')->get()
        );
    }

    // Crear un comentario en una incidencia.
    public function crearComentario(Request $request, Incidencia $incidencia)
    {
        if (! $this->puedeVerChat($request, $incidencia)) {
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
}
