<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EntornoPruebasTest extends TestCase
{
    // Salvaguarda: la suite debe correr contra la BD de pruebas, no la de desarrollo.
    public function test_las_pruebas_usan_la_base_de_datos_de_pruebas(): void
    {
        $this->assertSame('gestion_incidencias_test', DB::connection()->getDatabaseName());
    }
}
