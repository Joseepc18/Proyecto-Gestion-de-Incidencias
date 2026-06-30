<?php

namespace App\Http\Requests;

use App\Enums\TipoEvidencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

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
        return [
            'fotos' => 'required|array',
            'fotos.*' => 'image|mimes:jpg,jpeg,png|max:3072',
            'tipo_evidencia' => ['nullable', Rule::enum(TipoEvidencia::class)],
        ];
    }
}
