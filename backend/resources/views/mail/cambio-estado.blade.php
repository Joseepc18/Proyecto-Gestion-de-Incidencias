<x-mail::message>
# Hola, {{ $nombre }}

{{ $mensaje }}

@if ($url)
<x-mail::button :url="$url">
Ver incidencia
</x-mail::button>
@endif

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
