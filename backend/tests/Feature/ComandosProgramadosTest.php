<?php

namespace Tests\Feature;

use App\Mail\ResumenDiarioMail;
use App\Models\User;
use App\Notifications\IncidenciaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ComandosProgramadosTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_digest_diario_envia_un_correo_por_cada_admin_con_incidencias_gestionar(): void
    {
        Mail::fake();
        $admin = $this->crearUsuario('admin');
        // super_admin es view-only (sin incidencias.gestionar): no debe recibir el digest.
        $this->crearUsuario('super_admin');
        $autor = $this->crearUsuario('normal');
        $this->crearIncidencia($autor);

        // El seeder ya deja un admin ("admin@sistema.com"): el digest debe llegarle a él y al de este test.
        $totalAdmins = User::conPermiso('incidencias.gestionar')->count();

        Artisan::call('incidencias:digest-diario');

        Mail::assertQueued(ResumenDiarioMail::class, $totalAdmins);
        Mail::assertQueued(ResumenDiarioMail::class, fn ($mail) => $mail->hasTo($admin->email));
    }

    public function test_recordar_chat_sin_leer_avisa_una_sola_vez_por_hilo(): void
    {
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);

        // Simula la notificación de "nuevo comentario" que ya dejó el pipeline de eventos, hace más de 24h.
        $autor->notify(new IncidenciaNotification('COMENTARIO', 'Nuevo comentario', $incidencia->id_incidencia));
        DatabaseNotification::where('notifiable_id', $autor->id)
            ->where('data->tipo', 'COMENTARIO')
            ->update(['created_at' => now()->subHours(25)]);

        Artisan::call('incidencias:recordar-chat-sin-leer');
        $this->assertNotificado($autor, 'RECORDATORIO_CHAT');

        $primeraVez = $this->notificacionesDe($autor, 'RECORDATORIO_CHAT')->count();

        // Corre de nuevo enseguida: no debe duplicar el aviso (TTL de 7 días en caché).
        Artisan::call('incidencias:recordar-chat-sin-leer');
        $this->assertSame($primeraVez, $this->notificacionesDe($autor, 'RECORDATORIO_CHAT')->count());
    }

    public function test_recordar_chat_sin_leer_ignora_mensajes_recientes(): void
    {
        $autor = $this->crearUsuario('normal');
        $incidencia = $this->crearIncidencia($autor);

        // Notificación de menos de 24h: todavía no le toca el recordatorio.
        $autor->notify(new IncidenciaNotification('COMENTARIO', 'Nuevo comentario', $incidencia->id_incidencia));

        Artisan::call('incidencias:recordar-chat-sin-leer');

        $this->assertNoNotificado($autor, 'RECORDATORIO_CHAT');
    }

    public function test_usuarios_de_prueba_de_carga_no_se_crean_en_produccion(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('carga:usuarios-prueba', ['accion' => 'crear', '--cantidad' => 1])
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'carga1@test.local']);
    }

    public function test_usuarios_de_prueba_de_carga_se_crean_y_se_borran_fuera_de_produccion(): void
    {
        $this->artisan('carga:usuarios-prueba', ['accion' => 'crear', '--cantidad' => 2])
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'carga1@test.local']);

        // El borrado es físico: no debe quedar ni la fila suspendida.
        $this->artisan('carga:usuarios-prueba', ['accion' => 'borrar'])->assertSuccessful();

        $this->assertSame(0, User::withTrashed()->where('email', 'like', 'carga%@test.local')->count());
    }
}
