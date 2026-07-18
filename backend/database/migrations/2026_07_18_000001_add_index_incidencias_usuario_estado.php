<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Compuesto (id_usuario, estado_incidencia): cubre en una pasada el listado del ciudadano (esNormal), que siempre filtra por id_usuario y a menudo también por estado.
        DB::unprepared('CREATE INDEX IF NOT EXISTS idx_incidencias_usuario_estado ON incidencias(id_usuario, estado_incidencia);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP INDEX IF EXISTS idx_incidencias_usuario_estado;');
    }
};
