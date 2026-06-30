<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Throwable;

class BitacoraError extends Model
{
    protected $table = 'bitacora_errores';

    protected $primaryKey = 'id_bitacora_errores';

    protected $fillable = [
        'id_usuario',
        'tipo_error',
        'descripcion_error',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    // Registra un error en la bitácora desde los catch; $contexto es 'Clase@metodo'.
    // Si $origen es una QueryException, el SQL se oculta (mismo criterio que el handler global).
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
