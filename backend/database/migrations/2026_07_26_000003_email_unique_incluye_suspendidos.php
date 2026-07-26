<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Revierte el índice parcial de 2026_06_30_000001: al excluir a los suspendidos dejaba su correo libre
// y la suspensión se esquivaba re-registrándose con el mismo. Migración nueva y no editando la original
// porque aquella ya corrió en producción y Laravel no la vuelve a aplicar.
return new class extends Migration
{
    public function up(): void
    {
        // Se limpian las dos formas posibles: en una BD nueva es constraint, tras 2026_06_30_000001 es índice.
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_email_unique;');
        DB::statement('DROP INDEX IF EXISTS users_email_unique;');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_email_unique UNIQUE (email);');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_email_unique;');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_email_unique ON users (email) WHERE deleted_at IS NULL;');
    }
};
