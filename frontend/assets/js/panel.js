// panel.js — Pegamento del panel Blade: toast flash de sesión, confirmar antes de enviar y menús de acciones.

/* global bootstrap, mostrarToast, confirmar */

// El panel no carga api.js, pero toast.js y confirmar.js necesitan escaparHtml como global.
// La asignación a window lo mantiene global también cuando @vite lo empaqueta como módulo.
function escaparHtml(texto) {
  if (texto == null) return "";
  return String(texto)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}
window.escaparHtml = escaparHtml;

document.addEventListener("DOMContentLoaded", function () {
  // Mensaje flash que dejó el controlador en la sesión (éxito o error de una acción).
  const flash = document.getElementById("panelFlash");
  if (flash && flash.dataset.mensaje) {
    mostrarToast(flash.dataset.mensaje, flash.dataset.tipo || "info");
  }

  // Formularios de acción (suspender, eliminar) que piden confirmación antes de enviarse.
  document.querySelectorAll("form[data-confirmar]").forEach(function (form) {
    form.addEventListener("submit", async function (e) {
      if (form.dataset.confirmado === "1") return;
      e.preventDefault();
      const ok = await confirmar({
        titulo: form.dataset.confirmarTitulo || "¿Estás seguro?",
        mensaje: form.dataset.confirmarMensaje || "",
        textoConfirmar: form.dataset.confirmarTexto || "Confirmar",
        peligro: form.dataset.confirmarPeligro === "1",
      });
      if (ok) {
        form.dataset.confirmado = "1";
        form.submit();
      }
    });
  });

  // Menú de 3 puntos con estrategia "fixed" para que no lo recorte el scroll de .table-responsive.
  document.querySelectorAll("[data-menu-acciones]").forEach(function (boton) {
    bootstrap.Dropdown.getOrCreateInstance(boton, {
      popperConfig: (config) => ({ ...config, strategy: "fixed" }),
    });
  });
});
