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
        CREATE TABLE comentarios(
            id_comentario BIGSERIAL PRIMARY KEY,
            id_incidencia BIGINT NOT NULL,
            id_usuario BIGINT NOT NULL,
            comentario TEXT NOT NULL,
            FOREIGN KEY (id_incidencia) REFERENCES incidencias(id_incidencia) ON DELETE RESTRICT ON UPDATE CASCADE,
            FOREIGN KEY (id_usuario) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
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
        DB::statement("DROP TABLE IF EXISTS comentarios CASCADE;");
    }
};
