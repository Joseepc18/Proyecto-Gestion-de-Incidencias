<?php

namespace Tests\Feature;

use App\Enums\EstadoSolicitudReactivacion;
use App\Models\Rol;
use App\Models\SolicitudReactivacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

// Suspender debe ser una sanción real: el correo de una cuenta suspendida queda ocupado por
// los tres caminos de alta, y la única vía de vuelta es la solicitud de reactivación.
class ReactivacionCuentaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const CORREO_SUSPENDIDO = 'suspendido@ejemplo.com';

    // Crea un ciudadano y lo suspende (borrado lógico), como hace la gestión de usuarios.
    private function crearSuspendido(string $email = self::CORREO_SUSPENDIDO): User
    {
        $usuario = User::factory()->create([
            'email' => $email,
            'id_rol' => Rol::where('nombre_rol', Rol::NORMAL)->value('id_rol'),
        ]);
        $usuario->delete();

        return $usuario;
    }

    private function callbackGoogle(string $state = 'estado-oauth-valido')
    {
        return $this->withUnencryptedCookie('oauth_state', $state)
            ->get('/api/auth/google/callback?state='.$state);
    }

    public function test_suspendido_no_puede_registrarse_de_nuevo(): void
    {
        $this->crearSuspendido();

        $this->postJson('/api/register', [
            'name' => 'Otra Vez',
            'email' => self::CORREO_SUSPENDIDO,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_super_admin_no_puede_crear_usuario_con_el_correo_de_un_suspendido(): void
    {
        $this->crearSuspendido();
        Sanctum::actingAs($this->crearUsuario('super_admin'));

        $this->postJson('/api/usuarios', [
            'name' => 'Técnico Colado',
            'email' => self::CORREO_SUSPENDIDO,
            'password' => 'Clave1234',
            'password_confirmation' => 'Clave1234',
            'id_rol' => Rol::where('nombre_rol', 'tecnico')->value('id_rol'),
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_google_no_revive_ni_duplica_una_cuenta_suspendida(): void
    {
        config(['services.frontend_url' => 'https://example.test']);
        $suspendido = $this->crearSuspendido();

        $googleUser = (new SocialiteUser)->setRaw(['email_verified' => true])->map([
            'email' => self::CORREO_SUSPENDIDO,
            'name' => 'Suspendido Volviendo',
        ]);
        Socialite::shouldReceive('driver->stateless->user')->andReturn($googleUser);

        $this->callbackGoogle()
            ->assertRedirect('https://example.test/login/login.html?error=google_suspendido');

        // Ni cuenta nueva ni resurrección de la vieja.
        $this->assertSame(1, User::withTrashed()->where('email', self::CORREO_SUSPENDIDO)->count());
        $this->assertSoftDeleted($suspendido);
    }

    public function test_usuario_activo_no_puede_apropiarse_del_correo_de_un_suspendido(): void
    {
        $this->crearSuspendido();
        $activo = User::factory()->create([
            'password' => 'Password123',
            'id_rol' => Rol::where('nombre_rol', Rol::NORMAL)->value('id_rol'),
        ]);
        Sanctum::actingAs($activo);

        $this->putJson('/api/perfil', [
            'name' => $activo->name,
            'email' => self::CORREO_SUSPENDIDO,
            'current_password' => 'Password123',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_solicitud_de_un_suspendido_se_guarda(): void
    {
        $suspendido = $this->crearSuspendido();

        $this->postJson('/api/reactivacion/solicitar', [
            'email' => self::CORREO_SUSPENDIDO,
            'motivo' => 'Creo que la suspensión fue un error',
        ])->assertOk();

        $solicitud = SolicitudReactivacion::where('id_usuario', $suspendido->id)->first();
        $this->assertNotNull($solicitud);
        $this->assertSame(EstadoSolicitudReactivacion::Pendiente, $solicitud->estado_solicitud);
        $this->assertSame('Creo que la suspensión fue un error', $solicitud->motivo_solicitud);
    }

    public function test_la_respuesta_publica_es_identica_exista_o_no_la_cuenta(): void
    {
        $this->crearSuspendido();
        $activo = User::factory()->create([
            'email' => 'activo@ejemplo.com',
            'id_rol' => Rol::where('nombre_rol', Rol::NORMAL)->value('id_rol'),
        ]);

        $datos = ['motivo' => 'Creo que la suspensión fue un error'];
        $suspendido = $this->postJson('/api/reactivacion/solicitar', $datos + ['email' => self::CORREO_SUSPENDIDO]);
        $inexistente = $this->postJson('/api/reactivacion/solicitar', $datos + ['email' => 'nadie@ejemplo.com']);
        $existeActivo = $this->postJson('/api/reactivacion/solicitar', $datos + ['email' => $activo->email]);

        // Mismo status y mismo cuerpo: la respuesta no permite deducir qué correos hay ni cuáles están suspendidos.
        foreach ([$inexistente, $existeActivo] as $otra) {
            $this->assertSame($suspendido->getStatusCode(), $otra->getStatusCode());
            $this->assertSame($suspendido->getContent(), $otra->getContent());
        }

        // Y solo la suspendida generó registro.
        $this->assertSame(1, SolicitudReactivacion::count());
    }

    public function test_reenviar_la_solicitud_no_duplica_la_pendiente(): void
    {
        $suspendido = $this->crearSuspendido();
        $datos = ['email' => self::CORREO_SUSPENDIDO, 'motivo' => 'Creo que la suspensión fue un error'];

        $this->postJson('/api/reactivacion/solicitar', $datos)->assertOk();
        $this->postJson('/api/reactivacion/solicitar', $datos + ['motivo' => 'Otro motivo distinto'])->assertOk();

        $this->assertSame(1, SolicitudReactivacion::where('id_usuario', $suspendido->id)->count());
    }

    public function test_restaurar_al_usuario_aprueba_su_solicitud(): void
    {
        $suspendido = $this->crearSuspendido();
        $this->postJson('/api/reactivacion/solicitar', [
            'email' => self::CORREO_SUSPENDIDO,
            'motivo' => 'Creo que la suspensión fue un error',
        ])->assertOk();

        $superAdmin = $this->crearUsuario('super_admin');
        Sanctum::actingAs($superAdmin);

        $this->postJson("/api/usuarios/{$suspendido->id}/restaurar")->assertOk();

        $solicitud = SolicitudReactivacion::where('id_usuario', $suspendido->id)->firstOrFail();
        $this->assertSame(EstadoSolicitudReactivacion::Aprobada, $solicitud->estado_solicitud);
        $this->assertSame($superAdmin->id, $solicitud->id_admin_resuelve);
        $this->assertNotNull($solicitud->fecha_resolucion);
    }

    public function test_rechazar_la_solicitud_deja_la_cuenta_suspendida_y_permite_pedirlo_de_nuevo(): void
    {
        $suspendido = $this->crearSuspendido();
        $datos = ['email' => self::CORREO_SUSPENDIDO, 'motivo' => 'Creo que la suspensión fue un error'];
        $this->postJson('/api/reactivacion/solicitar', $datos)->assertOk();

        Sanctum::actingAs($this->crearUsuario('super_admin'));
        $this->postJson("/api/usuarios/{$suspendido->id}/rechazar-reactivacion")->assertOk();

        $this->assertSoftDeleted($suspendido);
        $this->assertSame(
            EstadoSolicitudReactivacion::Rechazada,
            SolicitudReactivacion::where('id_usuario', $suspendido->id)->value('estado_solicitud')
        );

        // El índice parcial solo bloquea las PENDIENTES: tras el rechazo puede volver a pedirlo.
        $this->postJson('/api/reactivacion/solicitar', $datos)->assertOk();
        $this->assertSame(2, SolicitudReactivacion::where('id_usuario', $suspendido->id)->count());
    }

    public function test_rechazar_sin_solicitud_pendiente_devuelve_422(): void
    {
        $suspendido = $this->crearSuspendido();
        Sanctum::actingAs($this->crearUsuario('super_admin'));

        $this->postJson("/api/usuarios/{$suspendido->id}/rechazar-reactivacion")->assertStatus(422);
    }

    public function test_el_listado_de_suspendidos_muestra_la_solicitud_pendiente(): void
    {
        $suspendido = $this->crearSuspendido();
        $this->postJson('/api/reactivacion/solicitar', [
            'email' => self::CORREO_SUSPENDIDO,
            'motivo' => 'Creo que la suspensión fue un error',
        ])->assertOk();

        Sanctum::actingAs($this->crearUsuario('super_admin'));

        $this->getJson('/api/usuarios?rol=suspendido')
            ->assertOk()
            ->assertJsonPath('data.0.id', $suspendido->id)
            ->assertJsonPath('data.0.solicitud_reactivacion.motivo', 'Creo que la suspensión fue un error');
    }

    public function test_la_solicitud_exige_correo_y_motivo(): void
    {
        $this->postJson('/api/reactivacion/solicitar', ['email' => 'no-es-correo', 'motivo' => 'x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'motivo']);
    }
}
