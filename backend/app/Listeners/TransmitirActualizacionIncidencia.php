<?php

namespace App\Listeners;

use App\Events\IncidenciaActualizada;
use App\Events\IncidenciaCambioEstado;

// Al cambiar el estado (cambiarEstado, archivar, auto-archivado), transmite el update en vivo al detalle y al tablero.
class TransmitirActualizacionIncidencia
{
    public function handle(IncidenciaCambioEstado $evento): void
    {
        broadcast(new IncidenciaActualizada($evento->incidencia));
    }
}
