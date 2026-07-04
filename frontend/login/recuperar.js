// recuperar.js — Solicita el enlace de restablecimiento. Usa apiFetch de api.js.

/* global apiFetch */

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("recuperarForm");
  const errorBox = document.getElementById("recuperarError");
  const okBox = document.getElementById("recuperarOk");
  const boton = document.getElementById("recuperarSubmit");
  const spinner = document.getElementById("recuperarSpinner");

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

    const email = document.getElementById("recuperarEmail").value.trim();

    try {
      const data = await apiFetch("/password/olvide", {
        method: "POST",
        body: JSON.stringify({ email }),
        sinSpinner: true,
      });
      // El backend responde siempre genérico (no revela si el correo existe).
      okBox.textContent = data.message || "Si el correo está registrado, te enviamos un enlace.";
      okBox.classList.remove("d-none");
      form.reset();
      form.classList.remove("was-validated");
    } catch (error) {
      errorBox.textContent = error.message;
      errorBox.classList.remove("d-none");
    } finally {
      boton.disabled = false;
      spinner.classList.add("d-none");
    }
  });
});
