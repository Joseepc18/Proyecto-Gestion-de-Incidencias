// registrar.js — Registrar incidencia: catálogos, cascada tipo→subtipo, fotos, envío.

/* global apiFetch, obtenerToken, eliminarToken, aplicarMenuRol, toastFlash, mostrarToast, imageCompression, crearMapaPicker, bootstrap */

document.addEventListener("DOMContentLoaded", async function () {
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

  let fotosSeleccionadas = [];

  let usuarioActual = null;
  try {
    usuarioActual = await apiFetch("/user");
    document.getElementById("nombreUsuario").textContent = usuarioActual.name;

    aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "");
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

  const esAdmin = usuarioActual.rol && usuarioActual.rol.nombre_rol === "admin";
  if (esAdmin) {
    document.querySelectorAll(".solo-admin").forEach(function (el) {
      el.classList.remove("d-none");
    });
  }

  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    bootstrap.Tooltip.getOrCreateInstance(el);
  });

  let catalogoTipos = [];
  let catalogoCiudades = [];

  try {
    catalogoTipos = await apiFetch("/catalogos/tipos-incidencia");
    const selectTipo = document.getElementById("crearTipo");
    catalogoTipos.forEach(function (tipo) {
      const op = document.createElement("option");
      op.value = tipo.id_tipo_incidencia;
      op.textContent = tipo.nombre_tipo_incidencia;
      selectTipo.appendChild(op);
    });

    catalogoCiudades = await apiFetch("/catalogos/ciudades");
    const provincias = await apiFetch("/catalogos/provincias");
    const selectProvincia = document.getElementById("crearProvincia");
    provincias.forEach(function (provincia) {
      const op = document.createElement("option");
      op.value = provincia.id_provincia;
      op.textContent = provincia.nombre_provincia;
      selectProvincia.appendChild(op);
    });
  } catch {
    mostrarToast("No se pudieron cargar los catálogos. Recarga la página.", "error");
  }

  document.getElementById("crearProvincia").addEventListener("change", function () {
    const selectCiudad = document.getElementById("crearCiudad");
    const provinciaId = parseInt(this.value);
    selectCiudad.innerHTML = "";

    if (!provinciaId) {
      selectCiudad.disabled = true;
      selectCiudad.innerHTML = '<option value="">Primero selecciona una provincia</option>';
      return;
    }

    selectCiudad.disabled = false;
    selectCiudad.innerHTML = '<option value="">Seleccionar...</option>';
    catalogoCiudades
      .filter(function (ciudad) {
        return ciudad.id_provincia === provinciaId;
      })
      .forEach(function (ciudad) {
        const op = document.createElement("option");
        op.value = ciudad.id_ciudad;
        op.textContent = ciudad.nombre_ciudad;
        selectCiudad.appendChild(op);
      });
  });

  document.getElementById("crearTipo").addEventListener("change", function () {
    const selectSubtipo = document.getElementById("crearSubtipo");
    selectSubtipo.innerHTML = "";

    const tipoId = parseInt(this.value);
    const tipo = catalogoTipos.find(function (t) {
      return t.id_tipo_incidencia === tipoId;
    });

    if (!tipo || !tipo.subtipos || tipo.subtipos.length === 0) {
      selectSubtipo.disabled = true;
      selectSubtipo.innerHTML = '<option value="">Primero selecciona un tipo</option>';
      return;
    }

    selectSubtipo.disabled = false;
    selectSubtipo.innerHTML = '<option value="">Seleccionar...</option>';
    tipo.subtipos.forEach(function (sub) {
      const op = document.createElement("option");
      op.value = sub.id_subtipo_incidencia;
      op.textContent = sub.nombre_subtipo_incidencia;
      selectSubtipo.appendChild(op);
    });
  });

  const opcionesCompresion = {
    maxSizeMB: 0.5,
    maxWidthOrHeight: 1920,
    useWebWorker: true,
    fileType: "image/jpeg",
    initialQuality: 0.8,
  };

  const inputFotos = document.getElementById("crearFotos");
  const previewFotos = document.getElementById("crearFotosPreview");
  const errorFotos = document.getElementById("crearFotosError");

  inputFotos.addEventListener("change", function () {
    procesarFotos(this.files);
    this.value = "";
  });

  const dropzone = document.getElementById("dropzoneFotos");
  ["dragenter", "dragover"].forEach(function (ev) {
    dropzone.addEventListener(ev, function (e) {
      e.preventDefault();
      dropzone.classList.add("dropzone-fotos--activo");
    });
  });
  ["dragleave", "dragend"].forEach(function (ev) {
    dropzone.addEventListener(ev, function () {
      dropzone.classList.remove("dropzone-fotos--activo");
    });
  });
  dropzone.addEventListener("drop", function (e) {
    e.preventDefault();
    dropzone.classList.remove("dropzone-fotos--activo");
    procesarFotos(e.dataTransfer.files);
  });

  async function procesarFotos(lista) {
    errorFotos.classList.add("d-none");
    for (const file of Array.from(lista)) {
      if (fotosSeleccionadas.length >= 3) {
        mostrarErrorFotos("Máximo 3 fotos permitidas.");
        break;
      }
      try {
        const comprimida = await imageCompression(file, opcionesCompresion);
        const jpg = new File([comprimida], file.name.replace(/\.\w+$/, ".jpg"), {
          type: "image/jpeg",
        });
        fotosSeleccionadas.push(jpg);
      } catch {
        mostrarErrorFotos('No se pudo procesar "' + file.name + '".');
      }
    }
    renderPreviews();
  }

  function mostrarErrorFotos(mensaje) {
    errorFotos.textContent = mensaje;
    errorFotos.classList.remove("d-none");
  }

  function renderPreviews() {
    previewFotos.innerHTML = "";
    dropzone.classList.toggle("d-none", fotosSeleccionadas.length > 0);

    fotosSeleccionadas.forEach(function (file, idx) {
      const cont = document.createElement("div");
      cont.className = "position-relative";

      const img = document.createElement("img");
      img.loading = "lazy";
      const url = URL.createObjectURL(file);
      img.onload = () => URL.revokeObjectURL(url);
      img.src = url;
      img.style.width = "80px";
      img.style.height = "80px";
      img.style.objectFit = "cover";
      img.style.borderRadius = "8px";

      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "btn btn-danger btn-sm position-absolute top-0 end-0 py-0 px-1";
      btn.innerHTML = "&times;";
      btn.addEventListener("click", function () {
        fotosSeleccionadas.splice(idx, 1);
        renderPreviews();
      });

      cont.appendChild(img);
      cont.appendChild(btn);
      previewFotos.appendChild(cont);
    });

    if (fotosSeleccionadas.length > 0 && fotosSeleccionadas.length < 3) {
      const agregar = document.createElement("button");
      agregar.type = "button";
      agregar.className = "foto-agregar";
      agregar.innerHTML = '<i class="bi bi-plus-lg" aria-hidden="true"></i>';
      agregar.addEventListener("click", function () {
        inputFotos.click();
      });
      previewFotos.appendChild(agregar);
    }
  }

  const picker = crearMapaPicker("mapaPicker", function (lat, lng) {
    document.getElementById("crearLatitud").value = lat.toFixed(6);
    document.getElementById("crearLongitud").value = lng.toFixed(6);
    document.getElementById("ubicacionError").classList.add("d-none");
  });
  setTimeout(function () {
    picker.map.invalidateSize();
  }, 200);

  document.getElementById("btnMiUbicacion").addEventListener("click", function () {
    picker.usarMiUbicacion();
  });

  document.getElementById("formCrear").addEventListener("submit", async function (e) {
    e.preventDefault();
    const form = this;
    const errorDiv = document.getElementById("crearError");
    const btnCrear = document.getElementById("btnCrearIncidencia");
    const spinner = document.getElementById("crearSpinner");

    errorDiv.classList.add("d-none");

    if (!form.checkValidity()) {
      form.classList.add("was-validated");
      return;
    }

    if (!document.getElementById("crearLatitud").value) {
      document.getElementById("ubicacionError").classList.remove("d-none");
      return;
    }

    btnCrear.disabled = true;
    spinner.classList.remove("d-none");

    const formData = new FormData();
    formData.append("nombre_incidencia", document.getElementById("crearTitulo").value.trim());
    formData.append(
      "descripcion_incidencia",
      document.getElementById("crearDescripcion").value.trim(),
    );
    if (esAdmin) {
      formData.append("prioridad_incidencia", document.getElementById("crearPrioridad").value);
      formData.append("estado_incidencia", document.getElementById("crearEstado").value);
    }
    formData.append("id_subtipo_incidencia", document.getElementById("crearSubtipo").value);
    formData.append("id_ciudad", document.getElementById("crearCiudad").value);
    formData.append("latitud_incidencia", document.getElementById("crearLatitud").value);
    formData.append("longitud_incidencia", document.getElementById("crearLongitud").value);

    const direccion = document.getElementById("crearDireccion").value.trim();
    if (direccion) formData.append("direccion_incidencia", direccion);

    fotosSeleccionadas.forEach(function (file) {
      formData.append("fotos[]", file);
    });

    try {
      await apiFetch("/incidencias", { method: "POST", body: formData });
      toastFlash("Incidencia registrada", "success");
      window.location.href = esAdmin
        ? "../gestion-incidencias/gestion-incidencias.html"
        : "../mis-incidencias/mis-incidencias.html";
    } catch (error) {
      errorDiv.textContent = error.message;
      errorDiv.classList.remove("d-none");
    } finally {
      btnCrear.disabled = false;
      spinner.classList.add("d-none");
    }
  });
});
