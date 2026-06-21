<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Listar todos los usuarios con su rol.
    public function listado()
    {
        return response()->json(
            User::with('rol')->orderBy('name')->get()
        );
    }

    // Listar los roles disponibles (para el desplegable del formulario).
    public function roles()
    {
        return response()->json(Rol::orderBy('nombre_rol')->get());
    }

    // Crear un usuario con su rol (el admin crea técnicos, otros admins, etc.).
    public function crear(Request $request)
    {
        $datos = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'id_rol' => 'required|exists:roles,id_rol',
        ]);

        $user = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => Hash::make($datos['password']),
            'id_rol' => $datos['id_rol'],
        ]);

        return response()->json($user->load('rol'), 201);
    }

    // Actualizar un usuario (nombre, correo, rol y, opcionalmente, contraseña).
    public function actualizar(Request $request, User $usuario)
    {
        $datos = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$usuario->id,
            'id_rol' => 'required|exists:roles,id_rol',
            'password' => 'nullable|string|min:8',
        ]);

        $usuario->name = $datos['name'];
        $usuario->email = $datos['email'];
        $usuario->id_rol = $datos['id_rol'];

        // Solo cambia la contraseña si se envió una nueva.
        if (! empty($datos['password'])) {
            $usuario->password = Hash::make($datos['password']);
        }

        $usuario->save();

        return response()->json($usuario->load('rol'));
    }

    // Eliminar (borrado lógico) un usuario.
    public function eliminar(Request $request, User $usuario)
    {
        // El admin no puede eliminar su propia cuenta.
        if ($usuario->id === $request->user()->id) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta'], 422);
        }

        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado']);
    }
}
