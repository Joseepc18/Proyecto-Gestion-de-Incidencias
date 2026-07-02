<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CrearComentarioRequest extends FormRequest
{
    // Autoriza antes de validar: reportador, admin o técnico responsable, y la incidencia
    // NO está resuelta (en RESUELTO el chat es solo de lectura).
    public function authorize(): bool
    {
        Gate::authorize('comentar', $this->route('incidencia'));

        return true;
    }

    public function rules(): array
    {
        return [
            'comentario' => 'required|string|min:1|max:1000',
        ];
    }
}
