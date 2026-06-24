<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CambiarEstadoRequest extends FormRequest
{
    // Autoriza antes de validar: solo admin o técnico asignado.
    public function authorize(): bool
    {
        Gate::authorize('cambiarEstado', $this->route('incidencia'));

        return true;
    }

    public function rules(): array
    {
        return [
            'estado_incidencia' => 'required|in:PENDIENTE,EN_PROCESO,RESUELTO',
        ];
    }
}
