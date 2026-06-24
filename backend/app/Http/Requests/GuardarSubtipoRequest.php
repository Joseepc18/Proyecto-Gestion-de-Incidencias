<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarSubtipoRequest extends FormRequest
{
    // Ruta protegida por el middleware 'admin'.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_subtipo_incidencia' => 'required|string|max:255',
            'descripcion_subtipo_incidencia' => 'nullable|string|max:500',
            'id_tipo_incidencia' => 'required|exists:tipos_incidencia,id_tipo_incidencia',
        ];
    }
}
