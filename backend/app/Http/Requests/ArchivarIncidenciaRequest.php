<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ArchivarIncidenciaRequest extends FormRequest
{
    // Autoriza antes de validar: solo el admin que reclamó esta incidencia (el estado RESUELTO lo valida el controller).
    public function authorize(): bool
    {
        Gate::authorize('archivar', $this->route('incidencia'));

        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
