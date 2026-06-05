<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
        CREATE TABLE tipos_incidencia(
            id_tipo_incidencia BIGSERIAL PRIMARY KEY,
            nombre_tipo_incidencia VARCHAR(255) NOT NULL UNIQUE,
            descripcion_tipo_incidencia TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
        DROP TABLE IF EXISTS tipos_incidencia CASCADE;
        ");
    }
};