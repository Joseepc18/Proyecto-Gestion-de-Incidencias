<x-mail::message>
# Hola, {{ $nombre }}

Gracias por registrarte. Confirma tu correo con el botón para activar tu cuenta.

<x-mail::button :url="$url">
Verificar correo
</x-mail::button>

Si no creaste esta cuenta, ignora este correo.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
