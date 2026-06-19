// registro.js — Lógica del registro. Usa apiFetch de api.js.

/* global apiFetch, toastFlash */

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("registroForm");
  const errorBox = document.getElementById("registroError");
  const boton = document.getElementById("registroSubmit");
  const spinner = document.getElementById("registroSpinner");

  form.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    if (!form.checkValidity()) {
      return; // form inválido: no enviar
    }
    errorBox.classList.add("d-none"); // ocultar error anterior

    // Datos del formulario (trim quita espacios sobrantes)
    const name = document.getElementById("registerName").value.trim();
    const email = document.getElementById("registerEmail").value.trim();
    const password = document.getElementById("registerPassword").value;
    const passwordConfirmation = document.getElementById("registerPasswordConfirmation").value;

    // Las contraseñas deben coincidir
    if (password !== passwordConfirmation) {
      errorBox.textContent = "Las contraseñas no coinciden.";
      errorBox.classList.remove("d-none");
      return;
    }

    // Bloquear botón y mostrar spinner mientras procesa
    boton.disabled = true;
    spinner.classList.remove("d-none");

    try {
      // Registrar
      await apiFetch("/register", {
        method: "POST",
        body: JSON.stringify({
          name,
          email,
          password,
          password_confirmation: passwordConfirmation,
        }),
      });
      // Cuenta creada: ir al login
      toastFlash("Cuenta creada. Inicia sesión.", "success");
      window.location.href = "../login/login.html";
    } catch (error) {
      // Mostrar error del backend
      errorBox.textContent = error.message;
      errorBox.classList.remove("d-none");
    } finally {
      // Reactivar botón y ocultar spinner pase lo que pase
      boton.disabled = false;
      spinner.classList.add("d-none");
    }
  });
});
