<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Cubre la autorización de routes/channels.php. En testing BROADCAST_CONNECTION=null (NullBroadcaster::auth()
// es un no-op), así que estas pruebas apuntan el broadcaster a 'reverb' (implementación Pusher: la firma es
// cálculo local, no necesita el contenedor Reverb corriendo) para ejercer de verdad los closures de channels.php.
class CanalesReverbTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // routes/channels.php se registra sobre el broadcaster de la conexión por defecto AL ARRANCAR la app, así que
    // la conexión hay que fijarla por variable de entorno ANTES de ese boot: cambiar config() con la app ya
    // arrancada deja los canales registrados en el broadcaster viejo (NullBroadcaster::auth() = siempre 200 vacío,
    // nunca revienta). PHPUnit reaplica el <env> de phpunit.xml antes de cada test, así que el putenv() va en
    // setUp() (antes de parent::setUp(), que es quien arranca la app), no en setUpBeforeClass().
    // Valor previo de cada variable que sobreescribimos, para restaurarlo en tearDown.
    private array $envPrevio = [];

    protected function setUp(): void
    {
        // putenv() solo toca el entorno a nivel de C; el repositorio de Dotenv de Laravel lee $_ENV primero,
        // que phpunit.xml ya dejó en "null" — sin fijar también $_ENV/$_SERVER aquí, env() sigue viendo "null".
        foreach ([
            'BROADCAST_CONNECTION' => 'reverb',
            'REVERB_APP_KEY' => 'test-key',
            'REVERB_APP_SECRET' => 'test-secret',
            'REVERB_APP_ID' => 'test-app',
        ] as $variable => $valor) {
            $this->envPrevio[$variable] = $_ENV[$variable] ?? null;
            putenv("{$variable}={$valor}");
            $_ENV[$variable] = $valor;
            $_SERVER[$variable] = $valor;
        }

        parent::setUp();
    }

    // Restaura el entorno: sin esto BROADCAST_CONNECTION=reverb quedaría pegado y contaminaría los tests que corren después (harían POST real a Reverb, que en CI no existe).
    protected function tearDown(): void
    {
        foreach ($this->envPrevio as $variable => $valor) {
            if ($valor === null) {
                putenv($variable);
                unset($_ENV[$variable], $_SERVER[$variable]);
            } else {
                putenv("{$variable}={$valor}");
                $_ENV[$variable] = $valor;
                $_SERVER[$variable] = $valor;
            }
        }

        parent::tearDown();
    }

    private function autenticarCanal(string $canal): TestResponse
    {
        return $this->postJson('/api/broadcasting/auth', [
            'channel_name' => $canal,
            'socket_id' => '123.456',
        ]);
    }

    public function test_reportador_y_admin_autentican_el_chat_pero_un_ajeno_no(): void
    {
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);
        $canal = 'private-incidencia.'.$incidencia->id_incidencia;

        Sanctum::actingAs($autor);
        $this->autenticarCanal($canal)->assertOk();

        Sanctum::actingAs($this->crearUsuario('admin'));
        $this->autenticarCanal($canal)->assertOk();

        Sanctum::actingAs($this->crearUsuario('normal'));
        $this->autenticarCanal($canal)->assertStatus(403);
    }

    public function test_solo_el_propio_usuario_autentica_su_canal_de_notificaciones(): void
    {
        $usuario = $this->crearUsuario('normal');
        $canal = 'private-App.Models.User.'.$usuario->id;

        Sanctum::actingAs($usuario);
        $this->autenticarCanal($canal)->assertOk();

        Sanctum::actingAs($this->crearUsuario('normal'));
        $this->autenticarCanal($canal)->assertStatus(403);
    }

    public function test_tablero_de_presencia_exige_incidencias_gestionar(): void
    {
        Sanctum::actingAs($this->crearUsuario('admin'));
        $this->autenticarCanal('presence-tablero')->assertOk();

        // super_admin es view-only en incidencias: no tiene incidencias.gestionar.
        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $this->autenticarCanal('presence-tablero')->assertStatus(403);

        Sanctum::actingAs($this->crearUsuario('tecnico'));
        $this->autenticarCanal('presence-tablero')->assertStatus(403);
    }

    public function test_canal_de_updates_sigue_la_policy_ver(): void
    {
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);
        $canal = 'private-incidencia.updates.'.$incidencia->id_incidencia;

        Sanctum::actingAs($autor);
        $this->autenticarCanal($canal)->assertOk();

        Sanctum::actingAs($this->crearUsuario('normal'));
        $this->autenticarCanal($canal)->assertStatus(403);
    }
}
