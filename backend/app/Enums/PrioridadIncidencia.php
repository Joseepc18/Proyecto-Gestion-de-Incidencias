<?php

namespace App\Enums;

// Prioridad de una incidencia. El valor coincide con el CHECK de la BD.
enum PrioridadIncidencia: string
{
    case Alta = 'ALTA';
    case Media = 'MEDIA';
    case Baja = 'BAJA';
}
