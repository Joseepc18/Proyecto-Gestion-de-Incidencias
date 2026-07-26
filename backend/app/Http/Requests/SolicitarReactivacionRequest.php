<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitarReactivacionRequest extends FormRequest
{
    // Pública: una cuenta suspendida no puede iniciar sesión, así que no hay a quién autorizar.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|string|email',
            // max:500 es el ancho de motivo_solicitud en la BD.
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
