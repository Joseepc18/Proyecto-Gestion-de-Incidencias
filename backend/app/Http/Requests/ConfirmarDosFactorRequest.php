<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmarDosFactorRequest extends FormRequest
{
    // Ruta protegida (auth:sanctum): el dueño confirma su propio 2FA.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string',
        ];
    }
}
