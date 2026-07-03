<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $esAdmin = $request->user()?->esAdmin();
        $esPropietario = $request->user()?->id === $this->id;

        // Solo emitimos las claves de permiso cuando se cargó rol.permisos (recurso de sesión: /user, login, registro).
        $permisos = $this->relationLoaded('rol') && $this->rol?->relationLoaded('permisos')
            ? $this->rol->permisos->pluck('clave_permiso')->values()
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $esAdmin || $esPropietario ? $this->email : null,
            'foto_perfil' => $this->foto_perfil,
            'id_rol' => $this->id_rol,
            'rol' => $this->whenLoaded('rol'),
            'permisos' => $permisos,
        ];
    }
}
