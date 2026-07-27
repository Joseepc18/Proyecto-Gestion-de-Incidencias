<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoSolicitudReactivacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarUsuarioRequest;
use App\Http\Requests\CrearUsuarioRequest;
use App\Http\Requests\ReiniciarDosFactorRequest;
use App\Http\Resources\UserResource;
use App\Models\BitacoraError;
use App\Models\Rol;
use App\Models\User;
use App\Notifications\ReactivacionResueltaNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

class UserController extends Controller
{
    // Listado de usuarios con filtro de rol; oculta los suspendidos (soft delete) salvo ?rol=suspendido.
    public function listado(Request $request)
    {
        $query = User::with('rol')->orderBy('name');

        if ($request->filled('rol')) {
            if ($request->rol === 'suspendido') {
                // Solo aquí se carga la solicitud de reactivación: es la única vista donde hay suspendidos.
                $query->onlyTrashed()->with('solicitudReactivacionPendiente');
            } else {
                $query->conRol($request->rol);
            }
        }

        if ($request->filled('busqueda')) {
            $termino = '%'.$request->busqueda.'%';
            $query->where(function ($q) use ($termino) {
                $q->where('name', 'ilike', $termino)
                    ->orWhere('email', 'ilike', $termino);
            });
        }

        // through() envuelve cada usuario en UserResource sin alterar el shape de paginación que consume el frontend.
        return $query->paginate($this->perPage($request))
            ->through(fn ($usuario) => new UserResource($usuario));
    }

    // Restaurar un usuario suspendido (borrado lógico): lo reactiva y vuelve a ser visible.
    public function restaurar(Request $request, int $id)
    {
        $usuario = User::withTrashed()->findOrFail($id);

        if (! $usuario->trashed()) {
            return response()->json(['message' => 'El usuario no está suspendido'], 422);
        }

        $usuario->restore();

        // Restaurar ES la aprobación: si había una solicitud pendiente queda cerrada con quién la resolvió.
        $solicitud = $usuario->solicitudReactivacionPendiente()->first();
        $solicitud?->resolver(EstadoSolicitudReactivacion::Aprobada, $request->user());

        // Solo se avisa si el ciudadano lo había pedido; una restauración manual no la disparó él.
        if ($solicitud) {
            $this->notificarSinRomper(
                fn () => $usuario->notify(new ReactivacionResueltaNotification(true)),
                $request->user(),
                'UserController@restaurar'
            );
        }

        return response()->json([
            'message' => 'Usuario restaurado',
            'usuario' => new UserResource($usuario->load('rol')),
        ]);
    }

    // Rechaza la solicitud de reactivación: la cuenta sigue suspendida y el ciudadano puede volver a pedirlo.
    public function rechazarReactivacion(Request $request, int $id)
    {
        $usuario = User::withTrashed()->findOrFail($id);
        $solicitud = $usuario->solicitudReactivacionPendiente()->first();

        if (! $solicitud) {
            return response()->json(['message' => 'El usuario no tiene una solicitud de reactivación pendiente'], 422);
        }

        $solicitud->resolver(EstadoSolicitudReactivacion::Rechazada, $request->user());

        $this->notificarSinRomper(
            fn () => $usuario->notify(new ReactivacionResueltaNotification(false)),
            $request->user(),
            'UserController@rechazarReactivacion'
        );

        return response()->json(['message' => 'Solicitud de reactivación rechazada']);
    }

    // Listar los roles disponibles (para el desplegable del formulario).
    public function roles()
    {
        return Rol::orderBy('nombre_rol')->get();
    }

    // Crear un usuario con su rol (el admin crea técnicos, otros admins, etc.).
    public function crear(CrearUsuarioRequest $request)
    {
        $datos = $request->validated();

        $user = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            'id_rol' => $datos['id_rol'],
            // Lo crea un super_admin autenticado: la cuenta nace verificada (sin correo de verificación).
            'email_verified_at' => now(),
        ]);

        return (new UserResource($user->load('rol')))->response()->setStatusCode(201);
    }

    // Actualizar un usuario (nombre, correo, rol y, opcionalmente, contraseña).
    public function actualizar(ActualizarUsuarioRequest $request, User $usuario)
    {
        $datos = $request->validated();

        $usuario->name = $datos['name'];
        $usuario->email = $datos['email'];
        $usuario->id_rol = $datos['id_rol'];

        if (! empty($datos['password'])) {
            $usuario->password = $datos['password'];
        }

        $usuario->save();

        return new UserResource($usuario->load('rol'));
    }

    // Suspender (borrado lógico) un usuario: lo desactiva y revoca sus tokens.
    public function eliminar(Request $request, User $usuario)
    {
        if ($usuario->id === $request->user()->id) {
            return response()->json(['message' => 'No puedes suspender tu propia cuenta'], 422);
        }

        $usuario->tokens()->delete();
        $usuario->delete();

        return ['message' => 'Usuario suspendido'];
    }

    // Restablece el 2FA de otro usuario (recuperación de cuentas bloqueadas): solo super_admin, nunca sobre sí mismo (validado en el FormRequest).
    public function resetearDosFactor(ReiniciarDosFactorRequest $request, User $usuario, DisableTwoFactorAuthentication $disable)
    {
        if (! $usuario->two_factor_secret && ! $usuario->two_factor_confirmed_at) {
            return response()->json(['message' => 'El usuario no tiene la verificación en dos pasos activa.'], 409);
        }

        // Pone en null two_factor_secret/recovery_codes/confirmed_at; a diferencia de /2fa (propio) aquí NO se pide código.
        $disable($usuario);

        // Queda en la bitácora que ven los super_admin (única superficie de auditoría de la app).
        BitacoraError::registrar(
            $request->user(),
            'AUTENTICACION',
            'UserController@resetearDosFactor',
            "El super_admin {$request->user()->id} restableció el 2FA del usuario {$usuario->id}."
        );

        return response()->json(['message' => 'Se restableció el 2FA del usuario. Deberá configurarlo de nuevo al iniciar sesión.']);
    }

    // Sirve la foto de perfil del disco privado; protegida por firma (igual patrón que EvidenciaController@archivo).
    public function foto(User $usuario)
    {
        $disco = Storage::disk('perfiles');

        if (! $usuario->foto_perfil || ! $disco->exists($usuario->foto_perfil)) {
            abort(404);
        }

        return $disco->response($usuario->foto_perfil);
    }
}
