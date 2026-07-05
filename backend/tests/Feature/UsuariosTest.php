<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // Payload base para crear un usuario por la gestión de admin.
    private function datosUsuario(array $override = []): array
    {
        return array_merge([
            'name' => 'Nuevo Técnico',
            'email' => 'tecnico.nuevo@sistema.com',
            'password' => 'Clave1234',
            'password_confirmation' => 'Clave1234',
            'id_rol' => Rol::where('nombre_rol', 'tecnico')->value('id_rol'),
        ], $override);
    }

    public function test_super_admin_crea_tecnico_o_admin(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));

        $this->postJson('/api/usuarios', $this->datosUsuario())->assertCreated();

        $this->postJson('/api/usuarios', $this->datosUsuario([
            'email' => 'otro.admin@sistema.com',
            'id_rol' => Rol::where('nombre_rol', 'admin')->value('id_rol'),
        ]))->assertCreated();

        // El usuario creado por el super_admin nace verificado (no arrastra el aviso de verificar correo).
        $this->assertNotNull(User::where('email', 'tecnico.nuevo@sistema.com')->value('email_verified_at'));
    }

    public function test_super_admin_no_puede_crear_usuario_normal(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));

        $this->postJson('/api/usuarios', $this->datosUsuario([
            'id_rol' => Rol::where('nombre_rol', 'normal')->value('id_rol'),
        ]))->assertStatus(422)->assertJsonValidationErrors('id_rol');
    }

    public function test_suspender_usuario_es_borrado_logico(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $tecnico = $this->crearUsuario('tecnico');

        $this->deleteJson("/api/usuarios/{$tecnico->id}")->assertOk();

        $this->assertSoftDeleted('users', ['id' => $tecnico->id]);
    }

    public function test_restaurar_reactiva_un_usuario_suspendido(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $suspendido = $this->crearUsuario('tecnico');

        $this->deleteJson("/api/usuarios/{$suspendido->id}")->assertOk();
        $this->assertSoftDeleted('users', ['id' => $suspendido->id]);

        $this->postJson("/api/usuarios/{$suspendido->id}/restaurar")->assertOk();

        $this->assertDatabaseHas('users', ['id' => $suspendido->id, 'deleted_at' => null]);
    }

    public function test_listado_oculta_suspendidos_y_los_muestra_con_filtro(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $activo = $this->crearUsuario('tecnico');
        $suspendido = $this->crearUsuario('tecnico');

        $this->deleteJson("/api/usuarios/{$suspendido->id}")->assertOk();

        // Por defecto el suspendido no aparece entre los activos.
        $sinFiltro = $this->getJson('/api/usuarios?per_page=50')->assertOk();
        $idsSinFiltro = collect($sinFiltro->json('data'))->pluck('id')->all();
        $this->assertContains($activo->id, $idsSinFiltro);
        $this->assertNotContains($suspendido->id, $idsSinFiltro);

        // Con ?rol=suspendido aparecen solo los soft-deleted (no el activo).
        $conFiltro = $this->getJson('/api/usuarios?rol=suspendido&per_page=50')->assertOk();
        $idsSuspendidos = collect($conFiltro->json('data'))->pluck('id')->all();
        $this->assertContains($suspendido->id, $idsSuspendidos);
        $this->assertNotContains($activo->id, $idsSuspendidos);
    }
}
