<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SubirEvidenciaRequest extends FormRequest
{
    // Autoriza antes de validar: autor (REPORTE) o técnico responsable (RESOLUCION).
    public function authorize(): bool
    {
        Gate::authorize('subirEvidencia', $this->route('incidencia'));

        return true;
    }

    public function rules(): array
    {
        // El tipo_evidencia ya NO se valida ni se usa del cliente: lo deriva el controller según el rol.
        return [
            'fotos' => 'required|array',
            'fotos.*' => 'image|mimes:jpg,jpeg,png|max:3072',
        ];
    }
}
