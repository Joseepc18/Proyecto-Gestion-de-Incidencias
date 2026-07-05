<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Evidencia extends Model
{
    use HasFactory;

    // Minutos de validez de la URL firmada que sirve el archivo privado.
    private const MINUTOS_URL_FIRMADA = 10;

    protected $table = 'evidencias';

    protected $primaryKey = 'id_evidencia';

    protected $fillable = [
        'id_incidencia',
        'url_evidencia',
        'id_usuario',
        'tipo_evidencia',
    ];

    // Se agrega a cada serialización para que el frontend cargue la foto por ruta firmada, no por /storage.
    protected $appends = ['url_completa'];

    // URL firmada con expiración: es la credencial del <img> (que no envía el token Bearer).
    public function getUrlCompletaAttribute(): string
    {
        return URL::temporarySignedRoute(
            'evidencias.archivo',
            now()->addMinutes(self::MINUTOS_URL_FIRMADA),
            ['evidencia' => $this->id_evidencia]
        );
    }

    public function incidencia()
    {
        return $this->belongsTo(Incidencia::class, 'id_incidencia', 'id_incidencia');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }
}
