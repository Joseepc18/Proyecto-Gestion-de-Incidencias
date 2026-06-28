// detalle.js — Página de detalle de una incidencia (vista admin con herramientas de gestión).

/* global apiFetch, obtenerToken, eliminarToken, aplicarMenuRol, mostrarToast, confirmar, crearMapaIncidencias, crearChat, estadoConfig, prioridadConfig */

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

  // Herramientas de gestión: solo admin
  if (esAdmin) {
    document.querySelectorAll(".solo-admin").forEach(function (el) {
      el.classList.remove("d-none");
    });
    prepararPrioridad(id);
    prepararEstado(id);
    prepararAsignacion(id);
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

// Solo ven el chat el admin, el reportador y el técnico responsable (el de apoyo queda fuera, igual que la policy verChat).
function puedeUsarChat() {
  if (!usuarioActual) return false;
  if (esAdmin) return true;
  if (incActual && incActual.usuario && incActual.usuario.id === usuarioActual.id) return true;
  return (
    responsableActual &&
    responsableActual.usuario &&
    responsableActual.usuario.id === usuarioActual.id
  );
}

// Muestra u oculta la burbuja del chat según quién mira (se reevalúa al cargar las asignaciones).
function actualizarChatFab() {
  const fab = document.getElementById("btnChatFab");
  if (fab) fab.classList.toggle("d-none", !puedeUsarChat());
}

// Chat flotante: la burbuja abre/cierra el chat (se crea la primera vez que se abre).
let chatCreado = false;
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

// Resalta el estado actual y deshabilita el botón correspondiente.
function marcarEstadoActivo() {
  document.querySelectorAll("#estadoBotones .btn-estado-tool").forEach(function (b) {
    const activo = b.dataset.estado === incActual.estado_incidencia;
    b.classList.toggle("activo", activo);
    b.disabled = activo;
  });
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
