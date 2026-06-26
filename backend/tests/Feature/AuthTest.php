<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // Siembra roles y catálogos (una vez) para que existan al correr los tests.
    protected $seed = true;

    public function test_registro_crea_usuario_normal_y_devuelve_token(): void
    {
        $respuesta = $this->postJson('/api/register', [
            'name' => 'Juan Perez',
            'email' => 'juan@ejemplo.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $respuesta->assertCreated()
            ->assertJsonStructure(['access_token', 'token_type', 'user' => ['id', 'email']]);

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
        ])->assertOk()->assertJsonStructure(['access_token']);
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

        $this->get('/api/auth/google/callback')
            ->assertRedirect('https://example.test/login/login.html?error=google');
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
}
