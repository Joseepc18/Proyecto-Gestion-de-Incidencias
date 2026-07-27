<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Fortify;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // Crea un usuario normal con 2FA ya confirmado y el secreto conocido del factory (para generar códigos válidos).
    private function usuarioConDosFactor(string $email = 'dosfactor@ejemplo.com'): User
    {
        $rol = Rol::where('nombre_rol', 'normal')->firstOrFail();

        return User::factory()->conDosFactor()->create([
            'id_rol' => $rol->id_rol,
            'email' => $email,
        ]);
    }

    // Código TOTP válido para un secreto cifrado en BD.
    private function codigoPara(string $secretoCifrado): string
    {
        $secreto = Fortify::currentEncrypter()->decrypt($secretoCifrado);

        return app(Google2FA::class)->getCurrentOtp($secreto);
    }

    public function test_enable_genera_secreto_qr_y_recovery_codes_sin_confirmar(): void
    {
        $usuario = $this->crearUsuario('normal');
        Sanctum::actingAs($usuario);

        $this->postJson('/api/2fa/enable')
            ->assertOk()
            ->assertJsonStructure(['svg', 'otpauth_url', 'recovery_codes']);

        $usuario->refresh();
        // Queda el secreto pero aún NO confirmado: el 2FA no se exige hasta confirmar.
        $this->assertNotNull($usuario->two_factor_secret);
        $this->assertNull($usuario->two_factor_confirmed_at);
        $this->assertFalse($usuario->hasEnabledTwoFactorAuthentication());
        $this->assertCount(8, $usuario->recoveryCodes());
    }

    public function test_confirm_con_codigo_valido_activa_el_2fa(): void
    {
        $usuario = $this->crearUsuario('normal');
        Sanctum::actingAs($usuario);

        $this->postJson('/api/2fa/enable')->assertOk();
        $codigo = $this->codigoPara($usuario->fresh()->two_factor_secret);

        $this->postJson('/api/2fa/confirm', ['code' => $codigo])->assertOk();

        $this->assertTrue($usuario->fresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_login_con_2fa_no_emite_token_y_pide_segundo_factor(): void
    {
        $this->usuarioConDosFactor();

        $this->postJson('/api/login', ['email' => 'dosfactor@ejemplo.com', 'password' => 'password'])
            ->assertOk()
            ->assertJson(['two_factor' => true])
            ->assertJsonStructure(['challenge_token'])
            // No se filtra el token ni los datos del usuario antes del segundo factor.
            ->assertJsonMissingPath('access_token')
            ->assertJsonMissingPath('user');
    }

    public function test_challenge_con_codigo_valido_emite_el_token(): void
    {
        $usuario = $this->usuarioConDosFactor();

        $challenge = $this->postJson('/api/login', ['email' => 'dosfactor@ejemplo.com', 'password' => 'password'])
            ->json('challenge_token');

        $codigo = $this->codigoPara($usuario->two_factor_secret);

        $this->postJson('/api/2fa/challenge', ['challenge_token' => $challenge, 'code' => $codigo])
            ->assertOk()
            ->assertJsonStructure(['access_token', 'user' => ['rol' => ['nombre_rol']]]);
    }

    public function test_challenge_con_codigo_invalido_no_emite_token(): void
    {
        $this->usuarioConDosFactor();

        $challenge = $this->postJson('/api/login', ['email' => 'dosfactor@ejemplo.com', 'password' => 'password'])
            ->json('challenge_token');

        // Con un código incorrecto no se emite token: es la defensa contra el bypass del segundo factor.
        $this->postJson('/api/2fa/challenge', ['challenge_token' => $challenge, 'code' => '000000'])
            ->assertStatus(422)
            ->assertJsonMissingPath('access_token');
    }

    public function test_challenge_con_token_invalido_es_rechazado(): void
    {
        $this->usuarioConDosFactor();

        // Un challenge_token que nunca se emitió (o ya expiró) no autoriza el segundo factor.
        $this->postJson('/api/2fa/challenge', ['challenge_token' => 'token-inexistente', 'code' => '000000'])
            ->assertStatus(422)
            ->assertJsonMissingPath('access_token');
    }

    // Mismo caso que en /login, el limitador por reto tampoco puede reventar con un valor que no es texto.
    public function test_challenge_con_el_token_como_arreglo_responde_422(): void
    {
        $this->postJson('/api/2fa/challenge', ['challenge_token' => ['a', 'b'], 'code' => '000000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('challenge_token');
    }

    public function test_disable_requiere_codigo_valido(): void
    {
        $usuario = $this->usuarioConDosFactor();
        Sanctum::actingAs($usuario);

        // Con código incorrecto no se desactiva (un token robado sin el authenticator no puede apagarlo).
        $this->deleteJson('/api/2fa', ['code' => '000000'])->assertStatus(422);
        $this->assertTrue($usuario->fresh()->hasEnabledTwoFactorAuthentication());

        $codigo = $this->codigoPara($usuario->two_factor_secret);
        $this->deleteJson('/api/2fa', ['code' => $codigo])->assertOk();
        $this->assertFalse($usuario->fresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_admin_sin_2fa_no_puede_realizar_acciones_privilegiadas(): void
    {
        $admin = $this->crearUsuario('admin', conDosFactor: false);
        Sanctum::actingAs($admin);

        $this->getJson('/api/tecnicos')
            ->assertStatus(403)
            ->assertJson(['two_factor_required' => true]);
    }
}
