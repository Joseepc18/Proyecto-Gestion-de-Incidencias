// restablecer.js — Define la nueva contraseña usando el token del correo. Usa apiFetch de api.js.

/* global apiFetch, toastFlash */

document.addEventListener("DOMContentLoaded", function () {
  // El token y el email vienen en la URL del enlace del correo.
  const params = new URLSearchParams(window.location.search);
  const token = params.get("token") || "";
  const email = params.get("email") || "";

  const form = document.getElementById("restablecerForm");
  const errorBox = document.getElementById("restablecerError");
  const boton = document.getElementById("restablecerSubmit");
  const spinner = document.getElementById("restablecerSpinner");

  // Sin token o email en el enlace no se puede continuar.
  if (!token || !email) {
    errorBox.textContent = "El enlace no es válido. Solicita uno nuevo.";
    errorBox.classList.remove("d-none");
    boton.disabled = true;
    return;
  }

  form.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    if (!form.checkValidity()) {
      form.classList.add("was-validated");
      return;
    }
    errorBox.classList.add("d-none");

    const password = document.getElementById("nuevaPassword").value;
    const passwordConfirmation = document.getElementById("confirmarPassword").value;

    if (password !== passwordConfirmation) {
      errorBox.textContent = "Las contraseñas no coinciden.";
      errorBox.classList.remove("d-none");
      return;
    }

    boton.disabled = true;
    spinner.classList.remove("d-none");

    try {
      await apiFetch("/password/restablecer", {
        method: "POST",
        body: JSON.stringify({
          token,
          email,
          password,
          password_confirmation: passwordConfirmation,
        }),
        sinSpinner: true,
      });
      toastFlash("Tu contraseña fue restablecida. Inicia sesión.", "success");
      window.location.href = "login.html";
    } catch (error) {
      errorBox.textContent = error.message;
      errorBox.classList.remove("d-none");
    } finally {
      boton.disabled = false;
      spinner.classList.add("d-none");
    }
  });
});
