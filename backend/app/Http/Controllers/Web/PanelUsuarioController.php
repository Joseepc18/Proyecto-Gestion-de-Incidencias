<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarUsuarioRequest;
use App\Http\Requests\CrearUsuarioRequest;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\Request;

class PanelUsuarioController extends Controller
{
    // Listado con filtro de rol; los suspendidos (soft delete) solo aparecen con ?rol=suspendido.
    public function index(Request $request)
    {
        $filtro = $request->query('rol', '');

        $query = User::with('rol')->orderBy('name');

        if ($filtro === 'suspendido') {
            $query->onlyTrashed();
        } elseif ($filtro !== '') {
            $query->conRol($filtro);
        }

        return view('panel.usuarios.index', [
            'usuarios' => $query->paginate(10)->withQueryString(),
            'filtro' => $filtro,
        ]);
    }

    public function crear()
    {
        return view('panel.usuarios.form', [
            'usuario' => null,
            'roles' => $this->rolesAsignables(),
        ]);
    }

    public function store(CrearUsuarioRequest $request)
    {
        $datos = $request->validated();

        User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            'id_rol' => $datos['id_rol'],
            // La crea un super_admin autenticado: nace verificada, sin correo de verificación.
            'email_verified_at' => now(),
        ]);

        return redirect()->route('panel.usuarios')->with('exito', 'Usuario creado');
    }

    public function editar(User $usuario)
    {
        // Los usuarios normales solo se editan en "Mi perfil", no desde la gestión.
        abort_if($usuario->esNormal(), 403);

        return view('panel.usuarios.form', [
            'usuario' => $usuario,
            'roles' => $this->rolesAsignables(),
        ]);
    }

    public function update(ActualizarUsuarioRequest $request, User $usuario)
    {
        $datos = $request->validated();

        $usuario->name = $datos['name'];
        $usuario->email = $datos['email'];
        $usuario->id_rol = $datos['id_rol'];

        if (! empty($datos['password'])) {
            $usuario->password = $datos['password'];
        }

        $usuario->save();

        return redirect()->route('panel.usuarios')->with('exito', 'Usuario actualizado');
    }

    // Suspender (borrado lógico): desactiva la cuenta y revoca sus tokens de API.
    public function destroy(Request $request, User $usuario)
    {
        if ($usuario->id === $request->user()->id) {
            return back()->with('error', 'No puedes suspender tu propia cuenta');
        }

        $usuario->tokens()->delete();
        $usuario->delete();

        return redirect()->route('panel.usuarios')->with('exito', 'Usuario suspendido');
    }

    public function restaurar(int $id)
    {
        $usuario = User::withTrashed()->findOrFail($id);

        if (! $usuario->trashed()) {
            return back()->with('error', 'El usuario no está suspendido');
        }

        $usuario->restore();

        return redirect()->route('panel.usuarios', ['rol' => 'suspendido'])->with('exito', 'Usuario restaurado');
    }

    // Roles que el admin puede asignar (técnico y admin); los normales nacen por auto-registro.
    private function rolesAsignables()
    {
        return Rol::whereIn('nombre_rol', [Rol::TECNICO, Rol::ADMIN])->orderBy('nombre_rol')->get();
    }
}
