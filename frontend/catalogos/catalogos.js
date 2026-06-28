// catalogos.js — Gestión de tipos y subtipos de incidencia (solo admin): listar, crear, editar y eliminar.

// Cache del último listado (tipos con sus subtipos anidados) para no pedirlo de más.
let tipos = [];
// Si es null estamos creando; si tiene un id estamos editando ese registro.
let tipoEditandoId = null;
let subtipoEditandoId = null;

document.addEventListener("DOMContentLoaded", async function () {
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

  try {
    const usuarioActual = await apiFetch("/user");
    document.getElementById("nombreUsuario").textContent = usuarioActual.name;
    aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "");

    if (!usuarioActual.rol || usuarioActual.rol.nombre_rol !== "admin") {
      window.location.href = "../inicio/inicio.html";
      return;
    }
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

  document.getElementById("formTipo").addEventListener("submit", guardarTipo);
  document.getElementById("btnCancelarTipo").addEventListener("click", salirModoEdicionTipo);
  document.getElementById("formSubtipo").addEventListener("submit", guardarSubtipo);
  document.getElementById("btnCancelarSubtipo").addEventListener("click", salirModoEdicionSubtipo);

  document.getElementById("filtroSubtipoTipo").addEventListener("change", pintarSubtipos);

  cargarCatalogos();
});

// Trae el catálogo (tipos con subtipos) y repinta ambas tablas + el select de tipos.
async function cargarCatalogos() {
  try {
    tipos = await apiFetch("/catalogos/tipos-incidencia");
    pintarTipos();
    llenarSelectTipos();
    llenarFiltroTipos();
    pintarSubtipos();
  } catch (error) {
    const msg =
      '<tr><td colspan="4" class="text-center text-danger py-4">' +
      escaparHtml(error.message) +
      "</td></tr>";
    document.getElementById("tbodyTipos").innerHTML = msg;
    document.getElementById("tbodySubtipos").innerHTML = msg;
  }
}

// Pinta la tabla de tipos.
function pintarTipos() {
  const tbody = document.getElementById("tbodyTipos");

  if (tipos.length === 0) {
    tbody.innerHTML =
      '<tr><td colspan="4" class="text-center text-muted py-4">Sin tipos.</td></tr>';
    return;
  }

  tbody.innerHTML = "";
  tipos.forEach(function (t) {
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
      crearMenuAcciones(
        function () {
          editarTipo(t);
        },
        function () {
          eliminarTipo(t);
        },
      ),
    );
    tr.appendChild(tdAcciones);

    tbody.appendChild(tr);
  });
}

// Pasa el formulario de tipo a modo edición y lo rellena.
function editarTipo(t) {
  tipoEditandoId = t.id_tipo_incidencia;
  document.getElementById("tipoNombre").value = t.nombre_tipo_incidencia;
  document.getElementById("tipoDescripcion").value = t.descripcion_tipo_incidencia || "";

  document.getElementById("tituloFormTipo").textContent = "Editar tipo";
  document.getElementById("btnTipoTexto").innerHTML =
    '<i class="bi bi-check-lg" aria-hidden="true"></i> Guardar';
  document.getElementById("btnCancelarTipo").classList.remove("d-none");

  document.getElementById("formTipo").scrollIntoView({ behavior: "smooth", block: "center" });
}

// Vuelve el formulario de tipo a modo "crear".
function salirModoEdicionTipo() {
  tipoEditandoId = null;
  document.getElementById("formTipo").reset();

  document.getElementById("tituloFormTipo").textContent = "Nuevo tipo";
  document.getElementById("btnTipoTexto").innerHTML =
    '<i class="bi bi-plus-lg" aria-hidden="true"></i> Crear';
  document.getElementById("btnCancelarTipo").classList.add("d-none");
}

// Envía el formulario de tipo: crea (POST) o actualiza (PUT) según el modo.
async function guardarTipo(e) {
  e.preventDefault();
  const btn = document.getElementById("btnGuardarTipo");
  const spinner = document.getElementById("tipoSpinner");

  const datos = {
    nombre_tipo_incidencia: document.getElementById("tipoNombre").value.trim(),
    descripcion_tipo_incidencia: document.getElementById("tipoDescripcion").value.trim() || null,
  };

  const editando = tipoEditandoId !== null;
  const endpoint = editando ? "/tipos-incidencia/" + tipoEditandoId : "/tipos-incidencia";
  const metodo = editando ? "PUT" : "POST";

  btn.disabled = true;
  spinner.classList.remove("d-none");

  try {
    await apiFetch(endpoint, { method: metodo, body: JSON.stringify(datos) });
    salirModoEdicionTipo();
    await cargarCatalogos();
    mostrarToast(editando ? "Tipo actualizado" : "Tipo creado", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  } finally {
    btn.disabled = false;
    spinner.classList.add("d-none");
  }
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

// Llena el desplegable de tipos del formulario de subtipo (conserva la selección si sigue existiendo).
function llenarSelectTipos() {
  const select = document.getElementById("subtipoTipo");
  const seleccionado = select.value;

  select.innerHTML = '<option value="">Seleccionar...</option>';
  tipos.forEach(function (t) {
    const opcion = document.createElement("option");
    opcion.value = t.id_tipo_incidencia;
    opcion.textContent = t.nombre_tipo_incidencia;
    select.appendChild(opcion);
  });

  select.value = seleccionado;
}

// Llena el desplegable que filtra la tabla de subtipos por tipo (conserva la selección).
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

// Pinta la tabla de subtipos (aplanando los subtipos de todos los tipos, aplicando el filtro por tipo).
function pintarSubtipos() {
  const tbody = document.getElementById("tbodySubtipos");
  const filtro = document.getElementById("filtroSubtipoTipo").value;

  const filas = [];
  tipos.forEach(function (t) {
    if (filtro && String(t.id_tipo_incidencia) !== filtro) return;
    (t.subtipos || []).forEach(function (s) {
      filas.push({ subtipo: s, nombreTipo: t.nombre_tipo_incidencia });
    });
  });

  if (filas.length === 0) {
    const texto = filtro ? "Sin subtipos para este tipo." : "Sin subtipos.";
    tbody.innerHTML =
      '<tr><td colspan="4" class="text-center text-muted py-4">' + texto + "</td></tr>";
    return;
  }

  tbody.innerHTML = "";
  filas.forEach(function (fila) {
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
      crearMenuAcciones(
        function () {
          editarSubtipo(s);
        },
        function () {
          eliminarSubtipo(s);
        },
      ),
    );
    tr.appendChild(tdAcciones);

    tbody.appendChild(tr);
  });
}

// Pasa el formulario de subtipo a modo edición y lo rellena.
function editarSubtipo(s) {
  subtipoEditandoId = s.id_subtipo_incidencia;
  document.getElementById("subtipoTipo").value = s.id_tipo_incidencia;
  document.getElementById("subtipoNombre").value = s.nombre_subtipo_incidencia;
  document.getElementById("subtipoDescripcion").value = s.descripcion_subtipo_incidencia || "";

  document.getElementById("tituloFormSubtipo").textContent = "Editar subtipo";
  document.getElementById("btnSubtipoTexto").innerHTML =
    '<i class="bi bi-check-lg" aria-hidden="true"></i> Guardar';
  document.getElementById("btnCancelarSubtipo").classList.remove("d-none");

  document.getElementById("formSubtipo").scrollIntoView({ behavior: "smooth", block: "center" });
}

// Vuelve el formulario de subtipo a modo "crear".
function salirModoEdicionSubtipo() {
  subtipoEditandoId = null;
  document.getElementById("formSubtipo").reset();

  document.getElementById("tituloFormSubtipo").textContent = "Nuevo subtipo";
  document.getElementById("btnSubtipoTexto").innerHTML =
    '<i class="bi bi-plus-lg" aria-hidden="true"></i> Crear';
  document.getElementById("btnCancelarSubtipo").classList.add("d-none");
}

// Envía el formulario de subtipo: crea (POST) o actualiza (PUT) según el modo.
async function guardarSubtipo(e) {
  e.preventDefault();
  const btn = document.getElementById("btnGuardarSubtipo");
  const spinner = document.getElementById("subtipoSpinner");

  const datos = {
    id_tipo_incidencia: document.getElementById("subtipoTipo").value,
    nombre_subtipo_incidencia: document.getElementById("subtipoNombre").value.trim(),
    descripcion_subtipo_incidencia:
      document.getElementById("subtipoDescripcion").value.trim() || null,
  };

  const editando = subtipoEditandoId !== null;
  const endpoint = editando ? "/subtipos-incidencia/" + subtipoEditandoId : "/subtipos-incidencia";
  const metodo = editando ? "PUT" : "POST";

  btn.disabled = true;
  spinner.classList.remove("d-none");

  try {
    await apiFetch(endpoint, { method: metodo, body: JSON.stringify(datos) });
    salirModoEdicionSubtipo();
    await cargarCatalogos();
    mostrarToast(editando ? "Subtipo actualizado" : "Subtipo creado", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  } finally {
    btn.disabled = false;
    spinner.classList.add("d-none");
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

// Construye el menú de 3 puntos (Editar / Eliminar) de una fila.
function crearMenuAcciones(alEditar, alEliminar) {
  const dropdown = document.createElement("div");
  dropdown.className = "dropdown";
  dropdown.innerHTML =
    '<button class="btn btn-light btn-sm" data-bs-toggle="dropdown" aria-expanded="false">' +
    '<i class="bi bi-three-dots-vertical"></i></button>' +
    '<ul class="dropdown-menu dropdown-menu-end">' +
    '<li><a class="dropdown-item" href="#" data-accion="editar">' +
    '<i class="bi bi-pencil me-2"></i>Editar</a></li>' +
    '<li><a class="dropdown-item text-danger" href="#" data-accion="eliminar">' +
    '<i class="bi bi-trash me-2"></i>Eliminar</a></li>' +
    "</ul>";

  dropdown.querySelector('[data-accion="editar"]').addEventListener("click", function (e) {
    e.preventDefault();
    alEditar();
  });
  dropdown.querySelector('[data-accion="eliminar"]').addEventListener("click", function (e) {
    e.preventDefault();
    alEliminar();
  });

  return dropdown;
}
