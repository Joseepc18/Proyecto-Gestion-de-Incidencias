<?php

namespace Database\Factories;

use App\Enums\EstadoIncidencia;
use App\Enums\PrioridadIncidencia;
use App\Models\Ciudad;
use App\Models\Incidencia;
use App\Models\SubtipoIncidencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incidencia>
 */
class IncidenciaFactory extends Factory
{
    protected $model = Incidencia::class;

    public function definition(): array
    {
        return [
            'nombre_incidencia' => ucfirst(fake()->words(4, true)),
            'descripcion_incidencia' => fake()->sentence(12),
            // Rango aproximado de Ecuador continental.
            'latitud_incidencia' => fake()->randomFloat(6, -4.0, 0.9),
            'longitud_incidencia' => fake()->randomFloat(6, -81.0, -75.2),
            // Solo prioridades reales; SIN_ASIGNAR es el estado inicial de triaje, no algo que se genere al azar.
            'prioridad_incidencia' => fake()->randomElement([PrioridadIncidencia::Alta, PrioridadIncidencia::Media, PrioridadIncidencia::Baja])->value,
            'estado_incidencia' => EstadoIncidencia::Pendiente->value,
            'id_ciudad' => Ciudad::inRandomOrder()->value('id_ciudad'),
            'id_subtipo_incidencia' => SubtipoIncidencia::inRandomOrder()->value('id_subtipo_incidencia'),
            'id_usuario' => User::factory()->conRol('normal'),
        ];
    }

    public function enProceso(): static
    {
        return $this->state(['estado_incidencia' => EstadoIncidencia::EnProceso->value]);
    }

    public function resuelta(): static
    {
        return $this->state([
            'estado_incidencia' => EstadoIncidencia::Resuelto->value,
            'fecha_resolucion' => now(),
        ]);
    }

    public function cerrada(): static
    {
        return $this->state([
            'estado_incidencia' => EstadoIncidencia::Cerrado->value,
            'fecha_resolucion' => now(),
        ]);
    }

    public function prioridad(PrioridadIncidencia $prioridad): static
    {
        return $this->state(['prioridad_incidencia' => $prioridad->value]);
    }
}
