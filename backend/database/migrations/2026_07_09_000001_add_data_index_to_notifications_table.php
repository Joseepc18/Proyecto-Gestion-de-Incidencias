<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Índice de expresión para las consultas que filtran dentro del jsonb `data` de las notificaciones:
// IncidenciaController@purgarIncidencia (data->>'id_incidencia') y la marca de reaperturas leídas
// (data->>'tipo' + data->>'id_incidencia'). id_incidencia va de líder porque ambas lo filtran; sin él
// esas consultas hacen un scan de toda la tabla `notifications`.
// Va en migración nueva (no editando la de create): notifications se trata como tabla del framework.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE INDEX notifications_data_incidencia_tipo_idx ON notifications ((data->>'id_incidencia'), (data->>'tipo'))");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS notifications_data_incidencia_tipo_idx');
    }
};
