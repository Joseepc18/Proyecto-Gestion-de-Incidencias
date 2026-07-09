<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BitacoraError;
use Illuminate\Http\Request;

class BitacoraController extends Controller
{
    // Listado paginado de errores registrados por errorControlado()/BitacoraError::registrar(); filtra por tipo_error.
    public function listado(Request $request)
    {
        $query = BitacoraError::with('usuario:id,name')->orderBy('created_at', 'desc');

        if ($request->filled('tipo_error')) {
            $query->where('tipo_error', $request->tipo_error);
        }

        return $query->paginate($this->perPage($request));
    }
}
