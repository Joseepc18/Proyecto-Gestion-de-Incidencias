// sesion.js — Arranque común de páginas admin: valida token+sesión y cablea logout.

/* exported requerirSesion, cablearLogout, inicializarPaginaAdmin */

/* global apiFetch, obtenerToken, eliminarToken, mostrarToast, tienePermiso, inicioSegunRol */

// Guard de rol por página: quién puede ver cada carpeta. Se expresa con permisos (misma fuente
// que aplicarMenuRol, para no duplicar reglas); solo normal/tecnico se distinguen por rol porque
// no tienen permisos. Las páginas no listadas (detalle-incidencia, perfil, notificaciones, login)
// no tienen restricción de rol.
function rolPermitidoEnPagina(pagina, rol) {
  switch (pagina) {
    case "inicio":
      return tienePermiso("dashboard.ver");
    case "inicio-tecnico":
      return rol === "tecnico";
    case "gestion-incidencias":
      return tienePermiso("incidencias.gestionar") || tienePermiso("dashboard.ver");
    case "papelera":
      return tienePermiso("incidencias.papelera");
    case "bitacora":
      return tienePermiso("bitacora.ver");
    case "permisos":
      return tienePermiso("permisos.administrar");
    case "usuarios":
      return tienePermiso("usuarios.administrar");
    case "catalogos":
      return tienePermiso("catalogos.administrar");
    case "registrar":
      return tienePermiso("incidencias.gestionar") || rol === "normal";
    case "mis-incidencias":
      return rol === "normal" || rol === "tecnico";
    default:
      return true;
  }
}

// Carpeta actual de la URL: /gestion-incidencias/gestion-incidencias.html -> "gestion-incidencias".
function paginaActualGuard() {
  const partes = window.location.pathname.split("/").filter(Boolean);
  return partes.length >= 2 ? partes[partes.length - 2] : "";
}

// Si el rol no puede ver la página actual, lo manda a su inicio. Devuelve true si redirigió.
function aplicarGuardRol(rol) {
  if (!rol) return false;
  const pagina = paginaActualGuard();
  if (rolPermitidoEnPagina(pagina, rol)) return false;
  const destino = inicioSegunRol(rol);
  // Evita bucle si el propio inicio del rol quedara bloqueado (caché incompleta): el chequeo
  // autoritativo de requerirSesion corrige luego con /user.
  if (destino.indexOf("/" + pagina + "/") !== -1) return false;
  window.location.replace(destino);
  return true;
}

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
    const rol = usuario.rol ? usuario.rol.nombre_rol : "";
    if (rol) localStorage.setItem("rol_usuario", rol);
    // Guard de rol autoritativo con /user (la caché pudo quedar vieja): si esta página no
    // corresponde al rol, redirige y corta para que el JS de la página no llegue a correr.
    if (aplicarGuardRol(rol)) return new Promise(function () {});
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
// Solo aplica a quien puede reportar incidencias (ciudadano y admin); técnico/super_admin no registran.
function mostrarAvisoVerificacion(usuario) {
  if (usuario.email_verificado !== false) return;
  const rol = usuario.rol ? usuario.rol.nombre_rol : "";
  if (rol !== "normal" && !tienePermiso("incidencias.gestionar")) return;
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
    } finally {
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

// Guard temprano: con el rol ya cacheado (login/visita previa) redirige ANTES de pintar la
// plantilla equivocada, sin esperar a /user (requerirSesion hace el chequeo autoritativo después).
// El listener de pageshow cubre el bfcache: atrás/adelante restauran la página sin re-ejecutar
// scripts, así que ahí se revalida a mano.
(function () {
  aplicarGuardRol(localStorage.getItem("rol_usuario"));
  window.addEventListener("pageshow", function (e) {
    if (e.persisted) aplicarGuardRol(localStorage.getItem("rol_usuario"));
  });
})();
