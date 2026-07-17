<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ReiniciarDosFactorRequest extends FormRequest
{
    // Ruta ya protegida por 'permiso:usuarios.administrar'; la Policy exige super_admin y prohíbe hacerlo sobre uno mismo.
    public function authorize(): bool
    {
        Gate::authorize('reiniciarDosFactor', $this->route('usuario'));

        return true;
    }

    // El actor no aporta código (justo el punto es que la víctima perdió su dispositivo); no hay nada que validar.
    public function rules(): array
    {
        return [];
    }
}
