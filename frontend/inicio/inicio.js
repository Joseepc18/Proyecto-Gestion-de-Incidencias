// inicio.js — Protege el panel, muestra el dashboard del admin y maneja logout.

// Guarda las gráficas creadas para poder destruirlas y repintarlas al cambiar de tema.
/* global apiFetchReintentar, aplicarMenuRol, tienePermiso, mostrarToast, Chart, L, requerirSesion, cablearLogout, inicioSegunRol, asegurarLibreria, colorVar, normalizarTexto, observarCambioDeTema, aplicarTemaChart, crearDonaEstado, ejesChart, obtenerEcho */

let graficos = [];
// Guarda las métricas ya cargadas para repintar sin volver a pedirlas al servidor.
let metricasCache = null;
// Referencias del mapa de coropletas (se crea una sola vez).
let mapaProv = null;
let capaProv = null;
let leyendaProv = null;
let geojsonProv = null;

// Devuelve los últimos n meses como { clave: 'YYYY-MM', etiqueta: 'ene 26' }.
function ultimosMeses(n) {
  const hoy = new Date();
  const meses = [];
  for (let i = n - 1; i >= 0; i--) {
    const f = new Date(hoy.getFullYear(), hoy.getMonth() - i, 1);
    const clave = f.getFullYear() + "-" + String(f.getMonth() + 1).padStart(2, "0");
    const etiqueta = f.toLocaleDateString("es", { month: "short", year: "2-digit" });
    meses.push({ clave, etiqueta });
  }
  return meses;
}

document.addEventListener("DOMContentLoaded", async function () {
  const usuario = await requerirSesion();
  if (!usuario) return;

  const rol = usuario.rol ? usuario.rol.nombre_rol : "";
  if (!tienePermiso("dashboard.ver")) {
    window.location.replace(inicioSegunRol(rol));
    return;
  }

  document.getElementById("nombreUsuario").textContent = usuario.name;
  document.getElementById("saludoNombre").textContent = usuario.name;

  aplicarMenuRol(rol, usuario.permisos);
  // Cableamos logout y tema ANTES del dashboard: si este falla, el admin siempre puede salir.
  cablearLogout();
  observarCambioDeTema(repintarPorTema);

  // El dashboard va aparte: si su render falla NO debe cerrar la sesión.
  await cargarDashboard();
});

// Carga inicial: pide las métricas (con reintentos ante micro-cortes) y pinta el panel.
async function cargarDashboard() {
  let datos;
  try {
    datos = await apiFetchReintentar("/dashboard/metricas", {}, 2);
  } catch {
    mostrarErrorDashboard();
    return;
  }

  quitarErrorDashboard();
  await renderDashboard(datos, false);
  conectarTablero();
}

// Pinta KPIs + gráficas + mapa a partir de las métricas. En modo silencioso (refresco en vivo) no muestra toasts de error.
async function renderDashboard(datos, silencioso) {
  metricasCache = datos;
  const totales = datos.totales || {};

  const vacio = document.getElementById("inicioVacio");
  const panel = document.getElementById("adminDashboard");

  // Sin datos: mostramos el estado vacío y ocultamos el panel (también al pasar de tener datos a cero en vivo).
  if (Number(totales.total || 0) === 0) {
    vacio.classList.remove("d-none");
    panel.classList.add("d-none");
    return;
  }

  vacio.classList.add("d-none");
  pintarKpis(totales);
  panel.classList.remove("d-none");

  // Gráficas y mapa se dibujan por separado: el fallo de una parte no debe borrar la otra.
  const hayChart = await asegurarLibreria("Chart", "../assets/vendors/chartjs/chart.umd.min.js");
  if (hayChart) {
    try {
      pintarGraficas(datos);
    } catch {
      if (!silencioso) mostrarToast("No se pudieron dibujar las gráficas.", "error");
    }
  } else if (!silencioso) {
    mostrarToast("No se pudieron cargar las gráficas.", "error");
  }

  const hayLeaflet = await asegurarLibreria("L", "../assets/vendors/leaflet/leaflet.js");
  if (hayLeaflet) {
    try {
      await pintarMapa(datos.por_provincia || []);
    } catch {
      if (!silencioso) mostrarToast("No se pudo dibujar el mapa.", "error");
    }
  } else if (!silencioso) {
    mostrarToast("No se pudo cargar el mapa.", "error");
  }
}

// Muestra un aviso con botón "Reintentar" en vez de dejar el panel en blanco tras agotar los reintentos.
function mostrarErrorDashboard() {
  mostrarToast("No se pudieron cargar las métricas del panel.", "error");
  let aviso = document.getElementById("dashboardError");
  if (!aviso) {
    aviso = document.createElement("section");
    aviso.id = "dashboardError";
    aviso.className = "row g-3 mt-1";
    aviso.innerHTML =
      '<div class="col-12"><article class="metric-card">' +
      '<p class="metric-label mb-2">No se pudo cargar el panel</p>' +
      '<p class="mb-3 text-muted">Revisa tu conexión e inténtalo de nuevo.</p>' +
      '<button type="button" class="btn btn-primary" id="btnReintentarDashboard">Reintentar</button>' +
      "</article></div>";
    document.getElementById("inicioVacio").insertAdjacentElement("beforebegin", aviso);
    aviso.querySelector("#btnReintentarDashboard").addEventListener("click", cargarDashboard);
  }
  aviso.classList.remove("d-none");
}

// Quita el aviso de error cuando una carga posterior sí tiene éxito.
function quitarErrorDashboard() {
  const aviso = document.getElementById("dashboardError");
  if (aviso) aviso.classList.add("d-none");
}

// Timer del debounce y bandera de refresco aplazado mientras la pestaña está oculta.
let refrescoTimer = null;
let refrescoPendiente = false;

// Se une al canal de presencia 'tablero' y agenda un refresco al detectar cambios de incidencias.
function conectarTablero() {
  const echo = obtenerEcho();
  if (!echo) return;
  echo
    .join("tablero")
    .listen(".IncidenciaCreada", programarRefresco)
    .listen(".IncidenciaActualizada", programarRefresco)
    .listen(".IncidenciaEliminada", programarRefresco);

  // Al volver a la pestaña, si hubo cambios mientras estaba oculta, refrescamos una vez.
  document.addEventListener("visibilitychange", function () {
    if (!document.hidden && refrescoPendiente) programarRefresco();
  });
}

// Junta ráfagas de eventos en un solo refetch (debounce) y repinta desde caché sin recrear todo de cero.
function programarRefresco() {
  clearTimeout(refrescoTimer);
  refrescoTimer = setTimeout(async function () {
    // En segundo plano no repintamos: dejamos la marca para refrescar al volver a la pestaña.
    if (document.hidden) {
      refrescoPendiente = true;
      return;
    }
    refrescoPendiente = false;
    let datos;
    try {
      // Refresco de fondo: reintenta en silencio y, si aun así falla, no molesta con un toast.
      datos = await apiFetchReintentar("/dashboard/metricas", { sinSpinner: true }, 2);
    } catch {
      return;
    }
    await renderDashboard(datos, true);
  }, 3500);
}

// Rellena las 4 tarjetas KPI con los conteos globales.
function pintarKpis(totales) {
  document.getElementById("kpiTotal").textContent = Number(totales.total || 0);
  document.getElementById("kpiPendientes").textContent = Number(totales.pendientes || 0);
  document.getElementById("kpiEnProceso").textContent = Number(totales.en_proceso || 0);
  document.getElementById("kpiResueltas").textContent = Number(totales.resueltas || 0);
}

// Crea (o recrea) las gráficas a partir de las métricas cacheadas.
function pintarGraficas(datos) {
  graficos.forEach((g) => g.destroy());
  graficos = [];

  const colorPendiente = colorVar("--admin-danger");
  const colorProceso = colorVar("--admin-warning");
  const colorResuelto = colorVar("--admin-success");
  const colorPrimary = colorVar("--admin-primary");
  const colorTexto = colorVar("--admin-muted");
  const colorGrid = colorVar("--admin-border");
  const colorSurface = colorVar("--admin-surface");

  aplicarTemaChart();

  const totales = datos.totales || {};
  const porTipo = (datos.por_tipo || []).filter((t) => Number(t.total || 0) > 0);
  const prioridad = datos.por_prioridad || {};

  graficos.push(crearDonaEstado(document.getElementById("graficoEstado"), totales));

  graficos.push(
    new Chart(document.getElementById("graficoTipo"), {
      type: "bar",
      data: {
        labels: porTipo.map((t) => t.nombre_tipo_incidencia),
        datasets: [
          {
            label: "Pendientes",
            data: porTipo.map((t) => Number(t.total_pendientes || 0)),
            backgroundColor: colorPendiente,
          },
          {
            label: "En proceso",
            data: porTipo.map((t) => Number(t.total_en_proceso || 0)),
            backgroundColor: colorProceso,
          },
          {
            label: "Resueltas",
            data: porTipo.map((t) => Number(t.total_resueltas || 0)),
            backgroundColor: colorResuelto,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: "bottom" } },
        scales: ejesChart(colorTexto, colorGrid, { apilado: true, precision: 0 }),
      },
    }),
  );

  graficos.push(
    new Chart(document.getElementById("graficoPrioridad"), {
      type: "doughnut",
      data: {
        labels: ["Alta", "Media", "Baja", "Sin asignar"],
        datasets: [
          {
            data: [
              Number(prioridad.alta || 0),
              Number(prioridad.media || 0),
              Number(prioridad.baja || 0),
              Number(prioridad.sin_asignar || 0),
            ],
            backgroundColor: [colorPendiente, colorProceso, colorResuelto, colorTexto],
            borderWidth: 2,
            borderColor: colorSurface,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "62%",
        plugins: { legend: { position: "bottom" } },
      },
    }),
  );

  const conPromedio = porTipo.filter((t) => t.promedio_dias_resolucion !== null);
  graficos.push(
    new Chart(document.getElementById("graficoPromedio"), {
      type: "bar",
      data: {
        labels: conPromedio.map((t) => t.nombre_tipo_incidencia),
        datasets: [
          {
            label: "Días promedio",
            data: conPromedio.map((t) => Number(t.promedio_dias_resolucion || 0)),
            backgroundColor: colorPrimary,
            borderRadius: 4,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: ejesChart(colorTexto, colorGrid),
      },
    }),
  );

  const meses = ultimosMeses(12);
  const conteoMes = {};
  (datos.por_mes || []).forEach(function (m) {
    conteoMes[m.mes] = Number(m.total || 0);
  });
  graficos.push(
    new Chart(document.getElementById("graficoMes"), {
      type: "line",
      data: {
        labels: meses.map((m) => m.etiqueta),
        datasets: [
          {
            label: "Incidencias",
            data: meses.map((m) => conteoMes[m.clave] || 0),
            borderColor: colorPrimary,
            backgroundColor: colorPrimary,
            tension: 0.3,
            pointRadius: 3,
            fill: false,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: ejesChart(colorTexto, colorGrid, { precision: 0 }),
      },
    }),
  );
}

// Devuelve un azul más intenso mientras mayor sea el conteo respecto al máximo.
function colorProvincia(conteo, maximo) {
  if (!conteo) return "#e9eef5";
  const ratio = maximo ? conteo / maximo : 0;
  if (ratio <= 0.2) return "#cfe0f3";
  if (ratio <= 0.4) return "#9cc3e6";
  if (ratio <= 0.6) return "#5b9bd5";
  if (ratio <= 0.8) return "#2f73b8";
  return "#1d4e89";
}

// Mueve unas coordenadas (recursivo: soporta Polygon y MultiPolygon).
function desplazarCoords(coords, dx, dy) {
  if (typeof coords[0] === "number") {
    coords[0] += dx;
    coords[1] += dy;
    return;
  }
  coords.forEach((c) => desplazarCoords(c, dx, dy));
}

// Acerca Galápagos al continente (inset) para que el mapa no quede dominado por el océano.
function acercarGalapagos(geojson) {
  const isla = geojson.features.find(
    (f) => normalizarTexto(f.properties.provincia) === "galapagos",
  );
  if (isla) desplazarCoords(isla.geometry.coordinates, 7, -1.5);
}

// Resalta el contorno de la provincia bajo el cursor.
function resaltarProvincia(e) {
  const layer = e.target;
  layer.setStyle({ weight: 3, color: colorVar("--admin-text"), fillOpacity: 1 });
  layer.bringToFront();
}

// Restaura el estilo de coropleta al quitar el cursor.
function quitarResaltado(e) {
  if (capaProv) capaProv.resetStyle(e.target);
}

// Dibuja el mapa de coropletas del Ecuador coloreando cada provincia por su conteo.
async function pintarMapa(porProvincia) {
  if (!geojsonProv) {
    try {
      const resp = await fetch("../assets/geo/ecuador-provincias.geojson");
      geojsonProv = await resp.json();
      acercarGalapagos(geojsonProv);
    } catch {
      mostrarToast("No se pudo cargar el mapa de provincias.", "error");
      return;
    }
  }

  const conteo = {};
  porProvincia.forEach(function (p) {
    conteo[normalizarTexto(p.nombre_provincia)] = Number(p.total || 0);
  });
  const maximo = Math.max(1, ...Object.values(conteo));

  function estilo(feature) {
    const valor = conteo[normalizarTexto(feature.properties.provincia)] || 0;
    return {
      fillColor: colorProvincia(valor, maximo),
      weight: 1,
      color: colorVar("--admin-surface"),
      fillOpacity: 0.9,
    };
  }

  if (!mapaProv) {
    mapaProv = L.map("mapaProvincias", {
      attributionControl: false,
      scrollWheelZoom: true,
      zoomControl: true,
    });
    // Reacciona al tamaño real en vez de adivinar con un setTimeout
    if (typeof ResizeObserver !== "undefined") {
      const ro = new ResizeObserver(function () {
        mapaProv.invalidateSize();
      });
      ro.observe(mapaProv.getContainer());
    }
  }
  if (capaProv) capaProv.remove();

  capaProv = L.geoJSON(geojsonProv, {
    style: estilo,
    onEachFeature: function (feature, layer) {
      const valor = conteo[normalizarTexto(feature.properties.provincia)] || 0;
      layer.bindTooltip(feature.properties.provincia + ": " + valor, { sticky: true });
      layer.on({ mouseover: resaltarProvincia, mouseout: quitarResaltado });
    },
  }).addTo(mapaProv);

  mapaProv.fitBounds(capaProv.getBounds(), { padding: [6, 6] });

  dibujarLeyenda(maximo);
}

// Leyenda del mapa: tramos de color con sus rangos de incidencias (sin rangos repetidos).
function dibujarLeyenda(maximo) {
  if (leyendaProv) leyendaProv.remove();
  leyendaProv = L.control({ position: "bottomright" });
  leyendaProv.onAdd = function () {
    const div = L.DomUtil.create("div", "leyenda-mapa");
    const tramos = [0, 0.2, 0.4, 0.6, 0.8];
    let html = "<strong>Incidencias</strong><br>";
    let anterior = null;
    tramos.forEach(function (t, i) {
      const desde = Math.round(t * maximo);
      const hasta = i < tramos.length - 1 ? Math.round(tramos[i + 1] * maximo) : maximo;
      const rango = i === 0 ? "0–" + hasta : desde + "–" + hasta;
      if (rango === anterior) return;
      anterior = rango;
      const color = colorProvincia(desde === 0 ? 0 : Math.max(1, desde), maximo);
      html += '<span><i style="background:' + color + '"></i>' + rango + "</span><br>";
    });
    div.innerHTML = html;
    return div;
  };
  leyendaProv.addTo(mapaProv);
}

// Repinta gráficas y mapa al cambiar de tema (lo dispara observarCambioDeTema de dashboard.js).
function repintarPorTema() {
  if (!metricasCache) return;
  if (graficos.length) pintarGraficas(metricasCache);
  if (capaProv) {
    capaProv.setStyle({ color: colorVar("--admin-surface") });
    const maximo = Math.max(
      1,
      ...(metricasCache.por_provincia || []).map((p) => Number(p.total || 0)),
    );
    dibujarLeyenda(maximo);
  }
}
