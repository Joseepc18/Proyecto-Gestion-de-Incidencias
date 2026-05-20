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
        CREATE TABLE evidencias(
            id_evidencia BIGSERIAL PRIMARY KEY,
            id_incidencia BIGINT NOT NULL,
            url_evidencia VARCHAR(255) NOT NULL,
            id_usuario BIGINT NOT NULL,
            FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia) ON DELETE CASCADE,
            FOREIGN KEY (id_usuario) REFERENCES users(id) ON DELETE CASCADE,
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
        DB::statement("DROP TABLE IF EXISTS evidencias CASCADE;");
    }
};
