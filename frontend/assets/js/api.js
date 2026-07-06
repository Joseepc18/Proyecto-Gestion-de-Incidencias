// api.js — Capa base de comunicación con el backend; URL relativa (mismo origen vía nginx).

/* exported guardarToken, obtenerToken, eliminarToken, apiFetch, aplicarMenuRol, tienePermiso, escaparHtml, hayCargaActiva */
/* global toastFlash */

const API_BASE = "/api";

// Escapa < > & " ' a entidades HTML para evitar XSS al meter texto en innerHTML/popups.
function escaparHtml(texto) {
  if (texto == null) return "";
  return String(texto)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

// Clave única para el token (evita errores de tipeo).
const TOKEN_KEY = "access_token";

// Helpers de token
function guardarToken(token) {
  localStorage.setItem(TOKEN_KEY, token);
}

function obtenerToken() {
  return localStorage.getItem(TOKEN_KEY);
}

function eliminarToken() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem("perfil_foto");
  localStorage.removeItem("rol_usuario");
  localStorage.removeItem("permisos_usuario");
}

// ¿El usuario autenticado tiene esta clave de permiso? (cacheada en localStorage por requerirSesion/login).
function tienePermiso(clave) {
  try {
    const lista = JSON.parse(localStorage.getItem("permisos_usuario") || "[]");
    return Array.isArray(lista) && lista.includes(clave);
  } catch {
    return false;
  }
}

// Spinner global (solo aparece si la petición tarda más de 300ms)
let peticionesActivas = 0;
let mostrarTimer = null;
let ocultarTimer = null;

// Crea (una sola vez) la capa con la ruedita de carga.
function obtenerSpinner() {
  let sp = document.getElementById("globalSpinner");
  if (!sp) {
    sp = document.createElement("div");
    sp.id = "globalSpinner";
    sp.className = "global-spinner d-none";
    sp.innerHTML =
      '<div class="loader-pin-container" role="status" aria-label="Cargando">' +
      '<svg class="loader-pin" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">' +
      '<mask id="loaderPinHole"><rect width="24" height="24" fill="white" />' +
      '<circle cx="12" cy="9" r="3.5" fill="black" /></mask>' +
      '<path d="M12 2C8.13 2 5 5.13 5 9C5 14.25 12 22 12 22C12 22 19 14.25 19 9C19 5.13 15.87 2 12 2Z" fill="currentColor" mask="url(#loaderPinHole)" />' +
      "</svg>" +
      '<div class="loader-pin-shadow"></div>' +
      "</div>";
    document.body.appendChild(sp);
  }
  return sp;
}

function spinnerVisible() {
  return !obtenerSpinner().classList.contains("d-none");
}

// Indica si hay alguna petición en curso (aunque el spinner aún no se haya pintado).
function hayCargaActiva() {
  return peticionesActivas > 0;
}

function spinnerInicio() {
  peticionesActivas++;
  if (ocultarTimer) {
    clearTimeout(ocultarTimer);
    ocultarTimer = null;
  }
  if (spinnerVisible() || mostrarTimer) {
    return;
  }
  mostrarTimer = setTimeout(function () {
    obtenerSpinner().classList.remove("d-none");
    mostrarTimer = null;
  }, 300);
}

function spinnerFin() {
  peticionesActivas = Math.max(0, peticionesActivas - 1);
  if (peticionesActivas > 0) {
    return;
  }
  if (mostrarTimer) {
    clearTimeout(mostrarTimer);
    mostrarTimer = null;
  }
  if (spinnerVisible()) {
    ocultarTimer = setTimeout(function () {
      obtenerSpinner().classList.add("d-none");
      ocultarTimer = null;
    }, 200);
  }
}

async function apiFetch(endpoint, opciones = {}) {
  const url = API_BASE + endpoint;

  const { sinSpinner = false, ...fetchOpts } = opciones;

  const headers = { Accept: "application/json" };

  const esFormData = fetchOpts.body instanceof FormData;
  if (!esFormData) {
    headers["Content-Type"] = "application/json";
  }

  const token = obtenerToken();
  if (token) {
    headers["Authorization"] = "Bearer " + token;
  }

  if (!sinSpinner) {
    spinnerInicio();
  }
  try {
    const respuesta = await fetch(url, { ...fetchOpts, headers });

    if (respuesta.status === 401 && token && !window.location.pathname.includes("/login/")) {
      eliminarToken();
      toastFlash("Tu sesión expiró. Vuelve a iniciar sesión.", "warning");
      window.location.href = "../login/login.html";
      return new Promise(function () {});
    }

    let data;
    try {
      data = await respuesta.json();
    } catch {
      data = null;
    }

    // El backend exige 2FA para esta acción y el admin aún no lo activó: mensaje claro + llevar al setup (perfil),
    // en vez del "Error al cargar…" genérico. Se salta si ya estamos en perfil/login para no rebotar.
    if (
      respuesta.status === 403 &&
      data &&
      data.two_factor_required &&
      !window.location.pathname.includes("/perfil/") &&
      !window.location.pathname.includes("/login/")
    ) {
      toastFlash("Activa la verificación en dos pasos para realizar esta acción.", "warning");
      window.location.href = "../perfil/perfil.html";
      return new Promise(function () {});
    }

    if (data === null) {
      // Respuesta sin JSON (502/HTML del túnel o Nginx, o 204 sin cuerpo): mensaje legible.
      if (!respuesta.ok) {
        throw new Error(
          respuesta.status >= 500
            ? "El servidor no está disponible. Intenta de nuevo en unos minutos."
            : "No se pudo completar la petición (" + respuesta.status + ").",
        );
      }
      return {};
    }

    if (!respuesta.ok) {
      let mensaje = data.message || "Error en la petición";
      if (data.errors) {
        const primero = Object.values(data.errors)[0];
        if (Array.isArray(primero) && primero[0]) {
          mensaje = primero[0];
        }
      }
      throw new Error(mensaje);
    }

    return data;
  } finally {
    if (!sinSpinner) {
      spinnerFin();
    }
  }
}

// Muestra/oculta los enlaces del menú por permiso (no por nombre de rol); el rol solo distingue técnico/normal.
function aplicarMenuRol(rol, permisos) {
  if (rol) localStorage.setItem("rol_usuario", rol);
  if (Array.isArray(permisos)) localStorage.setItem("permisos_usuario", JSON.stringify(permisos));
  const esNormal = rol === "normal";
  const esTecnico = rol === "tecnico";
  const esRolAdmin = rol === "admin" || rol === "super_admin";
  const gestiona = tienePermiso("incidencias.gestionar");
  const veDashboard = tienePermiso("dashboard.ver");
  const vePapelera = tienePermiso("incidencias.papelera");
  const veBitacora = tienePermiso("bitacora.ver");

  function mostrar(id, visible) {
    const el = document.getElementById(id);
    if (el) el.classList.toggle("d-none", !visible);
  }

  mostrar("navInicio", gestiona || veDashboard || esTecnico);

  // El "Inicio" del técnico es su propio panel (el href por defecto apunta al del admin).
  const navInicio = document.getElementById("navInicio");
  if (navInicio && esTecnico) navInicio.href = "../inicio-tecnico/inicio-tecnico.html";
  mostrar("navIncidencias", gestiona || veDashboard);
  mostrar("navPapelera", vePapelera);
  mostrar("navBitacora", veBitacora);
  mostrar("navPermisos", tienePermiso("permisos.administrar"));
  mostrar("navUsuarios", tienePermiso("usuarios.administrar"));
  mostrar("navCatalogos", tienePermiso("catalogos.administrar"));
  mostrar("navMisIncidencias", !gestiona && !esRolAdmin);
  mostrar("navRegistrar", gestiona || esNormal);

  const navMis = document.getElementById("navMisIncidencias");
  const textoMis = navMis ? navMis.querySelector(".nav-text") : null;
  if (textoMis) textoMis.textContent = rol === "tecnico" ? "Mis asignaciones" : "Mis reportes";
}

// Pantalla de arranque según el rol: admin/super_admin y técnico tienen su propio Inicio; el normal va a "Mis incidencias".
/* exported inicioSegunRol */
function inicioSegunRol(rol) {
  if (rol === "admin" || rol === "super_admin") return "../inicio/inicio.html";
  if (rol === "tecnico") return "../inicio-tecnico/inicio-tecnico.html";
  return "../mis-incidencias/mis-incidencias.html";
}

// abrirChat añade ?chat=1 para que la página abra el chat directamente
/* exported rutaDetalleIncidencia */
function rutaDetalleIncidencia(id, rol, abrirChat = false) {
  return "../detalle-incidencia/detalle-incidencia.html?id=" + id + (abrirChat ? "&chat=1" : "");
}
