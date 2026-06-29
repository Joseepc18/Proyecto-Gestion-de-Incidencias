// perfil.js — Edición del perfil propio: nombre, correo, contraseña y foto.

// Ruta de la foto guardada en el servidor (relativa); null si no tiene.
/* global apiFetch, obtenerToken, eliminarToken, aplicarMenuRol, mostrarToast, imageCompression, pintarAvatarNavbar */

let fotoActual = null;
// Foto nueva ya comprimida lista para subir; null si no se cambió.
let fotoSeleccionada = null;
// true si el usuario pidió quitar la foto sin reemplazarla.
let quitarFoto = false;
// objectURL del preview para liberarlo al reemplazarlo.
let previewUrl = null;

// Compresión de la foto: redimensiona a ~1920px y calidad 0.8 antes de subir.
const opcionesCompresion = {
  maxSizeMB: 0.5,
  maxWidthOrHeight: 1920,
  useWebWorker: true,
  fileType: "image/jpeg",
  initialQuality: 0.8,
};

document.addEventListener("DOMContentLoaded", async function () {
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

  try {
    const usuario = await apiFetch("/user");
    document.getElementById("nombreUsuario").textContent = usuario.name;
    aplicarMenuRol(usuario.rol ? usuario.rol.nombre_rol : "");

    document.getElementById("perfilNombre").value = usuario.name;
    document.getElementById("perfilEmail").value = usuario.email;

    fotoActual = usuario.foto_perfil || null;
    cachearFotoNavbar(fotoActual);
    mostrarAvatar(fotoActual ? "/storage/" + fotoActual : null);
  } catch {
    eliminarToken();
    window.location.href = "../login/login.html";
    return;
  }

  document.getElementById("btnLogout").addEventListener("click", async function (e) {
    e.preventDefault();
    this.classList.add("pe-none", "opacity-50");
    try {
      await apiFetch("/logout", { method: "POST" });
    } catch {
      /* ignorar */
    }
    eliminarToken();
    window.location.href = "../login/login.html";
  });

  const inputFoto = document.getElementById("perfilFoto");
  document.getElementById("btnCambiarFoto").addEventListener("click", function () {
    inputFoto.click();
  });
  inputFoto.addEventListener("change", procesarFoto);
  document.getElementById("btnQuitarFoto").addEventListener("click", quitarLaFoto);

  document.getElementById("formPerfil").addEventListener("submit", guardarPerfil);
});

// Comprime la foto elegida y la muestra como preview.
async function procesarFoto() {
  const input = document.getElementById("perfilFoto");
  const file = input.files[0];
  input.value = "";
  if (!file) return;

  try {
    const comprimida = await imageCompression(file, opcionesCompresion);
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
    mostrarAvatar(fotoActual ? "/storage/" + fotoActual : null);

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
