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
            'fotos.*' => 'image|mimes:jpg,jpeg,png|max:3072',
        ];

        // Solo el admin fija prioridad y estado al crear; al resto se les ignora.
        if ($this->user()?->esAdmin()) {
            $reglas['prioridad_incidencia'] = 'nullable|in:ALTA,MEDIA,BAJA';
            $reglas['estado_incidencia'] = 'nullable|in:PENDIENTE,EN_PROCESO,RESUELTO';
        }

        return $reglas;
    }
}
