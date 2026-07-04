<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistorialEstadoResource extends JsonResource
{
    // El usuario anidado pasa por UserResource para no filtrar su email fuera de admin/propietario.
    public function toArray(Request $request): array
    {
        return [
            'id_historial' => $this->id_historial,
            'id_incidencia' => $this->id_incidencia,
            'id_usuario' => $this->id_usuario,
            'estado_anterior' => $this->estado_anterior,
            'estado_nuevo' => $this->estado_nuevo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'usuario' => $this->whenLoaded('usuario', fn () => new UserResource($this->usuario)),
        ];
    }
}
