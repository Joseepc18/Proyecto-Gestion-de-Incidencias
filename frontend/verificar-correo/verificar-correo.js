// verificar-correo.js — Pantalla-muro: bloquea la app hasta que el ciudadano verifique su correo.

/* global apiFetch, obtenerToken, eliminarToken, mostrarToast, toastFlash, inicioSegunRol */

// Poll de auto-detección: si el usuario verifica desde el correo, entra solo sin re-loguear.
let intervaloPoll = null;

document.addEventListener("DOMContentLoaded", async function () {
  if (!obtenerToken()) {
    window.location.replace("../login/login.html");
    return;
  }
  const usuario = await estadoInicial();
  if (usuario) mostrarMuro(usuario);
});

// Trae /user; si ya está verificado (o no es ciudadano) entra a la app; si falla, muestra el muro igual.
async function estadoInicial() {
  try {
    const usuario = await apiFetch("/user", { sinSpinner: true });
    const rol = usuario.rol ? usuario.rol.nombre_rol : "";
    if (rol !== "normal" || usuario.email_verificado !== false) {
      entrarApp(usuario);
      return null;
    }
    return usuario;
  } catch {
    // Fallo transitorio del servidor: mostramos el muro (asumimos sin verificar) en vez de dejar entrar.
    return {};
  }
}

// Revela el muro, pinta el correo y cablea botones + auto-detección.
function mostrarMuro(usuario) {
  const email = document.getElementById("muroEmail");
  if (email && usuario.email) email.textContent = usuario.email;
  document.documentElement.classList.remove("verificando-sesion");

  document.getElementById("muroReenviar").addEventListener("click", reenviar);
  document.getElementById("muroVerificar").addEventListener("click", function () {
    comprobar(true);
  });
  document.getElementById("muroLogout").addEventListener("click", cerrarSesion);

  intervaloPoll = setInterval(function () {
    comprobar(false);
  }, 8000);
  // Al volver a la pestaña (p. ej. tras abrir el correo) comprueba enseguida.
  document.addEventListener("visibilitychange", function () {
    if (!document.hidden) comprobar(false);
  });
}

// Reenvía el correo de verificación al usuario en sesión.
async function reenviar() {
  const boton = document.getElementById("muroReenviar");
  boton.disabled = true;
  try {
    const r = await apiFetch("/email/reenviar-verificacion", { method: "POST", sinSpinner: true });
    mostrarToast(r.message || "Te reenviamos el correo de verificación.", "success");
  } catch (e) {
    mostrarToast(e.message || "No se pudo reenviar el correo.", "error");
  } finally {
    boton.disabled = false;
  }
}

// Comprueba si ya se verificó. manual=true avisa cuando aún no (clic en "Ya verifiqué").
async function comprobar(manual) {
  try {
    const usuario = await apiFetch("/user", { sinSpinner: true });
    if (usuario.email_verificado === true) {
      toastFlash("Correo verificado. ¡Bienvenido!", "success");
      entrarApp(usuario);
    } else if (manual) {
      mostrarToast("Aún no detectamos la verificación. Abre el enlace del correo.", "warning");
    }
  } catch {
    /* transitorio: el poll siguiente reintenta */
  }
}

// Verificado: corta el poll, cachea rol/permisos/foto y entra al inicio del ciudadano.
function entrarApp(usuario) {
  if (intervaloPoll) clearInterval(intervaloPoll);
  const rol = usuario.rol ? usuario.rol.nombre_rol : "";
  if (rol) localStorage.setItem("rol_usuario", rol);
  if (Array.isArray(usuario.permisos)) {
    localStorage.setItem("permisos_usuario", JSON.stringify(usuario.permisos));
  }
  localStorage.setItem("perfil_foto", usuario.foto_perfil || "");
  window.location.replace(inicioSegunRol(rol));
}

// Salida de emergencia (p. ej. correo equivocado): cierra sesión y vuelve al login.
async function cerrarSesion() {
  if (intervaloPoll) clearInterval(intervaloPoll);
  try {
    await apiFetch("/logout", { method: "POST", sinSpinner: true });
  } catch {
    /* igual limpiamos el token local */
  }
  eliminarToken();
  window.location.replace("../login/login.html");
}
