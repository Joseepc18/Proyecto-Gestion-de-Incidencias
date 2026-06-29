<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ActualizarUsuarioRequest extends FormRequest
{
    // Ruta protegida por el middleware 'admin'; además, los usuarios normales no se editan aquí (solo en Mi perfil).
    public function authorize(): bool
    {
        $usuario = $this->route('usuario');

        return $usuario && ! $usuario->esNormal();
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
                Rule::unique('users', 'email')->ignore($this->route('usuario')->id)->withoutTrashed(),
            ],
            // No se puede degradar a un usuario al rol normal desde la gestión.
            'id_rol' => ['required', Rule::exists('roles', 'id_rol')->whereIn('nombre_rol', ['tecnico', 'admin'])],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }
}
