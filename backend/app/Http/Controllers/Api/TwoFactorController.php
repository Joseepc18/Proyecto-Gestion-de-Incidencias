<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmarDosFactorRequest;
use App\Http\Requests\DesactivarDosFactorRequest;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;

// Backend stateless del 2FA (TOTP). Reusa las actions de Fortify pero el flujo lo controla nuestra API por token.
class TwoFactorController extends Controller
{
    // Paso 1: genera el secreto (sin confirmar) y devuelve el QR + los recovery codes para que el usuario los guarde.
    public function enable(Request $request, EnableTwoFactorAuthentication $enable)
    {
        $user = $request->user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return response()->json(['message' => 'La verificación en dos pasos ya está activa.'], 409);
        }

        // force: regenera secreto y recovery codes si el usuario había empezado un setup previo sin confirmar.
        $enable($user, force: true);

        return response()->json([
            'svg' => $user->twoFactorQrCodeSvg(),
            'otpauth_url' => $user->twoFactorQrCodeUrl(),
            'recovery_codes' => $user->recoveryCodes(),
        ]);
    }

    // Paso 2: el usuario confirma con el primer código del authenticator; recién ahí queda activo (two_factor_confirmed_at).
    public function confirm(ConfirmarDosFactorRequest $request, ConfirmTwoFactorAuthentication $confirm)
    {
        $user = $request->user();

        if (! $user->two_factor_secret) {
            return response()->json(['message' => 'Primero genera el código QR con /2fa/enable.'], 422);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return response()->json(['message' => 'La verificación en dos pasos ya está confirmada.'], 409);
        }

        // Lanza ValidationException (422) si el código no es válido.
        $confirm($user, $request->code);

        return response()->json(['message' => 'Verificación en dos pasos activada.']);
    }

    // Desactiva el 2FA. Exige un código válido para que un token robado no pueda apagarlo sin el authenticator.
    public function disable(DesactivarDosFactorRequest $request, DisableTwoFactorAuthentication $disable)
    {
        $user = $request->user();

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return response()->json(['message' => 'No tienes la verificación en dos pasos activa.'], 409);
        }

        if (! $user->verificarCodigoDosFactor($request->code)) {
            return response()->json(['message' => 'El código de verificación es incorrecto.'], 422);
        }

        $disable($user);

        return response()->json(['message' => 'Verificación en dos pasos desactivada.']);
    }
}
