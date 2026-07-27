<x-mail::message>
# Hola, {{ $nombre }}

@if ($aprobada)
Tu solicitud de reactivación fue **aprobada**. Tu cuenta ya está activa y puedes volver a iniciar sesión con tus credenciales de siempre.
@else
Tu solicitud de reactivación fue **rechazada**, así que tu cuenta sigue suspendida. Puedes volver a enviar una solicitud desde la pantalla de inicio de sesión.
@endif

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
