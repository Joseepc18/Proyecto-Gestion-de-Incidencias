// mis-incidencias.js — Vista maestro-detalle del usuario (lista + detalle embebido).

/* global apiFetch, aplicarMenuRol, mostrarToast, crearMapaIncidencias, escaparHtml, badgeEstadoHtml, badgePrioridadHtml, rutaDetalleIncidencia, codigoIncidencia, estadoVacioHtml, requerirSesion, cablearLogout */

let usuarioActual = null;
let mapa = null;

// Color del pin según el estado de la incidencia
const colorEstado = {
  PENDIENTE: "#dc2626",
  EN_PROCESO: "#d97706",
  RESUELTO: "#16a34a",
};

document.addEventListener("DOMContentLoaded", async function () {
  usuarioActual = await requerirSesion();
  if (!usuarioActual) return;
  aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "");
  cablearLogout();

  let timerBusqueda = null;
  document.getElementById("filtroBusqueda").addEventListener("input", function () {
    clearTimeout(timerBusqueda);
    timerBusqueda = setTimeout(cargarLista, 400);
  });

  inicializarFiltroEstado();

  // Delegación: el click en una tarjeta del feed dispara su selección.
  document.getElementById("listaIncidencias").addEventListener("click", function (e) {
    const card = e.target.closest("[data-id]");
    if (!card) return;
    seleccionarIncidencia(Number(card.dataset.id));
  });

  // Boton Volver (solo movil): regresa de la vista de detalle a la lista.
  document.getElementById("btnVolverMapa").addEventListener("click", function () {
    document.querySelector(".mis-mapa-main").classList.remove("mis-ver-detalle");
  });

  mapa = crearMapaIncidencias("mapaMisIncidencias");

  cargarLista();
});

let paginaActual = 1;
let porPagina = 10;
// Filtro de estado del feed ('' = todos).
let filtroEstado = "";

// Paso 2 — Trae las incidencias del usuario y pinta las tarjetas en #listaIncidencias.
async function cargarLista() {
  const contenedor = document.getElementById("listaIncidencias");

  const params = new URLSearchParams();
  params.set("page", paginaActual);
  params.set("per_page", porPagina);
  const busqueda = document.getElementById("filtroBusqueda").value.trim();
  if (busqueda) params.set("busqueda", busqueda);
  if (filtroEstado) params.set("estado", filtroEstado);

  try {
    const respuesta = await apiFetch("/incidencias?" + params.toString());
    const incidencias = respuesta.data;

    if (incidencias.length === 0) {
      const hayFiltro = busqueda || filtroEstado;
      contenedor.innerHTML = hayFiltro
        ? estadoVacioHtml(
            "bi-search",
            "Sin coincidencias",
            "Ninguna incidencia coincide con la búsqueda o el filtro.",
          )
        : estadoVacioHtml(
            "bi-clipboard-check",
            "Aún no tienes incidencias",
            "Cuando reportes una incidencia aparecerá aquí.",
          );
      document.getElementById("paginacionMis").innerHTML = "";
      if (mapa) mapa.pintarPines([], seleccionarIncidencia);
      return;
    }

    contenedor.innerHTML = incidencias
      .map(function (inc) {
        const ciudad = inc.ciudad ? inc.ciudad.nombre_ciudad : "Sin ubicación";
        const id = inc.id_incidencia;

        return (
          '<div class="incidencia-card" data-id="' +
          id +
          '">' +
          '<div class="d-flex justify-content-between align-items-start gap-2">' +
          '<span class="card-codigo">' +
          codigoIncidencia(id) +
          "</span>" +
          badgeEstadoHtml(inc.estado_incidencia) +
          "</div>" +
          '<p class="card-titulo">' +
          escaparHtml(inc.nombre_incidencia) +
          "</p>" +
          '<span class="card-ubicacion"><i class="bi bi-geo-alt me-1"></i>' +
          escaparHtml(ciudad) +
          "</span>" +
          "</div>"
        );
      })
      .join("");

    // Paginación simple de flechas (‹ ›): no requiere hacer scroll hacia los números.
    const current = respuesta.current_page || 1;
    const last = respuesta.last_page || 1;
    const total = respuesta.total || 0;
    renderFlechasPaginacion(current, last, total, respuesta.from || 0, respuesta.to || 0);

    if (mapa) {
      const pines = incidencias
        .filter(function (i) {
          return i.latitud_incidencia != null && i.longitud_incidencia != null;
        })
        .map(function (i) {
          return {
            id: i.id_incidencia,
            lat: Number(i.latitud_incidencia),
            lng: Number(i.longitud_incidencia),
            // titulo en crudo: mapa.js lo escapa dentro del bindPopup (defensa en profundidad).
            titulo: codigoIncidencia(i.id_incidencia) + " — " + i.nombre_incidencia,
            color: colorEstado[i.estado_incidencia] || "#2563eb",
          };
        });
      mapa.pintarPines(pines, seleccionarIncidencia);
    }
  } catch (error) {
    contenedor.innerHTML =
      '<p class="text-danger small text-center py-4 mb-0">' + escaparHtml(error.message) + "</p>";
  }
}

// No usa paginacion.js: evita forzar el scroll del panel hacia los números
function renderFlechasPaginacion(current, last, total, from, to) {
  const cont = document.getElementById("paginacionMis");
  if (!cont) return;

  if (last <= 1) {
    cont.innerHTML = "";
    return;
  }

  const primera = current === 1;
  const ultima = current === last;

  cont.innerHTML =
    '<button type="button" class="mis-pag-btn" data-page="' +
    (current - 1) +
    '" ' +
    (primera ? "disabled" : "") +
    ' aria-label="Página anterior"><i class="bi bi-chevron-left"></i></button>' +
    '<span class="mis-pag-info">Pág. ' +
    current +
    " / " +
    last +
    " · " +
    from +
    "–" +
    to +
    " / " +
    total +
    "</span>" +
    '<button type="button" class="mis-pag-btn" data-page="' +
    (current + 1) +
    '" ' +
    (ultima ? "disabled" : "") +
    ' aria-label="Página siguiente"><i class="bi bi-chevron-right"></i></button>';

  cont.querySelectorAll("[data-page]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      if (btn.disabled) return;
      const p = parseInt(btn.getAttribute("data-page"), 10);
      if (p >= 1 && p <= last && p !== current) {
        paginaActual = p;
        cargarLista();
      }
    });
  });
}

// Paso 3 — Carga el resumen liviano de la incidencia en la tarjeta derecha.
async function seleccionarIncidencia(id) {
  document.querySelectorAll(".incidencia-card").forEach(function (card) {
    card.classList.toggle("activa", Number(card.dataset.id) === id);
  });

  try {
    const inc = await apiFetch("/incidencias/" + id);

    const esAdmin = usuarioActual.rol && usuarioActual.rol.nombre_rol === "admin";
    const editable = esAdmin || inc.estado_incidencia === "PENDIENTE";
    document.getElementById("avisoEdicion").classList.toggle("d-none", editable);

    document.getElementById("detalleCodigo").textContent = codigoIncidencia(inc.id_incidencia);
    document.getElementById("detalleTitulo").textContent = inc.nombre_incidencia;
    document.getElementById("detalleFecha").textContent =
      "Creada: " + new Date(inc.created_at).toLocaleString("es-EC");

    const spanEstado = document.getElementById("detalleEstado");
    spanEstado.innerHTML = badgeEstadoHtml(inc.estado_incidencia);

    document.getElementById("detallePrioridad").innerHTML = badgePrioridadHtml(
      inc.prioridad_incidencia,
    );

    const ciudad = inc.ciudad ? inc.ciudad.nombre_ciudad : "Sin ciudad";
    const direccion = inc.direccion_incidencia ? " — " + inc.direccion_incidencia : "";
    document.getElementById("detalleUbicacion").textContent = ciudad + direccion;

    const imgCompacta = document.getElementById("detalleImagen");
    if (inc.evidencias && inc.evidencias.length > 0) {
      imgCompacta.src = "/storage/" + inc.evidencias[0].url_evidencia;
      imgCompacta.classList.remove("d-none");
    } else {
      imgCompacta.removeAttribute("src");
      imgCompacta.classList.add("d-none");
    }

    const rol = usuarioActual.rol ? usuarioActual.rol.nombre_rol : "";
    document.getElementById("btnVerDetalles").href = rutaDetalleIncidencia(id, rol);

    document.getElementById("detalleVacio").classList.add("d-none");
    document.getElementById("detalleContenido").classList.remove("d-none");

    // En movil se pasa a la vista de detalle (mapa + tarjeta). El mapa estaba oculto,
    // asi que tras mostrarlo Mapbox debe recalcular su tamano (resize) o sale en gris.
    document.querySelector(".mis-mapa-main").classList.add("mis-ver-detalle");
    requestAnimationFrame(function () {
      if (mapa) {
        mapa.map.resize();
        mapa.enfocar(id);
      }
    });
  } catch (error) {
    mostrarToast("Error al cargar el detalle: " + error.message, "error");
  }
}

// No muestra texto en el botón, para ahorrar ancho en el panel
function inicializarFiltroEstado() {
  document
    .querySelectorAll("#btnFiltroEstado + .dropdown-menu .dropdown-item")
    .forEach(function (item) {
      item.addEventListener("click", function (e) {
        e.preventDefault();
        document
          .querySelectorAll("#btnFiltroEstado + .dropdown-menu .dropdown-item")
          .forEach(function (i) {
            i.classList.remove("active");
          });
        this.classList.add("active");
        filtroEstado = this.dataset.estado || "";
        paginaActual = 1;
        cargarLista();
      });
    });
}
