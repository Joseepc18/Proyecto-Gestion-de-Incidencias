// util.js — Helpers pequeños reutilizables de presentación: textos de la UI, fechas, celdas y vacíos.

/* exported iniciales, codigoIncidencia, tiempoRelativo, estadoVacioHtml, filaVaciaHtml, asegurarLibreria, normalizarTexto, celdaTabla, etiquetaRol, avisoEnvioAPapelera */
/* global escaparHtml, tienePermiso */

// Etiqueta legible de un nombre_rol; única fuente para no mostrar el slug crudo en la UI.
function etiquetaRol(nombreRol) {
  const mapa = {
    super_admin: "Administrador del Sistema",
    admin: "Supervisor",
    tecnico: "Técnico",
    normal: "Ciudadano",
  };
  return mapa[nombreRol] || "Usuario";
}

// Borrar manda la incidencia a la papelera, pero solo quien tiene incidencias.papelera puede entrar a
// restaurarla: para el resto la papelera es invisible y lo único cierto es el plazo de la purga.
function avisoEnvioAPapelera(diasRetencion) {
  const purga = diasRetencion
    ? " Si nadie la restaura, se borra sola a los " + diasRetencion + " días."
    : "";

  if (tienePermiso("incidencias.papelera")) {
    return "Se enviará a la papelera y podrás restaurarla desde ahí." + purga;
  }

  return (
    "Se enviará a la papelera, pero tú no podrás entrar ahí a recuperarla: solo un administrador puede restaurarla." +
    purga
  );
}

// Normaliza un texto (sin tildes, minúsculas, espacios colapsados) para cruzar nombres o GeoJSON.
function normalizarTexto(texto) {
  return (texto || "")
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "")
    .toLowerCase()
    .replace(/\s+/g, " ")
    .trim();
}

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

// Tono neutro, no rojo (distinto de un error); para tablas usar filaVaciaHtml
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

// Si un 5xx del túnel/caché dejó el <script> sin ejecutar, la reinyecta con cache-buster
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

// Celda de tabla con texto escapado; label alimenta el data-label de la tarjeta (móvil) y secundario la oculta ahí.
function celdaTabla(texto, label, secundario) {
  const celda = document.createElement("td");
  if (label) celda.dataset.label = label;
  if (secundario) celda.classList.add("td-secundario");
  celda.textContent = texto;
  return celda;
}
