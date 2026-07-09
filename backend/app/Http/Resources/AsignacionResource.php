<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsignacionResource extends JsonResource
{
    // El usuario anidado pasa por UserResource para no filtrar su email fuera de admin/propietario.
    public function toArray(Request $request): array
    {
        return [
            'id_asignacion' => $this->id_asignacion,
            'id_incidencia' => $this->id_incidencia,
            'id_usuario' => $this->id_usuario,
            'rol_asignado' => $this->rol_asignado,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Técnico asignado puede estar suspendido (soft delete): la relación carga null, no falta.
            'usuario' => $this->whenLoaded('usuario', fn () => $this->usuario ? new UserResource($this->usuario) : null),
        ];
    }
}
