<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearIncidenciaRequest extends FormRequest
{
    // La autorización (crear = cualquier usuario autenticado) la cubre el middleware.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // between de lat/long = rango geográfico de Ecuador
        return [
            'nombre_incidencia' => 'required|string|min:5|max:255',
            'descripcion_incidencia' => 'nullable|string|max:1000',
            'direccion_incidencia' => 'nullable|string|max:500',
            'latitud_incidencia' => 'required|numeric|between:-5.5,1.8',
            'longitud_incidencia' => 'required|numeric|between:-82.0,-74.5',
            // Opcional: si no llega, el controller la deja en MEDIA (la prioridad la fija el admin).
            'prioridad_incidencia' => 'nullable|in:ALTA,MEDIA,BAJA',
            'id_ciudad' => 'required|exists:ciudades,id_ciudad',
            'id_subtipo_incidencia' => 'required|exists:subtipos_incidencia,id_subtipo_incidencia',
            'fotos' => 'nullable|array|max:3',
            'fotos.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}
