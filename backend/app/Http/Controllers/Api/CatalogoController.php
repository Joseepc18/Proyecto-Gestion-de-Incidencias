<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ciudad;
use App\Models\Pais;
use App\Models\Provincia;
use App\Models\TipoIncidencia;
use Illuminate\Support\Facades\Cache;

// Catálogos para poblar los dropdowns del frontend. Son datos casi estáticos: se cachean
// y CatalogoAdminController invalida la caché de tipos/subtipos al crear/editar/eliminar.
class CatalogoController extends Controller
{
    // Tipos con sus subtipos anidados
    public function tiposIncidencia()
    {
        return Cache::remember('catalogo_tipos_incidencia', 3600, function () {
            return TipoIncidencia::with('subtipos')->orderBy('nombre_tipo_incidencia')->get();
        });
    }

    // Ciudades con su provincia y país
    public function ciudades()
    {
        return Cache::remember('catalogo_ciudades', 3600, function () {
            return Ciudad::with('provincia.pais')->orderBy('nombre_ciudad')->get();
        });
    }

    // Provincias con su país
    public function provincias()
    {
        return Cache::remember('catalogo_provincias', 3600, function () {
            return Provincia::with('pais')->orderBy('nombre_provincia')->get();
        });
    }

    // Lista de países
    public function paises()
    {
        return Cache::remember('catalogo_paises', 3600, function () {
            return Pais::orderBy('nombre_pais')->get();
        });
    }
}
