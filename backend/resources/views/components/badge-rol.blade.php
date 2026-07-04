@props(['rol' => null])

<span class="badge-rol badge-rol-{{ $rol ?: 'normal' }}">{{ $rol ?: '—' }}</span>
