# Pruebas de carga (Artillery)

Evidencia de que el sistema escala horizontalmente: **2 réplicas de backend (PHP-FPM)
detrás de Nginx como balanceador**, sobre PostgreSQL. Respalda el bono +5 de
escalamiento y los puntos de pruebas de carga de Calidad de Software.

## Blanco

Se golpea el healthcheck público **`GET /api/health`** (sin token ni rate limiting).
El endpoint hace un `SELECT 1`, así que cada petición recorre la cadena completa
**Nginx (LB) → PHP-FPM → PostgreSQL** sin modificar datos.

## Cómo correrla

Requiere Node (ya lo usa el frontend). Artillery se baja con `npx`, no hace falta instalarlo.

```bash
# Local (stack docker, 2 réplicas) — carga fuerte
npx artillery@latest run --output pruebas-carga/resultado-local.json pruebas-carga/carga.yml

# Producción — corrida SUAVE (no afecta el sitio en vivo)
npx artillery@latest run -e prod --output pruebas-carga/resultado-prod.json pruebas-carga/carga.yml

# Reporte HTML a partir del JSON
npx artillery@latest report pruebas-carga/resultado-local.json
```

## Qué mirar en los resultados

- **http.codes.200**: que (casi) todas las respuestas sean 200 (sin 5xx ni caídas).
- **http.response_time** (p50 / p95 / p99): latencia bajo carga.
- **http.request_rate**: peticiones por segundo alcanzadas.

> Nota: en **local sobre Windows** la latencia sale peor de lo real por el bind-mount
> de Docker Desktop (I/O lento del volumen `./backend`). Las métricas de **producción
> (Linux, FS nativo)** son la referencia válida; local sirve para confirmar que el
> sistema aguanta y que el balanceador reparte entre las 2 réplicas.
