<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearComentarioRequest extends FormRequest
{
    // Quién puede comentar (chat) lo decide la IncidenciaPolicy en el controller.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comentario' => 'required|string|min:1|max:1000',
        ];
    }
}
