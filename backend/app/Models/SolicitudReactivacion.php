<?php

namespace App\Models;

use App\Enums\EstadoSolicitudReactivacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id_solicitud
 * @property int $id_usuario
 * @property string $motivo_solicitud
 * @property EstadoSolicitudReactivacion $estado_solicitud
 * @property int|null $id_admin_resuelve
 * @property Carbon|null $fecha_resolucion
 */
class SolicitudReactivacion extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_reactivacion';

    protected $primaryKey = 'id_solicitud';

    protected $fillable = [
        'id_usuario',
        'motivo_solicitud',
        'estado_solicitud',
        'id_admin_resuelve',
        'fecha_resolucion',
    ];

    protected function casts(): array
    {
        return [
            'estado_solicitud' => EstadoSolicitudReactivacion::class,
            'fecha_resolucion' => 'datetime',
        ];
    }

    public function scopePendientes($query)
    {
        return $query->where('estado_solicitud', EstadoSolicitudReactivacion::Pendiente);
    }

    // Cierra la solicitud dejando constancia de quién la resolvió y cuándo.
    public function resolver(EstadoSolicitudReactivacion $estado, User $admin): void
    {
        $this->estado_solicitud = $estado;
        $this->id_admin_resuelve = $admin->id;
        $this->fecha_resolucion = now();
        $this->save();
    }

    // withTrashed: el solicitante siempre está suspendido, si no el scope lo devolvería null.
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id')->withTrashed();
    }

    public function adminResuelve()
    {
        return $this->belongsTo(User::class, 'id_admin_resuelve', 'id');
    }
}
