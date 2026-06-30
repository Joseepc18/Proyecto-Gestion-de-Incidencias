<?php

namespace App\Enums;

// Tipo de foto: del reporte inicial o de la resolución. El valor coincide con el CHECK de la BD.
enum TipoEvidencia: string
{
    case Reporte = 'REPORTE';
    case Resolucion = 'RESOLUCION';
}
