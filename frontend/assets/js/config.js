// config.js — Configuracion global reutilizable de la app.
// Cambiar el nombre aqui lo actualiza en toda la interfaz: sidebar, login, errores y pestanas.

window.APP_CONFIG = {
  nombre: " QUICKMAP",
  subtitulo: "Gestión georreferenciada",
};

(function () {
  const cfg = window.APP_CONFIG;

  function aplicar() {
    // Rellena cualquier elemento marcado con data-app-nombre / data-app-subtitulo
    document.querySelectorAll("[data-app-nombre]").forEach(function (el) {
      el.textContent = cfg.nombre;
    });
    document.querySelectorAll("[data-app-subtitulo]").forEach(function (el) {
      el.textContent = cfg.subtitulo;
    });
    // La pestana usa el nombre como sufijo: "Pagina | Nombre"
    const titulo = document.title;
    const sep = titulo.indexOf("|");
    const prefijo = sep >= 0 ? titulo.slice(0, sep).trim() : titulo.trim();
    document.title = prefijo ? prefijo + " | " + cfg.nombre : cfg.nombre;
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", aplicar);
  } else {
    aplicar();
  }
})();
