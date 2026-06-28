// api.js — Capa base de comunicación con el backend; URL relativa (mismo origen vía nginx).

/* exported guardarToken, obtenerToken, eliminarToken, apiFetch, aplicarMenuRol, escaparHtml, hayCargaActiva */
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
  // Olvida la foto cacheada del navbar para no mostrar la del usuario anterior.
  localStorage.removeItem("perfil_foto");
  // Olvida el rol cacheado para no pintar el menú del usuario anterior.
  localStorage.removeItem("rol_usuario");
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
  // Si había una ocultación pendiente, cancélala: seguimos cargando (sin parpadeo)
  if (ocultarTimer) {
    clearTimeout(ocultarTimer);
    ocultarTimer = null;
  }
  // Si ya está visible o hay un "mostrar" en cola, no reprogramamos
  if (spinnerVisible() || mostrarTimer) {
    return;
  }
  // Solo aparece si la petición tarda más de 300ms
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
  // No quedan peticiones: cancela un "mostrar" que aún no ocurrió
  if (mostrarTimer) {
    clearTimeout(mostrarTimer);
    mostrarTimer = null;
  }
  // Oculta con margen de gracia: si otra petición arranca enseguida, no parpadea.
  if (spinnerVisible()) {
    ocultarTimer = setTimeout(function () {
      obtenerSpinner().classList.add("d-none");
      ocultarTimer = null;
    }, 200);
  }
}

async function apiFetch(endpoint, opciones = {}) {
  const url = API_BASE + endpoint;

  // sinSpinner: omite el spinner global (login/registro ya muestran el suyo en el botón).
  const { sinSpinner = false, ...fetchOpts } = opciones;

  const headers = { Accept: "application/json" };

  // FormData: el navegador pone Content-Type automáticamente (multipart)
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

    // Token vencido/inválido (401 con token): limpia y vuelve al login; en la página de login no.
    if (respuesta.status === 401 && token && !window.location.pathname.includes("/login/")) {
      eliminarToken();
      toastFlash("Tu sesión expiró. Vuelve a iniciar sesión.", "warning");
      window.location.href = "../login/login.html";
      // Promesa que nunca resuelve: corta el flujo del llamador mientras redirige.
      return new Promise(function () {});
    }

    const data = await respuesta.json();

    if (!respuesta.ok) {
      // Muestra el primer mensaje de validación (ya traducido), no el resumen en inglés.
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

// Muestra/oculta los enlaces del menú según el rol (el normal arranca en "Mis incidencias").
function aplicarMenuRol(rol) {
  // Cachea el rol para que layout.js pinte el menú correcto antes de pedir /user (sin parpadeo).
  if (rol) localStorage.setItem("rol_usuario", rol);
  const esAdmin = rol === "admin";
  const esNormal = rol === "normal";

  function mostrar(id, visible) {
    const el = document.getElementById(id);
    if (el) el.classList.toggle("d-none", !visible);
  }

  mostrar("navInicio", !esNormal);
  mostrar("navIncidencias", esAdmin);
  mostrar("navUsuarios", esAdmin);
  mostrar("navCatalogos", esAdmin);
  mostrar("navMisIncidencias", !esAdmin);
  // El técnico no registra incidencias; solo admin y ciudadano ven el enlace.
  mostrar("navRegistrar", esAdmin || esNormal);

  // "Mis incidencias" se renombra según el rol: el técnico ve asignaciones; el ciudadano, sus reportes.
  const navMis = document.getElementById("navMisIncidencias");
  const textoMis = navMis ? navMis.querySelector(".nav-text") : null;
  if (textoMis) textoMis.textContent = rol === "tecnico" ? "Mis asignaciones" : "Mis reportes";
}

// Pantalla de arranque según el rol: normal → "Mis incidencias"; resto → Inicio.
/* exported inicioSegunRol */
function inicioSegunRol(rol) {
  return rol === "normal" ? "../mis-incidencias/mis-incidencias.html" : "../inicio/inicio.html";
}

// Página de detalle de una incidencia según el rol: el ciudadano ve la suya; admin y técnico, la de gestión.
// abrirChat: añade ?chat=1 para que la página abra el chat directamente (notificación de comentario).
/* exported rutaDetalleIncidencia */
function rutaDetalleIncidencia(id, rol, abrirChat = false) {
  const base =
    rol === "normal"
      ? "../detalle-reporte/detalle-reporte.html"
      : "../detalle-gestion/detalle-gestion.html";
  return base + "?id=" + id + (abrirChat ? "&chat=1" : "");
}
