# Plan de Replicación — Sistema de Gestión de Incidencias
## Guía paso a paso para construir el proyecto desde cero

> **Propósito:** Este documento te guía para construir el mismo sistema en un repositorio nuevo,
> paso a paso. Úsalo junto con `aprender.md` cuando necesites entender algún concepto.
>
> **Marcadores usados en este plan:**
> - `[LINEAMIENTO]` — obligatorio según los lineamientos oficiales del proyecto
> - `[EXTRA]` — implementado en el proyecto original pero no obligatorio; da puntos bonus
> - `[WEB]` — cubre la materia **Tecnologías y Desarrollo Web** (40% de la nota)
> - `[CALIDAD]` — cubre la materia **Calidad de Software** (20% de la nota)
> - `[DATA CENTER]` — cubre la materia **Administración de Data Center** (20% de la nota)
> - `[BD]` — cubre la materia **Base de Datos I y II** (20% de la nota)
> - `📖 aprender.md §N` — sección del archivo aprender.md donde se explica ese concepto

---

## Cómo usar este plan con un agente de IA

> Esta sección es para el agente que acompañe el desarrollo. Si eres el agente, lee esto primero.

**Contexto del proyecto:**
Este es el plan para replicar un Sistema de Gestión de Incidencias Georreferenciadas.
El sistema original ya fue construido — este plan guía al usuario para construir la misma aplicación
en un repositorio nuevo, desde cero, a su propio ritmo.

**Tu rol como agente:**
1. Lee el plan completo al inicio de cada sesión para saber qué está hecho y qué falta
2. Cuando el usuario diga en qué día/paso está, ubícate en esa sección del plan
3. Explica cada paso antes de que el usuario lo ejecute
4. **Verifica el trabajo**: después de cada paso, lee los archivos creados y confirma que son correctos
5. Compara contra el proyecto de referencia descrito en el plan
6. Si algo está incorrecto, señala exactamente qué cambiar y por qué

**Cómo verificar cada paso:**
- Migraciones: verifica que la estructura SQL coincida con lo descrito en el plan
- Modelos: verifica que `fillable` y las relaciones estén definidas
- Controladores: verifica que los métodos existen y retornan la estructura JSON correcta
- Frontend: verifica que los archivos HTML/JS referencian las rutas correctas del plan
- Docker: verifica que `docker-compose up -d` levanta todos los servicios sin errores
- Tests: verifica con `php artisan test --testdox` que los 10 tests pasan

**Cómo saber el estado actual:**
El usuario te dirá en qué día está. Si no lo dice, pregúntale:
"¿En qué paso del plan te encuentras? ¿Qué fue lo último que completaste?"

**Archivos clave que el agente debe conocer:**
- `PLAN_REPLICACION.md` (este archivo) — la guía maestra
- `aprender.md` — explicaciones de cada concepto técnico
- `docker-compose.yml` — infraestructura Docker del proyecto
- `backend/Dockerfile` — imagen del backend
- `nginx/default.conf` — configuración del servidor web

---

## Qué evalúa cada materia y cómo lo cumples

### Tecnologías y Desarrollo Web `[WEB]` — 40%
Evalúa que construyas un sistema web funcional con las tecnologías pedidas.
| Qué piden | Cómo lo cumples | Cuándo |
|-----------|-----------------|--------|
| API REST con Laravel | Controladores + routes/api.php | Semana 2 |
| Frontend con HTML, CSS, Bootstrap | Páginas HTML + Bootstrap 5 | Semana 3 |
| JavaScript con fetch | apiFetch(), formularios, gráficas | Semana 3 |
| CRUD de incidencias | IncidenciaController + incidencias.html | Sem 2-3 |
| Autenticación | Sanctum + login.html + token en localStorage | Sem 2-3 |
| Control de roles | Middleware CheckAdmin + visibilidad por rol en JS | Sem 2-3 |
| Integración frontend-backend | apiFetch con Authorization header | Sem 3 |

### Calidad de Software `[CALIDAD]` — 20%
Evalúa que pruebes tu código con herramientas reales y documentes los resultados.
| Qué piden | Cómo lo cumples | Cuándo |
|-----------|-----------------|--------|
| Tests unitarios / de integración | **PHPUnit** con `php artisan test` | Día 10 |
| Pruebas de carga | **Artillery** — 50+ usuarios virtuales, flujo completo | Día 20 |
| Validaciones en el sistema | Validaciones en Request + en el formulario JS | Sem 2-3 |
| Manejo de errores | try/catch en controladores + tabla bitacora_errores | Sem 2 |
| Evidencias documentadas | Guardar output de `--testdox` y resultado de Artillery | Sem 4 |

> **PHPUnit** es el framework de pruebas de Laravel. Se corre con `php artisan test`.
> Cada "test" es una función que simula una petición HTTP y verifica la respuesta.
>
> **Artillery** es una herramienta Node.js que simula muchos usuarios haciendo peticiones
> al mismo tiempo. Se configura con un archivo `.yml` y se corre con `artillery run`.
>
> **`php artisan`** es la CLI de Laravel. Comandos útiles:
> - `php artisan test` — corre los tests PHPUnit
> - `php artisan migrate` — ejecuta las migraciones pendientes
> - `php artisan db:seed` — ejecuta los seeders
> - `php artisan make:controller` — genera un controlador
> - `php artisan optimize` — cachea rutas, config y vistas para producción
> - `php artisan storage:link` — crea enlace para servir archivos subidos

### Administración de Data Center `[DATA CENTER]` — 20%
Evalúa que despliegues el sistema en contenedores Docker correctamente.
| Qué piden | Cómo lo cumples | Cuándo |
|-----------|-----------------|--------|
| Contenedor de backend | Servicio `backend` en docker-compose.yml | Día 1 |
| Contenedor de base de datos | Servicio `db` (PostgreSQL) en docker-compose.yml | Día 1 |
| Integración entre contenedores | `DB_HOST=db` en el .env del backend | Día 1 |
| Configuración del entorno | `.env.example` documentado | Día 1 |
| Servicios adicionales `[EXTRA]` | Redis (caché) + PgAdmin (administración) | Día 1 |
| Healthchecks `[EXTRA]` | `healthcheck` en db y backend en docker-compose | Día 1 |
| Escalamiento horizontal `[EXTRA]` | `deploy.replicas: 2` en el servicio backend | Día 1 |

> **Docker Compose** es la herramienta que levanta varios contenedores a la vez.
> Cada `service` en el `docker-compose.yml` es un contenedor independiente.
> Los contenedores se comunican entre sí usando el nombre del servicio como hostname
> (por eso el backend usa `DB_HOST=db`, no `localhost`).

### Base de Datos I y II `[BD]` — 20%
Evalúa que diseñes bien el modelo de datos y uses SQL avanzado.
| Qué piden | Cómo lo cumples | Cuándo |
|-----------|-----------------|--------|
| Modelo relacional normalizado | 14 tablas con FK, CHECK, NOT NULL | Días 2-3 |
| Jerarquía geográfica | País → Provincia → Ciudad | Días 2-3 |
| Clasificación jerárquica | Tipo → Subtipo de incidencia | Días 2-3 |
| Historial de estados | Tabla historial_estados + trigger automático | Día 3 |
| Triggers | 3 triggers BEFORE/AFTER en PostgreSQL | Día 3 |
| Procedimientos almacenados `[EXTRA]` | resolver_incidencia, asignar_tecnico | Día 3 |
| Vistas SQL `[EXTRA]` | v_incidencias_completas, v_metricas_por_tipo | Día 3 |
| Función PL/pgSQL `[EXTRA]` | calcular_tiempo_resolucion() | Día 3 |
| Índices de rendimiento `[EXTRA]` | 5 índices en columnas más consultadas | Día 3 |
| Consultas con métricas | Dashboard: COUNT, AVG, GROUP BY, CASE WHEN | Semana 2 |
| Backup de la base de datos | pg_dump genera backup.sql | Día 21 |

---

## Mapa de días por materia

```
SEMANA 1
  Día 1  ─── [DATA CENTER] Docker, docker-compose, .env
  Día 2  ─── [BD] Migraciones: tablas base (users, cache, jobs)
  Día 3  ─── [BD] Migraciones: tablas del proyecto + triggers + vistas + procedimientos
  Día 4  ─── [WEB] Modelos Eloquent (capa de datos del backend)
  Día 5  ─── [BD] Seeders (datos iniciales y de prueba)

SEMANA 2
  Día 6  ─── [WEB] AuthController + Middleware + rutas de autenticación
  Día 7  ─── [WEB] CatalogoController + IncidenciaController (CRUD)
  Día 8  ─── [WEB] ComentarioController + AsignacionController + NotificacionController
  Día 9  ─── [WEB] DashboardController + UserController + api.php completo
  Día 10 ─── [CALIDAD] 10 tests PHPUnit con php artisan test

SEMANA 3
  Día 11 ─── [WEB] api.js + auth.js + notificaciones.js (base del frontend)
  Día 12 ─── [WEB] login.html + login.js + register.html + register.js
  Día 13 ─── [WEB] incidencias.html + incidencias.js (tabla, filtros, paginación)
  Día 14 ─── [WEB] Modal de nueva incidencia con mapa Leaflet y validaciones
  Día 15 ─── [WEB] nueva.html / modal de creación con FormData
  Día 16 ─── [WEB] Detalle de incidencia (modal/sección dentro de incidencias.html)
  Día 17 ─── [WEB] dashboard.html + dashboard.js con Chart.js
  Día 18 ─── [WEB] usuarios.html + usuarios.js [EXTRA]
  Día 19 ─── [WEB] Revisión general, control de visibilidad por rol

SEMANA 4
  Día 20 ─── [CALIDAD] Pruebas de carga con Artillery
  Día 21 ─── [BD] Verificar triggers en PgAdmin + generar backup.sql
  Día 22 ─── [DATA CENTER] Verificar docker-compose completo + réplicas
  Día 23 ─── Documento técnico: secciones BD y Data Center
  Día 24 ─── Documento técnico: secciones Web y Calidad + capturas de pantalla
  Día 25 ─── PR a main + entrega final
```

---

## Seguridad en Git — Qué nunca debes subir a GitHub

> Esta sección es crítica. Subir archivos sensibles a un repositorio público
> expone credenciales y puede comprometer el sistema.

### Archivos que NUNCA deben ir a GitHub

| Archivo | Por qué es peligroso |
|---------|----------------------|
| `backend/.env` | Contiene contraseñas de la BD, claves de API, APP_KEY de Laravel |
| `backend/vendor/` | Son librerías de terceros (miles de archivos). Se regeneran con `composer install` |
| `backend/storage/app/` | Archivos subidos por los usuarios (fotos de incidencias) |
| `backend/storage/logs/` | Logs internos con posibles datos sensibles |
| `backend/.phpunit.result.cache` | Caché interna de PHPUnit, no tiene valor para el repositorio |
| `node_modules/` | Dependencias de Node/Artillery. Se regeneran con `npm install` |
| `postgres_data/` | Datos del contenedor PostgreSQL (carpeta local si Docker usó bind mount) |
| `*.log` | Logs de cualquier tipo |

### Qué SÍ debes subir

| Archivo | Por qué va en el repositorio |
|---------|------------------------------|
| `backend/.env.example` | Plantilla pública con todas las variables pero sin valores reales |
| `backend/composer.json` | Lista de dependencias PHP (sin el código de las librerías) |
| `backend/composer.lock` | Versiones exactas para reproducir el entorno |
| `docker-compose.yml` | Configuración de contenedores (sin contraseñas reales si es público) |
| Todo el código fuente | Migraciones, modelos, controladores, HTML, JS |

### Configurar el `.gitignore` desde el primer día

Cuando creas el proyecto Laravel con `composer create-project`, ya genera un `.gitignore`
con las reglas básicas. Verifica que tenga al menos estas líneas:

```gitignore
# Credenciales — NUNCA subir
.env
.env.backup
.env.production

# Dependencias — se regeneran con composer install
/vendor

# Archivos generados — se crean al correr el sistema
/storage/app/public
/public/storage
/public/hot
*.log
```

Para el `.gitignore` de la raíz del proyecto (donde va el `docker-compose.yml`):
```gitignore
# Dependencias de Node (Artillery, etc.)
node_modules/

# Logs
*.log
npm-debug.log
```

### Verificar antes de cada commit que no subes nada sensible

```bash
# Ver exactamente qué archivos va a incluir el próximo commit
git status

# Si ves .env en "Changes to be committed": DETENTE y no hagas el commit
# Quítalo del staging con:
git restore --staged backend/.env

# Verificar que .env no está siendo trackeado por git
git ls-files backend/.env
# Si no imprime nada → correcto, no está trackeado
# Si imprime la ruta → está trackeado, hay que quitarlo (ver abajo)
```

### Si accidentalmente ya subiste el `.env` a GitHub

Si en algún momento hiciste `git add .env` y luego `git push`, hay que limpiarlo:

```bash
# 1. Sacar el archivo del seguimiento de git (sin borrarlo de tu disco)
git rm --cached backend/.env

# 2. Asegurarte de que está en el .gitignore
echo ".env" >> backend/.gitignore

# 3. Commit para registrar la eliminación
git add backend/.gitignore
git commit -m "fix: eliminar .env del repositorio y agregar a gitignore"

# 4. Push
git push

# 5. IMPORTANTE: cambiar todas las contraseñas y claves que estaban en ese .env
#    porque GitHub guarda historial y el archivo sigue visible en commits anteriores
```

### El flujo correcto de trabajo con credenciales

```
.env.example  →  lo subes a GitHub (es la plantilla pública)
     ↓
  Quien clone el proyecto hace:
  cp .env.example .env
  y llena sus propias credenciales
     ↓
  .env  →  NUNCA se sube (está en .gitignore)
```

---

## Herramientas que necesitas instalar antes de empezar

| Herramienta | Para qué sirve |
|-------------|----------------|
| Git + cuenta GitHub | Control de versiones y entrega del repositorio |
| Docker Desktop | Correr PostgreSQL, Redis y el backend en contenedores |
| PHP 8.2+ y Composer | Instalar Laravel y sus dependencias |
| Node.js (para Artillery) | Pruebas de carga |
| VS Code | Editor de código |

---

## Estructura de carpetas que tendrás al final

```
mi-proyecto/
├── backend/               ← Proyecto Laravel (API REST)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/   ← 8 controladores
│   │   │   └── Middleware/        ← CheckAdmin.php
│   │   └── Models/                ← 13 modelos Eloquent
│   ├── database/
│   │   ├── migrations/            ← 22 migraciones
│   │   └── seeders/               ← 5 seeders (DatabaseSeeder + 4 propios)
│   ├── backup.sql                 ← backup generado con pg_dump (Día 21)
│   └── routes/api.php             ← Todas las rutas API
├── frontend/              ← HTML + CSS + JS puro
│   ├── startbootstrap-sb-admin-gh-pages/  ← plantilla Bootstrap completa (se sube al repo)
│   │   ├── index.html          ← referencia — tu dashboard.html se basa en esto
│   │   ├── charts.html         ← referencia para gráficas (luego se elimina)
│   │   ├── tables.html         ← referencia para tablas (luego se elimina)
│   │   ├── css/                ← estilos de la plantilla (no modificar)
│   │   ├── js/                 ← JS de la plantilla (no modificar)
│   │   └── ...otros html/      ← los que no uses se eliminan al final
│   ├── css/
│   │   └── sb-admin.css        ← copiado de la plantilla para tus páginas personalizadas
│   ├── img/
│   │   └── ciudad.JPG          ← imagen de fondo para login.html [EXTRA]
│   ├── login.html              ← creado por ti fuera de la carpeta de plantilla
│   ├── register.html
│   ├── dashboard.html          ← basado en index.html de la plantilla
│   ├── incidencias.html
│   ├── nueva.html
│   ├── usuarios.html           ← [EXTRA]
│   └── js/                     ← tus archivos JS (carpeta propia, no la de la plantilla)
│       ├── api.js           ← función apiFetch compartida (API_BASE_URL = '/api')
│       ├── sb-admin.js      ← copiado de la plantilla (maneja sidebar toggle)
│       ├── notificaciones.js ← [LINEAMIENTO] badge y dropdown
│       ├── login.js
│       ├── register.js
│       ├── dashboard.js
│       ├── incidencias.js   ← incluye la vista de detalle como modal/sección
│       ├── nueva.js
│       └── usuarios.js     ← [EXTRA]
│   ← Nota: no hay auth.js separado; la verificación del token va inline en cada JS
├── capturas/              ← capturas de pantalla para el documento técnico
├── nginx/
│   └── default.conf       ← configuración de Nginx
├── artillery.yml          ← configuración de pruebas de carga
├── reporte.json           ← resultado de Artillery (generado al correr las pruebas)
├── docker-compose.yml     ← [LINEAMIENTO] orquestación de contenedores
└── .env.example           ← Variables de entorno documentadas
```

> **Sobre `sb-admin.js` y `sb-admin.css`:** son archivos de la plantilla SB Admin copiados
> a `frontend/js/` y `frontend/css/` para que tus páginas personalizadas (que están en
> `frontend/`, no dentro de la carpeta de la plantilla) puedan referenciarlos con rutas cortas.

---

## SEMANA 1 — Base de datos y configuración del entorno

### DÍA 1: Repositorio, Docker y Laravel `[DATA CENTER]` `[LINEAMIENTO]`

**Objetivo:** tener el entorno corriendo aunque sea vacío.

#### Paso 1.1 — Crear el repositorio GitHub
1. Ve a github.com → New repository
2. Nombre: `sistema-incidencias` (o el que prefieras)
3. Visibilidad: Public (para que el docente pueda ver)
4. Agrega un README inicial
5. Clona el repositorio en tu máquina: `git clone <URL>`

#### Paso 1.2 — Crear el proyecto Laravel dentro de la carpeta `backend/`
```bash
cd mi-proyecto
composer create-project laravel/laravel backend
cd backend
```
[📖 aprender.md — Qué es Laravel](aprender.md#qué-es-laravel)

#### Paso 1.3 — Instalar Laravel Sanctum (autenticación por tokens)
```bash
cd backend
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```
Sanctum crea la tabla `personal_access_tokens`. Explicación en la migración correspondiente.
[📖 aprender.md — Facades de Laravel: Auth, Hash, etc.](aprender.md#php-facades-de-laravel-hash-cache-storage-auth-db)

#### Paso 1.4 — Crear el `docker-compose.yml` en la raíz del proyecto `[DATA CENTER]` `[LINEAMIENTO]`

Crea el archivo `docker-compose.yml` en la **raíz del proyecto** (no dentro de `backend/`).

```yaml
services:

  # ── BASE DE DATOS ──────────────────────────────────────────────────────────
  db:
    image: postgres:16             # imagen oficial de PostgreSQL versión 16
    container_name: incidencias_db # nombre fijo del contenedor (útil para logs)
    ports:
      - "5433:5432"                # 5433 en tu PC → 5432 dentro del contenedor
                                   # se usa 5433 (no 5432) para evitar conflicto si
                                   # ya tienes PostgreSQL instalado localmente
    restart: always                # reinicia el contenedor si se cae
    environment:
      POSTGRES_DB:       gestion_incidencias  # nombre de la BD que crea automáticamente
      POSTGRES_USER:     admin                # usuario de la BD
      POSTGRES_PASSWORD: tu_password          # cámbialo por uno seguro
    volumes:
      - pgdata:/var/lib/postgresql/data       # persiste los datos fuera del contenedor
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U admin -d gestion_incidencias"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks:
      - incidencias_network        # red virtual que conecta todos los contenedores

  # ── PGADMIN — interfaz visual de la BD ─────────────────────────────────────
  pgadmin:                         # [EXTRA]
    image: dpage/pgadmin4
    container_name: incidencias_pgadmin
    environment:
      PGADMIN_DEFAULT_EMAIL:    admin@sistema.com
      PGADMIN_DEFAULT_PASSWORD: password123
    ports:
      - "5050:80"                  # abre en localhost:5050
    depends_on:
      - db
    networks:
      - incidencias_network

  # ── REDIS — caché ──────────────────────────────────────────────────────────
  redis:                           # [EXTRA]
    image: redis:alpine
    container_name: incidencias_redis
    ports:
      - "6379:6379"
    networks:
      - incidencias_network

  # ── BACKEND — Laravel API REST ─────────────────────────────────────────────
  backend:
    build:
      context: ./backend           # carpeta donde está el Dockerfile
      dockerfile: Dockerfile       # nombre del archivo (explícito para mayor claridad)
    command: php artisan serve --host=0.0.0.0 --port=8000
    # command: sobreescribe el CMD del Dockerfile
    # --host=0.0.0.0: escucha en todas las interfaces (no solo internamente)
    expose:
      - "8000"                     # expone el puerto SOLO dentro de la red Docker
                                   # NO a tu PC directamente — Nginx hace de intermediario
                                   # (diferencia con "ports": ports expone al exterior)
    volumes:
      - ./backend:/var/www/html    # monta tu carpeta backend/ dentro del contenedor
                                   # VENTAJA: cambios en tu código se reflejan al instante
                                   # sin necesidad de reconstruir la imagen (--build)
    depends_on:
      db:
        condition: service_healthy # espera que PostgreSQL pase el healthcheck
      redis:
        condition: service_started # espera que Redis arranque
    healthcheck:
      test: ["CMD-SHELL", "php -r 'echo 1;' || exit 1"]
      interval: 30s
      timeout: 10s
      retries: 3
    networks:
      - incidencias_network
    deploy:
      mode: replicated
      replicas: 2                  # [EXTRA] 2 copias del backend (escalamiento horizontal)

  # ── FRONTEND — Nginx sirve el HTML y balancea al backend ───────────────────
  frontend:
    image: nginx:alpine            # Nginx como servidor web y reverse proxy
    container_name: incidencias_frontend_lb
    ports:
      - "80:80"                    # abre en localhost:80 (sin número de puerto en el navegador)
    volumes:
      - ./frontend:/usr/share/nginx/html          # tus HTML van aquí
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf  # tu configuración de Nginx
    depends_on:
      - backend
    networks:
      - incidencias_network

# ── RED VIRTUAL ──────────────────────────────────────────────────────────────
networks:
  incidencias_network:
    driver: bridge                 # red tipo bridge: contenedores se ven entre sí por nombre
                                   # sin esta red, los contenedores estarían aislados

# ── VOLÚMENES ─────────────────────────────────────────────────────────────────
volumes:
  pgdata:                          # persiste los datos de PostgreSQL entre reinicios
```

**Diferencia entre `ports` y `expose`:**
- `ports: "8000:8000"` → el puerto es accesible desde tu PC (`localhost:8000`)
- `expose: "8000"` → el puerto solo es accesible desde otros contenedores de la misma red
- El backend usa `expose` porque no necesitas acceder directamente — Nginx recibe tus peticiones y las reenvía al backend internamente

[📖 aprender.md — Docker Compose: estructura y sintaxis](aprender.md#docker-compose-estructura-y-sintaxis-del-docker-composeyml)

---

#### Paso 1.4b — Crear el `Dockerfile` dentro de `backend/` `[DATA CENTER]` `[LINEAMIENTO]`

El `docker-compose.yml` usa `build: ./backend`, que busca un `Dockerfile` en esa carpeta.
Sin él, el comando `docker-compose up` falla con `no such file or directory`.

Crea el archivo `backend/Dockerfile` (sin extensión):

```dockerfile
# php:8.2-fpm = PHP versión 8.2 con FPM (FastCGI Process Manager)
# FPM es más adecuado que CLI para producción: maneja múltiples procesos PHP eficientemente
# En este proyecto el docker-compose.yml sobreescribe el comando con "php artisan serve"
FROM php:8.2-fpm

# Instala dependencias del sistema y extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    # libpq-dev: librería C de PostgreSQL (necesaria para pdo_pgsql)
    # libpng-dev, libjpeg-dev, libfreetype6-dev: necesarias para la extensión GD (imágenes)
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql gd \
    # pdo: capa de abstracción de BD (requerida por Laravel)
    # pdo_pgsql: driver para conectarse a PostgreSQL
    # gd: extensión para procesar imágenes (redimensionar, validar fotos de incidencias)
    && pecl install redis \
    # pecl: gestor de extensiones PHP — instala la extensión Redis
    # Esta extensión permite que PHP se comunique con el servidor Redis para el caché
    && docker-php-ext-enable redis
    # docker-php-ext-enable: activa la extensión recién instalada

# Copia el ejecutable de Composer sin tener que instalarlo manualmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Carpeta de trabajo dentro del contenedor
# El docker-compose monta ./backend aquí con "volumes: - ./backend:/var/www/html"
# Por eso NO hace falta "COPY . ." ni "RUN composer install" aquí:
# el código ya está disponible por el volume mount
WORKDIR /var/www/html
```

**¿Por qué NO hay `COPY . .` ni `CMD` en este Dockerfile?**

El `docker-compose.yml` tiene:
```yaml
volumes:
  - ./backend:/var/www/html    # monta el código directamente
command: php artisan serve ... # define el comando de arranque
```
- El `volume mount` reemplaza al `COPY . .` — tu código llega al contenedor por el volumen
- El `command:` reemplaza al `CMD` — el docker-compose define cómo arrancar
- Ventaja: puedes cambiar el código en tu PC y el contenedor lo ve al instante, sin `--build`

---

#### Paso 1.4c — Crear la carpeta `nginx/` y su configuración `[DATA CENTER]`

El servicio `frontend` del docker-compose monta `./nginx/default.conf` en Nginx.
Sin este archivo, el contenedor de Nginx falla al arrancar.

Crea la carpeta `nginx/` en la raíz del proyecto y dentro el archivo `default.conf`:

```nginx
# upstream: define un grupo de servidores backend al que Nginx puede redirigir
# "backend" es el nombre del servicio en docker-compose — Docker lo resuelve como hostname
upstream api_backend {
    server backend:8000;
}

server {
    listen 80;                       # Nginx escucha en el puerto 80
    server_name localhost;

    # Peticiones a / → sirve los archivos HTML del frontend
    location / {
        root /usr/share/nginx/html;  # carpeta donde están tus HTML (montada como volumen)
        index login.html dashboard.html;
        try_files $uri $uri/ =404;   # busca el archivo, luego la carpeta, si no → 404
    }

    # Peticiones a /api/ → las reenvía al backend Laravel
    location /api/ {
        proxy_pass http://api_backend/api/;
        # proxy_pass: reenvía la petición al grupo "api_backend" (backend:8000)
        # Así el navegador llama a localhost/api/... y Nginx lo manda a Laravel
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Peticiones a /storage/ → archivos subidos (fotos de incidencias)
    location /storage/ {
        proxy_pass http://api_backend/storage/;
        proxy_set_header Host      $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

**¿Por qué Nginx en lugar de acceder al backend directamente?**
- Sin Nginx: necesitarías poner `http://localhost:8000/api/...` en todo tu JS
- Con Nginx: pones `http://localhost/api/...` — Nginx intercepta y reenvía al backend
- Nginx también balancea la carga entre las 2 réplicas del backend automáticamente
- Es el patrón estándar en producción: Nginx al frente, backend(s) detrás

**Estructura final de archivos de infraestructura:**
```
Proyecto-Gestion-de-Incidencias/
├── docker-compose.yml     ← orquesta todos los contenedores
├── nginx/
│   └── default.conf       ← configuración de Nginx
├── backend/
│   ├── Dockerfile         ← cómo construir la imagen del backend
│   └── ...Laravel
└── frontend/
    └── ...HTML/JS
```

**IMPORTANTE — siempre correr desde la raíz:**
```bash
# CORRECTO
cd Proyecto-Gestion-de-Incidencias
docker-compose up -d --build

# INCORRECTO — desde dentro de backend/
cd Proyecto-Gestion-de-Incidencias/backend
docker-compose up -d    # error: no encuentra docker-compose.yml
```

#### Paso 1.5 — Configurar el `.env` del backend

Edita `backend/.env`. Las variables deben coincidir exactamente con lo que pusiste en `docker-compose.yml`:

```
APP_NAME="Gestion Incidencias UPSE"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# ── Base de datos ────────────────────────────────────────────────────────────
# IMPORTANTE: DB_HOST debe ser "db" (el nombre del servicio en docker-compose)
# NO uses "127.0.0.1" — dentro de Docker, localhost sería el propio contenedor
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=gestion_incidencias
DB_USERNAME=admin
DB_PASSWORD=tu_password_seguro

# ── Caché Redis ──────────────────────────────────────────────────────────────
# IMPORTANTE: REDIS_HOST debe ser "redis" (el nombre del servicio en docker-compose)
# Laravel 11 usa CACHE_STORE (no CACHE_DRIVER como en versiones anteriores)
CACHE_STORE=redis           # [EXTRA]
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

> **Nota crítica:** si pones `DB_HOST=127.0.0.1` o `DB_HOST=localhost`, Laravel no podrá conectarse
> a PostgreSQL desde dentro de Docker. El nombre del servicio `db` es el hostname que Docker asigna
> al contenedor de la base de datos en la red `incidencias_network`.

Crea `backend/.env.example` como copia con valores de ejemplo (sin contraseña real).
Agrega `.env` al `backend/.gitignore` (nunca subas credenciales).

#### Paso 1.6 — Arrancar el entorno y verificar que todo levanta

Corre estos comandos **desde la raíz del proyecto** (donde está el `docker-compose.yml`):

```bash
# 1. Construir las imágenes y levantar todos los contenedores
docker-compose up -d --build
# --build: reconstruye la imagen del backend con el Dockerfile
# -d: corre en segundo plano (detached), libera la terminal

# 2. Verificar que todos los servicios están corriendo
docker-compose ps
# Debes ver db, backend, redis, pgadmin todos con estado "Up"
# Si alguno dice "Exit" o "Restarting", tiene un error — revisa con:
docker-compose logs nombre_servicio
```

Una vez que todos los servicios estén `Up`, corre estos comandos **dentro del contenedor backend**:

```bash
# 3. Generar la APP_KEY de Laravel (OBLIGATORIO — sin esto Laravel da error 500)
# La APP_KEY es una clave de cifrado que Laravel necesita para sesiones y tokens
docker-compose exec backend php artisan key:generate
# Esto escribe APP_KEY=base64:... en tu .env automáticamente

# 4. Ejecutar las migraciones (crear las tablas en PostgreSQL)
docker-compose exec backend php artisan migrate
# Si es la primera vez y las tablas no existen, las crea todas

# 5. Crear el enlace simbólico para archivos subidos (fotos de incidencias)
# Sin esto, las fotos se guardan pero no se pueden mostrar en el navegador
docker-compose exec backend php artisan storage:link

# 6. Poblar la base de datos con datos de prueba
docker-compose exec backend php artisan db:seed
```

**Verificación final:**
- Abre `http://localhost/api/tipos-incidencia` en el navegador
  - El puerto 80 (Nginx) es el punto de entrada — **NO** `localhost:8000` (el backend usa `expose`, no `ports`)
  - Si ves un JSON con los tipos, el backend y Nginx están funcionando correctamente
- Abre `http://localhost:5050` para ver PgAdmin
  - Email: `admin@sistema.com` / Contraseña: `password123`
  - Agrega un servidor nuevo: host=`db`, puerto=`5432`, usuario=`admin`, contraseña=`<la que pusiste en el .env>`

**Si `php artisan migrate` da error de conexión:**
El backend arrancó antes de que PostgreSQL estuviera listo. Espera 10 segundos y vuelve a intentarlo. El healthcheck del `docker-compose.yml` previene esto normalmente, pero a veces el backend tarda en arrancar también.

```bash
# Reiniciar solo el backend si sigue fallando
docker-compose restart backend
docker-compose exec backend php artisan migrate
```

---

### DÍAS 2-3: Migraciones — Las tablas de la base de datos `[BD]` `[LINEAMIENTO]`

**Concepto clave:** las migraciones deben crearse en orden de dependencia.
Una tabla no puede referenciar (FK) a otra que aún no existe.
[📖 aprender.md — Schema Builder: métodos de Blueprint](aprender.md#php-schema-builder-métodos-de-blueprint) · [PostgreSQL SQL nativo](aprender.md#postgresql-sql-nativo-en-detalle)

**Orden exacto de creación:**

#### Grupo A — Tablas de Laravel (ya vienen, solo modificar)
Estas 3 migraciones ya existen al instalar Laravel. Las mantienes tal cual o las adaptas.

| Orden | Migración | Qué contiene |
|-------|-----------|--------------|
| 1 | `0001_01_01_000000_create_users_table` | users, password_resets, sessions |
| 2 | `0001_01_01_000001_create_cache_table` | cache, cache_locks |
| 3 | `0001_01_01_000002_create_jobs_table` | jobs, job_batches, failed_jobs |

**Modificación necesaria en `create_users_table`:** agrega la columna `rol_id` como nullable inicialmente (luego la vuelves NOT NULL después del seeder de roles). O bien crea una migración separada `add_rol_to_users_table` después de la migración de roles — ese es el orden que usamos.

#### Grupo B — Catálogos geográficos (sin dependencias entre sí excepto en cadena)

Crea cada migración con `php artisan make:migration create_<nombre>_table`
Luego edita el archivo generado.

| Orden | Migración | Depende de |
|-------|-----------|------------|
| 4 | `create_paises_table` | nada |
| 5 | `create_provincias_table` | paises |
| 6 | `create_ciudades_table` | provincias |

**Estructura de `paises`:**
```sql
-- Usando DB::statement() con SQL puro (no Schema Builder)
-- porque queremos BIGSERIAL y NOT NULL UNIQUE con sintaxis PostgreSQL explícita
CREATE TABLE paises (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

**Estructura de `provincias`:**
```sql
CREATE TABLE provincias (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    pais_id BIGINT NOT NULL REFERENCES paises(id) ON DELETE CASCADE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

**Estructura de `ciudades`:** igual que provincias pero referenciando a `provincias(id)`.

[📖 aprender.md — PostgreSQL SQL nativo](aprender.md#postgresql-sql-nativo-en-detalle)

#### Grupo C — Catálogos de tipos

| Orden | Migración | Depende de |
|-------|-----------|------------|
| 7 | `create_tipos_incidencia_table` | nada |
| 8 | `create_subtipos_incidencia_table` | tipos_incidencia |

**Estructura de `tipos_incidencia`:**
```sql
CREATE TABLE tipos_incidencia (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

**Estructura de `subtipos_incidencia`:**
```sql
CREATE TABLE subtipos_incidencia (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    tipo_id BIGINT NOT NULL REFERENCES tipos_incidencia(id) ON DELETE CASCADE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

#### Grupo D — Roles y relación con usuarios

| Orden | Migración | Depende de |
|-------|-----------|------------|
| 9 | `create_roles_table` | nada |
| 10 | `add_rol_to_users_table` | roles, users |

**Estructura de `roles`:**
```sql
CREATE TABLE roles (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,  -- 'admin', 'tecnico', 'normal'
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

**`add_rol_to_users`** usa `ALTER TABLE`:
```sql
ALTER TABLE users
    ADD COLUMN rol_id BIGINT NULL REFERENCES roles(id) ON DELETE SET NULL
-- ON DELETE SET NULL: si borras el rol, el usuario queda sin rol pero NO se borra
```

#### Grupo E — Tabla central: incidencias [LINEAMIENTO]

| Orden | Migración | Depende de |
|-------|-----------|------------|
| 11 | `create_incidencias_table` | users, subtipos_incidencia, ciudades |

**Estructura de `incidencias`** (la más importante del sistema):
```sql
CREATE TABLE incidencias (
    id BIGSERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    estado_actual VARCHAR(20) NOT NULL DEFAULT 'pendiente'
        CHECK (estado_actual IN ('pendiente','en_proceso','resuelto')),
    prioridad VARCHAR(10) NOT NULL DEFAULT 'media'
        CHECK (prioridad IN ('alta','media','baja')),
    latitud NUMERIC(10, 8),    -- coordenadas GPS con 8 decimales
    longitud NUMERIC(11, 8),
    ruta_archivo VARCHAR(500),  -- ruta al archivo subido (no el archivo mismo)
    fecha_resolucion TIMESTAMP, -- se llena automáticamente por un trigger
    usuario_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    subtipo_id BIGINT REFERENCES subtipos_incidencia(id) ON DELETE SET NULL,
    ciudad_id  BIGINT REFERENCES ciudades(id) ON DELETE SET NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

**Puntos clave de esta tabla:**
- `CHECK`: PostgreSQL valida que solo entren valores permitidos (pendiente/en_proceso/resuelto)
- `fecha_resolucion`: NO la llenas desde PHP, la llena el trigger automáticamente
- `ON DELETE SET NULL`: si el usuario o ciudad se borra, la incidencia sobrevive con el campo en NULL

#### Grupo F — Tablas de relaciones y auditoría

| Orden | Migración | Depende de | Para qué |
|-------|-----------|------------|----------|
| 12 | `create_asignaciones_incidencia_table` | incidencias, users | Muchos-a-muchos con rol |
| 13 | `create_historial_estados_table` | incidencias | Auditoría automática (trigger) [LINEAMIENTO] |
| 14 | `create_comentarios_table` | incidencias, users | Comentarios [LINEAMIENTO] |
| 15 | `create_evidencias_table` | incidencias | Archivos adjuntos |
| 16 | `create_notificaciones_table` | users | Notificaciones [LINEAMIENTO] |
| 17 | `create_bitacora_errores_table` | users | Log de errores del sistema |

**`asignaciones_incidencia`:**
```sql
CREATE TABLE asignaciones_incidencia (
    id BIGSERIAL PRIMARY KEY,
    incidencia_id BIGINT REFERENCES incidencias(id) ON DELETE CASCADE,
    usuario_id    BIGINT REFERENCES users(id) ON DELETE CASCADE,
    rol_asignado  VARCHAR(20) CHECK (rol_asignado IN ('responsable','apoyo')),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

**`historial_estados`** (la llena el trigger, no el código PHP):
```sql
CREATE TABLE historial_estados (
    id BIGSERIAL PRIMARY KEY,
    incidencia_id   BIGINT REFERENCES incidencias(id) ON DELETE CASCADE,
    usuario_id      BIGINT REFERENCES users(id) ON DELETE SET NULL,
    estado_anterior VARCHAR(20),
    estado_nuevo    VARCHAR(20),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

**`notificaciones`:**
```sql
CREATE TABLE notificaciones (
    id           BIGSERIAL PRIMARY KEY,
    usuario_id   BIGINT REFERENCES users(id) ON DELETE CASCADE,
    incidencia_id BIGINT REFERENCES incidencias(id) ON DELETE CASCADE,  -- agregada después
    mensaje      TEXT NOT NULL,
    leido        BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

**Nota:** en el proyecto original, `incidencia_id` se agregó en una migración separada
(`add_incidencia_id_to_notificaciones`). En tu replicación, como sabes de antemano que la
necesitas, puedes incluirla directamente en `create_notificaciones_table`.

#### Grupo G — SQL avanzado: triggers, procedimientos y vistas

Estas migraciones van al final porque referencian tablas que deben existir primero.
Usa `DB::unprepared()` para todo el código PL/pgSQL.
[📖 aprender.md — Trigger SQL](aprender.md#trigger-sql-historial-de-estados-automático) · [Vistas y funciones SQL](aprender.md#vistas-y-funciones-sql-avanzadas)

| Orden | Migración | Qué crea |
|-------|-----------|----------|
| 18 | `create_triggers` | 3 triggers de automatización |
| 19 | `create_personal_access_tokens_table` | tabla de tokens de Sanctum (ya existe — ver nota) |
| 20 | `create_vistas_funcion_indices_sql` | 2 vistas + 1 función + 5 índices [EXTRA puntos BD] |
| 21 | `add_incidencia_id_to_notificaciones` | columna FK si no la pusiste en el paso 16 |
| 22 | `create_procedimientos_almacenados` | 2 procedimientos almacenados [EXTRA puntos BD] |

> **Nota sobre la migración 19 (`personal_access_tokens`):** esta migración la genera
> automáticamente el comando `vendor:publish` de Sanctum del Paso 1.3. No la creas tú a mano,
> ya aparece en tu carpeta `migrations/` después de ese paso. Por eso el total llega a 22.

> **Nota sobre el orden procedimientos vs vistas:** los procedimientos almacenados
> (`resolver_incidencia`) usan la vista `v_incidencias_completas` internamente.
> Por eso las vistas (migración 20) deben existir ANTES que los procedimientos (migración 22).

**Los 3 triggers que necesitas crear:**

**Trigger 1 — `trg_registrar_cambio_estado` (AFTER UPDATE ON incidencias):**
Cada vez que cambia `estado_actual`, inserta una fila en `historial_estados` automáticamente.
El trigger es AFTER porque solo registra, no modifica la fila.

**Trigger 2 — `trg_fecha_resolucion_automatica` (BEFORE UPDATE ON incidencias):**
Si el estado cambia a 'resuelto', pone `fecha_resolucion = NOW()`.
Si vuelve de 'resuelto', limpia `fecha_resolucion = NULL`.
El trigger es BEFORE porque necesita modificar `NEW` antes de guardarlo.

**Trigger 3 — `trg_notificar_nuevo_comentario` (AFTER INSERT ON comentarios):**
Al insertar un comentario, notifica al ciudadano que reportó la incidencia.
No notifica si quien comenta es el mismo que reportó.

**Los 2 procedimientos almacenados [EXTRA]:**

**`resolver_incidencia(p_incidencia_id, p_usuario_id)`:**
- Valida que la incidencia exista y no esté ya resuelta
- Actualiza estado a 'resuelto' (el trigger llena fecha_resolucion)
- Notifica al ciudadano reportador y a todos los técnicos asignados

**`asignar_tecnico(p_incidencia_id, p_usuario_id, p_rol)`:**
- 4 validaciones: incidencia existe, usuario tiene rol tecnico/admin, no está ya asignado, rol válido
- Inserta la asignación y notifica al técnico

**Las vistas e índices [EXTRA]:**

**Vista `v_incidencias_completas`:** une incidencias con usuarios, subtipos, tipos, ciudades, provincias. Evita repetir los mismos JOINs en cada controlador.

**Vista `v_metricas_por_tipo`:** estadísticas agrupadas para el dashboard (total, pendientes, en_proceso, resueltas, promedio de días).

**Función `calcular_tiempo_resolucion(id)`:** devuelve los días que tardó en resolverse una incidencia específica.

**5 índices de rendimiento:**
- `idx_incidencias_estado` → acelera filtros por estado
- `idx_incidencias_usuario` → acelera "mis incidencias"
- `idx_comentarios_incidencia` → acelera carga de comentarios
- `idx_historial_incidencia` → acelera carga del historial
- `idx_notificaciones_usuario` → acelera carga de notificaciones

---

### DÍA 4: Modelos Eloquent — La capa de datos en PHP `[WEB]`

**Concepto:** cada modelo corresponde a una tabla. Define relaciones (`belongsTo`, `hasMany`) y qué columnas son asignables en masa (`fillable`).
[📖 aprender.md — Eloquent: métodos de consulta y CRUD](aprender.md#php-eloquent-todos-los-métodos-de-consulta-y-crud) · [Los Modelos](aprender.md#los-modelos)

**Crea los modelos en este orden** (respeta dependencias de relaciones):

```bash
php artisan make:model Pais
php artisan make:model Provincia
php artisan make:model Ciudad
php artisan make:model Role
php artisan make:model TipoIncidencia
php artisan make:model SubtipoIncidencia
php artisan make:model Incidencia
php artisan make:model AsignacionIncidencia
php artisan make:model HistorialEstado
php artisan make:model Comentario
php artisan make:model Notificacion
php artisan make:model BitacoraError
```

El modelo `User` ya existe en Laravel, solo agrega las relaciones y fillable.

> **Nota sobre `evidencias`:** la tabla `evidencias` existe en las migraciones pero no tiene modelo
> Eloquent — en el proyecto original se accede a través de relaciones en `Incidencia`
> (campo `ruta_archivo`) y con Storage directamente. No necesitas crear un modelo Evidencia.php.

**Relaciones clave a configurar:**

```php
// Pais.php
public function provincias() { return $this->hasMany(Provincia::class); }

// Provincia.php
public function pais()    { return $this->belongsTo(Pais::class); }
public function ciudades(){ return $this->hasMany(Ciudad::class); }

// Ciudad.php
public function provincia(){ return $this->belongsTo(Provincia::class); }

// Role.php
public function users(){ return $this->hasMany(User::class); }

// TipoIncidencia.php
public function subtipos(){ return $this->hasMany(SubtipoIncidencia::class, 'tipo_id'); }

// SubtipoIncidencia.php
public function tipo(){ return $this->belongsTo(TipoIncidencia::class, 'tipo_id'); }

// User.php
public function rol()        { return $this->belongsTo(Role::class); }
public function incidencias(){ return $this->hasMany(Incidencia::class, 'usuario_id'); }

// Incidencia.php — la más importante
public function usuario()    { return $this->belongsTo(User::class, 'usuario_id'); }
public function subtipo()    { return $this->belongsTo(SubtipoIncidencia::class, 'subtipo_id'); }
public function ciudad()     { return $this->belongsTo(Ciudad::class, 'ciudad_id'); }
public function asignaciones(){ return $this->hasMany(AsignacionIncidencia::class); }
public function historial()  { return $this->hasMany(HistorialEstado::class); }
public function comentarios(){ return $this->hasMany(Comentario::class); }

// Notificacion.php
public function usuario()    { return $this->belongsTo(User::class); }
public function incidencia() { return $this->belongsTo(Incidencia::class); }
```

---

### DÍA 5: Seeders — Datos iniciales obligatorios `[BD]` `[LINEAMIENTO]`

**Concepto:** los seeders insertan datos de prueba. Deben ejecutarse en orden porque respetan FK.
[📖 aprender.md — Seeders: los datos de prueba](aprender.md#seeders-los-datos-de-prueba)

**Crea los seeders:**
```bash
php artisan make:seeder RolesYUsuariosSeeder
php artisan make:seeder UbicacionSeeder
php artisan make:seeder IncidenciaSeeder
php artisan make:seeder IncidenciasDemoSeeder
```

> **Nomenclatura importante:** en este proyecto `IncidenciaSeeder` siembra los catálogos
> (tipos y subtipos de incidencia), NO incidencias reales. El nombre puede confundir,
> pero es lo que existe. `IncidenciasDemoSeeder` sí siembra incidencias de demostración
> y se llama **manualmente** con `--class`, no desde `DatabaseSeeder`.

**Orden en `DatabaseSeeder.php`:**
```php
public function run(): void
{
    $this->call([
        RolesYUsuariosSeeder::class,   // 1ro: roles (admin, tecnico, normal) y usuario admin
        UbicacionSeeder::class,         // 2do: Ecuador con 24 provincias y ciudades
        IncidenciaSeeder::class,         // 3ro: tipos y subtipos de incidencia
    ]);
    // IncidenciasDemoSeeder está omitido aquí para mantener la BD limpia en producción.
    // Para datos de demo, ejecutar manualmente:
    //   php artisan db:seed --class=IncidenciasDemoSeeder
}
```

**`RolesYUsuariosSeeder`** — crea los 3 roles y el usuario administrador:
```php
// Crear roles con firstOrCreate para no duplicar si ya existen
$admin   = Role::firstOrCreate(['nombre' => 'admin']);
$tecnico = Role::firstOrCreate(['nombre' => 'tecnico']);
$normal  = Role::firstOrCreate(['nombre' => 'normal']);

// Crear usuario administrador con contraseña hasheada
User::firstOrCreate(['email' => 'admin@sistema.com'], [
    'name'     => 'Administrador',
    'password' => Hash::make('password'),
    'rol_id'   => $admin->id,
]);
```

**`UbicacionSeeder`** — Ecuador con sus 24 provincias y ciudades principales.
Usa `firstOrCreate` en todos los registros para que el seeder sea idempotente
(puedes ejecutarlo varias veces sin duplicar datos).

**`IncidenciaSeeder`** — al menos 5 tipos de incidencia (Infraestructura, Servicios Básicos,
Alumbrado Público, Vías y Transporte, Medio Ambiente) con sus subtipos.

**`IncidenciasDemoSeeder`** — 10-20 incidencias con distintos estados, prioridades y ciudades.
Usa `faker` para generar datos aleatorios. Solo para desarrollo/demo.

Ejecutar con: `php artisan db:seed`

---

## SEMANA 2 — Backend API (Controladores y Rutas)

### DÍA 6: Autenticación — AuthController y Middleware `[WEB]` `[LINEAMIENTO]`

**Concepto:** Sanctum autentica por tokens. El usuario hace login, recibe un token,
y lo manda en el header `Authorization: Bearer TOKEN` en cada petición protegida.
[📖 aprender.md — Facades de Laravel: Auth, Hash, etc.](aprender.md#php-facades-de-laravel-hash-cache-storage-auth-db)

#### Paso 6.1 — Crear `AuthController`
```bash
php artisan make:controller Api/AuthController
```

Métodos que necesitas:

**`login(Request $request)`:**
```php
// 1. Valida que vengan email y password
$request->validate(['email' => 'required|email', 'password' => 'required']);

// 2. Intenta autenticar
if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
    return response()->json(['message' => 'Credenciales incorrectas'], 401);
}

// 3. Genera el token Sanctum
$user  = Auth::user();
$token = $user->createToken('auth_token')->plainTextToken;

// 4. Retorna token + datos del usuario + rol
return response()->json(['token' => $token, 'user' => $user->load('rol')]);
```

**`register(Request $request)`:**
```php
// Valida, crea el usuario con Hash::make($request->password), asigna rol 'normal'
// Retorna el token igual que login
```

**`logout(Request $request)`:**
```php
$request->user()->currentAccessToken()->delete();
return response()->json(['message' => 'Sesión cerrada']);
```

**`me(Request $request)`:**
```php
return response()->json($request->user()->load('rol'));
```

#### Paso 6.2 — Crear `CheckAdmin` Middleware
```bash
php artisan make:middleware CheckAdmin
```

```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();
    if (!$user || !$user->rol || $user->rol->nombre !== 'admin') {
        return response()->json(['message' => 'Acceso denegado'], 403);
    }
    return $next($request);
}
```

Registra el middleware en `bootstrap/app.php` o `Kernel.php` (según versión de Laravel):
```php
'admin' => \App\Http\Middleware\CheckAdmin::class,
```

#### Paso 6.3 — Configurar `routes/api.php` (rutas de autenticación)
```php
// Rutas públicas (no requieren token)
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Rutas protegidas con Sanctum (requieren token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    // ... aquí irán todos los demás endpoints
});
```

[📖 aprender.md — Las Rutas](aprender.md#las-rutas)

---

### DÍAS 7-8: Controladores principales `[WEB]` `[LINEAMIENTO]`

#### `CatalogoController` — Datos para poblar formularios
```bash
php artisan make:controller Api/CatalogoController
```

Endpoints:
- `GET /api/tipos-incidencia` → devuelve tipos con sus subtipos anidados (`->with('subtipos')`)
- `GET /api/ciudades` → devuelve ciudades con su provincia y país
- `GET /api/provincias` → lista de provincias
- `GET /api/paises` → lista de países

```php
public function tiposConSubtipos()
{
    return response()->json(TipoIncidencia::with('subtipos')->get());
}
```

#### `IncidenciaController` — El controlador más importante [LINEAMIENTO]
```bash
php artisan make:controller Api/IncidenciaController
```

**Método `index` (listar con filtros):**
```php
public function index(Request $request)
{
    $query = Incidencia::with(['usuario', 'subtipo.tipo', 'ciudad'])
        ->orderBy('created_at', 'desc');

    // Filtros opcionales
    if ($request->filled('estado'))   $query->where('estado_actual', $request->estado);
    if ($request->filled('prioridad'))$query->where('prioridad', $request->prioridad);
    if ($request->filled('busqueda')) $query->where('titulo', 'ilike', '%'.$request->busqueda.'%');
    if ($request->filled('tipo_id'))  $query->whereHas('subtipo', fn($q) => $q->where('tipo_id', $request->tipo_id));
    if ($request->filled('ciudad_id'))$query->where('ciudad_id', $request->ciudad_id);

    // Control de acceso: usuario normal solo ve sus propias incidencias
    $user = $request->user();
    if ($user->rol && $user->rol->nombre === 'normal') {
        $query->where('usuario_id', $user->id);
    }

    return response()->json($query->paginate(10)); // paginación [EXTRA]
}
```

[📖 aprender.md — Eloquent avanzado](aprender.md#php-eloquent-avanzado)

**Método `store` (crear incidencia con foto):**
```php
public function store(Request $request)
{
    $request->validate([
        'titulo'      => 'required|min:5',
        'subtipo_id'  => 'required|exists:subtipos_incidencia,id',
        'ciudad_id'   => 'required|exists:ciudades,id',
        'latitud'     => 'required|numeric',
        'longitud'    => 'required|numeric',
        'archivo'     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    $ruta = null;
    if ($request->hasFile('archivo')) {
        // Storage::disk('public') guarda en storage/app/public/
        $ruta = $request->file('archivo')->store('incidencias', 'public');
    }

    $incidencia = Incidencia::create([
        'titulo'       => $request->titulo,
        'descripcion'  => $request->descripcion,
        'subtipo_id'   => $request->subtipo_id,
        'ciudad_id'    => $request->ciudad_id,
        'latitud'      => $request->latitud,
        'longitud'     => $request->longitud,
        'prioridad'    => $request->prioridad ?? 'media',
        'usuario_id'   => $request->user()->id,
        'ruta_archivo' => $ruta,
    ]);

    return response()->json($incidencia, 201);
}
```

**Método `update` (editar estado y otros campos):**
```php
public function update(Request $request, $id)
{
    $incidencia = Incidencia::findOrFail($id);

    // Verificar que el usuario tiene permiso para editar esta incidencia
    $user = $request->user();
    if ($user->rol->nombre === 'normal' && $incidencia->usuario_id !== $user->id) {
        return response()->json(['message' => 'Sin permiso'], 403);
    }

    $incidencia->update($request->only(['titulo', 'descripcion', 'estado_actual', 'prioridad']));
    // El trigger fn_registrar_cambio_estado registrará el cambio en historial_estados
    // El trigger fn_fecha_resolucion_automatica llenará fecha_resolucion si estado = 'resuelto'

    return response()->json($incidencia);
}
```

**Método `destroy` (eliminar con archivo):**
```php
public function destroy(Request $request, $id)
{
    $incidencia = Incidencia::findOrFail($id);

    // Solo admin puede eliminar
    if ($request->user()->rol->nombre !== 'admin') {
        return response()->json(['message' => 'Sin permiso'], 403);
    }

    // Borrar el archivo físico antes de borrar la fila
    if ($incidencia->ruta_archivo) {
        Storage::disk('public')->delete($incidencia->ruta_archivo);
    }

    $incidencia->delete();
    return response()->json(['message' => 'Incidencia eliminada']);
}
```

[📖 aprender.md — Facades: Storage](aprender.md#php-facades-de-laravel-hash-cache-storage-auth-db) · [Eloquent: todos los métodos](aprender.md#php-eloquent-todos-los-métodos-de-consulta-y-crud)

#### `ComentarioController` [LINEAMIENTO]
```bash
php artisan make:controller Api/ComentarioController
```

Endpoints:
- `GET /api/incidencias/{id}/comentarios` → lista comentarios con usuario
- `POST /api/incidencias/{id}/comentarios` → crea comentario (el trigger notifica al reportador)

```php
public function store(Request $request, $incidenciaId)
{
    $request->validate(['contenido' => 'required']);
    $comentario = Comentario::create([
        'incidencia_id' => $incidenciaId,
        'usuario_id'    => $request->user()->id,
        'contenido'     => $request->contenido,
    ]);
    // El trigger trg_notificar_nuevo_comentario notifica automáticamente
    return response()->json($comentario->load('usuario'), 201);
}
```

#### `AsignacionController` [LINEAMIENTO]
```bash
php artisan make:controller Api/AsignacionController
```

Endpoints:
- `GET /api/incidencias/{id}/asignaciones` → lista técnicos asignados
- `POST /api/incidencias/{id}/asignaciones` → asigna técnico (usando el procedimiento almacenado)
- `DELETE /api/incidencias/{id}/asignaciones/{asignacion_id}` → quita asignación

```php
public function store(Request $request, $incidenciaId)
{
    $request->validate(['usuario_id' => 'required|exists:users,id', 'rol' => 'nullable|in:responsable,apoyo']);
    $rol = $request->rol ?? 'responsable';

    // Llamar al procedimiento almacenado (que valida todo internamente)
    DB::statement('CALL asignar_tecnico(?, ?, ?)', [(int)$incidenciaId, (int)$request->usuario_id, $rol]);
    return response()->json(['message' => 'Técnico asignado'], 201);
}
```

#### `NotificacionController` [LINEAMIENTO]
```bash
php artisan make:controller Api/NotificacionController
```

Endpoints:
- `GET /api/notificaciones` → lista notificaciones del usuario autenticado
- `PUT /api/notificaciones/{id}/leer` → marca como leída
- `PUT /api/notificaciones/leer-todas` → marca todas como leídas [EXTRA]

```php
public function index(Request $request)
{
    return response()->json(
        Notificacion::where('usuario_id', $request->user()->id)
            ->with('incidencia')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
    );
}
```

#### `DashboardController` — Métricas y gráficas [LINEAMIENTO]
```bash
php artisan make:controller Api/DashboardController
```

Usa la **vista SQL** `v_metricas_por_tipo` que ya creaste:
```php
public function index(Request $request)
{
    // Usar caché de Redis [EXTRA]: si ya calculamos estas métricas hace menos de 5 min, retornar la versión guardada
    return Cache::remember('dashboard_metricas', 300, function () {
        return [
            'por_estado'   => DB::table('incidencias')->select('estado_actual', DB::raw('COUNT(*) as total'))->groupBy('estado_actual')->get(),
            'por_tipo'     => DB::table('v_metricas_por_tipo')->get(),    // usa la vista SQL
            'por_ciudad'   => DB::table('v_incidencias_completas')->select('ciudad_nombre', DB::raw('COUNT(*) as total'))->groupBy('ciudad_nombre')->orderBy('total','desc')->take(5)->get(),
            'total'        => Incidencia::count(),
            'pendientes'   => Incidencia::where('estado_actual','pendiente')->count(),
            'en_proceso'   => Incidencia::where('estado_actual','en_proceso')->count(),
            'resueltos'    => Incidencia::where('estado_actual','resuelto')->count(),
        ];
    });
}
```

[📖 aprender.md — Facades: Cache](aprender.md#php-facades-de-laravel-hash-cache-storage-auth-db) · [Vistas y funciones SQL](aprender.md#vistas-y-funciones-sql-avanzadas)

#### `UserController` — Gestión de usuarios [EXTRA]
```bash
php artisan make:controller Api/UserController
```

Solo accesible para admin. Lista usuarios, cambia roles.
```php
public function index() { return User::with('rol')->get(); }
public function update(Request $request, $id) {
    $user = User::findOrFail($id);
    $user->update($request->only(['rol_id']));
    return response()->json($user->load('rol'));
}
```

---

### DÍA 9: Completar `routes/api.php` `[WEB]` `[LINEAMIENTO]`

Organiza todas las rutas en grupos lógicos. Este es el `routes/api.php` real del proyecto:

```php
use App\Http\Controllers\Api\{
    AuthController, IncidenciaController, ComentarioController,
    AsignacionController, NotificacionController, CatalogoController,
    DashboardController, UserController
};

// ── RUTAS PÚBLICAS (sin token) ──────────────────────────────────────────────
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// ── RUTAS AUTENTICADAS (requieren token Sanctum) ────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Datos del usuario actual
    // NOTA: la ruta es /user (no /me) — retorna usuario con relación 'role' cargada
    Route::get('/user', fn(Request $request) => $request->user()->load('role'));

    // ── SOLO ADMIN ──────────────────────────────────────────────────────────
    Route::middleware('admin')->group(function () {
        Route::get('/usuarios',           [UserController::class, 'index']);
        Route::post('/usuarios',          [UserController::class, 'store']);
        // Route Model Binding: {user} busca User por id automáticamente
        Route::put('/usuarios/{user}',    [UserController::class, 'update']);
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy']);
    });

    // Dashboard — método se llama obtenerMetricas() no index()
    Route::get('/dashboard', [DashboardController::class, 'obtenerMetricas']);

    // Catálogos — dentro del grupo auth (requieren token)
    // NOTA: están dentro de auth:sanctum, no son públicas
    Route::get('/tipos-incidencia',                        [CatalogoController::class, 'tiposIncidencia']);
    Route::get('/ciudades',                                [CatalogoController::class, 'ciudades']);
    Route::get('/provincias',                              [CatalogoController::class, 'provincias']);
    Route::get('/provincias/{provincia}/ciudades',         [CatalogoController::class, 'ciudadesPorProvincia']);

    // Notificaciones
    Route::get('/notificaciones',              [NotificacionController::class, 'index']);
    Route::put('/notificaciones/{id}/leer',    [NotificacionController::class, 'marcarLeida']);

    // ── INCIDENCIAS CRUD ────────────────────────────────────────────────────
    // apiResource genera las 5 rutas estándar (index, store, show, update, destroy)
    // en una sola línea, es más limpio que declararlas individualmente
    Route::apiResource('incidencias', IncidenciaController::class);

    // Sub-recursos de incidencias
    Route::get('/incidencias/{id}/historial',                        [IncidenciaController::class, 'historial']);
    Route::get('/incidencias/{id}/comentarios',                      [ComentarioController::class, 'index']);
    Route::post('/incidencias/{id}/comentarios',                     [ComentarioController::class, 'store']);
    Route::get('/incidencias/{id}/asignaciones',                     [AsignacionController::class, 'index']);
    // Las rutas de asignación llevan ->middleware('admin') individual (no agrupado)
    Route::post('/incidencias/{id}/asignaciones',                    [AsignacionController::class, 'store'])->middleware('admin');
    Route::delete('/incidencias/{id}/asignaciones/{asignacion_id}',  [AsignacionController::class, 'destroy'])->middleware('admin');
});
```

**Diferencias clave respecto a implementaciones típicas de Laravel:**
- `/user` en vez de `/me` para obtener el usuario actual
- `Route::apiResource()` en vez de 5 rutas individuales para incidencias
- Los catálogos (tipos, ciudades) requieren token — no son públicos
- El método del dashboard se llama `obtenerMetricas()`, no `index()`

---

### DÍA 10: Pruebas PHPUnit — Calidad de software `[CALIDAD]` `[LINEAMIENTO]`

**Concepto:** las pruebas automatizadas verifican que el código funciona correctamente.
[📖 aprender.md — PHPUnit: cómo se testea el backend](aprender.md#phpunit-cómo-se-testea-el-backend)

```bash
php artisan make:test IncidenciaApiTest
```

**Los 10 tests obligatorios que necesitas escribir:**

```php
// tests/Feature/IncidenciaApiTest.php
public function test_login_correcto_retorna_token()
{
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);
    $response->assertStatus(200)->assertJsonStructure(['token']);
}

public function test_login_incorrecto_retorna_401()
{
    $response = $this->postJson('/api/login', ['email' => 'no@existe.com', 'password' => 'wrong']);
    $response->assertStatus(401);
}

public function test_crear_incidencia_valida_retorna_201()
{
    $user = User::factory()->create(); // usuario autenticado
    $response = $this->actingAs($user)->postJson('/api/incidencias', [
        'titulo'     => 'Bache en calle principal',
        'subtipo_id' => 1,
        'ciudad_id'  => 1,
        'latitud'    => -0.2295,
        'longitud'   => -78.5243,
    ]);
    $response->assertStatus(201);
}

public function test_crear_incidencia_sin_titulo_retorna_422() { ... }
public function test_actualizar_estado_incidencia()            { ... }
public function test_eliminar_incidencia_borra_archivo()       { ... }
public function test_agregar_comentario()                      { ... }
public function test_obtener_historial()                       { ... }
public function test_dashboard_retorna_metricas()              { ... }
public function test_usuario_normal_no_puede_eliminar_ajena()  { ... }
```

Ejecutar: `php artisan test --testdox`
Guarda el output como evidencia para el documento técnico.

---

## SEMANA 3 — Frontend (HTML + JS) `[WEB]`

**Concepto general del frontend:**
- Todo el frontend son archivos `.html` + `.js` **sin framework** (HTML/CSS/JS puro)
- Se comunica con el backend mediante `fetch()` a la API REST
- El archivo `api.js` centraliza la función `apiFetch()` que agrega el token automáticamente
- Bootstrap 5 maneja el CSS y los componentes visuales (modales, tablas, badges)
- `notificaciones.js` se incluye en todas las páginas autenticadas

[📖 aprender.md — El DOM](aprender.md#javascript-el-dom-y-cómo-manipular-la-página) · [fetch y FormData](aprender.md#javascript-fetch-formdata-localstorage-urlsearchparams-date-math-object) · [Leaflet.js](aprender.md#javascript-leafletjs-referencia-completa) · [Chart.js](aprender.md#javascript-chartjs-referencia-completa) · [Bootstrap 5](aprender.md#bootstrap-5-clases-y-componentes-usados-en-el-proyecto)

---

### DÍA 11 (primera parte): Instalar la plantilla SB Admin `[WEB]`

**Antes de escribir una sola línea de HTML, primero pega la plantilla completa.**
Este es el orden correcto porque todas tus páginas van a basarse en su estructura.

#### Paso 11.0 — Descargar e incluir la plantilla en el repositorio

1. Descarga la plantilla **SB Admin** desde: `https://startbootstrap.com/template/sb-admin`
   (botón "Free Download" → descarga un `.zip`)

2. Descomprime el zip y copia la carpeta entera dentro de `frontend/`:
   ```
   frontend/
   └── startbootstrap-sb-admin-gh-pages/   ← la carpeta que pegaste
       ├── index.html
       ├── css/
       ├── js/
       └── ...
   ```

3. Agrégala al repositorio:
   ```bash
   git add frontend/startbootstrap-sb-admin-gh-pages/
   git commit -m "feat: agregar plantilla SB Admin como base del frontend"
   ```
   **Sí se sube al repo** — no es como `vendor/` porque no hay comando para reinstalarla automáticamente.

4. Abre `frontend/startbootstrap-sb-admin-gh-pages/index.html` en el navegador y verifica
   que se ve correctamente (sidebar, topnav, tarjetas). Así confirmas que la plantilla funciona.

#### Paso 11.1 — Entender qué archivos de la plantilla usarás y cuáles no

Abre la carpeta y revisa el contenido. Típicamente SB Admin trae:

| Archivo de la plantilla | Qué harás con él |
|-------------------------|------------------|
| `index.html` | Base para tu `dashboard.html` — copia su estructura |
| `charts.html` | Referencia para ver cómo se usan las gráficas — luego eliminar |
| `tables.html` | Referencia para ver cómo se hacen tablas — luego eliminar |
| `login.html` (si tiene) | Base para tu `login.html` |
| `404.html`, `blank.html`, etc. | Eliminar cuando termines, no los necesitas |
| `css/`, `js/`, `assets/` | **No tocar** — son los estilos e iconos de la plantilla |

#### Paso 11.2 — Estrategia de trabajo con la plantilla

**No modificas los archivos de la plantilla directamente.** El flujo correcto es:

```
plantilla/index.html  →  copias su estructura  →  creas tu dashboard.html
plantilla/index.html  →  copias su estructura  →  creas tu incidencias.html
...etc
```

Tus páginas van en `frontend/` (al mismo nivel que la carpeta de la plantilla, no dentro).
Tus JS van en `frontend/js/` (carpeta propia, separada del `js/` de la plantilla).

En cada HTML tuyo, los `<link>` y `<script>` apuntan a la plantilla así:
```html
<!-- CSS de la plantilla (ruta relativa desde tu html hasta la plantilla) -->
<link href="startbootstrap-sb-admin-gh-pages/css/styles.css" rel="stylesheet" />

<!-- Tu JS propio -->
<script src="js/dashboard.js"></script>
```

#### Paso 11.3 — Eliminar archivos innecesarios de la plantilla al final

Cuando ya tengas todas tus páginas listas (al final de la Semana 3), limpia la plantilla:
```bash
# Elimina los HTML de la plantilla que no usaste
rm frontend/startbootstrap-sb-admin-gh-pages/charts.html
rm frontend/startbootstrap-sb-admin-gh-pages/tables.html
rm frontend/startbootstrap-sb-admin-gh-pages/404.html
# etc — deja solo index.html si lo usas como referencia, o elimínalo también

git add -A
git commit -m "chore: eliminar páginas de plantilla no utilizadas"
```

---

### DÍA 11 (segunda parte): Archivos JS compartidos (base de todo el frontend)

Estos archivos van en `frontend/js/` y se incluyen en todas las páginas.

#### `api.js` — Función centralizada para llamadas a la API

> **IMPORTANTE sobre la URL base:** usa `/api` (relativa), NO `http://localhost:8000/api`.
> El backend usa `expose` en Docker (no `ports`), así que solo es accesible a través de Nginx
> en el puerto 80. Con la URL relativa `/api`, Nginx intercepta y reenvía al backend
> sin importar en qué puerto esté corriendo.

```javascript
// URL relativa — funciona con Nginx como intermediario
// NO uses 'http://localhost:8000/api' porque el backend no está expuesto directamente
const API_BASE_URL = '/api';

async function apiFetch(endpoint, options) {
    if (!options) options = {};

    const cabeceras = {
        'Accept': 'application/json'
    };

    // Si el body es FormData (tiene archivos), no pongas Content-Type
    // El navegador lo establece automáticamente con el boundary correcto
    if (!(options.body instanceof FormData)) {
        cabeceras['Content-Type'] = 'application/json';
    }

    // Agregar token de autenticación si el usuario está logueado
    const token = localStorage.getItem('token');
    if (token) {
        cabeceras['Authorization'] = 'Bearer ' + token;
    }

    const respuesta = await fetch(API_BASE_URL + endpoint, {
        method: options.method || 'GET',
        headers: cabeceras,
        body: options.body || null
    });

    const datos = await respuesta.json();

    if (!respuesta.ok) {
        throw { status: respuesta.status, data: datos };
    }

    return datos;
}
```

[📖 aprender.md — fetch, FormData, localStorage y más](aprender.md#javascript-fetch-formdata-localstorage-urlsearchparams-date-math-object)

#### `auth.js` — Protección de páginas y datos del usuario
```javascript
function verificarAuth() {
    const token = localStorage.getItem('token');
    if (!token) {
        window.location.href = '/login.html';
        return null;
    }
    return JSON.parse(localStorage.getItem('usuario') || '{}');
}

function cerrarSesion() {
    apiFetch('/logout', { method: 'POST' });
    localStorage.removeItem('token');
    localStorage.removeItem('usuario');
    window.location.href = '/login.html';
}
```

#### `notificaciones.js` — Badge y dropdown en el navbar [LINEAMIENTO]
```javascript
async function cargarNotificaciones() {
    const response = await apiFetch('/notificaciones');
    const data     = await response.json();

    // Actualizar badge con número de no leídas
    const noLeidas = data.filter(n => !n.leido).length;
    document.getElementById('badge-notif').textContent = noLeidas || '';

    // Poblar dropdown
    const lista = document.getElementById('lista-notif');
    lista.innerHTML = data.map(n => `
        <a class="dropdown-item ${n.leido ? '' : 'fw-bold'}" href="#"
           onclick="marcarLeida(${n.id}, ${n.incidencia_id})">
            ${n.mensaje}
        </a>
    `).join('');
}

async function marcarLeida(id, incidenciaId) {
    await apiFetch(`/notificaciones/${id}/leer`, { method: 'PUT' });
    if (incidenciaId) window.location.href = `/incidencias.html?ver=${incidenciaId}`;
    else cargarNotificaciones();
}

// Carga inicial y auto-recarga cada 60 segundos
cargarNotificaciones();
setInterval(cargarNotificaciones, 60000);
```

---

### DÍA 12: `login.html` y `login.js`

**`login.html`:**
- Formulario Bootstrap con campos email y password
- Fondo de imagen de ciudad con overlay oscuro [EXTRA]
- Link a la página de registro

**`login.js`:**
```javascript
document.getElementById('form-login').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email    = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    const response = await fetch(`${API_BASE}/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
    });

    if (response.ok) {
        const data = await response.json();
        localStorage.setItem('token',   data.token);
        localStorage.setItem('usuario', JSON.stringify(data.user));
        window.location.href = '/dashboard.html';
    } else {
        // Mostrar mensaje de error
        document.getElementById('error-msg').textContent = 'Credenciales incorrectas';
    }
});
```

**`register.html` y `register.js`:** igual que login pero con campos adicionales (nombre, confirmación de contraseña) y llama a `POST /api/register`.

---

### DÍAS 13-14: `incidencias.html` y `incidencias.js`

**Esta es la página más compleja del frontend.**

**`incidencias.html` — estructura:**
- Navbar con badge de notificaciones
- Barra de filtros: buscador de texto, selector de estado, selector de prioridad, selector de tipo [LINEAMIENTO]
- Filtros de fecha desde/hasta [EXTRA]
- Botón "Nueva Incidencia" (abre modal)
- Tabla con columnas: Título, Estado (badge de color), Prioridad, Tipo, Ciudad, Fecha, Acciones
- Paginación: botones Anterior / Siguiente [EXTRA]
- Modal de creación de incidencia con mapa Leaflet [EXTRA: mapa]
- Modal de edición de incidencia

**`incidencias.js` — flujo principal:**
```javascript
let paginaActual  = 1;
let filtrosActivos = {};

// 1. Cargar incidencias con filtros y paginación
async function cargarIncidencias() {
    const params = new URLSearchParams({
        page: paginaActual,
        ...filtrosActivos
    });
    const response = await apiFetch(`/incidencias?${params}`);
    const data     = await response.json();

    // data.data → array de incidencias
    // data.current_page, data.last_page → para la paginación
    renderizarTabla(data.data);
    actualizarPaginacion(data);
}

// 2. Poblar selects de ciudad y tipo/subtipo al cargar la página
async function cargarCatalogos() {
    const [tiposRes, ciudadesRes] = await Promise.all([
        apiFetch('/tipos-incidencia'),
        apiFetch('/ciudades'),
    ]);
    const tipos   = await tiposRes.json();
    const ciudades = await ciudadesRes.json();
    // Llenar los <select> del formulario
}

// 3. Mapa Leaflet en el modal de nueva incidencia [EXTRA]
function inicializarMapa() {
    const map = L.map('mapa').setView([-0.2295, -78.5243], 13); // Ecuador
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    let marker = null;
    map.on('click', (e) => {
        const { lat, lng } = e.latlng;
        if (marker) marker.setLatLng([lat, lng]);
        else        marker = L.marker([lat, lng]).addTo(map);

        // Llenar los inputs ocultos con las coordenadas
        document.getElementById('latitud').value  = lat;
        document.getElementById('longitud').value = lng;
    });
}
```

**Control de visibilidad por rol [LINEAMIENTO]:**
```javascript
const usuario = JSON.parse(localStorage.getItem('usuario'));
const esAdmin = usuario?.rol?.nombre === 'admin';

// En la tabla, mostrar botón Eliminar solo para admin
filas.forEach(incidencia => {
    const acciones = esAdmin
        ? `<button onclick="eliminar(${incidencia.id})">Eliminar</button>`
        : '';
    // ...
});
```

[📖 aprender.md — Leaflet.js referencia completa](aprender.md#javascript-leafletjs-referencia-completa) · [fetch y URLSearchParams](aprender.md#javascript-fetch-formdata-localstorage-urlsearchparams-date-math-object) · [El DOM](aprender.md#javascript-el-dom-y-cómo-manipular-la-página)

---

### DÍAS 15-16: `nueva.html` (o modal) — Crear incidencia

Si usas página separada en vez de modal, el flujo es el mismo.

**Validaciones visuales que debes implementar [LINEAMIENTO]:**
```javascript
function validarFormulario() {
    const errores = [];

    if (titulo.length < 5)
        errores.push('El título debe tener al menos 5 caracteres');
    if (!ciudadId)
        errores.push('Selecciona una ciudad');
    if (!categoriaId)
        errores.push('Selecciona una categoría');
    if (!latitud || !longitud)
        errores.push('Marca la ubicación en el mapa');
    if (latitud < -5 || latitud > 2)     // Rango aproximado de Ecuador
        errores.push('La ubicación debe estar en Ecuador');
    if (!archivo)
        errores.push('Adjunta una fotografía');
    if (archivo && !['image/jpeg','image/png'].includes(archivo.type))
        errores.push('Solo se permiten archivos JPG o PNG');
    if (archivo && archivo.size > 2 * 1024 * 1024)
        errores.push('La imagen no puede superar 2 MB');

    if (errores.length > 0) {
        // Mostrar con Bootstrap alert-danger, NO con alert()
        document.getElementById('errores').innerHTML =
            `<div class="alert alert-danger"><ul>${errores.map(e => `<li>${e}</li>`).join('')}</ul></div>`;
        return false;
    }
    return true;
}
```

**Enviar con FormData (para incluir el archivo):**
```javascript
const formData = new FormData();
formData.append('titulo',      titulo);
formData.append('subtipo_id',  subtipoId);
formData.append('ciudad_id',   ciudadId);
formData.append('latitud',     latitud);
formData.append('longitud',    longitud);
formData.append('descripcion', descripcion);
if (archivo) formData.append('archivo', archivo);

// apiFetch detecta FormData y omite Content-Type automáticamente
const response = await apiFetch('/incidencias', { method: 'POST', body: formData });
```

[📖 aprender.md — fetch y FormData](aprender.md#javascript-fetch-formdata-localstorage-urlsearchparams-date-math-object) · [Validaciones del lado del cliente](aprender.md#validaciones-del-lado-del-cliente-por-qué-y-cómo)

---

### DÍA 17 (continuación de incidencias.js): Vista de detalle dentro de `incidencias.html`

> **No existe `detalle.html` como página separada.** El proyecto original implementa la vista
> de detalle como un **modal o sección** dentro de `incidencias.html`, que se activa al hacer
> clic en una fila de la tabla. El parámetro `?ver=123` en la URL puede usarse para abrir
> el detalle al cargar la página (por ejemplo, al llegar desde una notificación).

**Agrega a `incidencias.js` la función de detalle:**

**Secciones que debe mostrar el modal/detalle:**
1. **Datos generales:** título, descripción, estado (badge), prioridad, fecha, reportado por
2. **Foto adjunta** (si tiene) — `<img src="/storage/incidencias/archivo.jpg">`
3. **Mapa Leaflet** con pin fijo en las coordenadas [EXTRA]
4. **Historial de estados** en lista [LINEAMIENTO]
5. **Técnicos asignados** con su rol [LINEAMIENTO]
6. **Formulario de asignación** (solo visible para admin)
7. **Comentarios** (lista + formulario para agregar) [LINEAMIENTO]
8. **Cambio de estado** (admin siempre, usuario normal solo en sus propias)

```javascript
async function cargarDetalle(id) {
    // Cargar en paralelo para mayor velocidad (Promise.all)
    const [incidencia, historial, comentarios, asignaciones] = await Promise.all([
        apiFetch(`/incidencias/${id}`),
        apiFetch(`/incidencias/${id}/historial`),
        apiFetch(`/incidencias/${id}/comentarios`),
        apiFetch(`/incidencias/${id}/asignaciones`),
    ]);

    // Llenar los campos del modal con los datos
    document.getElementById('detalle-titulo').textContent = incidencia.titulo;
    // ... etc

    // Mapa de ubicación con Leaflet [EXTRA]
    const map = L.map('mapa-detalle').setView([incidencia.latitud, incidencia.longitud], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    L.marker([incidencia.latitud, incidencia.longitud]).addTo(map);
}

// Detectar ?ver=ID en la URL (para llegar desde notificaciones)
const params = new URLSearchParams(window.location.search);
if (params.get('ver')) {
    cargarDetalle(params.get('ver'));
    // abrir el modal de detalle
}
```

[📖 aprender.md — Leaflet.js referencia completa](aprender.md#javascript-leafletjs-referencia-completa)

---

### DÍA 18: `dashboard.html` y `dashboard.js` [LINEAMIENTO]

**Tarjetas de métricas:**
```html
<div class="card text-white bg-primary">
    <div class="card-body">
        <h5>Total Incidencias</h5>
        <h2 id="total-incidencias">--</h2>
    </div>
</div>
```

**Gráficas con Chart.js [LINEAMIENTO]:**
```javascript
// 1. Gráfica de barras por estado
const ctxEstado = document.getElementById('grafica-estado').getContext('2d');
const graficaEstado = new Chart(ctxEstado, {
    type: 'bar',
    data: {
        labels: ['Pendiente', 'En Proceso', 'Resuelto'],
        datasets: [{
            label: 'Incidencias',
            data: [0, 0, 0],
            backgroundColor: ['#ffc107', '#0d6efd', '#198754'],
        }]
    },
    options: { responsive: true }
});

// 2. Gráfica de dona por tipo
const ctxTipo = document.getElementById('grafica-tipo').getContext('2d');
const graficaTipo = new Chart(ctxTipo, { type: 'doughnut', ... });

// Actualizar sin recrear la gráfica (evita parpadeo)
async function actualizarDashboard() {
    const response = await apiFetch('/dashboard');
    const data     = await response.json();

    graficaEstado.data.datasets[0].data = [
        data.pendientes, data.en_proceso, data.resueltos
    ];
    graficaEstado.update(); // actualiza sin destruir la gráfica
}
```

[📖 aprender.md — Chart.js referencia completa](aprender.md#javascript-chartjs-referencia-completa)

---

### DÍA 19: `usuarios.html` y `usuarios.js` [EXTRA]

Página de gestión de usuarios, solo visible para admin.

- Tabla con todos los usuarios, su email y rol actual
- Selector de rol para cambiar el rol de cada usuario
- Botón para eliminar usuarios

```javascript
async function cargarUsuarios() {
    const response = await apiFetch('/usuarios');
    const usuarios = await response.json();
    // renderizar tabla
}

async function cambiarRol(userId, nuevoRolId) {
    await apiFetch(`/usuarios/${userId}`, {
        method: 'PUT',
        body: JSON.stringify({ rol_id: nuevoRolId }),
    });
    cargarUsuarios();
}
```

---

## SEMANA 4 — Calidad, infraestructura y documentación

### DÍA 20: Artillery — Pruebas de carga `[CALIDAD]` `[LINEAMIENTO]`

```bash
npm install -g artillery
```

Crea el archivo `artillery.yml` en la **raíz del proyecto**:
```yaml
config:
  target: "http://localhost"    # Nginx en puerto 80 — NO localhost:8000
  phases:
    - duration: 30
      arrivalRate: 2            # 2 usuarios nuevos por segundo (carga inicial)
      name: "Carga inicial"
    - duration: 60
      arrivalRate: 5            # 5 usuarios por segundo (carga sostenida)
      name: "Carga sostenida"
  http:
    timeout: 30
  defaults:
    headers:
      Accept: "application/json"
      Content-Type: "application/json"

scenarios:
  - name: "Consulta de endpoints autenticados"
    flow:
      - get:
          url: "/api/incidencias"
          headers:
            Authorization: "Bearer TU_TOKEN_AQUI"
      - get:
          url: "/api/dashboard"
          headers:
            Authorization: "Bearer TU_TOKEN_AQUI"
      - get:
          url: "/api/notificaciones"
          headers:
            Authorization: "Bearer TU_TOKEN_AQUI"
```

> **Sobre el token:** Artillery no hace login automático en el proyecto actual. Genera un token
> manualmente (logueándote en la app o via Postman en `POST /api/login`) y cópialo en el YAML.
> El token es válido mientras no hagas logout.

```bash
# Corre la prueba y guarda el resultado en reporte.json (evidencia para el documento técnico)
artillery run artillery.yml --output reporte.json
```

Documenta los resultados (requests totales, exitosos, tiempo de respuesta p95, p99).
El archivo `reporte.json` queda en la raíz del proyecto junto al `artillery.yml`.

---

### DÍA 21: Backup SQL y verificación de BD `[BD]` `[LINEAMIENTO]`

**Generar backup:**
```bash
# -U admin: usuario definido en POSTGRES_USER del docker-compose
# gestion_incidencias: nombre de BD definido en POSTGRES_DB
docker-compose exec db pg_dump -U admin gestion_incidencias > backend/backup.sql
```

**Verificar que los triggers funcionan:**
En PgAdmin (http://localhost:5050), ejecuta:
```sql
-- 1. Cambiar estado de una incidencia y verificar historial
UPDATE incidencias SET estado_actual = 'en_proceso' WHERE id = 1;
SELECT * FROM historial_estados WHERE incidencia_id = 1;

-- 2. Verificar que se llenó fecha_resolucion al resolver
UPDATE incidencias SET estado_actual = 'resuelto' WHERE id = 1;
SELECT fecha_resolucion FROM incidencias WHERE id = 1;

-- 3. Probar la función calcular_tiempo_resolucion
SELECT calcular_tiempo_resolucion(1) AS dias;

-- 4. Consultar la vista completa
SELECT * FROM v_incidencias_completas LIMIT 5;

-- 5. Ver métricas por tipo
SELECT * FROM v_metricas_por_tipo;
```

---

### DÍA 22: Verificación final de Docker y réplicas `[DATA CENTER]` `[LINEAMIENTO]`

Antes del documento técnico, verifica que la infraestructura funciona correctamente:

```bash
# Baja todo y vuelve a levantar desde cero para simular el entorno del evaluador
docker-compose down -v
docker-compose up -d

# Verifica que todos los servicios están corriendo
docker-compose ps

# Verifica que el healthcheck del db pasa antes de que el backend arranque
docker-compose logs backend | grep "Migrat"

# Prueba que Redis caché funciona: primera petición al dashboard (sin caché)
curl -H "Authorization: Bearer TU_TOKEN" http://localhost/api/dashboard
# Segunda petición debe ser más rápida (datos en caché por 5 minutos)
curl -H "Authorization: Bearer TU_TOKEN" http://localhost/api/dashboard
```

**Lo que debes documentar en el DOCUMENTO_TECNICO.md:**
- Captura de `docker-compose ps` mostrando todos los servicios `Up`
- Explicación de para qué sirve cada servicio (db, backend, redis, pgadmin)
- Explicación del healthcheck y por qué evita errores de arranque
- Si tienes réplicas: `deploy.replicas: 2` y por qué escala horizontalmente

---

### DÍAS 23-24: Documento técnico `[WEB]` `[CALIDAD]` `[BD]` `[DATA CENTER]` `[LINEAMIENTO]`

Crea el archivo `DOCUMENTO_TECNICO.md` en la raíz del proyecto.
Debe tener las siguientes secciones con contenido real (no placeholders genéricos):

#### Sección 1 — Portada
```markdown
# Sistema Web de Gestión de Incidencias Georreferenciadas
Carrera: Ingeniería en Tecnologías de la Información
Asignaturas: Tecnologías y Desarrollo Web, Calidad de Software,
             Administración de Data Center, Base de Datos I y II
Integrantes: [tu nombre]
Docentes: [nombres de los docentes de cada materia]
Fecha: [mes y año]
```

#### Sección 2 — Descripción de la implementación
Explica en tus propias palabras:
- Qué problema resuelve el sistema (ciudadanos reportan incidencias urbanas con geolocalización)
- Las decisiones técnicas que tomaste y por qué (PostgreSQL, Vanilla JS, Docker, Leaflet, etc.)

#### Sección 3 — Arquitectura del sistema
Incluye:
- Tabla de tecnologías (Frontend, Backend, BD, Caché, Servidor, Contenedores, Auth, Pruebas)
- Diagrama ASCII de la arquitectura: Usuario → Nginx → Laravel(×2) → PostgreSQL + Redis

#### Sección 4 — Funcionalidades implementadas
Incluye la tabla completa de endpoints con todos los que están en `routes/api.php`:

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/login` | Autenticación de usuario |
| POST | `/api/register` | Registro de nuevo ciudadano |
| GET | `/api/user` | Datos del usuario autenticado con rol |
| GET | `/api/dashboard` | Métricas con caché Redis (TTL 5 min) |
| GET | `/api/tipos-incidencia` | Catálogo de tipos con subtipos anidados |
| GET | `/api/ciudades` | Catálogo de ciudades |
| GET | `/api/provincias` | Listado de provincias |
| GET | `/api/provincias/{id}/ciudades` | Ciudades de una provincia |
| GET | `/api/usuarios` | Usuarios con rol (solo admin) |
| GET/POST/PUT/DELETE | `/api/incidencias` | CRUD completo |
| GET | `/api/incidencias/{id}/historial` | Historial de estados (trigger) |
| GET/POST | `/api/incidencias/{id}/comentarios` | Comentarios |
| GET/POST/DELETE | `/api/incidencias/{id}/asignaciones` | Técnicos asignados |
| GET | `/api/notificaciones` | Notificaciones del usuario |
| PUT | `/api/notificaciones/{id}/leer` | Marcar como leída |

Y describe las 6 páginas del frontend: login, registro, dashboard, incidencias (con detalle modal), nueva incidencia, usuarios.

#### Sección 5 — Base de datos
Incluye:
- El diagrama ER (genera desde PgAdmin: Tools → ERD Tool, guarda como imagen)
- La tabla de las 14 tablas con su descripción y relaciones clave
- El código SQL de al menos un trigger (el de historial de estados)
- Mención de las 2 vistas, la función y los 5 índices

#### Sección 6 — Credenciales de acceso para evaluación
```markdown
| Rol | Email | Contraseña |
|-----|-------|------------|
| Administrador | admin@sistema.com | password123 |
| Usuario normal | usuario@sistema.com | password123 |
```

#### Sección 7 — Instrucciones de ejecución
```markdown
1. Tener Docker Desktop instalado y corriendo
2. Copiar las variables de entorno: `cp backend/.env.example backend/.env`
3. Editar `backend/.env` con la contraseña real de PostgreSQL
4. Desde la raíz: `docker-compose up -d --build`
5. Generar clave de Laravel: `docker-compose exec backend php artisan key:generate`
6. Crear tablas y datos: `docker-compose exec backend php artisan migrate:fresh --seed`
7. Acceder en: http://localhost
8. PgAdmin en: http://localhost:5050 (admin@sistema.com / password123)
```

> **`migrate:fresh --seed`:** es equivalente a `migrate` + `db:seed` pero primero borra todo.
> Útil para resetear la BD al estado inicial (datos del seeder) en una sola operación.

#### Sección 8 — Evidencias de calidad

**Capturas de pantalla** — guárdalas en `capturas/` con estos nombres:
```
capturas/
├── 01_login.png           ← pantalla de login con fondo de ciudad
├── 02_registro.png        ← formulario de registro
├── 03_dashboard.png       ← dashboard con gráficas y tarjetas
├── 04_incidencias_lista.png ← tabla con filtros
├── 05_nueva_incidencia_modal.png ← modal con mapa Leaflet
└── 06_detalle.png         ← modal de detalle con historial y comentarios
```

**Pruebas PHPUnit** — incluye el output de:
```bash
docker-compose exec backend php artisan test --testdox
```
Copia el resultado en el documento (muestra cada test con ✓ o ✗).

**Pruebas Artillery** — incluye el resultado de `reporte.json`:
- Requests totales ejecutados
- Requests exitosos (status 200)
- Tiempo de respuesta p95 y p99
- Duración total de la prueba

#### Sección 9 — Despliegue e infraestructura Docker
Describe los 5 servicios de `docker-compose.yml`:
- `db` (PostgreSQL 16) — BD relacional con healthcheck
- `backend` (Laravel, 2 réplicas) — API REST con escalamiento horizontal
- `frontend` (Nginx) — servidor web y balanceador de carga
- `redis` (Redis alpine) — caché para el dashboard
- `pgadmin` (PgAdmin4) — administración visual de la BD

**Para el diagrama ER:**
PgAdmin → clic derecho en la BD → ERD Tool → guarda como PNG en `capturas/`.

---

### DÍA 24: PR y entrega final

1. Verificar que todos los tests pasan: `php artisan test`
2. Verificar que Docker levanta limpio: `docker-compose down && docker-compose up -d`
3. Regenerar backup.sql con los datos actuales
4. Crear PR de la rama de trabajo → main
5. Hacer merge a main
6. Verificar que el repositorio GitHub muestra el historial de commits correctamente

---

## Resumen de lineamientos vs extras implementados

### Obligatorios (lineamientos) — sin estos no hay nota

| # | Funcionalidad | Dónde se implementa |
|---|---------------|---------------------|
| 1 | CRUD de incidencias | IncidenciaController + incidencias.html |
| 2 | Gestión de estados con historial | trigger trg_registrar_cambio_estado + historial_estados |
| 3 | Asignación de responsables (responsable/apoyo) | AsignacionController + procedimiento asignar_tecnico |
| 4 | Comentarios con autor y fecha | ComentarioController + trigger notificación |
| 5 | Ubicación País→Provincia→Ciudad | tablas geográficas + formulario dinámico |
| 6 | Clasificación Tipo→Subtipo | tablas de catálogos + optgroup en formulario |
| 7 | Notificaciones leído/no leído | NotificacionController + notificaciones.js con badge |
| 8 | Prioridad, fecha, tiempo de resolución | columna prioridad + trigger fecha + función calcular_tiempo |
| 9 | Métricas por estado, tipo, ubicación | DashboardController + vistas SQL |
| 10 | Docker: backend + base de datos | docker-compose.yml con db + backend |

### Extras implementados — suman puntos bonus

| Extra | Qué agrega | Dónde |
|-------|-----------|-------|
| Redis para caché | Segunda petición al dashboard es instantánea | DashboardController + Cache::remember() |
| PgAdmin | Administración visual de PostgreSQL | docker-compose.yml |
| Healthchecks Docker | El backend espera a que la BD esté lista | docker-compose.yml |
| Replicas (x2) | Escalamiento horizontal del backend | docker-compose (deploy.replicas: 2) |
| Paginación en tabla | No carga todas las incidencias a la vez | IncidenciaController::paginate(10) + JS paginación |
| Búsqueda en tiempo real | Filtra mientras escribe (con debounce) | incidencias.js |
| Filtro de fechas | Filtra por rango de fechas | IncidenciaController + incidencias.html |
| Mapa Leaflet interactivo | Clic para marcar ubicación, GPS para centrar | nueva.js con Leaflet |
| Mapa en detalle | Pin fijo en las coordenadas de la incidencia | incidencias.js (modal de detalle) con Leaflet |
| Chart.js por ciudad | Gráfica de barras adicional | DashboardController + dashboard.js |
| Gestión de usuarios | Admin puede cambiar roles | UserController + usuarios.html |
| Procedimientos almacenados | Lógica de negocio en la BD | resolver_incidencia, asignar_tecnico |
| Vistas SQL | JOINs centralizados | v_incidencias_completas, v_metricas_por_tipo |
| Función SQL | Cálculo de días individual | calcular_tiempo_resolucion() |
| 5 índices de rendimiento | Consultas más rápidas | idx_incidencias_estado, etc. |
| Bitácora de errores | Log automático de excepciones | BitacoraError en try/catch |
| Registro de usuarios | Ciudadanos crean su propia cuenta | register.html + POST /api/register |

---

## Tips importantes para cuando te trabe

1. **Si una migración falla con FK:** revisa que la migración de la tabla referenciada se ejecutó primero. El orden en el timestamp del nombre del archivo determina el orden de ejecución.

2. **Si el trigger no se dispara:** asegúrate de estar usando `DB::unprepared()` (no `DB::statement()`) para el código PL/pgSQL que contiene `$$`.

3. **Si Sanctum da 401 en cada request:** verifica que mandas el header `Authorization: Bearer TOKEN` y que el middleware del grupo de rutas es `auth:sanctum`.

4. **Si Leaflet no muestra el mapa:** el `div` del mapa debe tener altura CSS explícita (`height: 300px`). Sin altura, Leaflet no puede renderizar.

5. **Si Chart.js da error "canvas already in use":** llama a `grafica.destroy()` antes de crear una nueva gráfica en el mismo canvas.

6. **Si el archivo subido no se ve:** ejecuta `php artisan storage:link` para crear el enlace simbólico entre `public/storage` y `storage/app/public`.

7. **Consulta `aprender.md`:** si algo no entiendes, busca la sección correspondiente. Cada concepto del proyecto tiene su explicación ahí.
