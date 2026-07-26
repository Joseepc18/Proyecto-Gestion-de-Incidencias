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

    public function test_super_admin_no_puede_editarse_a_si_mismo(): void
    {
        $superAdmin = $this->crearUsuario('super_admin');
        Sanctum::actingAs($superAdmin);

        $this->putJson("/api/usuarios/{$superAdmin->id}", $this->datosUsuario([
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
        ]))->assertForbidden();

        // Sigue siendo super_admin: el formulario no pudo degradarlo.
        $this->assertTrue($superAdmin->fresh()->esSuperAdmin());
    }

    public function test_super_admin_no_puede_editar_a_otro_super_admin(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $otro = $this->crearUsuario('super_admin');

        $this->putJson("/api/usuarios/{$otro->id}", $this->datosUsuario([
            'name' => $otro->name,
            'email' => $otro->email,
        ]))->assertForbidden();

        $this->assertTrue($otro->fresh()->esSuperAdmin());
    }

    public function test_super_admin_edita_a_un_admin_y_a_un_tecnico(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $admin = $this->crearUsuario('admin');
        $tecnico = $this->crearUsuario('tecnico');
        $idRolTecnico = Rol::where('nombre_rol', 'tecnico')->value('id_rol');

        $this->putJson("/api/usuarios/{$admin->id}", $this->datosUsuario([
            'name' => 'Supervisor Editado',
            'email' => $admin->email,
            'id_rol' => $idRolTecnico,
        ]))->assertOk();

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'name' => 'Supervisor Editado', 'id_rol' => $idRolTecnico]);

        $this->putJson("/api/usuarios/{$tecnico->id}", $this->datosUsuario([
            'name' => 'Tecnico Editado',
            'email' => $tecnico->email,
        ]))->assertOk();

        $this->assertDatabaseHas('users', ['id' => $tecnico->id, 'name' => 'Tecnico Editado']);
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

    public function test_super_admin_restablece_el_2fa_de_otro_usuario(): void
    {
        $superAdmin = $this->crearUsuario('super_admin');
        Sanctum::actingAs($superAdmin);
        // El admin nace con 2FA confirmado (conDosFactor por defecto).
        $admin = $this->crearUsuario('admin');

        $this->postJson("/api/usuarios/{$admin->id}/reset-2fa")->assertOk();

        $admin->refresh();
        $this->assertNull($admin->two_factor_secret);
        $this->assertNull($admin->two_factor_recovery_codes);
        $this->assertNull($admin->two_factor_confirmed_at);

        // Queda registrado en la bitácora que ven los super_admin.
        $this->assertDatabaseHas('bitacora_errores', [
            'tipo_error' => 'AUTENTICACION',
            'descripcion_error' => "UserController@resetearDosFactor: El super_admin {$superAdmin->id} restableció el 2FA del usuario {$admin->id}.",
        ]);
    }

    public function test_super_admin_no_puede_restablecer_su_propio_2fa(): void
    {
        $superAdmin = $this->crearUsuario('super_admin');
        Sanctum::actingAs($superAdmin);

        $this->postJson("/api/usuarios/{$superAdmin->id}/reset-2fa")->assertForbidden();
    }

    public function test_un_admin_sin_permiso_no_puede_restablecer_2fa(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));
        $otro = $this->crearUsuario('admin');

        $this->postJson("/api/usuarios/{$otro->id}/reset-2fa")->assertForbidden();
    }

    public function test_listado_filtra_por_busqueda_de_nombre_o_correo(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $rolTecnico = Rol::where('nombre_rol', 'tecnico')->value('id_rol');
        $ana = User::factory()->create(['id_rol' => $rolTecnico, 'name' => 'Ana Torres', 'email' => 'ana.torres@sistema.com']);
        $luis = User::factory()->create(['id_rol' => $rolTecnico, 'name' => 'Luis Perez', 'email' => 'luis.perez@sistema.com']);

        $porNombre = $this->getJson('/api/usuarios?busqueda=torres&per_page=50')->assertOk();
        $ids = collect($porNombre->json('data'))->pluck('id')->all();
        $this->assertContains($ana->id, $ids);
        $this->assertNotContains($luis->id, $ids);

        // También busca por correo.
        $porCorreo = $this->getJson('/api/usuarios?busqueda=luis.perez&per_page=50')->assertOk();
        $this->assertContains($luis->id, collect($porCorreo->json('data'))->pluck('id')->all());
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
