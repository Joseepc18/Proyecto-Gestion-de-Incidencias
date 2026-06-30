<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{
    protected $table = 'ciudades';

    protected $primaryKey = 'id_ciudad';

    protected $fillable = [
        'nombre_ciudad',
        'id_provincia',
        'latitud',
        'longitud',
    ];

    // El frontend resuelve el cantón más cercano con estas coords: las quiere como números, no strings.
    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
    ];

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'id_provincia', 'id_provincia');
    }

    public function incidencias()
    {
        return $this->hasMany(Incidencia::class, 'id_ciudad', 'id_ciudad');
    }
}
