// misIncidencias.js — Vista maestro-detalle del usuario (lista + detalle embebido).

/* global apiFetch, obtenerToken, eliminarToken, aplicarMenuRol, mostrarToast */

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

  // Enlace del aviso: lleva a la caja de comentarios
  document.getElementById("linkComentarios").addEventListener("click", function (e) {
    e.preventDefault();
    const textarea = document.getElementById("nuevoComentario");
    textarea.scrollIntoView({ behavior: "smooth", block: "center" });
    textarea.focus();
  });

  // Enviar un comentario nuevo
  document.getElementById("formComentario").addEventListener("submit", async function (e) {
    e.preventDefault();
    if (!incidenciaSeleccionada) return;

    const textarea = document.getElementById("nuevoComentario");
    const btn = document.getElementById("btnEnviarComentario");
    const spinner = document.getElementById("comentarioSpinner");
    const texto = textarea.value.trim();
    if (!texto) return;

    btn.disabled = true;
    spinner.classList.remove("d-none");

    try {
      await apiFetch("/incidencias/" + incidenciaSeleccionada + "/comentarios", {
        method: "POST",
        body: JSON.stringify({ comentario: texto }),
      });
      textarea.value = "";
      await cargarComentarios(incidenciaSeleccionada);
      mostrarToast("Comentario enviado", "success");
    } catch (error) {
      mostrarToast("Error al enviar el comentario: " + error.message, "error");
    } finally {
      btn.disabled = false;
      spinner.classList.add("d-none");
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
    // Lanzar comentarios y responsables en paralelo con el detalle → un solo spinner
    cargarComentarios(id);
    cargarResponsables(id);

    const inc = await apiFetch("/incidencias/" + id);
    incidenciaSeleccionada = id;

    // El autor solo edita mientras está PENDIENTE; el admin siempre puede.
    const esAdmin = usuarioActual.rol && usuarioActual.rol.nombre_rol === "admin";
    const editable = esAdmin || inc.estado_incidencia === "PENDIENTE";
    document.getElementById("btnEditar").disabled = !editable;
    document.getElementById("avisoEdicion").classList.toggle("d-none", editable);

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
            '<img src="/storage/' +
            ev.url_evidencia +
            '" class="evidencia-foto rounded" data-lightbox="/storage/' +
            ev.url_evidencia +
            '" style="width:110px;height:110px;object-fit:cover" alt="Evidencia" />'
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
    mostrarToast("Error al cargar el detalle: " + error.message, "error");
  }
}

// Trae y pinta los comentarios de una incidencia.
async function cargarComentarios(id) {
  const contenedor = document.getElementById("detalleComentarios");

  try {
    const comentarios = await apiFetch("/incidencias/" + id + "/comentarios");

    if (comentarios.length === 0) {
      contenedor.innerHTML =
        '<p class="text-muted small mb-0">Aún no hay comentarios. Sé el primero.</p>';
      return;
    }

    contenedor.innerHTML = comentarios
      .map(function (c) {
        const autor = c.usuario ? c.usuario.name : "Usuario";
        const fecha = new Date(c.created_at).toLocaleString("es-EC");
        return (
          '<div class="comentario-item mb-2">' +
          '<div class="d-flex justify-content-between align-items-center mb-1">' +
          '<span class="fw-semibold small">' +
          autor +
          "</span>" +
          '<span class="comentario-meta small">' +
          fecha +
          "</span>" +
          "</div>" +
          '<p class="mb-0 small">' +
          c.comentario +
          "</p>" +
          "</div>"
        );
      })
      .join("");
  } catch (error) {
    contenedor.innerHTML =
      '<p class="text-danger small mb-0">No se pudieron cargar los comentarios: ' +
      error.message +
      "</p>";
  }
}

// Trae y muestra los técnicos asignados (solo lectura para el usuario).
async function cargarResponsables(id) {
  const cont = document.getElementById("misResponsables");

  try {
    const asignaciones = await apiFetch("/incidencias/" + id + "/asignaciones");

    if (asignaciones.length === 0) {
      cont.innerHTML = '<p class="text-muted small mb-0">Aún no hay técnicos asignados.</p>';
      return;
    }

    cont.innerHTML = "";
    asignaciones.forEach(function (asig) {
      const fila = document.createElement("div");
      fila.className = "d-flex align-items-center gap-2 mb-2";

      const badge = document.createElement("span");
      badge.className =
        "badge " + (asig.rol_asignado === "RESPONSABLE" ? "text-bg-primary" : "text-bg-secondary");
      badge.textContent = asig.rol_asignado === "RESPONSABLE" ? "Responsable" : "Apoyo";
      fila.appendChild(badge);

      const nombre = document.createElement("span");
      nombre.className = "small";
      nombre.textContent = asig.usuario ? asig.usuario.name : "—";
      fila.appendChild(nombre);

      cont.appendChild(fila);
    });
  } catch {
    cont.innerHTML =
      '<p class="text-danger small mb-0">No se pudieron cargar los responsables.</p>';
  }
}
