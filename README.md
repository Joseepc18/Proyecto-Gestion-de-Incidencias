# Gestión de Incidencias Georreferenciadas

> Plataforma web para el **registro, seguimiento y gestión de incidencias urbanas georreferenciadas**: la ciudadanía reporta problemas con ubicación en el mapa y evidencia fotográfica, y el equipo municipal los atiende de punta a punta (asignación, cambios de estado, chat en tiempo real y tablero de indicadores).

<!-- Badges de stack. Estáticos (shields.io); no exponen datos del proyecto. -->
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)

🔗 **Demo en vivo:** [incidenciasupse.site](https://incidenciasupse.site)

Proyecto integrador de la carrera de Tecnologías de la Información (UPSE).

## Tabla de contenidos

- [Funcionalidades por rol](#funcionalidades-por-rol)
- [Stack tecnológico](#stack-tecnológico)
- [Arquitectura](#arquitectura)
- [Estructura del repositorio](#estructura-del-repositorio)
- [Requisitos](#requisitos)
- [Instalación local](#instalación-local)
- [Variables de entorno](#variables-de-entorno)
- [CI y pruebas](#ci-y-pruebas)
- [Despliegue](#despliegue)
- [Equipo](#equipo)

## Funcionalidades por rol

La aplicación define **4 roles de cuenta** (fijos por usuario, tabla `roles`) y, aparte, **2 tipos de asignación** del técnico por incidencia (no son un 5º rol: son la relación de ESE técnico con ESA incidencia puntual).

- **Ciudadano (`normal`)** — se registra por sí mismo, reporta incidencias marcando la ubicación en el mapa y adjuntando fotos (máx. 3), y hace seguimiento y chatea en las incidencias que creó.
- **Técnico (`tecnico`)** — atiende las incidencias que se le asignan. Según el tipo de asignación en esa incidencia: el **responsable** sube la evidencia de resolución (máx. 3 fotos) y es el único que avanza el estado de *en proceso* a *resuelto*; el de **apoyo** solo colabora (ver, chatear), sin subir evidencia ni cerrar.
- **Administrador (`admin`)** — gestiona **todas las incidencias** (reclamar, asignar técnico, cambiar prioridad/estado, editar, enviar a la papelera) y consulta el tablero de indicadores. Debe **reclamar** una incidencia antes de gestionarla (candado de atención). No administra usuarios, catálogos ni permisos.
- **Super administrador (`super_admin`)** — gobierno del sistema: administra usuarios, roles, permisos y catálogos, con **visibilidad total** sobre incidencias (dashboard, historial, detalle). Es *view-only* en incidencias: no reclama, asigna, cambia estado, edita ni escribe en el chat.

> Los administradores y super administradores usan **doble factor (2FA) obligatorio**.

## Stack tecnológico

- **Backend:** Laravel 13.11 · PHP 8.3 — API REST *stateless* con autenticación por token (Laravel Sanctum). Fortify para el 2FA y Socialite para el login con Google.
- **Frontend:** HTML + Bootstrap 5 + JavaScript puro (`fetch`, sin framework), sobre la plantilla AdminHMD. Empaquetado con esbuild.
- **Base de datos:** PostgreSQL 16.
- **Tiempo real:** Laravel Reverb (WebSockets) para el chat y los avisos de cada incidencia.
- **Colas y jobs:** Redis + Laravel Horizon (correo y notificaciones diferidas).
- **Mapas:** Leaflet (satélite Esri + callejero OSM, sin token). **Gráficas:** Chart.js.
- **Infraestructura:** Docker Compose (PostgreSQL, backend ×2, Nginx, Redis, Reverb, Horizon, scheduler).

## Arquitectura

- **Cliente-servidor de N capas desacoplada, con patrón MVC en el backend.** Es un **monolito replicado ×2, NO microservicios**: hay dos réplicas idénticas del backend detrás de Nginx, que balancea por *upstream* (round-robin). Escala en horizontal (más réplicas) y en vertical (pool de workers PHP-FPM por FastCGI en el puerto 9000).
- **API stateless por token:** `/api/*` siempre responde JSON; la autenticación es Bearer (Sanctum), sin sesiones de login. Nginx unifica el origen: enruta `/api/` al backend y sirve el resto como estático desde `frontend/`.
- **Autorización y validación separadas de los controladores:** cada endpoint que muta datos pasa por un **FormRequest** cuyo `authorize()` invoca una **Policy** antes de validar.
- **Pipeline de dominio por eventos:** los hechos del dominio disparan **Events** que atienden **Listeners** y **Notifications** multicanal (base de datos + broadcast por Reverb + correo opcional en cola).
- **Candado de atención (mutex):** un administrador debe *reclamar* una incidencia antes de gestionarla; funciona como un *lease* con heartbeat (TTL 120 s), de modo que si el dueño deja de latir el reclamo caduca y otro admin lo toma.

## Estructura del repositorio

```
.
├── backend/            # API REST en Laravel (app/, database/, routes/, tests/)
├── frontend/           # multi-página (una recarga por navegación, sin router de cliente): carpeta por página + assets/ compartidos
│   ├── login/          #   nombre.html (estructura) + nombre.js (lógica)
│   ├── inicio/         #   ...
│   ├── gestion-incidencias/
│   ├── detalle-incidencia/
│   └── assets/         #   CSS/JS/vendors compartidos (api.js, mapa.js, chat.js, ...)
├── nginx/              # Config del reverse proxy / balanceador
├── pruebas-carga/      # Scripts de pruebas de carga
├── docs/               # Documentación interna (no versionada)
└── docker-compose.yml  # Orquestación de todos los servicios
```

## Requisitos

- [Docker](https://docs.docker.com/get-docker/) y Docker Compose (v2). Todo el ciclo de desarrollo corre dentro de contenedores; **no** hace falta instalar PHP, Composer, Node ni PostgreSQL en el host.
- Node.js solo si se quiere ejecutar el *lint*/formato del frontend fuera de Docker.

## Instalación local

```bash
# 1. Clonar el repositorio
git clone https://github.com/Joseepc18/Proyecto-Gestion-de-Incidencias.git
cd Proyecto-Gestion-de-Incidencias

# 2. Preparar variables de entorno
cp backend/.env.example backend/.env      # ajustar los valores locales
# Crear también un .env en la raíz con las contraseñas que lee Docker Compose:
#   POSTGRES_PASSWORD=... (obligatoria) y, opcionalmente, REDIS_PASSWORD / FRONTEND_ROOT

# 3. Levantar la infraestructura
docker compose up -d --build

# 4. Instalar dependencias PHP y generar la clave de la app (vendor/ no se versiona)
docker compose exec backend composer install
docker compose exec backend php artisan key:generate

# 5. Crear el esquema y datos de referencia (roles + cuentas sembradas)
docker compose exec backend php artisan migrate --seed
```

La aplicación queda disponible en `http://localhost`. En desarrollo Nginx sirve el frontend en crudo desde `frontend/`; para generar el *bundle* optimizado: `cd frontend && npm install && npm run build`.

## Variables de entorno

El backend se configura en `backend/.env` (ver `backend/.env.example`). Las contraseñas de los contenedores viven en un `.env` en la raíz que lee Docker Compose. **Nunca** se versionan valores reales; solo se documentan los nombres:

| Grupo | Variables |
| ----- | --------- |
| App | `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `APP_LOCALE` |
| Base de datos | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| Caché / colas | `CACHE_STORE`, `QUEUE_CONNECTION`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` |
| Broadcast (Reverb) | `BROADCAST_CONNECTION`, `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` |
| Correo | `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` |
| Login con Google | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, `FRONTEND_URL` |
| Semillas | `SEED_ADMIN_PASSWORD`, `SEED_SUPERADMIN_PASSWORD` |
| Docker Compose (raíz) | `POSTGRES_PASSWORD`, `REDIS_PASSWORD`, `FRONTEND_ROOT` |

## CI y pruebas

```bash
# Frontend: estilo y formato
cd frontend && npm run lint
cd frontend && npm run format:check

# Backend: formato PHP (Pint) y suite de pruebas (Feature contra PostgreSQL)
cd backend && ./vendor/bin/pint --test
docker compose exec backend php artisan test

# Un solo test
docker compose exec backend php artisan test --filter NombreDelTest
```

Para aplicar el formato en lugar de solo comprobarlo: `npm run format` (frontend) y `./vendor/bin/pint` (backend).

## Despliegue

- La producción corre con el **mismo `docker-compose.yml`** en un servidor Linux, expuesto a internet mediante un **túnel de Cloudflare** (`cloudflared`) con HTTPS y WebSockets (WSS).
- El *auto-deploy* se dispara con un `push` a la rama `main` (GitHub Actions sobre un *runner* self-hosted, `.github/workflows/deploy.yml`): `git pull` → build del frontend (`npm ci && npm run build`, esbuild → `frontend/dist`) → reinicia `backend`, `frontend` (nginx), `horizon`, `reverb` y `scheduler`. En producción `FRONTEND_ROOT=./frontend/dist`, así que Nginx sirve el **build minificado**, no el árbol crudo. Las **migraciones** y las **reconstrucciones de imagen** (`--build`, tras cambios de infra o dependencias nuevas) se aplican a mano tras el deploy.
- En producción el `.env` se gestiona directamente en el servidor (no viaja por git) y no contiene secretos en el repositorio.

## Equipo

- **Jose** — [@Joseepc18](https://github.com/Joseepc18)
- **Dayron** — [@dayronqv](https://github.com/dayronqv)
