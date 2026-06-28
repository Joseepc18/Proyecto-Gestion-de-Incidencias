// usuarios.js — Gestión de usuarios (solo admin): listar, crear, editar y eliminar.

/* global apiFetch, obtenerToken, eliminarToken, aplicarMenuRol, mostrarToast, confirmar, escaparHtml, renderizarPaginacion */

let usuarioActualId = null;
// Si es null estamos creando; si tiene un id estamos editando ese usuario.
let usuarioEditandoId = null;

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
    usuarioActualId = usuarioActual.id;
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

  document.getElementById("formUsuario").addEventListener("submit", guardarUsuario);
  document.getElementById("btnCancelarEdicion").addEventListener("click", salirModoEdicion);

  cargarRoles();
  cargarUsuarios();
});

// Llena el desplegable de roles del formulario.
async function cargarRoles() {
  const select = document.getElementById("usuarioRol");
  try {
    const roles = await apiFetch("/roles");
    roles.forEach(function (rol) {
      const opcion = document.createElement("option");
      opcion.value = rol.id_rol;
      opcion.textContent = rol.nombre_rol;
      select.appendChild(opcion);
    });
  } catch {
    mostrarToast("No se pudieron cargar los roles", "error");
  }
}

let paginaActual = 1;
let porPagina = 10;

// Trae y pinta la tabla de usuarios.
async function cargarUsuarios() {
  const tbody = document.getElementById("tbodyUsuarios");
  try {
    const params = new URLSearchParams();
    params.set("page", paginaActual);
    params.set("per_page", porPagina);

    const respuesta = await apiFetch("/usuarios?" + params.toString());
    const usuarios = respuesta.data;

    if (usuarios.length === 0) {
      tbody.innerHTML =
        '<tr><td colspan="4" class="text-center text-muted py-4">Sin usuarios.</td></tr>';
      document.getElementById("contenedorPaginacion").innerHTML = "";
      return;
    }

    tbody.innerHTML = "";
    usuarios.forEach(function (u) {
      const tr = document.createElement("tr");

      const tdNombre = document.createElement("td");
      tdNombre.textContent = u.name;
      tr.appendChild(tdNombre);

      const tdEmail = document.createElement("td");
      tdEmail.textContent = u.email;
      tr.appendChild(tdEmail);

      const tdRol = document.createElement("td");
      const badge = document.createElement("span");
      badge.className = "badge text-bg-secondary";
      badge.textContent = u.rol ? u.rol.nombre_rol : "—";
      tdRol.appendChild(badge);
      tr.appendChild(tdRol);

      const tdAcciones = document.createElement("td");
      tdAcciones.className = "text-end";
      if (u.id !== usuarioActualId) {
        tdAcciones.appendChild(crearMenuAcciones(u));
      }
      tr.appendChild(tdAcciones);

      tbody.appendChild(tr);
    });

    renderizarPaginacion({
      respuesta: respuesta,
      idContenedor: "contenedorPaginacion",
      onPageChange: (p) => {
        paginaActual = p;
        cargarUsuarios();
      },
      onPerPageChange: (pp) => {
        porPagina = pp;
        paginaActual = 1;
        cargarUsuarios();
      },
      perPage: porPagina,
    });
  } catch (error) {
    tbody.innerHTML =
      '<tr><td colspan="4" class="text-center text-danger py-4">' +
      escaparHtml(error.message) +
      "</td></tr>";
  }
}

// Construye el menú de 3 puntos (Editar / Eliminar) de una fila.
function crearMenuAcciones(u) {
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
    editarUsuario(u);
  });
  dropdown.querySelector('[data-accion="eliminar"]').addEventListener("click", function (e) {
    e.preventDefault();
    eliminarUsuario(u.id, u.name);
  });

  return dropdown;
}

// Pasa el formulario a modo edición y lo rellena con los datos del usuario.
function editarUsuario(u) {
  usuarioEditandoId = u.id;
  document.getElementById("usuarioNombre").value = u.name;
  document.getElementById("usuarioEmail").value = u.email;
  document.getElementById("usuarioRol").value = u.id_rol;

  const pass = document.getElementById("usuarioPassword");
  pass.value = "";
  pass.required = false;
  pass.placeholder = "Dejar vacío para no cambiar";

  document.getElementById("tituloFormUsuario").textContent = "Editar usuario";
  document.getElementById("btnUsuarioTexto").innerHTML =
    '<i class="bi bi-check-lg" aria-hidden="true"></i> Guardar';
  document.getElementById("btnCancelarEdicion").classList.remove("d-none");

  document.getElementById("formUsuario").scrollIntoView({ behavior: "smooth", block: "center" });
}

// Vuelve el formulario a modo "crear".
function salirModoEdicion() {
  usuarioEditandoId = null;
  document.getElementById("formUsuario").reset();

  const pass = document.getElementById("usuarioPassword");
  pass.required = true;
  pass.placeholder = "";

  document.getElementById("tituloFormUsuario").textContent = "Nuevo usuario";
  document.getElementById("btnUsuarioTexto").innerHTML =
    '<i class="bi bi-plus-lg" aria-hidden="true"></i> Crear';
  document.getElementById("btnCancelarEdicion").classList.add("d-none");
}

// Envía el formulario: crea (POST) o actualiza (PUT) según el modo.
async function guardarUsuario(e) {
  e.preventDefault();
  const btn = document.getElementById("btnCrearUsuario");
  const spinner = document.getElementById("usuarioSpinner");

  const datos = {
    name: document.getElementById("usuarioNombre").value.trim(),
    email: document.getElementById("usuarioEmail").value.trim(),
    id_rol: document.getElementById("usuarioRol").value,
  };
  const password = document.getElementById("usuarioPassword").value;
  if (password) {
    datos.password = password;
  }

  const editando = usuarioEditandoId !== null;
  const endpoint = editando ? "/usuarios/" + usuarioEditandoId : "/usuarios";
  const metodo = editando ? "PUT" : "POST";

  btn.disabled = true;
  spinner.classList.remove("d-none");

  try {
    await apiFetch(endpoint, { method: metodo, body: JSON.stringify(datos) });
    salirModoEdicion();
    await cargarUsuarios();
    mostrarToast(editando ? "Usuario actualizado" : "Usuario creado", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  } finally {
    btn.disabled = false;
    spinner.classList.add("d-none");
  }
}

// Elimina (borrado lógico) un usuario.
async function eliminarUsuario(id, nombre) {
  const ok = await confirmar({
    titulo: "¿Eliminar usuario?",
    mensaje: 'Se eliminará la cuenta de "' + nombre + '".',
    textoConfirmar: "Eliminar",
    peligro: true,
  });
  if (!ok) return;

  try {
    await apiFetch("/usuarios/" + id, { method: "DELETE" });
    await cargarUsuarios();
    mostrarToast("Usuario eliminado", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  }
}
