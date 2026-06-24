<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarPerfilRequest extends FormRequest
{
    // Cada usuario edita su propio perfil (ya está autenticado).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // El email debe ser único salvo el del propio usuario.
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$this->user()->id,
            'password' => 'nullable|string|min:8',
        ];
    }
}
