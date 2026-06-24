<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarUsuarioRequest extends FormRequest
{
    // Ruta protegida por el middleware 'admin'.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // El email debe ser único salvo el del propio usuario que se edita.
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$this->route('usuario')->id,
            'id_rol' => 'required|exists:roles,id_rol',
            'password' => 'nullable|string|min:8',
        ];
    }
}
