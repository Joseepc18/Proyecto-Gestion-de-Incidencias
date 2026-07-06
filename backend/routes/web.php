<?php

use Illuminate\Support\Facades\Route;

// La app es un SPA servido por Nginx + API stateless (Sanctum). Este redirect solo cubre el acceso directo a Laravel por "/".
Route::get('/', fn () => redirect('/login/login.html'));
