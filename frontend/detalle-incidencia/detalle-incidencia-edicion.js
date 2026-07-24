// detalle-incidencia-edicion.js — Edición del ciudadano dueño, llamado desde el núcleo (detalle-incidencia.js)

/* exported edicionAlCargarDetalle */

/* global apiFetch, tienePermiso, mostrarToast, abrirModal, motivoConOtroHtml, cablearMotivoConOtro, leerMotivoSeleccionado, crearGaleriaFotos, abrirSelectorFuenteFoto, crearCatalogosIncidencia, incActual, usuarioActual, idActual, activarMapaPicker, pintarMapaLectura, provinciaCiudadTexto, pintarFotos, fijarOpcionesFotosReporte */

// Catálogos + cascadas (helper compartido con registrar-incidencia); se cargan una sola vez.
const catalogos = crearCatalogosIncidencia({
  tipo: "editTipo",
  subtipo: "editSubtipo",
  provincia: "editProvincia",
  ciudad: "editCiudad",
});
// Ubicación elegida en el picker (arranca con la de la incidencia).
let latEdit = null;
let lngEdit = null;
// Galería de fotos nuevas del reporte (gestionada por galeriaFotos.js).
let galeriaReporte = null;
// Instancia del picker de ubicación en edición (para liberarla al volver a lectura).
let pickerEdicion = null;
// Valores de los campos al entrar en edición, para saber si hay algo que guardar.
let snapshotEdicion = null;
let mapaEditadoManualmente = false;

// Datos (texto/ubicación) solo editables en PENDIENTE; fotos hasta que se resuelve
function edicionAlCargarDetalle() {
  const esDueno = incActual.id_usuario === usuarioActual.id;

  if (esDueno && incActual.estado_incidencia === "RESUELTO") {
    prepararSolicitudReapertura();
  }

  if (
    esDueno &&
    incActual.estado_incidencia !== "RESUELTO" &&
    incActual.estado_incidencia !== "CERRADO"
  ) {
    prepararFotosReporte();
  }

  const editable = esDueno && incActual.estado_incidencia === "PENDIENTE";
  if (!editable) return;

  latEdit = incActual.latitud_incidencia != null ? Number(incActual.latitud_incidencia) : null;
  lngEdit = incActual.longitud_incidencia != null ? Number(incActual.longitud_incidencia) : null;

  const btnEditar = document.getElementById("btnEditar");
  btnEditar.classList.remove("d-none");
  btnEditar.addEventListener("click", entrarEdicion);
  document.getElementById("btnCancelarEdicion").addEventListener("click", salirEdicion);
  document.getElementById("btnGuardarEdicion").addEventListener("click", guardarCambios);
  document.getElementById("datosEdicion").addEventListener("input", actualizarBotonGuardar);
}

// Activa en PENDIENTE y EN_PROCESO, independiente del modo edición de texto/ubicación
function prepararFotosReporte() {
  galeriaReporte = crearGaleriaFotos({
    input: document.getElementById("editFotos"),
    inputCamara: document.getElementById("editFotosCamara"),
    preview: document.getElementById("editFotosPreview"),
    error: document.getElementById("editFotosError"),
    cupo: cupoFotos,
    textoCupo: "Máximo 3 fotos en total.",
    btnSubir: document.getElementById("btnSubirFotos"),
    onSubir: function (archivos) {
      return subirFotosNuevas(archivos);
    },
  });

  // El carrusel del reporte gana el botón "×" (borra al instante) y "+ Agregar foto"
  // (abre el mismo selector de cámara/galería que alimenta la cola de subida de arriba).
  fijarOpcionesFotosReporte({
    onEliminar: eliminarFotoInmediata,
    onAgregar: function () {
      abrirSelectorFuenteFoto(
        document.getElementById("editFotosCamara"),
        document.getElementById("editFotos"),
      );
    },
    puedeAgregar: function () {
      return cupoFotos() > 0;
    },
  });

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
  catalogos.poblarSubtipos(idTipo);
  if (incActual.subtipo) {
    document.getElementById("editSubtipo").value = incActual.subtipo.id_subtipo_incidencia;
  }
  const ciudadActual = catalogos.estado.ciudades.find(function (c) {
    return c.id_ciudad === incActual.id_ciudad;
  });
  const idProvincia = ciudadActual ? ciudadActual.id_provincia : "";
  document.getElementById("editProvincia").value = idProvincia || "";
  catalogos.poblarCiudades(idProvincia);
  document.getElementById("editCiudad").value = incActual.id_ciudad || "";

  // Rol normal: provincia/ciudad se autocompletan al marcar en el mapa, así que se ocultan.
  const esAdmin = tienePermiso("incidencias.gestionar");
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

  pickerEdicion = activarMapaPicker(latEdit, lngEdit, function (lat, lng) {
    latEdit = lat;
    lngEdit = lng;
    catalogos.autocompletarUbicacion(lat, lng);
    mapaEditadoManualmente = true;
    actualizarBotonGuardar();
  });
  const controles = document.getElementById("mapaEditControles");
  controles.classList.remove("d-none");
  controles.classList.add("d-flex");
  document.getElementById("btnMiUbicacionEdit").onclick = function () {
    pickerEdicion.usarMiUbicacion();
  };

  // Foto de los valores originales: mientras el formulario coincida con ella, Guardar queda deshabilitado.
  mapaEditadoManualmente = false;
  snapshotEdicion = valoresFormularioEdicion();
  actualizarBotonGuardar();
}

// Valores actuales de los campos de texto/ubicación del formulario de edición.
function valoresFormularioEdicion() {
  return {
    titulo: document.getElementById("editTitulo").value,
    descripcion: document.getElementById("editDescripcion").value,
    tipo: document.getElementById("editTipo").value,
    subtipo: document.getElementById("editSubtipo").value,
    provincia: document.getElementById("editProvincia").value,
    ciudad: document.getElementById("editCiudad").value,
    direccion: document.getElementById("editDireccion").value,
  };
}

// El mapa se compara aparte (mapaEditadoManualmente) porque autocompletar provincia/ciudad
// desde el pin ya cambia esos selects, así que comparar el mapa por coordenadas sería redundante.
function hayCambiosEdicion() {
  if (!snapshotEdicion) return false;
  if (mapaEditadoManualmente) return true;
  const actual = valoresFormularioEdicion();
  return Object.keys(snapshotEdicion).some((clave) => snapshotEdicion[clave] !== actual[clave]);
}

// Guardar solo se habilita si algo realmente cambió respecto al snapshot inicial.
function actualizarBotonGuardar() {
  document.getElementById("btnGuardarEdicion").disabled = !hayCambiosEdicion();
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
  const controles = document.getElementById("mapaEditControles");
  controles.classList.add("d-none");
  controles.classList.remove("d-flex");
  destruirPickerEdicion();
  pintarMapaLectura();
  snapshotEdicion = null;
  mapaEditadoManualmente = false;
}

// Carga los catálogos en los selects de edición (solo la 1.ª vez); el helper cablea las cascadas.
async function cargarCatalogos() {
  if (catalogos.estado.tipos.length) return;
  try {
    await catalogos.cargar();
  } catch (error) {
    mostrarToast("No se pudieron cargar los catálogos: " + error.message, "error");
  }
}

// Evidencias del reporte que se conservan (las de tipo RESOLUCION no las toca el ciudadano).
function evidenciasReporte() {
  return (incActual.evidencias || []).filter((ev) => ev.tipo_evidencia !== "RESOLUCION");
}

// Elimina una foto del backend y actualiza el carrusel al instante.
async function eliminarFotoInmediata(idEv) {
  try {
    await apiFetch("/evidencias/" + idEv, { method: "DELETE" });
    incActual.evidencias = (incActual.evidencias || []).filter((ev) => ev.id_evidencia !== idEv);
    pintarFotos();
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
    pintarFotos();
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
  document.getElementById("detalleTipoTexto").textContent = tipo;
  document.getElementById("detalleSubtipoTexto").textContent = subtipo;

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
  } finally {
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
  mostrarAvisoPlazoReapertura();
}

// Aviso del plazo para pedir reapertura antes de que el sistema archive la incidencia.
// Las horas vienen del backend (Incidencia::HORAS_PARA_ARCHIVAR) para no desincronizar con el job.
function mostrarAvisoPlazoReapertura() {
  const horas = incActual.horas_para_archivar;
  if (!horas) return;

  const aviso = document.getElementById("avisoPlazoReapertura");
  const texto = document.getElementById("avisoPlazoReaperturaTexto");
  texto.textContent =
    "Tienes " +
    horas +
    " horas desde que se resolvió para solicitar la reapertura; luego se archiva automáticamente.";
  aviso.classList.remove("d-none");
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
  // Ya hay solicitud pendiente: el job no la archiva, así que el aviso del plazo deja de aplicar.
  document.getElementById("avisoPlazoReapertura").classList.add("d-none");
}

// Eliminar la incidencia completa.
