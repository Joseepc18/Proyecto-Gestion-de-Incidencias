// sesion.js — Arranque común de páginas admin: valida token+sesión y cablea logout.

/* exported requerirSesion, cablearLogout, inicializarPaginaAdmin */

/* global apiFetch, obtenerToken, eliminarToken, mostrarToast */

// Si no hay token o falla (401/500), limpia y manda al login
async function requerirSesion() {
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return null;
  }
  try {
    const usuario = await apiFetch("/user");
    // Cachea los permisos para que los guards de página (tienePermiso) funcionen antes de pintar el menú.
    if (Array.isArray(usuario.permisos)) {
      localStorage.setItem("permisos_usuario", JSON.stringify(usuario.permisos));
    }
    // Pinta el nombre en el navbar compartido (todas las páginas admin lo tienen).
    const el = document.getElementById("nombreUsuario");
    if (el) el.textContent = usuario.name;
    // Cachea el id y avisa: la campana (script aparte) lo usa para su canal privado de notificaciones.
    localStorage.setItem("usuario_id", usuario.id);
    // Si el correo aún no está verificado, muestra el aviso con botón de reenvío.
    mostrarAvisoVerificacion(usuario);
    window.dispatchEvent(new CustomEvent("sesion-lista", { detail: usuario }));
    return usuario;
  } catch {
    eliminarToken();
    window.location.href = "../login/login.html";
    return null;
  }
}

// Inserta (una sola vez) el aviso "verifica tu correo" bajo el navbar, con botón para reenviar.
function mostrarAvisoVerificacion(usuario) {
  if (usuario.email_verificado !== false) return;
  const navbar = document.getElementById("adminNavbar");
  if (!navbar || document.getElementById("avisoVerificacion")) return;

  const aviso = document.createElement("div");
  aviso.id = "avisoVerificacion";
  aviso.className =
    "alert alert-warning d-flex align-items-center justify-content-between gap-2 rounded-0 mb-0 px-3 px-lg-4 py-2";
  aviso.setAttribute("role", "alert");

  const texto = document.createElement("span");
  texto.innerHTML =
    '<i class="bi bi-envelope-exclamation me-2" aria-hidden="true"></i>' +
    "Verifica tu correo electrónico para poder reportar incidencias.";

  const boton = document.createElement("button");
  boton.type = "button";
  boton.className = "btn btn-sm btn-warning flex-shrink-0";
  boton.textContent = "Reenviar correo";
  boton.addEventListener("click", async function () {
    boton.disabled = true;
    try {
      const r = await apiFetch("/email/reenviar-verificacion", { method: "POST" });
      mostrarToast(r.message || "Te reenviamos el correo de verificación.", "success");
    } catch (e) {
      mostrarToast(e.message || "No se pudo reenviar el correo.", "error");
      boton.disabled = false;
    }
  });

  aviso.append(texto, boton);
  navbar.insertAdjacentElement("afterend", aviso);
}

// Único listener de logout compartido por todas las páginas admin.
function cablearLogout() {
  const btn = document.getElementById("btnLogout");
  if (!btn || btn.dataset.logoutCableado === "1") return;
  btn.dataset.logoutCableado = "1";
  btn.addEventListener("click", async function (e) {
    e.preventDefault();
    btn.classList.add("pe-none", "opacity-50");
    try {
      await apiFetch("/logout", { method: "POST" });
    } catch {
      /* ignorar: igual limpiamos el token local */
    }
    eliminarToken();
    window.location.href = "../login/login.html";
  });
}

// Valida sesión (requerirSesion ya pinta el nombre) y cablea logout
async function inicializarPaginaAdmin() {
  const usuario = await requerirSesion();
  if (!usuario) return null;
  cablearLogout();
  return usuario;
}
