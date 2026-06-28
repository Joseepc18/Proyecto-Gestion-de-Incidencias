<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignarTecnicoRequest extends FormRequest
{
    // La autorización (solo admin) la cubre el middleware 'admin' de la ruta.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_usuario' => ['required', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'rol_asignado' => 'required|in:RESPONSABLE,APOYO',
        ];
    }
}
