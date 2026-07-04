<?php

namespace App\Events;

use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Hecho de dominio: se dispara una vez por lote de fotos, tras el commit; el listener notifica a quien corresponde.
class EvidenciaSubida
{
    use Dispatchable, SerializesModels;

    // $actor es quien subió las fotos (para no auto-notificarlo).
    public function __construct(
        public Incidencia $incidencia,
        public User $actor,
    ) {}
}
