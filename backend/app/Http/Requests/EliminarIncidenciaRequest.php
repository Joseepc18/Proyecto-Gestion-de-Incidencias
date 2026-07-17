<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class EliminarIncidenciaRequest extends FormRequest
{
    // Autoriza antes de validar: solo se elimina en estado PENDIENTE (para todos los roles).
    public function authorize(): bool
    {
        Gate::authorize('eliminar', $this->route('incidencia'));

        return true;
    }

    public function rules(): array
    {
        // El motivo solo aplica cuando alguien más borra la incidencia de otro (el admin).
        $incidencia = $this->route('incidencia');
        $esPropia = $incidencia && $incidencia->id_usuario === $this->user()?->id;

        return [
            'motivo' => $esPropia ? ['nullable', 'string', 'max:500'] : ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
