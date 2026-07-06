// realtime.js — Cliente Echo compartido (una sola conexión) + latido del candado.

/* global apiFetch, obtenerToken, Echo, Pusher */
/* exported obtenerEcho, iniciarHeartbeatReclamo */

// Instancia única de Echo para toda la página (evita abrir varias conexiones WebSocket).
let echoSingleton = null;

// Crea (o reutiliza) el cliente de Echo apuntando al servidor Reverb por detrás de Nginx.
function obtenerEcho() {
  if (echoSingleton) return echoSingleton;
  if (typeof Echo === "undefined" || typeof Pusher === "undefined") return null;

  const esHttps = window.location.protocol === "https:";
  echoSingleton = new Echo({
    broadcaster: "reverb",
    key: "incidencias-key",
    wsHost: window.location.hostname,
    wsPort: esHttps ? 443 : 80,
    wssPort: esHttps ? 443 : 80,
    forceTLS: esHttps,
    enabledTransports: ["ws", "wss"],
    authEndpoint: "/api/broadcasting/auth",
    auth: { headers: { Authorization: "Bearer " + obtenerToken() } },
  });
  return echoSingleton;
}

// Evita arrancar el latido dos veces si dos scripts de la página lo piden.
let heartbeatIniciado = false;

// Mantiene vivo el candado del admin: con la pestaña visible avisa al servidor cada 40s; si cierra la app deja de latir y, pasado el TTL, otro admin toma sus reclamos.
function iniciarHeartbeatReclamo() {
  if (heartbeatIniciado) return;
  heartbeatIniciado = true;

  const INTERVALO_MS = 40000;
  let timer = null;

  function latir() {
    apiFetch("/incidencias/reclamo/heartbeat", { method: "POST", sinSpinner: true }).catch(
      function () {
        // Silencioso: si un latido falla, el siguiente lo reintenta.
      },
    );
  }

  function programar() {
    clearInterval(timer);
    if (!document.hidden) {
      latir();
      timer = setInterval(latir, INTERVALO_MS);
    }
  }

  // Al ocultar la pestaña se pausa el latido; al volver se late de inmediato y se reanuda.
  document.addEventListener("visibilitychange", programar);
  programar();
}
