// detalle-incidencia-gestion.js — Herramientas de gestión (admin y técnico responsable).
// El admin: prioridad, asignación de técnicos y cambio de estado libre.
// El técnico responsable: cambio de estado EN_PROCESO→RESUELTO y fotos de la resolución.
// El núcleo (detalle-incidencia.js) llama a los hooks gestion* cuando hay datos.

/* exported gestionAlCargarDetalle, gestionAlCargarAsignaciones, gestionAsignacionesError */

// Lista de técnicos y últimas asignaciones cargadas (para poblar los selects sin refetch).
/* global apiFetch, mostrarToast, confirmar, imageCompression, opcionesCompresion, incActual, esAdmin, esResponsableActual, pintarBadgeEstado, pintarBadgePrioridad, pintarFotos, cargarHistorial, cargarAsignaciones, iniciales */

let listaTecnicos = [];
let ultimasAsignaciones = [];

// Fotos de resolución ya comprimidas, pendientes de subir.
let fotosResolucion = [];

// Hook del núcleo: al cargar el detalle. Revela y cablea lo del admin.
function gestionAlCargarDetalle(id) {
  if (!esAdmin) return;
  document.querySelectorAll(".solo-admin").forEach((el) => el.classList.remove("d-none"));
  prepararPrioridad(id);
  prepararAsignacion(id);
  habilitarGestionEstado(id);
}

// Hook del núcleo: al cargar las asignaciones. Habilita al responsable y pinta las listas del admin.
function gestionAlCargarAsignaciones(asignaciones, id) {
  ultimasAsignaciones = asignaciones;

  if (esResponsableActual()) {
    habilitarGestionEstado(id);
    habilitarFotosResolucion(id);
  }

  if (!esAdmin) return;
  renderAsignaciones(asignaciones, id);
  refrescarSelectsTecnicos();
}

// Hook del núcleo: si falló la carga de asignaciones.
function gestionAsignacionesError() {
  const responsableLista = document.getElementById("responsableLista");
  if (responsableLista) {
    responsableLista.innerHTML =
      '<p class="text-danger small mb-0">No se pudieron cargar las asignaciones.</p>';
  }
}

function prepararPrioridad(id) {
  const cont = document.getElementById("prioridadBotones");
  marcarPrioridadActiva();

  cont.addEventListener("click", async function (e) {
    const btn = e.target.closest("[data-prioridad]");
    if (!btn) return;
    const nueva = btn.dataset.prioridad;
    if (nueva === incActual.prioridad_incidencia) return;

    cont.querySelectorAll(".btn-tool").forEach((b) => (b.disabled = true));
    try {
      await apiFetch("/incidencias/" + id, {
        method: "PUT",
        body: JSON.stringify({ prioridad_incidencia: nueva }),
      });
      incActual.prioridad_incidencia = nueva;
      pintarBadgePrioridad(nueva);
      marcarPrioridadActiva();
      mostrarToast("Prioridad actualizada", "success");
    } catch (error) {
      mostrarToast(error.message, "error");
    } finally {
      cont.querySelectorAll(".btn-tool").forEach((b) => (b.disabled = false));
    }
  });
}

// Resalta el botón de la prioridad actual.
function marcarPrioridadActiva() {
  document.querySelectorAll("#prioridadBotones .btn-tool").forEach(function (b) {
    const activa = b.dataset.prioridad === incActual.prioridad_incidencia;
    b.classList.toggle("activa", activa);
    b.classList.toggle("activa-" + b.dataset.prioridad.toLowerCase(), activa);
  });
}

let gestionEstadoLista = false;
function habilitarGestionEstado(id) {
  if (gestionEstadoLista) return;
  gestionEstadoLista = true;
  document.querySelectorAll(".gestion-estado").forEach((el) => el.classList.remove("d-none"));
  prepararEstado(id);
}

function prepararEstado(id) {
  const cont = document.getElementById("estadoBotones");
  marcarEstadoActivo();

  cont.addEventListener("click", async function (e) {
    const btn = e.target.closest("[data-estado]");
    if (!btn || btn.disabled) return;
    const nuevo = btn.dataset.estado;
    if (nuevo === incActual.estado_incidencia) return;

    if (nuevo === "RESUELTO") {
      const ok = await confirmar({
        titulo: "Marcar como resuelto",
        mensaje: "Se registrará la fecha de resolución y se notificará a los involucrados.",
        textoConfirmar: "Resolver",
      });
      if (!ok) return;
    }

    cont.querySelectorAll(".btn-estado-tool").forEach((b) => (b.disabled = true));
    try {
      const actualizada = await apiFetch("/incidencias/" + id + "/estado", {
        method: "PATCH",
        body: JSON.stringify({ estado_incidencia: nuevo }),
      });
      incActual.estado_incidencia = actualizada.estado_incidencia;
      pintarBadgeEstado(incActual.estado_incidencia);
      marcarEstadoActivo();
      cargarHistorial(id);
      mostrarToast("Estado actualizado", "success");
    } catch (error) {
      mostrarToast(error.message, "error");
      marcarEstadoActivo();
    }
  });
}

// Resalta el estado actual; el admin habilita cualquier otro, el técnico solo EN_PROCESO → RESUELTO.
function marcarEstadoActivo() {
  const actual = incActual.estado_incidencia;
  document.querySelectorAll("#estadoBotones .btn-estado-tool").forEach(function (b) {
    const estado = b.dataset.estado;
    const activo = estado === actual;
    b.classList.toggle("activo", activo);
    if (activo) {
      b.disabled = true;
    } else if (esAdmin) {
      b.disabled = false;
    } else {
      b.disabled = !(actual === "EN_PROCESO" && estado === "RESUELTO");
    }
  });
}

let gestionFotosLista = false;
function habilitarFotosResolucion(id) {
  if (gestionFotosLista) return;
  gestionFotosLista = true;
  document.querySelectorAll(".gestion-fotos").forEach((el) => el.classList.remove("d-none"));
  prepararSubidaResolucion(id);
}

function prepararSubidaResolucion(id) {
  const input = document.getElementById("inputResolucion");
  const dropzone = document.getElementById("dropzoneResolucion");

  input.addEventListener("change", function () {
    procesarFotosResolucion(this.files);
    this.value = "";
  });

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
    procesarFotosResolucion(e.dataTransfer.files);
  });

  document.getElementById("btnSubirResolucion").addEventListener("click", function () {
    subirResolucion(id);
  });

  renderResolucionPreview();
}

// Cupo de fotos de resolución que aún se pueden subir (máx. 3 en total).
function cupoResolucion() {
  const existentes = (incActual.evidencias || []).filter(
    (ev) => ev.tipo_evidencia === "RESOLUCION",
  ).length;
  return 3 - existentes;
}

// Comprime cada foto y la agrega al acumulador, sin pasar del cupo.
async function procesarFotosResolucion(lista) {
  const error = document.getElementById("resolucionError");
  error.classList.add("d-none");
  for (const file of Array.from(lista)) {
    if (fotosResolucion.length >= cupoResolucion()) {
      error.textContent = "Máximo 3 fotos de resolución.";
      error.classList.remove("d-none");
      break;
    }
    try {
      const comprimida = await imageCompression(file, opcionesCompresion);
      const jpg = new File([comprimida], file.name.replace(/\.\w+$/, ".jpg"), {
        type: "image/jpeg",
      });
      fotosResolucion.push(jpg);
    } catch {
      error.textContent = 'No se pudo procesar "' + file.name + '".';
      error.classList.remove("d-none");
    }
  }
  renderResolucionPreview();
}

// Dibuja las miniaturas de las fotos elegidas (con botón para quitarlas).
function renderResolucionPreview() {
  const preview = document.getElementById("resolucionPreview");
  const dropzone = document.getElementById("dropzoneResolucion");
  const input = document.getElementById("inputResolucion");
  const btnSubir = document.getElementById("btnSubirResolucion");
  preview.innerHTML = "";

  const cupo = cupoResolucion();
  dropzone.classList.toggle("d-none", fotosResolucion.length > 0 || cupo <= 0);

  fotosResolucion.forEach(function (file, idx) {
    const cont = document.createElement("div");
    cont.className = "position-relative";

    const img = document.createElement("img");
    img.loading = "lazy";
    const url = URL.createObjectURL(file);
    img.onload = () => URL.revokeObjectURL(url);
    img.src = url;
    img.style.cssText = "width:80px;height:80px;object-fit:cover;border-radius:8px";

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "btn btn-danger btn-sm position-absolute top-0 end-0 py-0 px-1";
    btn.innerHTML = "&times;";
    btn.addEventListener("click", function () {
      fotosResolucion.splice(idx, 1);
      renderResolucionPreview();
    });

    cont.appendChild(img);
    cont.appendChild(btn);
    preview.appendChild(cont);
  });

  if (fotosResolucion.length > 0 && fotosResolucion.length < cupo) {
    const agregar = document.createElement("button");
    agregar.type = "button";
    agregar.className = "foto-agregar";
    agregar.innerHTML = '<i class="bi bi-plus-lg" aria-hidden="true"></i>';
    agregar.addEventListener("click", function () {
      input.click();
    });
    preview.appendChild(agregar);
  }

  btnSubir.classList.toggle("d-none", fotosResolucion.length === 0);
}

// Sube las fotos al backend (tipo RESOLUCION) y repinta las galerías con la respuesta.
async function subirResolucion(id) {
  if (fotosResolucion.length === 0) return;

  const btnSubir = document.getElementById("btnSubirResolucion");
  const spinner = document.getElementById("resolucionSpinner");
  btnSubir.disabled = true;
  spinner.classList.remove("d-none");

  try {
    const formData = new FormData();
    fotosResolucion.forEach(function (file) {
      formData.append("fotos[]", file);
    });
    formData.append("tipo_evidencia", "RESOLUCION");

    const actualizada = await apiFetch("/incidencias/" + id + "/evidencias", {
      method: "POST",
      body: formData,
    });
    incActual.evidencias = actualizada.evidencias || [];
    pintarFotos();
    fotosResolucion = [];
    renderResolucionPreview();
    mostrarToast("Fotos de resolución subidas", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  } finally {
    btnSubir.disabled = false;
    spinner.classList.add("d-none");
  }
}

// Pinta las listas de responsable y ayudantes a partir de las asignaciones.
function renderAsignaciones(asignaciones, id) {
  const responsableLista = document.getElementById("responsableLista");
  const ayudantesLista = document.getElementById("ayudantesLista");
  const responsable = asignaciones.find((a) => a.rol_asignado === "RESPONSABLE");
  const ayudantes = asignaciones.filter((a) => a.rol_asignado === "APOYO");

  responsableLista.innerHTML = "";
  if (responsable) {
    responsableLista.appendChild(filaTecnico(responsable, id, "primary"));
  } else {
    responsableLista.innerHTML = '<p class="text-muted small mb-0">Sin responsable asignado.</p>';
  }

  ayudantesLista.innerHTML = "";
  if (ayudantes.length) {
    ayudantes.forEach((a) => ayudantesLista.appendChild(filaTecnico(a, id, "secondary")));
  } else {
    ayudantesLista.innerHTML = '<p class="text-muted small mb-0">Sin ayudantes asignados.</p>';
  }

  const formResp = document.getElementById("responsableForm");
  if (formResp) formResp.classList.toggle("d-none", !!responsable);
}

// Fila visual de un técnico asignado (avatar + nombre + quitar).
function filaTecnico(asig, idIncidencia, color) {
  const fila = document.createElement("div");
  fila.className = "tecnico-fila";

  const avatar = document.createElement("div");
  avatar.className = "tecnico-avatar tecnico-avatar-" + color;
  avatar.textContent = iniciales(asig.usuario ? asig.usuario.name : "");
  fila.appendChild(avatar);

  const nombre = document.createElement("span");
  nombre.className = "tecnico-nombre";
  nombre.textContent = asig.usuario ? asig.usuario.name : "—";
  fila.appendChild(nombre);

  const btn = document.createElement("button");
  btn.type = "button";
  btn.className = "btn btn-sm btn-outline-danger ms-auto";
  btn.innerHTML = '<i class="bi bi-x-lg"></i>';
  btn.addEventListener("click", function () {
    quitarAsignacion(asig.id_asignacion, idIncidencia);
  });
  fila.appendChild(btn);

  return fila;
}

async function quitarAsignacion(idAsignacion, idIncidencia) {
  const ok = await confirmar({
    titulo: "¿Quitar asignación?",
    mensaje: "El técnico dejará de estar asignado a esta incidencia.",
    textoConfirmar: "Quitar",
    peligro: true,
  });
  if (!ok) return;

  try {
    await apiFetch("/asignaciones/" + idAsignacion, { method: "DELETE" });
    await cargarAsignaciones(idIncidencia);
    mostrarToast("Asignación eliminada", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  }
}

async function prepararAsignacion(id) {
  try {
    listaTecnicos = await apiFetch("/tecnicos");
  } catch {
    mostrarToast("No se pudieron cargar los técnicos", "error");
    return;
  }
  refrescarSelectsTecnicos();

  document.getElementById("btnAsignarResponsable").addEventListener("click", function () {
    asignar(id, "selectResponsable", "RESPONSABLE");
  });
  document.getElementById("btnAsignarAyudante").addEventListener("click", function () {
    asignar(id, "selectAyudante", "APOYO");
  });

  document.getElementById("selectResponsable").addEventListener("change", sincronizarSelects);
  document.getElementById("selectAyudante").addEventListener("change", sincronizarSelects);
}

// Rellena ambos selects con los técnicos que aún no están asignados (sin refetch).
function refrescarSelectsTecnicos() {
  const asignados = ultimasAsignaciones.map((a) => (a.usuario ? a.usuario.id : null));
  const disponibles = listaTecnicos.filter((t) => !asignados.includes(t.id));
  ["selectResponsable", "selectAyudante"].forEach(function (idSelect) {
    const select = document.getElementById(idSelect);
    if (!select) return;
    select.innerHTML = '<option value="">Selecciona un técnico…</option>';
    disponibles.forEach(function (t) {
      const op = document.createElement("option");
      op.value = String(t.id);
      op.textContent = t.name;
      select.appendChild(op);
    });
  });
  sincronizarSelects();
}

// Oculta en cada select al técnico ya elegido en el otro.
function sincronizarSelects() {
  const resp = document.getElementById("selectResponsable");
  const ayu = document.getElementById("selectAyudante");
  if (!resp || !ayu) return;
  excluirOpcion(ayu, resp.value);
  excluirOpcion(resp, ayu.value);
}

function excluirOpcion(select, valorExcluir) {
  Array.from(select.options).forEach(function (op) {
    if (!op.value) return;
    const ocultar = valorExcluir !== "" && op.value === valorExcluir;
    op.hidden = ocultar;
    op.disabled = ocultar;
  });
}

async function asignar(id, idSelect, rol) {
  const select = document.getElementById(idSelect);
  if (!select.value) {
    mostrarToast("Selecciona un técnico", "warning");
    return;
  }

  try {
    await apiFetch("/incidencias/" + id + "/asignaciones", {
      method: "POST",
      body: JSON.stringify({ id_usuario: select.value, rol_asignado: rol }),
    });
    await cargarAsignaciones(id);
    mostrarToast("Técnico asignado", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  }
}
