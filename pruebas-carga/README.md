# Pruebas de carga (Artillery)

Evidencia de que el sistema escala horizontalmente: **2 réplicas de backend (PHP-FPM)
detrás de Nginx como balanceador**, sobre PostgreSQL. Respalda el bono +5 de
escalamiento y los puntos de pruebas de carga de Calidad de Software.

## Dos escenarios

**1. Público — `carga.yml` → `GET /api/health`** (sin token ni rate limiting).
El endpoint hace un `SELECT 1`, así que cada petición recorre la cadena completa
**Nginx (LB) → PHP-FPM → PostgreSQL** sin modificar datos. Sirve para medir capacidad
bruta de la infra y el reparto entre réplicas.

**2. Autenticado — `carga-auth.yml` → `GET /api/incidencias`** (con token Sanctum).
Ejercita el camino real de lectura: carga 4 relaciones (usuario, subtipo.tipo, ciudad,
adminAtiende), ordena, pagina y filtra por rol. El login ocurre **una sola vez**
(processor `carga-auth.js`) y el token se reutiliza, así se mide el listado y no el
bcrypt del login.

> ⚠️ **Tope por diseño:** las rutas autenticadas están limitadas a **120 req/min por
> usuario** (`throttle:120,1`, keyed por id). Con un solo usuario el techo real es
> **~2 req/s**; por encima, Laravel responde **429** (protección, no fallo). Por eso
> `carga-auth.yml` se queda alrededor de 2/s. Para carga alta autenticada de verdad
> harían falta muchos usuarios distintos, cada uno con su propio cupo de 120/min.

## Cómo correrla

Requiere Node (ya lo usa el frontend). Artillery se baja con `npx`, no hace falta instalarlo.

```bash
# --- Escenario público (/api/health) ---
# Local (stack docker, 2 réplicas) — carga fuerte
npx artillery@latest run --output pruebas-carga/resultado-local.json pruebas-carga/carga.yml

# Producción — corrida SUAVE (no afecta el sitio en vivo)
npx artillery@latest run -e prod --output pruebas-carga/resultado-prod.json pruebas-carga/carga.yml

# --- Escenario autenticado (/api/incidencias) ---
# Necesita un CIUDADANO (rol normal, SIN 2FA). Credenciales por variables de entorno
# (nunca se commitean). En prod usar un ciudadano real.
ARTILLERY_EMAIL=carga@test.local ARTILLERY_PASSWORD=password123 \
  npx artillery@latest run --output pruebas-carga/resultado-auth-local.json pruebas-carga/carga-auth.yml

ARTILLERY_EMAIL=... ARTILLERY_PASSWORD=... \
  npx artillery@latest run -e prod --output pruebas-carga/resultado-auth-prod.json pruebas-carga/carga-auth.yml

# Reporte HTML a partir de cualquier JSON
npx artillery@latest report pruebas-carga/resultado-local.json
```

> El escenario autenticado necesita que el stack corra desde la **raíz del repo**
> (el `processor` y el `--output` son relativos al cwd).

## Qué mirar en los resultados

- **http.codes.200**: que (casi) todas las respuestas sean 200 (sin 5xx ni caídas).
- **http.response_time** (p50 / p95 / p99): latencia bajo carga.
- **http.request_rate**: peticiones por segundo alcanzadas.

> Nota: en **local sobre Windows** la latencia sale peor de lo real por el bind-mount
> de Docker Desktop (I/O lento del volumen `./backend`). Las métricas de **producción
> (Linux, FS nativo)** son la referencia válida; local sirve para confirmar que el
> sistema aguanta y que el balanceador reparte entre las 2 réplicas.
