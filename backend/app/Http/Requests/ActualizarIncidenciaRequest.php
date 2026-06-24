<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarIncidenciaRequest extends FormRequest
{
    // Quién puede editar lo decide la IncidenciaPolicy en el controller.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_incidencia' => 'sometimes|string|min:5|max:255',
            'descripcion_incidencia' => 'sometimes|nullable|string',
            'direccion_incidencia' => 'sometimes|nullable|string|max:500',
            'latitud_incidencia' => 'sometimes|numeric|between:-5.5,1.8',
            'longitud_incidencia' => 'sometimes|numeric|between:-82.0,-74.5',
            'prioridad_incidencia' => 'sometimes|in:ALTA,MEDIA,BAJA',
            'id_ciudad' => 'sometimes|exists:ciudades,id_ciudad',
            'id_subtipo_incidencia' => 'sometimes|exists:subtipos_incidencia,id_subtipo_incidencia',
        ];
    }
}
