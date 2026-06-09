// login.js — Lógica del login. Usa apiFetch y guardarToken de api.js.

/* global apiFetch, guardarToken */

document.addEventListener("DOMContentLoaded", function () {

  const form = document.getElementById("loginForm");
  const errorBox = document.getElementById("loginError");
  const boton = document.getElementById("loginSubmit");
  const spinner = document.getElementById("loginSpinner");

  form.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    if (!form.checkValidity()) {
      return;                          // form inválido: no enviar
    }
    errorBox.classList.add("d-none");  // ocultar error anterior

    // Bloquear botón y mostrar spinner mientras procesa
    boton.disabled = true;
    spinner.classList.remove("d-none");

    // Datos del formulario
    const email = document.getElementById("loginEmail").value;
    const password = document.getElementById("loginPassword").value;

    try {
      // Pedir login al backend
      const data = await apiFetch("/login", {
        method: "POST",
        body: JSON.stringify({ email, password }),
      });
      // Éxito: guardar token y entrar al dashboard
      guardarToken(data.access_token);
      window.location.href = "../dashboard/dashboard.html";
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
