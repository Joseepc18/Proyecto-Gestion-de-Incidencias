<?php

namespace Database\Factories;

use App\Models\Evidencia;
use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evidencia>
 */
class EvidenciaFactory extends Factory
{
    protected $model = Evidencia::class;

    public function definition(): array
    {
        return [
            'id_incidencia' => Incidencia::factory(),
            'id_usuario' => User::factory()->conRol('normal'),
            'url_evidencia' => 'incidencias/'.fake()->uuid().'.jpg',
            'tipo_evidencia' => 'REPORTE',
        ];
    }

    public function resolucion(): static
    {
        return $this->state(['tipo_evidencia' => 'RESOLUCION']);
    }
}
