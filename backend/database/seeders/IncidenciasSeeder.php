<?php

namespace Database\Seeders;

use App\Models\Incidencia;
use Illuminate\Database\Seeder;

class IncidenciasSeeder extends Seeder
{
    public function run(): void
    {
        Incidencia::create([
            'nombre_incidencia' => 'Bache en calle principal',
            'descripcion_incidencia' => 'Bache profundo de aproximadamente 50cm',
            'latitud_incidencia' => -2.1894,
            'longitud_incidencia' => -79.8891,
            'estado_incidencia' => 'PENDIENTE',
            'prioridad_incidencia' => 'ALTA',
            'id_ciudad' => 1,
            'id_subtipo_incidencia' => 1,
            'id_usuario' => 1,
        ]);
    }
}
