<?php

use App\Http\Controllers\Web\PanelAuthController;
use App\Http\Controllers\Web\PanelUsuarioController;
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
        Route::get('/', [PanelUsuarioController::class, 'index'])->name('panel.usuarios');
    });
});
