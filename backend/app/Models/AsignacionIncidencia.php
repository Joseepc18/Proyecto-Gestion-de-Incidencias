<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignacionIncidencia extends Model
{
    protected $table = 'asignaciones_incidencia';

    protected $primaryKey = 'id_asignacion';

    protected $fillable = [
        'id_incidencia',
        'id_usuario',
        'rol_asignado',
    ];

    public function incidencia()
    {
        return $this->belongsTo(Incidencia::class, 'id_incidencia', 'id_incidencia');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }
}
