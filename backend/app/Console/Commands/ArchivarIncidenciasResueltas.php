<?php

namespace App\Console\Commands;

use App\Enums\EstadoIncidencia;
use App\Events\IncidenciaCambioEstado;
use App\Models\Incidencia;
use Illuminate\Console\Command;

class ArchivarIncidenciasResueltas extends Command
{
    protected $signature = 'incidencias:archivar-resueltas';

    protected $description = 'Archiva (CERRADO) las incidencias RESUELTO con más de 24h y sin solicitud de reapertura pendiente';

    public function handle(): int
    {
        // Sin set_config('app.actor_id'): el trigger de historial queda con id_usuario NULL, o sea "lo hizo el sistema".
        $incidencias = Incidencia::resueltas()
            ->where('fecha_resolucion', '<=', now()->subHours(Incidencia::HORAS_PARA_ARCHIVAR))
            ->where('reapertura_solicitada', false)
            ->get();

        foreach ($incidencias as $incidencia) {
            // El update dispara el IncidenciaObserver y el event dispara InvalidarCacheDashboard: la caché ya se limpia sola.
            // Suelta el candado junto con el archivado, igual que /archivar: si no, el reclamo del admin quedaría puesto para siempre.
            $incidencia->update([
                'estado_incidencia' => EstadoIncidencia::Cerrado->value,
                'id_admin_atiende' => null,
                'reclamo_visto_en' => null,
            ]);
            // actor null = lo archivó el sistema: el listener avisa a reportador y técnicos sin excluir a nadie.
            event(new IncidenciaCambioEstado($incidencia, EstadoIncidencia::Resuelto->value, EstadoIncidencia::Cerrado->value, null));
        }

        $this->info($incidencias->count().' incidencia(s) archivada(s).');

        return self::SUCCESS;
    }
}
