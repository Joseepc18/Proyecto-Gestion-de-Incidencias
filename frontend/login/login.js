// login.js — Lógica del login. Usa apiFetch y guardarToken de api.js.

/* global apiFetch, guardarToken, obtenerToken, eliminarToken, mostrarToast, toastFlash, inicioSegunRol */

document.addEventListener("DOMContentLoaded", async function () {
  // Mensajes que vienen del enlace de verificación de correo (?verificado=1 o ?error=verificacion).
  const paramsUrl = new URLSearchParams(window.location.search);
  const avisoVerif =
    paramsUrl.get("verificado") === "1"
      ? ["Tu correo fue verificado. Ya puedes reportar incidencias.", "success"]
      : paramsUrl.get("error") === "verificacion"
        ? ["El enlace de verificación no es válido o ya expiró.", "error"]
        : null;
  if (avisoVerif) {
    window.history.replaceState({}, "", window.location.pathname);
    // Con sesión activa se redirige enseguida: el flash sobrevive a la navegación.
    if (obtenerToken()) toastFlash(avisoVerif[0], avisoVerif[1]);
    else mostrarToast(avisoVerif[0], avisoVerif[1]);
  }

  // Valida el token contra el backend antes de redirigir, para evitar un "flash" con uno inválido
  if (obtenerToken()) {
    try {
      const usuario = await apiFetch("/user", { sinSpinner: true });
      const rol = usuario.rol ? usuario.rol.nombre_rol : "";
      if (rol) localStorage.setItem("rol_usuario", rol);
      if (Array.isArray(usuario.permisos)) {
        localStorage.setItem("permisos_usuario", JSON.stringify(usuario.permisos));
      }
      localStorage.setItem("perfil_foto", usuario.foto_perfil || "");
      window.location.replace(inicioSegunRol(rol));
      return;
    } catch {
      // Token inválido/expirado: se limpia y se revela el formulario de login.
      eliminarToken();
      document.documentElement.classList.remove("verificando-sesion");
    }
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
      // /login ya devuelve el user con su rol: lo usamos y evitamos un segundo request a /user.
      const usuario = data.user || {};
      const rol = usuario.rol ? usuario.rol.nombre_rol : "";
      if (rol) localStorage.setItem("rol_usuario", rol);
      if (Array.isArray(usuario.permisos)) {
        localStorage.setItem("permisos_usuario", JSON.stringify(usuario.permisos));
      }
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
