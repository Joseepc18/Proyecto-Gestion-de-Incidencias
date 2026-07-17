// mis-incidencias.js — Vista maestro-detalle del usuario (lista + detalle embebido).

/* global apiFetch, aplicarMenuRol, tienePermiso, mostrarToast, crearMapaIncidencias, escaparHtml, badgeEstadoHtml, badgePrioridadHtml, colorEstado, estadoParaVista, rutaDetalleIncidencia, codigoIncidencia, estadoVacioHtml, requerirSesion, cablearLogout, obtenerEcho */

let usuarioActual = null;
let mapa = null;
// Incidencias de la página actual y la seleccionada, para actualizarlas en vivo sin recargar.
let incidenciasActuales = [];
let seleccionadaId = null;
// Canales de updates suscritos (uno por incidencia visible); se limpian al repintar la lista.
const canalesSuscritos = new Set();

document.addEventListener("DOMContentLoaded", async function () {
  usuarioActual = await requerirSesion();
  if (!usuarioActual) return;
  aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "", usuarioActual.permisos);
  cablearLogout();

  // El ciudadano ve todo lo archivado como "Resuelto" (estados.js); un filtro "Archivado" aparte
  // no tendría sentido para él, ya no distingue esa etiqueta en ningún lado de esta pantalla.
  if (rolNombre() === "normal") {
    const opcionArchivado = document.querySelector('[data-estado="CERRADO"]');
    if (opcionArchivado) opcionArchivado.closest("li").remove();
  }

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

  // Colapsar/expandir el panel de la lista para ver el mapa completo (solo desktop/tablet).
  const btnColapsar = document.getElementById("btnColapsarFeed");
  const mapaMain = document.querySelector(".mis-mapa-main");
  btnColapsar.addEventListener("click", function () {
    const colapsado = mapaMain.classList.toggle("mis-feed-colapsado");
    btnColapsar.innerHTML = colapsado
      ? '<i class="bi bi-chevron-right" aria-hidden="true"></i>'
      : '<i class="bi bi-chevron-left" aria-hidden="true"></i>';
    btnColapsar.setAttribute(
      "aria-label",
      colapsado ? "Mostrar panel de incidencias" : "Ocultar panel de incidencias",
    );
    btnColapsar.title = colapsado ? "Mostrar panel de incidencias" : "Ocultar panel de incidencias";
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

    incidenciasActuales = incidencias;

    const total = respuesta.total || 0;
    document.getElementById("misFeedContador").textContent =
      total === 1 ? "1 incidencia" : total + " incidencias";

    if (incidencias.length === 0) {
      const hayFiltro = busqueda || filtroEstado;
      // El técnico no reporta, se le asignan incidencias: el vacío sin filtro cambia según el rol.
      const vacioSinFiltro =
        rolNombre() === "normal"
          ? estadoVacioHtml(
              "bi-clipboard-check",
              "Aún no tienes incidencias",
              "Cuando reportes una incidencia aparecerá aquí.",
            )
          : estadoVacioHtml(
              "bi-inbox",
              "Sin asignaciones",
              "Cuando te asignen una incidencia aparecerá aquí.",
            );
      contenedor.innerHTML = hayFiltro
        ? estadoVacioHtml(
            "bi-search",
            "Sin coincidencias",
            "Ninguna incidencia coincide con la búsqueda o el filtro.",
          )
        : vacioSinFiltro;
      document.getElementById("paginacionMis").innerHTML = "";
      refrescarMapa();
      suscribirIncidencias();
      return;
    }

    contenedor.innerHTML = incidencias.map(tarjetaHtml).join("");

    // Paginación simple de flechas (‹ ›): no requiere hacer scroll hacia los números.
    const current = respuesta.current_page || 1;
    const last = respuesta.last_page || 1;
    renderFlechasPaginacion(current, last, total, respuesta.from || 0, respuesta.to || 0);

    refrescarMapa();
    suscribirIncidencias();
  } catch (error) {
    contenedor.innerHTML =
      '<p class="text-danger small text-center py-4 mb-0">' + escaparHtml(error.message) + "</p>";
  }
}

// El ciudadano ve "Resuelto" en vez de "Archivado" (estados.js); admin/técnico ven el estado real.
function rolNombre() {
  return usuarioActual && usuarioActual.rol ? usuarioActual.rol.nombre_rol : "";
}

// HTML de una tarjeta del feed (reutilizado al pintar la lista y al actualizar una en vivo).
function tarjetaHtml(inc) {
  const ciudad = inc.ciudad ? inc.ciudad.nombre_ciudad : "Sin ubicación";
  const id = inc.id_incidencia;
  return (
    '<div class="incidencia-card' +
    (id === seleccionadaId ? " activa" : "") +
    '" data-id="' +
    id +
    '">' +
    '<div class="d-flex justify-content-between align-items-start gap-2">' +
    '<span class="card-codigo">' +
    codigoIncidencia(id) +
    "</span>" +
    badgeEstadoHtml(estadoParaVista(inc.estado_incidencia, rolNombre())) +
    "</div>" +
    '<p class="card-titulo">' +
    escaparHtml(inc.nombre_incidencia) +
    "</p>" +
    '<span class="card-ubicacion"><i class="bi bi-geo-alt me-1"></i>' +
    escaparHtml(ciudad) +
    "</span>" +
    "</div>"
  );
}

// Repinta los pines del mapa desde incidenciasActuales (recolorea según estado).
function refrescarMapa() {
  if (!mapa) return;
  const pines = incidenciasActuales
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
        color: colorEstado(estadoParaVista(i.estado_incidencia, rolNombre())),
      };
    });
  mapa.pintarPines(pines, seleccionarIncidencia);
}

// Suscribe cada incidencia visible a su canal de updates; deja las de la página anterior que ya no están.
function suscribirIncidencias() {
  const echo = obtenerEcho();
  if (!echo) return;

  const vigentes = new Set(incidenciasActuales.map((i) => String(i.id_incidencia)));

  // Baja las que ya no están en pantalla.
  canalesSuscritos.forEach(function (id) {
    if (!vigentes.has(id)) {
      echo.leave("incidencia.updates." + id);
      canalesSuscritos.delete(id);
    }
  });

  // Sube las nuevas.
  vigentes.forEach(function (id) {
    if (canalesSuscritos.has(id)) return;
    canalesSuscritos.add(id);
    echo.private("incidencia.updates." + id).listen(".IncidenciaActualizada", actualizarEnVivo);
  });
}

// Aplica un cambio de estado/prioridad en vivo: tarjeta + pin + panel embebido si es la seleccionada.
function actualizarEnVivo(e) {
  const inc = incidenciasActuales.find((i) => i.id_incidencia === e.id_incidencia);
  if (!inc) return;
  inc.estado_incidencia = e.estado_incidencia;
  inc.prioridad_incidencia = e.prioridad_incidencia;

  const card = document.querySelector('.incidencia-card[data-id="' + e.id_incidencia + '"]');
  if (card) card.outerHTML = tarjetaHtml(inc);
  refrescarMapa();
  if (seleccionadaId === e.id_incidencia) seleccionarIncidencia(e.id_incidencia);
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
  seleccionadaId = id;
  document.querySelectorAll(".incidencia-card").forEach(function (card) {
    card.classList.toggle("activa", Number(card.dataset.id) === id);
  });

  try {
    const inc = await apiFetch("/incidencias/" + id);

    const esAdmin = tienePermiso("incidencias.gestionar");
    const editable = esAdmin || inc.estado_incidencia === "PENDIENTE";
    document.getElementById("avisoEdicion").classList.toggle("d-none", editable);

    document.getElementById("detalleCodigo").textContent = codigoIncidencia(inc.id_incidencia);
    document.getElementById("detalleTitulo").textContent = inc.nombre_incidencia;
    document.getElementById("detalleFecha").textContent =
      "Creada: " + new Date(inc.created_at).toLocaleString("es-EC");

    const spanEstado = document.getElementById("detalleEstado");
    spanEstado.innerHTML = badgeEstadoHtml(estadoParaVista(inc.estado_incidencia, rolNombre()));

    document.getElementById("detallePrioridad").innerHTML = badgePrioridadHtml(
      inc.prioridad_incidencia,
    );

    const ciudad = inc.ciudad ? inc.ciudad.nombre_ciudad : "Sin ciudad";
    const direccion = inc.direccion_incidencia ? " — " + inc.direccion_incidencia : "";
    document.getElementById("detalleUbicacion").textContent = ciudad + direccion;

    const imgCompacta = document.getElementById("detalleImagen");
    if (inc.evidencias && inc.evidencias.length > 0) {
      imgCompacta.src = inc.evidencias[0].url_completa;
      imgCompacta.classList.remove("d-none");
    } else {
      imgCompacta.removeAttribute("src");
      imgCompacta.classList.add("d-none");
    }

    const rol = usuarioActual.rol ? usuarioActual.rol.nombre_rol : "";
    document.getElementById("btnVerDetalles").href = rutaDetalleIncidencia(id, rol);

    document.getElementById("detalleVacio").classList.add("d-none");
    document.getElementById("detalleContenido").classList.remove("d-none");

    // En movil se pasa a la vista de detalle; el mapa estaba oculto, asi que Leaflet recalcula su tamano (invalidateSize) o sale en gris.
    document.querySelector(".mis-mapa-main").classList.add("mis-ver-detalle");
    requestAnimationFrame(function () {
      if (mapa) {
        mapa.map.invalidateSize();
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
