@php($pagina = 'usuarios')

@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
  <div class="page-heading">
    <div class="page-heading-copy">
      <span class="page-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
      <div>
        <p class="eyebrow mb-1">Gestión</p>
        <h1 class="h3 mb-1">Usuarios</h1>
        <p class="text-muted mb-0">Piloto Blade: listado renderizado en el servidor.</p>
      </div>
    </div>
  </div>

  <div class="card mt-4">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Rol</th>
            <th>Verificado</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($usuarios as $usuario)
            <tr>
              <td>{{ $usuario->name }}</td>
              <td>{{ $usuario->email }}</td>
              <td>{{ $usuario->rol?->nombre_rol ?? '—' }}</td>
              <td>
                @if ($usuario->email_verified_at)
                  <span class="badge bg-success-subtle text-success">Sí</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary">No</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-muted py-4">No hay usuarios.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3">
    {{ $usuarios->links() }}
  </div>
@endsection
