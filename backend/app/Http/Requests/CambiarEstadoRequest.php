<?php

namespace App\Http\Requests;

use App\Enums\EstadoIncidencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

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
            'estado_incidencia' => ['required', Rule::enum(EstadoIncidencia::class)],
        ];
    }
}
