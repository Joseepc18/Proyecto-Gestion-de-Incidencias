<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CrearComentarioRequest extends FormRequest
{
    // Autoriza antes de validar: reportador, admin o técnico responsable, y no está RESUELTO (solo lectura).
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
