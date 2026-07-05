<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPermisosTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_super_admin_ve_la_matriz_de_permisos(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));

        $this->getJson('/api/permisos')
            ->assertOk()
            ->assertJsonStructure([
                'roles' => [['id_rol', 'nombre_rol', 'editable', 'permisos']],
                'permisos' => [['id_permiso', 'clave_permiso', 'descripcion_permiso']],
            ])
            // El super_admin se muestra pero no es editable (anti-lockout).
            ->assertJsonFragment(['nombre_rol' => 'super_admin', 'editable' => false]);
    }

    public function test_super_admin_sincroniza_permisos_de_un_rol(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $rolTecnico = Rol::where('nombre_rol', 'tecnico')->value('id_rol');
        $idGestionar = Permiso::where('clave_permiso', 'incidencias.gestionar')->value('id_permiso');

        $this->putJson("/api/roles/{$rolTecnico}/permisos", ['permisos' => [$idGestionar]])
            ->assertOk();

        $this->assertDatabaseHas('rol_permiso', ['id_rol' => $rolTecnico, 'id_permiso' => $idGestionar]);
    }

    public function test_no_se_puede_modificar_el_rol_super_admin(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $rolSuper = Rol::where('nombre_rol', 'super_admin')->value('id_rol');

        $this->putJson("/api/roles/{$rolSuper}/permisos", ['permisos' => []])->assertStatus(422);

        // No se tocó nada: super_admin (4) + admin (1) = 5 filas del seeder.
        $this->assertDatabaseCount('rol_permiso', 5);
    }

    // End-to-end: dar 'usuarios.administrar' a un rol le abre la gestión de usuarios
    // (manda el permiso, no el nombre de rol).
    public function test_conceder_permiso_habilita_el_acceso_a_la_ruta(): void
    {
        $rolTecnico = Rol::where('nombre_rol', 'tecnico')->value('id_rol');
        $tecnico = $this->crearUsuario('tecnico');

        // Antes: el técnico no accede.
        Sanctum::actingAs($tecnico);
        $this->getJson('/api/usuarios')->assertStatus(403);

        // El super_admin le concede el permiso al rol técnico.
        $idUsuarios = Permiso::where('clave_permiso', 'usuarios.administrar')->value('id_permiso');
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $this->putJson("/api/roles/{$rolTecnico}/permisos", ['permisos' => [$idUsuarios]])->assertOk();

        // Después: el mismo técnico ya accede.
        Sanctum::actingAs($tecnico->fresh());
        $this->getJson('/api/usuarios')->assertOk();
    }
}
