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
        CREATE TABLE incidencias(
            id_incidencia BIGSERIAL PRIMARY KEY,
            nombre_incidencia VARCHAR(255) NOT NULL,
            descripcion_incidencia TEXT,
            direccion_incidencia VARCHAR(500),
            latitud_incidencia NUMERIC(10,8) NOT NULL,
            longitud_incidencia NUMERIC(11,8) NOT NULL,
            prioridad_incidencia VARCHAR(50) NOT NULL DEFAULT 'MEDIA' CHECK(prioridad_incidencia IN ('ALTA','MEDIA','BAJA')),
            estado_incidencia VARCHAR(50) NOT NULL DEFAULT 'PENDIENTE' CHECK(estado_incidencia IN ('PENDIENTE','EN_PROCESO','RESUELTO')),
            foto_incidencia VARCHAR(500),
            id_ciudad BIGINT NOT NULL,
            id_subtipo_incidencia BIGINT NOT NULL,
            id_usuario BIGINT NOT NULL,
            fecha_resolucion TIMESTAMP,
            FOREIGN KEY (id_ciudad) REFERENCES ciudades(id_ciudad) ON DELETE RESTRICT ON UPDATE CASCADE,
            FOREIGN KEY (id_subtipo_incidencia) REFERENCES subtipos_incidencia(id_subtipo_incidencia) ON DELETE RESTRICT ON UPDATE CASCADE,
            FOREIGN KEY (id_usuario) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
        DROP TABLE IF EXISTS incidencias CASCADE;
        ");
    }
};
