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

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $esAdmin || $esPropietario ? $this->email : null,
            'foto_perfil' => $this->foto_perfil,
            'id_rol' => $this->id_rol,
            'rol' => $this->whenLoaded('rol'),
        ];
    }
}
