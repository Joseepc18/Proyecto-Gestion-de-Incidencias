<x-mail::message>
# Hola, {{ $nombre }}

Recibimos una solicitud para usar esta dirección como el correo de tu cuenta. Confirma el cambio con el botón para activarlo.

<x-mail::button :url="$url">
Confirmar nuevo correo
</x-mail::button>

Si no solicitaste este cambio, ignora este correo: tu cuenta seguirá con el correo actual.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
