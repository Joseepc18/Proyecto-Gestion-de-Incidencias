// mis-incidencias.js — Vista maestro-detalle del usuario (lista + detalle embebido).

/* global apiFetch, obtenerToken, eliminarToken, aplicarMenuRol, mostrarToast, crearMapaIncidencias, escaparHtml, badgeEstadoHtml, prioridadConfig, rutaDetalleIncidencia */

let usuarioActual = null;
let mapa = null;

// Color del pin según el estado de la incidencia
const colorEstado = {
  PENDIENTE: "#dc2626",
  EN_PROCESO: "#d97706",
  RESUELTO: "#16a34a",
};

// Genera un código legible a partir del id (INC-0001).
function codigoIncidencia(id) {
  return "INC-" + String(id).padStart(4, "0");
}

document.addEventListener("DOMContentLoaded", async function () {
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

  try {
    usuarioActual = await apiFetch("/user");
    document.getElementById("nombreUsuario").textContent = usuarioActual.name;

    aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "");
  } catch {
    eliminarToken();
    window.location.href = "../login/login.html";
    return;
  }

  document.getElementById("btnLogout").addEventListener("click", async function (e) {
    e.preventDefault();
    this.classList.add("pe-none", "opacity-50");
    try {
      await apiFetch("/logout", { method: "POST" });
    } catch {
      /* ignorar */
    }
    eliminarToken();
    window.location.href = "../login/login.html";
  });

  let timerBusqueda = null;
  document.getElementById("filtroBusqueda").addEventListener("input", function () {
    clearTimeout(timerBusqueda);
    timerBusqueda = setTimeout(cargarLista, 400);
  });

  mapa = crearMapaIncidencias("mapaMisIncidencias");
  setTimeout(function () {
    mapa.map.invalidateSize();
  }, 200);

  cargarLista();
});

let paginaActual = 1;
let porPagina = 10;

// Paso 2 — Trae las incidencias del usuario y pinta las tarjetas en #listaIncidencias.
async function cargarLista() {
  const contenedor = document.getElementById("listaIncidencias");
  const info = document.getElementById("listaInfo");

  const params = new URLSearchParams();
  params.set("page", paginaActual);
  params.set("per_page", porPagina);
  const busqueda = document.getElementById("filtroBusqueda").value.trim();
  if (busqueda) params.set("busqueda", busqueda);

  try {
    const respuesta = await apiFetch("/incidencias?" + params.toString());
    const incidencias = respuesta.data;

    if (incidencias.length === 0) {
      contenedor.innerHTML =
        '<p class="text-muted small text-center py-4 mb-0">No se encontraron incidencias.</p>';
      info.textContent = "";
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
          '" onclick="seleccionarIncidencia(' +
          id +
          ')">' +
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

    info.textContent =
      "Mostrando " + incidencias.length + " de " + respuesta.total + " incidencias";

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
            titulo: codigoIncidencia(i.id_incidencia) + " — " + escaparHtml(i.nombre_incidencia),
            color: colorEstado[i.estado_incidencia] || "#2563eb",
          };
        });
      mapa.pintarPines(pines, seleccionarIncidencia);
    }
  } catch (error) {
    contenedor.innerHTML =
      '<p class="text-danger small text-center py-4 mb-0">' + escaparHtml(error.message) + "</p>";
    info.textContent = "";
  }
}

// Render compacto de flechas (‹ página ›) para el feed de Mis Incidencias.
// Vive solo en esta página: no usa el helper compartido paginacion.js para no forzar
// el scroll del panel hacia los números cuando hay muchas incidencias.
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

    const pri = prioridadConfig[inc.prioridad_incidencia] || {
      clase: "",
      icono: "",
      texto: inc.prioridad_incidencia,
    };
    const spanPri = document.getElementById("detallePrioridad");
    spanPri.className = "badge " + pri.clase;
    spanPri.innerHTML = '<i class="bi ' + pri.icono + ' me-1"></i>' + pri.texto;

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

    if (mapa) mapa.enfocar(id);
  } catch (error) {
    mostrarToast("Error al cargar el detalle: " + error.message, "error");
  }
}
