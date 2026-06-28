<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarUsuarioRequest;
use App\Http\Requests\CrearUsuarioRequest;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function listado()
    {
        return response()->json(
            UserResource::collection(User::with('rol')->orderBy('name')->get())
        );
    }

    // Listar los roles disponibles (para el desplegable del formulario).
    public function roles()
    {
        return response()->json(Rol::orderBy('nombre_rol')->get());
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
        ]);

        return response()->json(new UserResource($user->load('rol')), 201);
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

        return response()->json(new UserResource($usuario->load('rol')));
    }

    // Eliminar (borrado lógico) un usuario.
    public function eliminar(Request $request, User $usuario)
    {
        if ($usuario->id === $request->user()->id) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta'], 422);
        }

        $usuario->tokens()->delete();
        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado']);
    }
}
