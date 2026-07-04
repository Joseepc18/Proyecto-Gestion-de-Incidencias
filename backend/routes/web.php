<?php

use App\Http\Controllers\Web\PanelAuthController;
use App\Http\Controllers\Web\PanelCatalogoController;
use App\Http\Controllers\Web\PanelUsuarioController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Panel administrativo en Blade (server-side, guard web con sesión + CSRF).
// Convive con la API stateless (Sanctum/Bearer), que no se toca.
Route::prefix('panel')->group(function () {
    Route::get('/login', [PanelAuthController::class, 'mostrarLogin'])->name('panel.login');
    Route::post('/login', [PanelAuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [PanelAuthController::class, 'logout'])->name('panel.logout');

        // Entrada del panel: lleva a la primera sección que el usuario tenga permitida.
        Route::get('/', function () {
            return Auth::user()->tienePermiso('usuarios.administrar')
                ? redirect()->route('panel.usuarios')
                : redirect()->route('panel.catalogos');
        });

        // Gestión de usuarios (solo super_admin).
        Route::middleware('permiso:usuarios.administrar')->group(function () {
            Route::get('/usuarios', [PanelUsuarioController::class, 'index'])->name('panel.usuarios');
            Route::get('/usuarios/crear', [PanelUsuarioController::class, 'crear'])->name('panel.usuarios.crear');
            Route::post('/usuarios', [PanelUsuarioController::class, 'store'])->name('panel.usuarios.store');
            Route::get('/usuarios/{usuario}/editar', [PanelUsuarioController::class, 'editar'])->name('panel.usuarios.editar');
            Route::put('/usuarios/{usuario}', [PanelUsuarioController::class, 'update'])->name('panel.usuarios.update');
            Route::delete('/usuarios/{usuario}', [PanelUsuarioController::class, 'destroy'])->name('panel.usuarios.destroy');
            Route::post('/usuarios/{id}/restaurar', [PanelUsuarioController::class, 'restaurar'])
                ->whereNumber('id')->name('panel.usuarios.restaurar');
        });

        // Tipos y subtipos de incidencia (solo super_admin).
        Route::middleware('permiso:catalogos.administrar')->group(function () {
            Route::get('/catalogos', [PanelCatalogoController::class, 'index'])->name('panel.catalogos');

            Route::get('/catalogos/tipos/crear', [PanelCatalogoController::class, 'crearTipo'])->name('panel.catalogos.tipos.crear');
            Route::post('/catalogos/tipos', [PanelCatalogoController::class, 'guardarTipo'])->name('panel.catalogos.tipos.store');
            Route::get('/catalogos/tipos/{tipo}/editar', [PanelCatalogoController::class, 'editarTipo'])->name('panel.catalogos.tipos.editar');
            Route::put('/catalogos/tipos/{tipo}', [PanelCatalogoController::class, 'actualizarTipo'])->name('panel.catalogos.tipos.update');
            Route::delete('/catalogos/tipos/{tipo}', [PanelCatalogoController::class, 'eliminarTipo'])->name('panel.catalogos.tipos.destroy');

            Route::get('/catalogos/subtipos/crear', [PanelCatalogoController::class, 'crearSubtipo'])->name('panel.catalogos.subtipos.crear');
            Route::post('/catalogos/subtipos', [PanelCatalogoController::class, 'guardarSubtipo'])->name('panel.catalogos.subtipos.store');
            Route::get('/catalogos/subtipos/{subtipo}/editar', [PanelCatalogoController::class, 'editarSubtipo'])->name('panel.catalogos.subtipos.editar');
            Route::put('/catalogos/subtipos/{subtipo}', [PanelCatalogoController::class, 'actualizarSubtipo'])->name('panel.catalogos.subtipos.update');
            Route::delete('/catalogos/subtipos/{subtipo}', [PanelCatalogoController::class, 'eliminarSubtipo'])->name('panel.catalogos.subtipos.destroy');
        });
    });
});
