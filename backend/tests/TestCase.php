<?php

namespace Tests;

use App\Models\Ciudad;
use App\Models\Incidencia;
use App\Models\Rol;
use App\Models\SubtipoIncidencia;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

abstract class TestCase extends BaseTestCase
{
    // Notificaciones nativas de un usuario cuyo data->tipo coincide (tipo viaja dentro del payload json).
    protected function notificacionesDe(User $usuario, string $tipo): Collection
    {
        return DatabaseNotification::where('notifiable_type', User::class)
            ->where('notifiable_id', $usuario->id)
            ->where('data->tipo', $tipo)
            ->get();
    }

    protected function assertNotificado(User $usuario, string $tipo): void
    {
        $this->assertTrue(
            $this->notificacionesDe($usuario, $tipo)->isNotEmpty(),
            "Se esperaba una notificación {$tipo} para el usuario {$usuario->id}."
        );
    }

    protected function assertNoNotificado(User $usuario, string $tipo): void
    {
        $this->assertTrue(
            $this->notificacionesDe($usuario, $tipo)->isEmpty(),
            "No se esperaba una notificación {$tipo} para el usuario {$usuario->id}."
        );
    }

    // Crea un usuario con el rol indicado (id_rol es NOT NULL, por eso se pasa).
    protected function crearUsuario(string $nombreRol = 'normal'): User
    {
        $rol = Rol::where('nombre_rol', $nombreRol)->firstOrFail();

        return User::factory()->create(['id_rol' => $rol->id_rol]);
    }

    // Payload válido para crear una incidencia (coordenadas dentro del rango de Ecuador).
    protected function datosIncidenciaValidos(array $override = []): array
    {
        return array_merge([
            'nombre_incidencia' => 'Bache peligroso en la avenida principal',
            'descripcion_incidencia' => 'Hay un hueco grande que daña los autos.',
            'latitud_incidencia' => -2.17,
            'longitud_incidencia' => -79.92,
            'prioridad_incidencia' => 'MEDIA',
            'id_ciudad' => Ciudad::value('id_ciudad'),
            'id_subtipo_incidencia' => SubtipoIncidencia::value('id_subtipo_incidencia'),
        ], $override);
    }

    // Crea una incidencia a nombre del usuario dado (estado PENDIENTE por defecto).
    protected function crearIncidencia(User $usuario, array $override = []): Incidencia
    {
        return Incidencia::create(
            $this->datosIncidenciaValidos(array_merge(['id_usuario' => $usuario->id], $override))
        );
    }
}
