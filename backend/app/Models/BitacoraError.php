<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    public static function registrar(?User $usuario, string $tipo, string $contexto, string $mensaje): void
    {
        self::create([
            'id_usuario' => $usuario?->id,
            'tipo_error' => $tipo,
            'descripcion_error' => "$contexto: $mensaje",
        ]);
    }
}
