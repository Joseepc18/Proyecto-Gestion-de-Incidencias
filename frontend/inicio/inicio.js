// inicio.js — Protege el panel, muestra el dashboard del admin y maneja logout.

/* global apiFetch, obtenerToken, eliminarToken, aplicarMenuRol, mostrarToast, Chart, L */

// Guarda las gráficas creadas para poder destruirlas y repintarlas al cambiar de tema.
let graficos = [];
// Guarda las métricas ya cargadas para repintar sin volver a pedirlas al servidor.
let metricasCache = null;
// Referencias del mapa de coropletas (se crea una sola vez).
let mapaProv = null;
let capaProv = null;
let leyendaProv = null;
let geojsonProv = null;

// Lee un color de las variables --admin-* (cambian solas en modo claro/oscuro).
function colorVar(nombre) {
  return getComputedStyle(document.documentElement).getPropertyValue(nombre).trim();
}

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

// Normaliza un nombre de provincia (sin tildes, minúsculas) para cruzar BD y GeoJSON.
function normalizar(texto) {
  return (texto || "").normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase().trim();
}

document.addEventListener("DOMContentLoaded", async function () {
  // GUARD: si no hay token, no puede estar aquí -> al login.
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

  // Cargar el usuario autenticado y mostrar su nombre.
  try {
    const usuario = await apiFetch("/user");

    // El usuario normal no tiene Inicio: su pantalla es "Mis incidencias".
    if (usuario.rol && usuario.rol.nombre_rol === "normal") {
      window.location.replace("../mis-incidencias/mis-incidencias.html");
      return;
    }

    document.getElementById("nombreUsuario").textContent = usuario.name;
    document.getElementById("saludoNombre").textContent = usuario.name;

    const rol = usuario.rol ? usuario.rol.nombre_rol : "";

    // El menú se ajusta según el rol (admin: tabla + usuarios; resto: mis incidencias).
    aplicarMenuRol(rol);

    // El admin ve el dashboard; el técnico solo una bienvenida con acceso a su listado.
    if (rol === "admin") {
      await cargarDashboard();
    } else {
      mostrarInicioTecnico();
    }
  } catch {
    // Token inválido o expirado -> limpiar y al login.
    eliminarToken();
    window.location.href = "../login/login.html";
    return;
  }

  // LOGOUT
  document.getElementById("btnLogout").addEventListener("click", async (evento) => {
    evento.preventDefault();
    try {
      await apiFetch("/logout", { method: "POST" });
    } catch {
      /* ignorar */
    }
    eliminarToken();
    window.location.href = "../login/login.html";
  });

  // Repinta las gráficas y el mapa cuando se alterna el tema (los colores cambian).
  observarCambioDeTema();
});

// Bienvenida del técnico: ajusta el subtítulo y muestra su bloque (sin pedir métricas).
function mostrarInicioTecnico() {
  document.getElementById("inicioSubtitulo").textContent =
    "Revisa y atiende las incidencias que tienes asignadas.";
  document.getElementById("tecnicoInicio").classList.remove("d-none");
}

// Pide las métricas al backend y pinta KPIs + gráficas + tabla + mapa.
async function cargarDashboard() {
  let datos;
  try {
    datos = await apiFetch("/dashboard/metricas");
  } catch {
    mostrarToast("No se pudieron cargar las métricas del panel.", "error");
    return;
  }

  metricasCache = datos;
  const totales = datos.totales || {};

  // Sin incidencias todavía: mostrar el estado vacío en lugar de gráficas en cero.
  if (Number(totales.total || 0) === 0) {
    document.getElementById("inicioVacio").classList.remove("d-none");
    return;
  }

  pintarKpis(totales, datos.promedio_dias);
  // Mostrar el panel antes de pintar: el mapa y los canvas necesitan tener tamaño.
  document.getElementById("adminDashboard").classList.remove("d-none");
  pintarGraficas(datos);
  await pintarMapa(datos.por_provincia || []);
}

// Rellena las 5 tarjetas KPI con los conteos globales y el promedio de resolución.
function pintarKpis(totales, promedioDias) {
  document.getElementById("kpiTotal").textContent = Number(totales.total || 0);
  document.getElementById("kpiPendientes").textContent = Number(totales.pendientes || 0);
  document.getElementById("kpiEnProceso").textContent = Number(totales.en_proceso || 0);
  document.getElementById("kpiResueltas").textContent = Number(totales.resueltas || 0);
  // Si aún no hay nada resuelto, el promedio viene nulo.
  document.getElementById("kpiPromedio").textContent =
    promedioDias == null ? "—" : Number(promedioDias);
}

// Crea (o recrea) las gráficas a partir de las métricas cacheadas.
function pintarGraficas(datos) {
  // Destruir las anteriores para no duplicar al repintar por cambio de tema.
  graficos.forEach((g) => g.destroy());
  graficos = [];

  // Paleta de estados tomada de las variables del tema (se adapta a claro/oscuro).
  const colorPendiente = colorVar("--admin-danger");
  const colorProceso = colorVar("--admin-warning");
  const colorResuelto = colorVar("--admin-success");
  const colorPrimary = colorVar("--admin-primary");
  const colorTexto = colorVar("--admin-muted");
  const colorGrid = colorVar("--admin-border");
  const colorSurface = colorVar("--admin-surface");

  // Colores por defecto de Chart.js para que ejes y leyendas combinen con el tema.
  Chart.defaults.color = colorTexto;
  Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

  const totales = datos.totales || {};
  // La vista trae todos los tipos del catálogo; para las barras solo los que tienen incidencias.
  const porTipo = (datos.por_tipo || []).filter((t) => Number(t.total || 0) > 0);
  const prioridad = datos.por_prioridad || {};

  // Gráfica 1: dona con la distribución global por estado.
  graficos.push(
    new Chart(document.getElementById("graficoEstado"), {
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
            backgroundColor: [colorPendiente, colorProceso, colorResuelto],
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

  // Gráfica 2: barras apiladas por tipo, desglosadas por estado.
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
        scales: {
          x: { stacked: true, grid: { display: false }, ticks: { color: colorTexto } },
          y: {
            stacked: true,
            beginAtZero: true,
            ticks: { precision: 0, color: colorTexto },
            grid: { color: colorGrid },
          },
        },
      },
    }),
  );

  // Gráfica 3: dona por prioridad (ALTA/MEDIA/BAJA).
  graficos.push(
    new Chart(document.getElementById("graficoPrioridad"), {
      type: "doughnut",
      data: {
        labels: ["Alta", "Media", "Baja"],
        datasets: [
          {
            data: [
              Number(prioridad.alta || 0),
              Number(prioridad.media || 0),
              Number(prioridad.baja || 0),
            ],
            backgroundColor: [colorPendiente, colorProceso, colorResuelto],
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

  // Gráfica 4: promedio de días para resolver, por tipo (solo tipos con resueltas).
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
        scales: {
          x: { grid: { display: false }, ticks: { color: colorTexto } },
          y: { beginAtZero: true, ticks: { color: colorTexto }, grid: { color: colorGrid } },
        },
      },
    }),
  );

  // Gráfica 5: línea con la tendencia de incidencias por mes (últimos 12 meses).
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
        scales: {
          x: { grid: { display: false }, ticks: { color: colorTexto } },
          y: {
            beginAtZero: true,
            ticks: { precision: 0, color: colorTexto },
            grid: { color: colorGrid },
          },
        },
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
  const isla = geojson.features.find((f) => normalizar(f.properties.provincia) === "galapagos");
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
  // Cargar el GeoJSON de provincias una sola vez (y acercar Galápagos).
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

  // Conteo por provincia normalizado (sin tildes) para cruzar con el GeoJSON.
  const conteo = {};
  porProvincia.forEach(function (p) {
    conteo[normalizar(p.nombre_provincia)] = Number(p.total || 0);
  });
  const maximo = Math.max(1, ...Object.values(conteo));

  function estilo(feature) {
    const valor = conteo[normalizar(feature.properties.provincia)] || 0;
    return {
      fillColor: colorProvincia(valor, maximo),
      weight: 1,
      color: colorVar("--admin-surface"),
      fillOpacity: 0.9,
    };
  }

  // Crear el mapa la primera vez (sin tiles; zoom con la rueda y con botones).
  if (!mapaProv) {
    mapaProv = L.map("mapaProvincias", {
      attributionControl: false,
      scrollWheelZoom: true,
      zoomControl: true,
    });
  }
  if (capaProv) capaProv.remove();

  capaProv = L.geoJSON(geojsonProv, {
    style: estilo,
    onEachFeature: function (feature, layer) {
      const valor = conteo[normalizar(feature.properties.provincia)] || 0;
      layer.bindTooltip(feature.properties.provincia + ": " + valor, { sticky: true });
      layer.on({ mouseover: resaltarProvincia, mouseout: quitarResaltado });
    },
  }).addTo(mapaProv);

  mapaProv.fitBounds(capaProv.getBounds(), { padding: [6, 6] });
  // El contenedor acababa de hacerse visible; recalcular su tamaño.
  setTimeout(() => mapaProv.invalidateSize(), 0);

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
      // Con pocos datos varios tramos colapsan al mismo rango: se omiten.
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

// Observa el atributo data-theme del <html> para repintar gráficas y mapa al cambiar de tema.
function observarCambioDeTema() {
  const observador = new MutationObserver(function () {
    if (!metricasCache) return;
    if (graficos.length) pintarGraficas(metricasCache);
    // Solo cambian el borde (color del tema) y la leyenda; el relleno es fijo.
    if (capaProv) {
      capaProv.setStyle({ color: colorVar("--admin-surface") });
      const maximo = Math.max(
        1,
        ...(metricasCache.por_provincia || []).map((p) => Number(p.total || 0)),
      );
      dibujarLeyenda(maximo);
    }
  });
  observador.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["data-theme"],
  });
}
