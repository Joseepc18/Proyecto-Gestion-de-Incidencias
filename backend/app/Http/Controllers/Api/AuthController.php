<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarPerfilRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\BitacoraError;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // Registrar usuario, asignarle el rol 'normal' y devolver su token.
    public function register(RegisterRequest $request)
    {
        $rolNormal = Rol::where('nombre_rol', 'normal')->first();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'id_rol' => $rolNormal->id_rol,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }

    // Validar credenciales y devolver un token nuevo.
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        // No borramos los tokens previos: así el usuario puede mantener varias
        // sesiones abiertas a la vez (p. ej. laptop y celular) sin que un login
        // nuevo mate las sesiones anteriores.
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 200);
    }

    // Cerrar sesión: borra el token actual.
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    // Perfil del usuario autenticado (con su rol).
    public function me(Request $request)
    {
        return response()->json($request->user()->load('rol'));
    }

    // El usuario edita su propio perfil (nombre, correo y, opcionalmente, contraseña).
    public function actualizarPerfil(ActualizarPerfilRequest $request)
    {
        $datos = $request->validated();
        $user = $request->user();

        $user->name = $datos['name'];
        $user->email = $datos['email'];

        // Solo cambia la contraseña si se envió una nueva.
        if (! empty($datos['password'])) {
            $user->password = Hash::make($datos['password']);
        }

        $user->save();

        return response()->json($user->load('rol'));
    }

    // Paso 1 del login con Google: manda al usuario a la pantalla de Google.
    // stateless() = sin sesión de servidor (somos una API por token).
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // Paso 2: Google nos devuelve aquí. Buscamos el usuario por su correo;
    // si no existe lo creamos con rol 'normal', y lo mandamos al frontend ya logueado.
    public function handleGoogleCallback()
    {
        $frontend = rtrim(env('FRONTEND_URL', 'http://localhost'), '/');

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $rolNormal = Rol::where('nombre_rol', 'normal')->first();

            // Si ya existía (registro normal o Google previo) lo reutiliza por su email.
            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Usuario Google',
                    // Sin contraseña real: clave aleatoria para cumplir el NOT NULL de la columna.
                    'password' => Hash::make(Str::random(32)),
                    'id_rol' => $rolNormal->id_rol,
                ]
            );

            // Igual que en login: no borramos tokens previos (sesiones concurrentes).
            $token = $user->createToken('auth_token')->plainTextToken;

            // El token viaja en el fragmento (#) para que no quede en logs ni en el historial del servidor.
            return redirect($frontend.'/login/oauth.html#token='.$token);
        } catch (\Exception $e) {
            BitacoraError::create([
                'id_usuario' => null,
                'tipo_error' => 'AUTENTICACION',
                'descripcion_error' => 'AuthController@handleGoogleCallback: '.$e->getMessage(),
            ]);

            return redirect($frontend.'/login/login.html?error=google');
        }
    }
}
