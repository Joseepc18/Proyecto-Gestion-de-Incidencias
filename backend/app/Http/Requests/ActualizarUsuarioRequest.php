<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ActualizarUsuarioRequest extends FormRequest
{
    // Ruta ya protegida por 'permiso:usuarios.administrar'; la Policy cubre el permiso + la regla de negocio (no tocar normales).
    public function authorize(): bool
    {
        Gate::authorize('actualizar', $this->route('usuario'));

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
                Rule::unique('users', 'email')->ignore($this->route('usuario')->id)->withoutTrashed(),
            ],
            // No se puede degradar a un usuario al rol normal desde la gestión.
            'id_rol' => ['required', Rule::exists('roles', 'id_rol')->whereIn('nombre_rol', ['tecnico', 'admin'])],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }
}
