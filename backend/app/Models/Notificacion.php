<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';
    protected $primaryKey = 'id_notificacion';

    protected $fillable = [
        'id_incidencia',
        'id_usuario',
        'mensaje_notificacion',
        'estado_lectura',
        'fecha_lectura',
        'tipo_notificacion',
    ];

    protected $casts = [
        'estado_lectura' => 'boolean',
        'fecha_lectura'  => 'datetime',
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