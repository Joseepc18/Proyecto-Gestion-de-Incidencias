// notificaciones.js — Bandeja completa (NO confundir con assets/js/notificaciones.js, la campana).

/* global apiFetch, aplicarMenuRol, mostrarToast, rutaDetalleIncidencia, tiempoRelativo, estadoVacioHtml, requerirSesion, cablearLogout */

let usuarioActual = null;
let notificaciones = [];
// "todas" | "no_leidas"
let filtroActual = "todas";

// Icono de Bootstrap Icons según el tipo de notificación.
function iconoTipo(tipo) {
  const iconos = {
    ASIGNACION: "bi-person-check",
    CAMBIO_ESTADO: "bi-arrow-repeat",
    COMENTARIO: "bi-chat-dots",
    NUEVA_INCIDENCIA: "bi-exclamation-triangle",
    EVIDENCIA: "bi-camera",
    INCIDENCIA_ELIMINADA: "bi-trash",
    SOLICITUD_REAPERTURA: "bi-arrow-counterclockwise",
  };
  return iconos[tipo] || "bi-bell";
}

// Pinta el contador "N sin leer" y habilita/inhabilita "Marcar todas".
function actualizarContador(noLeidas) {
  const span = document.getElementById("contadorNoLeidas");
  const btnTodas = document.getElementById("btnMarcarTodasPag");
  span.textContent = noLeidas > 0 ? "(" + noLeidas + " sin leer)" : "";
  btnTodas.disabled = noLeidas === 0;
}

// createElement + textContent (sin innerHTML) para no abrir un XSS
function filaNotificacion(n, esHija) {
  const item = document.createElement("button");
  item.type = "button";
  // "no-leida" sigue el estado real; si no, un clic nunca la marcaría como leída
  const esAlerta = n.tipo_notificacion === "SOLICITUD_REAPERTURA";
  item.className =
    "notification-item notif-page-item" +
    (esAlerta ? " notification-alert" : "") +
    (n.estado_lectura ? "" : " no-leida") +
    (esHija ? " notif-hija" : "");
  item.dataset.id = n.id_notificacion;
  item.dataset.incidencia = n.id_incidencia;
  item.dataset.tipo = n.tipo_notificacion;

  const linea = document.createElement("div");
  linea.className = "notif-line";

  const icono = document.createElement("span");
  icono.className = "notif-icon";
  const i = document.createElement("i");
  i.className = "bi " + iconoTipo(n.tipo_notificacion);
  icono.appendChild(i);

  const body = document.createElement("div");
  body.className = "notif-body";

  const msg = document.createElement("span");
  msg.className = "notification-msg";
  msg.textContent = n.mensaje_notificacion;

  const hora = document.createElement("span");
  hora.className = "notification-time";
  hora.textContent = tiempoRelativo(n.created_at);

  body.append(msg, hora);
  linea.append(icono, body);
  item.appendChild(linea);
  return item;
}

// La más reciente queda como cabecera navegable + chevron que despliega el resto
function grupoNotificaciones(grupo) {
  const wrap = document.createElement("div");
  wrap.className = "notif-group";

  const fila = document.createElement("div");
  fila.className = "notif-group-row";

  const cabecera = filaNotificacion(grupo[0], false);
  cabecera.classList.add("notif-cabecera");

  const badge = document.createElement("span");
  badge.className = "notif-count";
  badge.textContent = grupo.length;
  cabecera.querySelector(".notif-line").appendChild(badge);

  const chevron = document.createElement("button");
  chevron.type = "button";
  chevron.className = "notif-chevron";
  chevron.setAttribute("aria-label", "Desplegar notificaciones");
  const ch = document.createElement("i");
  ch.className = "bi bi-chevron-down";
  chevron.appendChild(ch);

  fila.append(cabecera, chevron);

  const hijas = document.createElement("div");
  hijas.className = "notif-hijas d-none";
  grupo.slice(1).forEach(function (n) {
    hijas.appendChild(filaNotificacion(n, true));
  });

  wrap.append(fila, hijas);
  return wrap;
}

// Dibuja la lista agrupando por incidencia (una fila por incidencia; el resto se despliega).
function render() {
  const cont = document.getElementById("listaNotificaciones");
  cont.innerHTML = "";

  const visibles =
    filtroActual === "no_leidas"
      ? notificaciones.filter(function (n) {
          return !n.estado_lectura;
        })
      : notificaciones;

  if (visibles.length === 0) {
    // Contenido estático (sin datos del usuario), seguro de inyectar como HTML.
    cont.innerHTML =
      filtroActual === "no_leidas"
        ? estadoVacioHtml("bi-check2-all", "Todo al día", "No tienes notificaciones sin leer.")
        : estadoVacioHtml(
            "bi-bell",
            "Sin notificaciones",
            "Aquí verás los avisos de tus incidencias.",
          );
    return;
  }

  // Las INCIDENCIA_ELIMINADA (sin incidencia) van cada una en su propio grupo
  const grupos = [];
  const indice = new Map();
  visibles.forEach(function (n) {
    const clave = n.id_incidencia ? "i" + n.id_incidencia : "n" + n.id_notificacion;
    let grupo = indice.get(clave);
    if (!grupo) {
      grupo = [];
      indice.set(clave, grupo);
      grupos.push(grupo);
    }
    grupo.push(n);
  });

  grupos.forEach(function (grupo) {
    cont.appendChild(
      grupo.length === 1 ? filaNotificacion(grupo[0], false) : grupoNotificaciones(grupo),
    );
  });
}

// Trae las notificaciones del usuario y refresca la vista.
async function cargar() {
  try {
    const data = await apiFetch("/notificaciones");
    notificaciones = data.notificaciones || [];
    actualizarContador(data.no_leidas);
    render();
  } catch (error) {
    mostrarToast("No se pudieron cargar las notificaciones: " + error.message, "error");
  }
}

document.addEventListener("DOMContentLoaded", async function () {
  usuarioActual = await requerirSesion();
  if (!usuarioActual) return;
  aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "", usuarioActual.permisos);
  cablearLogout();

  document.querySelectorAll("[data-filtro]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      filtroActual = btn.dataset.filtro;
      document.querySelectorAll("[data-filtro]").forEach(function (b) {
        b.classList.toggle("active", b === btn);
      });
      render();
    });
  });

  document.getElementById("btnMarcarTodasPag").addEventListener("click", async function () {
    try {
      await apiFetch("/notificaciones/leer-todas", { method: "PATCH" });
      await cargar();
    } catch (error) {
      mostrarToast("No se pudieron marcar como leídas: " + error.message, "error");
    }
  });

  document.getElementById("listaNotificaciones").addEventListener("click", async function (evento) {
    // El chevron solo despliega/colapsa el resto del grupo, no navega.
    const chevron = evento.target.closest(".notif-chevron");
    if (chevron) {
      const hijas = chevron.closest(".notif-group").querySelector(".notif-hijas");
      hijas.classList.toggle("d-none");
      chevron.classList.toggle("abierto");
      return;
    }

    const item = evento.target.closest(".notification-item");
    if (!item) return;

    if (item.classList.contains("no-leida")) {
      try {
        await apiFetch("/notificaciones/" + item.dataset.id + "/leida", {
          method: "PATCH",
          sinSpinner: true,
        });
      } catch {
        /* aunque falle el marcado, seguimos a la incidencia */
      }
      item.classList.remove("no-leida");
    }

    // Las de INCIDENCIA_ELIMINADA no tienen incidencia a la cual ir (se borró físico).
    if (!item.dataset.incidencia || item.dataset.incidencia === "null") return;

    const rol = usuarioActual.rol ? usuarioActual.rol.nombre_rol : "";
    const abrirChat = item.dataset.tipo === "COMENTARIO";
    window.location.href = rutaDetalleIncidencia(item.dataset.incidencia, rol, abrirChat);
  });

  cargar();
});
