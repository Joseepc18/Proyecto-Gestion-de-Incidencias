<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // Regresión: el panel Blade exige '2fa' igual que la API. Un super_admin sin 2FA
    // no debe poder gestionar usuarios/catálogos desde /panel.
    public function test_super_admin_sin_2fa_no_accede_a_usuarios_del_panel(): void
    {
        $admin = $this->crearUsuario('super_admin', conDosFactor: false);
        $this->actingAs($admin);

        $this->get('/panel/usuarios')->assertForbidden();
    }

    public function test_super_admin_con_2fa_si_accede_a_usuarios_del_panel(): void
    {
        $admin = $this->crearUsuario('super_admin');
        $this->actingAs($admin);

        $this->get('/panel/usuarios')->assertOk();
    }
}
