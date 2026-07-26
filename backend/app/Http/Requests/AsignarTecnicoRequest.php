<?php

namespace App\Http\Requests;

use App\Enums\RolAsignacion;
use App\Models\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsignarTecnicoRequest extends FormRequest
{
    // La autorización (solo admin) la cubre el middleware 'permiso:incidencias.gestionar' de la ruta.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Solo técnicos, y nunca el que reportó la incidencia (coincide con el SP asignar_tecnico).
            'id_usuario' => ['required', Rule::notIn([$this->route('incidencia')->id_usuario]), Rule::exists('users', 'id')
                ->whereNull('deleted_at')
                ->where('id_rol', Rol::where('nombre_rol', 'tecnico')->value('id_rol'))],
            'rol_asignado' => ['required', Rule::enum(RolAsignacion::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'id_usuario.not_in' => 'Quien reportó la incidencia no puede atenderla.',
        ];
    }
}
