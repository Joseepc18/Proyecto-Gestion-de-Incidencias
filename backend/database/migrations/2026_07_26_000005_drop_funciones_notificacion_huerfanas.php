<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Las notificaciones las emite el pipeline de Events/Listeners de Laravel, no la BD: estas cuatro funciones
// quedaron sin trigger que las use al consolidar una migración ya ejecutada, así que en producción siguen
// existiendo aunque ninguna migración del repo las cree. Se van en migración nueva por eso mismo.
// Sin CASCADE a propósito: si algo dependiera de ellas, preferimos que la migración falle a borrarlo en silencio.
return new class extends Migration
{
    private const FUNCIONES = [
        'fn_notificar_asignacion',
        'fn_notificar_cambio_estado',
        'fn_notificar_nueva_incidencia',
        'fn_notificar_nuevo_comentario',
    ];

    public function up(): void
    {
        foreach (self::FUNCIONES as $funcion) {
            DB::statement("DROP FUNCTION IF EXISTS {$funcion};");
        }
    }

    // Sin vuelta atrás: el cuerpo de estas funciones no vive en el repo, no hay nada que recrear.
    public function down(): void {}
};
