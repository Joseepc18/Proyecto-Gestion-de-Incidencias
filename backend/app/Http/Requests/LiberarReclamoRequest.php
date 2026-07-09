<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class LiberarReclamoRequest extends FormRequest
{
    // Solo admin (mismo gate que reclamar); el detalle dueño/super_admin/vencido lo resuelve el controller.
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
