<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RestablecerPasswordNotification;
use App\Notifications\VerificarEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // Siembra roles y catálogos (una vez) para que existan al correr los tests.
    protected $seed = true;

    // Simula la vuelta de Google con un state válido: la cookie y el query param coinciden (contrato anti-CSRF).
    private function callbackGoogle(string $state = 'estado-oauth-valido')
    {
        return $this->withUnencryptedCookie('oauth_state', $state)
            ->get('/api/auth/google/callback?state='.$state);
    }

    public function test_registro_crea_usuario_normal_y_devuelve_token(): void
    {
        $respuesta = $this->postJson('/api/register', [
            'name' => 'Juan Perez',
            'email' => 'juan@ejemplo.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $respuesta->assertCreated()
            ->assertJsonStructure(['access_token', 'token_type', 'user' => ['id', 'email', 'rol' => ['nombre_rol']]])
            // El registro debe traer el rol embebido para que el front no re-pida /user.
            ->assertJsonPath('user.rol.nombre_rol', 'normal');

        $this->assertDatabaseHas('users', ['email' => 'juan@ejemplo.com']);

        // El registro público siempre asigna el rol 'normal'.
        $usuario = User::where('email', 'juan@ejemplo.com')->first();
        $this->assertSame('normal', $usuario->rol->nombre_rol);
    }

    public function test_healthcheck_publico_responde_ok(): void
    {
        // Público, sin token: 200 con status ok y la BD respondiendo.
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJson(['status' => 'ok', 'db' => true]);
    }

    public function test_login_con_credenciales_validas_devuelve_token(): void
    {
        $this->crearUsuario('normal')->forceFill([
            'email' => 'ana@ejemplo.com',
            'password' => 'Password123',
        ])->save();

        $this->postJson('/api/login', [
            'email' => 'ana@ejemplo.com',
            'password' => 'Password123',
        ])->assertOk()
            ->assertJsonStructure(['access_token', 'user' => ['rol' => ['nombre_rol']]])
            // El login debe traer el rol embebido para que el front no re-pida /user.
            ->assertJsonPath('user.rol.nombre_rol', 'normal');
    }

    public function test_login_con_credenciales_invalidas_devuelve_401(): void
    {
        $this->crearUsuario('normal')->forceFill([
            'email' => 'ana@ejemplo.com',
            'password' => 'Password123',
        ])->save();

        $this->postJson('/api/login', [
            'email' => 'ana@ejemplo.com',
            'password' => 'claveIncorrecta',
        ])->assertStatus(401);
    }

    public function test_ruta_protegida_sin_header_json_devuelve_401(): void
    {
        // Sin token y sin "Accept: application/json", la API debe dar 401 limpio
        // (no un 500 por intentar redirigir a la ruta web 'login' inexistente).
        $this->get('/api/user')->assertStatus(401);
    }

    public function test_registro_rechaza_password_debil(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Usuario Debil',
            'email' => 'debil@ejemplo.com',
            'password' => '123',
            'password_confirmation' => '123',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_logout_invalida_el_token(): void
    {
        $this->crearUsuario('normal')->forceFill([
            'email' => 'salir@ejemplo.com',
            'password' => 'Password123',
        ])->save();

        $token = $this->postJson('/api/login', [
            'email' => 'salir@ejemplo.com',
            'password' => 'Password123',
        ])->json('access_token');

        // Tras el login el usuario tiene exactamente un token.
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        // Logout borra el token: ya no queda ninguno en la base de datos.
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_no_invalida_las_sesiones_anteriores(): void
    {
        // Regresión H-A: dos logins seguidos NO deben borrar el token previo,
        // para permitir sesiones concurrentes (laptop + celular) sin "cerrarse sola".
        $this->crearUsuario('normal')->forceFill([
            'email' => 'multi@ejemplo.com',
            'password' => 'Password123',
        ])->save();

        $credenciales = ['email' => 'multi@ejemplo.com', 'password' => 'Password123'];

        $primerToken = $this->postJson('/api/login', $credenciales)->json('access_token');
        $this->postJson('/api/login', $credenciales)->assertOk();

        // Quedan los dos tokens en la base de datos.
        $this->assertDatabaseCount('personal_access_tokens', 2);

        // Y el primer token sigue siendo válido (la sesión anterior no murió).
        $this->withToken($primerToken)->getJson('/api/user')->assertOk();
    }

    public function test_callback_google_redirige_usando_la_url_del_frontend(): void
    {
        // Regresión H-C: el callback arma la URL con config('services.frontend_url'), no env() (null tras config:cache). Forzamos el error mockeando Socialite.
        config(['services.frontend_url' => 'https://example.test/']);

        Socialite::shouldReceive('driver->stateless->user')
            ->andThrow(new \Exception('fallo de google'));

        $this->callbackGoogle()
            ->assertRedirect('https://example.test/login/login.html?error=google');
    }

    public function test_redirect_google_incluye_state_y_fija_la_cookie(): void
    {
        config(['services.google' => [
            'client_id' => 'demo-client-id',
            'client_secret' => 'demo-secret',
            'redirect' => 'https://example.test/api/auth/google/callback',
        ]]);

        $respuesta = $this->get('/api/auth/google/redirect');

        // El redirect a Google lleva el 'state' en la URL y dejamos la cookie para validarlo a la vuelta (anti-CSRF).
        $this->assertStringContainsString('state=', (string) $respuesta->headers->get('Location'));
        $respuesta->assertCookie('oauth_state');
    }

    public function test_callback_google_rechaza_state_que_no_coincide(): void
    {
        config(['services.frontend_url' => 'https://example.test']);

        // La cookie y el 'state' del query no coinciden (posible CSRF): se corta antes de tocar a Google.
        $this->withUnencryptedCookie('oauth_state', 'cookie-legitima')
            ->get('/api/auth/google/callback?state=state-del-atacante')
            ->assertRedirect('https://example.test/login/login.html?error=google_state');
    }

    public function test_callback_google_rechaza_cuando_falta_el_state(): void
    {
        config(['services.frontend_url' => 'https://example.test']);

        // Sin cookie ni 'state' (petición directa al callback): se rechaza.
        $this->get('/api/auth/google/callback')
            ->assertRedirect('https://example.test/login/login.html?error=google_state');
    }

    public function test_callback_google_usuario_nuevo_marca_nuevo_en_la_url(): void
    {
        config(['services.frontend_url' => 'https://example.test']);

        $googleUser = (new SocialiteUser)->setRaw(['email_verified' => true])->map([
            'email' => 'nuevo@gmail.com',
            'name' => 'Usuario Nuevo',
        ]);
        Socialite::shouldReceive('driver->stateless->user')->andReturn($googleUser);

        $respuesta = $this->callbackGoogle();

        // Redirige al frontend con el token y &nuevo=1 (contrato: F2 muestra el toast de bienvenida).
        $location = $respuesta->headers->get('Location');
        $this->assertStringContainsString('oauth.html#token=', $location);
        $this->assertStringContainsString('&nuevo=1', $location);

        $usuario = User::where('email', 'nuevo@gmail.com')->first();
        $this->assertNotNull($usuario);
        $this->assertTrue($usuario->esNormal());
    }

    public function test_callback_google_rechaza_email_no_verificado(): void
    {
        config(['services.frontend_url' => 'https://example.test']);

        $googleUser = (new SocialiteUser)->setRaw(['email_verified' => false])->map([
            'email' => 'sinverificar@gmail.com',
            'name' => 'Sin Verificar',
        ]);
        Socialite::shouldReceive('driver->stateless->user')->andReturn($googleUser);

        $this->callbackGoogle()
            ->assertRedirect('https://example.test/login/login.html?error=google_email');

        // No se crea ninguna cuenta a partir de un email sin verificar.
        $this->assertDatabaseMissing('users', ['email' => 'sinverificar@gmail.com']);
    }

    public function test_callback_google_no_loguea_si_el_email_es_de_un_admin(): void
    {
        config(['services.frontend_url' => 'https://example.test']);

        // Ya existe un admin con ese correo: Google no debe loguear como él (se fuerza login clásico).
        $this->crearUsuario('admin')->forceFill(['email' => 'jefe@gmail.com'])->save();

        $googleUser = (new SocialiteUser)->setRaw(['email_verified' => true])->map([
            'email' => 'jefe@gmail.com',
            'name' => 'Suplantador',
        ]);
        Socialite::shouldReceive('driver->stateless->user')->andReturn($googleUser);

        $this->callbackGoogle()
            ->assertRedirect('https://example.test/login/login.html?error=google_privilegiado');

        // No se emitió ningún token para esa cuenta.
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_el_token_caduca_tras_la_expiracion_configurada(): void
    {
        // H-D: los tokens caducan tras el plazo configurado en sanctum.expiration
        // (1440 min por defecto). Leemos el valor real y viajamos justo más allá.
        $minutos = (int) config('sanctum.expiration');
        $this->assertGreaterThan(0, $minutos, 'sanctum.expiration debe ser > 0 (no null)');

        $this->crearUsuario('normal')->forceFill([
            'email' => 'caduca@ejemplo.com',
            'password' => 'Password123',
        ])->save();

        $token = $this->postJson('/api/login', [
            'email' => 'caduca@ejemplo.com',
            'password' => 'Password123',
        ])->json('access_token');

        // Dentro de la ventana: el token funciona.
        $this->withToken($token)->getJson('/api/user')->assertOk();

        // Envejecemos el token más allá del plazo (su id es la parte antes del '|').
        $id = explode('|', $token, 2)[0];
        PersonalAccessToken::findOrFail($id)->forceFill([
            'created_at' => now()->subMinutes($minutos + 1),
        ])->save();

        // El guard memoriza al usuario entre llamadas del test; lo olvidamos para que la 2.ª re-evalúe el token (como en HTTP real).
        $this->app['auth']->forgetGuards();

        // Pasado el plazo: el token caduca y la API responde 401.
        $this->withToken($token)->getJson('/api/user')->assertStatus(401);
    }

    public function test_login_se_bloquea_tras_demasiados_intentos(): void
    {
        $credenciales = ['email' => 'noexiste@ejemplo.com', 'password' => 'claveMala'];

        // El límite es 5 por minuto; al 6.º intento debe responder 429.
        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/login', $credenciales);
        }

        $this->postJson('/api/login', $credenciales)->assertStatus(429);
    }

    public function test_registro_envia_correo_de_verificacion(): void
    {
        Notification::fake();

        $this->postJson('/api/register', [
            'name' => 'Nuevo Usuario',
            'email' => 'porverificar@ejemplo.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertCreated();

        $usuario = User::where('email', 'porverificar@ejemplo.com')->first();
        // Nace sin verificar y con su correo de verificación enviado.
        $this->assertNull($usuario->email_verified_at);
        Notification::assertSentTo($usuario, VerificarEmailNotification::class);
    }

    public function test_olvide_password_envia_enlace_a_usuario_existente(): void
    {
        Notification::fake();

        $usuario = $this->crearUsuario('normal');
        $usuario->forceFill(['email' => 'existe@ejemplo.com'])->save();

        $this->postJson('/api/password/olvide', ['email' => 'existe@ejemplo.com'])->assertOk();

        Notification::assertSentTo($usuario, RestablecerPasswordNotification::class);
    }

    public function test_olvide_password_no_revela_correos_inexistentes(): void
    {
        Notification::fake();

        // Mismo 200 genérico aunque el correo no exista: no se filtra qué cuentas están registradas.
        $this->postJson('/api/password/olvide', ['email' => 'fantasma@ejemplo.com'])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_restablecer_password_con_token_valido_cambia_la_clave_y_cierra_sesiones(): void
    {
        $usuario = $this->crearUsuario('normal');
        $usuario->forceFill(['email' => 'reset@ejemplo.com', 'password' => 'Password123'])->save();

        // Una sesión viva que el cambio de clave debe invalidar.
        $usuario->createToken('auth_token');
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $token = Password::createToken($usuario);

        $this->postJson('/api/password/restablecer', [
            'token' => $token,
            'email' => 'reset@ejemplo.com',
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])->assertOk();

        // Los tokens previos se borraron al cambiar la contraseña.
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // La clave vieja ya no sirve; la nueva sí.
        $this->postJson('/api/login', ['email' => 'reset@ejemplo.com', 'password' => 'Password123'])
            ->assertStatus(401);
        $this->postJson('/api/login', ['email' => 'reset@ejemplo.com', 'password' => 'NuevaClave123'])
            ->assertOk();
    }

    public function test_restablecer_password_con_token_invalido_devuelve_422(): void
    {
        $usuario = $this->crearUsuario('normal');
        $usuario->forceFill(['email' => 'reset2@ejemplo.com'])->save();

        $this->postJson('/api/password/restablecer', [
            'token' => 'token-invalido',
            'email' => 'reset2@ejemplo.com',
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])->assertStatus(422);
    }

    public function test_verificar_email_con_firma_valida_marca_verificado_y_redirige(): void
    {
        config(['services.frontend_url' => 'https://example.test']);

        $usuario = $this->crearUsuario('normal');
        $usuario->forceFill(['email' => 'verifica@ejemplo.com', 'email_verified_at' => null])->save();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $usuario->id,
            'hash' => sha1($usuario->getEmailForVerification()),
        ], absolute: false);

        $this->get($url)->assertRedirect('https://example.test/login/login.html?verificado=1');

        $this->assertNotNull($usuario->fresh()->email_verified_at);
    }

    public function test_verificar_email_con_firma_invalida_redirige_con_error(): void
    {
        config(['services.frontend_url' => 'https://example.test']);

        $usuario = $this->crearUsuario('normal');
        $usuario->forceFill(['email_verified_at' => null])->save();

        // Sin los parámetros de firma la URL no es válida: redirige con error y no verifica.
        $this->get('/api/email/verificar/'.$usuario->id.'/'.sha1($usuario->getEmailForVerification()))
            ->assertRedirect('https://example.test/login/login.html?error=verificacion');

        $this->assertNull($usuario->fresh()->email_verified_at);
    }

    public function test_reenviar_verificacion_a_usuario_no_verificado(): void
    {
        Notification::fake();

        $usuario = $this->crearUsuario('normal');
        $usuario->forceFill(['email_verified_at' => null])->save();
        Sanctum::actingAs($usuario);

        $this->postJson('/api/email/reenviar-verificacion')->assertOk();

        Notification::assertSentTo($usuario, VerificarEmailNotification::class);
    }

    public function test_reenviar_verificacion_no_reenvia_si_ya_esta_verificado(): void
    {
        Notification::fake();

        // El factory crea el usuario ya verificado.
        $usuario = $this->crearUsuario('normal');
        Sanctum::actingAs($usuario);

        $this->postJson('/api/email/reenviar-verificacion')->assertOk();

        Notification::assertNothingSent();
    }

    public function test_usuario_no_verificado_no_puede_crear_incidencia(): void
    {
        $usuario = $this->crearUsuario('normal');
        $usuario->forceFill(['email_verified_at' => null])->save();
        Sanctum::actingAs($usuario);

        // El middleware 'verificado' corta antes de la validación: 403, no 422.
        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos())->assertStatus(403);
    }

    public function test_usuario_verificado_pasa_el_middleware_y_crea_incidencia(): void
    {
        $usuario = $this->crearUsuario('normal');
        Sanctum::actingAs($usuario);

        $this->postJson('/api/incidencias', $this->datosIncidenciaValidos())->assertCreated();
    }

    public function test_callback_google_marca_el_correo_como_verificado(): void
    {
        config(['services.frontend_url' => 'https://example.test']);

        $googleUser = (new SocialiteUser)->setRaw(['email_verified' => true])->map([
            'email' => 'googleverificado@gmail.com',
            'name' => 'Google Verificado',
        ]);
        Socialite::shouldReceive('driver->stateless->user')->andReturn($googleUser);

        $this->callbackGoogle();

        // La cuenta creada por Google nace verificada (Google ya validó el correo).
        $usuario = User::where('email', 'googleverificado@gmail.com')->first();
        $this->assertNotNull($usuario->email_verified_at);
    }

    public function test_quitar_foto_borra_la_foto_de_perfil(): void
    {
        Storage::fake('public');

        // Un usuario con una foto ya guardada en disco.
        Storage::disk('public')->put('perfiles/vieja.jpg', 'contenido');
        $usuario = $this->crearUsuario('normal');
        $usuario->forceFill(['foto_perfil' => 'perfiles/vieja.jpg'])->save();

        Sanctum::actingAs($usuario);

        $this->putJson('/api/perfil', [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'quitar_foto' => true,
        ])->assertOk()->assertJsonPath('foto_perfil', null);

        // Queda sin foto en la BD y el archivo se borró del disco.
        $this->assertDatabaseHas('users', ['id' => $usuario->id, 'foto_perfil' => null]);
        Storage::disk('public')->assertMissing('perfiles/vieja.jpg');
    }
}
