<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    // S7: alinea el UNIQUE de users.email con la validación withoutTrashed.
    // El UNIQUE plano cuenta a los suspendidos (soft-deleted) y rompía con 500 al
    // reusar su correo; el índice único parcial solo aplica a usuarios activos.
    public function up(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_email_unique;');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_email_unique ON users (email) WHERE deleted_at IS NULL;');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_email_unique;');
        DB::statement('ALTER TABLE users ADD CONSTRAINT users_email_unique UNIQUE (email);');
    }
};
