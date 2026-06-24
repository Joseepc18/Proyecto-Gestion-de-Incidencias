<?php

use Illuminate\Database\Migrations\Migration;

// H-06: alinea id_rol a NOT NULL (en prod había quedado nullable).
// Falla si algún usuario tiene id_rol nulo: verificar antes en prod.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users ALTER COLUMN id_rol SET NOT NULL;');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users ALTER COLUMN id_rol DROP NOT NULL;');
    }
};
