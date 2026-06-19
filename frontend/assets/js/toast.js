// toast.js — Notificaciones tipo "toast" (reemplazan a alert()).

/* exported mostrarToast, toastFlash */

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

  const cont = obtenerContenedorToasts();

  const toast = document.createElement("div");
  toast.className = "toast-item toast-" + tipo;
  toast.innerHTML =
    '<i class="bi ' +
    (iconos[tipo] || iconos.info) +
    ' toast-icon"></i>' +
    '<span class="toast-msg">' +
    mensaje +
    "</span>" +
    '<button class="toast-close" aria-label="Cerrar">&times;</button>' +
    '<span class="toast-progress"></span>';

  cont.appendChild(toast);

  // Entrada con animación
  requestAnimationFrame(function () {
    toast.classList.add("toast-visible");
  });

  // La barra de progreso dura lo mismo que el toast
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

// Al cargar cada página, muestra el toast pendiente si lo hay.
document.addEventListener("DOMContentLoaded", function () {
  const pendiente = sessionStorage.getItem("toastFlash");
  if (pendiente) {
    sessionStorage.removeItem("toastFlash");
    try {
      const { mensaje, tipo } = JSON.parse(pendiente);
      mostrarToast(mensaje, tipo);
    } catch {
      /* ignorar */
    }
  }
});
