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
        // 'admin' (no 'tecnico'/'normal'): esos dos tienen vetados los permisos privilegiados (ver test del guard).
        $rolAdmin = Rol::where('nombre_rol', 'admin')->value('id_rol');
        // catalogos.administrar no está en los permisos base de admin por seeder: hay diferencia real que verificar.
        $idCatalogos = Permiso::where('clave_permiso', 'catalogos.administrar')->value('id_permiso');

        $this->putJson("/api/roles/{$rolAdmin}/permisos", ['permisos' => [$idCatalogos]])
            ->assertOk();

        $this->assertDatabaseHas('rol_permiso', ['id_rol' => $rolAdmin, 'id_permiso' => $idCatalogos]);
    }

    // Guard de B2: ni un super_admin puede darle un permiso privilegiado a normal/tecnico desde la UI de permisos.
    public function test_no_se_puede_dar_permiso_privilegiado_a_rol_de_bajo_privilegio(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $rolTecnico = Rol::where('nombre_rol', 'tecnico')->value('id_rol');
        $idGestionar = Permiso::where('clave_permiso', 'incidencias.gestionar')->value('id_permiso');

        $this->putJson("/api/roles/{$rolTecnico}/permisos", ['permisos' => [$idGestionar]])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Ese permiso no se puede asignar a normal/tecnico']);

        $this->assertDatabaseMissing('rol_permiso', ['id_rol' => $rolTecnico, 'id_permiso' => $idGestionar]);
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

    // Los permisos de gobierno están fijados al rol super_admin: ni siquiera con otro rol de respaldo se le quitan.
    public function test_no_se_puede_quitar_un_permiso_fijado_al_super_admin(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $rolSuper = Rol::where('nombre_rol', 'super_admin')->value('id_rol');
        $rolAdmin = Rol::where('nombre_rol', 'admin')->value('id_rol');
        $idAdministrar = Permiso::where('clave_permiso', 'permisos.administrar')->value('id_permiso');
        $idUsuarios = Permiso::where('clave_permiso', 'usuarios.administrar')->value('id_permiso');

        // Con respaldo o sin él da igual: primero le damos permisos.administrar también a admin,
        // así el que rechaza es el guard de permisos fijos y no el anti-lockout.
        $permisosAdminActuales = DB::table('rol_permiso')->where('id_rol', $rolAdmin)->pluck('id_permiso')->all();
        $this->putJson("/api/roles/{$rolAdmin}/permisos", ['permisos' => [...$permisosAdminActuales, $idAdministrar]])
            ->assertOk();

        // Intentar dejar al super_admin solo con usuarios.administrar (le falta permisos.administrar).
        $this->putJson("/api/roles/{$rolSuper}/permisos", ['permisos' => [$idUsuarios]])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'El Administrador del Sistema no puede quedarse sin los permisos de gobierno (usuarios y permisos)']);

        // No se tocó nada: conserva los dos permisos fijos.
        $this->assertDatabaseHas('rol_permiso', ['id_rol' => $rolSuper, 'id_permiso' => $idAdministrar]);
        $this->assertDatabaseHas('rol_permiso', ['id_rol' => $rolSuper, 'id_permiso' => $idUsuarios]);
    }

    // Lo fijado es solo el gobierno: el resto de la fila del super_admin sigue siendo editable.
    public function test_al_super_admin_si_se_le_puede_quitar_un_permiso_no_fijado(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $rolSuper = Rol::where('nombre_rol', 'super_admin')->value('id_rol');
        $idCatalogos = Permiso::where('clave_permiso', 'catalogos.administrar')->value('id_permiso');

        // Se reenvía su set actual sin catalogos.administrar; los dos fijos siguen dentro.
        $sinCatalogos = DB::table('rol_permiso')
            ->where('id_rol', $rolSuper)
            ->where('id_permiso', '!=', $idCatalogos)
            ->pluck('id_permiso')
            ->all();

        $this->putJson("/api/roles/{$rolSuper}/permisos", ['permisos' => $sinCatalogos])->assertOk();

        $this->assertDatabaseMissing('rol_permiso', ['id_rol' => $rolSuper, 'id_permiso' => $idCatalogos]);
    }

    // End-to-end: dar 'usuarios.administrar' a un rol le abre la gestión de usuarios
    // (manda el permiso, no el nombre de rol). 'admin' porque tecnico/normal tienen ese permiso vetado (guard de B2).
    public function test_conceder_permiso_habilita_el_acceso_a_la_ruta(): void
    {
        $rolAdmin = Rol::where('nombre_rol', 'admin')->value('id_rol');
        $admin = $this->crearUsuario('admin');

        // Antes: el admin (sin usuarios.administrar por seeder) no accede.
        Sanctum::actingAs($admin);
        $this->getJson('/api/usuarios')->assertStatus(403);

        // El super_admin le concede el permiso al rol admin.
        $idUsuarios = Permiso::where('clave_permiso', 'usuarios.administrar')->value('id_permiso');
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $this->putJson("/api/roles/{$rolAdmin}/permisos", ['permisos' => [$idUsuarios]])->assertOk();

        // Después: el mismo admin ya accede.
        Sanctum::actingAs($admin->fresh());
        $this->getJson('/api/usuarios')->assertOk();
    }
}
