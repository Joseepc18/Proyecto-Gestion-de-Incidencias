// api.js — Capa base de comunicación con el backend (Laravel).
// nginx hace que front y back compartan origen, por eso la URL es relativa.

/* exported guardarToken, obtenerToken, eliminarToken, apiFetch, aplicarMenuRol */

const API_BASE = "/api";

// Clave única para el token (evita errores de tipeo).
const TOKEN_KEY = "access_token";

// ---- Helpers de token ----

function guardarToken(token) {
  localStorage.setItem(TOKEN_KEY, token);
}

function obtenerToken() {
  return localStorage.getItem(TOKEN_KEY);
}

function eliminarToken() {
  localStorage.removeItem(TOKEN_KEY);
}

// ---- Spinner global (solo aparece si la petición tarda más de 300ms) ----

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
  // Oculta con un margen de gracia: si otra petición arranca enseguida,
  // spinnerInicio cancela esta ocultación y el spinner no parpadea
  if (spinnerVisible()) {
    ocultarTimer = setTimeout(function () {
      obtenerSpinner().classList.add("d-none");
      ocultarTimer = null;
    }, 200);
  }
}

async function apiFetch(endpoint, opciones = {}) {
  const url = API_BASE + endpoint;

  // sinSpinner: omite el spinner global (útil en login/registro, que ya muestran
  // su propio spinner en el botón). El resto de opciones van directo a fetch.
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
    const data = await respuesta.json();

    if (!respuesta.ok) {
      // En errores de validación, muestra el primer mensaje (ya traducido),
      // no el resumen "(and N more errors)" que Laravel arma en inglés.
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

// Muestra/oculta los enlaces del menú según el rol del usuario.
// Admin: tabla de incidencias + usuarios. Resto: "Mis incidencias".
function aplicarMenuRol(rol) {
  const esAdmin = rol === "admin";

  function mostrar(id, visible) {
    const el = document.getElementById(id);
    if (el) el.classList.toggle("d-none", !visible);
  }

  mostrar("navIncidencias", esAdmin);
  mostrar("navUsuarios", esAdmin);
  mostrar("navMisIncidencias", !esAdmin);
}
