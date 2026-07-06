<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            // Todos los roles son editables; la red de seguridad es el anti-lockout de sincronizar().
            ->assertJsonFragment(['nombre_rol' => 'super_admin', 'editable' => true]);
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

    // Anti-lockout: super_admin es el único rol con permisos.administrar (por seeder), así que no puede quitárselo a sí mismo.
    public function test_no_se_puede_dejar_el_sistema_sin_ningun_rol_con_permisos_administrar(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $rolSuper = Rol::where('nombre_rol', 'super_admin')->value('id_rol');
        $filasAntes = DB::table('rol_permiso')->count();

        $this->putJson("/api/roles/{$rolSuper}/permisos", ['permisos' => []])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Debe quedar al menos un rol con el permiso permisos.administrar']);

        // No se tocó nada: el sync nunca corrió.
        $this->assertDatabaseCount('rol_permiso', $filasAntes);
    }

    // Si YA hay otro rol con permisos.administrar, sí se le puede quitar a super_admin (deja de ser el único).
    public function test_se_puede_quitar_permisos_administrar_a_super_admin_si_otro_rol_lo_tiene(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $rolSuper = Rol::where('nombre_rol', 'super_admin')->value('id_rol');
        $rolAdmin = Rol::where('nombre_rol', 'admin')->value('id_rol');
        $idAdministrar = Permiso::where('clave_permiso', 'permisos.administrar')->value('id_permiso');

        // Primero se lo damos también a admin.
        $permisosAdminActuales = DB::table('rol_permiso')->where('id_rol', $rolAdmin)->pluck('id_permiso')->all();
        $this->putJson("/api/roles/{$rolAdmin}/permisos", ['permisos' => [...$permisosAdminActuales, $idAdministrar]])
            ->assertOk();

        // Ahora sí se le puede quitar a super_admin: admin queda como respaldo.
        $this->putJson("/api/roles/{$rolSuper}/permisos", ['permisos' => []])->assertOk();

        $this->assertDatabaseMissing('rol_permiso', ['id_rol' => $rolSuper, 'id_permiso' => $idAdministrar]);
        $this->assertDatabaseHas('rol_permiso', ['id_rol' => $rolAdmin, 'id_permiso' => $idAdministrar]);
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
