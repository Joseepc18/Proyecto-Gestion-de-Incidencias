// notificaciones.js — Campana del navbar: lista, contador sin leer y marcar como leídas.

/* global apiFetch, obtenerToken, obtenerEcho, rutaDetalleIncidencia, tiempoRelativo */

document.addEventListener("DOMContentLoaded", function () {
  const boton = document.getElementById("btnNotificaciones");
  const badge = document.getElementById("notifBadge");
  const lista = document.getElementById("notifLista");
  const btnTodas = document.getElementById("btnMarcarTodas");
  if (!boton || !lista || !obtenerToken()) return;

  function pintarBadge(noLeidas) {
    if (noLeidas > 0) {
      badge.textContent = noLeidas > 9 ? "9+" : String(noLeidas);
      badge.classList.remove("d-none");
      btnTodas.classList.remove("d-none");
    } else {
      badge.classList.add("d-none");
      btnTodas.classList.add("d-none");
    }
  }

  function pintarLista(notificaciones) {
    lista.innerHTML = "";

    if (notificaciones.length === 0) {
      const vacio = document.createElement("p");
      vacio.className = "notification-empty";
      vacio.textContent = "No tienes notificaciones.";
      lista.appendChild(vacio);
      return;
    }

    notificaciones.forEach(function (n) {
      const item = document.createElement("button");
      item.type = "button";
      // "no-leida" sigue el estado real; el CSS de alerta la pinta roja igual aunque se marque leída
      const esAlerta = n.tipo === "SOLICITUD_REAPERTURA";
      const clases = ["notification-item"];
      if (esAlerta) clases.push("notification-alert");
      if (!n.estado_lectura) clases.push("no-leida");
      item.className = clases.join(" ");
      item.dataset.id = n.id;
      item.dataset.incidencia = n.id_incidencia;
      item.dataset.tipo = n.tipo;

      const msg = document.createElement("span");
      msg.className = "notification-msg";
      msg.textContent = n.mensaje;

      const hora = document.createElement("span");
      hora.className = "notification-time";
      hora.textContent = tiempoRelativo(n.created_at);

      item.append(msg, hora);
      lista.appendChild(item);
    });
  }

  async function cargar() {
    try {
      const data = await apiFetch("/notificaciones", { sinSpinner: true });
      pintarBadge(data.no_leidas);
      pintarLista(data.notificaciones);
    } catch {
      /* silencioso: si falla, la campana solo no se actualiza */
    }
  }

  lista.addEventListener("click", async function (evento) {
    const item = evento.target.closest(".notification-item");
    if (!item) return;

    if (item.classList.contains("no-leida")) {
      try {
        await apiFetch("/notificaciones/" + item.dataset.id + "/leida", {
          method: "PATCH",
          sinSpinner: true,
        });
      } catch {
        /* aunque falle el marcado, igual vamos a la incidencia */
      }
      item.classList.remove("no-leida");
    }

    // INCIDENCIA_ELIMINADA no tiene incidencia a la cual ir: se borró físico
    if (!item.dataset.incidencia || item.dataset.incidencia === "null") {
      window.location.href = "../notificaciones/notificaciones.html";
      return;
    }

    const rol = localStorage.getItem("rol_usuario") || "";
    const abrirChat = item.dataset.tipo === "COMENTARIO";
    window.location.href = rutaDetalleIncidencia(item.dataset.incidencia, rol, abrirChat);
  });

  btnTodas.addEventListener("click", async function () {
    try {
      await apiFetch("/notificaciones/leer-todas", { method: "PATCH", sinSpinner: true });
      cargar();
    } catch {
      /* silencioso */
    }
  });

  // Push en vivo: la campana se actualiza sola en cuanto llega una notificación por WebSocket.
  let suscrito = false;
  function suscribir(idUsuario) {
    if (suscrito || !idUsuario) return;
    const echo = obtenerEcho();
    if (!echo) return;
    suscrito = true;
    // .notification() escucha el evento nativo que Laravel emite al canal privado del usuario.
    echo.private("App.Models.User." + idUsuario).notification(function () {
      cargar();
    });
  }

  // El id puede estar ya cacheado (páginas siguientes) o llegar cuando requerirSesion resuelve.
  suscribir(localStorage.getItem("usuario_id"));
  window.addEventListener("sesion-lista", function (e) {
    suscribir(e.detail.id);
  });

  // Al volver a la pestaña, reconciliamos por si algún push se perdió mientras estaba oculta.
  document.addEventListener("visibilitychange", function () {
    if (!document.hidden) cargar();
  });

  cargar();
});
