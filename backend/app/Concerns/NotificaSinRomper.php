<?php

namespace App\Concerns;

use App\Models\BitacoraError;
use App\Models\User;

// Lo comparten los controllers y los listeners que notifican: desde que el canal database va en línea
// (ver IncidenciaNotification::viaConnections), un fallo al notificar ocurre dentro de la petición y
// después del commit, así que sin esto una operación ya confirmada devolvería 500.
trait NotificaSinRomper
{
    // Ejecuta el envío de notificaciones sin dejar que un fallo rompa la respuesta ya commiteada; solo lo bitacoriza.
    protected function notificarSinRomper(callable $accion, ?User $actor, string $contexto): void
    {
        try {
            $accion();
        } catch (\Throwable $e) {
            BitacoraError::registrar($actor, 'SERVIDOR', $contexto, $e->getMessage());
        }
    }
}
