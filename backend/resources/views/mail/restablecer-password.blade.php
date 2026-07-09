<x-mail::message>
# Hola, {{ $nombre }}

Recibimos una solicitud para restablecer la contraseña de tu cuenta. Usa el botón para elegir una nueva.

<x-mail::button :url="$url">
Restablecer contraseña
</x-mail::button>

Este enlace caduca en {{ $minutos }} minutos. Si no fuiste tú, ignora este correo: tu contraseña seguirá igual.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
