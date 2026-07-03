// dashboard.js — Helpers compartidos por los paneles de inicio (admin y técnico).

/* exported colorVar, normalizarTexto, observarCambioDeTema */

// Lee un color de las variables --admin-* (cambian solas en modo claro/oscuro).
function colorVar(nombre) {
  return getComputedStyle(document.documentElement).getPropertyValue(nombre).trim();
}

// Normaliza un texto (sin tildes, minúsculas) para cruzar datos con etiquetas o GeoJSON.
function normalizarTexto(texto) {
  return (texto || "").normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase().trim();
}

// Repinta (gráficas/mapa) cada vez que cambia el atributo data-theme del <html>.
function observarCambioDeTema(repintar) {
  const observador = new MutationObserver(repintar);
  observador.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["data-theme"],
  });
}
