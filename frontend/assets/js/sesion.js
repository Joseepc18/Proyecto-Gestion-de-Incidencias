// sesion.js — Arranque común de páginas admin: valida token+sesión y cablea logout.

/* exported requerirSesion, cablearLogout, inicializarPaginaAdmin */

/* global apiFetch, obtenerToken, eliminarToken */

// Pide /user; si no hay token o falla (401/500), limpia y manda al login.
// Devuelve el usuario o null (la página debe abortar en ese caso).
async function requerirSesion() {
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return null;
  }
  try {
    return await apiFetch("/user");
  } catch {
    eliminarToken();
    window.location.href = "../login/login.html";
    return null;
  }
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

// Arranque típico de página admin: valida sesión, muestra el nombre y cablea logout.
// Devuelve el usuario o null. opts.pintarNombre=false omite el "#nombreUsuario".
async function inicializarPaginaAdmin(opts) {
  opts = opts || {};
  const usuario = await requerirSesion();
  if (!usuario) return null;
  if (opts.pintarNombre !== false) {
    const el = document.getElementById("nombreUsuario");
    if (el) el.textContent = usuario.name;
  }
  cablearLogout();
  return usuario;
}
