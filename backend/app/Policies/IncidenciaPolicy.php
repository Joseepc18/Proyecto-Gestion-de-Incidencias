<?php

namespace App\Policies;

use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class IncidenciaPolicy
{
    // Ver una incidencia: el ciudadano solo las suyas; admin y técnico, cualquiera.
    public function ver(User $user, Incidencia $incidencia): Response
    {
        if ($user->esAdmin() || $user->esTecnico() || $incidencia->id_usuario === $user->id) {
            return Response::allow();
        }

        return Response::deny('No autorizado');
    }

    // El historial sigue las mismas reglas que ver el detalle.
    public function verHistorial(User $user, Incidencia $incidencia): Response
    {
        return $this->ver($user, $incidencia);
    }

    // Editar: admin o técnico asignado siempre; el autor solo mientras esté PENDIENTE.
    public function actualizar(User $user, Incidencia $incidencia): Response
    {
        if ($user->esAdmin() || $this->esTecnicoAsignado($user, $incidencia)) {
            return Response::allow();
        }

        if ($incidencia->id_usuario === $user->id) {
            return $incidencia->estado_incidencia === 'PENDIENTE'
                ? Response::allow()
                : Response::deny('No puedes editar esta incidencia porque ya está en proceso. Usa los comentarios para comunicarte con el equipo.');
        }

        return Response::deny('No autorizado');
    }

    // Eliminar: solo admin o autor.
    public function eliminar(User $user, Incidencia $incidencia): Response
    {
        return $user->esAdmin() || $incidencia->id_usuario === $user->id
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Cambiar de estado: admin (libre) o técnico asignado (la transición la valida el controller).
    public function cambiarEstado(User $user, Incidencia $incidencia): Response
    {
        return $user->esAdmin() || $this->esTecnicoAsignado($user, $incidencia)
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Subir evidencias: admin, autor o técnico asignado.
    public function subirEvidencia(User $user, Incidencia $incidencia): Response
    {
        return $user->esAdmin()
        || $incidencia->id_usuario === $user->id
        || $this->esTecnicoAsignado($user, $incidencia)
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Ver/escribir el chat: reportador, admin y técnico RESPONSABLE (el apoyo queda fuera).
    public function verChat(User $user, Incidencia $incidencia): Response
    {
        $esResponsable = $incidencia->asignaciones()
            ->where('id_usuario', $user->id)
            ->where('rol_asignado', 'RESPONSABLE')
            ->exists();

        return $user->esAdmin() || $incidencia->id_usuario === $user->id || $esResponsable
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Un técnico asignado (responsable o apoyo) a la incidencia.
    private function esTecnicoAsignado(User $user, Incidencia $incidencia): bool
    {
        return $incidencia->asignaciones()->where('id_usuario', $user->id)->exists();
    }
}
