<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComentarioResource extends JsonResource
{
    // El usuario anidado pasa por UserResource para no filtrar su email fuera de admin/propietario.
    public function toArray(Request $request): array
    {
        return [
            'id_comentario' => $this->id_comentario,
            'id_incidencia' => $this->id_incidencia,
            'id_usuario' => $this->id_usuario,
            'comentario' => $this->comentario,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Autor del comentario puede estar suspendido (soft delete): la relación carga null, no falta.
            'usuario' => $this->whenLoaded('usuario', fn () => $this->usuario ? new UserResource($this->usuario) : null),
        ];
    }
}
