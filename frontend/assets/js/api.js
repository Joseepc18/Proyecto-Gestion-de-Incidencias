// =============================================================
//  api.js — Capa base de comunicación con el backend (Laravel)
//  nginx hace que front y back compartan origen -> URL relativa.
// =============================================================

/* exported guardarToken, obtenerToken, eliminarToken, apiFetch */

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
  // URL completa: "/api" + "/login" -> "/api/login"
  const url = API_BASE + endpoint;

  // Headers que siempre mandamos
  const headers = {
    Accept: "application/json",
    "Content-Type": "application/json",
  };

  // Si ya hay token guardado, lo añadimos para las rutas protegidas
  const token = obtenerToken();
  if (token) {
    headers["Authorization"] = "Bearer " + token;
  }

  // Hacemos la petición y esperamos la respuesta
  const respuesta = await fetch(url, { ...opciones, headers });

  // Leemos el cuerpo y lo convertimos de JSON a objeto JS
  const data = await respuesta.json();

  // Si el status no fue 2xx, lanzamos error con el mensaje del backend
  if (!respuesta.ok) {
    throw new Error(data.message || "Error en la petición");
  }

  return data;
}
