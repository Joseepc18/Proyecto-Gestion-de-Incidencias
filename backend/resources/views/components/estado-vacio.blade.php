@props(['icono' => 'bi-inbox', 'titulo', 'texto' => null])

{{-- Estado vacío neutro (no rojo) para tablas y secciones sin datos; equivale a estadoVacioHtml de util.js. --}}
<div class="estado-vacio">
  <i class="bi {{ $icono }} estado-vacio-icono" aria-hidden="true"></i>
  <p class="estado-vacio-titulo">{{ $titulo }}</p>
  @if ($texto)
    <p class="estado-vacio-texto">{{ $texto }}</p>
  @endif
</div>
