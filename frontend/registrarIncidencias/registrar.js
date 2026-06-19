// registrar.js — Registrar incidencia: catálogos, cascada tipo→subtipo, fotos, envío.

/* global apiFetch, obtenerToken, eliminarToken, aplicarMenuRol */

document.addEventListener("DOMContentLoaded", async function () {
  // Guard
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

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

  // Preview de fotos
  document.getElementById("crearFotos").addEventListener("change", function () {
    const preview = document.getElementById("crearFotosPreview");
    const errorDiv = document.getElementById("crearFotosError");
    preview.innerHTML = "";
    errorDiv.classList.add("d-none");

    if (this.files.length > 3) {
      errorDiv.textContent = "Máximo 3 fotos permitidas.";
      errorDiv.classList.remove("d-none");
      this.value = "";
      return;
    }

    Array.from(this.files).forEach(function (file) {
      if (file.size > 2 * 1024 * 1024) {
        errorDiv.textContent = '"' + file.name + '" supera los 2MB.';
        errorDiv.classList.remove("d-none");
        return;
      }
      const img = document.createElement("img");
      img.src = URL.createObjectURL(file);
      img.style.width = "80px";
      img.style.height = "80px";
      img.style.objectFit = "cover";
      img.style.borderRadius = "8px";
      preview.appendChild(img);
    });
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

    const fotos = document.getElementById("crearFotos").files;
    for (let i = 0; i < fotos.length; i++) {
      formData.append("fotos[]", fotos[i]);
    }

    try {
      await apiFetch("/incidencias", { method: "POST", body: formData });
      window.location.href = "../incidencias/incidencias.html";
    } catch (error) {
      errorDiv.textContent = error.message;
      errorDiv.classList.remove("d-none");
    } finally {
      btnCrear.disabled = false;
      spinner.classList.add("d-none");
    }
  });
});
