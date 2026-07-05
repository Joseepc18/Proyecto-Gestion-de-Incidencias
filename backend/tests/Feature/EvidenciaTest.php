<?php

namespace Tests\Feature;

use App\Models\Evidencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenciaTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_la_url_firmada_de_la_evidencia_sirve_el_archivo(): void
    {
        Storage::fake('evidencias');
        Storage::disk('evidencias')->put('incidencias/foto.jpg', 'contenido-falso');

        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $evidencia = Evidencia::factory()->create([
            'id_incidencia' => $incidencia->id_incidencia,
            'url_evidencia' => 'incidencias/foto.jpg',
        ]);

        // La URL firmada la genera el accessor (como en la respuesta ya autorizada de la incidencia).
        $this->get($evidencia->url_completa)
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    }

    public function test_sin_firma_valida_la_ruta_del_archivo_rechaza(): void
    {
        Storage::fake('evidencias');
        Storage::disk('evidencias')->put('incidencias/foto.jpg', 'contenido-falso');

        $incidencia = $this->crearIncidencia($this->crearUsuario('normal'));
        $evidencia = Evidencia::factory()->create([
            'id_incidencia' => $incidencia->id_incidencia,
            'url_evidencia' => 'incidencias/foto.jpg',
        ]);

        // Sin los parámetros expires/signature el middleware signed responde 403.
        $this->get('/api/evidencias/'.$evidencia->id_evidencia.'/archivo')
            ->assertForbidden();
    }
}
