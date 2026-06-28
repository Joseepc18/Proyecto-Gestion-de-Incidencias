<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CrearUsuarioRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->withoutTrashed()],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'id_rol' => 'required|exists:roles,id_rol',
        ];
    }
}
