// registro.js — Lógica del registro. Usa apiFetch de api.js.

/* global apiFetch, toastFlash, guardarToken */

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
      const data = await apiFetch("/register", {
        method: "POST",
        body: JSON.stringify({
          name,
          email,
          password,
          password_confirmation: passwordConfirmation,
        }),
        sinSpinner: true,
      });
      // Opción A: el registro ya devuelve token; guardamos la sesión y caemos directo en el muro de verificación.
      guardarToken(data.access_token);
      const usuario = data.user || {};
      if (usuario.rol) localStorage.setItem("rol_usuario", usuario.rol.nombre_rol);
      if (Array.isArray(usuario.permisos)) {
        localStorage.setItem("permisos_usuario", JSON.stringify(usuario.permisos));
      }
      toastFlash("Cuenta creada. Te enviamos un correo de verificación.", "success");
      window.location.href = "../verificar-correo/verificar-correo.html";
    } catch (error) {
      errorBox.textContent = error.message;
      errorBox.classList.remove("d-none");
    } finally {
      boton.disabled = false;
      spinner.classList.add("d-none");
    }
  });
});
