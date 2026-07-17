// perfil.js — Edición del perfil propio: la foto se guarda sola; nombre, correo y contraseña por modal.

// URL firmada de la foto guardada en el servidor (temporal); null si no tiene.
/* global apiFetch, aplicarMenuRol, mostrarToast, imageCompression, pintarAvatarNavbar, OPCIONES_COMPRESION, requerirSesion, cablearLogout, abrirModal */

let fotoActual = null;
// objectURL del preview para liberarlo al reemplazarlo.
let previewUrl = null;
// Nombre confirmado; los modales lo reenvían sin cambio (el backend lo exige).
let nombreOriginal = "";
// Correo confirmado; sirve de valor "sin cambio" para los modales.
let emailOriginal = "";
// ¿El usuario tiene 2FA activo? Los modales piden el código solo si es true.
let dosFactorActivo = false;

document.addEventListener("DOMContentLoaded", async function () {
  const usuario = await requerirSesion();
  if (!usuario) return;
  aplicarMenuRol(usuario.rol ? usuario.rol.nombre_rol : "", usuario.permisos);

  document.getElementById("perfilNombre").value = usuario.name;
  document.getElementById("perfilEmail").value = usuario.email;
  nombreOriginal = usuario.name;
  emailOriginal = usuario.email;
  mostrarAvisoPendiente(usuario.email_pendiente || null);

  fotoActual = usuario.foto_perfil || null;
  cachearFotoNavbar(fotoActual);
  mostrarAvatar(fotoActual);

  cablearLogout();

  const inputFoto = document.getElementById("perfilFoto");
  document.getElementById("btnCambiarFoto").addEventListener("click", function () {
    inputFoto.click();
  });
  inputFoto.addEventListener("change", procesarFoto);
  document.getElementById("btnQuitarFoto").addEventListener("click", quitarLaFoto);

  document.getElementById("btnCambiarNombre").addEventListener("click", abrirModalNombre);
  document.getElementById("btnCambiarCorreo").addEventListener("click", abrirModalCorreo);
  document.getElementById("btnCambiarPassword").addEventListener("click", abrirModalPassword);

  iniciarDosFactor(usuario);
});

// Comprime la foto elegida y la sube al momento; si falla, revierte al avatar guardado.
async function procesarFoto() {
  const input = document.getElementById("perfilFoto");
  const file = input.files[0];
  input.value = "";
  if (!file) return;

  let foto;
  try {
    const comprimida = await imageCompression(file, OPCIONES_COMPRESION);
    foto = new File([comprimida], "perfil.jpg", { type: "image/jpeg" });
  } catch {
    mostrarToast("No se pudo procesar la imagen", "error");
    return;
  }

  await guardarFoto({ foto }, "Foto actualizada");
}

// Quita la foto en el servidor al momento (solo si había); si falla, revierte.
async function quitarLaFoto() {
  if (fotoActual === null) return;
  await guardarFoto({ quitar_foto: "1" }, "Foto eliminada");
}

// Sube el estado de la foto a /perfil (nombre/correo sin cambio → el backend no pide contraseña).
async function guardarFoto(campos, mensajeOk) {
  avatarCargando(true);
  const datos = new FormData();
  datos.append("_method", "PUT");
  datos.append("name", nombreOriginal);
  datos.append("email", emailOriginal);
  Object.entries(campos).forEach(([clave, valor]) => datos.append(clave, valor));

  try {
    const usuario = await apiFetch("/perfil", { method: "POST", body: datos });
    fotoActual = usuario.foto_perfil || null;
    cachearFotoNavbar(fotoActual);
    mostrarAvatar(fotoActual);
    mostrarToast(mensajeOk, "success");
  } catch (error) {
    mostrarToast(error.message, "error");
    mostrarAvatar(fotoActual);
  } finally {
    avatarCargando(false);
  }
}

// Muestra un spinner en el avatar mientras la foto se sube y bloquea sus botones.
function avatarCargando(cargando) {
  document.getElementById("btnCambiarFoto").disabled = cargando;
  document.getElementById("btnQuitarFoto").disabled = cargando;
  if (!cargando) return;
  const spinner = document.createElement("span");
  spinner.className = "spinner-border";
  spinner.setAttribute("role", "status");
  spinner.setAttribute("aria-label", "Subiendo foto");
  document.getElementById("perfilAvatar").replaceChildren(spinner);
}

// Pinta el avatar de la página: imagen si hay src, o el icono por defecto. Muestra "Quitar" solo si hay foto.
function mostrarAvatar(src) {
  const cont = document.getElementById("perfilAvatar");
  const btnQuitar = document.getElementById("btnQuitarFoto");

  if (previewUrl) {
    URL.revokeObjectURL(previewUrl);
    previewUrl = null;
  }

  if (src) {
    if (src.startsWith("blob:")) previewUrl = src;
    const img = document.createElement("img");
    img.loading = "lazy";
    img.src = src;
    img.alt = "Foto de perfil";
    cont.replaceChildren(img);
    btnQuitar.classList.remove("d-none");
  } else {
    const icono = document.createElement("i");
    icono.className = "bi bi-person";
    icono.setAttribute("aria-hidden", "true");
    cont.replaceChildren(icono);
    btnQuitar.classList.add("d-none");
  }
}

// Modal chico para cambiar el nombre: un solo campo, sin contraseña ni 2FA (no es sensible).
function abrirModalNombre() {
  const cuerpoHtml =
    '<div class="mb-3">' +
    '<label class="form-label" for="mNuevoNombre">Nuevo nombre</label>' +
    '<input class="form-control" type="text" id="mNuevoNombre" minlength="3" maxlength="100" autocomplete="name" required />' +
    "</div>";

  return abrirModal({
    titulo: "Cambiar nombre",
    cuerpoHtml,
    textoConfirmar: "Guardar nombre",
    alConfirmar: async function (form) {
      const datos = new FormData();
      datos.append("_method", "PUT");
      datos.append("name", form.querySelector("#mNuevoNombre").value.trim());
      datos.append("email", emailOriginal);

      const usuario = await apiFetch("/perfil", { method: "POST", body: datos });

      nombreOriginal = usuario.name;
      document.getElementById("perfilNombre").value = usuario.name;
      document.getElementById("nombreUsuario").textContent = usuario.name;
      mostrarToast("Nombre actualizado", "success");
    },
  });
}

// Campo de contraseña con botón mostrar/ocultar (los cablea password.js por delegación).
function campoPasswordHtml(id, etiqueta, atributos, ayuda) {
  return (
    '<div class="mb-3">' +
    '<label class="form-label" for="' +
    id +
    '">' +
    etiqueta +
    "</label>" +
    '<div class="input-group">' +
    '<input class="form-control" type="password" id="' +
    id +
    '" ' +
    (atributos || "") +
    " />" +
    '<button class="btn toggle-password" type="button" data-target="' +
    id +
    '" aria-label="Mostrar contraseña" aria-pressed="false">' +
    '<i class="bi bi-eye" aria-hidden="true"></i></button>' +
    "</div>" +
    (ayuda ? '<div class="form-text">' + ayuda + "</div>" : "") +
    "</div>"
  );
}

// Campo de código 2FA; vacío si el usuario no tiene 2FA activo (entonces el backend no lo exige).
function campoDosFactorHtml(id) {
  if (!dosFactorActivo) return "";
  return (
    '<div class="mb-3">' +
    '<label class="form-label" for="' +
    id +
    '">Código de verificación (2FA)</label>' +
    '<input class="form-control" type="text" id="' +
    id +
    '" inputmode="numeric" autocomplete="one-time-code" required />' +
    '<div class="form-text">Ingresa el código de tu app de autenticación.</div>' +
    "</div>"
  );
}

// Modal chico para cambiar el correo: nuevo correo + contraseña actual (+ código 2FA si aplica).
function abrirModalCorreo() {
  const cuerpoHtml =
    '<div class="mb-3">' +
    '<label class="form-label" for="mNuevoCorreo">Nuevo correo</label>' +
    '<input class="form-control" type="email" id="mNuevoCorreo" autocomplete="email" required />' +
    "</div>" +
    campoPasswordHtml(
      "mCorreoActual",
      "Contraseña actual",
      'autocomplete="current-password" required',
    ) +
    campoDosFactorHtml("mCorreoCodigo");

  return abrirModal({
    titulo: "Cambiar correo",
    cuerpoHtml,
    textoConfirmar: "Enviar enlace",
    alConfirmar: async function (form) {
      const datos = new FormData();
      datos.append("_method", "PUT");
      datos.append("name", nombreOriginal);
      datos.append("email", form.querySelector("#mNuevoCorreo").value.trim());
      datos.append("current_password", form.querySelector("#mCorreoActual").value);
      const codigo = form.querySelector("#mCorreoCodigo");
      if (codigo) datos.append("two_factor_code", codigo.value.trim());

      const usuario = await apiFetch("/perfil", { method: "POST", body: datos });

      // El correo es diferido: el confirmado sigue igual y el nuevo queda como pendiente.
      emailOriginal = usuario.email;
      document.getElementById("perfilEmail").value = usuario.email;
      mostrarAvisoPendiente(usuario.email_pendiente || null);
      mostrarToast(
        "Te enviamos un enlace a tu nuevo correo. El cambio se aplicará al confirmarlo.",
        "success",
      );
    },
  });
}

// Modal chico para cambiar la contraseña: nueva + confirmar + contraseña actual (+ código 2FA si aplica).
function abrirModalPassword() {
  const cuerpoHtml =
    campoPasswordHtml(
      "mNuevaPass",
      "Nueva contraseña",
      'minlength="8" autocomplete="new-password" required',
      "Mínimo 8 caracteres, con letras y números.",
    ) +
    campoPasswordHtml(
      "mNuevaPass2",
      "Confirmar nueva contraseña",
      'minlength="8" autocomplete="new-password" required',
    ) +
    campoPasswordHtml(
      "mPassActual",
      "Contraseña actual",
      'autocomplete="current-password" required',
    ) +
    campoDosFactorHtml("mPassCodigo");

  return abrirModal({
    titulo: "Cambiar contraseña",
    cuerpoHtml,
    textoConfirmar: "Guardar contraseña",
    alConfirmar: async function (form) {
      const password = form.querySelector("#mNuevaPass").value;
      const passwordConfirm = form.querySelector("#mNuevaPass2").value;
      if (password !== passwordConfirm) {
        throw new Error("Las contraseñas no coinciden.");
      }

      const datos = new FormData();
      datos.append("_method", "PUT");
      datos.append("name", nombreOriginal);
      datos.append("email", emailOriginal);
      datos.append("password", password);
      datos.append("password_confirmation", passwordConfirm);
      datos.append("current_password", form.querySelector("#mPassActual").value);
      const codigo = form.querySelector("#mPassCodigo");
      if (codigo) datos.append("two_factor_code", codigo.value.trim());

      await apiFetch("/perfil", { method: "POST", body: datos });
      mostrarToast("Contraseña actualizada", "success");
    },
  });
}

// ¿El rol exige 2FA? (admin/super_admin); se usa para reponer el aviso al desactivar.
let esRolPrivilegiado = false;

// Cablea el panel de verificación en dos pasos con el estado que trae /user.
function iniciarDosFactor(usuario) {
  esRolPrivilegiado = ["admin", "super_admin"].includes(usuario.rol ? usuario.rol.nombre_rol : "");
  pintarEstadoDosFactor(usuario.two_factor_enabled === true);

  document.getElementById("btnDfActivar").addEventListener("click", activarDosFactor);
  document.getElementById("btnDfDesactivar").addEventListener("click", () => {
    document.getElementById("dfDisableForm").classList.remove("d-none");
    document.getElementById("dfDisableCode").focus();
  });
  document
    .getElementById("btnDfCancelar")
    .addEventListener("click", () => pintarEstadoDosFactor(false));
  document
    .getElementById("btnDfDisableCancelar")
    .addEventListener("click", () => pintarEstadoDosFactor(true));
  document.getElementById("btnDfCopiar").addEventListener("click", copiarCodigosRecuperacion);
  document.getElementById("btnDfDescargar").addEventListener("click", descargarCodigosRecuperacion);
  // Sin confirmar que se guardaron los códigos no se puede terminar la activación.
  document.getElementById("dfGuardadosCheck").addEventListener("change", (e) => {
    document.getElementById("btnDfConfirmar").disabled = !e.target.checked;
  });
  document.getElementById("dfConfirmForm").addEventListener("submit", confirmarDosFactor);
  document.getElementById("dfDisableForm").addEventListener("submit", desactivarDosFactor);
}

// Pinta el estado (activa/inactiva), muestra la acción que toca y oculta los sub-formularios.
function pintarEstadoDosFactor(activa) {
  dosFactorActivo = activa;
  const badge = document.getElementById("dfEstado");
  badge.textContent = activa ? "Activa" : "Inactiva";
  badge.className = "badge " + (activa ? "bg-success" : "bg-secondary");

  document.getElementById("btnDfActivar").classList.toggle("d-none", activa);
  document.getElementById("btnDfDesactivar").classList.toggle("d-none", !activa);
  // El aviso de obligatoriedad solo aplica a roles privilegiados sin 2FA activo.
  document
    .getElementById("dfRequeridoAviso")
    .classList.toggle("d-none", !(esRolPrivilegiado && !activa));

  document.getElementById("dfSetup").classList.add("d-none");
  document.getElementById("dfDisableForm").classList.add("d-none");
  document.getElementById("dfConfirmCode").value = "";
  document.getElementById("dfDisableCode").value = "";
  reiniciarGuardadoCodigos();
}

// Vuelve el flujo de activación a "códigos sin guardar": desmarca y bloquea confirmar.
function reiniciarGuardadoCodigos() {
  document.getElementById("dfGuardadosCheck").checked = false;
  document.getElementById("btnDfConfirmar").disabled = true;
}

// Junta los códigos de recuperación de la lista en texto (uno por línea).
function textoCodigosRecuperacion() {
  return Array.from(document.querySelectorAll("#dfRecovery li"))
    .map((li) => li.textContent)
    .join("\n");
}

// Copia los códigos al portapapeles; son sensibles, no salen a ningún otro lado.
async function copiarCodigosRecuperacion() {
  try {
    await navigator.clipboard.writeText(textoCodigosRecuperacion());
    mostrarToast("Códigos copiados.", "success");
  } catch {
    mostrarToast("No se pudieron copiar los códigos.", "error");
  }
}

// Descarga los códigos como .txt local mediante un Blob, sin enviarlos al servidor.
function descargarCodigosRecuperacion() {
  const blob = new Blob([textoCodigosRecuperacion() + "\n"], { type: "text/plain" });
  const url = URL.createObjectURL(blob);
  const enlace = document.createElement("a");
  enlace.href = url;
  enlace.download = "codigos-recuperacion.txt";
  enlace.click();
  URL.revokeObjectURL(url);
}

// Paso 1: genera el secreto y muestra el QR + los códigos de recuperación.
async function activarDosFactor() {
  const btn = document.getElementById("btnDfActivar");
  btn.disabled = true;
  try {
    const data = await apiFetch("/2fa/enable", { method: "POST" });
    // El SVG lo genera nuestro backend (BaconQrCode): se inserta tal cual.
    document.getElementById("dfQr").innerHTML = data.svg || "";

    const lista = document.getElementById("dfRecovery");
    lista.replaceChildren();
    (data.recovery_codes || []).forEach((codigo) => {
      const li = document.createElement("li");
      li.textContent = codigo;
      lista.appendChild(li);
    });

    reiniciarGuardadoCodigos();
    btn.classList.add("d-none");
    document.getElementById("dfSetup").classList.remove("d-none");
    document.getElementById("dfConfirmCode").focus();
  } catch (error) {
    mostrarToast(error.message, "error");
  } finally {
    btn.disabled = false;
  }
}

// Paso 2: confirma con el primer código de la app; recién ahí queda activa.
async function confirmarDosFactor(e) {
  e.preventDefault();
  const btn = e.target.querySelector('button[type="submit"]');
  btn.disabled = true;
  try {
    await apiFetch("/2fa/confirm", {
      method: "POST",
      body: JSON.stringify({ code: document.getElementById("dfConfirmCode").value.trim() }),
    });
    pintarEstadoDosFactor(true);
    mostrarToast("Verificación en dos pasos activada.", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  } finally {
    btn.disabled = false;
  }
}

// Desactiva el 2FA exigiendo un código válido (un token robado no basta).
async function desactivarDosFactor(e) {
  e.preventDefault();
  const btn = e.target.querySelector('button[type="submit"]');
  btn.disabled = true;
  try {
    await apiFetch("/2fa", {
      method: "DELETE",
      body: JSON.stringify({ code: document.getElementById("dfDisableCode").value.trim() }),
    });
    pintarEstadoDosFactor(false);
    mostrarToast("Verificación en dos pasos desactivada.", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  } finally {
    btn.disabled = false;
  }
}

// Muestra u oculta el aviso de cambio de correo pendiente de confirmar.
function mostrarAvisoPendiente(email) {
  const aviso = document.getElementById("perfilPendienteAviso");
  if (email) {
    document.getElementById("perfilPendienteEmail").textContent = email;
    aviso.hidden = false;
  } else {
    aviso.hidden = true;
  }
}

// Guarda la foto en caché y repinta el avatar del navbar (compartido entre páginas).
function cachearFotoNavbar(foto) {
  if (foto) {
    localStorage.setItem("perfil_foto", foto);
  } else {
    localStorage.removeItem("perfil_foto");
  }
  if (typeof pintarAvatarNavbar === "function") {
    pintarAvatarNavbar(foto || "");
  }
}
