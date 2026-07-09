<?php

use Illuminate\Support\Facades\Route;

// La app es un SPA servido por Nginx + API stateless (Sanctum). Este redirect solo cubre el acceso directo a Laravel por "/".
// Route::redirect (no un closure) para que route:cache funcione en prod.
Route::redirect('/', '/login/login.html');
