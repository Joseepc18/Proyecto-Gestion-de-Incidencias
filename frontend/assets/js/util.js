// util.js — Helpers pequeños reutilizables: iniciales, código de incidencia y tiempo relativo.

/* exported iniciales, codigoIncidencia, tiempoRelativo, estadoVacioHtml, filaVaciaHtml, asegurarLibreria */
/* global escaparHtml */

// Iniciales de un nombre (hasta 2 letras); fallback "?" si está vacío.
function iniciales(nombre) {
  const p = (nombre || "").trim().split(/\s+/);
  const a = p[0] ? p[0][0] : "";
  const b = p[1] ? p[1][0] : "";
  return (a + b).toUpperCase() || "?";
}

// Código legible a partir del id de incidencia (INC-0001).
function codigoIncidencia(id) {
  return "INC-" + String(id).padStart(4, "0");
}

// Fecha ISO -> texto relativo ("hace 5 min", "hace 2 h", ...).
function tiempoRelativo(iso) {
  const fecha = new Date(iso);
  const seg = Math.floor((Date.now() - fecha.getTime()) / 1000);
  if (seg < 60) return "hace un momento";
  if (seg < 3600) return "hace " + Math.floor(seg / 60) + " min";
  if (seg < 86400) return "hace " + Math.floor(seg / 3600) + " h";
  if (seg < 604800) return "hace " + Math.floor(seg / 86400) + " d";
  return fecha.toLocaleDateString("es-EC");
}

// HTML de un "estado vacío" (lista sin datos): icono + título + texto opcional.
// Es distinto de un error: tono neutro, no rojo. Para tablas usar filaVaciaHtml.
function estadoVacioHtml(icono, titulo, texto) {
  return (
    '<div class="estado-vacio">' +
    '<i class="bi ' +
    icono +
    ' estado-vacio-icono" aria-hidden="true"></i>' +
    '<p class="estado-vacio-titulo">' +
    escaparHtml(titulo) +
    "</p>" +
    (texto ? '<p class="estado-vacio-texto">' + escaparHtml(texto) + "</p>" : "") +
    "</div>"
  );
}

// Garantiza que una librería global (Chart, L) esté cargada; si un 5xx del túnel/caché
// dejó el <script> sin ejecutar, la reinyecta con cache-buster para saltar la copia mala.
function asegurarLibreria(nombreGlobal, src) {
  if (typeof window[nombreGlobal] !== "undefined") return Promise.resolve(true);
  return new Promise(function (resolve) {
    const s = document.createElement("script");
    s.src = src + "?reintento=" + Date.now();
    s.onload = () => resolve(typeof window[nombreGlobal] !== "undefined");
    s.onerror = () => resolve(false);
    document.head.appendChild(s);
  });
}

// Igual que estadoVacioHtml pero envuelto en una fila de tabla que ocupa todas las columnas.
function filaVaciaHtml(colspan, icono, titulo, texto) {
  return (
    '<tr><td colspan="' + colspan + '">' + estadoVacioHtml(icono, titulo, texto) + "</td></tr>"
  );
}
