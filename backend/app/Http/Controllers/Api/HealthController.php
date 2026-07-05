<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Throwable;

// Healthcheck público (sin token, throttle holgado 120/min) para monitoreo y pruebas de carga; el SELECT 1 toca toda la cadena Nginx → PHP-FPM → PostgreSQL.
class HealthController extends Controller
{
    public function __invoke()
    {
        try {
            DB::select('select 1');
            $db = true;
        } catch (Throwable $e) {
            $db = false;
        }

        return response()->json(['status' => 'ok', 'db' => $db, 'host' => gethostname()]);
    }
}
