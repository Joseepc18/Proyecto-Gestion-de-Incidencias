// papelera.js — Listado de incidencias eliminadas, restaurar y purgar (solo admin/super_admin).

/* global apiFetch, aplicarMenuRol, tienePermiso, mostrarToast, toastFlash, confirmar, renderizarPaginacion, crearMenuAcciones, filaVaciaHtml, celdaTabla, requerirSesion, cablearLogout, obtenerEcho */

document.addEventListener("DOMContentLoaded", async function () {
  const usuarioActual = await requerirSesion();
  if (!usuarioActual) return;

  aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "", usuarioActual.permisos);

  if (!tienePermiso("incidencias.papelera")) {
    window.location.href = "../mis-incidencias/mis-incidencias.html";
    return;
  }

  cablearLogout();

  async function restaurar(id) {
    const ok = await confirmar({
      titulo: "Restaurar incidencia",
      mensaje: "Volverá a aparecer en el listado activo.",
      textoConfirmar: "Restaurar",
    });
    if (!ok) return;

    try {
      await apiFetch("/incidencias/" + id + "/restaurar", { method: "POST" });
      toastFlash("Incidencia restaurada", "success");
      location.reload();
    } catch (error) {
      mostrarToast("Error: " + error.message, "error");
    }
  }

  async function purgar(id) {
    const ok = await confirmar({
      titulo: "Eliminar definitivamente",
      mensaje:
        "Esta acción no se puede deshacer. Se borrarán también sus comentarios, evidencias e historial.",
      textoConfirmar: "Eliminar para siempre",
      peligro: true,
    });
    if (!ok) return;

    try {
      await apiFetch("/incidencias/" + id + "/purgar", { method: "DELETE" });
      toastFlash("Incidencia eliminada definitivamente", "success");
      location.reload();
    } catch (error) {
      mostrarToast("Error: " + error.message, "error");
    }
  }

  let paginaActual = 1;
  let porPagina = 10;

  async function cargarPapelera() {
    const params = new URLSearchParams();
    params.set("page", paginaActual);
    params.set("per_page", porPagina);

    const busqueda = document.getElementById("filtroBusqueda").value.trim();
    if (busqueda) params.set("busqueda", busqueda);

    try {
      const respuesta = await apiFetch("/incidencias/papelera?" + params.toString());
      renderizarTabla(respuesta.data);
      renderizarPaginacion({
        respuesta: respuesta,
        idContenedor: "contenedorPaginacion",
        onPageChange: (p) => {
          paginaActual = p;
          cargarPapelera();
        },
        onPerPageChange: (pp) => {
          porPagina = pp;
          paginaActual = 1;
          cargarPapelera();
        },
        perPage: porPagina,
      });
    } catch (error) {
      mostrarToast("No se pudo cargar la papelera: " + error.message, "error");
    }
  }

  // Celda con el contador para el borrado definitivo: deleted_at + días de retención, con badge según urgencia.
  function celdaContador(inc) {
    const td = document.createElement("td");
    td.dataset.label = "Se elimina en";

    const dias = inc.dias_retencion_papelera;
    const purga = new Date(inc.deleted_at).getTime() + dias * 86400000;
    const diasRestantes = Math.ceil((purga - Date.now()) / 86400000);

    const badge = document.createElement("span");
    badge.className = "badge";
    if (diasRestantes <= 0) {
      badge.classList.add("text-bg-danger");
      badge.textContent = "En la próxima limpieza";
    } else {
      const clase =
        diasRestantes <= 3
          ? "text-bg-danger"
          : diasRestantes <= 7
            ? "text-bg-warning"
            : "text-bg-secondary";
      badge.classList.add(clase);
      badge.textContent = diasRestantes === 1 ? "1 día" : diasRestantes + " días";
    }

    td.title = "Borrado automático a los " + dias + " días en la papelera";
    td.appendChild(badge);
    return td;
  }

  function renderizarTabla(incidencias) {
    const tbody = document.getElementById("tbodyPapelera");
    tbody.innerHTML = "";

    if (incidencias.length === 0) {
      tbody.innerHTML = filaVaciaHtml(
        6,
        "bi-trash3",
        "Papelera vacía",
        "No hay incidencias en la papelera.",
      );
      return;
    }

    incidencias.forEach(function (inc) {
      const tr = document.createElement("tr");

      const nombreTipo =
        inc.subtipo && inc.subtipo.tipo ? inc.subtipo.tipo.nombre_tipo_incidencia : "—";
      const nombreCiudad = inc.ciudad ? inc.ciudad.nombre_ciudad : "—";
      const fecha = new Date(inc.deleted_at).toLocaleString("es-EC");

      tr.appendChild(celdaTabla(inc.nombre_incidencia, "Título"));
      tr.appendChild(celdaTabla(nombreTipo, "Tipo", true));
      tr.appendChild(celdaTabla(nombreCiudad, "Ciudad", true));
      tr.appendChild(celdaTabla(fecha, "En papelera desde"));
      tr.appendChild(celdaContador(inc));

      const acciones = [
        {
          icon: "bi bi-arrow-counterclockwise me-2",
          label: "Restaurar",
          handler: () => restaurar(inc.id_incidencia),
        },
        {
          icon: "bi bi-trash3-fill me-2",
          label: "Eliminar para siempre",
          peligro: true,
          handler: () => purgar(inc.id_incidencia),
        },
      ];

      const tdAcc = document.createElement("td");
      tdAcc.className = "text-end";
      tdAcc.appendChild(crearMenuAcciones(acciones));
      tr.appendChild(tdAcc);

      tbody.appendChild(tr);
    });
  }

  let timerBusqueda = null;
  document.getElementById("filtroBusqueda").addEventListener("input", function () {
    clearTimeout(timerBusqueda);
    timerBusqueda = setTimeout(function () {
      paginaActual = 1;
      cargarPapelera();
    }, 400);
  });

  // Papelera en vivo: recarga ante enviar/restaurar/purgar de otro admin, sin recargar la página.
  // El canal de presencia solo autoriza a quien gestiona (routes/channels.php), permiso distinto del
  // que abre esta página, así que sin él ni se pide para no provocar un 403 y los reintentos del socket.
  const echo = tienePermiso("incidencias.gestionar") ? obtenerEcho() : null;
  if (echo) {
    echo
      .join("tablero")
      .listen(".IncidenciaEliminada", cargarPapelera)
      .listen(".IncidenciaRestaurada", cargarPapelera)
      .listen(".IncidenciaPurgada", cargarPapelera);
  }

  cargarPapelera();
});
