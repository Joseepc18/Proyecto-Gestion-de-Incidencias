<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

// Autorización de canales privados de WebSocket (Reverb); valida con el token Sanctum.
class BroadcastAuthController extends Controller
{
    public function __invoke(Request $request)
    {
        return Broadcast::auth($request);
    }
}
