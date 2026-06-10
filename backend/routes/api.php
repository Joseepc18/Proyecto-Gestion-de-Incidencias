<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\IncidenciaController;
use Illuminate\Support\Facades\Route;

// Rutas públicas (sin token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas (requieren token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Apis de usuario
    Route::get('/user', [AuthController::class, 'me']);
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

});
