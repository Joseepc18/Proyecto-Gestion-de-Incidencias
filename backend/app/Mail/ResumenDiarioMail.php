<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

// Correo del digest diario: se arma en DigestDiarioAdmin y se manda una vez por cada admin/super_admin.
class ResumenDiarioMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public int $creadasHoy,
        public int $resueltasHoy,
        public int $totalPendientes,
        public Collection $pendientesPorPrioridad,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Resumen diario de incidencias — '.now()->format('d/m/Y'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.resumen-diario');
    }
}
