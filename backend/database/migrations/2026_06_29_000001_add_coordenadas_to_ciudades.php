<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Coordenadas del cantón (capital). Nullable: hay cantones sin dato en el dataset.
        // DECIMAL(10,7) = ~1 cm de precisión, suficiente para resolver el cantón más cercano.
        DB::statement('
        ALTER TABLE ciudades
            ADD COLUMN latitud DECIMAL(10, 7),
            ADD COLUMN longitud DECIMAL(10, 7);
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('
        ALTER TABLE ciudades
            DROP COLUMN IF EXISTS latitud,
            DROP COLUMN IF EXISTS longitud;
        ');
    }
};
