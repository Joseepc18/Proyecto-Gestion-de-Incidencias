<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incidencia extends Model
{
    protected $table = 'incidencias';
    protected $primaryKey = 'id_incidencia';

    protected $fillable = [
        'nombre_incidencia',
        'descripcion_incidencia',
        'direccion_incidencia',
        'latitud_incidencia',
        'longitud_incidencia',
        'prioridad_incidencia',
        'estado_incidencia',
        'foto_incidencia',
        'id_ciudad',
        'id_subtipo_incidencia',
        'id_usuario',
        'fecha_resolucion',
    ];

    protected $casts = [
        'latitud_incidencia'  => 'decimal:8',
        'longitud_incidencia' => 'decimal:8',
        'fecha_resolucion'    => 'datetime',
    ];

    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class, 'id_ciudad', 'id_ciudad');
    }

    public function subtipo()
    {
        return $this->belongsTo(SubtipoIncidencia::class, 'id_subtipo_incidencia', 'id_subtipo_incidencia');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionIncidencia::class, 'id_incidencia', 'id_incidencia');
    }

    public function historialEstados()
    {
        return $this->hasMany(HistorialEstado::class, 'id_incidencia', 'id_incidencia');
    }

    public function comentarios()
    {
        return $this->hasMany(Comentario::class, 'id_incidencia', 'id_incidencia');
    }

    public function evidencias()
    {
        return $this->hasMany(Evidencia::class, 'id_incidencia', 'id_incidencia');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'id_incidencia', 'id_incidencia');
    }
}