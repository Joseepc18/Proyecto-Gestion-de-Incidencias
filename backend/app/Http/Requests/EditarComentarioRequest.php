<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class EditarComentarioRequest extends FormRequest
{
    // Autoriza antes de validar: solo el autor del comentario.
    public function authorize(): bool
    {
        Gate::authorize('actualizar', $this->route('comentario'));

        return true;
    }

    public function rules(): array
    {
        return [
            'comentario' => 'required|string|min:1|max:1000',
        ];
    }
}
