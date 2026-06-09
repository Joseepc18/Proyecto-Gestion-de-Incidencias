<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ciudad;
use App\Models\Pais;
use App\Models\Provincia;
use App\Models\TipoIncidencia;

// Catálogos para poblar los dropdowns del frontend.
class CatalogoController extends Controller
{
    // Tipos con sus subtipos anidados
    public function tiposIncidencia()
    {
        return response()->json(
            TipoIncidencia::with('subtipos')->orderBy('nombre_tipo_incidencia')->get()
        );
    }

    // Ciudades con su provincia y país
    public function ciudades()
    {
        return response()->json(
            Ciudad::with('provincia.pais')->orderBy('nombre_ciudad')->get()
        );
    }

    // Provincias con su país
    public function provincias()
    {
        return response()->json(
            Provincia::with('pais')->orderBy('nombre_provincia')->get()
        );
    }

    // Lista de países
    public function paises()
    {
        return response()->json(Pais::orderBy('nombre_pais')->get());
    }
}
