<?php

namespace Tests\Feature;

use App\Models\Rol;
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

    public function test_admin_crea_tecnico_o_admin(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->postJson('/api/usuarios', $this->datosUsuario())
            ->assertCreated();

        $this->postJson('/api/usuarios', $this->datosUsuario([
            'email' => 'otro.admin@sistema.com',
            'id_rol' => Rol::where('nombre_rol', 'admin')->value('id_rol'),
        ]))->assertCreated();
    }

    public function test_admin_no_puede_crear_usuario_normal(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));

        $this->postJson('/api/usuarios', $this->datosUsuario([
            'id_rol' => Rol::where('nombre_rol', 'normal')->value('id_rol'),
        ]))->assertStatus(422)->assertJsonValidationErrors('id_rol');
    }

    public function test_admin_no_puede_editar_a_un_usuario_normal(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));
        $normal = $this->crearUsuario('normal');

        $this->putJson("/api/usuarios/{$normal->id}", [
            'name' => 'Intento de cambio',
            'email' => $normal->email,
            'id_rol' => Rol::where('nombre_rol', 'tecnico')->value('id_rol'),
        ])->assertStatus(403);
    }

    public function test_admin_edita_a_un_tecnico(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));
        $tecnico = $this->crearUsuario('tecnico');

        $this->putJson("/api/usuarios/{$tecnico->id}", [
            'name' => 'Técnico Renombrado',
            'email' => $tecnico->email,
            'id_rol' => Rol::where('nombre_rol', 'tecnico')->value('id_rol'),
        ])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $tecnico->id, 'name' => 'Técnico Renombrado']);
    }

    public function test_admin_no_puede_degradar_a_un_usuario_a_normal(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));
        $tecnico = $this->crearUsuario('tecnico');

        $this->putJson("/api/usuarios/{$tecnico->id}", [
            'name' => $tecnico->name,
            'email' => $tecnico->email,
            'id_rol' => Rol::where('nombre_rol', 'normal')->value('id_rol'),
        ])->assertStatus(422)->assertJsonValidationErrors('id_rol');
    }

    public function test_suspender_usuario_es_borrado_logico(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));
        $tecnico = $this->crearUsuario('tecnico');

        $this->deleteJson("/api/usuarios/{$tecnico->id}")->assertOk();

        $this->assertSoftDeleted('users', ['id' => $tecnico->id]);
    }

    public function test_admin_no_puede_suspenderse_a_si_mismo(): void
    {
        $admin = $this->crearUsuario('admin');
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/usuarios/{$admin->id}")->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_listado_filtra_por_rol(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));
        $tecnico = $this->crearUsuario('tecnico');

        $respuesta = $this->getJson('/api/usuarios?rol=tecnico&per_page=50')->assertOk();

        foreach ($respuesta->json('data') as $u) {
            $this->assertSame('tecnico', $u['rol']['nombre_rol']);
        }
        $this->assertContains($tecnico->id, collect($respuesta->json('data'))->pluck('id')->all());
    }

    public function test_listado_oculta_suspendidos_y_los_muestra_con_filtro(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));
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

    public function test_restaurar_reactiva_un_usuario_suspendido(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));
        $suspendido = $this->crearUsuario('tecnico');

        $this->deleteJson("/api/usuarios/{$suspendido->id}")->assertOk();
        $this->assertSoftDeleted('users', ['id' => $suspendido->id]);

        $this->postJson("/api/usuarios/{$suspendido->id}/restaurar")->assertOk();

        $this->assertDatabaseHas('users', ['id' => $suspendido->id, 'deleted_at' => null]);
    }

    public function test_restaurar_rechaza_un_usuario_no_suspendido(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));
        $activo = $this->crearUsuario('tecnico');

        $this->postJson("/api/usuarios/{$activo->id}/restaurar")->assertStatus(422);
    }
}
