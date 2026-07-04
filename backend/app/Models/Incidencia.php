<?php

namespace App\Models;

use App\Enums\EstadoIncidencia;
use App\Enums\PrioridadIncidencia;
use App\Exceptions\AlmacenamientoException;
use Illuminate\Database\Eloquent\Model;

class Incidencia extends Model
{
    protected $table = 'incidencias';

    protected $primaryKey = 'id_incidencia';

    // Segundos sin latido tras los cuales el reclamo de un admin se considera vencido (abandonado).
    public const RECLAMO_TTL_SEGUNDOS = 120;

    // Tope de fotos por tipo de evidencia (REPORTE/RESOLUCION); debe coincidir con v_limite del trigger fn_limite_evidencias.
    public const LIMITE_EVIDENCIAS_POR_TIPO = 3;

    // Set de relaciones para la respuesta de detalle tras mutar la incidencia; un solo lugar para todos los endpoints.
    public const RELACIONES_DETALLE = ['usuario', 'subtipo.tipo', 'ciudad.provincia', 'adminAtiende'];

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
        'id_admin_atiende',
        'reclamo_visto_en',
    ];

    protected $casts = [
        'estado_incidencia' => EstadoIncidencia::class,
        'prioridad_incidencia' => PrioridadIncidencia::class,
        'latitud_incidencia' => 'decimal:8',
        'longitud_incidencia' => 'decimal:8',
        'fecha_resolucion' => 'datetime',
        'reapertura_solicitada' => 'boolean',
        'reclamo_visto_en' => 'datetime',
    ];

    public function scopePendientes($query)
    {
        return $query->where('estado_incidencia', EstadoIncidencia::Pendiente->value);
    }

    public function scopeResueltas($query)
    {
        return $query->where('estado_incidencia', EstadoIncidencia::Resuelto->value);
    }

    // Todo lo que no está archivado: el listado activo por defecto.
    public function scopeActivas($query)
    {
        return $query->where('estado_incidencia', '<>', EstadoIncidencia::Cerrado->value);
    }

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

    // Admin que reclamó la incidencia (dueño de la atención); NULL si nadie la ha reclamado.
    public function adminAtiende()
    {
        return $this->belongsTo(User::class, 'id_admin_atiende', 'id');
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

    public function estaResuelta(): bool
    {
        return $this->estado_incidencia === EstadoIncidencia::Resuelto;
    }

    public function estaCerrada(): bool
    {
        return $this->estado_incidencia === EstadoIncidencia::Cerrado;
    }

    // El reclamo está vencido si hay un admin atendiendo pero su último latido caducó (abandonó la app).
    public function reclamoVencido(): bool
    {
        return $this->id_admin_atiende !== null
            && ($this->reclamo_visto_en === null
                || $this->reclamo_visto_en->lt(now()->subSeconds(self::RECLAMO_TTL_SEGUNDOS)));
    }

    // RESUELTO o CERRADO: ambos son de solo lectura (editar, comentar, subir evidencias, cambiar asignaciones).
    public function esTerminal(): bool
    {
        return $this->estaResuelta() || $this->estaCerrada();
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
