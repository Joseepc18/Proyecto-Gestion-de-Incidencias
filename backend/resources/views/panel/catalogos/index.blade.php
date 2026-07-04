@php($pagina = 'catalogos')

@extends('layouts.app')

@section('titulo', 'Tipos de incidencia')

@php($esSubtipos = $vista === 'subtipos')

@section('contenido')
  <div class="page-heading">
    <div class="page-heading-copy">
      <span class="page-icon"><i class="bi bi-diagram-3" aria-hidden="true"></i></span>
      <div>
        <p class="eyebrow mb-1">Gestión</p>
        <h1 class="h3 mb-1">Tipos de incidencia</h1>
        <p class="text-muted mb-0">Administra los tipos y subtipos de incidencia del sistema.</p>
      </div>
    </div>
    <div class="heading-actions">
      <a class="btn btn-primary btn-sm"
         href="{{ $esSubtipos ? route('panel.catalogos.subtipos.crear') : route('panel.catalogos.tipos.crear') }}">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo {{ $esSubtipos ? 'subtipo' : 'tipo' }}
      </a>
    </div>
  </div>

  <section class="panel">
    <div class="panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div class="btn-group btn-group-sm" role="group" aria-label="Tipos o subtipos">
        <a class="btn btn-outline-primary {{ $esSubtipos ? '' : 'active' }}" href="{{ route('panel.catalogos') }}">
          <i class="bi bi-tag" aria-hidden="true"></i> Tipos
        </a>
        <a class="btn btn-outline-primary {{ $esSubtipos ? 'active' : '' }}" href="{{ route('panel.catalogos', ['vista' => 'subtipos']) }}">
          <i class="bi bi-tags" aria-hidden="true"></i> Subtipos
        </a>
      </div>

      @if ($esSubtipos)
        @php($nombreFiltro = optional($tipos->firstWhere('id_tipo_incidencia', (int) $filtroTipo))->nombre_tipo_incidencia)
        <div class="dropdown">
          <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-funnel me-1" aria-hidden="true"></i>{{ $nombreFiltro ?? 'Todos los tipos' }}
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ route('panel.catalogos', ['vista' => 'subtipos']) }}">Todos los tipos</a></li>
            @foreach ($tipos as $t)
              <li>
                <a class="dropdown-item" href="{{ route('panel.catalogos', ['vista' => 'subtipos', 'tipo' => $t->id_tipo_incidencia]) }}">
                  {{ $t->nombre_tipo_incidencia }}
                </a>
              </li>
            @endforeach
          </ul>
        </div>
      @endif
    </div>

    <div class="table-responsive">
      <table class="table tabla-cards align-middle mb-0">
        <thead>
          @if ($esSubtipos)
            <tr><th>Subtipo</th><th>Tipo padre</th><th>Descripción</th><th class="text-end">Acciones</th></tr>
          @else
            <tr><th>Nombre</th><th>Descripción</th><th class="text-center">Subtipos</th><th class="text-end">Acciones</th></tr>
          @endif
        </thead>
        <tbody>
          @forelse ($registros as $registro)
            @if ($esSubtipos)
              <tr>
                <td data-label="Subtipo">{{ $registro->nombre_subtipo_incidencia }}</td>
                <td data-label="Tipo padre"><span class="badge text-bg-light">{{ $registro->tipo?->nombre_tipo_incidencia }}</span></td>
                <td data-label="Descripción" class="text-muted td-secundario">{{ $registro->descripcion_subtipo_incidencia ?: '—' }}</td>
                <td class="text-end">
                  <x-menu-acciones>
                    <li>
                      <a class="dropdown-item" href="{{ route('panel.catalogos.subtipos.editar', $registro) }}">
                        <i class="bi bi-pencil me-2" aria-hidden="true"></i>Editar
                      </a>
                    </li>
                    <li>
                      <form method="POST" action="{{ route('panel.catalogos.subtipos.destroy', $registro) }}"
                            data-confirmar data-confirmar-peligro="1" data-confirmar-titulo="¿Eliminar subtipo?"
                            data-confirmar-mensaje="Se eliminará el subtipo &quot;{{ $registro->nombre_subtipo_incidencia }}&quot;."
                            data-confirmar-texto="Eliminar">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger">
                          <i class="bi bi-trash me-2" aria-hidden="true"></i>Eliminar
                        </button>
                      </form>
                    </li>
                  </x-menu-acciones>
                </td>
              </tr>
            @else
              <tr>
                <td data-label="Nombre">{{ $registro->nombre_tipo_incidencia }}</td>
                <td data-label="Descripción" class="text-muted td-secundario">{{ $registro->descripcion_tipo_incidencia ?: '—' }}</td>
                <td data-label="Subtipos" class="text-center"><span class="badge text-bg-secondary">{{ $registro->subtipos_count }}</span></td>
                <td class="text-end">
                  <x-menu-acciones>
                    <li>
                      <a class="dropdown-item" href="{{ route('panel.catalogos.tipos.editar', $registro) }}">
                        <i class="bi bi-pencil me-2" aria-hidden="true"></i>Editar
                      </a>
                    </li>
                    <li>
                      <form method="POST" action="{{ route('panel.catalogos.tipos.destroy', $registro) }}"
                            data-confirmar data-confirmar-peligro="1" data-confirmar-titulo="¿Eliminar tipo?"
                            data-confirmar-mensaje="Se eliminará el tipo &quot;{{ $registro->nombre_tipo_incidencia }}&quot;."
                            data-confirmar-texto="Eliminar">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger">
                          <i class="bi bi-trash me-2" aria-hidden="true"></i>Eliminar
                        </button>
                      </form>
                    </li>
                  </x-menu-acciones>
                </td>
              </tr>
            @endif
          @empty
            <tr>
              <td colspan="4">
                <x-estado-vacio icono="bi-tags"
                  :titulo="$esSubtipos ? 'Sin subtipos' : 'Sin tipos'"
                  :texto="$esSubtipos ? 'Crea el primer subtipo con el botón de arriba.' : 'Crea el primer tipo de incidencia con el botón de arriba.'" />
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="px-3 pb-3 pt-3">
      {{ $registros->links() }}
    </div>
  </section>
@endsection
