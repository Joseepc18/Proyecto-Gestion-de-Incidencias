<?php

namespace App\Enums;

// Estados del flujo de una incidencia. El valor coincide con el CHECK de la BD.
enum EstadoIncidencia: string
{
    case Pendiente = 'PENDIENTE';
    case EnProceso = 'EN_PROCESO';
    case Resuelto = 'RESUELTO';
    case Cerrado = 'CERRADO';

    // Grafo legal del flujo, sin roles: desde cada estado, a qué estados se puede ir. CERRADO es terminal.
    public function transicionesValidas(): array
    {
        return match ($this) {
            self::Pendiente => [self::EnProceso, self::Resuelto],
            self::EnProceso => [self::Resuelto],
            self::Resuelto => [self::EnProceso, self::Cerrado],
            self::Cerrado => [],
        };
    }

    public function puedeTransicionarA(self $destino): bool
    {
        return in_array($destino, $this->transicionesValidas(), true);
    }

    // Etiqueta legible en español (correos y vistas del backend).
    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnProceso => 'En proceso',
            self::Resuelto => 'Resuelto',
            self::Cerrado => 'Archivado',
        };
    }
}
