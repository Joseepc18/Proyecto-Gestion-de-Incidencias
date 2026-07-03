// modal.js — Modal reutilizable para formularios pequeños (crear/editar). Devuelve Promise<boolean>.

/* exported abrirModal, motivoConOtroHtml, cablearMotivoConOtro, leerMotivoSeleccionado */
/* global escaparHtml */

// HTML de un <select> de motivos frecuentes + "Otro" con textarea libre (eliminar incidencia, reapertura, etc.).
function motivoConOtroHtml(opciones) {
  const listaOpciones = opciones
    .map((m) => '<option value="' + m + '">' + m + "</option>")
    .join("");
  return (
    '<label for="modalMotivoTipo" class="form-label">Motivo</label>' +
    '<select class="form-select" id="modalMotivoTipo" required>' +
    '<option value="" disabled selected>Selecciona un motivo…</option>' +
    listaOpciones +
    '<option value="__otro__">Otro (especificar)</option>' +
    "</select>" +
    '<div class="mt-2 d-none" id="modalMotivoOtroWrap">' +
    '<label for="modalMotivoOtro" class="form-label">Especifica el motivo</label>' +
    '<textarea class="form-control" id="modalMotivoOtro" rows="3" minlength="5" maxlength="500"></textarea>' +
    "</div>"
  );
}

// Muestra/exige el textarea "Otro" solo al elegirlo; llamar tras abrirModal() con motivoConOtroHtml() en el cuerpo.
function cablearMotivoConOtro() {
  const selTipo = document.getElementById("modalMotivoTipo");
  const wrapOtro = document.getElementById("modalMotivoOtroWrap");
  const txtOtro = document.getElementById("modalMotivoOtro");
  selTipo.addEventListener("change", function () {
    const esOtro = selTipo.value === "__otro__";
    wrapOtro.classList.toggle("d-none", !esOtro);
    txtOtro.required = esOtro;
    if (esOtro) txtOtro.focus();
  });
}

// Lee el motivo final del form del modal: el texto libre si eligió "Otro", si no la opción tal cual.
function leerMotivoSeleccionado(form) {
  const tipo = form.querySelector("#modalMotivoTipo").value;
  return tipo === "__otro__" ? form.querySelector("#modalMotivoOtro").value.trim() : tipo;
}

// alConfirmar(form): callback async; si lanza, el modal queda abierto y muestra el error.
function abrirModal(opciones = {}) {
  const {
    titulo = "",
    cuerpoHtml = "",
    textoConfirmar = "Guardar",
    textoCancelar = "Cancelar",
    peligro = false,
    alConfirmar = null,
  } = opciones;

  return new Promise(function (resolve) {
    const overlay = document.createElement("div");
    overlay.className = "modal-overlay";
    overlay.innerHTML =
      '<div class="modal-caja" role="dialog" aria-modal="true">' +
      '<div class="modal-cabecera">' +
      '<h3 class="modal-titulo">' +
      escaparHtml(titulo) +
      "</h3>" +
      '<button class="modal-cerrar" type="button" aria-label="Cerrar">' +
      '<i class="bi bi-x-lg" aria-hidden="true"></i></button>' +
      "</div>" +
      '<form class="modal-form" id="modalForm" novalidate>' +
      '<div class="modal-cuerpo">' +
      cuerpoHtml +
      "</div>" +
      '<p class="modal-error text-danger d-none" role="alert"></p>' +
      '<div class="modal-acciones">' +
      '<button class="btn btn-outline-secondary btn-sm" type="button" data-cancelar>' +
      escaparHtml(textoCancelar) +
      "</button>" +
      '<button class="btn btn-sm ' +
      (peligro ? "btn-danger" : "btn-primary") +
      '" type="submit" data-confirmar>' +
      '<span class="spinner-border spinner-border-sm me-1 d-none" data-spinner role="status"></span>' +
      escaparHtml(textoConfirmar) +
      "</button>" +
      "</div></form></div>";

    document.body.appendChild(overlay);
    requestAnimationFrame(function () {
      overlay.classList.add("modal-visible");
    });

    const form = overlay.querySelector("#modalForm");
    const error = overlay.querySelector(".modal-error");
    const btnConfirmar = overlay.querySelector("[data-confirmar]");
    const spinner = overlay.querySelector("[data-spinner]");

    // Foco al primer control del formulario.
    const primero = form.querySelector("input, select, textarea");
    if (primero) setTimeout(() => primero.focus(), 50);

    let cerrado = false;
    function cerrar(resultado) {
      if (cerrado) return;
      cerrado = true;
      overlay.classList.remove("modal-visible");
      setTimeout(() => overlay.remove(), 200);
      document.removeEventListener("keydown", alPulsarTecla);
      resolve(resultado);
    }

    function alPulsarTecla(e) {
      if (e.key === "Escape") {
        cerrar(false);
        return;
      }
      // Atrapa el foco dentro del modal: el Tab cicla entre sus controles, no se va al fondo.
      if (e.key !== "Tab") return;
      const focos = overlay.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
      );
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

    overlay.querySelector(".modal-cerrar").addEventListener("click", () => cerrar(false));
    overlay.querySelector("[data-cancelar]").addEventListener("click", () => cerrar(false));
    overlay.addEventListener("click", function (e) {
      if (e.target === overlay) cerrar(false);
    });

    form.addEventListener("submit", async function (e) {
      e.preventDefault();
      if (!form.reportValidity()) return;
      error.classList.add("d-none");

      if (!alConfirmar) {
        cerrar(true);
        return;
      }

      btnConfirmar.disabled = true;
      spinner.classList.remove("d-none");
      try {
        await alConfirmar(form);
        cerrar(true);
      } catch (err) {
        error.textContent = err.message || "No se pudo guardar.";
        error.classList.remove("d-none");
      } finally {
        btnConfirmar.disabled = false;
        spinner.classList.add("d-none");
      }
    });
  });
}
