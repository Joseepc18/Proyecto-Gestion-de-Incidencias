// detalle.js — Página de detalle de una incidencia del usuario (ver + editar + eliminar).

/* global apiFetch, obtenerToken, eliminarToken, aplicarMenuRol, mostrarToast, toastFlash, crearMapaIncidencias, crearMapaPicker, crearChat, confirmar, imageCompression */

const estadoConfig = {
  PENDIENTE: { clase: "badge-estado-pendiente", icono: "bi-clock-history", texto: "Pendiente" },
  EN_PROCESO: {
    clase: "badge-estado-proceso",
    icono: "bi-gear-wide-connected",
    texto: "En proceso",
  },
  RESUELTO: { clase: "badge-estado-resuelto", icono: "bi-check2-circle", texto: "Resuelto" },
};
const prioridadConfig = {
  ALTA: { clase: "text-bg-danger", icono: "bi-fire", texto: "Alta" },
  MEDIA: { clase: "text-bg-warning", icono: "bi-shield-exclamation", texto: "Media" },
  BAJA: { clase: "text-bg-success", icono: "bi-arrow-down-circle", texto: "Baja" },
};

// Estado de la página (se llena al cargar el detalle).
let incActual = null;
let usuarioActual = null;
let catalogoTipos = [];
let mapaVista = null; // instancia de solo-lectura (crearMapaIncidencias)
let picker = null; // instancia del modo edición (crearMapaPicker)
let latEdit = null;
let lngEdit = null;
const fotosNuevas = []; // fotos comprimidas pendientes de subir
const evidenciasAEliminar = new Set(); // ids de evidencias marcadas para borrar

// Compresión: redimensiona a ~1920px y calidad 0.8 antes de subir
const opcionesCompresion = {
  maxSizeMB: 0.5,
  maxWidthOrHeight: 1920,
  useWebWorker: true,
  fileType: "image/jpeg",
  initialQuality: 0.8,
};

function codigoIncidencia(id) {
  return "INC-" + String(id).padStart(4, "0");
}

document.addEventListener("DOMContentLoaded", async function () {
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

  // El id viene en la URL (?id=). Sin id, de vuelta al mapa.
  const id = new URLSearchParams(window.location.search).get("id");
  if (!id) {
    window.location.replace("../misIncidencias/misIncidencias.html");
    return;
  }

  // Usuario (navbar + menú por rol + chat)
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
    try {
      await apiFetch("/logout", { method: "POST" });
    } catch {
      /* ignorar */
    }
    eliminarToken();
    window.location.href = "../login/login.html";
  });

  // Botones de edición / eliminación
  document.getElementById("btnEditar").addEventListener("click", entrarEdicion);
  document.getElementById("btnCancelarEdicion").addEventListener("click", function () {
    window.location.reload();
  });
  document.getElementById("btnGuardarEdicion").addEventListener("click", guardarCambios);
  document.getElementById("btnEliminar").addEventListener("click", eliminarIncidencia);

  // Fotos nuevas: input + arrastrar/soltar (igual que registrar)
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

  await cargarDetalle(id);
});

async function cargarDetalle(id) {
  try {
    const inc = await apiFetch("/incidencias/" + id);
    incActual = inc;
    latEdit = inc.latitud_incidencia != null ? Number(inc.latitud_incidencia) : null;
    lngEdit = inc.longitud_incidencia != null ? Number(inc.longitud_incidencia) : null;

    document.getElementById("detalleCodigo").textContent = codigoIncidencia(inc.id_incidencia);
    document.getElementById("detalleTitulo").textContent = inc.nombre_incidencia;
    document.getElementById("detalleDescripcion").textContent =
      inc.descripcion_incidencia || "Sin descripción.";

    // Estado
    const est = estadoConfig[inc.estado_incidencia] || {
      clase: "",
      icono: "",
      texto: inc.estado_incidencia,
    };
    const spanEstado = document.getElementById("detalleEstado");
    spanEstado.className = "badge " + est.clase;
    spanEstado.innerHTML = '<i class="bi ' + est.icono + ' me-1"></i>' + est.texto;

    // Prioridad
    const pri = prioridadConfig[inc.prioridad_incidencia] || {
      clase: "",
      icono: "",
      texto: inc.prioridad_incidencia,
    };
    const spanPri = document.getElementById("detallePrioridad");
    spanPri.className = "badge " + pri.clase;
    spanPri.innerHTML = '<i class="bi ' + pri.icono + ' me-1"></i>' + pri.texto;

    // Datos
    const tipo = inc.subtipo && inc.subtipo.tipo ? inc.subtipo.tipo.nombre_tipo_incidencia : "—";
    const subtipo = inc.subtipo ? inc.subtipo.nombre_subtipo_incidencia : "—";
    document.getElementById("detalleTipoSubtipo").textContent = tipo + " / " + subtipo;

    const ciudad = inc.ciudad ? inc.ciudad.nombre_ciudad : "Sin ciudad";
    const direccion = inc.direccion_incidencia ? " — " + inc.direccion_incidencia : "";
    document.getElementById("detalleUbicacion").textContent = ciudad + direccion;

    document.getElementById("detalleFecha").textContent = new Date(inc.created_at).toLocaleString(
      "es-EC",
    );

    // Evidencias (solo lectura)
    renderEvidenciasVista();

    // Mostrar contenido
    document.getElementById("detalleCargando").classList.add("d-none");
    document.getElementById("detalleContenido").classList.remove("d-none");

    // Editar/Eliminar: solo el autor y mientras esté PENDIENTE
    const esAutor = inc.id_usuario === usuarioActual.id;
    const editable = esAutor && inc.estado_incidencia === "PENDIENTE";
    document.getElementById("btnEditar").classList.toggle("d-none", !editable);
    document.getElementById("btnEliminar").classList.toggle("d-none", !editable);

    // Responsable (a partir de las asignaciones)
    cargarResponsable(id);

    // Mapa con el pin de la incidencia
    if (latEdit != null && lngEdit != null) {
      mapaVista = crearMapaIncidencias("mapaDetalle");
      setTimeout(function () {
        mapaVista.map.invalidateSize();
        mapaVista.pintarPines([
          {
            id: inc.id_incidencia,
            lat: latEdit,
            lng: lngEdit,
            titulo: codigoIncidencia(inc.id_incidencia) + " — " + inc.nombre_incidencia,
            color: "#2563eb",
          },
        ]);
      }, 200);
    } else {
      document.getElementById("mapaDetalle").innerHTML =
        '<p class="text-muted small p-3 mb-0">Esta incidencia no tiene ubicación.</p>';
    }

    // Chat (reutilizable)
    crearChat("chatContenedor", id, usuarioActual);
  } catch (error) {
    document.getElementById("detalleCargando").classList.add("d-none");
    mostrarToast("No se pudo cargar la incidencia: " + error.message, "error");
  }
}

// Muestra el técnico responsable (si lo hay) en la ficha de datos.
async function cargarResponsable(id) {
  const cont = document.getElementById("detalleResponsable");
  try {
    const asignaciones = await apiFetch("/incidencias/" + id + "/asignaciones");
    const responsable = asignaciones.find(function (a) {
      return a.rol_asignado === "RESPONSABLE";
    });
    cont.textContent =
      responsable && responsable.usuario ? responsable.usuario.name : "Sin asignar";
  } catch {
    cont.textContent = "—";
  }
}

// Pinta las evidencias en modo solo lectura (con lightbox).
function renderEvidenciasVista() {
  const cont = document.getElementById("detalleEvidencias");
  const evidencias = incActual.evidencias || [];
  if (evidencias.length === 0) {
    cont.innerHTML = '<p class="text-muted small mb-0">Sin evidencias cargadas.</p>';
    return;
  }
  cont.innerHTML = evidencias
    .map(function (ev) {
      return (
        '<img src="/storage/' +
        ev.url_evidencia +
        '" class="evidencia-foto rounded" data-lightbox="/storage/' +
        ev.url_evidencia +
        '" style="width:110px;height:110px;object-fit:cover" alt="Evidencia" />'
      );
    })
    .join("");
}

// Modo edición

async function entrarEdicion() {
  // Catálogos (una sola vez) y selección de los valores actuales
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
  document.getElementById("editCiudad").value = incActual.id_ciudad || "";

  // Alternar vista → edición
  document.getElementById("datosVista").classList.add("d-none");
  document.getElementById("datosEdicion").classList.remove("d-none");
  document.getElementById("edicionAcciones").classList.remove("d-none");
  document.getElementById("btnEditar").classList.add("d-none");
  document.getElementById("btnEliminar").classList.add("d-none");

  // Mapa: pasar de solo-lectura a selector
  activarPicker();

  // Evidencias: editables (borrar viejas) + dropzone (subir nuevas)
  renderEvidenciasEdit();
  renderFotosNuevas();
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

    const ciudades = await apiFetch("/catalogos/ciudades");
    const selectCiudad = document.getElementById("editCiudad");
    ciudades.forEach(function (c) {
      const op = document.createElement("option");
      op.value = c.id_ciudad;
      op.textContent = c.nombre_ciudad;
      selectCiudad.appendChild(op);
    });

    // Cascada tipo → subtipo
    selectTipo.addEventListener("change", function () {
      poblarSubtipos(parseInt(this.value));
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

// Convierte el mapa de solo-lectura en un selector de ubicación.
function activarPicker() {
  const cont = document.getElementById("mapaDetalle");
  if (mapaVista) {
    mapaVista.map.remove();
    mapaVista = null;
  }
  cont.innerHTML = "";

  picker = crearMapaPicker("mapaDetalle", function (lat, lng) {
    latEdit = lat;
    lngEdit = lng;
  });
  setTimeout(function () {
    picker.map.invalidateSize();
    if (latEdit != null && lngEdit != null) picker.setUbicacion(latEdit, lngEdit);
  }, 200);

  document.getElementById("mapaEditControles").classList.remove("d-none");
  document.getElementById("mapaEditControles").classList.add("d-flex");
  document.getElementById("btnMiUbicacionEdit").onclick = function () {
    picker.usarMiUbicacion();
  };
}

// Cuántas evidencias se conservan (las que no están marcadas para borrar).
function evidenciasConservadas() {
  return (incActual.evidencias || []).filter(function (ev) {
    return !evidenciasAEliminar.has(ev.id_evidencia);
  });
}

// Evidencias en modo edición: cada una con una "×" para marcarla y borrarla al guardar.
function renderEvidenciasEdit() {
  const cont = document.getElementById("detalleEvidencias");
  cont.innerHTML = "";
  const conservadas = evidenciasConservadas();
  if (conservadas.length === 0) {
    cont.innerHTML = '<p class="text-muted small mb-0">Sin evidencias.</p>';
  }
  conservadas.forEach(function (ev) {
    const wrap = document.createElement("div");
    wrap.className = "position-relative";

    const img = document.createElement("img");
    img.src = "/storage/" + ev.url_evidencia;
    img.className = "evidencia-foto rounded";
    img.style.cssText = "width:110px;height:110px;object-fit:cover";
    img.alt = "Evidencia";

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "btn btn-danger btn-sm position-absolute top-0 end-0 py-0 px-1";
    btn.innerHTML = "&times;";
    btn.addEventListener("click", function () {
      evidenciasAEliminar.add(ev.id_evidencia);
      renderEvidenciasEdit();
      renderFotosNuevas(); // el cupo disponible cambió
    });

    wrap.appendChild(img);
    wrap.appendChild(btn);
    cont.appendChild(wrap);
  });
  document.getElementById("evidenciasEdicion").classList.remove("d-none");
}

// Cupo de fotos nuevas que aún se pueden subir (máx. 3 en total).
function cupoFotos() {
  return 3 - evidenciasConservadas().length;
}

// Comprime cada foto y la agrega al acumulador, sin pasar del cupo.
async function procesarFotos(lista) {
  const errorFotos = document.getElementById("editFotosError");
  errorFotos.classList.add("d-none");
  for (const file of Array.from(lista)) {
    if (fotosNuevas.length >= cupoFotos()) {
      errorFotos.textContent = "Máximo 3 fotos en total.";
      errorFotos.classList.remove("d-none");
      break;
    }
    try {
      const comprimida = await imageCompression(file, opcionesCompresion);
      const jpg = new File([comprimida], file.name.replace(/\.\w+$/, ".jpg"), {
        type: "image/jpeg",
      });
      fotosNuevas.push(jpg);
    } catch {
      errorFotos.textContent = 'No se pudo procesar "' + file.name + '".';
      errorFotos.classList.remove("d-none");
    }
  }
  renderFotosNuevas();
}

// Dibuja las miniaturas de las fotos nuevas (con botón para quitarlas).
function renderFotosNuevas() {
  const preview = document.getElementById("editFotosPreview");
  const dropzone = document.getElementById("dropzoneFotos");
  const inputFotos = document.getElementById("editFotos");
  preview.innerHTML = "";

  const cupo = cupoFotos();
  // El dropzone grande se oculta si ya hay fotos nuevas o si no queda cupo
  dropzone.classList.toggle("d-none", fotosNuevas.length > 0 || cupo <= 0);

  fotosNuevas.forEach(function (file, idx) {
    const cont = document.createElement("div");
    cont.className = "position-relative";

    const img = document.createElement("img");
    img.src = URL.createObjectURL(file);
    img.style.cssText = "width:80px;height:80px;object-fit:cover;border-radius:8px";

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "btn btn-danger btn-sm position-absolute top-0 end-0 py-0 px-1";
    btn.innerHTML = "&times;";
    btn.addEventListener("click", function () {
      fotosNuevas.splice(idx, 1);
      renderFotosNuevas();
    });

    cont.appendChild(img);
    cont.appendChild(btn);
    preview.appendChild(cont);
  });

  // Azulejo "+" para seguir agregando mientras quede cupo
  if (fotosNuevas.length > 0 && fotosNuevas.length < cupo) {
    const agregar = document.createElement("button");
    agregar.type = "button";
    agregar.className = "foto-agregar";
    agregar.innerHTML = '<i class="bi bi-plus-lg" aria-hidden="true"></i>';
    agregar.addEventListener("click", function () {
      inputFotos.click();
    });
    preview.appendChild(agregar);
  }
}

// Guarda: datos (PUT) → borra evidencias marcadas → sube las nuevas.
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
    // 1) Datos descriptivos + ubicación
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

    // 2) Borrar las evidencias marcadas
    for (const idEv of evidenciasAEliminar) {
      await apiFetch("/evidencias/" + idEv, { method: "DELETE" });
    }

    // 3) Subir las fotos nuevas
    if (fotosNuevas.length > 0) {
      const formData = new FormData();
      fotosNuevas.forEach(function (file) {
        formData.append("fotos[]", file);
      });
      formData.append("tipo_evidencia", "REPORTE");
      await apiFetch("/incidencias/" + incActual.id_incidencia + "/evidencias", {
        method: "POST",
        body: formData,
      });
    }

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
    window.location.href = "../misIncidencias/misIncidencias.html";
  } catch (error) {
    mostrarToast("No se pudo eliminar: " + error.message, "error");
  }
}
