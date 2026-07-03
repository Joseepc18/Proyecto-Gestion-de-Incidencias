// gestion-incidencias.js — Listado, filtros, paginación y acciones.

/* global apiFetch, aplicarMenuRol, mostrarToast, toastFlash, confirmar, abrirModal, motivoConOtroHtml, cablearMotivoConOtro, leerMotivoSeleccionado, badgeEstadoHtml, badgePrioridadHtml, rutaDetalleIncidencia, renderizarPaginacion, crearMenuAcciones, filaVaciaHtml, requerirSesion, cablearLogout */

document.addEventListener("DOMContentLoaded", async function () {
  const usuarioActual = await requerirSesion();
  if (!usuarioActual) return;

  aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "");

  if (!usuarioActual.rol || usuarioActual.rol.nombre_rol !== "admin") {
    window.location.href = "../mis-incidencias/mis-incidencias.html";
    return;
  }

  cablearLogout();

  // Ver detalle lleva a la página propia de la incidencia (buena página de admin).
  function verDetalle(id) {
    window.location.href = rutaDetalleIncidencia(id, "admin");
  }

  // Motivos frecuentes para eliminar la incidencia de otro; "Otro" abre un textarea libre.
  const MOTIVOS_ELIMINACION = [
    "Reporte duplicado",
    "Información insuficiente o incorrecta",
    "Fuera de jurisdicción / no corresponde",
    "Contenido inapropiado o spam",
    "Incidencia ya resuelta por otra vía",
  ];

  // Si es de otro usuario, pide el motivo (se le notifica al dueño); si es propia, solo confirma.
  async function eliminarIncidencia(id, esAutor) {
    if (esAutor) {
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
      return;
    }

    const promesaModal = abrirModal({
      titulo: "Eliminar incidencia",
      cuerpoHtml:
        '<p class="text-secondary small">Se notificará al reportador el motivo de la eliminación.</p>' +
        motivoConOtroHtml(MOTIVOS_ELIMINACION),
      textoConfirmar: "Eliminar",
      peligro: true,
      alConfirmar: async function (form) {
        await apiFetch("/incidencias/" + id, {
          method: "DELETE",
          body: JSON.stringify({ motivo: leerMotivoSeleccionado(form) }),
        });
      },
    });

    cablearMotivoConOtro();

    const confirmado = await promesaModal;
    if (!confirmado) return;

    toastFlash("Incidencia eliminada", "success");
    location.reload();
  }

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
      mostrarToast("No se pudieron cargar los filtros: " + error.message, "error");
    }
  }

  let paginaActual = 1;
  let porPagina = 10;

  async function cargarIncidencias() {
    const params = new URLSearchParams();
    params.set("page", paginaActual);
    params.set("per_page", porPagina);

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
      renderizarPaginacion({
        respuesta: respuesta,
        idContenedor: "contenedorPaginacion",
        onPageChange: (p) => {
          paginaActual = p;
          cargarIncidencias();
        },
        onPerPageChange: (pp) => {
          porPagina = pp;
          paginaActual = 1;
          cargarIncidencias();
        },
        perPage: porPagina,
      });
    } catch (error) {
      mostrarToast("No se pudieron cargar las incidencias: " + error.message, "error");
    }
  }

  // Construye una celda con texto escapado para evitar XSS en innerHTML.
  // label alimenta el data-label que se muestra como encabezado en la vista de tarjeta (movil).
  function td(texto, label) {
    const celda = document.createElement("td");
    if (label) celda.dataset.label = label;
    celda.textContent = texto;
    return celda;
  }

  function renderizarTabla(incidencias) {
    const tbody = document.getElementById("tbodyIncidencias");
    tbody.innerHTML = "";

    if (incidencias.length === 0) {
      tbody.innerHTML = filaVaciaHtml(
        7,
        "bi-clipboard-x",
        "Sin incidencias",
        "No hay incidencias que coincidan con los filtros.",
      );
      return;
    }

    const esAdmin = usuarioActual.rol && usuarioActual.rol.nombre_rol === "admin";

    incidencias.forEach(function (inc) {
      const tr = document.createElement("tr");
      tr.dataset.id = inc.id_incidencia;

      const nombreTipo =
        inc.subtipo && inc.subtipo.tipo ? inc.subtipo.tipo.nombre_tipo_incidencia : "—";
      const nombreCiudad = inc.ciudad ? inc.ciudad.nombre_ciudad : "—";
      const fecha = new Date(inc.created_at).toLocaleDateString("es-EC");
      const esAutor = inc.id_usuario === usuarioActual.id;

      tr.appendChild(td(inc.nombre_incidencia, "Título"));

      const tdEstado = document.createElement("td");
      tdEstado.dataset.label = "Estado";
      tdEstado.innerHTML = badgeEstadoHtml(inc.estado_incidencia);
      tr.appendChild(tdEstado);

      const tdPri = document.createElement("td");
      tdPri.dataset.label = "Prioridad";
      tdPri.innerHTML = badgePrioridadHtml(inc.prioridad_incidencia);
      tr.appendChild(tdPri);

      tr.appendChild(td(nombreTipo, "Tipo"));
      tr.appendChild(td(nombreCiudad, "Ciudad"));
      tr.appendChild(td(fecha, "Fecha"));

      const acciones = [
        {
          icon: "bi bi-eye me-2",
          label: "Ver detalle",
          handler: () => verDetalle(inc.id_incidencia),
        },
      ];
      if (esAdmin || esAutor) {
        acciones.push({
          icon: "bi bi-trash me-2",
          label: "Eliminar",
          peligro: true,
          handler: () => eliminarIncidencia(inc.id_incidencia, esAutor),
        });
      }

      const tdAcc = document.createElement("td");
      tdAcc.className = "text-end";
      tdAcc.appendChild(crearMenuAcciones(acciones));
      tr.appendChild(tdAcc);

      tbody.appendChild(tr);
    });
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

  let timerBusqueda = null;
  document.getElementById("filtroBusqueda").addEventListener("input", function () {
    clearTimeout(timerBusqueda);
    timerBusqueda = setTimeout(function () {
      paginaActual = 1;
      cargarIncidencias();
    }, 400);
  });

  cargarCatalogos();
  cargarIncidencias();
});
