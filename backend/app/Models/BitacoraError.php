<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\QueryException;
use Throwable;

class BitacoraError extends Model
{
    use Prunable;

    protected $table = 'bitacora_errores';

    protected $primaryKey = 'id_bitacora_errores';

    // Retención de la bitácora: pasado este plazo el error ya no sirve para diagnosticar y la tabla solo crecería.
    public const DIAS_RETENCION = 90;

    protected $fillable = [
        'id_usuario',
        'tipo_error',
        'descripcion_error',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    // Selección de model:prune: errores registrados hace más de DIAS_RETENCION.
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(self::DIAS_RETENCION));
    }

    // Registra un error en la bitácora desde los catch ($contexto = 'Clase@metodo'); si es QueryException, oculta el SQL.
    public static function registrar(?User $usuario, string $tipo, string $contexto, string $mensaje, ?Throwable $origen = null): void
    {
        $descripcion = $origen instanceof QueryException
            ? "$contexto: Error de base de datos (SQL oculto por seguridad)"
            : "$contexto: $mensaje";

        self::create([
            'id_usuario' => $usuario?->id,
            'tipo_error' => $tipo,
            'descripcion_error' => $descripcion,
        ]);
    }
}
