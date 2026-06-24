<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CambiarEstadoRequest extends FormRequest
{
    // Quién puede cambiar el estado lo decide la IncidenciaPolicy en el controller.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado_incidencia' => 'required|in:PENDIENTE,EN_PROCESO,RESUELTO',
        ];
    }
}
