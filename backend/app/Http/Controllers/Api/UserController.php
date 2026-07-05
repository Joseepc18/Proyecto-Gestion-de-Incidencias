<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarUsuarioRequest;
use App\Http\Requests\CrearUsuarioRequest;
use App\Http\Resources\UserResource;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Listado de usuarios con filtro de rol; oculta los suspendidos (soft delete) salvo ?rol=suspendido.
    public function listado(Request $request)
    {
        $query = User::with('rol')->orderBy('name');

        if ($request->filled('rol')) {
            if ($request->rol === 'suspendido') {
                $query->onlyTrashed();
            } else {
                $query->conRol($request->rol);
            }
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

        return response()->json([
            'message' => 'Usuario restaurado',
            'usuario' => new UserResource($usuario->load('rol')),
        ]);
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
}
