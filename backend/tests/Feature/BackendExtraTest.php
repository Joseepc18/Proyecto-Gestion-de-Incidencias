<?php

namespace Tests\Feature;

use App\Models\Comentario;
use App\Models\SubtipoIncidencia;
use App\Notifications\AvisoCambioEmailNotification;
use App\Notifications\ConfirmarCambioEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BackendExtraTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_solo_el_autor_edita_su_comentario(): void
    {
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);
        $comentario = Comentario::create([
            'id_incidencia' => $incidencia->id_incidencia,
            'id_usuario' => $autor->id,
            'comentario' => 'Comentario original.',
        ]);

        // El autor sí puede editarlo.
        Sanctum::actingAs($autor);
        $this->putJson("/api/comentarios/{$comentario->id_comentario}", [
            'comentario' => 'Comentario corregido.',
        ])->assertOk();

        // Otro usuario no.
        Sanctum::actingAs($this->crearUsuario('normal'));
        $this->putJson("/api/comentarios/{$comentario->id_comentario}", [
            'comentario' => 'Intento ajeno.',
        ])->assertStatus(403);
    }

    public function test_usuario_sube_su_foto_de_perfil(): void
    {
        Storage::fake('public');
        $usuario = $this->crearUsuario('normal');
        Sanctum::actingAs($usuario);

        $this->put('/api/perfil', [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'foto' => UploadedFile::fake()->image('avatar.jpg'),
        ])->assertOk();

        $usuario->refresh();
        $this->assertNotNull($usuario->foto_perfil);
        Storage::disk('public')->assertExists($usuario->foto_perfil);
    }

    public function test_cambiar_correo_exige_la_contrasena_actual(): void
    {
        $usuario = $this->crearUsuario('normal');
        Sanctum::actingAs($usuario);

        // Sin la contraseña actual, el cambio de correo se rechaza.
        $this->putJson('/api/perfil', [
            'name' => $usuario->name,
            'email' => 'nuevo@example.com',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $usuario->refresh();
        $this->assertNull($usuario->email_pendiente);
    }

    public function test_cambiar_correo_no_es_inmediato_y_notifica(): void
    {
        Notification::fake();
        $usuario = $this->crearUsuario('normal');
        $correoViejo = $usuario->email;
        Sanctum::actingAs($usuario);

        $this->putJson('/api/perfil', [
            'name' => $usuario->name,
            'email' => 'nuevo@example.com',
            'current_password' => 'password',
        ])->assertOk();

        $usuario->refresh();
        // El correo NO cambia al instante: queda como pendiente.
        $this->assertSame($correoViejo, $usuario->email);
        $this->assertSame('nuevo@example.com', $usuario->email_pendiente);

        // Enlace de confirmación al correo nuevo (on-demand) + aviso al correo viejo.
        Notification::assertSentOnDemand(ConfirmarCambioEmailNotification::class);
        Notification::assertSentTo($usuario, AvisoCambioEmailNotification::class);
    }

    public function test_confirmar_cambio_de_correo_aplica_el_correo_nuevo(): void
    {
        $usuario = $this->crearUsuario('normal');
        $usuario->email_pendiente = 'nuevo@example.com';
        $usuario->save();

        $url = URL::temporarySignedRoute(
            'email.confirmar-cambio',
            now()->addHour(),
            ['id' => $usuario->id, 'hash' => sha1($usuario->email_pendiente)],
            absolute: false
        );

        $this->get($url)->assertRedirect();

        $usuario->refresh();
        $this->assertSame('nuevo@example.com', $usuario->email);
        $this->assertNull($usuario->email_pendiente);
        $this->assertNotNull($usuario->email_verified_at);
    }

    public function test_cambiar_correo_con_2fa_exige_ademas_un_codigo(): void
    {
        $admin = $this->crearUsuario('admin');
        Sanctum::actingAs($admin);

        // Con 2FA activo la contraseña actual no basta: falta el código.
        $this->putJson('/api/perfil', [
            'name' => $admin->name,
            'email' => 'admin-nuevo@example.com',
            'current_password' => 'password',
        ])->assertStatus(422)->assertJsonValidationErrors('two_factor_code');

        // Con un recovery code válido, el cambio se acepta.
        $this->putJson('/api/perfil', [
            'name' => $admin->name,
            'email' => 'admin-nuevo@example.com',
            'current_password' => 'password',
            'two_factor_code' => 'ABCD-1234',
        ])->assertOk();

        $admin->refresh();
        $this->assertSame('admin-nuevo@example.com', $admin->email_pendiente);
    }

    public function test_super_admin_crea_tipo_y_no_puede_borrarlo_con_subtipos(): void
    {
        Sanctum::actingAs($this->crearUsuario('super_admin'));

        // Crear un tipo.
        $resp = $this->postJson('/api/tipos-incidencia', [
            'nombre_tipo_incidencia' => 'Alumbrado público',
        ])->assertCreated();
        $idTipo = $resp->json('id_tipo_incidencia');

        // Con un subtipo asociado, no se puede eliminar (guarda de integridad).
        SubtipoIncidencia::create([
            'nombre_subtipo_incidencia' => 'Poste apagado',
            'id_tipo_incidencia' => $idTipo,
        ]);
        $this->deleteJson("/api/tipos-incidencia/{$idTipo}")->assertStatus(422);

        $this->assertDatabaseHas('tipos_incidencia', ['id_tipo_incidencia' => $idTipo]);
    }
}
