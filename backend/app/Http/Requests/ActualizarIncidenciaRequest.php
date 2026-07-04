<?php

namespace App\Http\Requests;

use App\Enums\PrioridadIncidencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ActualizarIncidenciaRequest extends FormRequest
{
    // Autoriza antes de validar: admin/técnico asignado siempre; el autor solo si PENDIENTE.
    public function authorize(): bool
    {
        Gate::authorize('actualizar', $this->route('incidencia'));

        return true;
    }

    public function rules(): array
    {
        $reglas = [
            'nombre_incidencia' => 'sometimes|string|min:5|max:100',
            'descripcion_incidencia' => 'sometimes|nullable|string|max:500',
            'direccion_incidencia' => 'sometimes|nullable|string|max:500',
            // required_with: si se manda una coordenada, la otra tiene que venir igual (no quedan descoordinadas)
            'latitud_incidencia' => 'required_with:longitud_incidencia|numeric|between:-5.5,1.8',
            'longitud_incidencia' => 'required_with:latitud_incidencia|numeric|between:-82.0,-74.5',
            'id_ciudad' => 'sometimes|exists:ciudades,id_ciudad',
            'id_subtipo_incidencia' => 'sometimes|exists:subtipos_incidencia,id_subtipo_incidencia',
        ];

        if ($this->user()?->esAdmin()) {
            $reglas['prioridad_incidencia'] = ['sometimes', Rule::enum(PrioridadIncidencia::class)];
        }

        return $reglas;
    }
}
