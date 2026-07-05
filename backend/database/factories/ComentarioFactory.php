<?php

namespace Database\Factories;

use App\Models\Comentario;
use App\Models\Incidencia;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comentario>
 */
class ComentarioFactory extends Factory
{
    protected $model = Comentario::class;

    public function definition(): array
    {
        return [
            'id_incidencia' => Incidencia::factory(),
            'id_usuario' => User::factory()->conRol('normal'),
            'comentario' => fake()->sentence(10),
        ];
    }
}
