<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ciudad;
use App\Models\Pais;
use App\Models\Provincia;
use App\Models\TipoIncidencia;

// Catálogos para poblar los dropdowns del frontend.
// NOTA: se probó cachear estos catálogos con Redis (Cache::remember) pero causó 500 intermitentes
// en prod (__PHP_Incomplete_Class al deserializar el Collection cacheado — bug de serialización
// con phpredis en este entorno, no reproducido de forma 100% determinista). Revertido: son consultas
// baratas (221 ciudades, 6 tipos), no vale la pena el riesgo. Ver docs/dificultades_soluciones.md.
class CatalogoController extends Controller
{
    // Tipos con sus subtipos anidados
    public function tiposIncidencia()
    {
        return TipoIncidencia::with('subtipos')->orderBy('nombre_tipo_incidencia')->get();
    }

    // Ciudades con su provincia y país
    public function ciudades()
    {
        return Ciudad::with('provincia.pais')->orderBy('nombre_ciudad')->get();
    }

    // Provincias con su país
    public function provincias()
    {
        return Provincia::with('pais')->orderBy('nombre_provincia')->get();
    }

    // Lista de países
    public function paises()
    {
        return Pais::orderBy('nombre_pais')->get();
    }
}
