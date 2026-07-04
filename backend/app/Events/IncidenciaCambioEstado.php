<?php

namespace App\Events;

use App\Models\Incidencia;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Hecho de dominio: una incidencia cambió de estado. Lo disparan los puntos que cambian el estado
// (cambiarEstado, archivar, el comando de auto-archivado). Sus listeners reaccionan por separado:
// uno notifica (BD + broadcast), otro invalida la caché del dashboard.
class IncidenciaCambioEstado
{
    use Dispatchable, SerializesModels;

    // $actorId es quien ejecutó el cambio (para no auto-notificarlo); null = lo hizo el sistema.
    public function __construct(
        public Incidencia $incidencia,
        public string $estadoAnterior,
        public string $estadoNuevo,
        public ?int $actorId,
    ) {}
}
