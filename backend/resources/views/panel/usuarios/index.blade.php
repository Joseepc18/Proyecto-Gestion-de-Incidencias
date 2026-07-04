@php($pagina = 'usuarios')

@extends('layouts.app')

@section('titulo', 'Usuarios')

@php
    $etiquetasFiltro = [
        '' => 'Todos',
        'admin' => 'Administradores',
        'tecnico' => 'Técnicos',
        'normal' => 'Normales',
        'suspendido' => 'Suspendidos',
    ];
@endphp

@section('contenido')
  <div class="page-heading">
    <div class="page-heading-copy">
      <span class="page-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
      <div>
        <p class="eyebrow mb-1">Gestión</p>
        <h1 class="h3 mb-1">Usuarios</h1>
        <p class="text-muted mb-0">Crea y administra las cuentas del sistema.</p>
      </div>
    </div>
    <div class="heading-actions">
      <a class="btn btn-primary btn-sm" href="{{ route('panel.usuarios.crear') }}">
        <i class="bi bi-person-plus" aria-hidden="true"></i> Nuevo usuario
      </a>
    </div>
  </div>

  <section class="panel">
    <div class="panel-header">
      <h2 class="h5 mb-0 section-title">
        <i class="bi bi-list-ul" aria-hidden="true"></i>
        <span>Listado de usuarios</span>
      </h2>

      <div class="dropdown">
        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-funnel me-1" aria-hidden="true"></i>{{ $etiquetasFiltro[$filtro] ?? 'Todos' }}
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="{{ route('panel.usuarios') }}">Todos</a></li>
          <li><a class="dropdown-item" href="{{ route('panel.usuarios', ['rol' => 'admin']) }}">Administradores</a></li>
          <li><a class="dropdown-item" href="{{ route('panel.usuarios', ['rol' => 'tecnico']) }}">Técnicos</a></li>
          <li><a class="dropdown-item" href="{{ route('panel.usuarios', ['rol' => 'normal']) }}">Normales</a></li>
          <li><hr class="dropdown-divider" /></li>
          <li>
            <a class="dropdown-item text-warning" href="{{ route('panel.usuarios', ['rol' => 'suspendido']) }}">
              <i class="bi bi-slash-circle me-1" aria-hidden="true"></i>Suspendidos
            </a>
          </li>
        </ul>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table tabla-cards align-middle mb-0">
        <thead>
          <tr>
            <th></th>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Rol</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($usuarios as $usuario)
            @php($suspendido = $filtro === 'suspendido')
            <tr @class(['table-secondary' => $suspendido])>
              <td class="td-avatar"><x-avatar-usuario :usuario="$usuario" /></td>
              <td data-label="Nombre" @class(['text-decoration-line-through' => $suspendido])>{{ $usuario->name }}</td>
              <td data-label="Correo" class="td-secundario">{{ $usuario->email }}</td>
              <td data-label="Rol"><x-badge-rol :rol="$usuario->rol?->nombre_rol" /></td>
              <td class="text-end">
                @if ($usuario->id !== auth()->id())
                  <x-menu-acciones>
                    @if ($suspendido)
                      <li>
                        <form method="POST" action="{{ route('panel.usuarios.restaurar', $usuario->id) }}"
                              data-confirmar data-confirmar-titulo="¿Restaurar usuario?"
                              data-confirmar-mensaje="Se reactivará la cuenta de &quot;{{ $usuario->name }}&quot;. Volverá a poder iniciar sesión."
                              data-confirmar-texto="Restaurar">
                          @csrf
                          <button type="submit" class="dropdown-item">
                            <i class="bi bi-arrow-counterclockwise me-2" aria-hidden="true"></i>Restaurar
                          </button>
                        </form>
                      </li>
                    @else
                      @unless ($usuario->esNormal())
                        <li>
                          <a class="dropdown-item" href="{{ route('panel.usuarios.editar', $usuario) }}">
                            <i class="bi bi-pencil me-2" aria-hidden="true"></i>Editar
                          </a>
                        </li>
                      @endunless
                      <li>
                        <form method="POST" action="{{ route('panel.usuarios.destroy', $usuario) }}"
                              data-confirmar data-confirmar-peligro="1" data-confirmar-titulo="¿Suspender usuario?"
                              data-confirmar-mensaje="Se suspenderá la cuenta de &quot;{{ $usuario->name }}&quot;. No podrá iniciar sesión."
                              data-confirmar-texto="Suspender">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-slash-circle me-2" aria-hidden="true"></i>Suspender
                          </button>
                        </form>
                      </li>
                    @endif
                  </x-menu-acciones>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5">
                <x-estado-vacio icono="bi-people" titulo="Sin usuarios" texto="No hay usuarios que coincidan con el filtro." />
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="px-3 pb-3 pt-3">
      {{ $usuarios->links() }}
    </div>
  </section>
@endsection
