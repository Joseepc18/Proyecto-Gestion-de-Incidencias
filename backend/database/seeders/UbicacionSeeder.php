<?php

namespace Database\Seeders;

use App\Models\Ciudad;
use App\Models\Pais;
use App\Models\Provincia;
use Illuminate\Database\Seeder;

class UbicacionSeeder extends Seeder
{
    // Puebla las 24 provincias y sus cantones con lat/lng desde database/data/ubicaciones_ec.json
    // (generado cruzando datasets reales; ver build_ubicaciones.mjs). updateOrCreate respeta el
    // UNIQUE(nombre_ciudad, id_provincia) y actualiza las coords de las ciudades ya existentes.
    public function run(): void
    {
        $ruta = database_path('data/ubicaciones_ec.json');
        $data = json_decode(file_get_contents($ruta), true);

        $ecuador = Pais::firstOrCreate(['nombre_pais' => $data['pais']]);

        foreach ($data['provincias'] as $prov) {
            $provincia = Provincia::firstOrCreate([
                'nombre_provincia' => $prov['provincia'],
                'id_pais' => $ecuador->id_pais,
            ]);

            foreach ($prov['cantones'] as $canton) {
                Ciudad::updateOrCreate(
                    ['nombre_ciudad' => $canton['ciudad'], 'id_provincia' => $provincia->id_provincia],
                    ['latitud' => $canton['latitud'], 'longitud' => $canton['longitud']]
                );
            }
        }

        if (! empty($data['sin_coordenadas'])) {
            $this->command?->warn('Cantones sin coordenadas (quedan sin lat/lng): '.count($data['sin_coordenadas']));
        }
    }
}
