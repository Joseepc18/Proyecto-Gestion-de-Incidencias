<?php

namespace Database\Seeders;

use App\Models\Ciudad;
use App\Models\Pais;
use App\Models\Provincia;
use Illuminate\Database\Seeder;

class UbicacionSeeder extends Seeder
{
    public function run(): void
    {
        $ecuador = Pais::firstOrCreate(['nombre_pais' => 'Ecuador']);

        // Azuay
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Azuay', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Cuenca',    'id_provincia' => $p->id_provincia]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Gualaceo',  'id_provincia' => $p->id_provincia]);

        // Bolívar
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Bolívar', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Guaranda', 'id_provincia' => $p->id_provincia]);

        // Cañar
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Cañar', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Azogues',    'id_provincia' => $p->id_provincia]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'La Troncal', 'id_provincia' => $p->id_provincia]);

        // Carchi
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Carchi', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Tulcán', 'id_provincia' => $p->id_provincia]);

        // Chimborazo
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Chimborazo', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Riobamba', 'id_provincia' => $p->id_provincia]);

        // Cotopaxi
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Cotopaxi', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Latacunga', 'id_provincia' => $p->id_provincia]);

        // El Oro
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'El Oro', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Machala',    'id_provincia' => $p->id_provincia]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Huaquillas', 'id_provincia' => $p->id_provincia]);

        // Esmeraldas
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Esmeraldas', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Esmeraldas', 'id_provincia' => $p->id_provincia]);

        // Galápagos
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Galápagos', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Puerto Baquerizo Moreno', 'id_provincia' => $p->id_provincia]);

        // Guayas
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Guayas', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Guayaquil', 'id_provincia' => $p->id_provincia]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Durán',     'id_provincia' => $p->id_provincia]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Milagro',   'id_provincia' => $p->id_provincia]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Daule',     'id_provincia' => $p->id_provincia]);

        // Imbabura
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Imbabura', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Ibarra',  'id_provincia' => $p->id_provincia]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Otavalo', 'id_provincia' => $p->id_provincia]);

        // Loja
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Loja', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Loja', 'id_provincia' => $p->id_provincia]);

        // Los Ríos
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Los Ríos', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Babahoyo', 'id_provincia' => $p->id_provincia]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Quevedo',  'id_provincia' => $p->id_provincia]);

        // Manabí
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Manabí', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Portoviejo', 'id_provincia' => $p->id_provincia]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Manta',      'id_provincia' => $p->id_provincia]);

        // Morona Santiago
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Morona Santiago', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Macas', 'id_provincia' => $p->id_provincia]);

        // Napo
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Napo', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Tena', 'id_provincia' => $p->id_provincia]);

        // Orellana
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Orellana', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Francisco de Orellana', 'id_provincia' => $p->id_provincia]);

        // Pastaza
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Pastaza', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Puyo', 'id_provincia' => $p->id_provincia]);

        // Pichincha
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Pichincha', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Quito',      'id_provincia' => $p->id_provincia]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Sangolquí',  'id_provincia' => $p->id_provincia]);

        // Santa Elena
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Santa Elena', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Santa Elena', 'id_provincia' => $p->id_provincia]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Salinas',     'id_provincia' => $p->id_provincia]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'La Libertad', 'id_provincia' => $p->id_provincia]);

        // Santo Domingo
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Santo Domingo de los Tsáchilas', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Santo Domingo', 'id_provincia' => $p->id_provincia]);

        // Sucumbíos
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Sucumbíos', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Nueva Loja', 'id_provincia' => $p->id_provincia]);

        // Tungurahua
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Tungurahua', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Ambato', 'id_provincia' => $p->id_provincia]);

        // Zamora Chinchipe
        $p = Provincia::firstOrCreate(['nombre_provincia' => 'Zamora Chinchipe', 'id_pais' => $ecuador->id_pais]);
        Ciudad::firstOrCreate(['nombre_ciudad' => 'Zamora', 'id_provincia' => $p->id_provincia]);
    }
}
