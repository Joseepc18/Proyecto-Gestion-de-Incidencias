<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    // P1: Postgres no indexa las FK automáticamente. id_ciudad e id_subtipo_incidencia
    // se filtran (listado) y se joinean (dashboard, vistas de métricas) sin índice.
    public function up(): void
    {
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_incidencias_ciudad ON incidencias(id_ciudad);');
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_incidencias_subtipo ON incidencias(id_subtipo_incidencia);');
    }

    public function down(): void
    {
        DB::unprepared('DROP INDEX IF EXISTS idx_incidencias_ciudad;');
        DB::unprepared('DROP INDEX IF EXISTS idx_incidencias_subtipo;');
    }
};
