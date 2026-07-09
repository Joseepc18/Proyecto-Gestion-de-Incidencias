// Fija el tema guardado antes de pintar, para que no parpadee en cada carga.
// Sin defer: debe ejecutarse en el <head>, bloqueando, antes del primer paint.
(function () {
  try {
    var tema = localStorage.getItem("adminHMD.colorTheme");
    if (tema !== "dark" && tema !== "light") {
      tema =
        window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches
          ? "dark"
          : "light";
    }
    document.documentElement.setAttribute("data-theme", tema);
    document.documentElement.setAttribute("data-bs-theme", tema);
  } catch {
    /* localStorage no disponible: se queda en claro */
  }
})();
