<?php

use App\Http\Controllers\Api\AsignacionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogoAdminController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\ComentarioController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EvidenciaController;
use App\Http\Controllers\Api\IncidenciaController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Rutas públicas (sin token) — con límite de intentos para frenar fuerza bruta
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Login con Google (OAuth) — navegación del navegador, devuelven redirecciones
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Rutas protegidas (requieren token Sanctum).
// throttle:120,1 = máx. 120 peticiones por minuto y POR USUARIO (autenticado,
// Laravel keyea por id, no por IP → no se pisan entre usuarios tras Cloudflare).
// Holgado para el polling de la campana (cada 30s = 2/min) + navegación normal.
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    // Apis de usuario
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/perfil', [AuthController::class, 'actualizarPerfil']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Apis de catálogos para poblar los formularios
    Route::get('/catalogos/tipos-incidencia', [CatalogoController::class, 'tiposIncidencia']);
    Route::get('/catalogos/ciudades', [CatalogoController::class, 'ciudades']);
    Route::get('/catalogos/provincias', [CatalogoController::class, 'provincias']);
    Route::get('/catalogos/paises', [CatalogoController::class, 'paises']);

    // Apis de incidencias
    Route::get('/incidencias', [IncidenciaController::class, 'listadoIncidencias']);
    Route::post('/incidencias', [IncidenciaController::class, 'crearIncidencia']);
    Route::get('/incidencias/{incidencia}', [IncidenciaController::class, 'verIncidencia']);
    Route::put('/incidencias/{incidencia}', [IncidenciaController::class, 'actualizarIncidencia']);
    Route::delete('/incidencias/{incidencia}', [IncidenciaController::class, 'eliminarIncidencia']);
    Route::get('/incidencias/{incidencia}/historial', [IncidenciaController::class, 'historialIncidencia']);
    Route::patch('/incidencias/{incidencia}/estado', [IncidenciaController::class, 'cambiarEstado']);

    // Apis de comentarios
    Route::get('/incidencias/{incidencia}/comentarios', [ComentarioController::class, 'listadoComentarios']);
    Route::post('/incidencias/{incidencia}/comentarios', [ComentarioController::class, 'crearComentario']);
    Route::put('/comentarios/{comentario}', [ComentarioController::class, 'actualizarComentario']);

    // Apis de evidencias (fotos)
    Route::post('/incidencias/{incidencia}/evidencias', [EvidenciaController::class, 'subir']);
    Route::delete('/evidencias/{evidencia}', [EvidenciaController::class, 'eliminar']);

    // Apis de notificaciones (del usuario autenticado)
    Route::get('/notificaciones', [NotificacionController::class, 'listado']);
    Route::patch('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodas']);
    Route::patch('/notificaciones/{notificacion}/leida', [NotificacionController::class, 'marcarLeida']);

    // Apis de asignaciones (responsable / apoyo)
    Route::get('/incidencias/{incidencia}/asignaciones', [AsignacionController::class, 'listado']);
    Route::middleware('admin')->group(function () {
        Route::post('/incidencias/{incidencia}/asignaciones', [AsignacionController::class, 'asignar']);
        Route::delete('/asignaciones/{asignacion}', [AsignacionController::class, 'quitar']);
        Route::get('/tecnicos', [AsignacionController::class, 'tecnicos']);

        // CRUD de catálogos de tipos y subtipos (solo admin)
        Route::post('/tipos-incidencia', [CatalogoAdminController::class, 'crearTipo']);
        Route::put('/tipos-incidencia/{tipo}', [CatalogoAdminController::class, 'actualizarTipo']);
        Route::delete('/tipos-incidencia/{tipo}', [CatalogoAdminController::class, 'eliminarTipo']);
        Route::post('/subtipos-incidencia', [CatalogoAdminController::class, 'crearSubtipo']);
        Route::put('/subtipos-incidencia/{subtipo}', [CatalogoAdminController::class, 'actualizarSubtipo']);
        Route::delete('/subtipos-incidencia/{subtipo}', [CatalogoAdminController::class, 'eliminarSubtipo']);

        // Métricas del dashboard (solo admin)
        Route::get('/dashboard/metricas', [DashboardController::class, 'metricas']);

        // Gestión de usuarios (solo admin)
        Route::get('/usuarios', [UserController::class, 'listado']);
        Route::post('/usuarios', [UserController::class, 'crear']);
        Route::put('/usuarios/{usuario}', [UserController::class, 'actualizar']);
        Route::delete('/usuarios/{usuario}', [UserController::class, 'eliminar']);
        Route::get('/roles', [UserController::class, 'roles']);
    });
});
