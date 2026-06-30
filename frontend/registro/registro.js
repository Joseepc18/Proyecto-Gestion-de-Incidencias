// registro.js — Lógica del registro. Usa apiFetch de api.js.

/* global apiFetch, toastFlash */

document.addEventListener("DOMContentLoaded", function () {
  // El redirect con token ya lo valida login.js (carga antes); aquí sólo va el formulario.

  const form = document.getElementById("registroForm");
  const errorBox = document.getElementById("registroError");
  const boton = document.getElementById("registroSubmit");
  const spinner = document.getElementById("registroSpinner");

  form.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    if (!form.checkValidity()) {
      return;
    }
    errorBox.classList.add("d-none");

    const name = document.getElementById("registerName").value.trim();
    const email = document.getElementById("registerEmail").value.trim();
    const password = document.getElementById("registerPassword").value;
    const passwordConfirmation = document.getElementById("registerPasswordConfirmation").value;

    if (password !== passwordConfirmation) {
      errorBox.textContent = "Las contraseñas no coinciden.";
      errorBox.classList.remove("d-none");
      return;
    }

    boton.disabled = true;
    spinner.classList.remove("d-none");

    try {
      await apiFetch("/register", {
        method: "POST",
        body: JSON.stringify({
          name,
          email,
          password,
          password_confirmation: passwordConfirmation,
        }),
        sinSpinner: true,
      });
      toastFlash("Cuenta creada. Inicia sesión.", "success");
      window.location.href = "../login/login.html";
    } catch (error) {
      errorBox.textContent = error.message;
      errorBox.classList.remove("d-none");
    } finally {
      boton.disabled = false;
      spinner.classList.add("d-none");
    }
  });
});
