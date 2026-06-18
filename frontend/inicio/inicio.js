// inicio.js — Protege el panel y maneja usuario + logout.

/* global apiFetch, obtenerToken, eliminarToken */

document.addEventListener("DOMContentLoaded", async function () {
  // GUARD: si no hay token, no puede estar aquí -> al login.
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

  // Cargar el usuario autenticado y mostrar su nombre.
  try {
    const usuario = await apiFetch("/user");
    document.getElementById("nombreUsuario").textContent = usuario.name;
    document.getElementById("saludoNombre").textContent = usuario.name;

    // El enlace "Usuarios" solo se muestra al admin.
    if (usuario.rol && usuario.rol.nombre_rol === "admin") {
      document.getElementById("navUsuarios").classList.remove("d-none");
    }
  } catch {
    // Token inválido o expirado -> limpiar y al login.
    eliminarToken();
    window.location.href = "../login/login.html";
    return;
  }

  // LOGOUT
  document.getElementById("btnLogout").addEventListener("click", async (evento) => {
    evento.preventDefault();
    try { await apiFetch("/logout", { method: "POST" }); } catch { /* ignorar */ }
    eliminarToken();
    window.location.href = "../login/login.html";
  });
});
