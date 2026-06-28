// confirmar.js — Diálogo de confirmación (reemplaza a confirm()). Retorna una Promise<boolean>.

/* exported confirmar */
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

    function cerrar(resultado) {
      overlay.classList.remove("confirm-visible");
      setTimeout(function () {
        overlay.remove();
      }, 200);
      resolve(resultado);
    }

    overlay.querySelector("[data-confirmar]").addEventListener("click", function () {
      cerrar(true);
    });
    overlay.querySelector("[data-cancelar]").addEventListener("click", function () {
      cerrar(false);
    });
    overlay.addEventListener("click", function (e) {
      if (e.target === overlay) cerrar(false);
    });
  });
}
