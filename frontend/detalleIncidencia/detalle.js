// detalle.js — Página de detalle de una incidencia (antes era un modal).

/* global apiFetch, obtenerToken, eliminarToken, aplicarMenuRol */

document.addEventListener("DOMContentLoaded", async function () {
  // Guard de sesión
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

  // Cargar usuario (nombre en navbar + mostrar Usuarios si es admin)
  try {
    const usuarioActual = await apiFetch("/user");
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

  // Leer el id de la incidencia desde la URL (?id=5)
  const params = new URLSearchParams(window.location.search);
  const id = params.get("id");

  if (!id) {
    window.location.href = "../incidencias/incidencias.html";
    return;
  }

  cargarDetalle(id);
});

async function cargarDetalle(id) {
  const cargando = document.getElementById("detalleCargando");
  const contenido = document.getElementById("detalleContenido");

  try {
    const inc = await apiFetch("/incidencias/" + id);

    // Título (encabezado de página)
    document.getElementById("detalleTitulo").textContent = inc.nombre_incidencia;

    // Estado
    const estadoConfig = {
      PENDIENTE: { clase: "badge-estado-pendiente", icono: "bi-clock-history", texto: "Pendiente" },
      EN_PROCESO: {
        clase: "badge-estado-proceso",
        icono: "bi-gear-wide-connected",
        texto: "En proceso",
      },
      RESUELTO: { clase: "badge-estado-resuelto", icono: "bi-check2-circle", texto: "Resuelto" },
    };
    const est = estadoConfig[inc.estado_incidencia] || {
      clase: "",
      icono: "",
      texto: inc.estado_incidencia,
    };
    const spanEstado = document.getElementById("detalleEstado");
    spanEstado.className = "badge " + est.clase;
    spanEstado.innerHTML = '<i class="bi ' + est.icono + ' me-1"></i>' + est.texto;

    // Prioridad
    const prioridadConfig = {
      ALTA: { clase: "text-bg-danger", icono: "bi-fire", texto: "Alta" },
      MEDIA: { clase: "text-bg-warning", icono: "bi-shield-exclamation", texto: "Media" },
      BAJA: { clase: "text-bg-success", icono: "bi-arrow-down-circle", texto: "Baja" },
    };
    const pri = prioridadConfig[inc.prioridad_incidencia] || {
      clase: "",
      icono: "",
      texto: inc.prioridad_incidencia,
    };
    const spanPri = document.getElementById("detallePrioridad");
    spanPri.className = "badge " + pri.clase;
    spanPri.innerHTML = '<i class="bi ' + pri.icono + ' me-1"></i>' + pri.texto;

    // Descripción
    const bloqueDesc = document.getElementById("detalleDescripcionBloque");
    if (inc.descripcion_incidencia) {
      bloqueDesc.classList.remove("d-none");
      document.getElementById("detalleDescripcion").textContent = inc.descripcion_incidencia;
    } else {
      bloqueDesc.classList.add("d-none");
    }

    // Tipo / Subtipo
    const tipo = inc.subtipo && inc.subtipo.tipo ? inc.subtipo.tipo.nombre_tipo_incidencia : "—";
    const subtipo = inc.subtipo ? inc.subtipo.nombre_subtipo_incidencia : "—";
    document.getElementById("detalleTipoSubtipo").textContent = tipo + " → " + subtipo;

    // Ciudad
    document.getElementById("detalleCiudad").textContent = inc.ciudad
      ? inc.ciudad.nombre_ciudad
      : "—";

    // Coordenadas
    document.getElementById("detalleCoordenadas").textContent =
      inc.latitud_incidencia + ", " + inc.longitud_incidencia;

    // Dirección
    document.getElementById("detalleDireccion").textContent =
      inc.direccion_incidencia || "No especificada";

    // Usuario
    document.getElementById("detalleUsuario").textContent = inc.usuario ? inc.usuario.name : "—";

    // Fecha
    document.getElementById("detalleFecha").textContent = new Date(inc.created_at).toLocaleString(
      "es-EC",
    );

    // Fotos
    const bloqueFotos = document.getElementById("detalleFotosBloque");
    const contenedorFotos = document.getElementById("detalleFotos");
    if (inc.evidencias && inc.evidencias.length > 0) {
      bloqueFotos.classList.remove("d-none");
      contenedorFotos.innerHTML = inc.evidencias
        .map(function (ev) {
          return (
            '<a href="/storage/' +
            ev.url_evidencia +
            '" target="_blank">' +
            '<img src="/storage/' +
            ev.url_evidencia +
            '" class="rounded" ' +
            'style="width:120px;height:120px;object-fit:cover" alt="Evidencia" />' +
            "</a>"
          );
        })
        .join("");
    } else {
      bloqueFotos.classList.add("d-none");
      contenedorFotos.innerHTML = "";
    }

    cargando.classList.add("d-none");
    contenido.classList.remove("d-none");
  } catch (error) {
    cargando.innerHTML =
      '<p class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>' +
      error.message +
      "</p>";
  }
}
