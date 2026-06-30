// notificaciones.js — Bandeja completa (NO confundir con assets/js/notificaciones.js, la campana).

/* global apiFetch, aplicarMenuRol, rutaDetalleIncidencia, tiempoRelativo, requerirSesion, cablearLogout */

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

// Dibuja la lista con createElement + textContent (sin innerHTML) para no abrir un XSS.
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
    const vacio = document.createElement("p");
    vacio.className = "notification-empty";
    vacio.textContent =
      filtroActual === "no_leidas"
        ? "No tienes notificaciones sin leer."
        : "No tienes notificaciones.";
    cont.appendChild(vacio);
    return;
  }

  visibles.forEach(function (n) {
    const item = document.createElement("button");
    item.type = "button";
    item.className = "notification-item notif-page-item" + (n.estado_lectura ? "" : " no-leida");
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
    cont.appendChild(item);
  });
}

// Trae las notificaciones del usuario y refresca la vista.
async function cargar() {
  try {
    const data = await apiFetch("/notificaciones");
    notificaciones = data.notificaciones || [];
    actualizarContador(data.no_leidas);
    render();
  } catch {
    /* apiFetch ya mostró el error */
  }
}

document.addEventListener("DOMContentLoaded", async function () {
  usuarioActual = await requerirSesion();
  if (!usuarioActual) return;
  aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "");
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
    } catch {
      /* apiFetch ya mostró el error */
    }
  });

  document.getElementById("listaNotificaciones").addEventListener("click", async function (evento) {
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
    }
    const rol = usuarioActual.rol ? usuarioActual.rol.nombre_rol : "";
    const abrirChat = item.dataset.tipo === "COMENTARIO";
    window.location.href = rutaDetalleIncidencia(item.dataset.incidencia, rol, abrirChat);
  });

  cargar();
});
