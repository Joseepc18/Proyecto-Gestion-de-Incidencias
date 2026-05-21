<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubtipoIncidencia extends Model
{
    protected $table = 'subtipos_incidencia';
    protected $primaryKey = 'id_subtipo_incidencia';

    protected $fillable = [
        'nombre_subtipo_incidencia',
        'descripcion_subtipo_incidencia',
        'id_tipo_incidencia',
    ];

    public function tipo()
    {
        return $this->belongsTo(TipoIncidencia::class, 'id_tipo_incidencia', 'id_tipo_incidencia');
    }

    public function incidencias()
    {
        return $this->hasMany(Incidencia::class, 'id_subtipo_incidencia', 'id_subtipo_incidencia');
    }
}