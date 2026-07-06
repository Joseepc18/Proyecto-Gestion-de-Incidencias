<?php

namespace App\Policies;

use App\Enums\EstadoIncidencia;
use App\Models\Incidencia;
use App\Models\User;
use App\Policies\Concerns\AutorizaReclamo;
use Illuminate\Auth\Access\Response;

class IncidenciaPolicy
{
    use AutorizaReclamo;

    // Ver una incidencia: el admin ve todas; el ciudadano las suyas; el técnico solo las que tiene asignadas.
    public function ver(User $user, Incidencia $incidencia): Response
    {
        if ($user->esAdmin() || $incidencia->id_usuario === $user->id) {
            return Response::allow();
        }

        // El técnico (responsable o de apoyo) solo ve las incidencias donde está asignado.
        if ($user->esTecnico() && $this->esAsignado($user, $incidencia)) {
            return Response::allow();
        }

        return Response::deny('No autorizado');
    }

    // El historial sigue las mismas reglas que ver el detalle.
    public function verHistorial(User $user, Incidencia $incidencia): Response
    {
        return $this->ver($user, $incidencia);
    }

    // Editar los detalles: quien tiene el permiso de gestión y es dueño del reclamo, o el autor mientras esté PENDIENTE; en RESUELTO/CERRADO es de solo lectura para todos.
    public function actualizar(User $user, Incidencia $incidencia): Response
    {
        if ($incidencia->esTerminal()) {
            return Response::deny('La incidencia está resuelta; no se puede editar.');
        }

        if ($user->tienePermiso('incidencias.gestionar')) {
            return $this->esDuenoDelReclamo($user, $incidencia);
        }

        if ($incidencia->id_usuario === $user->id) {
            return $incidencia->estado_incidencia === EstadoIncidencia::Pendiente
                ? Response::allow()
                : Response::deny('No puedes editar esta incidencia porque ya está en proceso. Usa los comentarios para comunicarte con el equipo.');
        }

        return Response::deny('No autorizado');
    }

    // Eliminar: quien tiene el permiso de eliminar (siempre) o autor (solo mientras esté PENDIENTE, para no perder trazabilidad).
    public function eliminar(User $user, Incidencia $incidencia): Response
    {
        if ($user->tienePermiso('incidencias.eliminar')) {
            return Response::allow();
        }

        if ($incidencia->id_usuario === $user->id) {
            return $incidencia->estado_incidencia === EstadoIncidencia::Pendiente
                ? Response::allow()
                : Response::deny('No puedes eliminar esta incidencia porque ya está en proceso o resuelta.');
        }

        return Response::deny('No autorizado');
    }

    // Cambiar de estado: el admin DUEÑO del reclamo, o el técnico RESPONSABLE (el de APOYO no cambia estados; la transición la valida el controller).
    public function cambiarEstado(User $user, Incidencia $incidencia): Response
    {
        if ($user->esResponsableDe($incidencia)) {
            return Response::allow();
        }

        return $user->tienePermiso('incidencias.gestionar')
            ? $this->esDuenoDelReclamo($user, $incidencia)
            : Response::deny('No autorizado');
    }

    // Subir evidencias: autor (REPORTE) o técnico RESPONSABLE (RESOLUCION); en RESUELTO/CERRADO nadie sube (pedir reapertura).
    public function subirEvidencia(User $user, Incidencia $incidencia): Response
    {
        if ($incidencia->esTerminal()) {
            return Response::deny('No se pueden agregar evidencias a una incidencia resuelta.');
        }

        return ($incidencia->id_usuario === $user->id || $user->esResponsableDe($incidencia))
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Ver el chat (lectura): reportador, admin y técnico RESPONSABLE; sigue permitido en RESUELTO (se conserva el historial).
    public function verChat(User $user, Incidencia $incidencia): Response
    {
        return $user->participaEn($incidencia)
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Escribir en el chat: mismos roles que verChat, pero NO en RESUELTO/CERRADO (solo lectura).
    // Un admin/super_admin sin el permiso de gestión (view-only) ve el chat pero no escribe en él.
    public function comentar(User $user, Incidencia $incidencia): Response
    {
        if ($incidencia->esTerminal()) {
            return Response::deny('La incidencia está resuelta; el chat es solo de lectura.');
        }

        if ($user->esAdmin() && ! $user->tienePermiso('incidencias.gestionar')) {
            return Response::deny('No autorizado');
        }

        return $user->participaEn($incidencia)
            ? Response::allow()
            : Response::deny('No autorizado');
    }

    // Solicitar reapertura: solo el reportador y solo si ya está RESUELTO.
    public function solicitarReapertura(User $user, Incidencia $incidencia): Response
    {
        if ($incidencia->id_usuario !== $user->id) {
            return Response::deny('No autorizado');
        }

        return $incidencia->estaResuelta()
            ? Response::allow()
            : Response::deny('Solo puedes solicitar la reapertura de una incidencia ya resuelta.');
    }

    // Rechazar la solicitud de reapertura: quien tiene el permiso de gestión, y solo si hay una solicitud pendiente.
    public function rechazarReapertura(User $user, Incidencia $incidencia): Response
    {
        if (! $user->tienePermiso('incidencias.gestionar')) {
            return Response::deny('No autorizado');
        }

        return $incidencia->reapertura_solicitada
            ? Response::allow()
            : Response::deny('Esta incidencia no tiene una solicitud de reapertura pendiente.');
    }

    // Asignar un técnico: solo quien tiene el permiso de gestión y es DUEÑO del reclamo (el estado RESUELTO ya lo valida el controller con su propio 422).
    public function asignarTecnico(User $user, Incidencia $incidencia): Response
    {
        return $user->tienePermiso('incidencias.gestionar')
            ? $this->esDuenoDelReclamo($user, $incidencia)
            : Response::deny('No autorizado');
    }

    // Reclamar: cualquiera con el permiso de gestión puede intentarlo; el controller valida de forma atómica que nadie se le adelante.
    public function reclamar(User $user, Incidencia $incidencia): Response
    {
        if (! $user->tienePermiso('incidencias.gestionar')) {
            return Response::deny('No autorizado');
        }

        return $incidencia->estaCerrada()
            ? Response::deny('No se puede reclamar una incidencia archivada.')
            : Response::allow();
    }

    // Archivar/cerrar: solo el admin que reclamó esta incidencia (el controller valida que esté RESUELTO).
    public function archivar(User $user, Incidencia $incidencia): Response
    {
        return $incidencia->id_admin_atiende === $user->id
            ? Response::allow()
            : Response::deny('Solo el administrador que reclamó esta incidencia puede archivarla.');
    }

    // El técnico está asignado a la incidencia (responsable o de apoyo).
    private function esAsignado(User $user, Incidencia $incidencia): bool
    {
        return $incidencia->asignaciones()
            ->where('id_usuario', $user->id)
            ->exists();
    }
}
