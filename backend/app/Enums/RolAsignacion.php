<?php

namespace App\Enums;

// Rol de un técnico asignado a una incidencia. El valor coincide con el CHECK de la BD.
enum RolAsignacion: string
{
    case Responsable = 'RESPONSABLE';
    case Apoyo = 'APOYO';
}
