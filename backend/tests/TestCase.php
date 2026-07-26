<?php

namespace Tests;

use App\Models\Ciudad;
use App\Models\Incidencia;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\SubtipoIncidencia;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

abstract class TestCase extends BaseTestCase
{
    // Corre con la app ya creada pero antes de que RefreshDatabase lance su migrate:fresh: última red
    // para no rehacer una base que no sea la de pruebas (ver la salvaguarda de tests/bootstrap.php).
    protected function setUpTraits()
    {
        $conexion = config('database.default');
        $base = (string) config("database.connections.{$conexion}.database");

        if (! str_ends_with($base, '_test')) {
            throw new \RuntimeException(
                "La suite apunta a la base '{$base}' y se esperaba una terminada en '_test'. ".
                'RefreshDatabase la borraría entera: revisá phpunit.xml y que la config no esté cacheada.'
            );
        }

        return parent::setUpTraits();
    }

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
    // Los roles privilegiados nacen con 2FA confirmado (lo exige el middleware '2fa'); pasar $conDosFactor=false para probar ese caso.
    protected function crearUsuario(string $nombreRol = 'normal', bool $conDosFactor = true): User
    {
        $rol = Rol::where('nombre_rol', $nombreRol)->firstOrFail();

        $factory = User::factory();
        if ($conDosFactor && in_array($nombreRol, ['admin', 'super_admin'], true)) {
            $factory = $factory->conDosFactor();
        }

        return $factory->create(['id_rol' => $rol->id_rol]);
    }

    // Activa un permiso en un rol, como haría el super_admin desde el panel (no duplica si ya lo tiene).
    protected function darPermisoAlRol(string $nombreRol, string $clavePermiso): void
    {
        $rol = Rol::where('nombre_rol', $nombreRol)->firstOrFail();
        $permiso = Permiso::where('clave_permiso', $clavePermiso)->firstOrFail();

        $rol->permisos()->syncWithoutDetaching([$permiso->id_permiso]);
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
