@php($pagina = 'usuarios')

@extends('layouts.app')

@php($editando = $usuario !== null)

@section('titulo', $editando ? 'Editar usuario' : 'Nuevo usuario')

@section('contenido')
  <div class="page-heading">
    <div class="page-heading-copy">
      <span class="page-icon"><i class="bi bi-person-plus" aria-hidden="true"></i></span>
      <div>
        <p class="eyebrow mb-1"><a href="{{ route('panel.usuarios') }}" class="text-decoration-none">Usuarios</a></p>
        <h1 class="h3 mb-1">{{ $editando ? 'Editar usuario' : 'Nuevo usuario' }}</h1>
      </div>
    </div>
  </div>

  <section class="panel">
    <div class="p-3 p-lg-4" style="max-width: 560px">
      <form method="POST"
            action="{{ $editando ? route('panel.usuarios.update', $usuario) : route('panel.usuarios.store') }}">
        @csrf
        @if ($editando)
          @method('PUT')
        @endif

        <div class="mb-3">
          <label class="form-label" for="name">Nombre</label>
          <input type="text" id="name" name="name" required
                 class="form-control @error('name') is-invalid @enderror"
                 value="{{ old('name', $usuario?->name) }}" />
          @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
          <label class="form-label" for="email">Correo</label>
          <input type="email" id="email" name="email" required
                 class="form-control @error('email') is-invalid @enderror"
                 value="{{ old('email', $usuario?->email) }}" />
          @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
          <label class="form-label" for="password">Contraseña</label>
          <div class="input-group has-validation">
            <input type="password" id="password" name="password" minlength="8"
                   class="form-control @error('password') is-invalid @enderror"
                   @if ($editando) placeholder="Dejar vacío para no cambiar" @else required @endif />
            <button class="btn toggle-password" type="button" data-target="password"
                    aria-label="Mostrar contraseña" aria-pressed="false">
              <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
          <div class="input-group has-validation">
            <input type="password" id="password_confirmation" name="password_confirmation" minlength="8"
                   class="form-control"
                   @if ($editando) placeholder="Dejar vacío para no cambiar" @else required @endif />
            <button class="btn toggle-password" type="button" data-target="password_confirmation"
                    aria-label="Mostrar contraseña" aria-pressed="false">
              <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label" for="id_rol">Rol</label>
          <select id="id_rol" name="id_rol" required class="form-select @error('id_rol') is-invalid @enderror">
            @foreach ($roles as $rol)
              <option value="{{ $rol->id_rol }}" @selected(old('id_rol', $usuario?->id_rol) == $rol->id_rol)>
                {{ $rol->nombre_rol }}
              </option>
            @endforeach
          </select>
          @error('id_rol')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">{{ $editando ? 'Guardar' : 'Crear' }}</button>
          <a href="{{ route('panel.usuarios') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </section>
@endsection
