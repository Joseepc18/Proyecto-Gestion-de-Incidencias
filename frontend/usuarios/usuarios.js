// usuarios.js — Gestión de usuarios (solo admin): listar, crear, editar y suspender en modal.

/* global apiFetch, aplicarMenuRol, mostrarToast, confirmar, escaparHtml, renderizarPaginacion, abrirModal, crearMenuAcciones, iniciales, filaVaciaHtml, requerirSesion, cablearLogout */

let usuarioActualId = null;
// Roles que el admin puede asignar (los normales nacen por auto-registro, no se crean aquí).
let rolesAsignables = [];

document.addEventListener("DOMContentLoaded", async function () {
  const usuarioActual = await requerirSesion();
  if (!usuarioActual) return;

  aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "");

  if (!usuarioActual.rol || usuarioActual.rol.nombre_rol !== "admin") {
    window.location.href = "../inicio/inicio.html";
    return;
  }
  usuarioActualId = usuarioActual.id;

  cablearLogout();

  document
    .getElementById("btnNuevoUsuario")
    .addEventListener("click", () => abrirModalUsuario(null));

  await cargarRoles();
  inicializarFiltroRol();
  cargarUsuarios();
});

// Trae los roles y se queda solo con los asignables por el admin (técnico y admin).
async function cargarRoles() {
  try {
    const roles = await apiFetch("/roles");
    rolesAsignables = roles.filter((r) => r.nombre_rol === "tecnico" || r.nombre_rol === "admin");
  } catch {
    mostrarToast("No se pudieron cargar los roles", "error");
  }
}

let paginaActual = 1;
let porPagina = 10;
// Filtro de rol activo: '' (todos), 'admin', 'tecnico', 'normal' o 'suspendido'.
let filtroRol = "";

// Trae y pinta la tabla de usuarios.
async function cargarUsuarios() {
  const tbody = document.getElementById("tbodyUsuarios");
  try {
    const params = new URLSearchParams();
    params.set("page", paginaActual);
    params.set("per_page", porPagina);
    if (filtroRol) params.set("rol", filtroRol);

    const respuesta = await apiFetch("/usuarios?" + params.toString());
    const usuarios = respuesta.data;
    const suspendidos = filtroRol === "suspendido";

    if (usuarios.length === 0) {
      tbody.innerHTML = filaVaciaHtml(
        5,
        "bi-people",
        "Sin usuarios",
        "No hay usuarios que coincidan con el filtro.",
      );
      document.getElementById("contenedorPaginacion").innerHTML = "";
      return;
    }

    tbody.innerHTML = "";
    usuarios.forEach(function (u) {
      const tr = document.createElement("tr");
      if (suspendidos) tr.classList.add("table-secondary");

      const tdAvatar = document.createElement("td");
      tdAvatar.style.width = "48px";
      tdAvatar.appendChild(crearAvatar(u));
      tr.appendChild(tdAvatar);

      const tdNombre = document.createElement("td");
      tdNombre.textContent = u.name;
      if (suspendidos) tdNombre.classList.add("text-decoration-line-through");
      tr.appendChild(tdNombre);

      const tdEmail = document.createElement("td");
      tdEmail.textContent = u.email;
      tr.appendChild(tdEmail);

      const tdRol = document.createElement("td");
      const nombreRol = u.rol ? u.rol.nombre_rol : "";
      const badge = document.createElement("span");
      badge.className = "badge-rol badge-rol-" + (nombreRol || "normal");
      badge.textContent = nombreRol || "—";
      tdRol.appendChild(badge);
      tr.appendChild(tdRol);

      const tdAcciones = document.createElement("td");
      tdAcciones.className = "text-end";
      if (u.id !== usuarioActualId) {
        tdAcciones.appendChild(menuAccionesUsuario(u, suspendidos));
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
      '<tr><td colspan="5" class="text-center text-danger py-4">' +
      escaparHtml(error.message) +
      "</td></tr>";
  }
}

// Etiquetas que muestra el botón según el filtro elegido.
const etiquetasFiltro = {
  "": "Todos",
  admin: "Administradores",
  tecnico: "Técnicos",
  normal: "Normales",
  suspendido: "Suspendidos",
};

function inicializarFiltroRol() {
  document
    .querySelectorAll("#btnFiltroRol + .dropdown-menu .dropdown-item")
    .forEach(function (item) {
      item.addEventListener("click", function (e) {
        e.preventDefault();
        filtroRol = this.dataset.rol || "";
        document.getElementById("filtroRolTexto").textContent =
          etiquetasFiltro[filtroRol] || "Todos";
        paginaActual = 1;
        cargarUsuarios();
      });
    });
}

// Iniciales vienen de util.js (helper compartido).

// Avatar de la fila: foto de perfil o iniciales sobre el color primario.
function crearAvatar(u) {
  const avatar = document.createElement("span");
  avatar.className = "tabla-avatar";
  if (u.foto_perfil) {
    const img = document.createElement("img");
    img.src = "/storage/" + u.foto_perfil;
    img.alt = "";
    avatar.appendChild(img);
  } else {
    avatar.textContent = iniciales(u.name);
  }
  return avatar;
}

// Menú de acciones de la fila: suspendidos se restauran; los normales solo se suspenden;
// técnicos/admins además se editan (helper compartido de menuAcciones.js).
function menuAccionesUsuario(u, suspendido) {
  const acciones = [];
  if (!suspendido && u.rol && u.rol.nombre_rol !== "normal") {
    acciones.push({
      icon: "bi bi-pencil me-2",
      label: "Editar",
      handler: () => abrirModalUsuario(u),
    });
  }
  if (suspendido) {
    acciones.push({
      icon: "bi bi-arrow-counterclockwise me-2",
      label: "Restaurar",
      handler: () => restaurarUsuario(u.id, u.name),
    });
  } else {
    acciones.push({
      icon: "bi bi-slash-circle me-2",
      label: "Suspender",
      peligro: true,
      handler: () => suspenderUsuario(u.id, u.name),
    });
  }
  return crearMenuAcciones(acciones);
}

// Abre el modal de crear (u = null) o editar (u = usuario) y guarda al confirmar.
function abrirModalUsuario(u) {
  const editando = u !== null;

  const opciones = rolesAsignables
    .map(
      (r) =>
        '<option value="' +
        r.id_rol +
        '"' +
        (editando && u.id_rol === r.id_rol ? " selected" : "") +
        ">" +
        escaparHtml(r.nombre_rol) +
        "</option>",
    )
    .join("");

  const cuerpoHtml =
    '<div class="mb-3">' +
    '<label class="form-label" for="mNombre">Nombre</label>' +
    '<input class="form-control form-control-sm" id="mNombre" type="text" required value="' +
    (editando ? escaparHtml(u.name) : "") +
    '" />' +
    "</div>" +
    '<div class="mb-3">' +
    '<label class="form-label" for="mEmail">Correo</label>' +
    '<input class="form-control form-control-sm" id="mEmail" type="email" required value="' +
    (editando ? escaparHtml(u.email) : "") +
    '" />' +
    "</div>" +
    '<div class="mb-3">' +
    '<label class="form-label" for="mPassword">Contraseña</label>' +
    '<div class="input-group input-group-sm">' +
    '<input class="form-control form-control-sm" id="mPassword" type="password" minlength="8" ' +
    (editando ? 'placeholder="Dejar vacío para no cambiar"' : "required") +
    " />" +
    '<button class="btn toggle-password" type="button" data-target="mPassword" ' +
    'aria-label="Mostrar contraseña" aria-pressed="false">' +
    '<i class="bi bi-eye" aria-hidden="true"></i></button>' +
    "</div></div>" +
    '<div class="mb-3">' +
    '<label class="form-label" for="mPassword2">Confirmar contraseña</label>' +
    '<input class="form-control form-control-sm" id="mPassword2" type="password" minlength="8" ' +
    (editando ? 'placeholder="Dejar vacío para no cambiar"' : "required") +
    " />" +
    "</div>" +
    '<div class="mb-1">' +
    '<label class="form-label" for="mRol">Rol</label>' +
    '<select class="form-select form-select-sm" id="mRol" required>' +
    opciones +
    "</select></div>";

  return abrirModal({
    titulo: editando ? "Editar usuario" : "Nuevo usuario",
    cuerpoHtml,
    textoConfirmar: editando ? "Guardar" : "Crear",
    alConfirmar: async function (form) {
      const password = form.querySelector("#mPassword").value;
      const password2 = form.querySelector("#mPassword2").value;
      if (password !== password2) {
        throw new Error("Las contraseñas no coinciden.");
      }

      const datos = {
        name: form.querySelector("#mNombre").value.trim(),
        email: form.querySelector("#mEmail").value.trim(),
        id_rol: form.querySelector("#mRol").value,
      };
      if (password) {
        datos.password = password;
        datos.password_confirmation = password2;
      }

      const endpoint = editando ? "/usuarios/" + u.id : "/usuarios";
      const metodo = editando ? "PUT" : "POST";

      await apiFetch(endpoint, { method: metodo, body: JSON.stringify(datos) });
      await cargarUsuarios();
      mostrarToast(editando ? "Usuario actualizado" : "Usuario creado", "success");
    },
  });
}

// Suspende (borrado lógico) un usuario.
async function suspenderUsuario(id, nombre) {
  const ok = await confirmar({
    titulo: "¿Suspender usuario?",
    mensaje: 'Se suspenderá la cuenta de "' + nombre + '". No podrá iniciar sesión.',
    textoConfirmar: "Suspender",
    peligro: true,
  });
  if (!ok) return;

  try {
    await apiFetch("/usuarios/" + id, { method: "DELETE" });
    await cargarUsuarios();
    mostrarToast("Usuario suspendido", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  }
}

// Reactiva un usuario suspendido (revierte el borrado lógico).
async function restaurarUsuario(id, nombre) {
  const ok = await confirmar({
    titulo: "¿Restaurar usuario?",
    mensaje: 'Se reactivará la cuenta de "' + nombre + '". Volverá a poder iniciar sesión.',
    textoConfirmar: "Restaurar",
  });
  if (!ok) return;

  try {
    await apiFetch("/usuarios/" + id + "/restaurar", { method: "POST" });
    await cargarUsuarios();
    mostrarToast("Usuario restaurado", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  }
}
