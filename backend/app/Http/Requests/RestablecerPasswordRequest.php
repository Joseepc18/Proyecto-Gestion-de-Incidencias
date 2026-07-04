<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RestablecerPasswordRequest extends FormRequest
{
    // Confirmar el reset es público: el token del correo es la credencial.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string',
            'email' => 'required|string|email',
            // Mismas reglas que el registro para mantener la política de contraseñas.
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }
}
