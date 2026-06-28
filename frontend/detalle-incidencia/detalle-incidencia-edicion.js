// detalle-incidencia-edicion.js — Edición del ciudadano dueño (solo cuando está PENDIENTE).
// Editar texto + ubicación (botón "Editar") y subir/borrar fotos del reporte (en todo momento).
// El núcleo (detalle-incidencia.js) llama a edicionAlCargarDetalle cuando hay datos.

/* global apiFetch, mostrarToast, toastFlash, confirmar, imageCompression, opcionesCompresion, incActual, usuarioActual, idActual, activarMapaPicker */
/* exported edicionAlCargarDetalle */

// Catálogos para los selects de edición (se cargan una sola vez).
let catalogoTipos = [];
let catalogoCiudades = [];
// Ubicación elegida en el picker (arranca con la de la incidencia).
let latEdit = null;
let lngEdit = null;
// Fotos del reporte comprimidas, en cola para subir.
const fotosEnCola = [];

// Hook del núcleo: decide si esta incidencia es editable por quien la mira.
function edicionAlCargarDetalle() {
  const esDueno = incActual.id_usuario === usuarioActual.id;
  const editable = esDueno && incActual.estado_incidencia === "PENDIENTE";
  if (!editable) return;

  latEdit = incActual.latitud_incidencia != null ? Number(incActual.latitud_incidencia) : null;
  lngEdit = incActual.longitud_incidencia != null ? Number(incActual.longitud_incidencia) : null;

  // Botones de editar / eliminar
  const btnEditar = document.getElementById("btnEditar");
  const btnEliminar = document.getElementById("btnEliminar");
  btnEditar.classList.remove("d-none");
  btnEliminar.classList.remove("d-none");
  btnEditar.addEventListener("click", entrarEdicion);
  btnEliminar.addEventListener("click", eliminarIncidencia);
  document.getElementById("btnCancelarEdicion").addEventListener("click", function () {
    window.location.reload();
  });
  document.getElementById("btnGuardarEdicion").addEventListener("click", guardarCambios);
  document.getElementById("btnSubirFotos").addEventListener("click", subirFotosNuevas);

  // Fotos nuevas: input + arrastrar/soltar
  const inputFotos = document.getElementById("editFotos");
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

  // Fotos del reporte: editables desde el inicio (sin tocar "Editar").
  renderEvidenciasReporteEditable();
  document.getElementById("evidenciasReporteEdicion").classList.remove("d-none");
  renderFotosNuevas();
}

// Modo edición de datos + mapa (las fotos ya son independientes).
async function entrarEdicion() {
  await cargarCatalogos();
  document.getElementById("editTitulo").value = incActual.nombre_incidencia || "";
  document.getElementById("editDescripcion").value = incActual.descripcion_incidencia || "";
  document.getElementById("editDireccion").value = incActual.direccion_incidencia || "";

  const idTipo =
    incActual.subtipo && incActual.subtipo.tipo ? incActual.subtipo.tipo.id_tipo_incidencia : "";
  document.getElementById("editTipo").value = idTipo || "";
  poblarSubtipos(idTipo);
  if (incActual.subtipo) {
    document.getElementById("editSubtipo").value = incActual.subtipo.id_subtipo_incidencia;
  }
  const ciudadActual = catalogoCiudades.find(function (c) {
    return c.id_ciudad === incActual.id_ciudad;
  });
  const idProvincia = ciudadActual ? ciudadActual.id_provincia : "";
  document.getElementById("editProvincia").value = idProvincia || "";
  poblarCiudades(idProvincia);
  document.getElementById("editCiudad").value = incActual.id_ciudad || "";

  document.getElementById("datosVista").classList.add("d-none");
  document.getElementById("datosEdicion").classList.remove("d-none");
  document.getElementById("edicionAcciones").classList.remove("d-none");
  document.getElementById("btnEditar").classList.add("d-none");
  document.getElementById("btnEliminar").classList.add("d-none");

  // Mapa: pasar de solo-lectura a selector (el núcleo gestiona la instancia).
  const p = activarMapaPicker(latEdit, lngEdit, function (lat, lng) {
    latEdit = lat;
    lngEdit = lng;
  });
  const controles = document.getElementById("mapaEditControles");
  controles.classList.remove("d-none");
  controles.classList.add("d-flex");
  document.getElementById("btnMiUbicacionEdit").onclick = function () {
    p.usarMiUbicacion();
  };
}

// Carga los catálogos de tipos y ciudades en los selects (solo la 1.ª vez).
async function cargarCatalogos() {
  if (catalogoTipos.length > 0) return;
  try {
    catalogoTipos = await apiFetch("/catalogos/tipos-incidencia");
    const selectTipo = document.getElementById("editTipo");
    catalogoTipos.forEach(function (t) {
      const op = document.createElement("option");
      op.value = t.id_tipo_incidencia;
      op.textContent = t.nombre_tipo_incidencia;
      selectTipo.appendChild(op);
    });

    catalogoCiudades = await apiFetch("/catalogos/ciudades");
    const provincias = await apiFetch("/catalogos/provincias");
    const selectProvincia = document.getElementById("editProvincia");
    provincias.forEach(function (p) {
      const op = document.createElement("option");
      op.value = p.id_provincia;
      op.textContent = p.nombre_provincia;
      selectProvincia.appendChild(op);
    });

    // Cascada tipo → subtipo
    selectTipo.addEventListener("change", function () {
      poblarSubtipos(parseInt(this.value));
    });

    // Cascada provincia → ciudad
    selectProvincia.addEventListener("change", function () {
      poblarCiudades(parseInt(this.value));
    });
  } catch (error) {
    mostrarToast("No se pudieron cargar los catálogos: " + error.message, "error");
  }
}

// Llena el select de subtipos según el tipo elegido.
function poblarSubtipos(idTipo) {
  const selectSubtipo = document.getElementById("editSubtipo");
  selectSubtipo.innerHTML = "";
  const tipo = catalogoTipos.find(function (t) {
    return t.id_tipo_incidencia === parseInt(idTipo);
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
}

// Llena el select de ciudades según la provincia elegida.
function poblarCiudades(idProvincia) {
  const selectCiudad = document.getElementById("editCiudad");
  selectCiudad.innerHTML = "";
  if (!idProvincia) {
    selectCiudad.disabled = true;
    selectCiudad.innerHTML = '<option value="">Primero selecciona una provincia</option>';
    return;
  }
  selectCiudad.disabled = false;
  selectCiudad.innerHTML = '<option value="">Seleccionar...</option>';
  catalogoCiudades
    .filter(function (c) {
      return c.id_provincia === parseInt(idProvincia);
    })
    .forEach(function (c) {
      const op = document.createElement("option");
      op.value = c.id_ciudad;
      op.textContent = c.nombre_ciudad;
      selectCiudad.appendChild(op);
    });
}

// Evidencias del reporte que se conservan (las de tipo RESOLUCION no las toca el ciudadano).
function evidenciasReporte() {
  return (incActual.evidencias || []).filter((ev) => ev.tipo_evidencia !== "RESOLUCION");
}

// Pinta las fotos del reporte con botón × para eliminación inmediata.
function renderEvidenciasReporteEditable() {
  const cont = document.getElementById("fotosReporte");
  cont.innerHTML = "";
  const reporte = evidenciasReporte();
  if (reporte.length === 0) {
    cont.innerHTML = '<p class="text-muted small mb-0">Sin fotos del reporte.</p>';
  }
  reporte.forEach(function (ev) {
    const wrap = document.createElement("div");
    wrap.className = "position-relative";

    const img = document.createElement("img");
    img.src = "/storage/" + ev.url_evidencia;
    img.className = "evidencia-foto rounded";
    img.style.cssText = "width:130px;height:130px;object-fit:cover";
    img.alt = "Evidencia";

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "btn btn-danger btn-sm position-absolute top-0 end-0 py-0 px-1";
    btn.innerHTML = "&times;";
    btn.addEventListener("click", function () {
      eliminarFotoInmediata(ev.id_evidencia);
    });

    wrap.appendChild(img);
    wrap.appendChild(btn);
    cont.appendChild(wrap);
  });
}

// Elimina una foto del backend y actualiza la galería al instante.
async function eliminarFotoInmediata(idEv) {
  try {
    await apiFetch("/evidencias/" + idEv, { method: "DELETE" });
    incActual.evidencias = (incActual.evidencias || []).filter((ev) => ev.id_evidencia !== idEv);
    renderEvidenciasReporteEditable();
    renderFotosNuevas();
  } catch (error) {
    mostrarToast("No se pudo eliminar la foto: " + error.message, "error");
  }
}

// Cupo de fotos nuevas del reporte (máx. 3 en total).
function cupoFotos() {
  return 3 - evidenciasReporte().length;
}

// Comprime cada foto y la agrega a la cola, sin pasar del cupo.
async function procesarFotos(lista) {
  const errorFotos = document.getElementById("editFotosError");
  errorFotos.classList.add("d-none");
  for (const file of Array.from(lista)) {
    if (fotosEnCola.length >= cupoFotos()) {
      errorFotos.textContent = "Máximo 3 fotos en total.";
      errorFotos.classList.remove("d-none");
      break;
    }
    try {
      const comprimida = await imageCompression(file, opcionesCompresion);
      const jpg = new File([comprimida], file.name.replace(/\.\w+$/, ".jpg"), {
        type: "image/jpeg",
      });
      fotosEnCola.push(jpg);
    } catch {
      errorFotos.textContent = 'No se pudo procesar "' + file.name + '".';
      errorFotos.classList.remove("d-none");
    }
  }
  renderFotosNuevas();
}

// Dibuja las miniaturas de la cola y el botón "Subir fotos".
function renderFotosNuevas() {
  const preview = document.getElementById("editFotosPreview");
  const dropzone = document.getElementById("dropzoneFotos");
  const inputFotos = document.getElementById("editFotos");
  const btnSubir = document.getElementById("btnSubirFotos");
  preview.innerHTML = "";

  const cupo = cupoFotos();
  dropzone.classList.toggle("d-none", fotosEnCola.length > 0 || cupo <= 0);

  fotosEnCola.forEach(function (file, idx) {
    const cont = document.createElement("div");
    cont.className = "position-relative";

    const img = document.createElement("img");
    const url = URL.createObjectURL(file);
    img.onload = () => URL.revokeObjectURL(url);
    img.src = url;
    img.style.cssText = "width:80px;height:80px;object-fit:cover;border-radius:8px";

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "btn btn-danger btn-sm position-absolute top-0 end-0 py-0 px-1";
    btn.innerHTML = "&times;";
    btn.addEventListener("click", function () {
      fotosEnCola.splice(idx, 1);
      renderFotosNuevas();
    });

    cont.appendChild(img);
    cont.appendChild(btn);
    preview.appendChild(cont);
  });

  // Azulejo "+" para seguir agregando mientras quede cupo.
  if (fotosEnCola.length > 0 && fotosEnCola.length < cupo) {
    const agregar = document.createElement("button");
    agregar.type = "button";
    agregar.className = "foto-agregar";
    agregar.innerHTML = '<i class="bi bi-plus-lg" aria-hidden="true"></i>';
    agregar.addEventListener("click", function () {
      inputFotos.click();
    });
    preview.appendChild(agregar);
  }

  btnSubir.classList.toggle("d-none", fotosEnCola.length === 0);
}

// Sube las fotos en cola al backend (tipo REPORTE) y actualiza la galería.
async function subirFotosNuevas() {
  if (fotosEnCola.length === 0) return;

  const btn = document.getElementById("btnSubirFotos");
  const spinner = document.getElementById("subirFotosSpinner");
  btn.disabled = true;
  spinner.classList.remove("d-none");

  try {
    const formData = new FormData();
    fotosEnCola.forEach(function (file) {
      formData.append("fotos[]", file);
    });
    formData.append("tipo_evidencia", "REPORTE");
    const actualizada = await apiFetch("/incidencias/" + idActual + "/evidencias", {
      method: "POST",
      body: formData,
    });
    incActual.evidencias = actualizada.evidencias || [];
    fotosEnCola.length = 0;
    renderEvidenciasReporteEditable();
    renderFotosNuevas();
    mostrarToast("Fotos subidas", "success");
  } catch (error) {
    mostrarToast("No se pudo subir: " + error.message, "error");
  } finally {
    btn.disabled = false;
    spinner.classList.add("d-none");
  }
}

// Guarda solo los datos descriptivos y la ubicación (las fotos ya se persistieron aparte).
async function guardarCambios() {
  const form = document.getElementById("datosEdicion");
  if (!form.checkValidity()) {
    form.classList.add("was-validated");
    return;
  }
  if (latEdit == null || lngEdit == null) {
    mostrarToast("Marca la ubicación en el mapa.", "warning");
    return;
  }

  const btn = document.getElementById("btnGuardarEdicion");
  const spinner = document.getElementById("guardarSpinner");
  btn.disabled = true;
  spinner.classList.remove("d-none");

  try {
    await apiFetch("/incidencias/" + incActual.id_incidencia, {
      method: "PUT",
      body: JSON.stringify({
        nombre_incidencia: document.getElementById("editTitulo").value.trim(),
        descripcion_incidencia: document.getElementById("editDescripcion").value.trim(),
        direccion_incidencia: document.getElementById("editDireccion").value.trim(),
        id_subtipo_incidencia: document.getElementById("editSubtipo").value,
        id_ciudad: document.getElementById("editCiudad").value,
        latitud_incidencia: latEdit,
        longitud_incidencia: lngEdit,
      }),
    });
    toastFlash("Cambios guardados", "success");
    window.location.reload();
  } catch (error) {
    mostrarToast("No se pudo guardar: " + error.message, "error");
    btn.disabled = false;
    spinner.classList.add("d-none");
  }
}

// Eliminar la incidencia completa.
async function eliminarIncidencia() {
  const ok = await confirmar({
    titulo: "Eliminar incidencia",
    mensaje: "Esta acción no se puede deshacer. ¿Deseas continuar?",
    textoConfirmar: "Eliminar",
    peligro: true,
  });
  if (!ok) return;

  try {
    await apiFetch("/incidencias/" + incActual.id_incidencia, { method: "DELETE" });
    toastFlash("Incidencia eliminada", "success");
    window.location.href = "../mis-incidencias/mis-incidencias.html";
  } catch (error) {
    mostrarToast("No se pudo eliminar: " + error.message, "error");
  }
}
