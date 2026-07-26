<?php

namespace App\Enums;

// Estado de una solicitud de reactivación de cuenta. El valor coincide con el CHECK de la BD.
enum EstadoSolicitudReactivacion: string
{
    case Pendiente = 'PENDIENTE';
    case Aprobada = 'APROBADA';
    case Rechazada = 'RECHAZADA';
}
