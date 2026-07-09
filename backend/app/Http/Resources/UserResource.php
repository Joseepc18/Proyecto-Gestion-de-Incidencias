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
            // Solo al dueño: correo nuevo aún sin confirmar (para avisar del cambio pendiente en el perfil).
            'email_pendiente' => $esPropietario ? $this->email_pendiente : null,
            // URL firmada y temporal (no la ruta cruda del disco): el disco 'perfiles' es privado.
            'foto_perfil' => $this->foto_perfil_url,
            'email_verificado' => $this->email_verified_at !== null,
            'id_rol' => $this->id_rol,
            'rol' => $this->whenLoaded('rol'),
            'permisos' => $permisos,
            // Solo al dueño: si tiene 2FA activo y si su rol lo exige (para que el front lo guíe a configurarlo).
            'two_factor_enabled' => $esPropietario ? $this->hasEnabledTwoFactorAuthentication() : null,
            'two_factor_required' => $esPropietario ? ($this->esAdmin() && ! $this->hasEnabledTwoFactorAuthentication()) : null,
        ];
    }
}
