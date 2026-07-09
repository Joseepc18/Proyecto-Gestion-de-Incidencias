<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarSubtipoRequest extends FormRequest
{
    // Ruta protegida por el middleware 'permiso:catalogos.administrar'.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('subtipo')?->id_subtipo_incidencia;

        return [
            'nombre_subtipo_incidencia' => [
                'required', 'string', 'max:255',
                Rule::unique('subtipos_incidencia', 'nombre_subtipo_incidencia')
                    ->where('id_tipo_incidencia', $this->input('id_tipo_incidencia'))
                    ->ignore($id, 'id_subtipo_incidencia'),
            ],
            'descripcion_subtipo_incidencia' => 'nullable|string|max:500',
            'id_tipo_incidencia' => 'required|exists:tipos_incidencia,id_tipo_incidencia',
        ];
    }
}
