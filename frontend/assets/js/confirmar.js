// confirmar.js — Diálogo de confirmación (reemplaza a confirm()). Retorna una Promise<boolean>.

/* exported confirmar */
/* global escaparHtml */

function confirmar(opciones = {}) {
  const {
    titulo = "¿Estás seguro?",
    mensaje = "",
    textoConfirmar = "Confirmar",
    textoCancelar = "Cancelar",
    peligro = false,
  } = opciones;

  return new Promise(function (resolve) {
    const overlay = document.createElement("div");
    overlay.className = "confirm-overlay";
    overlay.innerHTML =
      '<div class="confirm-box">' +
      '<h3 class="confirm-titulo">' +
      escaparHtml(titulo) +
      "</h3>" +
      (mensaje ? '<p class="confirm-mensaje">' + escaparHtml(mensaje) + "</p>" : "") +
      '<div class="confirm-acciones">' +
      '<button class="btn btn-outline-secondary btn-sm" data-cancelar>' +
      escaparHtml(textoCancelar) +
      "</button>" +
      '<button class="btn btn-sm ' +
      (peligro ? "btn-danger" : "btn-primary") +
      '" data-confirmar>' +
      escaparHtml(textoConfirmar) +
      "</button>" +
      "</div></div>";

    document.body.appendChild(overlay);
    requestAnimationFrame(function () {
      overlay.classList.add("confirm-visible");
    });

    const btnConfirmar = overlay.querySelector("[data-confirmar]");
    // Foco inicial al botón de confirmar (accesibilidad, igual que modal.js).
    setTimeout(() => btnConfirmar.focus(), 50);

    let cerrado = false;
    function cerrar(resultado) {
      if (cerrado) return;
      cerrado = true;
      overlay.classList.remove("confirm-visible");
      setTimeout(function () {
        overlay.remove();
      }, 200);
      document.removeEventListener("keydown", alPulsarTecla);
      resolve(resultado);
    }

    // Cierre con Escape (cancela) y foco atrapado dentro del diálogo, igual que modal.js.
    function alPulsarTecla(e) {
      if (e.key === "Escape") {
        cerrar(false);
        return;
      }
      if (e.key !== "Tab") return;
      const focos = overlay.querySelectorAll("button:not([disabled])");
      if (!focos.length) return;
      const primero = focos[0];
      const ultimo = focos[focos.length - 1];
      if (e.shiftKey && document.activeElement === primero) {
        e.preventDefault();
        ultimo.focus();
      } else if (!e.shiftKey && document.activeElement === ultimo) {
        e.preventDefault();
        primero.focus();
      }
    }
    document.addEventListener("keydown", alPulsarTecla);

    btnConfirmar.addEventListener("click", function () {
      cerrar(true);
    });
    overlay.querySelector("[data-cancelar]").addEventListener("click", function () {
      cerrar(false);
    });
    // Cierra al hacer clic en el fondo, pero no si el gesto empezó adentro (p. ej. seleccionar
    // texto y soltar el mouse fuera): el click solo cuenta si el mousedown también fue en el fondo.
    let mousedownEnFondo = false;
    overlay.addEventListener("mousedown", function (e) {
      mousedownEnFondo = e.target === overlay;
    });
    overlay.addEventListener("click", function (e) {
      if (e.target === overlay && mousedownEnFondo) cerrar(false);
    });
  });
}
