<?php

namespace App\Console\Commands;

use App\Enums\EstadoIncidencia;
use App\Models\Incidencia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ArchivarIncidenciasResueltas extends Command
{
    protected $signature = 'incidencias:archivar-resueltas';

    protected $description = 'Archiva (CERRADO) las incidencias RESUELTO con más de 24h y sin solicitud de reapertura pendiente';

    public function handle(): int
    {
        // Sin set_config('app.actor_id'): el trigger de historial queda con id_usuario NULL, o sea "lo hizo el sistema".
        $incidencias = Incidencia::where('estado_incidencia', EstadoIncidencia::Resuelto->value)
            ->where('fecha_resolucion', '<=', now()->subHours(24))
            ->where('reapertura_solicitada', false)
            ->get();

        foreach ($incidencias as $incidencia) {
            $incidencia->update(['estado_incidencia' => EstadoIncidencia::Cerrado->value]);
        }

        if ($incidencias->isNotEmpty()) {
            Cache::forget('dashboard_metricas');
        }

        $this->info($incidencias->count().' incidencia(s) archivada(s).');

        return self::SUCCESS;
    }
}
