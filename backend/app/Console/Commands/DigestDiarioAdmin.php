<?php

namespace App\Console\Commands;

use App\Enums\EstadoIncidencia;
use App\Mail\ResumenDiarioMail;
use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class DigestDiarioAdmin extends Command
{
    protected $signature = 'incidencias:digest-diario';

    protected $description = 'Envía a admin/super_admin un resumen por email de las incidencias del día';

    public function handle(): int
    {
        $creadasHoy = Incidencia::whereDate('created_at', today())->count();
        $resueltasHoy = Incidencia::whereDate('fecha_resolucion', today())->count();

        $pendientesPorPrioridad = Incidencia::where('estado_incidencia', EstadoIncidencia::Pendiente->value)
            ->selectRaw('prioridad_incidencia, count(*) as total')
            ->groupBy('prioridad_incidencia')
            ->pluck('total', 'prioridad_incidencia');

        $totalPendientes = $pendientesPorPrioridad->sum();

        $admins = User::conPermiso('incidencias.gestionar')->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->queue(
                new ResumenDiarioMail($admin->name, $creadasHoy, $resueltasHoy, $totalPendientes, $pendientesPorPrioridad)
            );
        }

        $this->info('Digest enviado a '.$admins->count().' admin(s).');

        return self::SUCCESS;
    }
}
