// dashboard.js — Helpers compartidos por los paneles de inicio (admin y técnico).

/* global Chart */
/* exported colorVar, observarCambioDeTema, aplicarTemaChart, crearDonaEstado */

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

// Repinta (gráficas/mapa) cada vez que cambia el atributo data-theme del <html>.
function observarCambioDeTema(repintar) {
  const observador = new MutationObserver(repintar);
  observador.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["data-theme"],
  });
}
