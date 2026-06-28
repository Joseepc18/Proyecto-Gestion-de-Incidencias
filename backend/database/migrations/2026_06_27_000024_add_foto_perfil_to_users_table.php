<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
        ALTER TABLE users ADD COLUMN IF NOT EXISTS foto_perfil VARCHAR(255) NULL;
        ');
    }

    public function down(): void
    {
        DB::statement('
        ALTER TABLE users DROP COLUMN IF EXISTS foto_perfil;
        ');
    }
};
