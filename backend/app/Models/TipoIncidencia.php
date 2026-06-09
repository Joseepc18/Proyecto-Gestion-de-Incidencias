<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoIncidencia extends Model
{
    protected $table = 'tipos_incidencia';

    protected $primaryKey = 'id_tipo_incidencia';

    protected $fillable = [
        'nombre_tipo_incidencia',
        'descripcion_tipo_incidencia',
    ];

    public function subtipos()
    {
        return $this->hasMany(SubtipoIncidencia::class, 'id_tipo_incidencia', 'id_tipo_incidencia');
    }
}
