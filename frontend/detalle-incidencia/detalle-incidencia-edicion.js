// detalle-incidencia-edicion.js — Edición del ciudadano dueño, llamado desde el núcleo (detalle-incidencia.js)

/* exported edicionAlCargarDetalle */

// Catálogos para los selects de edición (se cargan una sola vez).
/* global apiFetch, mostrarToast, toastFlash, confirmar, abrirModal, motivoConOtroHtml, cablearMotivoConOtro, leerMotivoSeleccionado, crearGaleriaFotos, poblarSelectCascada, itemsSubtiposDe, itemsCiudadesDe, ciudadEnPunto, incActual, usuarioActual, idActual, activarMapaPicker, pintarMapaLectura, provinciaCiudadTexto */

let catalogoTipos = [];
let catalogoCiudades = [];
// Polígonos de cantón para resolver la ciudad exacta al marcar en el mapa.
let cantonesGeo = null;
// Ubicación elegida en el picker (arranca con la de la incidencia).
let latEdit = null;
let lngEdit = null;
// Galería de fotos nuevas del reporte (gestionada por galeriaFotos.js).
let galeriaReporte = null;
// Instancia del picker de ubicación en edición (para liberarla al volver a lectura).
let pickerEdicion = null;

// Datos (texto/ubicación) solo editables en PENDIENTE; fotos hasta que se resuelve
function edicionAlCargarDetalle() {
  const esDueno = incActual.id_usuario === usuarioActual.id;

  if (esDueno && incActual.estado_incidencia === "RESUELTO") {
    prepararSolicitudReapertura();
  }

  if (esDueno && incActual.estado_incidencia !== "RESUELTO") {
    prepararFotosReporte();
  }

  const editable = esDueno && incActual.estado_incidencia === "PENDIENTE";
  if (!editable) return;

  latEdit = incActual.latitud_incidencia != null ? Number(incActual.latitud_incidencia) : null;
  lngEdit = incActual.longitud_incidencia != null ? Number(incActual.longitud_incidencia) : null;

  const btnEditar = document.getElementById("btnEditar");
  const btnEliminar = document.getElementById("btnEliminar");
  btnEditar.classList.remove("d-none");
  btnEliminar.classList.remove("d-none");
  btnEditar.addEventListener("click", entrarEdicion);
  btnEliminar.addEventListener("click", eliminarIncidencia);
  document.getElementById("btnCancelarEdicion").addEventListener("click", salirEdicion);
  document.getElementById("btnGuardarEdicion").addEventListener("click", guardarCambios);
}

// Activa en PENDIENTE y EN_PROCESO, independiente del modo edición de texto/ubicación
function prepararFotosReporte() {
  // Modo compacto: el azulejo "+" va inline en la grilla para no desbordar el panel
  galeriaReporte = crearGaleriaFotos({
    input: document.getElementById("editFotos"),
    preview: document.getElementById("editFotosPreview"),
    error: document.getElementById("editFotosError"),
    cupo: cupoFotos,
    textoCupo: "Máximo 3 fotos en total.",
    btnSubir: document.getElementById("btnSubirFotos"),
    onSubir: function (archivos) {
      return subirFotosNuevas(archivos);
    },
  });

  renderEvidenciasReporteEditable();
  document.getElementById("evidenciasReporteEdicion").classList.remove("d-none");
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

  // Rol normal: provincia/ciudad se autocompletan al marcar en el mapa, así que se ocultan.
  const esAdmin = usuarioActual.rol && usuarioActual.rol.nombre_rol === "admin";
  if (!esAdmin) {
    document.getElementById("campoEditProvincia").classList.add("d-none");
    document.getElementById("campoEditCiudad").classList.add("d-none");
    document.getElementById("editProvincia").required = false;
    document.getElementById("editCiudad").required = false;
  }

  document.getElementById("datosVista").classList.add("d-none");
  document.getElementById("datosEdicion").classList.remove("d-none");
  document.getElementById("edicionAcciones").classList.remove("d-none");
  document.getElementById("edicionAcciones").classList.add("d-flex");
  document.getElementById("btnEditar").classList.add("d-none");
  document.getElementById("btnEliminar").classList.add("d-none");

  pickerEdicion = activarMapaPicker(latEdit, lngEdit, function (lat, lng) {
    latEdit = lat;
    lngEdit = lng;
    autocompletarUbicacionEdit(lat, lng);
  });
  const controles = document.getElementById("mapaEditControles");
  controles.classList.remove("d-none");
  controles.classList.add("d-flex");
  document.getElementById("btnMiUbicacionEdit").onclick = function () {
    pickerEdicion.usarMiUbicacion();
  };
}

// Libera el picker de ubicación y deja el contenedor listo para el mapa de lectura.
function destruirPickerEdicion() {
  if (pickerEdicion) {
    pickerEdicion.map.remove();
    pickerEdicion = null;
  }
  document.getElementById("mapaDetalle").innerHTML = "";
}

// Vuelve de edición a la vista de lectura sin recargar la página (restaurar DOM + mapa).
function salirEdicion() {
  document.getElementById("datosVista").classList.remove("d-none");
  document.getElementById("datosEdicion").classList.add("d-none");
  const acciones = document.getElementById("edicionAcciones");
  acciones.classList.add("d-none");
  acciones.classList.remove("d-flex");
  document.getElementById("btnEditar").classList.remove("d-none");
  document.getElementById("btnEliminar").classList.remove("d-none");
  const controles = document.getElementById("mapaEditControles");
  controles.classList.add("d-none");
  controles.classList.remove("d-flex");
  destruirPickerEdicion();
  pintarMapaLectura();
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
    fetch("../assets/geo/ecuador-cantones.geojson")
      .then(function (r) {
        return r.json();
      })
      .then(function (g) {
        cantonesGeo = g;
      })
      .catch(function () {});
    const provincias = await apiFetch("/catalogos/provincias");
    const selectProvincia = document.getElementById("editProvincia");
    provincias.forEach(function (p) {
      const op = document.createElement("option");
      op.value = p.id_provincia;
      op.textContent = p.nombre_provincia;
      selectProvincia.appendChild(op);
    });

    selectTipo.addEventListener("change", function () {
      poblarSubtipos(parseInt(this.value));
    });

    selectProvincia.addEventListener("change", function () {
      poblarCiudades(parseInt(this.value));
    });
  } catch (error) {
    mostrarToast("No se pudieron cargar los catálogos: " + error.message, "error");
  }
}

// Llena el select de subtipos según el tipo elegido (cascada reutilizable).
function poblarSubtipos(idTipo) {
  const tipo = catalogoTipos.find(function (t) {
    return t.id_tipo_incidencia === parseInt(idTipo);
  });
  poblarSelectCascada(
    document.getElementById("editSubtipo"),
    itemsSubtiposDe(tipo),
    "Primero selecciona un tipo",
  );
}

// Llena el select de ciudades según la provincia elegida (cascada reutilizable).
function poblarCiudades(idProvincia) {
  poblarSelectCascada(
    document.getElementById("editCiudad"),
    itemsCiudadesDe(catalogoCiudades, idProvincia),
    "Primero selecciona una provincia",
  );
}

// Marca en el mapa → autocompleta provincia + ciudad por el cantón que contiene el punto.
function autocompletarUbicacionEdit(lat, lng) {
  const ciudad = ciudadEnPunto(cantonesGeo, catalogoCiudades, lat, lng);
  if (!ciudad) return;
  document.getElementById("editProvincia").value = ciudad.id_provincia;
  poblarCiudades(ciudad.id_provincia);
  document.getElementById("editCiudad").value = ciudad.id_ciudad;
}

// Evidencias del reporte que se conservan (las de tipo RESOLUCION no las toca el ciudadano).
function evidenciasReporte() {
  return (incActual.evidencias || []).filter((ev) => ev.tipo_evidencia !== "RESOLUCION");
}

// Grid de miniaturas con botón × para borrar y un azulejo "+" si aún hay cupo
function renderEvidenciasReporteEditable() {
  const cont = document.getElementById("fotosReporte");
  // Reemplaza el layout de carrusel (flex-columna) que deja montarCarrusel
  cont.className = "d-flex gap-2 flex-wrap align-items-center";
  cont.innerHTML = "";
  const reporte = evidenciasReporte();

  reporte.forEach(function (ev) {
    const wrap = document.createElement("div");
    wrap.className = "position-relative";

    const img = document.createElement("img");
    img.loading = "lazy";
    img.src = "/storage/" + ev.url_evidencia;
    img.className = "evidencia-foto evidencia-foto-md rounded";
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

  // Azulejo "+" del tamaño de una miniatura para abrir el selector de archivos.
  if (cupoFotos() > 0) {
    const agregar = document.createElement("button");
    agregar.type = "button";
    agregar.className = "foto-agregar foto-agregar-md";
    agregar.title = "Agregar fotos";
    agregar.innerHTML = '<i class="bi bi-plus-lg" aria-hidden="true"></i>';
    agregar.addEventListener("click", function () {
      document.getElementById("editFotos").click();
    });
    cont.appendChild(agregar);
  } else if (reporte.length === 0) {
    cont.innerHTML = '<p class="text-muted small mb-0">Sin fotos del reporte.</p>';
  }
}

// Elimina una foto del backend y actualiza la galería al instante.
async function eliminarFotoInmediata(idEv) {
  try {
    await apiFetch("/evidencias/" + idEv, { method: "DELETE" });
    incActual.evidencias = (incActual.evidencias || []).filter((ev) => ev.id_evidencia !== idEv);
    renderEvidenciasReporteEditable();
    if (galeriaReporte) galeriaReporte.render();
  } catch (error) {
    mostrarToast("No se pudo eliminar la foto: " + error.message, "error");
  }
}

// Cupo de fotos nuevas del reporte (máx. 3 en total).
function cupoFotos() {
  return 3 - evidenciasReporte().length;
}

// Sube las fotos en cola al backend (tipo REPORTE) y actualiza la galería.
async function subirFotosNuevas(archivos) {
  const btn = document.getElementById("btnSubirFotos");
  const spinner = document.getElementById("subirFotosSpinner");
  btn.disabled = true;
  spinner.classList.remove("d-none");

  try {
    const formData = new FormData();
    archivos.forEach(function (file) {
      formData.append("fotos[]", file);
    });
    formData.append("tipo_evidencia", "REPORTE");
    const actualizada = await apiFetch("/incidencias/" + idActual + "/evidencias", {
      method: "POST",
      body: formData,
    });
    incActual.evidencias = actualizada.evidencias || [];
    renderEvidenciasReporteEditable();
    galeriaReporte.limpiar();
    mostrarToast("Fotos subidas", "success");
  } catch (error) {
    mostrarToast("No se pudo subir: " + error.message, "error");
  } finally {
    btn.disabled = false;
    spinner.classList.add("d-none");
  }
}

// Vuelve a pintar los campos de solo lectura a partir de incActual (sin recargar).
function repintarVistaLectura() {
  document.getElementById("detalleTitulo").textContent = incActual.nombre_incidencia;

  const tipo =
    incActual.subtipo && incActual.subtipo.tipo
      ? incActual.subtipo.tipo.nombre_tipo_incidencia
      : "—";
  const subtipo = incActual.subtipo ? incActual.subtipo.nombre_subtipo_incidencia : "—";
  document.getElementById("detalleTipoBadge").textContent = tipo;
  document.getElementById("detalleTipoTexto").textContent = "Tipo: " + tipo;
  document.getElementById("detalleSubtipoTexto").textContent = "Subtipo: " + subtipo;

  const bloqueDesc = document.getElementById("detalleDescripcionBloque");
  if (incActual.descripcion_incidencia) {
    bloqueDesc.classList.remove("d-none");
    document.getElementById("detalleDescripcion").textContent = incActual.descripcion_incidencia;
  } else {
    bloqueDesc.classList.add("d-none");
  }

  document.getElementById("metaProvinciaCiudad").textContent = provinciaCiudadTexto(
    incActual.ciudad,
  );
  document.getElementById("detalleDireccion").textContent =
    incActual.direccion_incidencia || "No especificada";
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
    // El PUT devuelve la incidencia actualizada (sin evidencias); fusionamos campos puntuales.
    const actualizada = await apiFetch("/incidencias/" + incActual.id_incidencia, {
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
    incActual.nombre_incidencia = actualizada.nombre_incidencia;
    incActual.descripcion_incidencia = actualizada.descripcion_incidencia;
    incActual.direccion_incidencia = actualizada.direccion_incidencia;
    incActual.subtipo = actualizada.subtipo;
    incActual.id_subtipo_incidencia = actualizada.id_subtipo_incidencia;
    incActual.ciudad = actualizada.ciudad;
    incActual.id_ciudad = actualizada.id_ciudad;
    incActual.latitud_incidencia = actualizada.latitud_incidencia;
    incActual.longitud_incidencia = actualizada.longitud_incidencia;

    repintarVistaLectura();
    salirEdicion();
    mostrarToast("Cambios guardados", "success");
  } catch (error) {
    mostrarToast("No se pudo guardar: " + error.message, "error");
    btn.disabled = false;
    spinner.classList.add("d-none");
  }
}

// Motivos frecuentes para pedir reapertura; "Otro" abre un textarea libre.
const MOTIVOS_REAPERTURA = [
  "El problema sigue igual",
  "Volvió a aparecer poco después",
  "La resolución fue incompleta",
  "No era la solución correcta",
];

// El dueño pide reabrir su incidencia resuelta: NO cambia el estado, solo lo notifica al admin.
function prepararSolicitudReapertura() {
  const btn = document.getElementById("btnSolicitarReapertura");
  btn.classList.remove("d-none");
  btn.addEventListener("click", solicitarReapertura);
}

async function solicitarReapertura() {
  const promesaModal = abrirModal({
    titulo: "No quedó resuelto",
    cuerpoHtml:
      '<p class="text-secondary small">Un administrador revisará tu solicitud antes de reabrirla.</p>' +
      motivoConOtroHtml(MOTIVOS_REAPERTURA),
    textoConfirmar: "Enviar solicitud",
    alConfirmar: async function (form) {
      await apiFetch("/incidencias/" + idActual + "/solicitar-reapertura", {
        method: "POST",
        body: JSON.stringify({ motivo: leerMotivoSeleccionado(form) }),
      });
    },
  });

  cablearMotivoConOtro();

  const confirmado = await promesaModal;
  if (!confirmado) return;

  mostrarToast("Solicitud enviada. Un administrador la revisará.", "success");
  document.getElementById("btnSolicitarReapertura").classList.add("d-none");
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
