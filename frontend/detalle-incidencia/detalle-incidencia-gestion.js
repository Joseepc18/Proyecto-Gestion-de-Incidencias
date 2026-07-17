// detalle-incidencia-gestion.js — Herramientas de gestión (admin y técnico responsable)

/* exported gestionAlCargarDetalle, gestionAlCargarAsignaciones, gestionAsignacionesError, gestionAlActualizarEnVivo, gestionAlCambiarReclamo, soyDuenoDelReclamo */

// Lista de técnicos y últimas asignaciones cargadas (para poblar los selects sin refetch).
/* global apiFetch, mostrarToast, confirmar, crearGaleriaFotos, abrirSelectorFuenteFoto, crearComboboxBuscable, crearCatalogosIncidencia, estadoConfig, prioridadConfig, incActual, usuarioActual, esAdmin, esResponsableActual, pintarBadgeEstado, pintarBadgePrioridad, pintarFotos, fijarOpcionesFotosResolucion, pintarMetaAdminAtiende, cargarHistorial, cargarAsignaciones, iniciales */

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
  prepararReclasificacion(id);
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
      pintarBadgePrioridad(nueva, true);
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
    // Solo lectura en RESUELTO, o si el admin aún no reclamó la incidencia (backend responde 403 igual).
    b.disabled = bloqueada || !soyDuenoDelReclamo();
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
      pintarBadgeEstado(incActual.estado_incidencia, true);
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
      // El admin solo cambia el estado si reclamó la incidencia (mismo candado que el backend).
      b.disabled = !soyDuenoDelReclamo();
    } else {
      b.disabled = !(actual === "EN_PROCESO" && estado === "RESUELTO");
    }
  });
}

// El admin reabre o rechaza, solo si hay una solicitud del reportador sin revisar
function prepararReaperturaAdmin(id) {
  const hayPendiente = incActual.estado_incidencia === "RESUELTO" && incActual.reapertura_pendiente;

  const btnReabrir = document.getElementById("btnReabrirIncidencia");
  const btnRechazar = document.getElementById("btnRechazarReapertura");
  if (!btnReabrir || !btnRechazar) return;

  btnReabrir.classList.toggle("d-none", !hayPendiente);
  btnRechazar.classList.toggle("d-none", !hayPendiente);

  if (btnReabrir.dataset.cableado !== "1") {
    btnReabrir.dataset.cableado = "1";
    btnReabrir.addEventListener("click", async function () {
      const ok = await confirmar({
        titulo: "Reabrir incidencia",
        mensaje: "Volverá a EN_PROCESO y se notificará al ciudadano y al técnico.",
        textoConfirmar: "Reabrir",
      });
      if (!ok) return;

      btnReabrir.disabled = true;
      try {
        const actualizada = await apiFetch("/incidencias/" + id + "/estado", {
          method: "PATCH",
          body: JSON.stringify({ estado_incidencia: "EN_PROCESO" }),
        });
        incActual.estado_incidencia = actualizada.estado_incidencia;
        incActual.reapertura_pendiente = actualizada.reapertura_pendiente;
        pintarBadgeEstado(incActual.estado_incidencia, true);
        marcarEstadoActivo();
        btnReabrir.classList.add("d-none");
        btnRechazar.classList.add("d-none");
        cargarHistorial(id);
        mostrarToast("Incidencia reabierta", "success");
      } catch (error) {
        mostrarToast(error.message, "error");
      } finally {
        btnReabrir.disabled = false;
      }
    });
  }

  if (btnRechazar.dataset.cableado !== "1") {
    btnRechazar.dataset.cableado = "1";
    btnRechazar.addEventListener("click", async function () {
      const ok = await confirmar({
        titulo: "No reabrir la incidencia",
        mensaje: "La incidencia se mantiene resuelta y se le avisará al ciudadano por qué.",
        textoConfirmar: "No reabrir",
      });
      if (!ok) return;

      btnRechazar.disabled = true;
      try {
        const actualizada = await apiFetch("/incidencias/" + id + "/rechazar-reapertura", {
          method: "POST",
        });
        incActual.reapertura_pendiente = actualizada.reapertura_pendiente;
        btnReabrir.classList.add("d-none");
        btnRechazar.classList.add("d-none");
        mostrarToast("Solicitud de reapertura rechazada", "success");
      } catch (error) {
        mostrarToast(error.message, "error");
      } finally {
        btnRechazar.disabled = false;
      }
    });
  }
}

// Milisegundos sin latido tras los que el candado se ve "vencido" en el cliente (igual al TTL del backend).
const RECLAMO_TTL_MS = 120000;

// El admin solo gestiona (estado/prioridad/asignaciones) la incidencia que él mismo reclamó (candado del backend).
function soyDuenoDelReclamo() {
  return !!(usuarioActual && incActual.id_admin_atiende === usuarioActual.id);
}

// Reaplica el candado del reclamo a los controles de gestión del admin (estado, prioridad, asignaciones).
function refrescarGestionSegunReclamo() {
  if (!esAdmin) return;
  marcarEstadoActivo();
  marcarPrioridadActiva();
  renderAsignaciones(ultimasAsignaciones, incActual.id_incidencia);
  marcarReclasificacionSegunReclamo();
}

// Catálogos tipo→subtipo para reclasificar (solo admin); instancia propia, sin provincia/ciudad.
let catalogosGestion = null;

// Carga los catálogos, preselecciona el tipo/subtipo actual y cablea el botón de guardar.
async function prepararReclasificacion(id) {
  catalogosGestion = crearCatalogosIncidencia({ tipo: "gestionTipo", subtipo: "gestionSubtipo" });
  try {
    await catalogosGestion.cargar();
  } catch (error) {
    mostrarToast("No se pudieron cargar los catálogos: " + error.message, "error");
    return;
  }
  preseleccionarReclasificacion();
  document.getElementById("btnGuardarReclasificacion").addEventListener("click", function () {
    guardarReclasificacion(id);
  });
  marcarReclasificacionSegunReclamo();
}

// Deja los selects en el tipo/subtipo actual de la incidencia.
function preseleccionarReclasificacion() {
  const idTipo =
    incActual.subtipo && incActual.subtipo.tipo ? incActual.subtipo.tipo.id_tipo_incidencia : "";
  document.getElementById("gestionTipo").value = idTipo || "";
  catalogosGestion.poblarSubtipos(idTipo);
  if (incActual.subtipo) {
    document.getElementById("gestionSubtipo").value = incActual.subtipo.id_subtipo_incidencia;
  }
}

// Reclasificar respeta el mismo candado que estado/prioridad: solo el dueño del reclamo y no congelada.
function marcarReclasificacionSegunReclamo() {
  const seccion = document.getElementById("reclasificacionSeccion");
  if (!seccion) return;
  const habilitada = soyDuenoDelReclamo() && !gestionBloqueada();
  document.getElementById("gestionTipo").disabled = !habilitada;
  document.getElementById("gestionSubtipo").disabled =
    !habilitada || !document.getElementById("gestionTipo").value;
  document.getElementById("btnGuardarReclasificacion").disabled = !habilitada;
}

// Envía el nuevo subtipo (reasignarlo cambia el tipo) con el mismo patrón que la prioridad.
async function guardarReclasificacion(id) {
  const idSubtipo = document.getElementById("gestionSubtipo").value;
  if (!idSubtipo) {
    mostrarToast("Selecciona un subtipo", "warning");
    return;
  }
  if (incActual.subtipo && String(incActual.subtipo.id_subtipo_incidencia) === String(idSubtipo)) {
    return;
  }

  const btn = document.getElementById("btnGuardarReclasificacion");
  const spinner = document.getElementById("reclasificacionSpinner");
  btn.disabled = true;
  spinner.classList.remove("d-none");
  try {
    const actualizada = await apiFetch("/incidencias/" + id, {
      method: "PUT",
      body: JSON.stringify({ id_subtipo_incidencia: idSubtipo }),
    });
    incActual.subtipo = actualizada.subtipo;
    incActual.id_subtipo_incidencia = actualizada.id_subtipo_incidencia;
    repintarTipoSubtipo();
    mostrarToast("Reclasificación guardada", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  } finally {
    btn.disabled = false;
    spinner.classList.add("d-none");
  }
}

// Repinta los textos de solo lectura de tipo/subtipo tras reclasificar (sin recargar).
function repintarTipoSubtipo() {
  const tipo =
    incActual.subtipo && incActual.subtipo.tipo
      ? incActual.subtipo.tipo.nombre_tipo_incidencia
      : "—";
  const subtipo = incActual.subtipo ? incActual.subtipo.nombre_subtipo_incidencia : "—";
  document.getElementById("detalleTipoTexto").textContent = tipo;
  document.getElementById("detalleSubtipoTexto").textContent = subtipo;
}

// Recalcula "vencido" en el cliente desde el último latido; el servidor revalida al liberar (fuente de verdad).
function reclamoVencidoCliente() {
  if (!incActual.id_admin_atiende) return false;
  if (!incActual.reclamo_visto_en) return true;
  return Date.now() - new Date(incActual.reclamo_visto_en).getTime() > RECLAMO_TTL_MS;
}

// Reclamar/Liberar/Archivar (candado v2). El lease se refresca con el heartbeat; si vence, otro admin lo toma.
function prepararAtencionAdmin(id) {
  pintarAtencionAdmin();

  const btnReclamar = document.getElementById("btnReclamarIncidencia");
  const btnForzar = document.getElementById("btnForzarLiberar");
  const btnArchivar = document.getElementById("btnArchivarIncidencia");
  if (btnReclamar.dataset.cableado === "1") return;
  btnReclamar.dataset.cableado = "1";
  btnForzar.dataset.cableado = "1";
  btnArchivar.dataset.cableado = "1";

  // Repinta cada 15s para revelar el botón de tomar/forzar cuando el lease de otro admin caduca, sin recargar.
  setInterval(pintarAtencionAdmin, 15000);

  btnReclamar.addEventListener("click", async function () {
    btnReclamar.disabled = true;
    try {
      const actualizada = await apiFetch("/incidencias/" + id + "/reclamar", { method: "POST" });
      aplicarReclamo(actualizada);
      mostrarToast("Incidencia reclamada", "success");
    } catch (error) {
      mostrarToast(error.message, "error");
    } finally {
      btnReclamar.disabled = false;
    }
  });

  btnForzar.addEventListener("click", async function () {
    const soyYo = incActual.admin_atiende && incActual.admin_atiende.id === usuarioActual.id;
    const ok = await confirmar({
      titulo: soyYo ? "Liberar atención" : "Forzar liberar",
      mensaje: soyYo
        ? "Dejará de estar a tu cargo y otro administrador podrá tomarla."
        : "Quitarás la atención al administrador actual para que quede libre.",
      textoConfirmar: soyYo ? "Liberar" : "Forzar",
      peligro: !soyYo,
    });
    if (!ok) return;

    btnForzar.disabled = true;
    try {
      const actualizada = await apiFetch("/incidencias/" + id + "/reclamar", { method: "DELETE" });
      aplicarReclamo(actualizada);
      mostrarToast("Atención liberada", "success");
    } catch (error) {
      mostrarToast(error.message, "error");
    } finally {
      btnForzar.disabled = false;
    }
  });

  btnArchivar.addEventListener("click", async function () {
    const ok = await confirmar({
      titulo: "Cerrar / Archivar incidencia",
      mensaje: "Pasará a Cerrada y quedará de solo lectura para todos.",
      textoConfirmar: "Archivar",
    });
    if (!ok) return;

    btnArchivar.disabled = true;
    try {
      const actualizada = await apiFetch("/incidencias/" + id + "/archivar", { method: "PATCH" });
      incActual.estado_incidencia = actualizada.estado_incidencia;
      pintarBadgeEstado(incActual.estado_incidencia, true);
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

// Vuelca en incActual la respuesta de reclamar/liberar y repinta la sección de atención.
function aplicarReclamo(actualizada) {
  incActual.id_admin_atiende = actualizada.id_admin_atiende;
  incActual.admin_atiende = actualizada.admin_atiende;
  incActual.reclamo_visto_en = actualizada.reclamo_visto_en;
  incActual.reclamo_vencido = actualizada.reclamo_vencido;
  pintarAtencionAdmin();
  pintarMetaAdminAtiende();
  // Reclamar/liberar habilita o bloquea los controles de gestión en el acto.
  refrescarGestionSegunReclamo();
}

// Pinta "Atendida por" y decide los botones: Reclamar (libre o vencido) / Liberar-Forzar / Archivar.
function pintarAtencionAdmin() {
  const info = document.getElementById("atencionAdminInfo");
  const btnReclamar = document.getElementById("btnReclamarIncidencia");
  const btnForzar = document.getElementById("btnForzarLiberar");
  const btnArchivar = document.getElementById("btnArchivarIncidencia");
  const admin = incActual.admin_atiende;

  if (!admin) {
    info.textContent = "Sin reclamar. Reclámala para poder gestionarla.";
    btnReclamar.classList.remove("d-none");
    btnForzar.classList.add("d-none");
    btnArchivar.classList.add("d-none");
    return;
  }

  const soyYo = usuarioActual && admin.id === usuarioActual.id;
  const vencido = reclamoVencidoCliente();
  info.textContent =
    "Atendida por: " +
    admin.name +
    (soyYo ? " (tú)" : vencido ? " (inactivo)" : ". Solo ese administrador puede gestionarla.");

  // Otro admin puede tomar el candado directamente cuando el lease del dueño venció.
  btnReclamar.classList.toggle("d-none", !(vencido && !soyYo));
  // El dueño libera el suyo cuando quiera; super_admin fuerza un lease activo de otro.
  btnForzar.textContent = "";
  const iconoForzar = document.createElement("i");
  iconoForzar.className = "bi bi-unlock me-1";
  btnForzar.append(iconoForzar, soyYo ? " Liberar mi atención" : " Forzar liberar");
  // Solo el dueño libera su propio reclamo; nadie más puede forzar uno activo (super_admin es view-only).
  btnForzar.classList.toggle("d-none", !soyYo);
  // Archivar solo el dueño y solo si está RESUELTO.
  btnArchivar.classList.toggle("d-none", !(soyYo && incActual.estado_incidencia === "RESUELTO"));
}

// Hook del núcleo: llegó un cambio de estado/prioridad en vivo. Refleja botones y visibilidad del admin.
function gestionAlActualizarEnVivo() {
  if (!esAdmin && !esResponsableActual()) return;
  marcarEstadoActivo();
  if (!esAdmin) return;
  marcarPrioridadActiva();
  pintarAtencionAdmin();
  const hayPendiente = incActual.estado_incidencia === "RESUELTO" && incActual.reapertura_pendiente;
  const btnReabrir = document.getElementById("btnReabrirIncidencia");
  const btnRechazar = document.getElementById("btnRechazarReapertura");
  if (btnReabrir) btnReabrir.classList.toggle("d-none", !hayPendiente);
  if (btnRechazar) btnRechazar.classList.toggle("d-none", !hayPendiente);
}

// Hook del núcleo: llegó un cambio de candado en vivo (otro admin reclamó/liberó).
function gestionAlCambiarReclamo() {
  if (!esAdmin) return;
  pintarAtencionAdmin();
  refrescarGestionSegunReclamo();
}

let gestionFotosLista = false;
function habilitarFotosResolucion(id) {
  if (gestionFotosLista) return;
  gestionFotosLista = true;
  document.getElementById("evidenciasResolucionEdicion").classList.remove("d-none");
  prepararSubidaResolucion(id);

  // El carrusel de resolución gana el botón "×" (borra al instante) y "+ Agregar foto"
  // (abre el mismo selector de cámara/galería que alimenta la cola de subida de abajo).
  fijarOpcionesFotosResolucion({
    onEliminar: eliminarFotoResolucion,
    onAgregar: function () {
      abrirSelectorFuenteFoto(
        document.getElementById("inputResolucionCamara"),
        document.getElementById("inputResolucion"),
      );
    },
    puedeAgregar: function () {
      return cupoResolucion() > 0;
    },
  });
}

// Elimina una foto de resolución ya subida (backend ya lo permite al técnico responsable).
async function eliminarFotoResolucion(idEv) {
  try {
    await apiFetch("/evidencias/" + idEv, { method: "DELETE" });
    incActual.evidencias = (incActual.evidencias || []).filter((ev) => ev.id_evidencia !== idEv);
    pintarFotos();
  } catch (error) {
    mostrarToast(error.message, "error");
  }
}

function prepararSubidaResolucion(id) {
  galeriaResolucion = crearGaleriaFotos({
    input: document.getElementById("inputResolucion"),
    inputCamara: document.getElementById("inputResolucionCamara"),
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

  // Las asignaciones se editan solo si el admin reclamó y no está congelada (RESUELTO/CERRADO).
  const bloqueada = gestionBloqueada() || !soyDuenoDelReclamo();
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

  // Sin botón de quitar si está congelada (RESUELTO/CERRADO) o si el admin no reclamó la incidencia.
  if (gestionBloqueada() || !soyDuenoDelReclamo()) return fila;

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
