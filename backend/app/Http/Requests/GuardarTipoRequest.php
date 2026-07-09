<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarTipoRequest extends FormRequest
{
    // Ruta protegida por el middleware 'permiso:catalogos.administrar'.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('tipo')?->id_tipo_incidencia;

        return [
            'nombre_tipo_incidencia' => [
                'required', 'string', 'max:255',
                Rule::unique('tipos_incidencia', 'nombre_tipo_incidencia')->ignore($id, 'id_tipo_incidencia'),
            ],
            'descripcion_tipo_incidencia' => 'nullable|string|max:500',
        ];
    }
}
