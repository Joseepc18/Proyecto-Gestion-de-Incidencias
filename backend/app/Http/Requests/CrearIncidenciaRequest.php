<?php

namespace App\Http\Requests;

use App\Enums\EstadoIncidencia;
use App\Enums\PrioridadIncidencia;
use App\Models\Incidencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CrearIncidenciaRequest extends FormRequest
{
    // Reportar depende del permiso incidencias.crear, configurable por rol desde el panel de permisos.
    public function authorize(): bool
    {
        Gate::authorize('crear', Incidencia::class);

        return true;
    }

    public function rules(): array
    {
        $reglas = [
            'nombre_incidencia' => 'required|string|min:5|max:100',
            'descripcion_incidencia' => 'nullable|string|max:500',
            'direccion_incidencia' => 'nullable|string|max:500',
            'latitud_incidencia' => 'required|numeric|between:-5.5,1.8',
            'longitud_incidencia' => 'required|numeric|between:-82.0,-74.5',
            'id_ciudad' => 'required|exists:ciudades,id_ciudad',
            'id_subtipo_incidencia' => 'required|exists:subtipos_incidencia,id_subtipo_incidencia',
            'fotos' => 'nullable|array|max:3',
            'fotos.*' => 'image|mimes:jpg,jpeg,png|max:3072',
        ];

        // Nacer con prioridad/estado es triaje: lo decide el permiso de gestión, no el nombre del rol
        // (con esAdmin() el super_admin view-only también podía, saltándose el triaje).
        if ($this->user()?->tienePermiso('incidencias.gestionar')) {
            $reglas['prioridad_incidencia'] = ['nullable', Rule::enum(PrioridadIncidencia::class)];
            // No se permite crear en RESUELTO: resolver pasa por cambiarEstado (corre el SP, setea fecha e historial).
            $reglas['estado_incidencia'] = ['nullable', 'in:'.implode(',', [EstadoIncidencia::Pendiente->value, EstadoIncidencia::EnProceso->value])];
        }

        return $reglas;
    }
}
