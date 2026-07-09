<?php

namespace Database\Factories;

use App\Models\AsignacionIncidencia;
use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AsignacionIncidencia>
 */
class AsignacionIncidenciaFactory extends Factory
{
    protected $model = AsignacionIncidencia::class;

    public function definition(): array
    {
        return [
            'id_incidencia' => Incidencia::factory(),
            'id_usuario' => User::factory()->conRol('tecnico'),
            'rol_asignado' => 'RESPONSABLE',
        ];
    }

    public function apoyo(): static
    {
        return $this->state(['rol_asignado' => 'APOYO']);
    }
}
