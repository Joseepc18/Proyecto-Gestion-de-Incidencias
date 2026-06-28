<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ActualizarPerfilRequest extends FormRequest
{
    // Cada usuario edita su propio perfil (ya está autenticado).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id)->withoutTrashed(),
            ],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'quitar_foto' => 'nullable|boolean',
        ];
    }
}
