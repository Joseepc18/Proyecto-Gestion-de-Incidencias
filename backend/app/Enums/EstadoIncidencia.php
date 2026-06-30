<?php

namespace App\Enums;

// Estados del flujo de una incidencia. El valor coincide con el CHECK de la BD.
enum EstadoIncidencia: string
{
    case Pendiente = 'PENDIENTE';
    case EnProceso = 'EN_PROCESO';
    case Resuelto = 'RESUELTO';
}
