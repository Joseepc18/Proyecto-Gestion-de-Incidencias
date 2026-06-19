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
let spinnerTimer = null;

// Crea (una sola vez) la capa con la ruedita de carga.
function obtenerSpinner() {
  let sp = document.getElementById("globalSpinner");
  if (!sp) {
    sp = document.createElement("div");
    sp.id = "globalSpinner";
    sp.className = "global-spinner d-none";
    sp.innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
    document.body.appendChild(sp);
  }
  return sp;
}

function spinnerInicio() {
  peticionesActivas++;
  // Solo la primera petición arranca el temporizador de 300ms
  if (peticionesActivas === 1) {
    spinnerTimer = setTimeout(function () {
      obtenerSpinner().classList.remove("d-none");
    }, 300);
  }
}

function spinnerFin() {
  peticionesActivas = Math.max(0, peticionesActivas - 1);
  // Cuando ya no quedan peticiones, cancela el temporizador y oculta
  if (peticionesActivas === 0) {
    clearTimeout(spinnerTimer);
    obtenerSpinner().classList.add("d-none");
  }
}

async function apiFetch(endpoint, opciones = {}) {
  const url = API_BASE + endpoint;

  const headers = { Accept: "application/json" };

  // FormData: el navegador pone Content-Type automáticamente (multipart)
  const esFormData = opciones.body instanceof FormData;
  if (!esFormData) {
    headers["Content-Type"] = "application/json";
  }

  const token = obtenerToken();
  if (token) {
    headers["Authorization"] = "Bearer " + token;
  }

  spinnerInicio();
  try {
    const respuesta = await fetch(url, { ...opciones, headers });
    const data = await respuesta.json();

    if (!respuesta.ok) {
      throw new Error(data.message || "Error en la petición");
    }

    return data;
  } finally {
    spinnerFin();
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
