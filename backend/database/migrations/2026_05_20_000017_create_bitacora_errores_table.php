<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE TABLE bitacora_errores(
                id_bitacora_errores BIGSERIAL PRIMARY KEY,
                id_usuario BIGINT NULL,
                FOREIGN KEY (id_usuario) REFERENCES users(id) ON DELETE SET NULL,
                tipo_error VARCHAR(50) NOT NULL CHECK(tipo_error IN ('AUTENTICACION','VALIDACION','BASE_DATOS','SERVIDOR','ARCHIVO')),
                descripcion_error TEXT NOT NULL,
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
        DB::statement('DROP TABLE IF EXISTS bitacora_errores CASCADE;');
    }
};
