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
        ALTER TABLE users
        ADD COLUMN id_rol BIGINT NULL,
        ADD CONSTRAINT fk_users_roles FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE SET NULL;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
        ALTER TABLE users
        DROP CONSTRAINT IF EXISTS fk_users_roles,
        DROP COLUMN IF EXISTS id_rol;
        ");
    }
};
