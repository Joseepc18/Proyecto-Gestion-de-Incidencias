<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;
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

        // El registro público siempre asigna el rol 'normal'.
        $usuario = User::where('email', 'juan@ejemplo.com')->first();
        $this->assertSame('normal', $usuario->rol->nombre_rol);
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

    public function test_login_se_bloquea_tras_demasiados_intentos(): void
    {
        $credenciales = ['email' => 'noexiste@ejemplo.com', 'password' => 'claveMala'];

        // El límite es 5 por minuto; al 6.º intento debe responder 429.
        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/login', $credenciales);
        }

        $this->postJson('/api/login', $credenciales)->assertStatus(429);
    }

    public function test_el_limite_de_login_no_se_esquiva_falsificando_x_forwarded_for(): void
    {
        $credenciales = ['email' => 'noexiste@ejemplo.com', 'password' => 'claveMala'];

        // Sin proxies confiables la cabecera del cliente se ignora: los intentos caen todos en el mismo cubo.
        for ($i = 1; $i <= 5; $i++) {
            $this->withHeader('X-Forwarded-For', "198.51.100.{$i}")->postJson('/api/login', $credenciales);
        }

        $this->withHeader('X-Forwarded-For', '198.51.100.6')
            ->postJson('/api/login', $credenciales)
            ->assertStatus(429);
    }

    public function test_el_limite_por_cuenta_frena_los_intentos_repartidos_entre_varias_ips(): void
    {
        $credenciales = ['email' => 'victima@ejemplo.com', 'password' => 'claveMala'];

        // Cada intento llega desde una IP distinta, así que el cubo por IP (5/min) nunca se agota.
        for ($i = 1; $i <= 10; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "203.0.113.{$i}"])->postJson('/api/login', $credenciales);
        }

        // El 11.º lo corta el cubo por correo (10/min).
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->postJson('/api/login', $credenciales)
            ->assertStatus(429);
    }

    // H-45: el limitador por cuenta no puede reventar antes de contar el intento; un correo que no
    // es texto tiene que morir en la validación (422), no en un 500 que además esquiva el tope.
    public function test_login_con_el_correo_como_arreglo_responde_422(): void
    {
        $this->postJson('/api/login', ['email' => ['a', 'b'], 'password' => 'claveMala'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_cambiar_la_contrasena_desde_el_perfil_cierra_las_demas_sesiones(): void
    {
        $usuario = $this->crearUsuario('normal');
        // Dos sesiones abiertas: la que hace el cambio y otra que debe quedar fuera.
        $tokenOtroDispositivo = $usuario->createToken('otro_dispositivo')->plainTextToken;
        $tokenActual = $usuario->createToken('auth_token')->plainTextToken;

        $this->withToken($tokenActual)->putJson('/api/perfil', [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
            'current_password' => 'password',
        ])->assertOk();

        // Se comprueba sobre los tokens y no con otra petición: dentro de un mismo test el guard
        // ya tiene el usuario resuelto en memoria y no volvería a validar la credencial.
        $this->assertNull(PersonalAccessToken::findToken($tokenOtroDispositivo));
        $this->assertNotNull(PersonalAccessToken::findToken($tokenActual));
        $this->assertDatabaseCount('personal_access_tokens', 1);
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

    public function test_callback_google_rechaza_state_que_no_coincide(): void
    {
        config(['services.frontend_url' => 'https://example.test']);

        // La cookie y el 'state' del query no coinciden (posible CSRF): se corta antes de tocar a Google.
        $this->withUnencryptedCookie('oauth_state', 'cookie-legitima')
            ->get('/api/auth/google/callback?state=state-del-atacante')
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

        // La cuenta creada por Google nace como 'normal' y verificada (Google ya validó el correo).
        $usuario = User::where('email', 'nuevo@gmail.com')->first();
        $this->assertTrue($usuario->esNormal());
        $this->assertNotNull($usuario->email_verified_at);
    }
}
