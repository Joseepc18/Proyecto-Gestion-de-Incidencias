// inicio-tecnico.js — Panel de inicio del técnico: sus KPIs, mapa de asignadas, lista y gráficas.

/* global apiFetch, aplicarMenuRol, mostrarToast, Chart, requerirSesion, cablearLogout, inicioSegunRol, asegurarLibreria, crearMapaIncidencias, rutaDetalleIncidencia, codigoIncidencia, tiempoRelativo, estadoConfig, prioridadConfig, estadoVacioHtml, colorEstado, colorVar, observarCambioDeTema, aplicarTemaChart, crearDonaEstado, ejesChart, obtenerEcho */

// Guarda las gráficas creadas para poder destruirlas y repintarlas al cambiar de tema.
let graficos = [];
// Guarda las métricas ya cargadas para repintar sin volver a pedirlas al servidor.
let datosCache = null;

document.addEventListener("DOMContentLoaded", async function () {
  const usuario = await requerirSesion();
  if (!usuario) return;

  const rol = usuario.rol ? usuario.rol.nombre_rol : "";
  if (rol !== "tecnico") {
    window.location.replace(inicioSegunRol(rol));
    return;
  }

  document.getElementById("saludoNombre").textContent = usuario.name;

  aplicarMenuRol(rol, usuario.permisos);
  // Cableamos logout y tema ANTES del panel: si este falla, el técnico siempre puede salir.
  cablearLogout();
  observarCambioDeTema(repintarPorTema);

  await cargarPanel();
  conectarTiempoReal(usuario.id);
});

// Al asignar o quitar una incidencia se repinta el panel entero (KPIs + lista + mapa) refrescando /dashboard/tecnico, la vía simple y correcta.
function conectarTiempoReal(idUsuario) {
  const echo = obtenerEcho();
  if (!echo) return;
  const canal = echo.private("App.Models.User." + idUsuario);
  canal.notification(function (n) {
    if (n.tipo === "ASIGNACION") cargarPanel();
  });
  canal.listen(".AsignacionCambiada", function (e) {
    if (e.accion === "quitada") cargarPanel();
  });
}

// Pide las métricas del técnico y pinta KPIs + lista + mapa + gráficas.
async function cargarPanel() {
  let datos;
  try {
    datos = await apiFetch("/dashboard/tecnico");
  } catch {
    mostrarToast("No se pudieron cargar tus métricas.", "error");
    return;
  }

  datosCache = datos;
  const totales = datos.totales || {};
  const activas = datos.activas || [];

  const total =
    Number(totales.pendientes || 0) +
    Number(totales.en_proceso || 0) +
    Number(totales.resueltas || 0) +
    Number(totales.apoyo_activas || 0);
  if (total === 0) {
    document.getElementById("inicioVacio").classList.remove("d-none");
    return;
  }

  pintarKpis(totales);
  pintarLista(activas);
  // El panel se muestra ANTES de crear el mapa para que Mapbox mida bien su contenedor.
  document.getElementById("tecnicoDashboard").classList.remove("d-none");

  // Mapa y gráficas van por separado: el fallo de una parte no debe borrar la otra.
  try {
    pintarMapa(activas);
  } catch (err) {
    console.error(err);
    mostrarToast("No se pudo dibujar el mapa.", "error");
  }

  const hayChart = await asegurarLibreria("Chart", "../assets/vendors/chartjs/chart.umd.min.js");
  if (hayChart) {
    try {
      pintarGraficas(datos);
    } catch (err) {
      console.error(err);
      mostrarToast("No se pudieron dibujar las gráficas.", "error");
    }
  } else {
    mostrarToast("No se pudieron cargar las gráficas.", "error");
  }
}

// Rellena las 4 tarjetas KPI (activas = pendientes + en proceso como responsable).
function pintarKpis(totales) {
  const activas = Number(totales.pendientes || 0) + Number(totales.en_proceso || 0);
  document.getElementById("kpiActivas").textContent = activas;
  document.getElementById("kpiEnProceso").textContent = Number(totales.en_proceso || 0);
  document.getElementById("kpiResueltasMes").textContent = Number(totales.resueltas_mes || 0);
  document.getElementById("kpiApoyo").textContent = Number(totales.apoyo_activas || 0);
}

// Lista "Atiende primero": cada fila enlaza al detalle de la incidencia.
function pintarLista(activas) {
  const cont = document.getElementById("listaAtender");
  if (!activas.length) {
    cont.innerHTML = estadoVacioHtml(
      "bi-check2-circle",
      "Sin incidencias activas",
      "No tienes trabajo pendiente por ahora.",
    );
    return;
  }

  cont.innerHTML = "";
  activas.forEach(function (inc) {
    const prio = prioridadConfig[inc.prioridad_incidencia] || prioridadConfig.BAJA;
    const estado = estadoConfig[inc.estado_incidencia] || estadoConfig.PENDIENTE;

    const fila = document.createElement("a");
    fila.className = "atender-item";
    fila.href = rutaDetalleIncidencia(inc.id_incidencia, "tecnico");

    const badge = document.createElement("span");
    badge.className = "badge " + prio.clase;
    badge.title = "Prioridad " + prio.texto.toLowerCase();
    badge.innerHTML = '<i class="bi ' + prio.icono + '" aria-hidden="true"></i>';

    const info = document.createElement("span");
    info.className = "atender-info";
    const nombre = document.createElement("span");
    nombre.className = "atender-nombre";
    nombre.textContent = inc.nombre_incidencia;
    const meta = document.createElement("span");
    meta.className = "atender-meta";
    meta.textContent =
      codigoIncidencia(inc.id_incidencia) +
      " · " +
      estado.texto +
      " · " +
      tiempoRelativo(inc.created_at) +
      (inc.rol_asignado === "APOYO" ? " · Apoyo" : "");
    info.append(nombre, meta);

    fila.append(badge, info);
    cont.appendChild(fila);
  });
}

// Mapa con un pin por incidencia activa (color según estado); el clic lleva al detalle.
function pintarMapa(activas) {
  const cont = document.getElementById("mapaAsignadas");
  // El panel se repinta en cada evento de tiempo real: reseteamos el contenedor para no chocar con "Map container is already initialized".
  if (cont._leaflet_id) {
    cont._leaflet_id = null;
    cont.innerHTML = "";
  }

  if (!activas.length) {
    cont.innerHTML =
      '<p class="text-muted small p-3 mb-0">No hay incidencias activas que ubicar.</p>';
    return;
  }

  const pines = activas.map(function (inc) {
    return {
      id: inc.id_incidencia,
      lat: Number(inc.latitud_incidencia),
      lng: Number(inc.longitud_incidencia),
      titulo: codigoIncidencia(inc.id_incidencia) + " — " + inc.nombre_incidencia,
      color: colorEstado(inc.estado_incidencia),
    };
  });

  const mapa = crearMapaIncidencias("mapaAsignadas");
  mapa.pintarPines(pines, function (id) {
    window.location.href = rutaDetalleIncidencia(id, "tecnico");
  });
}

// "2026-06-29" -> "29 jun" (la fecha del lunes de esa semana).
function etiquetaSemana(iso) {
  const f = new Date(iso + "T00:00:00");
  return f.toLocaleDateString("es", { day: "numeric", month: "short" });
}

// Crea (o recrea) la dona por estado y las barras de resueltas por semana.
function pintarGraficas(datos) {
  graficos.forEach((g) => g.destroy());
  graficos = [];

  const colorResuelto = colorVar("--admin-success");
  const colorTexto = colorVar("--admin-muted");
  const colorGrid = colorVar("--admin-border");

  aplicarTemaChart();

  const totales = datos.totales || {};

  const canvasEstado = document.getElementById("graficoEstado");
  Chart.getChart(canvasEstado)?.destroy();
  graficos.push(crearDonaEstado(canvasEstado, totales));

  const semanas = datos.por_semana || [];
  const canvasSemana = document.getElementById("graficoSemana");
  Chart.getChart(canvasSemana)?.destroy();
  graficos.push(
    new Chart(canvasSemana, {
      type: "bar",
      data: {
        labels: semanas.map((s) => etiquetaSemana(s.semana)),
        datasets: [
          {
            label: "Resueltas",
            data: semanas.map((s) => Number(s.total || 0)),
            backgroundColor: colorResuelto,
            borderRadius: 4,
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

// Repinta las gráficas al cambiar de tema (lo dispara observarCambioDeTema de dashboard.js).
function repintarPorTema() {
  if (datosCache && graficos.length) pintarGraficas(datosCache);
}
