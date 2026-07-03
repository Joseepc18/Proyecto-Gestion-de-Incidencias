// detalle-incidencia-gestion.js — Herramientas de gestión (admin y técnico responsable)

/* exported gestionAlCargarDetalle, gestionAlCargarAsignaciones, gestionAsignacionesError */

// Lista de técnicos y últimas asignaciones cargadas (para poblar los selects sin refetch).
/* global apiFetch, mostrarToast, confirmar, crearGaleriaFotos, crearComboboxBuscable, estadoConfig, prioridadConfig, incActual, usuarioActual, esAdmin, esResponsableActual, pintarBadgeEstado, pintarBadgePrioridad, pintarFotos, cargarHistorial, cargarAsignaciones, iniciales */

let listaTecnicos = [];
let ultimasAsignaciones = [];
// Comboboxes buscables de Responsable/Ayudante (se crean una vez, en prepararAsignacion).
let comboResponsable = null;
let comboAyudante = null;

// Galería de fotos de resolución (gestionada por galeriaFotos.js).
let galeriaResolucion = null;

// En RESUELTO/CERRADO, prioridad/asignaciones/estado quedan de solo lectura para el admin
function gestionBloqueada() {
  return incActual.estado_incidencia === "RESUELTO" || incActual.estado_incidencia === "CERRADO";
}

// Hook del núcleo: al cargar el detalle. Revela y cablea lo del admin.
function gestionAlCargarDetalle(id) {
  if (!esAdmin) return;
  document.querySelectorAll(".solo-admin").forEach((el) => el.classList.remove("d-none"));
  prepararPrioridad(id);
  prepararAsignacion(id);
  habilitarGestionEstado(id);
  prepararReaperturaAdmin(id);
  prepararAtencionAdmin(id);
}

// Hook del núcleo: al cargar las asignaciones. Habilita al responsable y pinta las listas del admin.
function gestionAlCargarAsignaciones(asignaciones, id) {
  ultimasAsignaciones = asignaciones;

  if (esResponsableActual()) {
    habilitarGestionEstado(id);
    // En RESUELTO/CERRADO el responsable ya no sube/borra fotos (policy 403), solo las ve
    if (!gestionBloqueada()) {
      habilitarFotosResolucion(id);
    }
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
    if (!btn || btn.disabled) return;
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

// Pinta el botón de la prioridad actual con el mismo color que su badge
function marcarPrioridadActiva() {
  const bloqueada = gestionBloqueada();
  document.querySelectorAll("#prioridadBotones .btn-tool").forEach(function (b) {
    const cfg = prioridadConfig[b.dataset.prioridad];
    const activa = b.dataset.prioridad === incActual.prioridad_incidencia;
    Object.values(prioridadConfig).forEach((c) => b.classList.remove(...c.clase.split(" ")));
    b.classList.toggle("activa", activa);
    if (activa) b.classList.add(...cfg.clase.split(" "));
    // En RESUELTO la prioridad es de solo lectura (backend responde 403 igual).
    b.disabled = bloqueada;
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

// RESUELTO/CERRADO quedan bloqueados para todos: RESUELTO solo sale con "Reabrir"; CERRADO ya es definitivo.
function marcarEstadoActivo() {
  const actual = incActual.estado_incidencia;
  document.querySelectorAll("#estadoBotones .btn-estado-tool").forEach(function (b) {
    const estado = b.dataset.estado;
    const cfg = estadoConfig[estado];
    const activo = estado === actual;
    Object.values(estadoConfig).forEach((c) => b.classList.remove(c.clase));
    b.classList.toggle("activo", activo);
    if (activo) b.classList.add(cfg.clase);
    if (activo || actual === "RESUELTO" || actual === "CERRADO") {
      b.disabled = true;
    } else if (esAdmin) {
      b.disabled = false;
    } else {
      b.disabled = !(actual === "EN_PROCESO" && estado === "RESUELTO");
    }
  });
}

// El admin reabre solo si hay una solicitud del reportador sin revisar
function prepararReaperturaAdmin(id) {
  const btn = document.getElementById("btnReabrirIncidencia");
  if (!btn) return;
  btn.classList.toggle(
    "d-none",
    !(incActual.estado_incidencia === "RESUELTO" && incActual.reapertura_pendiente),
  );
  if (btn.dataset.cableado === "1") return;
  btn.dataset.cableado = "1";

  btn.addEventListener("click", async function () {
    const ok = await confirmar({
      titulo: "Reabrir incidencia",
      mensaje: "Volverá a EN_PROCESO y se notificará al reportador y al técnico.",
      textoConfirmar: "Reabrir",
    });
    if (!ok) return;

    btn.disabled = true;
    try {
      const actualizada = await apiFetch("/incidencias/" + id + "/estado", {
        method: "PATCH",
        body: JSON.stringify({ estado_incidencia: "EN_PROCESO" }),
      });
      incActual.estado_incidencia = actualizada.estado_incidencia;
      incActual.reapertura_pendiente = actualizada.reapertura_pendiente;
      pintarBadgeEstado(incActual.estado_incidencia);
      marcarEstadoActivo();
      btn.classList.add("d-none");
      cargarHistorial(id);
      mostrarToast("Incidencia reabierta", "success");
    } catch (error) {
      mostrarToast(error.message, "error");
    } finally {
      btn.disabled = false;
    }
  });
}

// Reclamar/Archivar: el primer admin que reclama queda como "Atendido por"; solo ese admin ve "Cerrar/Archivar".
function prepararAtencionAdmin(id) {
  pintarAtencionAdmin();

  const btnReclamar = document.getElementById("btnReclamarIncidencia");
  const btnArchivar = document.getElementById("btnArchivarIncidencia");
  if (btnReclamar.dataset.cableado === "1") return;
  btnReclamar.dataset.cableado = "1";
  btnArchivar.dataset.cableado = "1";

  btnReclamar.addEventListener("click", async function () {
    btnReclamar.disabled = true;
    try {
      const actualizada = await apiFetch("/incidencias/" + id + "/reclamar", { method: "POST" });
      incActual.id_admin_atiende = actualizada.id_admin_atiende;
      incActual.admin_atiende = actualizada.admin_atiende;
      pintarAtencionAdmin();
      mostrarToast("Incidencia reclamada", "success");
    } catch (error) {
      mostrarToast(error.message, "error");
    } finally {
      btnReclamar.disabled = false;
    }
  });

  btnArchivar.addEventListener("click", async function () {
    const ok = await confirmar({
      titulo: "Cerrar / Archivar incidencia",
      mensaje: "Pasará a Archivado y quedará de solo lectura para todos.",
      textoConfirmar: "Archivar",
    });
    if (!ok) return;

    btnArchivar.disabled = true;
    try {
      const actualizada = await apiFetch("/incidencias/" + id + "/archivar", { method: "PATCH" });
      incActual.estado_incidencia = actualizada.estado_incidencia;
      pintarBadgeEstado(incActual.estado_incidencia);
      marcarEstadoActivo();
      pintarAtencionAdmin();
      cargarHistorial(id);
      mostrarToast("Incidencia archivada", "success");
    } catch (error) {
      mostrarToast(error.message, "error");
    } finally {
      btnArchivar.disabled = false;
    }
  });
}

// Pinta el texto "Atendida por" y decide qué botón mostrar (Reclamar / Archivar / ninguno).
function pintarAtencionAdmin() {
  const info = document.getElementById("atencionAdminInfo");
  const btnReclamar = document.getElementById("btnReclamarIncidencia");
  const btnArchivar = document.getElementById("btnArchivarIncidencia");
  const admin = incActual.admin_atiende;

  if (!admin) {
    info.textContent = "Sin reclamar.";
    btnReclamar.classList.remove("d-none");
    btnArchivar.classList.add("d-none");
    return;
  }

  const soyYo = usuarioActual && admin.id === usuarioActual.id;
  info.textContent = "Atendida por: " + admin.name + (soyYo ? " (tú)" : "");
  btnReclamar.classList.add("d-none");
  btnArchivar.classList.toggle("d-none", !(soyYo && incActual.estado_incidencia === "RESUELTO"));
}

let gestionFotosLista = false;
function habilitarFotosResolucion(id) {
  if (gestionFotosLista) return;
  gestionFotosLista = true;
  document.querySelectorAll(".gestion-fotos").forEach((el) => el.classList.remove("d-none"));
  prepararSubidaResolucion(id);
}

function prepararSubidaResolucion(id) {
  galeriaResolucion = crearGaleriaFotos({
    input: document.getElementById("inputResolucion"),
    dropzone: document.getElementById("dropzoneResolucion"),
    preview: document.getElementById("resolucionPreview"),
    error: document.getElementById("resolucionError"),
    cupo: cupoResolucion,
    textoCupo: "Máximo 3 fotos de resolución.",
    btnSubir: document.getElementById("btnSubirResolucion"),
    onSubir: function (archivos) {
      return subirResolucion(id, archivos);
    },
  });
}

// Cupo de fotos de resolución que aún se pueden subir (máx. 3 en total).
function cupoResolucion() {
  const existentes = (incActual.evidencias || []).filter(
    (ev) => ev.tipo_evidencia === "RESOLUCION",
  ).length;
  return 3 - existentes;
}

// Sube las fotos al backend (tipo RESOLUCION) y repinta las galerías con la respuesta.
async function subirResolucion(id, archivos) {
  const btnSubir = document.getElementById("btnSubirResolucion");
  const spinner = document.getElementById("resolucionSpinner");
  btnSubir.disabled = true;
  spinner.classList.remove("d-none");

  try {
    const formData = new FormData();
    archivos.forEach(function (file) {
      formData.append("fotos[]", file);
    });
    formData.append("tipo_evidencia", "RESOLUCION");

    const actualizada = await apiFetch("/incidencias/" + id + "/evidencias", {
      method: "POST",
      body: formData,
    });
    incActual.evidencias = actualizada.evidencias || [];
    pintarFotos();
    galeriaResolucion.limpiar();
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

  // En RESUELTO las asignaciones quedan congeladas: se ocultan los formularios de agregar
  const bloqueada = gestionBloqueada();
  const formResp = document.getElementById("responsableForm");
  if (formResp) formResp.classList.toggle("d-none", bloqueada || !!responsable);
  const formAyu = document.getElementById("ayudanteForm");
  if (formAyu) formAyu.classList.toggle("d-none", bloqueada);
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

  // En RESUELTO no se puede quitar (asignaciones congeladas): sin botón de quitar.
  if (gestionBloqueada()) return fila;

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

  comboResponsable = crearComboboxBuscable("selectResponsable", "comboResponsable");
  comboAyudante = crearComboboxBuscable("selectAyudante", "comboAyudante");

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
  // El select siempre vuelve al placeholder tras repoblar: refleja lo mismo en los combobox.
  if (comboResponsable) comboResponsable.resetear();
  if (comboAyudante) comboAyudante.resetear();
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
