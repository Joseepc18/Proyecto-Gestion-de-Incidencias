<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DosFactorChallengeRequest extends FormRequest
{
    // Ruta pública: la credencial es el challenge_token efímero emitido por el login + el código del authenticator.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'challenge_token' => 'required|string',
            'code' => 'required|string',
        ];
    }
}
