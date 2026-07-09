<?php

namespace Database\Factories;

use App\Enums\EstadoIncidencia;
use App\Models\HistorialEstado;
use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HistorialEstado>
 */
class HistorialEstadoFactory extends Factory
{
    protected $model = HistorialEstado::class;

    public function definition(): array
    {
        return [
            'id_incidencia' => Incidencia::factory(),
            'id_usuario' => User::factory()->conRol('admin')->conDosFactor(),
            'estado_anterior' => EstadoIncidencia::Pendiente->value,
            'estado_nuevo' => EstadoIncidencia::EnProceso->value,
        ];
    }
}
