<x-mail::message>
# Hola, {{ $nombre }}

Se solicitó cambiar el correo de tu cuenta a **{{ $emailNuevo }}**. El cambio no se aplicará hasta confirmarlo desde ese correo nuevo.

Si fuiste tú, no tienes que hacer nada más. **Si no reconoces esta solicitud**, cambia tu contraseña cuanto antes: alguien podría tener acceso a tu cuenta.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
