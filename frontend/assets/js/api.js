// =============================================================
//  api.js — Capa base de comunicación con el backend (Laravel)
//  nginx hace que front y back compartan origen -> URL relativa.
// =============================================================

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

  const respuesta = await fetch(url, { ...opciones, headers });
  const data = await respuesta.json();

  if (!respuesta.ok) {
    throw new Error(data.message || "Error en la petición");
  }

  return data;
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
