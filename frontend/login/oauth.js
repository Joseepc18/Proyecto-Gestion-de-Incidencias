// Callback de Google: el backend devuelve el token en el fragmento; api.js/toast.js van antes en el bundle.

/* global guardarToken, toastFlash, apiFetch, inicioSegunRol */

document.addEventListener("DOMContentLoaded", function () {
  // El token viene en el hash (#token=...&nuevo=1 si es la 1ª vez).
  const params = new URLSearchParams(window.location.hash.substring(1));
  const token = params.get("token");
  const esNuevo = params.get("nuevo") === "1";

  if (token) {
    guardarToken(token);
    if (typeof toastFlash === "function") {
      toastFlash(
        esNuevo
          ? "¡Bienvenido a Gestión de Incidencias! Tu cuenta quedó creada con Google."
          : "Bienvenido",
        "success",
      );
    }
    // Entra a la pantalla de arranque según el rol (normal → mis incidencias).
    apiFetch("/user", { sinSpinner: true })
      .then(function (usuario) {
        const rol = usuario.rol ? usuario.rol.nombre_rol : "";
        window.location.replace(inicioSegunRol(rol));
      })
      .catch(function () {
        window.location.replace("../inicio/inicio.html");
      });
  } else {
    window.location.replace("login.html?error=google");
  }
});
