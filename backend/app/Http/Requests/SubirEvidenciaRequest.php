<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubirEvidenciaRequest extends FormRequest
{
    // Quién puede subir evidencias lo decide la IncidenciaPolicy en el controller.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fotos' => 'required|array',
            'fotos.*' => 'image|mimes:jpg,jpeg,png|max:2048',
            'tipo_evidencia' => 'nullable|in:REPORTE,RESOLUCION',
        ];
    }
}
