<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CancelarReaperturaRequest extends FormRequest
{
    // Gate propio, NO el de solicitar: solicitar exige RESUELTO y retirar debe poder hacerse igual.
    public function authorize(): bool
    {
        Gate::authorize('cancelarReapertura', $this->route('incidencia'));

        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
