// registrar.js — Registrar incidencia: catálogos, cascada tipo→subtipo, fotos, envío.

/* global apiFetch, aplicarMenuRol, toastFlash, mostrarToast, crearMapaPicker, bootstrap, poblarSelectCascada, itemsSubtiposDe, itemsCiudadesDe, ciudadMasCercana, crearGaleriaFotos, requerirSesion, cablearLogout */

document.addEventListener("DOMContentLoaded", async function () {
  const usuarioActual = await requerirSesion();
  if (!usuarioActual) return;
  aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "");
  cablearLogout();

  const esAdmin = usuarioActual.rol && usuarioActual.rol.nombre_rol === "admin";
  if (esAdmin) {
    document.querySelectorAll(".solo-admin").forEach(function (el) {
      el.classList.remove("d-none");
    });
  } else {
    // Rol normal: provincia/ciudad se autocompletan al marcar en el mapa, así que se ocultan.
    document.getElementById("campoProvincia").classList.add("d-none");
    document.getElementById("campoCiudad").classList.add("d-none");
    document.getElementById("crearProvincia").required = false;
    document.getElementById("crearCiudad").required = false;
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

  // Cascada provincia→ciudad (helper compartido).
  document.getElementById("crearProvincia").addEventListener("change", function () {
    const provinciaId = parseInt(this.value);
    poblarSelectCascada(
      document.getElementById("crearCiudad"),
      itemsCiudadesDe(catalogoCiudades, provinciaId),
      "Primero selecciona una provincia",
    );
  });

  // Cascada tipo→subtipo (helper compartido).
  document.getElementById("crearTipo").addEventListener("change", function () {
    const tipoId = parseInt(this.value);
    const tipo = catalogoTipos.find(function (t) {
      return t.id_tipo_incidencia === tipoId;
    });
    poblarSelectCascada(
      document.getElementById("crearSubtipo"),
      itemsSubtiposDe(tipo),
      "Primero selecciona un tipo",
    );
  });

  // Galería de fotos (compressión + preview + revocación vía galeriaFotos.js).
  const galeriaFotos = crearGaleriaFotos({
    input: document.getElementById("crearFotos"),
    dropzone: document.getElementById("dropzoneFotos"),
    preview: document.getElementById("crearFotosPreview"),
    error: document.getElementById("crearFotosError"),
    cupo: function () {
      return 3;
    },
    textoCupo: "Máximo 3 fotos permitidas.",
  });

  // Marca en el mapa → elige la ciudad existente más cercana y rellena provincia + ciudad.
  function autocompletarUbicacion(lat, lng) {
    const ciudad = ciudadMasCercana(catalogoCiudades, lat, lng);
    if (!ciudad) return;
    document.getElementById("crearProvincia").value = ciudad.id_provincia;
    poblarSelectCascada(
      document.getElementById("crearCiudad"),
      itemsCiudadesDe(catalogoCiudades, ciudad.id_provincia),
      "Primero selecciona una provincia",
    );
    document.getElementById("crearCiudad").value = ciudad.id_ciudad;
  }

  const picker = crearMapaPicker("mapaPicker", function (lat, lng) {
    document.getElementById("crearLatitud").value = lat.toFixed(6);
    document.getElementById("crearLongitud").value = lng.toFixed(6);
    document.getElementById("ubicacionError").classList.add("d-none");
    autocompletarUbicacion(lat, lng);
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
    }
    formData.append("id_subtipo_incidencia", document.getElementById("crearSubtipo").value);
    formData.append("id_ciudad", document.getElementById("crearCiudad").value);
    formData.append("latitud_incidencia", document.getElementById("crearLatitud").value);
    formData.append("longitud_incidencia", document.getElementById("crearLongitud").value);

    const direccion = document.getElementById("crearDireccion").value.trim();
    if (direccion) formData.append("direccion_incidencia", direccion);

    galeriaFotos.archivos.forEach(function (file) {
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
