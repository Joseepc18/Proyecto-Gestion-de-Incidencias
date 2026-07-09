<?php

namespace App\Enums;

// Prioridad de una incidencia. El valor coincide con el CHECK de la BD.
enum PrioridadIncidencia: string
{
    case Alta = 'ALTA';
    case Media = 'MEDIA';
    case Baja = 'BAJA';
    // Estado inicial al reportar: la incidencia nace sin prioridad hasta que el admin la triaja.
    case SinAsignar = 'SIN_ASIGNAR';

    // Etiqueta legible en español (correos y vistas del backend).
    public function etiqueta(): string
    {
        return match ($this) {
            self::Alta => 'Alta',
            self::Media => 'Media',
            self::Baja => 'Baja',
            self::SinAsignar => 'Sin asignar',
        };
    }
}
