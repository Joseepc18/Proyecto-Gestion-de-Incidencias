<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ReclamarIncidenciaRequest extends FormRequest
{
    // Autoriza antes de validar: solo admin (la incidencia ya reclamada la valida el controller, 422).
    public function authorize(): bool
    {
        Gate::authorize('reclamar', $this->route('incidencia'));

        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
