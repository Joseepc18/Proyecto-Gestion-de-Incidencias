@props(['usuario'])

@php
    $rol = $usuario->rol?->nombre_rol ?: 'normal';
    $partes = preg_split('/\s+/', trim($usuario->name));
    $iniciales = strtoupper(mb_substr($partes[0] ?? '', 0, 1).mb_substr($partes[1] ?? '', 0, 1)) ?: '?';
@endphp

<span class="tabla-avatar tabla-avatar-{{ $rol }}">
  @if ($usuario->foto_perfil)
    <img src="/storage/{{ $usuario->foto_perfil }}" alt="" />
  @else
    {{ $iniciales }}
  @endif
</span>
