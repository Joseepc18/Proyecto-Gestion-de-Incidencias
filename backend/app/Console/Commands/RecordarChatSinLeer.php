<?php

namespace App\Console\Commands;

use App\Models\Incidencia;
use App\Models\Notificacion;
use App\Models\User;
use App\Notifications\IncidenciaNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RecordarChatSinLeer extends Command
{
    protected $signature = 'incidencias:recordar-chat-sin-leer';

    protected $description = 'Avisa por correo (una sola vez) a quien tenga mensajes del chat sin leer hace más de 24h';

    // Ventana cerrada: la notificación es candidata solo entre estas dos edades y después sale para siempre.
    // Es lo que hace el aviso definitivo y lo que impide que el comando arrastre todo el histórico sin leer.
    private const HORAS_ESPERA = 24;

    private const HORAS_LIMITE = 48;

    // TTL de la marca "ya se le avisó de este hilo"; muy por encima de la ventana, así no repite dentro de ella.
    private const DIAS_SIN_REPETIR = 7;

    public function handle(): int
    {
        $pendientes = Notificacion::whereNull('read_at')
            ->whereBetween('created_at', [now()->subHours(self::HORAS_LIMITE), now()->subHours(self::HORAS_ESPERA)])
            ->whereJsonContains('data->tipo', 'COMENTARIO')
            ->get()
            ->groupBy(fn (Notificacion $n) => $n->notifiable_id.':'.$n->data['id_incidencia']);

        $avisados = 0;

        foreach ($pendientes as $clave => $grupo) {
            [$idUsuario, $idIncidencia] = explode(':', $clave);
            $cacheKey = "recordatorio_chat:{$idUsuario}:{$idIncidencia}";

            if (Cache::has($cacheKey)) {
                continue;
            }

            $usuario = User::find($idUsuario);
            $incidencia = Incidencia::find($idIncidencia);
            if (! $usuario || ! $incidencia) {
                continue;
            }

            $usuario->notify(new IncidenciaNotification(
                'RECORDATORIO_CHAT',
                'Tienes mensajes sin leer en la incidencia: '.$incidencia->nombre_incidencia,
                $incidencia->id_incidencia,
                correo: true,
            ));

            Cache::put($cacheKey, true, now()->addDays(self::DIAS_SIN_REPETIR));
            $avisados++;
        }

        $this->info($avisados.' recordatorio(s) de chat sin leer enviado(s).');

        return self::SUCCESS;
    }
}
