// reactivar.js — Solicita la reactivación de una cuenta suspendida. Usa apiFetch de api.js.

/* global apiFetch, motivoConOtroHtml, cablearMotivoConOtro, leerMotivoSeleccionado */

// Motivos frecuentes para pedir la reactivación; "Otro" abre un textarea libre.
const MOTIVOS_REACTIVACION = [
  "Creo que la suspensión fue un error",
  "Ya corregí la conducta que la motivó",
  "Necesito dar seguimiento a una incidencia que reporté",
  "Nunca supe el motivo de la suspensión",
];

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("reactivarForm");
  const errorBox = document.getElementById("reactivarError");
  const okBox = document.getElementById("reactivarOk");
  const boton = document.getElementById("reactivarSubmit");
  const spinner = document.getElementById("reactivarSpinner");
  const contenedorMotivo = document.getElementById("reactivarMotivo");

  // Mismo selector de motivos que usan los modales de eliminar y de reapertura.
  contenedorMotivo.innerHTML = motivoConOtroHtml(MOTIVOS_REACTIVACION);
  cablearMotivoConOtro();

  form.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    if (!form.checkValidity()) {
      form.classList.add("was-validated");
      return;
    }
    errorBox.classList.add("d-none");
    okBox.classList.add("d-none");
    boton.disabled = true;
    spinner.classList.remove("d-none");

    const email = document.getElementById("reactivarEmail").value.trim();
    const motivo = leerMotivoSeleccionado(form);

    try {
      const data = await apiFetch("/reactivacion/solicitar", {
        method: "POST",
        body: JSON.stringify({ email, motivo }),
        sinSpinner: true,
      });
      // El backend responde siempre genérico (no revela si el correo existe ni si está suspendido).
      okBox.textContent =
        data.message ||
        "Si el correo corresponde a una cuenta suspendida, registramos tu solicitud.";
      okBox.classList.remove("d-none");
      form.reset();
      form.classList.remove("was-validated");
      // El reset devuelve el select al placeholder, pero el bloque de "Otro" no se oculta solo.
      document.getElementById("modalMotivoOtroWrap").classList.add("d-none");
      document.getElementById("modalMotivoOtro").required = false;
    } catch (error) {
      errorBox.textContent = error.message;
      errorBox.classList.remove("d-none");
    } finally {
      boton.disabled = false;
      spinner.classList.add("d-none");
    }
  });
});
