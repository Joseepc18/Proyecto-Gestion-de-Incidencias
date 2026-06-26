<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'id_usuario' => 'required|exists:users,id',
            'rol_asignado' => 'required|in:RESPONSABLE,APOYO',
        ];
    }
}
