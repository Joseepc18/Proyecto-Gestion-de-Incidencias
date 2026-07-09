<?php

namespace App\Events;

use App\Models\Incidencia;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Hecho de dominio: lo disparan los puntos que cambian el estado; los listeners notifican e invalidan caché por separado.
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
