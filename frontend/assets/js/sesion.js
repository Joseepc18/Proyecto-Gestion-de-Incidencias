// sesion.js — Arranque común de páginas admin: valida token+sesión y cablea logout.

/* exported requerirSesion, cablearLogout, inicializarPaginaAdmin */

/* global apiFetch, obtenerToken, eliminarToken */

// Si no hay token o falla (401/500), limpia y manda al login
async function requerirSesion() {
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return null;
  }
  try {
    const usuario = await apiFetch("/user");
    // Pinta el nombre en el navbar compartido (todas las páginas admin lo tienen).
    const el = document.getElementById("nombreUsuario");
    if (el) el.textContent = usuario.name;
    return usuario;
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

// Valida sesión (requerirSesion ya pinta el nombre) y cablea logout
async function inicializarPaginaAdmin() {
  const usuario = await requerirSesion();
  if (!usuario) return null;
  cablearLogout();
  return usuario;
}
