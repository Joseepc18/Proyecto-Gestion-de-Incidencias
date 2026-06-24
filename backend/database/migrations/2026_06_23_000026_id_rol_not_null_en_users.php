<?php

use Illuminate\Database\Migrations\Migration;

// H-06: el esquema divergió entre entornos. En local id_rol es NOT NULL (lo define
// la migración add_rol_to_users), pero en producción quedó nullable. Esta migración
// vuelve a forzar NOT NULL para que ambos entornos coincidan.
//
// IMPORTANTE: si en producción existe algún usuario con id_rol nulo, el SET NOT NULL
// fallará. Antes de correrla en prod, verificar que no haya nulos:
//   SELECT COUNT(*) FROM users WHERE id_rol IS NULL;   -- debe dar 0
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
