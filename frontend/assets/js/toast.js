// toast.js — Notificaciones tipo "toast" (reemplazan a alert()).

/* exported mostrarToast, toastFlash */
/* global hayCargaActiva */

// Crea (una sola vez) el contenedor donde se apilan los toasts.
function obtenerContenedorToasts() {
  let cont = document.getElementById("toastContainer");
  if (!cont) {
    cont = document.createElement("div");
    cont.id = "toastContainer";
    cont.className = "toast-container";
    document.body.appendChild(cont);
  }
  return cont;
}

// Muestra un toast. tipo: "success" | "error" | "warning" | "info".
function mostrarToast(mensaje, tipo = "info", duracion = 4000) {
  const iconos = {
    success: "bi-check-circle-fill",
    error: "bi-x-circle-fill",
    warning: "bi-exclamation-triangle-fill",
    info: "bi-info-circle-fill",
  };

  const titulos = {
    success: "Éxito",
    error: "Error",
    warning: "Advertencia",
    info: "Información",
  };

  const cont = obtenerContenedorToasts();

  const toast = document.createElement("div");
  toast.className = "toast-item toast-" + tipo;
  toast.innerHTML =
    '<span class="toast-icon"><i class="bi ' +
    (iconos[tipo] || iconos.info) +
    '"></i></span>' +
    '<div class="toast-cuerpo">' +
    '<p class="toast-titulo">' +
    (titulos[tipo] || titulos.info) +
    "</p>" +
    '<p class="toast-msg">' +
    mensaje +
    "</p>" +
    "</div>" +
    '<button class="toast-close" aria-label="Cerrar">&times;</button>' +
    '<span class="toast-progress"></span>';

  cont.appendChild(toast);

  requestAnimationFrame(function () {
    toast.classList.add("toast-visible");
  });

  toast.querySelector(".toast-progress").style.animationDuration = duracion + "ms";

  function cerrar() {
    toast.classList.remove("toast-visible");
    setTimeout(function () {
      toast.remove();
    }, 300);
  }

  const timer = setTimeout(cerrar, duracion);
  toast.querySelector(".toast-close").addEventListener("click", function () {
    clearTimeout(timer);
    cerrar();
  });
}

// Guarda un toast para mostrarlo en la siguiente página (tras recargar o redirigir).
function toastFlash(mensaje, tipo = "info") {
  sessionStorage.setItem("toastFlash", JSON.stringify({ mensaje, tipo }));
}

// Al cargar la página, muestra el toast pendiente (flash) cuando el spinner ya se ocultó.
document.addEventListener("DOMContentLoaded", function () {
  const pendiente = sessionStorage.getItem("toastFlash");
  if (!pendiente) {
    return;
  }
  sessionStorage.removeItem("toastFlash");

  let datos;
  try {
    datos = JSON.parse(pendiente);
  } catch {
    return;
  }

  // Espera a que terminen las peticiones de la página antes de mostrar el toast,
  // para que aparezca después del spinner y no encima.
  let intentos = 0;
  let sinCargaSeguidas = 0;
  function mostrarCuandoListo() {
    const cargando = typeof hayCargaActiva === "function" && hayCargaActiva();
    // Solo damos por terminada la carga si lleva varios ciclos seguidos sin peticiones,
    // para no colarnos en el hueco entre dos rondas (ej. usuario y luego el dashboard).
    sinCargaSeguidas = cargando ? 0 : sinCargaSeguidas + 1;
    if (sinCargaSeguidas < 3 && intentos < 60) {
      intentos++;
      setTimeout(mostrarCuandoListo, 100);
    } else {
      mostrarToast(datos.mensaje, datos.tipo);
    }
  }
  // Margen inicial para que arranquen las peticiones de la página (spinner 300ms).
  setTimeout(mostrarCuandoListo, 350);
});
