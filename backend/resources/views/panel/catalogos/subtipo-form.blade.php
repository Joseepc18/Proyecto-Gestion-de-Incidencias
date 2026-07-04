@php($pagina = 'catalogos')

@extends('layouts.app')

@php($editando = $subtipo !== null)

@section('titulo', $editando ? 'Editar subtipo' : 'Nuevo subtipo')

@section('contenido')
  <div class="page-heading">
    <div class="page-heading-copy">
      <span class="page-icon"><i class="bi bi-tags" aria-hidden="true"></i></span>
      <div>
        <p class="eyebrow mb-1"><a href="{{ route('panel.catalogos', ['vista' => 'subtipos']) }}" class="text-decoration-none">Subtipos de incidencia</a></p>
        <h1 class="h3 mb-1">{{ $editando ? 'Editar subtipo' : 'Nuevo subtipo' }}</h1>
      </div>
    </div>
  </div>

  <section class="panel">
    <div class="p-3 p-lg-4" style="max-width: 560px">
      <form method="POST"
            action="{{ $editando ? route('panel.catalogos.subtipos.update', $subtipo) : route('panel.catalogos.subtipos.store') }}">
        @csrf
        @if ($editando)
          @method('PUT')
        @endif

        <div class="mb-3">
          <label class="form-label" for="id_tipo_incidencia">Tipo padre</label>
          <select id="id_tipo_incidencia" name="id_tipo_incidencia" required
                  class="form-select @error('id_tipo_incidencia') is-invalid @enderror">
            <option value="" disabled @selected(! old('id_tipo_incidencia', $subtipo?->id_tipo_incidencia))>Selecciona un tipo…</option>
            @foreach ($tipos as $t)
              <option value="{{ $t->id_tipo_incidencia }}"
                      @selected(old('id_tipo_incidencia', $subtipo?->id_tipo_incidencia) == $t->id_tipo_incidencia)>
                {{ $t->nombre_tipo_incidencia }}
              </option>
            @endforeach
          </select>
          @error('id_tipo_incidencia')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
          <label class="form-label" for="nombre_subtipo_incidencia">Nombre</label>
          <input type="text" id="nombre_subtipo_incidencia" name="nombre_subtipo_incidencia" required maxlength="255"
                 class="form-control @error('nombre_subtipo_incidencia') is-invalid @enderror"
                 value="{{ old('nombre_subtipo_incidencia', $subtipo?->nombre_subtipo_incidencia) }}" />
          @error('nombre_subtipo_incidencia')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="mb-4">
          <label class="form-label" for="descripcion_subtipo_incidencia">Descripción <span class="text-muted">(opcional)</span></label>
          <textarea id="descripcion_subtipo_incidencia" name="descripcion_subtipo_incidencia" rows="3" maxlength="500"
                    class="form-control @error('descripcion_subtipo_incidencia') is-invalid @enderror">{{ old('descripcion_subtipo_incidencia', $subtipo?->descripcion_subtipo_incidencia) }}</textarea>
          @error('descripcion_subtipo_incidencia')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">{{ $editando ? 'Guardar' : 'Crear' }}</button>
          <a href="{{ route('panel.catalogos', ['vista' => 'subtipos']) }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
      </form>
    </div>
  </section>
@endsection
