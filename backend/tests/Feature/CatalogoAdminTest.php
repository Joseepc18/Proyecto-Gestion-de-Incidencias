<?php

namespace Tests\Feature;

use App\Models\SubtipoIncidencia;
use App\Models\TipoIncidencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CatalogoAdminTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // Un subtipo recién creado no tiene incidencias colgando: se borra sin ruido.
    public function test_subtipo_sin_incidencias_se_elimina(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));

        $subtipo = SubtipoIncidencia::create([
            'nombre_subtipo_incidencia' => 'Semáforo intermitente',
            'id_tipo_incidencia' => TipoIncidencia::value('id_tipo_incidencia'),
        ]);

        $this->deleteJson("/api/subtipos-incidencia/{$subtipo->id_subtipo_incidencia}")->assertOk();

        $this->assertDatabaseMissing('subtipos_incidencia', [
            'id_subtipo_incidencia' => $subtipo->id_subtipo_incidencia,
        ]);
    }

    // El conteo de incidencias no llevaba withTrashed: las de la papelera no se veían, la FK RESTRICT
    // frenaba el DELETE y el usuario recibía un 500 en vez de este 422.
    public function test_subtipo_con_incidencias_en_la_papelera_devuelve_422(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $idSubtipo = $incidencia->id_subtipo_incidencia;
        $incidencia->delete();

        // Queda soft-deleted: sigue apuntando al subtipo aunque el listado normal ya no la vea.
        $this->assertSoftDeleted('incidencias', ['id_incidencia' => $incidencia->id_incidencia]);

        Sanctum::actingAs($this->crearUsuario('super_admin'));

        $this->deleteJson("/api/subtipos-incidencia/{$idSubtipo}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'No puedes eliminar un subtipo con incidencias registradas, incluidas las que estén en la papelera.');

        $this->assertDatabaseHas('subtipos_incidencia', ['id_subtipo_incidencia' => $idSubtipo]);
    }

    // El caso que ya funcionaba: con la incidencia activa (sin papelera de por medio) también son 422.
    public function test_subtipo_con_incidencias_activas_devuelve_422(): void
    {
        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));

        Sanctum::actingAs($this->crearUsuario('super_admin'));

        $this->deleteJson("/api/subtipos-incidencia/{$incidencia->id_subtipo_incidencia}")
            ->assertStatus(422);
    }

    // Un tipo arrastra sus subtipos por FK: se bloquea antes de llegar al DELETE.
    public function test_tipo_con_subtipos_devuelve_422(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));

        $idTipo = SubtipoIncidencia::value('id_tipo_incidencia');

        $this->deleteJson("/api/tipos-incidencia/{$idTipo}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'No puedes eliminar un tipo con subtipos. Elimina primero sus subtipos.');
    }

    public function test_tipo_sin_subtipos_se_elimina(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));

        $tipo = TipoIncidencia::create(['nombre_tipo_incidencia' => 'Ornato']);

        $this->deleteJson("/api/tipos-incidencia/{$tipo->id_tipo_incidencia}")->assertOk();

        $this->assertDatabaseMissing('tipos_incidencia', ['id_tipo_incidencia' => $tipo->id_tipo_incidencia]);
    }
}
