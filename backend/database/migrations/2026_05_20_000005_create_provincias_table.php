<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('
        CREATE TABLE provincias(
            id_provincia BIGSERIAL PRIMARY KEY,
            nombre_provincia VARCHAR(255) NOT NULL,
            id_pais BIGINT NOT NULL,
            FOREIGN KEY (id_pais) REFERENCES paises(id_pais) ON DELETE RESTRICT ON UPDATE CASCADE,
            UNIQUE(nombre_provincia, id_pais),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('
        DROP TABLE IF EXISTS provincias CASCADE;
        ');
    }
};
