@php($pagina = 'catalogos')

@extends('layouts.app')

@php($editando = $tipo !== null)

@section('titulo', $editando ? 'Editar tipo' : 'Nuevo tipo')

@section('contenido')
  <div class="page-heading">
    <div class="page-heading-copy">
      <span class="page-icon"><i class="bi bi-tag" aria-hidden="true"></i></span>
      <div>
        <p class="eyebrow mb-1"><a href="{{ route('panel.catalogos') }}" class="text-decoration-none">Tipos de incidencia</a></p>
        <h1 class="h3 mb-1">{{ $editando ? 'Editar tipo' : 'Nuevo tipo' }}</h1>
      </div>
    </div>
  </div>

  <section class="panel">
    <div class="p-3 p-lg-4" style="max-width: 560px">
      <form method="POST"
            action="{{ $editando ? route('panel.catalogos.tipos.update', $tipo) : route('panel.catalogos.tipos.store') }}">
        @csrf
        @if ($editando)
          @method('PUT')
        @endif

        <div class="mb-3">
          <label class="form-label" for="nombre_tipo_incidencia">Nombre</label>
          <input type="text" id="nombre_tipo_incidencia" name="nombre_tipo_incidencia" required maxlength="255"
                 class="form-control @error('nombre_tipo_incidencia') is-invalid @enderror"
                 value="{{ old('nombre_tipo_incidencia', $tipo?->nombre_tipo_incidencia) }}" />
          @error('nombre_tipo_incidencia')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-4">
          <label class="form-label" for="descripcion_tipo_incidencia">Descripción <span class="text-muted">(opcional)</span></label>
          <textarea id="descripcion_tipo_incidencia" name="descripcion_tipo_incidencia" rows="3" maxlength="500"
                    class="form-control @error('descripcion_tipo_incidencia') is-invalid @enderror">{{ old('descripcion_tipo_incidencia', $tipo?->descripcion_tipo_incidencia) }}</textarea>
          @error('descripcion_tipo_incidencia')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">{{ $editando ? 'Guardar' : 'Crear' }}</button>
          <a href="{{ route('panel.catalogos') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </section>
@endsection
