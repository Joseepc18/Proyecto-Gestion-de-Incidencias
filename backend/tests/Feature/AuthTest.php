<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
