<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class RechazarReaperturaRequest extends FormRequest
{
    // Autoriza antes de validar: solo admin y solo si hay una solicitud de reapertura pendiente.
    public function authorize(): bool
    {
        Gate::authorize('rechazarReapertura', $this->route('incidencia'));

        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
