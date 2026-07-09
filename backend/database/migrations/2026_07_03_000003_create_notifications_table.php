<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tabla nativa de notificaciones de Laravel (la usa el trait Notifiable): PK uuid + morph + data json.
// Es una tabla del framework (como users/jobs), por eso va en migración nueva, no editando otra.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            // jsonb (no el text por defecto de Laravel): permite consultar dentro del payload
            // (data->tipo, data->id_incidencia) para consolidar comentarios y marcar reaperturas leídas.
            $table->jsonb('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
