<?php

namespace App\Models;

use App\Enums\EstadoIncidencia;
use App\Enums\PrioridadIncidencia;
use App\Enums\RolAsignacion;
use App\Exceptions\AlmacenamientoException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;

class Incidencia extends Model
{
    use HasFactory, Prunable, SoftDeletes;

    protected $table = 'incidencias';

    protected $primaryKey = 'id_incidencia';

    // Segundos sin latido tras los cuales el reclamo de un admin se considera vencido (abandonado).
    public const RECLAMO_TTL_SEGUNDOS = 120;

    // Tope de fotos por tipo de evidencia (REPORTE/RESOLUCION); debe coincidir con v_limite del trigger fn_limite_evidencias.
    public const LIMITE_EVIDENCIAS_POR_TIPO = 3;

    // Horas que una incidencia RESUELTO tiene para pedir reapertura antes de que el command incidencias:archivar-resueltas la cierre.
    // Fuente única del plazo: lo usan el job, la notificación al reportador y el IncidenciaResource (el frontend lo lee de ahí).
    public const HORAS_PARA_ARCHIVAR = 24;

    // Días en la papelera (soft-deleted) tras los cuales "php artisan model:prune" borra la incidencia DEFINITIVAMENTE.
    // Cada incidencia cuenta sus propios días desde su deleted_at (purga rodante, no en bloque).
    public const DIAS_RETENCION_PAPELERA = 30;

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
        'correo_detalle_enviado',
    ];

    protected $casts = [
        'estado_incidencia' => EstadoIncidencia::class,
        'prioridad_incidencia' => PrioridadIncidencia::class,
        'latitud_incidencia' => 'decimal:8',
        'longitud_incidencia' => 'decimal:8',
        'fecha_resolucion' => 'datetime',
        'reapertura_solicitada' => 'boolean',
        'reclamo_visto_en' => 'datetime',
        'correo_detalle_enviado' => 'boolean',
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

    // Hito del correo de detalle al ciudadano: reúne admin + EN_PROCESO + prioridad asignada + técnico responsable,
    // y aún no se ha enviado. La bandera se re-evalúa tras cada acción de gestión; quien complete la última pieza lo dispara.
    public function estaListaParaCorreoDetalle(): bool
    {
        return ! $this->correo_detalle_enviado
            && $this->id_admin_atiende !== null
            && $this->estado_incidencia === EstadoIncidencia::EnProceso
            && $this->prioridad_incidencia !== PrioridadIncidencia::SinAsignar
            && $this->asignaciones()->where('rol_asignado', RolAsignacion::Responsable->value)->exists();
    }

    // Acumula las rutas en $rutasGuardadas (por referencia) para que el controller las limpie si la transacción revienta.
    public function guardarEvidencias(array $fotos, int $idUsuario, ?string $tipo, array &$rutasGuardadas): void
    {
        foreach ($fotos as $foto) {
            $ruta = $foto->store('incidencias', 'evidencias');
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

    // Selección de model:prune: incidencias en la papelera hace más de DIAS_RETENCION_PAPELERA (cada una por su deleted_at).
    public function prunable()
    {
        return static::where('deleted_at', '<=', now()->subDays(self::DIAS_RETENCION_PAPELERA));
    }

    // Antes de que prune() haga el forceDelete: limpia lo que la fila no arrastra sola (no hay ON DELETE CASCADE).
    // Réplica de IncidenciaController@purgarIncidencia: hijos + notificaciones + archivos físicos de las evidencias.
    protected function pruning(): void
    {
        $rutasEvidencias = $this->evidencias->pluck('url_evidencia');

        $this->comentarios()->delete();
        $this->historialEstados()->delete();
        $this->asignaciones()->delete();
        DatabaseNotification::where('data->id_incidencia', (string) $this->id_incidencia)->delete();

        foreach ($rutasEvidencias as $ruta) {
            if (! Storage::disk('evidencias')->delete($ruta)) {
                BitacoraError::registrar(null, 'ARCHIVO', 'Incidencia@pruning', 'no se pudo borrar '.$ruta);
            }
        }
    }
}
