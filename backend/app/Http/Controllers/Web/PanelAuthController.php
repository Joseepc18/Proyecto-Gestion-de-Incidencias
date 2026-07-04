<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PanelAuthController extends Controller
{
    // Formulario de acceso al panel Blade. A diferencia de la API (token Bearer), aquí sí hay sesión.
    public function mostrarLogin()
    {
        if (Auth::check()) {
            return redirect('/panel');
        }

        return view('panel.login');
    }

    // Inicia sesión con el guard web (sesión + cookie), separado del guard sanctum de la API.
    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credenciales, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ]);
        }

        // Un usuario suspendido (soft delete) no debe entrar aunque la contraseña sea válida.
        if (Auth::user()->trashed()) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Esta cuenta está suspendida.',
            ]);
        }

        // Por ahora el panel Blade es solo administrativo: exige el permiso de gestión de usuarios.
        if (! Auth::user()->tienePermiso('usuarios.administrar')) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'Tu cuenta no tiene acceso al panel.',
            ]);
        }

        // Previene fijación de sesión: nueva id de sesión tras autenticar.
        $request->session()->regenerate();

        return redirect()->intended('/panel');
    }

    // Cierra la sesión web e invalida la cookie; los tokens Sanctum de la API no se ven afectados.
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/panel/login');
    }
}
