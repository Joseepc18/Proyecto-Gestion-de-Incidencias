<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    // Alinea el UNIQUE de users.email con withoutTrashed: el plano contaba a los suspendidos y rompía con 500 al reusar el correo.
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
