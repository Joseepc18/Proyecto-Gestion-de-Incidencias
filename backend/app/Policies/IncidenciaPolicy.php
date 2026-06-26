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

    // Editar los detalles: solo admin o el autor mientras esté PENDIENTE.
    // Los técnicos (responsable incluido) NO editan los detalles de la incidencia.
    public function actualizar(User $user, Incidencia $incidencia): Response
    {
        if ($user->esAdmin()) {
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

    // Cambiar de estado: admin (libre) o el técnico RESPONSABLE (la transición la valida el controller).
    // El técnico de APOYO no cambia estados (solo puede ver el detalle).
    public function cambiarEstado(User $user, Incidencia $incidencia): Response
    {
        return $user->esAdmin() || $this->esResponsable($user, $incidencia)
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Subir evidencias: admin, autor (fotos de REPORTE) o el técnico RESPONSABLE (foto de RESOLUCION).
    // El técnico de APOYO no sube evidencias.
    public function subirEvidencia(User $user, Incidencia $incidencia): Response
    {
        return $user->esAdmin()
        || $incidencia->id_usuario === $user->id
        || $this->esResponsable($user, $incidencia)
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Ver/escribir el chat: reportador, admin y técnico RESPONSABLE (el apoyo queda fuera).
    public function verChat(User $user, Incidencia $incidencia): Response
    {
        return $user->esAdmin() || $incidencia->id_usuario === $user->id || $this->esResponsable($user, $incidencia)
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // El técnico RESPONSABLE de la incidencia (el de APOYO no cuenta: solo puede ver).
    private function esResponsable(User $user, Incidencia $incidencia): bool
    {
        return $incidencia->asignaciones()
            ->where('id_usuario', $user->id)
            ->where('rol_asignado', 'RESPONSABLE')
            ->exists();
    }
}
