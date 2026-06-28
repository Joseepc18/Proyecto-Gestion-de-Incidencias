// detalle-incidencia.js — Núcleo de la pantalla de detalle (común a los 3 roles).
// Carga la incidencia, pinta lo compartido (info, mapa, fotos, historial, chat) y
// dispara los "hooks" de los módulos por rol (gestión y edición) cuando hay datos.

/* exported incActual, usuarioActual, esAdmin, idActual, responsableActual, opcionesCompresion, codigoIncidencia, esResponsableActual, pintarBadgeEstado, pintarBadgePrioridad, pintarFotos, cargarHistorial, cargarAsignaciones, activarMapaPicker */

// Estado compartido (los módulos por rol lo leen).
let incActual = null;
let usuarioActual = null;
let esAdmin = false;
let idActual = null;
// técnico responsable actual (chat + herramientas del responsable)
let responsableActual = null;
// instancia del mapa de solo-lectura (crearMapaIncidencias)
let mapaVista = null;
// instancia del selector de ubicación (crearMapaPicker), solo en edición del ciudadano
let picker = null;

// Compresión de fotos antes de subir (la comparten los módulos de gestión y edición).
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

// Iniciales para el avatar de un técnico.
function iniciales(nombre) {
  const partes = (nombre || "").trim().split(/\s+/);
  const a = partes[0] ? partes[0][0] : "";
  const b = partes[1] ? partes[1][0] : "";
  return (a + b).toUpperCase() || "?";
}

// Lista a la que vuelve cada rol (también se usa si no llega ?id=).
function rutaLista(rol) {
  return rol === "admin"
    ? "../gestion-incidencias/gestion-incidencias.html"
    : "../mis-incidencias/mis-incidencias.html";
}

document.addEventListener("DOMContentLoaded", async function () {
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

  let rol = "";
  try {
    usuarioActual = await apiFetch("/user");
    rol = usuarioActual.rol ? usuarioActual.rol.nombre_rol : "";
    esAdmin = rol === "admin";
    document.getElementById("nombreUsuario").textContent = usuarioActual.name;
    aplicarMenuRol(rol);
  } catch {
    eliminarToken();
    window.location.href = "../login/login.html";
    return;
  }

  const btnVolver = document.getElementById("btnVolver");
  btnVolver.href = rutaLista(rol);
  const textoVolver = rol === "admin" ? "Volver a incidencias" : "Volver a la lista";
  btnVolver.innerHTML = '<i class="bi bi-arrow-left" aria-hidden="true"></i> ' + textoVolver;

  document.getElementById("btnLogout").addEventListener("click", async function (e) {
    e.preventDefault();
    this.classList.add("pe-none", "opacity-50");
    try {
      await apiFetch("/logout", { method: "POST" });
    } catch {
      /* ignorar */
    }
    eliminarToken();
    window.location.href = "../login/login.html";
  });

  const id = new URLSearchParams(window.location.search).get("id");
  if (!id) {
    window.location.replace(rutaLista(rol));
    return;
  }
  idActual = id;

  await cargarDetalle(id);
  cargarAsignaciones(id);
  cargarHistorial(id);
  configurarChatFlotante(id);
});

async function cargarDetalle(id) {
  const cargando = document.getElementById("detalleCargando");
  const contenido = document.getElementById("detalleContenido");

  try {
    const inc = await apiFetch("/incidencias/" + id);
    incActual = inc;

    document.getElementById("detalleCodigo").textContent = codigoIncidencia(inc.id_incidencia);
    document.getElementById("detalleTitulo").textContent = inc.nombre_incidencia;

    pintarBadgeEstado(inc.estado_incidencia);
    pintarBadgePrioridad(inc.prioridad_incidencia);

    const tipo = inc.subtipo && inc.subtipo.tipo ? inc.subtipo.tipo.nombre_tipo_incidencia : "—";
    const subtipo = inc.subtipo ? inc.subtipo.nombre_subtipo_incidencia : "—";
    document.getElementById("detalleTipoBadge").textContent = tipo;

    const fecha = new Date(inc.created_at).toLocaleString("es-EC");
    const reporta = inc.usuario ? inc.usuario.name : "—";
    document.getElementById("detalleMeta").textContent =
      tipo + " → " + subtipo + " · Reportado por " + reporta + " · " + fecha;

    const bloqueDesc = document.getElementById("detalleDescripcionBloque");
    if (inc.descripcion_incidencia) {
      bloqueDesc.classList.remove("d-none");
      document.getElementById("detalleDescripcion").textContent = inc.descripcion_incidencia;
    } else {
      bloqueDesc.classList.add("d-none");
    }

    document.getElementById("detalleUsuario").textContent = reporta;
    document.getElementById("detalleCiudad").textContent = inc.ciudad
      ? inc.ciudad.nombre_ciudad
      : "—";
    document.getElementById("detalleDireccion").textContent =
      inc.direccion_incidencia || "No especificada";
    document.getElementById("detalleFecha").textContent = fecha;

    pintarFotos();

    cargando.classList.add("d-none");
    contenido.classList.remove("d-none");

    pintarMapaLectura();

    if (typeof gestionAlCargarDetalle === "function") gestionAlCargarDetalle(id);
    if (typeof edicionAlCargarDetalle === "function") edicionAlCargarDetalle(id);

    ajustarLayout();
  } catch (error) {
    cargando.innerHTML =
      '<p class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>' +
      escaparHtml(error.message) +
      "</p>";
  }
}

// Crea el mapa de solo-lectura con el pin de la incidencia (o un aviso si no hay ubicación).
function pintarMapaLectura() {
  const lat = incActual.latitud_incidencia != null ? Number(incActual.latitud_incidencia) : null;
  const lng = incActual.longitud_incidencia != null ? Number(incActual.longitud_incidencia) : null;
  if (lat == null || lng == null) {
    document.getElementById("mapaDetalle").innerHTML =
      '<p class="text-muted small p-3 mb-0">Esta incidencia no tiene ubicación.</p>';
    return;
  }
  mapaVista = crearMapaIncidencias("mapaDetalle");
  setTimeout(function () {
    mapaVista.map.invalidateSize();
    mapaVista.pintarPines([
      {
        id: incActual.id_incidencia,
        lat: lat,
        lng: lng,
        titulo: codigoIncidencia(incActual.id_incidencia) + " — " + incActual.nombre_incidencia,
        color: "#2563eb",
      },
    ]);
  }, 200);
}

// Convierte el mapa de solo-lectura en un selector de ubicación (lo usa el módulo de edición).
// Devuelve el picker para que el llamador cablee "Usar mi ubicación".
function activarMapaPicker(lat, lng, onCambio) {
  if (mapaVista) {
    mapaVista.map.remove();
    mapaVista = null;
  }
  document.getElementById("mapaDetalle").innerHTML = "";
  picker = crearMapaPicker("mapaDetalle", onCambio);
  setTimeout(function () {
    picker.map.invalidateSize();
    if (lat != null && lng != null) picker.setUbicacion(lat, lng);
  }, 200);
  return picker;
}

// Pinta el badge de estado a partir del código.
function pintarBadgeEstado(estado) {
  const span = document.getElementById("detalleEstado");
  span.outerHTML = badgeEstadoHtml(estado).replace('<span class="badge', '<span id="detalleEstado" class="badge');
}

// Pinta el badge de prioridad y la franja lateral de la tarjeta.
function pintarBadgePrioridad(prioridad) {
  const span = document.getElementById("detallePrioridad");
  span.outerHTML = badgePrioridadHtml(prioridad).replace('<span class="badge', '<span id="detallePrioridad" class="badge');

  const panel = document.getElementById("panelDetalle");
  if (panel) {
    const acento = { ALTA: "acento-alta", MEDIA: "acento-media", BAJA: "acento-baja" };
    panel.classList.remove("acento-alta", "acento-media", "acento-baja");
    if (acento[prioridad]) panel.classList.add(acento[prioridad]);
  }
}

// Reparte las evidencias en las dos galerías (reporte / resolución), en solo lectura.
// El módulo de edición sobrescribe #fotosReporte con la versión editable si es el dueño.
function pintarFotos() {
  const evidencias = incActual.evidencias || [];
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
    '" style="width:130px;height:130px;object-fit:cover" alt="Evidencia" loading="lazy" />'
  );
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

// Trae las asignaciones: fija el responsable (chat) y delega las listas al módulo de gestión.
async function cargarAsignaciones(id) {
  try {
    const asignaciones = await apiFetch("/incidencias/" + id + "/asignaciones");
    const responsable = asignaciones.find((a) => a.rol_asignado === "RESPONSABLE");
    responsableActual = responsable || null;

    pintarParticipantes();
    actualizarChatFab();

    if (typeof gestionAlCargarAsignaciones === "function") {
      gestionAlCargarAsignaciones(asignaciones, id);
    }

    ajustarLayout();
  } catch {
    if (typeof gestionAsignacionesError === "function") gestionAsignacionesError();
  }
}

// Si la columna de gestión no tiene ningún panel visible (ciudadano / técnico de apoyo),
// se oculta y la grilla pasa de 3 a 2 columnas para no desperdiciar el ancho.
function ajustarLayout() {
  const col = document.getElementById("colGestion");
  const grid = document.querySelector(".detalle-grid");
  if (!col || !grid) return;
  const tieneContenido = Array.from(col.children).some((el) => !el.classList.contains("d-none"));
  col.classList.toggle("d-none", !tieneContenido);
  grid.classList.toggle("detalle-grid--2col", !tieneContenido);
}

async function cargarHistorial(id) {
  const cont = document.getElementById("historialTimeline");
  try {
    const historial = await apiFetch("/incidencias/" + id + "/historial", { sinSpinner: true });

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

      const titulo = document.createElement("p");
      titulo.className = "timeline-titulo";
      titulo.innerHTML = badgeEstadoHtml(ev.estado);
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

// Solo ven el chat el admin, el reportador y el técnico responsable (el apoyo queda fuera).
function puedeUsarChat() {
  if (!usuarioActual) return false;
  if (esAdmin) return true;
  if (incActual && incActual.usuario && incActual.usuario.id === usuarioActual.id) return true;
  return esResponsableActual();
}

// Muestra u oculta la burbuja del chat según quién mira (se reevalúa al cargar las asignaciones).
function actualizarChatFab() {
  const fab = document.getElementById("btnChatFab");
  if (!fab) return;
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

  if (incActual.usuario && incActual.usuario.id !== usuarioActual.id) {
    contactos.push({ nombre: incActual.usuario.name, rol: "Reportador", color: "secondary" });
  }
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
