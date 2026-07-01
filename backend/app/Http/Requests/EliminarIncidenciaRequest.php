<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class EliminarIncidenciaRequest extends FormRequest
{
    // Autoriza antes de validar: admin siempre; el autor solo si PENDIENTE.
    public function authorize(): bool
    {
        Gate::authorize('eliminar', $this->route('incidencia'));

        return true;
    }

    public function rules(): array
    {
        // El motivo solo aplica cuando alguien más borra la incidencia de otro (el admin):
        // el dueño borrando la suya propia no necesita explicarse a sí mismo.
        $incidencia = $this->route('incidencia');
        $esPropia = $incidencia && $incidencia->id_usuario === $this->user()?->id;

        return [
            'motivo' => $esPropia ? ['nullable', 'string', 'max:500'] : ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
