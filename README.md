# Proyecto Gestión de Incidencias

Sistema web para el **registro, seguimiento y gestión de incidencias urbanas georreferenciadas**.
Permite a la ciudadanía reportar incidencias con ubicación en el mapa y evidencia fotográfica, y a los
administradores y responsables gestionarlas de punta a punta (asignación, cambios de estado, chat en tiempo
real y tablero de indicadores).

## Stack tecnológico

- **Backend:** Laravel 13.11 · PHP 8.3 — API REST stateless (token Sanctum) + panel Blade (guard web) para el CRUD plano.
- **Frontend:** HTML + Bootstrap 5 + JavaScript puro (`fetch`, sin framework). Plantilla AdminHMD.
- **Base de datos:** PostgreSQL 16.
- **Tiempo real:** Laravel Reverb (WebSockets) para el chat de cada incidencia.
- **Colas:** Redis + Laravel Horizon.
- **Mapas:** Leaflet · **Gráficas:** Chart.js.
- **Infraestructura:** Docker Compose (PostgreSQL, backend ×2, frontend/Nginx, Redis). Monolito replicado
  con balanceo por upstream de Nginx.

## Roles

- **Ciudadano** — reporta incidencias y da seguimiento a las suyas.
- **Administrador** — gestiona todas las incidencias, usuarios y catálogos, asigna responsables.
- **Responsable** — resuelve las incidencias que se le asignan.
