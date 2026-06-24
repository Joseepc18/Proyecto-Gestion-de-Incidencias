// login.js — Lógica del login. Usa apiFetch y guardarToken de api.js.

/* global apiFetch, guardarToken, mostrarToast, toastFlash, inicioSegunRol */

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("loginForm");
  const errorBox = document.getElementById("loginError");
  const boton = document.getElementById("loginSubmit");
  const spinner = document.getElementById("loginSpinner");

  // Si Google rebotó al usuario con un error, avisarle.
  if (new URLSearchParams(window.location.search).get("error") === "google") {
    mostrarToast("No se pudo iniciar sesión con Google. Intenta de nuevo.", "error");
    window.history.replaceState({}, "", window.location.pathname);
  }

  form.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    if (!form.checkValidity()) {
      return; // form inválido: no enviar
    }
    errorBox.classList.add("d-none"); // ocultar error anterior

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
        sinSpinner: true,
      });
      // Éxito: guardar token y entrar a la pantalla de arranque según el rol
      guardarToken(data.access_token);
      const usuario = await apiFetch("/user", { sinSpinner: true });
      toastFlash("Bienvenido de nuevo", "success");
      window.location.href = inicioSegunRol(usuario.rol ? usuario.rol.nombre_rol : "");
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

// Alterna entre login y registro (animación deslizante). Se llama desde el HTML.
// eslint-disable-next-line no-unused-vars
function toggleAuth(registrando) {
  document.getElementById("authSlider").classList.toggle("is-registering", registrando);
}
