// estados.js — Mapas compartidos de estado y prioridad de incidencias (clase, icono y etiqueta).

/* exported estadoConfig, prioridadConfig, badgeEstadoHtml, badgePrioridadHtml, colorEstado */

// color usa variables CSS para que pines de mapa e historial sigan el modo claro/oscuro.
const estadoConfig = {
  PENDIENTE: {
    clase: "badge-estado-pendiente",
    icono: "bi-clock-history",
    texto: "Pendiente",
    color: "var(--admin-danger)",
  },
  EN_PROCESO: {
    clase: "badge-estado-proceso",
    icono: "bi-gear-wide-connected",
    texto: "En proceso",
    color: "var(--admin-warning)",
  },
  RESUELTO: {
    clase: "badge-estado-resuelto",
    icono: "bi-check2-circle",
    texto: "Resuelto",
    color: "var(--admin-success)",
  },
  CERRADO: {
    clase: "badge-estado-cerrado",
    icono: "bi-archive",
    texto: "Archivado",
    color: "var(--admin-muted)",
  },
};

// Color asociado a un estado (única fuente de verdad para pines de mapa y puntos del historial).
function colorEstado(estado) {
  return (estadoConfig[estado] || estadoConfig.PENDIENTE).color;
}

const prioridadConfig = {
  ALTA: { clase: "text-bg-danger", icono: "bi-fire", texto: "Alta" },
  MEDIA: { clase: "text-bg-warning", icono: "bi-shield-exclamation", texto: "Media" },
  BAJA: { clase: "text-bg-success", icono: "bi-arrow-down-circle", texto: "Baja" },
  SIN_ASIGNAR: { clase: "text-bg-secondary", icono: "bi-dash-circle", texto: "Sin asignar" },
};

function badgeEstadoHtml(estado) {
  const cfg = estadoConfig[estado] || estadoConfig.PENDIENTE;
  return `<span class="badge ${cfg.clase}"><i class="bi ${cfg.icono} me-1"></i>${cfg.texto}</span>`;
}

function badgePrioridadHtml(prioridad) {
  const cfg = prioridadConfig[prioridad] || prioridadConfig.BAJA;
  return `<span class="badge ${cfg.clase} px-2 py-1"><i class="bi ${cfg.icono} me-1"></i>${cfg.texto}</span>`;
}
