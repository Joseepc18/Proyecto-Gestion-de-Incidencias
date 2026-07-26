<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
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
        $user = $this->user();
        // Cambios sensibles = tocar correo o contraseña; solo esos exigen re-autenticación.
        $cambiaEmail = $this->input('email') !== $user->email;
        $sensible = $cambiaEmail || $this->filled('password');
        $tieneDosFactor = $user->hasEnabledTwoFactorAuthentication();

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                // Sin withoutTrashed: cambiar de correo tampoco puede apropiarse del de una cuenta suspendida.
                Rule::unique('users', 'email')->ignore($user->id),
                // No permitir pedir un correo que otro usuario ya tiene pendiente de confirmar.
                Rule::unique('users', 'email_pendiente')->ignore($user->id),
            ],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'quitar_foto' => 'nullable|boolean',
            // Re-autenticación: la contraseña actual solo se exige al cambiar correo o contraseña.
            'current_password' => [
                Rule::requiredIf($sensible),
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($user) {
                    if (filled($value) && ! Hash::check($value, $user->password)) {
                        $fail('La contraseña actual no es correcta.');
                    }
                },
            ],
            // Segundo factor: si el usuario tiene 2FA activo, el cambio sensible pide además un código válido.
            'two_factor_code' => [
                Rule::requiredIf($sensible && $tieneDosFactor),
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($user) {
                    if (filled($value) && ! $user->verificarCodigoDosFactor($value)) {
                        $fail('El código de verificación es incorrecto.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Ingresa tu contraseña actual para confirmar el cambio.',
            'two_factor_code.required' => 'Ingresa un código de verificación en dos pasos para confirmar el cambio.',
        ];
    }
}
