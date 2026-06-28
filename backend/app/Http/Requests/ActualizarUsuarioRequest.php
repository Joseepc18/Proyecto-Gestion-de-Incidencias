<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ActualizarUsuarioRequest extends FormRequest
{
    // Ruta protegida por el middleware 'admin'.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('usuario')->id)->withoutTrashed(),
            ],
            'id_rol' => 'required|exists:roles,id_rol',
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }
}
