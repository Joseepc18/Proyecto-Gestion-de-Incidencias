<x-mail::message>
# Hola, {{ $nombre }}

{{ $mensaje }}

Revisa los detalles y la ubicación de la incidencia para empezar a atenderla.

@if ($url)
<x-mail::button :url="$url">
Ver incidencia
</x-mail::button>
@endif

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
