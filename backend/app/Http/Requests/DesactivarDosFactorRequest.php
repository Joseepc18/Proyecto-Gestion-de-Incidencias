<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DesactivarDosFactorRequest extends FormRequest
{
    // Ruta protegida (auth:sanctum): el dueño desactiva su propio 2FA.
    public function authorize(): bool
    {
        return true;
    }

    // Exigir un código válido para desactivar impide que un token robado (sin el authenticator) apague el 2FA.
    public function rules(): array
    {
        return [
            'code' => 'required|string',
        ];
    }
}
