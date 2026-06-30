// catalogos.js — Tipos y subtipos de incidencia (solo admin): tabla única con toggle, crear/editar en modal.

/* global apiFetch, aplicarMenuRol, mostrarToast, confirmar, escaparHtml, abrirModal, renderizarPaginacion, crearMenuAcciones, requerirSesion, cablearLogout */

// Catálogo completo (tipos con sus subtipos anidados) cacheado para paginar en cliente.
let tipos = [];
let vista = "tipos";
let filtroTipo = "";
let paginaActual = 1;
let porPagina = 10;

document.addEventListener("DOMContentLoaded", async function () {
  const usuarioActual = await requerirSesion();
  if (!usuarioActual) return;

  aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "");

  if (!usuarioActual.rol || usuarioActual.rol.nombre_rol !== "admin") {
    window.location.href = "../inicio/inicio.html";
    return;
  }

  cablearLogout();

  document.querySelectorAll("[data-vista]").forEach(function (btn) {
    btn.addEventListener("click", () => cambiarVista(btn.dataset.vista));
  });
  document.getElementById("btnNuevoCatalogo").addEventListener("click", abrirModalNuevo);
  document.getElementById("filtroSubtipoTipo").addEventListener("change", function () {
    filtroTipo = this.value;
    paginaActual = 1;
    pintar();
  });

  cargarCatalogos();
});

// Trae el catálogo y repinta la vista activa + el select de filtro.
async function cargarCatalogos() {
  try {
    tipos = await apiFetch("/catalogos/tipos-incidencia");
    llenarFiltroTipos();
    pintar();
  } catch (error) {
    document.getElementById("tbodyCatalogo").innerHTML =
      '<tr><td colspan="4" class="text-center text-danger py-4">' +
      escaparHtml(error.message) +
      "</td></tr>";
  }
}

// Cambia entre la vista de tipos y la de subtipos.
function cambiarVista(v) {
  if (v === vista) return;
  vista = v;
  paginaActual = 1;

  document.querySelectorAll("[data-vista]").forEach(function (btn) {
    btn.classList.toggle("active", btn.dataset.vista === v);
  });
  document.getElementById("filtroSubtipoWrap").classList.toggle("d-none", v !== "subtipos");

  pintar();
}

// Llena el desplegable que filtra los subtipos por tipo (conserva la selección si sigue existiendo).
function llenarFiltroTipos() {
  const select = document.getElementById("filtroSubtipoTipo");
  const seleccionado = select.value;

  select.innerHTML = '<option value="">Todos los tipos</option>';
  tipos.forEach(function (t) {
    const opcion = document.createElement("option");
    opcion.value = t.id_tipo_incidencia;
    opcion.textContent = t.nombre_tipo_incidencia;
    select.appendChild(opcion);
  });

  select.value = seleccionado;
}

// Devuelve la lista de la vista activa: tipos, o subtipos aplanados (con su tipo padre) y filtrados.
function listaActiva() {
  if (vista === "tipos") return tipos;

  const filas = [];
  tipos.forEach(function (t) {
    if (filtroTipo && String(t.id_tipo_incidencia) !== filtroTipo) return;
    (t.subtipos || []).forEach(function (s) {
      filas.push({ subtipo: s, nombreTipo: t.nombre_tipo_incidencia });
    });
  });
  return filas;
}

// Corta un array en la página actual y arma un objeto al estilo de la paginación de Laravel.
function paginarCliente(items) {
  const total = items.length;
  const last = Math.max(1, Math.ceil(total / porPagina));
  const current = Math.min(paginaActual, last);
  const desde = (current - 1) * porPagina;
  const data = items.slice(desde, desde + porPagina);

  return {
    data,
    current_page: current,
    last_page: last,
    total,
    from: total ? desde + 1 : 0,
    to: desde + data.length,
  };
}

// Pinta la cabecera + filas de la vista activa y los controles de paginación.
function pintar() {
  const thead = document.getElementById("theadCatalogo");
  const tbody = document.getElementById("tbodyCatalogo");

  thead.innerHTML =
    vista === "tipos"
      ? "<tr><th>Nombre</th><th>Descripción</th><th class='text-center'>Subtipos</th><th class='text-end'>Acciones</th></tr>"
      : "<tr><th>Subtipo</th><th>Tipo padre</th><th>Descripción</th><th class='text-end'>Acciones</th></tr>";

  const pagina = paginarCliente(listaActiva());

  if (pagina.total === 0) {
    const texto =
      vista === "tipos"
        ? "Sin tipos."
        : filtroTipo
          ? "Sin subtipos para este tipo."
          : "Sin subtipos.";
    tbody.innerHTML =
      '<tr><td colspan="4" class="text-center text-muted py-4">' + texto + "</td></tr>";
    document.getElementById("contenedorPaginacion").innerHTML = "";
    return;
  }

  tbody.innerHTML = "";
  pagina.data.forEach((item) =>
    tbody.appendChild(vista === "tipos" ? filaTipo(item) : filaSubtipo(item)),
  );

  renderizarPaginacion({
    respuesta: pagina,
    idContenedor: "contenedorPaginacion",
    onPageChange: (p) => {
      paginaActual = p;
      pintar();
    },
    onPerPageChange: (pp) => {
      porPagina = pp;
      paginaActual = 1;
      pintar();
    },
    perPage: porPagina,
  });
}

// Construye una fila de la tabla de tipos.
function filaTipo(t) {
  const tr = document.createElement("tr");

  const tdNombre = document.createElement("td");
  tdNombre.textContent = t.nombre_tipo_incidencia;
  tr.appendChild(tdNombre);

  const tdDesc = document.createElement("td");
  tdDesc.className = "text-muted";
  tdDesc.textContent = t.descripcion_tipo_incidencia || "—";
  tr.appendChild(tdDesc);

  const tdConteo = document.createElement("td");
  tdConteo.className = "text-center";
  const badge = document.createElement("span");
  badge.className = "badge text-bg-secondary";
  badge.textContent = t.subtipos ? t.subtipos.length : 0;
  tdConteo.appendChild(badge);
  tr.appendChild(tdConteo);

  const tdAcciones = document.createElement("td");
  tdAcciones.className = "text-end";
  tdAcciones.appendChild(
    menuAccionesCatalogo(
      () => abrirModalTipo(t),
      () => eliminarTipo(t),
    ),
  );
  tr.appendChild(tdAcciones);

  return tr;
}

// Construye una fila de la tabla de subtipos.
function filaSubtipo(fila) {
  const s = fila.subtipo;
  const tr = document.createElement("tr");

  const tdNombre = document.createElement("td");
  tdNombre.textContent = s.nombre_subtipo_incidencia;
  tr.appendChild(tdNombre);

  const tdTipo = document.createElement("td");
  const badge = document.createElement("span");
  badge.className = "badge text-bg-light";
  badge.textContent = fila.nombreTipo;
  tdTipo.appendChild(badge);
  tr.appendChild(tdTipo);

  const tdDesc = document.createElement("td");
  tdDesc.className = "text-muted";
  tdDesc.textContent = s.descripcion_subtipo_incidencia || "—";
  tr.appendChild(tdDesc);

  const tdAcciones = document.createElement("td");
  tdAcciones.className = "text-end";
  tdAcciones.appendChild(
    menuAccionesCatalogo(
      () => abrirModalSubtipo(s),
      () => eliminarSubtipo(s),
    ),
  );
  tr.appendChild(tdAcciones);

  return tr;
}

// El botón "Nuevo" abre el modal de la vista activa.
function abrirModalNuevo() {
  if (vista === "tipos") abrirModalTipo(null);
  else abrirModalSubtipo(null);
}

// Modal de crear/editar un tipo.
function abrirModalTipo(t) {
  const editando = t !== null;
  const cuerpoHtml =
    '<div class="mb-3">' +
    '<label class="form-label" for="mTipoNombre">Nombre</label>' +
    '<input class="form-control form-control-sm" id="mTipoNombre" type="text" maxlength="255" required value="' +
    (editando ? escaparHtml(t.nombre_tipo_incidencia) : "") +
    '" /></div>' +
    '<div class="mb-1">' +
    '<label class="form-label" for="mTipoDesc">Descripción (opcional)</label>' +
    '<textarea class="form-control form-control-sm" id="mTipoDesc" maxlength="500" rows="2">' +
    (editando ? escaparHtml(t.descripcion_tipo_incidencia || "") : "") +
    "</textarea></div>";

  return abrirModal({
    titulo: editando ? "Editar tipo" : "Nuevo tipo",
    cuerpoHtml,
    textoConfirmar: editando ? "Guardar" : "Crear",
    alConfirmar: async function (form) {
      const datos = {
        nombre_tipo_incidencia: form.querySelector("#mTipoNombre").value.trim(),
        descripcion_tipo_incidencia: form.querySelector("#mTipoDesc").value.trim() || null,
      };
      const endpoint = editando ? "/tipos-incidencia/" + t.id_tipo_incidencia : "/tipos-incidencia";
      await apiFetch(endpoint, { method: editando ? "PUT" : "POST", body: JSON.stringify(datos) });
      await cargarCatalogos();
      mostrarToast(editando ? "Tipo actualizado" : "Tipo creado", "success");
    },
  });
}

// Modal de crear/editar un subtipo.
function abrirModalSubtipo(s) {
  const editando = s !== null;
  const opciones = tipos
    .map(
      (t) =>
        '<option value="' +
        t.id_tipo_incidencia +
        '"' +
        (editando && s.id_tipo_incidencia === t.id_tipo_incidencia ? " selected" : "") +
        ">" +
        escaparHtml(t.nombre_tipo_incidencia) +
        "</option>",
    )
    .join("");

  const cuerpoHtml =
    '<div class="mb-3">' +
    '<label class="form-label" for="mSubTipo">Tipo</label>' +
    '<select class="form-select form-select-sm" id="mSubTipo" required>' +
    '<option value="">Seleccionar...</option>' +
    opciones +
    "</select></div>" +
    '<div class="mb-3">' +
    '<label class="form-label" for="mSubNombre">Nombre</label>' +
    '<input class="form-control form-control-sm" id="mSubNombre" type="text" maxlength="255" required value="' +
    (editando ? escaparHtml(s.nombre_subtipo_incidencia) : "") +
    '" /></div>' +
    '<div class="mb-1">' +
    '<label class="form-label" for="mSubDesc">Descripción (opcional)</label>' +
    '<textarea class="form-control form-control-sm" id="mSubDesc" maxlength="500" rows="2">' +
    (editando ? escaparHtml(s.descripcion_subtipo_incidencia || "") : "") +
    "</textarea></div>";

  return abrirModal({
    titulo: editando ? "Editar subtipo" : "Nuevo subtipo",
    cuerpoHtml,
    textoConfirmar: editando ? "Guardar" : "Crear",
    alConfirmar: async function (form) {
      const datos = {
        id_tipo_incidencia: form.querySelector("#mSubTipo").value,
        nombre_subtipo_incidencia: form.querySelector("#mSubNombre").value.trim(),
        descripcion_subtipo_incidencia: form.querySelector("#mSubDesc").value.trim() || null,
      };
      const endpoint = editando
        ? "/subtipos-incidencia/" + s.id_subtipo_incidencia
        : "/subtipos-incidencia";
      await apiFetch(endpoint, { method: editando ? "PUT" : "POST", body: JSON.stringify(datos) });
      await cargarCatalogos();
      mostrarToast(editando ? "Subtipo actualizado" : "Subtipo creado", "success");
    },
  });
}

// Elimina un tipo (el backend rechaza con 422 si todavía tiene subtipos).
async function eliminarTipo(t) {
  const ok = await confirmar({
    titulo: "¿Eliminar tipo?",
    mensaje: 'Se eliminará el tipo "' + t.nombre_tipo_incidencia + '".',
    textoConfirmar: "Eliminar",
    peligro: true,
  });
  if (!ok) return;

  try {
    await apiFetch("/tipos-incidencia/" + t.id_tipo_incidencia, { method: "DELETE" });
    await cargarCatalogos();
    mostrarToast("Tipo eliminado", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  }
}

// Elimina un subtipo (el backend rechaza con 422 si tiene incidencias registradas).
async function eliminarSubtipo(s) {
  const ok = await confirmar({
    titulo: "¿Eliminar subtipo?",
    mensaje: 'Se eliminará el subtipo "' + s.nombre_subtipo_incidencia + '".',
    textoConfirmar: "Eliminar",
    peligro: true,
  });
  if (!ok) return;

  try {
    await apiFetch("/subtipos-incidencia/" + s.id_subtipo_incidencia, { method: "DELETE" });
    await cargarCatalogos();
    mostrarToast("Subtipo eliminado", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  }
}

// Construye el menú de 3 puntos (Editar / Eliminar) de una fila vía helper compartido.
function menuAccionesCatalogo(alEditar, alEliminar) {
  return crearMenuAcciones([
    { icon: "bi bi-pencil me-2", label: "Editar", handler: alEditar },
    { icon: "bi bi-trash me-2", label: "Eliminar", peligro: true, handler: alEliminar },
  ]);
}
