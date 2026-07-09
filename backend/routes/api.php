<?php

use App\Http\Controllers\Api\AsignacionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BitacoraController;
use App\Http\Controllers\Api\BroadcastAuthController;
use App\Http\Controllers\Api\CatalogoAdminController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\ComentarioController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EvidenciaController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\IncidenciaController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\PermisoController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Healthcheck público (sin token, throttle holgado 120/min) para monitoreo y pruebas de carga.
Route::get('/health', HealthController::class)->middleware('throttle:120,1');

// Rutas públicas (sin token) — con límite de intentos para frenar fuerza bruta.
// El 3er parámetro (prefijo) es obligatorio en cada una: sin sesión, Laravel keyea el throttle
// solo por IP (ignora la ruta/dominio), así que rutas con el mismo N,M compartirían cupo entre sí.
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1,register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1,login');

// Login con Google (OAuth) — navegación del navegador, devuelven redirecciones
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Restablecer contraseña (público): el token del correo es la credencial.
Route::post('/password/olvide', [AuthController::class, 'olvidePassword'])->middleware('throttle:5,1,password-olvide');
Route::post('/password/restablecer', [AuthController::class, 'restablecerPassword'])->middleware('throttle:5,1,password-restablecer');

// Verificación de email vía enlace firmado (navegación del navegador, devuelve redirección al frontend).
Route::get('/email/verificar/{id}/{hash}', [AuthController::class, 'verificarEmail'])
    ->name('verification.verify')
    ->middleware('throttle:6,1,email-verificar');

// Confirmación del cambio de correo: enlace firmado enviado al correo NUEVO; recién ahí se aplica el cambio.
Route::get('/email/confirmar-cambio/{id}/{hash}', [AuthController::class, 'verificarCambioEmail'])
    ->name('email.confirmar-cambio')
    ->middleware('throttle:6,1,email-confirmar-cambio');

// Segundo factor del login (público): la credencial es el challenge_token efímero emitido por /login.
Route::post('/2fa/challenge', [AuthController::class, 'dosFactorChallenge'])->middleware('throttle:6,1,2fa-challenge');

// Archivo de evidencia privado: lo carga el <img> (sin token Bearer), por eso la credencial es la firma con expiración.
Route::get('/evidencias/{evidencia}/archivo', [EvidenciaController::class, 'archivo'])
    ->name('evidencias.archivo')
    ->middleware('signed');

// Foto de perfil privada: mismo patrón que evidencias (la firma reemplaza al token Bearer que el <img> no manda).
Route::get('/usuarios/{usuario}/foto', [UserController::class, 'foto'])
    ->name('usuarios.foto')
    ->middleware('signed');

// Rutas protegidas (token Sanctum). throttle:120,1 = 120 req/min por usuario (Laravel keyea por id, no por IP); holgado para el polling de la campana.
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    // Apis de usuario
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/perfil', [AuthController::class, 'actualizarPerfil']);
    Route::post('/logout', [AuthController::class, 'logout']);
    // El 3er parámetro (prefijo) es obligatorio aquí: por defecto Laravel keyea el throttle SOLO
    // por id de usuario (ignora la ruta) en peticiones autenticadas, así que sin prefijo propio
    // estas 4 rutas compartirían un único cupo de 6/min entre sí.
    Route::post('/email/reenviar-verificacion', [AuthController::class, 'reenviarVerificacion'])->middleware('throttle:6,1,email-verificacion');

    // Gestión del segundo factor (TOTP) del propio usuario.
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->middleware('throttle:6,1,2fa-enable');
    Route::post('/2fa/confirm', [TwoFactorController::class, 'confirm'])->middleware('throttle:6,1,2fa-confirm');
    Route::delete('/2fa', [TwoFactorController::class, 'disable'])->middleware('throttle:6,1,2fa-disable');

    // Autorización de canales privados de WebSocket (Reverb); valida con el token Sanctum.
    Route::post('/broadcasting/auth', BroadcastAuthController::class);

    // Apis de catálogos para poblar los formularios
    Route::get('/catalogos/tipos-incidencia', [CatalogoController::class, 'tiposIncidencia']);
    Route::get('/catalogos/ciudades', [CatalogoController::class, 'ciudades']);
    Route::get('/catalogos/provincias', [CatalogoController::class, 'provincias']);
    Route::get('/catalogos/paises', [CatalogoController::class, 'paises']);

    // Apis de incidencias.
    // El '2fa' de las acciones de gestión solo bloquea si esAdmin() sin 2FA (ver RequiereDosFactor):
    // técnicos y ciudadanos, que también usan estado/comentar/evidencias, pasan sin verse afectados.
    Route::get('/incidencias', [IncidenciaController::class, 'listadoIncidencias']);
    Route::post('/incidencias', [IncidenciaController::class, 'crearIncidencia'])->middleware('verificado');
    // Latido del candado (sin {incidencia}): refresca el lease de todos los reclamos del admin. Va antes del binding.
    Route::post('/incidencias/reclamo/heartbeat', [IncidenciaController::class, 'heartbeatReclamo'])->middleware('2fa');
    // Papelera (sin {incidencia}): va antes del binding, si no "papelera" se intenta resolver como id y da 404.
    Route::get('/incidencias/papelera', [IncidenciaController::class, 'papelera'])->middleware(['permiso:incidencias.papelera', '2fa']);
    Route::get('/incidencias/{incidencia}', [IncidenciaController::class, 'verIncidencia']);
    Route::put('/incidencias/{incidencia}', [IncidenciaController::class, 'actualizarIncidencia'])->middleware('2fa');
    Route::delete('/incidencias/{incidencia}', [IncidenciaController::class, 'eliminarIncidencia'])->middleware('2fa');
    Route::get('/incidencias/{incidencia}/historial', [IncidenciaController::class, 'historialIncidencia']);
    Route::patch('/incidencias/{incidencia}/estado', [IncidenciaController::class, 'cambiarEstado'])->middleware('2fa');
    Route::post('/incidencias/{incidencia}/solicitar-reapertura', [IncidenciaController::class, 'solicitarReapertura'])->middleware('verificado');
    Route::post('/incidencias/{incidencia}/rechazar-reapertura', [IncidenciaController::class, 'rechazarReapertura'])->middleware('2fa');
    Route::post('/incidencias/{incidencia}/reclamar', [IncidenciaController::class, 'reclamarIncidencia'])->middleware('2fa');
    Route::delete('/incidencias/{incidencia}/reclamar', [IncidenciaController::class, 'liberarReclamo'])->middleware('2fa');
    Route::patch('/incidencias/{incidencia}/archivar', [IncidenciaController::class, 'archivarIncidencia'])->middleware('2fa');

    // Apis de comentarios (crear/editar: 2FA a los admin y correo verificado al ciudadano; técnicos pasan).
    Route::get('/incidencias/{incidencia}/comentarios', [ComentarioController::class, 'listadoComentarios']);
    Route::post('/incidencias/{incidencia}/comentarios', [ComentarioController::class, 'crearComentario'])->middleware(['verificado', '2fa']);
    Route::put('/comentarios/{comentario}', [ComentarioController::class, 'actualizarComentario'])->middleware(['verificado', '2fa']);

    // Apis de evidencias (fotos): subir/eliminar exigen 2FA a los admin y correo verificado al ciudadano; el técnico responsable pasa.
    Route::post('/incidencias/{incidencia}/evidencias', [EvidenciaController::class, 'subir'])->middleware(['verificado', '2fa']);
    Route::delete('/evidencias/{evidencia}', [EvidenciaController::class, 'eliminar'])->middleware(['verificado', '2fa']);

    // Apis de notificaciones (del usuario autenticado)
    Route::get('/notificaciones', [NotificacionController::class, 'listado']);
    Route::patch('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodas']);
    Route::patch('/notificaciones/{id}/leida', [NotificacionController::class, 'marcarLeida']);

    // Apis de asignaciones (responsable / apoyo)
    Route::get('/incidencias/{incidencia}/asignaciones', [AsignacionController::class, 'listado']);

    // Métricas del panel del técnico (el rol se valida en el controller)
    Route::get('/dashboard/tecnico', [DashboardController::class, 'metricasTecnico']);

    // Gestión operativa de incidencias (admin): asignar técnicos.
    Route::middleware(['permiso:incidencias.gestionar', '2fa'])->group(function () {
        Route::post('/incidencias/{incidencia}/asignaciones', [AsignacionController::class, 'asignar']);
        Route::delete('/asignaciones/{asignacion}', [AsignacionController::class, 'quitar']);
        Route::get('/tecnicos', [AsignacionController::class, 'tecnicos']);
    });

    // Papelera de incidencias: restaurar y purgar (separado de incidencias.gestionar).
    Route::middleware(['permiso:incidencias.papelera', '2fa'])->group(function () {
        Route::post('/incidencias/{id}/restaurar', [IncidenciaController::class, 'restaurarIncidencia'])->where('id', '[0-9]+');
        Route::delete('/incidencias/{id}/purgar', [IncidenciaController::class, 'purgarIncidencia'])->where('id', '[0-9]+');
    });

    // Dashboard de métricas: view-only, no implica poder gestionar incidencias.
    Route::middleware(['permiso:dashboard.ver', '2fa'])->group(function () {
        Route::get('/dashboard/metricas', [DashboardController::class, 'metricas']);
    });

    // Bitácora de errores del sistema (auditoría).
    Route::middleware(['permiso:bitacora.ver', '2fa'])->group(function () {
        Route::get('/bitacora', [BitacoraController::class, 'listado']);
    });

    // CRUD de catálogos de tipos y subtipos (solo super_admin).
    Route::middleware(['permiso:catalogos.administrar', '2fa'])->group(function () {
        Route::post('/tipos-incidencia', [CatalogoAdminController::class, 'crearTipo']);
        Route::put('/tipos-incidencia/{tipo}', [CatalogoAdminController::class, 'actualizarTipo']);
        Route::delete('/tipos-incidencia/{tipo}', [CatalogoAdminController::class, 'eliminarTipo']);
        Route::post('/subtipos-incidencia', [CatalogoAdminController::class, 'crearSubtipo']);
        Route::put('/subtipos-incidencia/{subtipo}', [CatalogoAdminController::class, 'actualizarSubtipo']);
        Route::delete('/subtipos-incidencia/{subtipo}', [CatalogoAdminController::class, 'eliminarSubtipo']);
    });

    // Gestión de usuarios (solo super_admin).
    Route::middleware(['permiso:usuarios.administrar', '2fa'])->group(function () {
        Route::get('/usuarios', [UserController::class, 'listado']);
        Route::post('/usuarios', [UserController::class, 'crear']);
        Route::put('/usuarios/{usuario}', [UserController::class, 'actualizar']);
        Route::delete('/usuarios/{usuario}', [UserController::class, 'eliminar']);
        Route::post('/usuarios/{id}/restaurar', [UserController::class, 'restaurar'])->where('id', '[0-9]+');
        Route::get('/roles', [UserController::class, 'roles']);
    });

    // Asignación de permisos por rol (solo super_admin).
    Route::middleware(['permiso:permisos.administrar', '2fa'])->group(function () {
        Route::get('/permisos', [PermisoController::class, 'index']);
        Route::put('/roles/{rol}/permisos', [PermisoController::class, 'sincronizar']);
    });
});
