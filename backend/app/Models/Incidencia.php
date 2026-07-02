<?php

namespace App\Models;

use App\Exceptions\AlmacenamientoException;
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
        'id_ciudad',
        'id_subtipo_incidencia',
        'id_usuario',
        'fecha_resolucion',
        'reapertura_solicitada',
    ];

    protected $casts = [
        'latitud_incidencia' => 'decimal:8',
        'longitud_incidencia' => 'decimal:8',
        'fecha_resolucion' => 'datetime',
        'reapertura_solicitada' => 'boolean',
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

    // Acumula las rutas en $rutasGuardadas (por referencia) para que el controller las limpie si la transacción revienta.
    public function guardarEvidencias(array $fotos, int $idUsuario, ?string $tipo, array &$rutasGuardadas): void
    {
        foreach ($fotos as $foto) {
            $ruta = $foto->store('incidencias', 'public');
            if ($ruta === false) {
                throw new AlmacenamientoException('No se pudo guardar la foto en el disco');
            }
            $rutasGuardadas[] = $ruta;
            $datos = ['url_evidencia' => $ruta, 'id_usuario' => $idUsuario];
            if ($tipo !== null) {
                $datos['tipo_evidencia'] = $tipo;
            }
            $this->evidencias()->create($datos);
        }
    }
}
