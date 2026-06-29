// login.js — Lógica del login. Usa apiFetch y guardarToken de api.js.

/* global apiFetch, guardarToken, obtenerToken, mostrarToast, toastFlash, inicioSegunRol */

document.addEventListener("DOMContentLoaded", function () {
  if (obtenerToken()) {
    window.location.replace(inicioSegunRol(localStorage.getItem("rol_usuario") || ""));
    return;
  }

  const form = document.getElementById("loginForm");
  const errorBox = document.getElementById("loginError");
  const boton = document.getElementById("loginSubmit");
  const spinner = document.getElementById("loginSpinner");

  if (new URLSearchParams(window.location.search).get("error") === "google") {
    mostrarToast("No se pudo iniciar sesión con Google. Intenta de nuevo.", "error");
    window.history.replaceState({}, "", window.location.pathname);
  }

  form.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    if (!form.checkValidity()) {
      return;
    }
    errorBox.classList.add("d-none");

    boton.disabled = true;
    spinner.classList.remove("d-none");

    const email = document.getElementById("loginEmail").value;
    const password = document.getElementById("loginPassword").value;

    try {
      const data = await apiFetch("/login", {
        method: "POST",
        body: JSON.stringify({ email, password }),
        sinSpinner: true,
      });
      guardarToken(data.access_token);
      const usuario = await apiFetch("/user", { sinSpinner: true });
      const rol = usuario.rol ? usuario.rol.nombre_rol : "";
      if (rol) localStorage.setItem("rol_usuario", rol);
      // Cachea la foto para que el navbar la pinte ya en la primera pantalla tras iniciar sesión.
      localStorage.setItem("perfil_foto", usuario.foto_perfil || "");
      toastFlash("Bienvenido", "success");
      window.location.href = inicioSegunRol(rol);
    } catch (error) {
      errorBox.textContent = error.message;
      errorBox.classList.remove("d-none");
    } finally {
      boton.disabled = false;
      spinner.classList.add("d-none");
    }
  });

  const btnIrRegistro = document.getElementById("btnIrRegistro");
  if (btnIrRegistro) {
    btnIrRegistro.addEventListener("click", () => toggleAuth(true));
  }

  const btnIrLogin = document.getElementById("btnIrLogin");
  if (btnIrLogin) {
    btnIrLogin.addEventListener("click", () => toggleAuth(false));
  }
});

// Alterna entre login y registro (animación deslizante).
function toggleAuth(registrando) {
  document.getElementById("authSlider").classList.toggle("is-registering", registrando);
}
