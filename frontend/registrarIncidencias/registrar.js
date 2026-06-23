// registrar.js — Registrar incidencia: catálogos, cascada tipo→subtipo, fotos, envío.

/* global apiFetch, obtenerToken, eliminarToken, aplicarMenuRol, toastFlash, imageCompression, crearMapaPicker */

document.addEventListener("DOMContentLoaded", async function () {
  // Guard
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

  // Fotos ya comprimidas, listas para enviar
  let fotosSeleccionadas = [];

  // Cargar usuario
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

  // Logout
  document.getElementById("btnLogout").addEventListener("click", async function (e) {
    e.preventDefault();
    try {
      await apiFetch("/logout", { method: "POST" });
    } catch {
      /* ignorar */
    }
    eliminarToken();
    window.location.href = "../login/login.html";
  });

  // Catálogos
  let catalogoTipos = [];

  try {
    catalogoTipos = await apiFetch("/catalogos/tipos-incidencia");
    const selectTipo = document.getElementById("crearTipo");
    catalogoTipos.forEach(function (tipo) {
      const op = document.createElement("option");
      op.value = tipo.id_tipo_incidencia;
      op.textContent = tipo.nombre_tipo_incidencia;
      selectTipo.appendChild(op);
    });

    const ciudades = await apiFetch("/catalogos/ciudades");
    const selectCiudad = document.getElementById("crearCiudad");
    ciudades.forEach(function (ciudad) {
      const op = document.createElement("option");
      op.value = ciudad.id_ciudad;
      op.textContent = ciudad.nombre_ciudad;
      selectCiudad.appendChild(op);
    });
  } catch (error) {
    console.error("Error cargando catálogos:", error);
  }

  // Cascada tipo → subtipo
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

  // Compresión de fotos: redimensiona a ~1920px y calidad 0.8 antes de subir
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

  // El <label for> abre el selector al hacer clic; aquí procesamos la selección
  inputFotos.addEventListener("change", function () {
    procesarFotos(this.files);
    this.value = ""; // limpia el input para poder agregar más sin reemplazar
  });

  // Arrastrar y soltar sobre la zona de carga
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

  // Comprime cada foto y la agrega al acumulador (máx. 3)
  async function procesarFotos(lista) {
    errorFotos.classList.add("d-none");
    for (const file of Array.from(lista)) {
      if (fotosSeleccionadas.length >= 3) {
        mostrarErrorFotos("Máximo 3 fotos permitidas.");
        break;
      }
      try {
        const comprimida = await imageCompression(file, opcionesCompresion);
        // Forzar nombre .jpg para que calce con la validación del backend
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

  // Dibuja las miniaturas con un botón para quitar cada foto.
  // El cuadro grande solo se ve sin fotos; con fotos aparece un azulejo "+".
  function renderPreviews() {
    previewFotos.innerHTML = "";
    dropzone.classList.toggle("d-none", fotosSeleccionadas.length > 0);

    fotosSeleccionadas.forEach(function (file, idx) {
      const cont = document.createElement("div");
      cont.className = "position-relative";

      const img = document.createElement("img");
      img.src = URL.createObjectURL(file);
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

    // Azulejo "+" para agregar más, hasta el tope de 3
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

  // Mapa para elegir la ubicación (llena lat/long al hacer clic)
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

  // Envío del formulario
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

    // La ubicación se marca en el mapa (los campos son de solo lectura)
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
    formData.append("prioridad_incidencia", document.getElementById("crearPrioridad").value);
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
      // El admin va a la tabla de gestión; el resto, a "Mis incidencias".
      const esAdmin = usuarioActual.rol && usuarioActual.rol.nombre_rol === "admin";
      window.location.href = esAdmin
        ? "../incidencias/incidencias.html"
        : "../misIncidencias/misIncidencias.html";
    } catch (error) {
      errorDiv.textContent = error.message;
      errorDiv.classList.remove("d-none");
    } finally {
      btnCrear.disabled = false;
      spinner.classList.add("d-none");
    }
  });
});
