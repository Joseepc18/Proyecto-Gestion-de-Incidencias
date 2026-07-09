// perfil.js — Edición del perfil propio: nombre, correo, contraseña y foto.

// URL firmada de la foto guardada en el servidor (temporal); null si no tiene.
/* global apiFetch, aplicarMenuRol, mostrarToast, imageCompression, pintarAvatarNavbar, OPCIONES_COMPRESION, requerirSesion, cablearLogout */

let fotoActual = null;
// Foto nueva ya comprimida lista para subir; null si no se cambió.
let fotoSeleccionada = null;
// true si el usuario pidió quitar la foto sin reemplazarla.
let quitarFoto = false;
// objectURL del preview para liberarlo al reemplazarlo.
let previewUrl = null;
// Correo actual confirmado; sirve para saber si el usuario está intentando cambiarlo.
let emailOriginal = "";

document.addEventListener("DOMContentLoaded", async function () {
  const usuario = await requerirSesion();
  if (!usuario) return;
  aplicarMenuRol(usuario.rol ? usuario.rol.nombre_rol : "", usuario.permisos);

  document.getElementById("perfilNombre").value = usuario.name;
  document.getElementById("perfilEmail").value = usuario.email;
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

  document.getElementById("formPerfil").addEventListener("submit", guardarPerfil);

  iniciarDosFactor(usuario);
});

// Comprime la foto elegida y la muestra como preview.
async function procesarFoto() {
  const input = document.getElementById("perfilFoto");
  const file = input.files[0];
  input.value = "";
  if (!file) return;

  try {
    const comprimida = await imageCompression(file, OPCIONES_COMPRESION);
    fotoSeleccionada = new File([comprimida], "perfil.jpg", { type: "image/jpeg" });
    quitarFoto = false;
    mostrarAvatar(URL.createObjectURL(fotoSeleccionada));
  } catch {
    mostrarToast("No se pudo procesar la imagen", "error");
  }
}

// Marca la foto para borrarse (si había en el servidor) y vuelve al icono por defecto.
function quitarLaFoto() {
  fotoSeleccionada = null;
  quitarFoto = fotoActual !== null;
  mostrarAvatar(null);
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

// Guarda los cambios. Usa POST + _method=PUT porque PHP no parsea multipart en PUT.
async function guardarPerfil(e) {
  e.preventDefault();
  const btn = document.getElementById("btnGuardarPerfil");
  const spinner = document.getElementById("perfilSpinner");

  const emailNuevo = document.getElementById("perfilEmail").value.trim();
  const datos = new FormData();
  datos.append("_method", "PUT");
  datos.append("name", document.getElementById("perfilNombre").value.trim());
  datos.append("email", emailNuevo);

  const password = document.getElementById("perfilPassword").value;
  const passwordConfirm = document.getElementById("perfilPasswordConfirm").value;
  if (password) {
    if (password !== passwordConfirm) {
      mostrarToast("Las contraseñas no coinciden.", "error");
      return;
    }
    datos.append("password", password);
    datos.append("password_confirmation", passwordConfirm);
  }

  // Cambiar correo o contraseña es sensible: exige la contraseña actual (y el código 2FA si lo tiene).
  const cambiaEmail = emailNuevo !== emailOriginal;
  const sensible = cambiaEmail || Boolean(password);
  const currentPassword = document.getElementById("perfilCurrentPassword").value;
  if (sensible && !currentPassword) {
    mostrarToast("Ingresa tu contraseña actual para confirmar el cambio.", "error");
    return;
  }
  if (currentPassword) {
    datos.append("current_password", currentPassword);
  }
  const dosFactorCode = document.getElementById("perfilDosFactorCode").value.trim();
  if (dosFactorCode) {
    datos.append("two_factor_code", dosFactorCode);
  }

  if (fotoSeleccionada) {
    datos.append("foto", fotoSeleccionada);
  } else if (quitarFoto) {
    datos.append("quitar_foto", "1");
  }

  btn.disabled = true;
  spinner.classList.remove("d-none");

  try {
    const usuario = await apiFetch("/perfil", { method: "POST", body: datos });

    fotoActual = usuario.foto_perfil || null;
    fotoSeleccionada = null;
    quitarFoto = false;
    document.getElementById("perfilPassword").value = "";
    document.getElementById("perfilPasswordConfirm").value = "";
    document.getElementById("perfilCurrentPassword").value = "";
    document.getElementById("perfilDosFactorCode").value = "";
    mostrarAvatar(fotoActual);

    // El correo no cambia al instante: reflejamos el confirmado y mostramos el pendiente si lo hay.
    emailOriginal = usuario.email;
    document.getElementById("perfilEmail").value = usuario.email;
    mostrarAvisoPendiente(usuario.email_pendiente || null);

    document.getElementById("nombreUsuario").textContent = usuario.name;
    cachearFotoNavbar(fotoActual);

    if (cambiaEmail) {
      mostrarToast(
        "Te enviamos un enlace a tu nuevo correo. El cambio se aplicará al confirmarlo.",
        "success",
      );
    } else {
      mostrarToast("Perfil actualizado", "success");
    }
  } catch (error) {
    mostrarToast(error.message, "error");
  } finally {
    btn.disabled = false;
    spinner.classList.add("d-none");
  }
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
  document.getElementById("dfConfirmForm").addEventListener("submit", confirmarDosFactor);
  document.getElementById("dfDisableForm").addEventListener("submit", desactivarDosFactor);
}

// Pinta el estado (activa/inactiva), muestra la acción que toca y oculta los sub-formularios.
function pintarEstadoDosFactor(activa) {
  const badge = document.getElementById("dfEstado");
  badge.textContent = activa ? "Activa" : "Inactiva";
  badge.className = "badge " + (activa ? "bg-success" : "bg-secondary");

  document.getElementById("btnDfActivar").classList.toggle("d-none", activa);
  document.getElementById("btnDfDesactivar").classList.toggle("d-none", !activa);
  // El campo de código 2FA del formulario de cuenta solo aplica si el 2FA está activo.
  document.getElementById("perfilDosFactorGrupo").classList.toggle("d-none", !activa);
  // El aviso de obligatoriedad solo aplica a roles privilegiados sin 2FA activo.
  document
    .getElementById("dfRequeridoAviso")
    .classList.toggle("d-none", !(esRolPrivilegiado && !activa));

  document.getElementById("dfSetup").classList.add("d-none");
  document.getElementById("dfDisableForm").classList.add("d-none");
  document.getElementById("dfConfirmCode").value = "";
  document.getElementById("dfDisableCode").value = "";
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
