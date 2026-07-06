// dashboard.js — Helpers compartidos por los paneles de inicio (admin y técnico).

/* global Chart */
/* exported colorVar, observarCambioDeTema, aplicarTemaChart, crearDonaEstado, ejesChart */

// Lee un color de las variables --admin-* (cambian solas en modo claro/oscuro).
function colorVar(nombre) {
  return getComputedStyle(document.documentElement).getPropertyValue(nombre).trim();
}

// Ajusta color de texto y fuente de Chart al tema actual; llamar antes de crear las gráficas.
function aplicarTemaChart() {
  Chart.defaults.color = colorVar("--admin-muted");
  Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
}

// Dona de estado (Pendientes/En proceso/Resueltas) compartida por ambos paneles.
function crearDonaEstado(canvas, totales) {
  return new Chart(canvas, {
    type: "doughnut",
    data: {
      labels: ["Pendientes", "En proceso", "Resueltas"],
      datasets: [
        {
          data: [
            Number(totales.pendientes || 0),
            Number(totales.en_proceso || 0),
            Number(totales.resueltas || 0),
          ],
          backgroundColor: [
            colorVar("--admin-danger"),
            colorVar("--admin-warning"),
            colorVar("--admin-success"),
          ],
          borderWidth: 2,
          borderColor: colorVar("--admin-surface"),
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: "62%",
      plugins: { legend: { position: "bottom" } },
    },
  });
}

// Ejes X/Y estándar de las gráficas de barras/línea del dashboard (rejilla y colores según el tema).
function ejesChart(colorTexto, colorGrid, opciones) {
  opciones = opciones || {};
  const x = { grid: { display: false }, ticks: { color: colorTexto } };
  const y = { beginAtZero: true, ticks: { color: colorTexto }, grid: { color: colorGrid } };
  if (opciones.precision != null) y.ticks.precision = opciones.precision;
  if (opciones.apilado) {
    x.stacked = true;
    y.stacked = true;
  }
  return { x: x, y: y };
}

// Repinta (gráficas/mapa) cada vez que cambia el atributo data-theme del <html>.
function observarCambioDeTema(repintar) {
  const observador = new MutationObserver(repintar);
  observador.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["data-theme"],
  });
}
