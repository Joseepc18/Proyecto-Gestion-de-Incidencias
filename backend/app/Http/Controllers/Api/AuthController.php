<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AlmacenamientoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarPerfilRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\BitacoraError;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            'password' => $request->password,
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

        // No borramos tokens previos: permite varias sesiones a la vez (laptop, celular).
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

    // El usuario edita su propio perfil (nombre, correo y, opcionalmente, contraseña y foto).
    public function actualizarPerfil(ActualizarPerfilRequest $request)
    {
        $datos = $request->validated();
        $user = $request->user();

        $user->name = $datos['name'];
        $user->email = $datos['email'];

        // Solo cambia la contraseña si se envió una nueva.
        if (! empty($datos['password'])) {
            $user->password = $datos['password'];
        }

        try {
            // Foto nueva: la guarda y borra la anterior; o "quitar_foto" la elimina sin reemplazo.
            if ($request->hasFile('foto')) {
                $ruta = $request->file('foto')->store('perfiles', 'public');
                if ($ruta === false) {
                    throw new AlmacenamientoException('No se pudo guardar la foto de perfil en el disco');
                }
                $this->borrarFotoAnterior($user);
                $user->foto_perfil = $ruta;
            } elseif (! empty($datos['quitar_foto'])) {
                $this->borrarFotoAnterior($user);
                $user->foto_perfil = null;
            }
        } catch (AlmacenamientoException $e) {
            BitacoraError::create([
                'id_usuario' => $user->id,
                'tipo_error' => 'ARCHIVO',
                'descripcion_error' => 'AuthController@actualizarPerfil: '.$e->getMessage(),
            ]);

            return response()->json(['message' => 'No se pudo guardar la foto. Intenta de nuevo.'], 500);
        }

        $user->save();

        return response()->json($user->load('rol'));
    }

    // Borra del disco la foto de perfil actual (si la hay) para no dejar archivos huérfanos.
    private function borrarFotoAnterior(User $user): void
    {
        if ($user->foto_perfil) {
            Storage::disk('public')->delete($user->foto_perfil);
        }
    }

    // Paso 1 del login con Google: redirige a Google. stateless() = API por token, sin sesión.
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // Paso 2: Google vuelve aquí. Busca el usuario por email o lo crea (rol 'normal') y lo loguea.
    public function handleGoogleCallback()
    {
        $frontend = rtrim(config('services.frontend_url'), '/');

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $rolNormal = Rol::where('nombre_rol', 'normal')->first();

            // Si ya existía (registro normal o Google previo) lo reutiliza por su email.
            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Usuario Google',
                    // Sin contraseña real: clave aleatoria para cumplir el NOT NULL de la columna.
                    'password' => Str::random(32),
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
                'descripcion_error' => 'AuthController@handleGoogleCallback: '.get_class($e).' (detalles omitidos por seguridad)',
            ]);

            return redirect($frontend.'/login/login.html?error=google');
        }
    }
}
