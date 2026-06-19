// misIncidencias.js — Vista maestro-detalle del usuario (lista + detalle embebido).

/* global apiFetch, obtenerToken, eliminarToken, aplicarMenuRol */

let usuarioActual = null;
let incidenciaSeleccionada = null;

// Config de badges (compartida entre lista y detalle).
const estadoConfig = {
  PENDIENTE: { clase: "badge-estado-pendiente", icono: "bi-clock-history", texto: "Pendiente" },
  EN_PROCESO: {
    clase: "badge-estado-proceso",
    icono: "bi-gear-wide-connected",
    texto: "En proceso",
  },
  RESUELTO: { clase: "badge-estado-resuelto", icono: "bi-check2-circle", texto: "Resuelto" },
};
const prioridadConfig = {
  ALTA: { clase: "text-bg-danger", icono: "bi-fire", texto: "Alta" },
  MEDIA: { clase: "text-bg-warning", icono: "bi-shield-exclamation", texto: "Media" },
  BAJA: { clase: "text-bg-success", icono: "bi-arrow-down-circle", texto: "Baja" },
};

// Genera un código legible a partir del id (INC-0001).
function codigoIncidencia(id) {
  return "INC-" + String(id).padStart(4, "0");
}

document.addEventListener("DOMContentLoaded", async function () {
  // Guard de sesión
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

  // Cargar usuario (nombre en navbar + mostrar Usuarios si es admin)
  try {
    usuarioActual = await apiFetch("/user");
    document.getElementById("nombreUsuario").textContent = usuarioActual.name;

    aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "");
  } catch {
    eliminarToken();
    window.location.href = "../login/login.html";
    return;
  }

  // Logout
  document.getElementById("btnLogout").addEventListener("click", async function (e) {
    e.preventDefault();
    try {
      await apiFetch("/logout", { method: "POST" });
    } catch {
      /* ignorar */
    }
    eliminarToken();
    window.location.href = "../login/login.html";
  });

  // Búsqueda con debounce 400ms
  let timerBusqueda = null;
  document.getElementById("filtroBusqueda").addEventListener("input", function () {
    clearTimeout(timerBusqueda);
    timerBusqueda = setTimeout(cargarLista, 400);
  });

  // Botón "Editar Incidencia" → formulario de registro con ?id=
  document.getElementById("btnEditar").addEventListener("click", function () {
    if (incidenciaSeleccionada) {
      window.location.href = "../registrarIncidencias/registrar.html?id=" + incidenciaSeleccionada;
    }
  });

  // Arranque
  cargarLista();
});

// Paso 2 — Trae las incidencias del usuario y pinta las tarjetas en #listaIncidencias.
async function cargarLista() {
  const contenedor = document.getElementById("listaIncidencias");
  const info = document.getElementById("listaInfo");

  const params = new URLSearchParams();
  const busqueda = document.getElementById("filtroBusqueda").value.trim();
  if (busqueda) params.set("busqueda", busqueda);

  try {
    const respuesta = await apiFetch("/incidencias?" + params.toString());
    const incidencias = respuesta.data;

    if (incidencias.length === 0) {
      contenedor.innerHTML =
        '<p class="text-muted small text-center py-4 mb-0">No se encontraron incidencias.</p>';
      info.textContent = "";
      return;
    }

    contenedor.innerHTML = incidencias
      .map(function (inc) {
        const est = estadoConfig[inc.estado_incidencia] || {
          clase: "",
          icono: "",
          texto: inc.estado_incidencia,
        };
        const ciudad = inc.ciudad ? inc.ciudad.nombre_ciudad : "Sin ubicación";

        return (
          '<button type="button" class="incidencia-card" data-id="' +
          inc.id_incidencia +
          '" onclick="seleccionarIncidencia(' +
          inc.id_incidencia +
          ')">' +
          '<div class="d-flex justify-content-between align-items-start gap-2">' +
          '<span class="card-codigo">' +
          codigoIncidencia(inc.id_incidencia) +
          "</span>" +
          '<span class="badge ' +
          est.clase +
          '"><i class="bi ' +
          est.icono +
          ' me-1"></i>' +
          est.texto +
          "</span>" +
          "</div>" +
          '<p class="card-titulo">' +
          inc.nombre_incidencia +
          "</p>" +
          '<span class="card-ubicacion"><i class="bi bi-geo-alt me-1"></i>' +
          ciudad +
          "</span>" +
          "</button>"
        );
      })
      .join("");

    info.textContent =
      "Mostrando " + incidencias.length + " de " + respuesta.total + " incidencias";
  } catch (error) {
    contenedor.innerHTML =
      '<p class="text-danger small text-center py-4 mb-0">' + error.message + "</p>";
    info.textContent = "";
  }
}

// Paso 3 — Carga el detalle de una incidencia y lo muestra en el panel derecho.
// eslint-disable-next-line no-unused-vars
async function seleccionarIncidencia(id) {
  // Marcar la tarjeta activa
  document.querySelectorAll(".incidencia-card").forEach(function (card) {
    card.classList.toggle("activa", Number(card.dataset.id) === id);
  });

  try {
    const inc = await apiFetch("/incidencias/" + id);
    incidenciaSeleccionada = id;

    // Cabecera
    document.getElementById("detalleCodigo").textContent = codigoIncidencia(inc.id_incidencia);
    document.getElementById("detalleTitulo").textContent = inc.nombre_incidencia;
    document.getElementById("detalleFecha").textContent =
      "Creada: " + new Date(inc.created_at).toLocaleString("es-EC");

    // Estado
    const est = estadoConfig[inc.estado_incidencia] || {
      clase: "",
      icono: "",
      texto: inc.estado_incidencia,
    };
    const spanEstado = document.getElementById("detalleEstado");
    spanEstado.className = "badge " + est.clase;
    spanEstado.innerHTML = '<i class="bi ' + est.icono + ' me-1"></i>' + est.texto;

    // Prioridad
    const pri = prioridadConfig[inc.prioridad_incidencia] || {
      clase: "",
      icono: "",
      texto: inc.prioridad_incidencia,
    };
    const spanPri = document.getElementById("detallePrioridad");
    spanPri.className = "badge " + pri.clase;
    spanPri.innerHTML = '<i class="bi ' + pri.icono + ' me-1"></i>' + pri.texto;

    // Información general
    document.getElementById("detalleDescripcion").textContent =
      inc.descripcion_incidencia || "Sin descripción.";
    document.getElementById("detalleTipo").textContent =
      inc.subtipo && inc.subtipo.tipo ? inc.subtipo.tipo.nombre_tipo_incidencia : "—";
    document.getElementById("detalleSubtipo").textContent = inc.subtipo
      ? inc.subtipo.nombre_subtipo_incidencia
      : "—";

    const ciudad = inc.ciudad ? inc.ciudad.nombre_ciudad : "Sin ciudad";
    const direccion = inc.direccion_incidencia ? " — " + inc.direccion_incidencia : "";
    document.getElementById("detalleUbicacion").textContent = ciudad + direccion;

    // Evidencias (fotos reales del backend)
    const contenedorFotos = document.getElementById("detalleEvidencias");
    if (inc.evidencias && inc.evidencias.length > 0) {
      contenedorFotos.innerHTML = inc.evidencias
        .map(function (ev) {
          return (
            '<a href="/storage/' +
            ev.url_evidencia +
            '" target="_blank">' +
            '<img src="/storage/' +
            ev.url_evidencia +
            '" class="rounded" style="width:110px;height:110px;object-fit:cover" alt="Evidencia" />' +
            "</a>"
          );
        })
        .join("");
    } else {
      contenedorFotos.innerHTML = '<p class="text-muted small mb-0">Sin evidencias cargadas.</p>';
    }

    // Mostrar el panel de detalle
    document.getElementById("detalleVacio").classList.add("d-none");
    document.getElementById("detalleContenido").classList.remove("d-none");
  } catch (error) {
    alert("Error al cargar el detalle: " + error.message);
  }
}
