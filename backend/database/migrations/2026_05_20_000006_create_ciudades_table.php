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
        CREATE TABLE ciudades(
            id_ciudad BIGSERIAL PRIMARY KEY,
            nombre_ciudad VARCHAR(255) NOT NULL UNIQUE,
            id_provincia BIGINT NOT NULL,
            FOREIGN KEY (id_provincia) REFERENCES provincias(id_provincia) ON DELETE RESTRICT ON UPDATE CASCADE,
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
        DROP TABLE IF EXISTS ciudades CASCADE;
        ");
    }
};
