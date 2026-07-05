<?php

namespace App\Console\Commands;

use App\Enums\PrioridadIncidencia;
use App\Models\Incidencia;
use App\Models\User;
use App\Notifications\IncidenciaNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class EscalarIncidenciasAntiguas extends Command
{
    protected $signature = 'incidencias:escalar-antiguas';

    protected $description = 'Sube un nivel de prioridad a las incidencias PENDIENTE sin atender hace más de 24h';

    // BAJA→MEDIA, MEDIA→ALTA; ALTA ya es el tope y queda fuera de la consulta.
    private const SIGUIENTE_PRIORIDAD = [
        'BAJA' => 'MEDIA',
        'MEDIA' => 'ALTA',
    ];

    public function handle(): int
    {
        $incidencias = Incidencia::pendientes()
            ->whereNull('id_admin_atiende')
            ->where('created_at', '<=', now()->subHours(24))
            ->where('prioridad_incidencia', '!=', PrioridadIncidencia::Alta->value)
            ->get();

        $admins = User::conPermiso('incidencias.gestionar')->get();

        foreach ($incidencias as $incidencia) {
            $anterior = $incidencia->prioridad_incidencia->value;
            // El observer invalida la caché del dashboard con el update, igual que cualquier otro cambio.
            $incidencia->update(['prioridad_incidencia' => self::SIGUIENTE_PRIORIDAD[$anterior]]);

            Notification::send($admins, new IncidenciaNotification(
                'ESCALADO',
                'Incidencia sin atender hace más de 24h, prioridad subida de '.$anterior.' a '.self::SIGUIENTE_PRIORIDAD[$anterior].': '.$incidencia->nombre_incidencia,
                $incidencia->id_incidencia,
                correo: true,
            ));
        }

        $this->info($incidencias->count().' incidencia(s) escalada(s).');

        return self::SUCCESS;
    }
}
