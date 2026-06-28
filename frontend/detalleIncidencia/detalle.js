// detalle.js — Página de detalle de una incidencia (vista de gestión: admin y técnico responsable).

/* global apiFetch, obtenerToken, eliminarToken, aplicarMenuRol, mostrarToast, confirmar, crearMapaIncidencias, crearChat, estadoConfig, prioridadConfig, imageCompression */

// Estado de la página.
let esAdmin = false;
let usuarioActual = null;
let incActual = null;
// instancia de solo-lectura (crearMapaIncidencias)
let mapaVista = null;
// técnico responsable actual (para los participantes del chat)
let responsableActual = null;

function codigoIncidencia(id) {
  return "INC-" + String(id).padStart(4, "0");
}

// Iniciales para el avatar de un técnico.
function iniciales(nombre) {
  const partes = (nombre || "").trim().split(/\s+/);
  const a = partes[0] ? partes[0][0] : "";
  const b = partes[1] ? partes[1][0] : "";
  return (a + b).toUpperCase() || "?";
}

document.addEventListener("DOMContentLoaded", async function () {
  // Guard de sesión
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

  // Cargar usuario (nombre en navbar + menú por rol)
  try {
    usuarioActual = await apiFetch("/user");
    document.getElementById("nombreUsuario").textContent = usuarioActual.name;
    aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "");
    esAdmin = usuarioActual.rol && usuarioActual.rol.nombre_rol === "admin";
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

  // Leer el id de la incidencia desde la URL (?id=5)
  const id = new URLSearchParams(window.location.search).get("id");
  if (!id) {
    window.location.href = "../incidencias/incidencias.html";
    return;
  }

  await cargarDetalle(id);
  cargarAsignaciones(id);
  cargarHistorial(id);
  configurarChatFlotante(id);

  // Prioridad y asignación de técnicos: solo admin.
  if (esAdmin) {
    document.querySelectorAll(".solo-admin").forEach(function (el) {
      el.classList.remove("d-none");
    });
    prepararPrioridad(id);
    prepararAsignacion(id);
    // El admin gestiona el estado (no sube fotos de resolución, solo las ve).
    habilitarGestionResolucion(id);
  }
});

async function cargarDetalle(id) {
  const cargando = document.getElementById("detalleCargando");
  const contenido = document.getElementById("detalleContenido");

  try {
    const inc = await apiFetch("/incidencias/" + id);
    incActual = inc;

    // Encabezado
    document.getElementById("detalleCodigo").textContent = codigoIncidencia(inc.id_incidencia);
    document.getElementById("detalleTitulo").textContent = inc.nombre_incidencia;

    // Badges estado / prioridad / tipo
    pintarBadgeEstado(inc.estado_incidencia);
    pintarBadgePrioridad(inc.prioridad_incidencia);

    const tipo = inc.subtipo && inc.subtipo.tipo ? inc.subtipo.tipo.nombre_tipo_incidencia : "—";
    const subtipo = inc.subtipo ? inc.subtipo.nombre_subtipo_incidencia : "—";
    document.getElementById("detalleTipoBadge").textContent = tipo;

    // Línea meta
    const fecha = new Date(inc.created_at).toLocaleString("es-EC");
    const reporta = inc.usuario ? inc.usuario.name : "—";
    document.getElementById("detalleMeta").textContent =
      tipo + " → " + subtipo + " · Reportado por " + reporta + " · " + fecha;

    // Descripción
    const bloqueDesc = document.getElementById("detalleDescripcionBloque");
    if (inc.descripcion_incidencia) {
      bloqueDesc.classList.remove("d-none");
      document.getElementById("detalleDescripcion").textContent = inc.descripcion_incidencia;
    } else {
      bloqueDesc.classList.add("d-none");
    }

    // Datos
    document.getElementById("detalleUsuario").textContent = reporta;
    document.getElementById("detalleCiudad").textContent = inc.ciudad
      ? inc.ciudad.nombre_ciudad
      : "—";
    document.getElementById("detalleDireccion").textContent =
      inc.direccion_incidencia || "No especificada";
    document.getElementById("detalleFecha").textContent = fecha;

    // Fotos (separadas por tipo de evidencia)
    pintarFotos(inc.evidencias || []);

    cargando.classList.add("d-none");
    contenido.classList.remove("d-none");

    // Mapa con el pin de la incidencia (solo lectura)
    const lat = inc.latitud_incidencia != null ? Number(inc.latitud_incidencia) : null;
    const lng = inc.longitud_incidencia != null ? Number(inc.longitud_incidencia) : null;
    if (lat != null && lng != null) {
      mapaVista = crearMapaIncidencias("mapaDetalle");
      setTimeout(function () {
        mapaVista.map.invalidateSize();
        mapaVista.pintarPines([
          {
            id: inc.id_incidencia,
            lat: lat,
            lng: lng,
            titulo: codigoIncidencia(inc.id_incidencia) + " — " + inc.nombre_incidencia,
            color: "#2563eb",
          },
        ]);
      }, 200);
    } else {
      document.getElementById("mapaDetalle").innerHTML =
        '<p class="text-muted small p-3 mb-0">Esta incidencia no tiene ubicación.</p>';
    }
  } catch (error) {
    cargando.innerHTML =
      '<p class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>' +
      error.message +
      "</p>";
  }
}

// ¿Quien mira es el técnico RESPONSABLE de esta incidencia? (el de apoyo no cuenta)
function esResponsableActual() {
  return !!(
    responsableActual &&
    responsableActual.usuario &&
    usuarioActual &&
    responsableActual.usuario.id === usuarioActual.id
  );
}

// Solo ven el chat el admin, el reportador y el técnico responsable (el de apoyo queda fuera, igual que la policy verChat).
function puedeUsarChat() {
  if (!usuarioActual) return false;
  if (esAdmin) return true;
  if (incActual && incActual.usuario && incActual.usuario.id === usuarioActual.id) return true;
  return esResponsableActual();
}

// Muestra u oculta la burbuja del chat según quién mira (se reevalúa al cargar las asignaciones).
function actualizarChatFab() {
  const fab = document.getElementById("btnChatFab");
  if (fab) {
    if (puedeUsarChat()) {
      fab.classList.remove("d-none");
      if (new URLSearchParams(window.location.search).get("chat") === "1" && !chatAbiertoAuto) {
        chatAbiertoAuto = true;
        fab.click();
      }
    } else {
      fab.classList.add("d-none");
    }
  }
}

// Chat flotante: la burbuja abre/cierra el chat (se crea la primera vez que se abre).
let chatCreado = false;
let chatAbiertoAuto = false;

function configurarChatFlotante(id) {
  const fab = document.getElementById("btnChatFab");
  const panel = document.getElementById("chatPanel");
  const cerrar = document.getElementById("btnCerrarChat");

  actualizarChatFab();

  fab.addEventListener("click", function () {
    panel.classList.remove("d-none");
    fab.classList.add("d-none");
    pintarParticipantes();
    if (!chatCreado) {
      crearChat("chatContenedor", id, usuarioActual);
      chatCreado = true;
    }
  });

  function cerrarChat() {
    panel.classList.add("d-none");
    fab.classList.remove("d-none");
  }

  cerrar.addEventListener("click", cerrarChat);

  // Esc cierra el chat si está abierto.
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && !panel.classList.contains("d-none")) {
      cerrarChat();
    }
  });
}

// Pinta los otros participantes del chat (todos menos quien mira).
function pintarParticipantes() {
  const cont = document.getElementById("chatParticipantes");
  if (!cont || !incActual || !usuarioActual) return;

  const rol = usuarioActual.rol ? usuarioActual.rol.nombre_rol : "";
  const contactos = [];

  // Reportador (dueño de la incidencia)
  if (incActual.usuario && incActual.usuario.id !== usuarioActual.id) {
    contactos.push({ nombre: incActual.usuario.name, rol: "Reportador", color: "secondary" });
  }
  // Técnico responsable (si hay)
  if (
    responsableActual &&
    responsableActual.usuario &&
    responsableActual.usuario.id !== usuarioActual.id
  ) {
    contactos.push({
      nombre: responsableActual.usuario.name,
      rol: "Técnico responsable",
      color: "success",
    });
  }
  // Administración (genérica) — solo si quien mira no es admin
  if (rol !== "admin") {
    contactos.push({ nombre: "Administración", rol: "Administrador", color: "primary" });
  }

  cont.innerHTML = "";
  if (!contactos.length) {
    const vacio = document.createElement("span");
    vacio.className = "chat-participantes-vacio";
    vacio.textContent = "Aún no hay otros participantes.";
    cont.appendChild(vacio);
    return;
  }

  contactos.forEach(function (c) {
    const chip = document.createElement("span");
    chip.className = "chat-contacto";
    chip.title = c.rol;

    const ava = document.createElement("span");
    ava.className = "chat-ava chat-ava-" + c.color;
    ava.textContent = iniciales(c.nombre);
    chip.appendChild(ava);

    const info = document.createElement("span");
    info.className = "chat-contacto-info";
    const nombre = document.createElement("span");
    nombre.className = "chat-contacto-nombre";
    nombre.textContent = c.nombre;
    const etiqueta = document.createElement("span");
    etiqueta.className = "chat-contacto-rol";
    etiqueta.textContent = c.rol;
    info.append(nombre, etiqueta);
    chip.appendChild(info);

    cont.appendChild(chip);
  });
}

// Pinta el badge de estado a partir del código.
function pintarBadgeEstado(estado) {
  const est = estadoConfig[estado] || { clase: "", icono: "", texto: estado };
  const span = document.getElementById("detalleEstado");
  span.className = "badge " + est.clase;
  span.innerHTML = '<i class="bi ' + est.icono + ' me-1"></i>' + est.texto;
}

// Pinta el badge de prioridad a partir del código.
function pintarBadgePrioridad(prioridad) {
  const pri = prioridadConfig[prioridad] || { clase: "", icono: "", texto: prioridad };
  const span = document.getElementById("detallePrioridad");
  span.className = "badge " + pri.clase;
  span.innerHTML = '<i class="bi ' + pri.icono + ' me-1"></i>' + pri.texto;

  // Franja lateral de la tarjeta de detalles según la prioridad
  const panel = document.getElementById("panelDetalle");
  if (panel) {
    const acento = { ALTA: "acento-alta", MEDIA: "acento-media", BAJA: "acento-baja" };
    panel.classList.remove("acento-alta", "acento-media", "acento-baja");
    if (acento[prioridad]) panel.classList.add(acento[prioridad]);
  }
}

// Reparte las evidencias en las dos galerías (reporte / resolución).
function pintarFotos(evidencias) {
  const reporte = evidencias.filter((ev) => ev.tipo_evidencia !== "RESOLUCION");
  const resolucion = evidencias.filter((ev) => ev.tipo_evidencia === "RESOLUCION");

  const contReporte = document.getElementById("fotosReporte");
  contReporte.innerHTML = reporte.length
    ? reporte.map(miniaturaFoto).join("")
    : '<p class="text-muted small mb-0">Sin fotos del reporte.</p>';

  const bloqueRes = document.getElementById("fotosResolucionBloque");
  if (resolucion.length) {
    bloqueRes.classList.remove("d-none");
    document.getElementById("fotosResolucion").innerHTML = resolucion.map(miniaturaFoto).join("");
  } else {
    bloqueRes.classList.add("d-none");
  }
}

// HTML de una miniatura con lightbox.
function miniaturaFoto(ev) {
  return (
    '<img src="/storage/' +
    ev.url_evidencia +
    '" class="evidencia-foto rounded" data-lightbox="/storage/' +
    ev.url_evidencia +
    '" style="width:130px;height:130px;object-fit:cover" alt="Evidencia" />'
  );
}

// Bloque: Prioridad

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

// Revela y cablea (una sola vez) el cambio de estado (admin y responsable) y la subida de fotos (solo responsable).
let gestionResolucionLista = false;
function habilitarGestionResolucion(id) {
  if (gestionResolucionLista) return;
  gestionResolucionLista = true;

  // Cambiar estado: lo usan el admin (libre) y el técnico responsable (solo EN_PROCESO → RESUELTO).
  document.querySelectorAll(".gestion-estado").forEach(function (el) {
    el.classList.remove("d-none");
  });
  prepararEstado(id);

  // Subir fotos de resolución: solo el técnico responsable (el admin las ve, pero no las sube).
  if (!esAdmin && esResponsableActual()) {
    document.querySelectorAll(".gestion-fotos").forEach(function (el) {
      el.classList.remove("d-none");
    });
    prepararSubidaResolucion(id);
  }
}

// Bloque: Cambiar estado

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

// Bloque: Subir fotos de la resolución

// Compresión: redimensiona a ~1920px y calidad 0.8 antes de subir.
const opcionesCompresion = {
  maxSizeMB: 0.5,
  maxWidthOrHeight: 1920,
  useWebWorker: true,
  fileType: "image/jpeg",
  initialQuality: 0.8,
};

// Fotos de resolución ya comprimidas, pendientes de subir.
let fotosResolucion = [];

function prepararSubidaResolucion(id) {
  const input = document.getElementById("inputResolucion");
  const dropzone = document.getElementById("dropzoneResolucion");

  // El <label for> abre el selector; aquí procesamos la selección.
  input.addEventListener("change", function () {
    procesarFotosResolucion(this.files);
    // Limpia el input para poder agregar más sin reemplazar.
    this.value = "";
  });

  // Arrastrar y soltar sobre la zona de carga.
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
      // Forzar nombre .jpg para que calce con la validación del backend.
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
  // El dropzone grande se oculta si ya hay fotos elegidas o si no queda cupo.
  dropzone.classList.toggle("d-none", fotosResolucion.length > 0 || cupo <= 0);

  fotosResolucion.forEach(function (file, idx) {
    const cont = document.createElement("div");
    cont.className = "position-relative";

    const img = document.createElement("img");
    const url = URL.createObjectURL(file);
    // Libera el objectURL una vez que la miniatura ya cargó.
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

  // Azulejo "+" para seguir agregando mientras quede cupo.
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

  // El botón de subir solo aparece si hay fotos elegidas.
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
    // El backend devuelve la incidencia con sus evidencias: repintamos las galerías.
    incActual.evidencias = actualizada.evidencias || [];
    pintarFotos(incActual.evidencias);
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

// Bloque: Asignación de técnicos

async function cargarAsignaciones(id) {
  const responsableLista = document.getElementById("responsableLista");
  const ayudantesLista = document.getElementById("ayudantesLista");

  try {
    const asignaciones = await apiFetch("/incidencias/" + id + "/asignaciones");
    const responsable = asignaciones.find((a) => a.rol_asignado === "RESPONSABLE");
    const ayudantes = asignaciones.filter((a) => a.rol_asignado === "APOYO");

    // Guardar el responsable para los contactos del chat
    responsableActual = responsable || null;
    pintarParticipantes();
    // Ya se sabe quién es el responsable: reevaluar si esta persona puede ver el chat.
    actualizarChatFab();
    // Si quien mira es el responsable, habilitar sus herramientas (cambiar estado + fotos de resolución).
    if (esResponsableActual()) habilitarGestionResolucion(id);

    // Responsable
    responsableLista.innerHTML = "";
    if (responsable) {
      responsableLista.appendChild(filaTecnico(responsable, id, "primary"));
    } else {
      responsableLista.innerHTML = '<p class="text-muted small mb-0">Sin responsable asignado.</p>';
    }

    // Ayudantes
    ayudantesLista.innerHTML = "";
    if (ayudantes.length) {
      ayudantes.forEach((a) => ayudantesLista.appendChild(filaTecnico(a, id, "secondary")));
    } else {
      ayudantesLista.innerHTML = '<p class="text-muted small mb-0">Sin ayudantes asignados.</p>';
    }

    // El select del responsable se oculta si ya hay uno (solo admin lo ve)
    const formResp = document.getElementById("responsableForm");
    if (formResp) formResp.classList.toggle("d-none", !!responsable);
  } catch {
    responsableLista.innerHTML =
      '<p class="text-danger small mb-0">No se pudieron cargar las asignaciones.</p>';
  }
}

// Fila visual de un técnico asignado (avatar + nombre + quitar si admin).
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

  if (esAdmin) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "btn btn-sm btn-outline-danger ms-auto";
    btn.innerHTML = '<i class="bi bi-x-lg"></i>';
    btn.addEventListener("click", function () {
      quitarAsignacion(asig.id_asignacion, idIncidencia);
    });
    fila.appendChild(btn);
  }

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
    await refrescarSelectsTecnicos(idIncidencia);
    mostrarToast("Asignación eliminada", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  }
}

let listaTecnicos = [];

async function prepararAsignacion(id) {
  // Cargar la lista de técnicos una vez
  try {
    listaTecnicos = await apiFetch("/tecnicos");
  } catch {
    mostrarToast("No se pudieron cargar los técnicos", "error");
    return;
  }
  await refrescarSelectsTecnicos(id);

  document.getElementById("btnAsignarResponsable").addEventListener("click", function () {
    asignar(id, "selectResponsable", "RESPONSABLE");
  });
  document.getElementById("btnAsignarAyudante").addEventListener("click", function () {
    asignar(id, "selectAyudante", "APOYO");
  });

  // Al elegir un técnico en un select, se oculta en el otro (aunque no se haya asignado aún).
  document.getElementById("selectResponsable").addEventListener("change", sincronizarSelects);
  document.getElementById("selectAyudante").addEventListener("change", sincronizarSelects);
}

// Rellena ambos selects con los técnicos que aún no están asignados.
async function refrescarSelectsTecnicos(id) {
  let asignados = [];
  try {
    const asignaciones = await apiFetch("/incidencias/" + id + "/asignaciones");
    asignados = asignaciones.map((a) => (a.usuario ? a.usuario.id : null));
  } catch {
    /* si falla, se muestran todos */
  }

  const disponibles = listaTecnicos.filter((t) => !asignados.includes(t.id));
  ["selectResponsable", "selectAyudante"].forEach(function (idSelect) {
    const select = document.getElementById(idSelect);
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
    await refrescarSelectsTecnicos(id);
    mostrarToast("Técnico asignado", "success");
  } catch (error) {
    mostrarToast(error.message, "error");
  }
}

// Bloque: Historial de estados

async function cargarHistorial(id) {
  const cont = document.getElementById("historialTimeline");
  try {
    const historial = await apiFetch("/incidencias/" + id + "/historial", { sinSpinner: true });

    // El historial viene de más nuevo a más viejo; al final se agrega la creación (PENDIENTE).
    const eventos = historial.map(function (h) {
      return {
        estado: h.estado_nuevo,
        nombre: h.usuario ? h.usuario.name : "Sistema",
        fecha: h.created_at,
      };
    });
    if (incActual) {
      eventos.push({
        estado: "PENDIENTE",
        nombre: incActual.usuario ? incActual.usuario.name : "Sistema",
        fecha: incActual.created_at,
      });
    }

    cont.innerHTML = "";
    eventos.forEach(function (ev) {
      const item = document.createElement("div");
      item.className = "timeline-item";

      const punto = document.createElement("span");
      punto.className = "timeline-punto";
      item.appendChild(punto);

      const est = estadoConfig[ev.estado] || { texto: ev.estado };
      const titulo = document.createElement("p");
      titulo.className = "timeline-titulo";
      titulo.textContent = est.texto;
      item.appendChild(titulo);

      const meta = document.createElement("p");
      meta.className = "timeline-meta";
      const cuando = new Date(ev.fecha).toLocaleString("es-EC");
      meta.textContent = ev.nombre + " · " + cuando;
      item.appendChild(meta);

      cont.appendChild(item);
    });
  } catch {
    cont.innerHTML = '<p class="text-danger small mb-0">No se pudo cargar el historial.</p>';
  }
}
