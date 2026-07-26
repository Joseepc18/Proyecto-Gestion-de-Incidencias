// login.js — Lógica del login. Usa apiFetch y guardarToken de api.js.

/* global apiFetch, guardarToken, obtenerToken, eliminarToken, mostrarToast, toastFlash, inicioSegunRol */

// Token efímero del reto 2FA: lo devuelve /login cuando el usuario tiene segundo factor.
let challengeToken = null;

document.addEventListener("DOMContentLoaded", async function () {
  // Mensajes que vienen de los enlaces de correo (verificación y cambio de correo).
  const paramsUrl = new URLSearchParams(window.location.search);
  const avisosCorreo = {
    "verificado=1": ["Tu correo fue verificado. Ya puedes reportar incidencias.", "success"],
    "error=verificacion": ["El enlace de verificación no es válido o ya expiró.", "error"],
    "correo_cambiado=1": ["Tu correo fue cambiado. Inicia sesión con el nuevo.", "success"],
    "error=cambio_correo": ["El enlace para cambiar el correo no es válido o ya expiró.", "error"],
    "error=cambio_correo_ocupado": ["Ese correo ya está en uso por otra cuenta.", "error"],
  };
  const avisoVerif =
    avisosCorreo[`verificado=${paramsUrl.get("verificado")}`] ||
    avisosCorreo[`correo_cambiado=${paramsUrl.get("correo_cambiado")}`] ||
    avisosCorreo[`error=${paramsUrl.get("error")}`] ||
    null;
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
      // Rol privilegiado sin 2FA: lo mandamos a configurarlo antes de que choque con un 403 en el panel.
      if (usuario.two_factor_required) {
        toastFlash("Activa la verificación en dos pasos para gestionar el sistema.", "warning");
        window.location.replace("../perfil/perfil.html");
        return;
      }
      // Ciudadano sin verificar: al muro de verificación, no a su inicio.
      if (rol === "normal" && usuario.email_verificado === false) {
        window.location.replace("../verificar-correo/verificar-correo.html");
        return;
      }
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

  // Mensajes de vuelta del login con Google (todos llegan por ?error=...).
  const erroresGoogle = {
    google: "No se pudo iniciar sesión con Google. Intenta de nuevo.",
    google_email: "Tu cuenta de Google no tiene el correo verificado.",
    google_privilegiado: "Esa cuenta debe iniciar sesión con correo y contraseña.",
    google_state: "La conexión con Google expiró o no es válida. Intenta de nuevo.",
    google_suspendido:
      "Esa cuenta está suspendida. Puedes pedir su reactivación desde “¿Cuenta suspendida?”.",
  };
  const errGoogle = paramsUrl.get("error");
  if (erroresGoogle[errGoogle]) {
    mostrarToast(erroresGoogle[errGoogle], "error");
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
      // Con 2FA activo el backend no emite token todavía: pide el segundo factor.
      if (data.two_factor) {
        challengeToken = data.challenge_token;
        mostrarReto();
        return;
      }
      entrarConSesion(data);
    } catch (error) {
      errorBox.textContent = error.message;
      errorBox.classList.remove("d-none");
    } finally {
      boton.disabled = false;
      spinner.classList.add("d-none");
    }
  });

  // Segundo factor: envía el challenge_token + el código y, si es válido, entra.
  const challengeForm = document.getElementById("challengeForm");
  const challengeError = document.getElementById("challengeError");
  const challengeBoton = document.getElementById("challengeSubmit");
  const challengeSpinner = document.getElementById("challengeSpinner");

  challengeForm.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    if (!challengeForm.checkValidity()) {
      return;
    }
    challengeError.classList.add("d-none");
    challengeBoton.disabled = true;
    challengeSpinner.classList.remove("d-none");

    try {
      const data = await apiFetch("/2fa/challenge", {
        method: "POST",
        body: JSON.stringify({
          challenge_token: challengeToken,
          code: document.getElementById("challengeCode").value.trim(),
        }),
        sinSpinner: true,
      });
      entrarConSesion(data);
    } catch (error) {
      challengeError.textContent = error.message;
      challengeError.classList.remove("d-none");
    } finally {
      challengeBoton.disabled = false;
      challengeSpinner.classList.add("d-none");
    }
  });

  // "Volver": descarta el reto y regresa al formulario de login.
  document.getElementById("btnVolverLogin").addEventListener("click", () => {
    challengeToken = null;
    document.getElementById("challengeCode").value = "";
    challengeError.classList.add("d-none");
    challengeForm.classList.add("d-none");
    form.classList.remove("d-none");
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

// Guarda el token, cachea rol/permisos/foto y redirige al inicio del rol. Compartido por login y reto 2FA.
function entrarConSesion(data) {
  guardarToken(data.access_token);
  // La respuesta ya trae el user con su rol: lo usamos y evitamos un segundo request a /user.
  const usuario = data.user || {};
  const rol = usuario.rol ? usuario.rol.nombre_rol : "";
  if (rol) localStorage.setItem("rol_usuario", rol);
  if (Array.isArray(usuario.permisos)) {
    localStorage.setItem("permisos_usuario", JSON.stringify(usuario.permisos));
  }
  // Cachea la foto para que el navbar la pinte ya en la primera pantalla tras iniciar sesión.
  localStorage.setItem("perfil_foto", usuario.foto_perfil || "");
  // Rol privilegiado sin 2FA: lo encaminamos a configurarlo antes que al panel (que daría 403).
  if (usuario.two_factor_required) {
    toastFlash("Activa la verificación en dos pasos para gestionar el sistema.", "warning");
    window.location.href = "../perfil/perfil.html";
    return;
  }
  // Ciudadano sin verificar: cae en el muro de verificación (Opción A), no en su inicio.
  if (rol === "normal" && usuario.email_verificado === false) {
    window.location.href = "../verificar-correo/verificar-correo.html";
    return;
  }
  toastFlash("Bienvenido", "success");
  window.location.href = inicioSegunRol(rol);
}

// Oculta el formulario de login y revela el del segundo factor.
function mostrarReto() {
  document.getElementById("loginForm").classList.add("d-none");
  const challengeForm = document.getElementById("challengeForm");
  challengeForm.classList.remove("d-none");
  document.getElementById("challengeCode").focus();
}

// Alterna entre login y registro (animación deslizante).
function toggleAuth(registrando) {
  document.getElementById("authSlider").classList.toggle("is-registering", registrando);
}
