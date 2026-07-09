<x-mail::message>
# Hola, {{ $nombre }}

Este es el resumen de incidencias de hoy ({{ now()->format('d/m/Y') }}):

<x-mail::table>
| Métrica | Cantidad |
| :------ | :------- |
| Creadas hoy | {{ $creadasHoy }} |
| Resueltas hoy | {{ $resueltasHoy }} |
| Pendientes (total) | {{ $totalPendientes }} |
| Pendientes ALTA | {{ $pendientesPorPrioridad->get('ALTA', 0) }} |
| Pendientes MEDIA | {{ $pendientesPorPrioridad->get('MEDIA', 0) }} |
| Pendientes BAJA | {{ $pendientesPorPrioridad->get('BAJA', 0) }} |
</x-mail::table>

<x-mail::button :url="config('services.frontend_url')">
Ir al panel
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
