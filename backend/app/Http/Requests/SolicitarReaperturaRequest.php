<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SolicitarReaperturaRequest extends FormRequest
{
    // Autoriza antes de validar: solo el reportador y solo si ya está RESUELTO.
    public function authorize(): bool
    {
        Gate::authorize('solicitarReapertura', $this->route('incidencia'));

        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
