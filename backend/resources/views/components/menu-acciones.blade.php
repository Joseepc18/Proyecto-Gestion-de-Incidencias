{{-- Menú desplegable de 3 puntos; el slot recibe los <li> (enlaces o formularios de acción). --}}
<div class="dropdown">
  <button class="btn btn-light btn-sm" type="button" data-bs-toggle="dropdown" data-menu-acciones
          aria-expanded="false" aria-label="Acciones">
    <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
  </button>
  <ul class="dropdown-menu dropdown-menu-end">
    {{ $slot }}
  </ul>
</div>
