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
        $reglas = [
            'nombre_incidencia' => 'required|string|min:5|max:100',
            'descripcion_incidencia' => 'nullable|string|max:500',
            'direccion_incidencia' => 'nullable|string|max:500',
            'latitud_incidencia' => 'required|numeric|between:-5.5,1.8',
            'longitud_incidencia' => 'required|numeric|between:-82.0,-74.5',
            'id_ciudad' => 'required|exists:ciudades,id_ciudad',
            'id_subtipo_incidencia' => 'required|exists:subtipos_incidencia,id_subtipo_incidencia',
            'fotos' => 'nullable|array|max:3',
            'fotos.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ];

        // El ciudadano siempre crea en MEDIA; solo el admin puede fijar la prioridad
        // al crear. Si la manda otro rol, no se valida y el controller la deja en MEDIA.
        if ($this->user()?->esAdmin()) {
            $reglas['prioridad_incidencia'] = 'nullable|in:ALTA,MEDIA,BAJA';
        }

        return $reglas;
    }
}
