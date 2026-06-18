// incidencias.js — Listado, filtros, paginación y acciones.

/* global apiFetch, obtenerToken, eliminarToken, bootstrap */

let usuarioActual = null;

document.addEventListener("DOMContentLoaded", async function () {
  // Guard
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

  // Cargar usuario
  try {
    usuarioActual = await apiFetch("/user");
    document.getElementById("nombreUsuario").textContent = usuarioActual.name;

    if (usuarioActual.rol && usuarioActual.rol.nombre_rol === "admin") {
      document.getElementById("navUsuarios").classList.remove("d-none");
    }
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

  // Catálogos
  async function cargarCatalogos() {
    try {
      const tipos = await apiFetch("/catalogos/tipos-incidencia");
      const selectTipo = document.getElementById("filtroTipo");
      tipos.forEach(function (tipo) {
        const op = document.createElement("option");
        op.value = tipo.id_tipo_incidencia;
        op.textContent = tipo.nombre_tipo_incidencia;
        selectTipo.appendChild(op);
      });

      const ciudades = await apiFetch("/catalogos/ciudades");
      const selectCiudad = document.getElementById("filtroCiudad");
      ciudades.forEach(function (ciudad) {
        const op = document.createElement("option");
        op.value = ciudad.id_ciudad;
        op.textContent = ciudad.nombre_ciudad;
        selectCiudad.appendChild(op);
      });
    } catch (error) {
      console.error("Error cargando catálogos:", error);
    }
  }

  // Incidencias
  let paginaActual = 1;

  async function cargarIncidencias() {
    const params = new URLSearchParams();
    params.set("page", paginaActual);

    const busqueda = document.getElementById("filtroBusqueda").value.trim();
    if (busqueda) params.set("busqueda", busqueda);

    const estado = document.getElementById("filtroEstado").value;
    if (estado) params.set("estado", estado);

    const prioridad = document.getElementById("filtroPrioridad").value;
    if (prioridad) params.set("prioridad", prioridad);

    const tipo = document.getElementById("filtroTipo").value;
    if (tipo) params.set("tipo_id", tipo);

    const ciudad = document.getElementById("filtroCiudad").value;
    if (ciudad) params.set("ciudad_id", ciudad);

    try {
      const respuesta = await apiFetch("/incidencias?" + params.toString());
      renderizarTabla(respuesta.data);
      actualizarPaginacion(respuesta);
    } catch (error) {
      console.error("Error cargando incidencias:", error);
    }
  }

  // Tabla
  function renderizarTabla(incidencias) {
    const tbody = document.getElementById("tbodyIncidencias");

    if (incidencias.length === 0) {
      tbody.innerHTML =
        '<tr><td colspan="7" class="text-center text-muted py-4">' +
        "No se encontraron incidencias</td></tr>";
      return;
    }

    tbody.innerHTML = incidencias
      .map(function (inc) {
        const estadoConfig = {
          PENDIENTE: {
            clase: "badge-estado-pendiente",
            icono: "bi-clock-history",
            texto: "Pendiente",
          },
          EN_PROCESO: {
            clase: "badge-estado-proceso",
            icono: "bi-gear-wide-connected",
            texto: "En proceso",
          },
          RESUELTO: {
            clase: "badge-estado-resuelto",
            icono: "bi-check2-circle",
            texto: "Resuelto",
          },
        };
        const prioridadConfig = {
          ALTA: { clase: "text-bg-danger", icono: "bi-fire", texto: "Alta" },
          MEDIA: { clase: "text-bg-warning", icono: "bi-shield-exclamation", texto: "Media" },
          BAJA: { clase: "text-bg-success", icono: "bi-arrow-down-circle", texto: "Baja" },
        };
        const est = estadoConfig[inc.estado_incidencia] || {
          clase: "",
          icono: "",
          texto: inc.estado_incidencia,
        };
        const pri = prioridadConfig[inc.prioridad_incidencia] || {
          clase: "",
          icono: "",
          texto: inc.prioridad_incidencia,
        };

        const nombreTipo =
          inc.subtipo && inc.subtipo.tipo ? inc.subtipo.tipo.nombre_tipo_incidencia : "—";

        const nombreCiudad = inc.ciudad ? inc.ciudad.nombre_ciudad : "—";
        const fecha = new Date(inc.created_at).toLocaleDateString("es-EC");

        const esAdmin = usuarioActual.rol && usuarioActual.rol.nombre_rol === "admin";
        const esAutor = inc.id_usuario === usuarioActual.id;
        const opcionEliminar =
          esAdmin || esAutor
            ? '<li><a class="dropdown-item text-danger" href="#" onclick="eliminarIncidencia(' +
              inc.id_incidencia +
              '); return false;">' +
              '<i class="bi bi-trash me-2"></i>Eliminar</a></li>'
            : "";

        const acciones =
          '<div class="dropdown">' +
          '<button class="btn btn-light btn-sm" data-bs-toggle="dropdown" aria-expanded="false">' +
          '<i class="bi bi-three-dots-vertical"></i>' +
          "</button>" +
          '<ul class="dropdown-menu dropdown-menu-end">' +
          '<li><a class="dropdown-item" href="#" onclick="verDetalle(' +
          inc.id_incidencia +
          '); return false;">' +
          '<i class="bi bi-eye me-2"></i>Ver detalle</a></li>' +
          opcionEliminar +
          "</ul>" +
          "</div>";

        return (
          "<tr>" +
          "<td>" +
          inc.nombre_incidencia +
          "</td>" +
          '<td><span class="badge ' +
          est.clase +
          '">' +
          '<i class="bi ' +
          est.icono +
          ' me-1"></i>' +
          est.texto +
          "</span></td>" +
          '<td><span class="badge ' +
          pri.clase +
          '">' +
          '<i class="bi ' +
          pri.icono +
          ' me-1"></i>' +
          pri.texto +
          "</span></td>" +
          "<td>" +
          nombreTipo +
          "</td>" +
          "<td>" +
          nombreCiudad +
          "</td>" +
          "<td>" +
          fecha +
          "</td>" +
          '<td class="text-end">' +
          acciones +
          "</td>" +
          "</tr>"
        );
      })
      .join("");
  }

  // Paginación
  function actualizarPaginacion(respuesta) {
    document.getElementById("infoPaginacion").textContent =
      "Página " +
      respuesta.current_page +
      " de " +
      respuesta.last_page +
      " (" +
      respuesta.total +
      " incidencias)";

    const btnAnt = document.getElementById("btnAnterior");
    const btnSig = document.getElementById("btnSiguiente");
    btnAnt.disabled = respuesta.current_page <= 1;
    btnSig.disabled = respuesta.current_page >= respuesta.last_page;
  }

  // Filtros
  document.getElementById("filtroEstado").addEventListener("change", function () {
    paginaActual = 1;
    cargarIncidencias();
  });
  document.getElementById("filtroPrioridad").addEventListener("change", function () {
    paginaActual = 1;
    cargarIncidencias();
  });
  document.getElementById("filtroTipo").addEventListener("change", function () {
    paginaActual = 1;
    cargarIncidencias();
  });
  document.getElementById("filtroCiudad").addEventListener("change", function () {
    paginaActual = 1;
    cargarIncidencias();
  });

  // Búsqueda con debounce 400ms
  let timerBusqueda = null;
  document.getElementById("filtroBusqueda").addEventListener("input", function () {
    clearTimeout(timerBusqueda);
    timerBusqueda = setTimeout(function () {
      paginaActual = 1;
      cargarIncidencias();
    }, 400);
  });

  // Botones paginación
  document.getElementById("btnAnterior").addEventListener("click", function () {
    if (paginaActual > 1) {
      paginaActual--;
      cargarIncidencias();
    }
  });
  document.getElementById("btnSiguiente").addEventListener("click", function () {
    paginaActual++;
    cargarIncidencias();
  });

  // Arranque
  cargarCatalogos();
  cargarIncidencias();
});

// Funciones globales (se llaman desde onclick en el HTML)
// eslint-disable-next-line no-unused-vars
async function verDetalle(id) {
  const modal = new bootstrap.Modal(document.getElementById("modalDetalle"));
  const cargando = document.getElementById("detalleCargando");
  const contenido = document.getElementById("detalleContenido");

  cargando.classList.remove("d-none");
  contenido.classList.add("d-none");
  modal.show();

  try {
    const inc = await apiFetch("/incidencias/" + id);

    // Título
    document.getElementById("modalDetalleLabel").textContent = inc.nombre_incidencia;

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

// eslint-disable-next-line no-unused-vars
async function eliminarIncidencia(id) {
  if (!confirm("¿Estás seguro de eliminar esta incidencia?")) return;

  try {
    await apiFetch("/incidencias/" + id, { method: "DELETE" });
    location.reload();
  } catch (error) {
    alert("Error: " + error.message);
  }
}
