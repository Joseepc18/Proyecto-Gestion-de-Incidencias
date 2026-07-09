<x-mail::message>
# Hola, {{ $nombre }}

Tu incidencia **{{ $incidencia->nombre_incidencia }}** ya está en atención. Estos son los detalles:

<x-mail::table>
| Detalle | Información |
|:--------|:-----------|
| Estado | {{ $estado }} |
| Prioridad | {{ $prioridad }} |
| Responsable | {{ count($responsables) ? implode(', ', $responsables) : 'Por asignar' }} |
@if (count($apoyos))
| Apoyo | {{ implode(', ', $apoyos) }} |
@endif
</x-mail::table>

@if ($incidencia->descripcion_incidencia)
**Descripción reportada:** {{ $incidencia->descripcion_incidencia }}
@endif

<x-mail::button :url="$url">
Ver incidencia
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
