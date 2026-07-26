// detalle-incidencia.js — Núcleo de la pantalla de detalle (común a los 3 roles)

/* exported incActual, usuarioActual, esAdmin, esRolAdmin, esSuperAdmin, idActual, responsableActual, esResponsableActual, pintarBadgeEstado, pintarBadgePrioridad, pintarFotos, fijarOpcionesFotosReporte, fijarOpcionesFotosResolucion, pintarMetaAdminAtiende, cargarHistorial, cargarAsignaciones, activarMapaPicker, provinciaCiudadTexto */

// Estado compartido (los módulos por rol lo leen).
/* global apiFetch, aplicarMenuRol, tienePermiso, crearMapaIncidencias, crearMapaPicker, crearChat, escaparHtml, estadoConfig, colorEstado, badgeEstadoHtml, prioridadConfig, estadoParaVista, codigoIncidencia, iniciales, tiempoRelativo, montarCarrusel, requerirSesion, cablearLogout, toastFlash, gestionAlCargarDetalle, edicionAlCargarDetalle, edicionAlActualizarEnVivo, gestionAlCargarAsignaciones, gestionAsignacionesError, obtenerEcho, gestionAlActualizarEnVivo, gestionAlCambiarReclamo, soyDuenoDelReclamo */

let incActual = null;
let usuarioActual = null;
let esAdmin = false;
// Rol admin o super_admin (independiente del permiso incidencias.gestionar): decide visibilidad del chat.
let esRolAdmin = false;
// Administrador del Sistema: no gestiona, pero es la llave maestra del candado de atención.
let esSuperAdmin = false;
let idActual = null;
// técnico responsable actual (chat + herramientas del responsable)
let responsableActual = null;
// instancia del mapa de solo-lectura (crearMapaIncidencias)
let mapaVista = null;
// instancia del selector de ubicación (crearMapaPicker), solo en edición del ciudadano
let picker = null;

// Lista a la que vuelve cada rol (también se usa si no llega ?id=).
function rutaLista(rol) {
  return rol === "admin" || rol === "super_admin"
    ? "../gestion-incidencias/gestion-incidencias.html"
    : "../mis-incidencias/mis-incidencias.html";
}

document.addEventListener("DOMContentLoaded", async function () {
  usuarioActual = await requerirSesion();
  if (!usuarioActual) return;
  let rol = usuarioActual.rol ? usuarioActual.rol.nombre_rol : "";
  esAdmin = tienePermiso("incidencias.gestionar");
  esRolAdmin = rol === "admin" || rol === "super_admin";
  esSuperAdmin = rol === "super_admin";
  aplicarMenuRol(rol, usuarioActual.permisos);

  const btnVolver = document.getElementById("btnVolver");
  btnVolver.href = rutaLista(rol);
  const textoVolver = esRolAdmin ? "Volver a incidencias" : "Volver a la lista";
  btnVolver.innerHTML = '<i class="bi bi-arrow-left" aria-hidden="true"></i> ' + textoVolver;

  cablearLogout();

  const id = new URLSearchParams(window.location.search).get("id");
  if (!id) {
    window.location.replace(rutaLista(rol));
    return;
  }
  idActual = id;

  prepararToggleFotos();

  await cargarDetalle(id);
  cargarAsignaciones(id);
  cargarHistorial(id);
  configurarChatFlotante(id);
  conectarTiempoReal(id);
});

// Suscripción central del detalle: estado/prioridad, asignaciones y candado llegan solos a los 3 roles.
function conectarTiempoReal(id) {
  const echo = obtenerEcho();
  if (!echo) return;
  const canal = echo.private("incidencia.updates." + id);

  canal.listen(".IncidenciaActualizada", function (e) {
    if (!incActual) return;
    incActual.estado_incidencia = e.estado_incidencia;
    incActual.prioridad_incidencia = e.prioridad_incidencia;
    incActual.reapertura_pendiente = e.reapertura_pendiente;
    pintarBadgeEstado(e.estado_incidencia, true);
    pintarBadgePrioridad(e.prioridad_incidencia, true);
    cargarHistorial(id);
    if (typeof gestionAlActualizarEnVivo === "function") gestionAlActualizarEnVivo();
    if (typeof edicionAlActualizarEnVivo === "function") edicionAlActualizarEnVivo();
  });

  canal.listen(".IncidenciaEliminada", function () {
    // La incidencia se fue a la papelera mientras la mirábamos: avisar y volver a la lista sin recargar.
    const rol = usuarioActual && usuarioActual.rol ? usuarioActual.rol.nombre_rol : "";
    toastFlash("Esta incidencia fue eliminada.", "warning");
    window.location.replace(rutaLista(rol));
  });

  canal.listen(".AsignacionCambiada", function () {
    cargarAsignaciones(id);
  });

  canal.listen(".EvidenciasActualizadas", function (e) {
    if (!incActual) return;
    incActual.evidencias = e.evidencias;
    pintarFotos();
  });

  canal.listen(".ReclamoCambiado", function (e) {
    if (!incActual) return;
    incActual.id_admin_atiende = e.id_admin_atiende;
    incActual.admin_atiende = e.admin_atiende;
    incActual.reclamo_visto_en = e.reclamo_visto_en;
    // Un reclamo recién hecho/liberado no está vencido; el detalle lo reevalúa con su timer.
    incActual.reclamo_vencido = false;
    pintarMetaAdminAtiende();
    if (typeof gestionAlCambiarReclamo === "function") gestionAlCambiarReclamo();
  });
}

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
    document.getElementById("detalleTipoTexto").textContent = tipo;
    document.getElementById("detalleSubtipoTexto").textContent = subtipo;

    const reporta = inc.usuario ? inc.usuario.name : "—";

    document.getElementById("metaReportadoPor").textContent = reporta;
    document.getElementById("metaProvinciaCiudad").textContent = provinciaCiudadTexto(inc.ciudad);
    pintarMetaAdminAtiende();

    const bloqueDesc = document.getElementById("detalleDescripcionBloque");
    if (inc.descripcion_incidencia) {
      bloqueDesc.classList.remove("d-none");
      document.getElementById("detalleDescripcion").textContent = inc.descripcion_incidencia;
    } else {
      bloqueDesc.classList.add("d-none");
    }

    document.getElementById("detalleDireccion").textContent =
      inc.direccion_incidencia || "No especificada";

    const esTerminal = inc.estado_incidencia === "RESUELTO" || inc.estado_incidencia === "CERRADO";
    document.getElementById("detalleTiempoTranscurrido").textContent =
      esTerminal && inc.fecha_resolucion
        ? "Resuelta en " + duracionEntre(inc.created_at, inc.fecha_resolucion)
        : "Abierta " + tiempoRelativo(inc.created_at);
    document.getElementById("detalleUltimaActividad").textContent = tiempoRelativo(inc.updated_at);

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

// "Provincia / Ciudad" combinado para la barra de meta-información (ej. "Guayas / Guayaquil").
function provinciaCiudadTexto(ciudad) {
  if (!ciudad) return "—";
  const provincia = ciudad.provincia ? ciudad.provincia.nombre_provincia + " / " : "";
  return provincia + ciudad.nombre_ciudad;
}

// Duración entre dos fechas ISO (a diferencia de tiempoRelativo, que es "desde ahora").
function duracionEntre(iso1, iso2) {
  const seg = Math.floor((new Date(iso2).getTime() - new Date(iso1).getTime()) / 1000);
  if (seg < 60) return "menos de un minuto";
  if (seg < 3600) return Math.floor(seg / 60) + " min";
  if (seg < 86400) return Math.floor(seg / 3600) + " h";
  return Math.floor(seg / 86400) + " d";
}

// Tarjeta "Admin. que atendió" de la barra de meta-información. Ojo: solo refleja al admin mientras
// tiene el reclamo activo (id_admin_atiende); al liberarlo vuelve a "Sin asignar" (el backend no
// guarda un registro aparte de quién la atendió una vez liberada).
function pintarMetaAdminAtiende() {
  const el = document.getElementById("metaAdminAtiende");
  if (!el || !incActual) return;
  el.textContent = incActual.admin_atiende ? incActual.admin_atiende.name : "Sin asignar";
}

// Crea el mapa de solo-lectura con el pin de la incidencia (o un aviso si no hay ubicación).
function pintarMapaLectura() {
  const lat = incActual.latitud_incidencia != null ? Number(incActual.latitud_incidencia) : null;
  const lng = incActual.longitud_incidencia != null ? Number(incActual.longitud_incidencia) : null;
  const btnComoLlegar = document.getElementById("btnComoLlegar");
  if (lat == null || lng == null) {
    document.getElementById("mapaDetalle").innerHTML =
      '<p class="text-muted small p-3 mb-0">Esta incidencia no tiene ubicación.</p>';
    if (btnComoLlegar) btnComoLlegar.classList.add("d-none");
    return;
  }
  if (btnComoLlegar) {
    btnComoLlegar.href = "https://www.google.com/maps/dir/?api=1&destination=" + lat + "," + lng;
    btnComoLlegar.classList.remove("d-none");
  }
  mapaVista = crearMapaIncidencias("mapaDetalle");
  mapaVista.pintarPines([
    {
      id: incActual.id_incidencia,
      lat: lat,
      lng: lng,
      titulo: codigoIncidencia(incActual.id_incidencia) + " — " + incActual.nombre_incidencia,
      color: "#2563eb",
    },
  ]);
}

// Convierte el mapa de solo-lectura en un selector de ubicación (lo usa el módulo de edición)
function activarMapaPicker(lat, lng, onCambio) {
  if (mapaVista) {
    mapaVista.map.remove();
    mapaVista = null;
  }
  const btnComoLlegar = document.getElementById("btnComoLlegar");
  if (btnComoLlegar) btnComoLlegar.classList.add("d-none");
  document.getElementById("mapaDetalle").innerHTML = "";
  picker = crearMapaPicker("mapaDetalle", onCambio);
  if (lat != null && lng != null) picker.setUbicacion(lat, lng);
  return picker;
}

// El ciudadano ve "Resuelto" en vez de "Archivado": para él ya está resuelto, archivar es un
// detalle interno del admin que no le aporta nada y solo genera dudas ("¿no era que se resolvió?").
// La regla vive en estados.js (la comparte mis-incidencias.js); acá solo se resuelve el rol.
function rolNombre() {
  return usuarioActual && usuarioActual.rol ? usuarioActual.rol.nombre_rol : "";
}

// El estado ya no tiene badge propio (se ve en el historial); la función se mantiene para los
// llamadores (carga y updates en vivo) pero es no-op si el elemento no existe.
function pintarBadgeEstado(estado, animar) {
  const el = document.getElementById("detalleEstado");
  if (!el) return;
  el.innerHTML = badgeEstadoHtml(estadoParaVista(estado, rolNombre()));
  if (animar) destellarBadge(el);
}

// Insignia de prioridad (pill propio del hero) + tiñe el hero (fondo + icono translúcido).
// El diseño del pill es exclusivo del detalle, por eso no reusa badgePrioridadHtml (compartido).
function pintarBadgePrioridad(prioridad, animar) {
  const el = document.getElementById("detallePrioridad");
  const clave = prioridadConfig[prioridad] ? prioridad : "SIN_ASIGNAR";
  const cfg = prioridadConfig[clave];
  el.className = "detalle-hero-prio prio-chip--" + clave.toLowerCase().replace(/_/g, "");
  el.innerHTML = `<i class="bi ${cfg.icono}" aria-hidden="true"></i><span>${cfg.texto}</span>`;
  if (animar) destellarBadge(el);

  const hero = document.getElementById("detalleHero");
  if (hero) {
    const clasePrio = { ALTA: "prio-alta", MEDIA: "prio-media", BAJA: "prio-baja" };
    hero.classList.remove("prio-alta", "prio-media", "prio-baja");
    if (clasePrio[prioridad]) hero.classList.add(clasePrio[prioridad]);

    // El icono de fondo reusa el mismo de la insignia de prioridad (estados.js = única fuente).
    const icono = document.getElementById("detalleHeroIcono");
    if (icono) icono.className = "detalle-hero-icono bi " + cfg.icono;
  }
}

// Reinicia la animación de destello (por si ya estaba corriendo, forzando un reflow).
function destellarBadge(el) {
  el.classList.remove("badge-destello");
  void el.offsetWidth;
  el.classList.add("badge-destello");
}

// Opciones de edición del carrusel (botón "×" y "+ Agregar foto"), registradas por los módulos de
// rol: edicion.js para el reporte (ciudadano dueño), gestion.js para la resolución (técnico responsable).
let opcionesFotosReporte = null;
let opcionesFotosResolucion = null;

function fijarOpcionesFotosReporte(opts) {
  opcionesFotosReporte = opts;
  if (incActual) pintarFotos();
}

function fijarOpcionesFotosResolucion(opts) {
  opcionesFotosResolucion = opts;
  if (incActual) pintarFotos();
}

function pintarFotos() {
  const evidencias = incActual.evidencias || [];
  const reporte = evidencias.filter((ev) => ev.tipo_evidencia !== "RESOLUCION");
  const resolucion = evidencias.filter((ev) => ev.tipo_evidencia === "RESOLUCION");

  montarCarrusel(
    document.getElementById("fotosReporte"),
    reporte,
    "Sin fotos del reporte.",
    opcionesFotosReporte,
  );
  montarCarrusel(
    document.getElementById("fotosResolucion"),
    resolucion,
    "Sin fotos de resolución.",
    opcionesFotosResolucion,
  );
}

// Cablea el toggle Reportador/Técnico de la tarjeta de fotos (siempre visible, una vez).
function prepararToggleFotos() {
  document.getElementById("fotosToggleGrupo").addEventListener("click", function (e) {
    const btn = e.target.closest("[data-tab]");
    if (!btn) return;
    const esReporte = btn.dataset.tab === "reporte";
    document.getElementById("tabReporte").classList.toggle("d-none", !esReporte);
    document.getElementById("fotosResolucionBloque").classList.toggle("d-none", esReporte);
    document.getElementById("btnFotosReportador").classList.toggle("active", esReporte);
    document.getElementById("btnFotosTecnico").classList.toggle("active", !esReporte);
  });
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

    // Tarjeta de la barra de meta-información (solo el responsable, el apoyo no).
    document.getElementById("metaTecnicoResponsable").textContent =
      responsable && responsable.usuario ? responsable.usuario.name : "Sin asignar";

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

// Sin grupos visibles en Acciones, oculta el sidebar y el contenido principal ocupa todo el ancho
function ajustarLayout() {
  const col = document.getElementById("colGestion");
  const panel = document.getElementById("panelAcciones");
  if (!col || !panel) return;
  const tieneContenido = Array.from(panel.querySelectorAll(".solo-admin, .gestion-estado")).some(
    (el) => !el.classList.contains("d-none"),
  );
  col.classList.toggle("d-none", !tieneContenido);
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

    // La API entrega lo más reciente primero; para la línea de tiempo (izquierda→derecha,
    // antiguo→actual) se recorre al revés. El último paso (el más reciente) es el "actual".
    const pasos = eventos.slice().reverse();

    // El ciudadano ve CERRADO como "Resuelto" (estados.js): se colapsan pasos consecutivos que se ven igual para no repetir "Resuelto" en la línea de tiempo.
    const vista = (ev) => estadoParaVista(ev.estado, rolNombre());
    const pasosVisibles = pasos.filter(function (ev, i) {
      return i === 0 || vista(ev) !== vista(pasos[i - 1]);
    });

    cont.innerHTML = "";
    pasosVisibles.forEach(function (ev, i) {
      const estadoMostrado = estadoParaVista(ev.estado, rolNombre());
      const cfg = estadoConfig[estadoMostrado] || estadoConfig.PENDIENTE;
      const esActual = i === pasosVisibles.length - 1;

      const paso = document.createElement("div");
      paso.className = "historial-paso" + (esActual ? " historial-paso--actual" : "");

      const punto = document.createElement("span");
      punto.className = "historial-paso-punto";
      punto.style.background = colorEstado(estadoMostrado);
      // "color" (no solo background) para que el anillo del paso actual (currentColor) tome el mismo tono.
      punto.style.color = colorEstado(estadoMostrado);
      paso.appendChild(punto);

      const texto = document.createElement("div");
      texto.className = "historial-paso-texto";

      const estadoSpan = document.createElement("span");
      estadoSpan.className = "historial-paso-estado";
      estadoSpan.style.color = colorEstado(ev.estado);
      estadoSpan.textContent = cfg.texto;
      texto.appendChild(estadoSpan);

      const quienSpan = document.createElement("span");
      quienSpan.className = "historial-paso-quien";
      quienSpan.textContent = ev.nombre;
      texto.appendChild(quienSpan);

      const fechaSpan = document.createElement("span");
      fechaSpan.className = "historial-paso-fecha";
      fechaSpan.textContent = new Date(ev.fecha).toLocaleString("es-EC");
      texto.appendChild(fechaSpan);

      paso.appendChild(texto);
      cont.appendChild(paso);
    });
  } catch {
    cont.innerHTML = '<p class="text-danger small mb-0">No se pudo cargar el historial.</p>';
  }
}

// Ven el chat el admin/super_admin (aunque este último no escriba), el reportador y el técnico responsable (el apoyo queda fuera).
function puedeUsarChat() {
  if (!usuarioActual) return false;
  if (esRolAdmin) return true;
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

// Por qué no se puede escribir en el chat, con el mismo orden que IncidenciaPolicy::comentar.
// Cadena vacía = se puede escribir; el texto se le muestra a quien mira, así que dice qué hacer.
function motivoChatSoloLectura() {
  if (
    incActual &&
    (incActual.estado_incidencia === "RESUELTO" || incActual.estado_incidencia === "CERRADO")
  ) {
    return "La incidencia está resuelta: el chat es solo de lectura.";
  }

  // Responder en el propio reporte es la vía del autor, no gestión: queda fuera del candado.
  const esAutor = incActual && incActual.id_usuario === usuarioActual.id;
  if (!esRolAdmin || esAutor) return "";

  if (!esAdmin) return "Tu rol solo puede leer el chat de esta incidencia.";

  return soyDuenoDelReclamo() ? "" : "Reclama la incidencia para escribir en el chat.";
}

let chatCreado = false;
let chatAbiertoAuto = false;

function configurarChatFlotante(id) {
  const fab = document.getElementById("btnChatFab");
  const panel = document.getElementById("chatPanel");
  const cerrar = document.getElementById("btnCerrarChat");

  fab.addEventListener("click", function () {
    panel.classList.remove("d-none");
    fab.classList.add("d-none");
    pintarParticipantes();
    if (!chatCreado) {
      crearChat("chatContenedor", id, usuarioActual, {
        avisoSoloLectura: motivoChatSoloLectura(),
      });
      chatCreado = true;
    }
  });

  // El auto-open por ?chat=1 necesita el listener de arriba ya registrado antes de simular el click
  actualizarChatFab();

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

// Pinta la cabecera del chat con el interlocutor principal (foto/perfil o iniciales + nombre + rol).
function pintarInterlocutorCabecera() {
  const cont = document.getElementById("chatInterlocutor");
  if (!cont || !incActual || !usuarioActual) return;

  let nombre = "";
  let rol = "";
  let foto = null;

  // Orden de prioridad del interlocutor: reportador → técnico responsable → Administración.
  if (incActual.usuario && incActual.usuario.id !== usuarioActual.id) {
    nombre = incActual.usuario.name;
    rol = "Reportador";
    foto = incActual.usuario.foto_perfil || null;
  } else if (
    responsableActual &&
    responsableActual.usuario &&
    responsableActual.usuario.id !== usuarioActual.id
  ) {
    nombre = responsableActual.usuario.name;
    rol = "Técnico responsable";
    foto = responsableActual.usuario.foto_perfil || null;
  } else if (!tienePermiso("incidencias.gestionar")) {
    nombre = "Administración";
    rol = "Administrador";
  }

  // Sin interlocutor (p. ej. conversación con uno mismo): se deja el título estático de fallback.
  if (!nombre) return;

  cont.replaceChildren();

  const avatar = document.createElement("span");
  avatar.className = "chat-interlocutor-avatar";
  if (foto) {
    // "foto" ya es la URL firmada completa que manda el backend, no una ruta cruda.
    const img = document.createElement("img");
    img.src = foto;
    img.alt = "";
    avatar.appendChild(img);
  } else {
    avatar.textContent = iniciales(nombre);
  }

  const info = document.createElement("span");
  info.className = "chat-interlocutor-info";
  const spanNombre = document.createElement("span");
  spanNombre.className = "chat-interlocutor-nombre";
  spanNombre.textContent = nombre;
  const spanRol = document.createElement("span");
  spanRol.className = "chat-interlocutor-rol";
  spanRol.textContent = rol;
  info.append(spanNombre, spanRol);

  cont.append(avatar, info);
}

// Pinta los otros participantes del chat (todos menos quien mira).
function pintarParticipantes() {
  pintarInterlocutorCabecera();
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
