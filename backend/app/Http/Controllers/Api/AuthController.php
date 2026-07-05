<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AlmacenamientoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarPerfilRequest;
use App\Http\Requests\DosFactorChallengeRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\OlvidePasswordRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RestablecerPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\BitacoraError;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // Registrar usuario, asignarle el rol 'normal' y devolver su token.
    public function register(RegisterRequest $request)
    {
        $rolNormal = Rol::where('nombre_rol', Rol::NORMAL)->firstOrFail();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'id_rol' => $rolNormal->id_rol,
        ]);

        // Registro por email/clave: enviamos el correo de verificación (llamada explícita, no dependemos del listener).
        $user->sendEmailVerificationNotification();

        return $this->respuestaConToken($user, 201);
    }

    // Validar credenciales y devolver un token nuevo.
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        // Con 2FA activo no se emite el token todavía: se pide el segundo factor (paso /2fa/challenge).
        if ($user->hasEnabledTwoFactorAuthentication()) {
            return $this->retoDosFactor($user);
        }

        return $this->respuestaConToken($user);
    }

    // Segundo factor: valida el challenge_token efímero + el código y recién ahí emite el token de sesión.
    public function dosFactorChallenge(DosFactorChallengeRequest $request)
    {
        $clave = $this->claveReto($request->challenge_token);
        $idUsuario = Cache::get($clave);

        if (! $idUsuario) {
            return response()->json(['message' => 'El proceso de verificación expiró. Inicia sesión de nuevo.'], 422);
        }

        $user = User::find($idUsuario);
        if (! $user || ! $user->verificarCodigoDosFactor($request->code)) {
            return response()->json(['message' => 'El código de verificación es incorrecto.'], 422);
        }

        // El reto es de un solo uso: se consume al validarlo.
        Cache::forget($clave);

        return $this->respuestaConToken($user);
    }

    // Emite un token de sesión y arma la respuesta estándar (usado por register, login y el reto 2FA).
    private function respuestaConToken(User $user, int $status = 200)
    {
        $token = $user->createToken('auth_token')->plainTextToken;

        // El dueño ve su propio email en la respuesta.
        Auth::setUser($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            // Cargamos el rol para que el front tenga el rol sin re-pedir /user.
            'user' => new UserResource($user->load('rol.permisos')),
        ], $status);
    }

    // Genera un challenge_token efímero (5 min, en caché) que autoriza a pedir el segundo factor sin reenviar la contraseña.
    private function retoDosFactor(User $user)
    {
        $challenge = Str::random(64);
        Cache::put($this->claveReto($challenge), $user->id, now()->addMinutes(5));

        return response()->json([
            'two_factor' => true,
            'challenge_token' => $challenge,
        ]);
    }

    // Guardamos el hash del challenge (no el valor crudo) para que un volcado de caché no filtre tokens usables.
    private function claveReto(string $challenge): string
    {
        return '2fa:challenge:'.hash('sha256', $challenge);
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
        return new UserResource($request->user()->load('rol.permisos'));
    }

    // El usuario edita su propio perfil (nombre, correo y, opcionalmente, contraseña y foto).
    public function actualizarPerfil(ActualizarPerfilRequest $request)
    {
        $datos = $request->validated();
        $user = $request->user();

        $user->name = $datos['name'];
        $user->email = $datos['email'];

        if (! empty($datos['password'])) {
            $user->password = $datos['password'];
        }

        try {
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
            BitacoraError::registrar($user, 'ARCHIVO', 'AuthController@actualizarPerfil', $e->getMessage());

            return response()->json(['message' => 'No se pudo guardar la foto. Intenta de nuevo.'], 500);
        }

        $user->save();

        return new UserResource($user->load('rol.permisos'));
    }

    // Borra del disco la foto de perfil actual (si la hay) para no dejar archivos huérfanos.
    private function borrarFotoAnterior(User $user): void
    {
        if ($user->foto_perfil) {
            Storage::disk('public')->delete($user->foto_perfil);
        }
    }

    // Solicita el enlace de restablecimiento. Responde SIEMPRE 200 genérico para no revelar si el correo existe (evita enumeración).
    public function olvidePassword(OlvidePasswordRequest $request)
    {
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña.',
        ]);
    }

    // Confirma el reset con el token del correo y guarda la nueva contraseña.
    public function restablecerPassword(RestablecerPasswordRequest $request)
    {
        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                // El cast 'hashed' del modelo hashea la contraseña al asignarla.
                $user->forceFill(['password' => $password])->save();
                // Cambiar la clave cierra las sesiones vivas (tokens Sanctum).
                $user->tokens()->delete();
            }
        );

        if ($estado === Password::PasswordReset) {
            return response()->json([
                'message' => 'Tu contraseña fue restablecida. Ya puedes iniciar sesión.',
            ]);
        }

        $mensajes = [
            Password::INVALID_TOKEN => 'El enlace de restablecimiento no es válido o ya expiró.',
            Password::INVALID_USER => 'No encontramos una cuenta con ese correo.',
            Password::RESET_THROTTLED => 'Espera un momento antes de solicitar otro enlace.',
        ];

        return response()->json([
            'message' => $mensajes[$estado] ?? 'No se pudo restablecer la contraseña.',
        ], 422);
    }

    // Enlace firmado del correo (navegación del navegador): valida la firma, marca verificado y redirige al frontend.
    public function verificarEmail(Request $request, int $id, string $hash)
    {
        $frontend = rtrim(config('services.frontend_url'), '/');

        // Validamos la firma (relativa) a mano para poder redirigir con un mensaje en vez de soltar un 403 crudo.
        if (! $request->hasValidSignature(absolute: false)) {
            return redirect($frontend.'/login/login.html?error=verificacion');
        }

        $user = User::find($id);
        if (! $user || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect($frontend.'/login/login.html?error=verificacion');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect($frontend.'/login/login.html?verificado=1');
    }

    // Reenvía el correo de verificación al usuario autenticado.
    public function reenviarVerificacion(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Tu correo ya está verificado.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Te reenviamos el correo de verificación.']);
    }

    // Paso 1 del login con Google: redirige a Google. stateless() = API por token, sin sesión.
    // stateless() apaga el 'state' de Socialite (que vive en sesión); lo reponemos a mano vía cookie para conservar la protección CSRF del flujo OAuth.
    public function redirectToGoogle(Request $request)
    {
        $state = Str::random(40);

        // Cookie efímera (5 min), httpOnly y SameSite=Lax; secure solo si la conexión ya es HTTPS (permite el dev local por http).
        $cookie = Cookie::make('oauth_state', $state, 5, null, null, $request->isSecure(), true, false, 'lax');

        return Socialite::driver('google')->stateless()->with(['state' => $state])->redirect()->withCookie($cookie);
    }

    // Paso 2: Google vuelve aquí. Busca el usuario por email o lo crea (rol 'normal') y lo loguea.
    public function handleGoogleCallback(Request $request)
    {
        $frontend = rtrim(config('services.frontend_url'), '/');

        // Al salir siempre limpiamos la cookie del state (de un solo uso).
        $olvidarState = Cookie::forget('oauth_state');

        // Anti-CSRF: el 'state' devuelto por Google debe coincidir con el de la cookie que fijamos al redirigir.
        $stateEsperado = $request->cookie('oauth_state');
        $stateRecibido = $request->query('state');
        if (! $stateEsperado || ! $stateRecibido || ! hash_equals($stateEsperado, (string) $stateRecibido)) {
            return redirect($frontend.'/login/login.html?error=google_state')->withCookie($olvidarState);
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // (a) Solo aceptamos cuentas con el email verificado por Google.
            $emailVerificado = $googleUser->user['email_verified'] ?? $googleUser->user['verified_email'] ?? false;
            if (! $emailVerificado) {
                return redirect($frontend.'/login/login.html?error=google_email')->withCookie($olvidarState);
            }

            // (b) Si ese email ya es de un admin o técnico, no permitimos Google (evita entrar como cuenta privilegiada por Gmail).
            $existente = User::where('email', $googleUser->getEmail())->first();
            if ($existente && ($existente->esAdmin() || $existente->esTecnico())) {
                return redirect($frontend.'/login/login.html?error=google_privilegiado')->withCookie($olvidarState);
            }

            $rolNormal = Rol::where('nombre_rol', Rol::NORMAL)->firstOrFail();

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Usuario Google',
                    'password' => Str::random(32),
                    'id_rol' => $rolNormal->id_rol,
                    // Google ya verificó el correo (validado arriba): la cuenta nace verificada, sin correo extra.
                    'email_verified_at' => now(),
                ]
            );

            $token = $user->createToken('auth_token')->plainTextToken;

            // El frontend (F2) lee &nuevo=1 para mostrar el toast de bienvenida solo en el primer login.
            $url = $frontend.'/login/oauth.html#token='.$token;
            if ($user->wasRecentlyCreated) {
                $url .= '&nuevo=1';
            }

            return redirect($url)->withCookie($olvidarState);
        } catch (\Exception $e) {
            BitacoraError::registrar(null, 'AUTENTICACION', 'AuthController@handleGoogleCallback', get_class($e).' (detalles omitidos por seguridad)');

            return redirect($frontend.'/login/login.html?error=google')->withCookie($olvidarState);
        }
    }
}
