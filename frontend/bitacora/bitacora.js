// bitacora.js — Listado de errores registrados por el sistema (requiere bitacora.ver).

/* global apiFetch, aplicarMenuRol, tienePermiso, mostrarToast, renderizarPaginacion, filaVaciaHtml, celdaTabla, requerirSesion, cablearLogout */

// Etiqueta legible de cada tipo_error del CHECK de la tabla.
function etiquetaTipo(tipo) {
  const mapa = {
    AUTENTICACION: "Autenticación",
    VALIDACION: "Validación",
    BASE_DATOS: "Base de datos",
    SERVIDOR: "Servidor",
    ARCHIVO: "Archivo",
  };
  return mapa[tipo] || tipo;
}

document.addEventListener("DOMContentLoaded", async function () {
  const usuarioActual = await requerirSesion();
  if (!usuarioActual) return;

  aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "", usuarioActual.permisos);

  if (!tienePermiso("bitacora.ver")) {
    window.location.href = "../mis-incidencias/mis-incidencias.html";
    return;
  }

  cablearLogout();

  let paginaActual = 1;
  let porPagina = 10;

  async function cargarBitacora() {
    const params = new URLSearchParams();
    params.set("page", paginaActual);
    params.set("per_page", porPagina);

    const tipo = document.getElementById("filtroTipo").value;
    if (tipo) params.set("tipo_error", tipo);

    try {
      const respuesta = await apiFetch("/bitacora?" + params.toString());
      renderizarTabla(respuesta.data);
      renderizarPaginacion({
        respuesta: respuesta,
        idContenedor: "contenedorPaginacion",
        onPageChange: (p) => {
          paginaActual = p;
          cargarBitacora();
        },
        onPerPageChange: (pp) => {
          porPagina = pp;
          paginaActual = 1;
          cargarBitacora();
        },
        perPage: porPagina,
      });
    } catch (error) {
      mostrarToast("No se pudo cargar la bitácora: " + error.message, "error");
    }
  }

  function renderizarTabla(errores) {
    const tbody = document.getElementById("tbodyBitacora");
    tbody.innerHTML = "";

    if (errores.length === 0) {
      tbody.innerHTML = filaVaciaHtml(
        4,
        "bi-journal-check",
        "Sin errores",
        "No hay errores registrados para este filtro.",
      );
      return;
    }

    errores.forEach(function (err) {
      const tr = document.createElement("tr");

      const fecha = new Date(err.created_at).toLocaleString("es-EC");
      const usuario = err.usuario ? err.usuario.name : "Sistema";

      tr.appendChild(celdaTabla(fecha, "Fecha"));

      const tdTipo = document.createElement("td");
      tdTipo.dataset.label = "Tipo";
      const badge = document.createElement("span");
      badge.className = "badge text-bg-secondary";
      badge.textContent = etiquetaTipo(err.tipo_error);
      tdTipo.appendChild(badge);
      tr.appendChild(tdTipo);

      tr.appendChild(celdaTabla(usuario, "Usuario", true));
      tr.appendChild(celdaTabla(err.descripcion_error, "Descripción"));

      tbody.appendChild(tr);
    });
  }

  document.getElementById("filtroTipo").addEventListener("change", function () {
    paginaActual = 1;
    cargarBitacora();
  });

  cargarBitacora();
});
