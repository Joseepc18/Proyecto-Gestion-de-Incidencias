<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class PanelUsuarioController extends Controller
{
    // Piloto Blade: listado renderizado en el servidor con paginación nativa ($usuarios->links()).
    public function index(Request $request)
    {
        $usuarios = User::with('rol')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('panel.usuarios.index', compact('usuarios'));
    }
}
