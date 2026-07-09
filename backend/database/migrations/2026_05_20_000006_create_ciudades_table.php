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
        CREATE TABLE ciudades(
            id_ciudad BIGSERIAL PRIMARY KEY,
            nombre_ciudad VARCHAR(255) NOT NULL,
            id_provincia BIGINT NOT NULL,
            -- Coordenadas del cantón (capital). Nullable: hay cantones sin dato en el dataset.
            -- DECIMAL(10,7) = ~1 cm de precisión, suficiente para resolver el cantón más cercano.
            latitud DECIMAL(10, 7),
            longitud DECIMAL(10, 7),
            FOREIGN KEY (id_provincia) REFERENCES provincias(id_provincia) ON DELETE RESTRICT ON UPDATE CASCADE,
            UNIQUE(nombre_ciudad, id_provincia),
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
        DROP TABLE IF EXISTS ciudades CASCADE;
        ');
    }
};
