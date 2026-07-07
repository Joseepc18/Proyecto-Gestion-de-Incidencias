// perfil.js — Edición del perfil propio: nombre, correo, contraseña y foto.

// Ruta de la foto guardada en el servidor (relativa); null si no tiene.
/* global apiFetch, aplicarMenuRol, mostrarToast, imageCompression, pintarAvatarNavbar, OPCIONES_COMPRESION, requerirSesion, cablearLogout */

let fotoActual = null;
// Foto nueva ya comprimida lista para subir; null si no se cambió.
let fotoSeleccionada = null;
// true si el usuario pidió quitar la foto sin reemplazarla.
let quitarFoto = false;
// objectURL del preview para liberarlo al reemplazarlo.
let previewUrl = null;

document.addEventListener("DOMContentLoaded", async function () {
  const usuario = await requerirSesion();
  if (!usuario) return;
  aplicarMenuRol(usuario.rol ? usuario.rol.nombre_rol : "", usuario.permisos);

  document.getElementById("perfilNombre").value = usuario.name;
  document.getElementById("perfilEmail").value = usuario.email;

  fotoActual = usuario.foto_perfil || null;
  cachearFotoNavbar(fotoActual);
  mostrarAvatar(fotoActual ? "/storage/" + encodeURIComponent(fotoActual) : null);

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

  const datos = new FormData();
  datos.append("_method", "PUT");
  datos.append("name", document.getElementById("perfilNombre").value.trim());
  datos.append("email", document.getElementById("perfilEmail").value.trim());

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
    mostrarAvatar(fotoActual ? "/storage/" + encodeURIComponent(fotoActual) : null);

    document.getElementById("nombreUsuario").textContent = usuario.name;
    cachearFotoNavbar(fotoActual);

    mostrarToast("Perfil actualizado", "success");
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
