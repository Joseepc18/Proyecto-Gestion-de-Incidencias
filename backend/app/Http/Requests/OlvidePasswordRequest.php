<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OlvidePasswordRequest extends FormRequest
{
    // Solicitar el enlace es público.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|string|email',
        ];
    }
}
