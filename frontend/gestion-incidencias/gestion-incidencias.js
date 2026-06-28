// gestion-incidencias.js — Listado, filtros, paginación y acciones.

/* global apiFetch, obtenerToken, eliminarToken, aplicarMenuRol, confirmar, mostrarToast, toastFlash, escaparHtml, estadoConfig, prioridadConfig, rutaDetalleIncidencia */

let usuarioActual = null;

document.addEventListener("DOMContentLoaded", async function () {
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

  try {
    usuarioActual = await apiFetch("/user");
    document.getElementById("nombreUsuario").textContent = usuarioActual.name;

    aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "");

    // Esta tabla de gestión es solo para admin; el resto va a "Mis incidencias".
    if (!usuarioActual.rol || usuarioActual.rol.nombre_rol !== "admin") {
      window.location.href = "../mis-incidencias/mis-incidencias.html";
      return;
    }
  } catch {
    eliminarToken();
    window.location.href = "../login/login.html";
    return;
  }

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
          escaparHtml(inc.nombre_incidencia) +
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
          escaparHtml(nombreTipo) +
          "</td>" +
          "<td>" +
          escaparHtml(nombreCiudad) +
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

  cargarCatalogos();
  cargarIncidencias();
});

// Funciones globales (se llaman desde onclick en el HTML).
// eslint-disable-next-line no-unused-vars
function verDetalle(id) {
  window.location.href = rutaDetalleIncidencia(id, "admin");
}

// eslint-disable-next-line no-unused-vars
async function eliminarIncidencia(id) {
  const ok = await confirmar({
    titulo: "Eliminar incidencia",
    mensaje: "Esta acción no se puede deshacer. ¿Deseas continuar?",
    textoConfirmar: "Eliminar",
    peligro: true,
  });
  if (!ok) return;

  try {
    await apiFetch("/incidencias/" + id, { method: "DELETE" });
    toastFlash("Incidencia eliminada", "success");
    location.reload();
  } catch (error) {
    mostrarToast("Error: " + error.message, "error");
  }
}
