// login.js — Lógica del login. Usa apiFetch y guardarToken de api.js.

/* global apiFetch, guardarToken */

// Espera a que el HTML esté cargado antes de tocar los elementos.
document.addEventListener("DOMContentLoaded", function () {

  const form = document.getElementById("loginForm");
  const errorBox = document.getElementById("loginError");

  // Al enviar el formulario:
  form.addEventListener("submit", async (evento) => {
    evento.preventDefault();        // no recargar la página
    errorBox.classList.add("d-none"); // ocultar error anterior

    // Datos escritos por el usuario
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
      // Fallo: mostrar el mensaje del backend
      errorBox.textContent = error.message;
      errorBox.classList.remove("d-none");
    }
  });

});
