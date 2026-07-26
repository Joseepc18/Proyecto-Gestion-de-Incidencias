<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class LiberarReclamoRequest extends FormRequest
{
    // Gate propio, NO el de reclamar: reclamar se niega en archivadas y eso dejaba el candado puesto sin forma de soltarlo.
    public function authorize(): bool
    {
        Gate::authorize('liberar', $this->route('incidencia'));

        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
