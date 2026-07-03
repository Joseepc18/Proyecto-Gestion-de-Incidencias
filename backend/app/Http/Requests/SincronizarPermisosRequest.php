<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SincronizarPermisosRequest extends FormRequest
{
    // Ruta ya protegida por el middleware 'permiso:permisos.administrar'.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permisos' => ['present', 'array'],
            'permisos.*' => ['integer', Rule::exists('permisos', 'id_permiso')],
        ];
    }
}
