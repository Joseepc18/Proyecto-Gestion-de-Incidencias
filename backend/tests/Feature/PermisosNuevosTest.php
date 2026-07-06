<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PermisosNuevosTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // Papelera separada de incidencias.gestionar: admin entra, super_admin (view-only) no.
    public function test_papelera_requiere_incidencias_papelera(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));
        $this->getJson('/api/incidencias/papelera')->assertOk();

        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $this->getJson('/api/incidencias/papelera')->assertStatus(403);
    }

    // Dashboard separado de incidencias.gestionar: super_admin (view-only) también lo ve.
    public function test_dashboard_requiere_dashboard_ver(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $this->getJson('/api/dashboard/metricas')->assertOk();

        Sanctum::actingAs($this->crearUsuario('tecnico'));
        $this->getJson('/api/dashboard/metricas')->assertStatus(403);
    }

    // Bitácora: solo super_admin la tiene sembrada por defecto.
    public function test_bitacora_requiere_bitacora_ver(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $this->getJson('/api/bitacora')->assertOk();

        Sanctum::actingAs($this->crearUsuario('admin'));
        $this->getJson('/api/bitacora')->assertStatus(403);
    }
}
