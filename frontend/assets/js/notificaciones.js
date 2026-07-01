// notificaciones.js — Campana del navbar: lista, contador sin leer y marcar como leídas.

/* global apiFetch, obtenerToken, rutaDetalleIncidencia, tiempoRelativo */

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
      item.className = "notification-item" + (n.estado_lectura ? "" : " no-leida");
      item.dataset.id = n.id_notificacion;
      item.dataset.incidencia = n.id_incidencia;
      item.dataset.tipo = n.tipo_notificacion;

      const msg = document.createElement("span");
      msg.className = "notification-msg";
      msg.textContent = n.mensaje_notificacion;

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

    // Las de INCIDENCIA_ELIMINADA no tienen incidencia a la cual ir (se borró físico):
    // se lleva a la bandeja para que el usuario lea el motivo completo.
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

  let timerNotif;
  function programarRefresco() {
    clearTimeout(timerNotif);
    if (!document.hidden) {
      timerNotif = setTimeout(async function () {
        await cargar();
        programarRefresco();
      }, 30000);
    }
  }

  document.addEventListener("visibilitychange", function () {
    if (!document.hidden) {
      cargar();
      programarRefresco();
    } else {
      clearTimeout(timerNotif);
    }
  });

  cargar();
  programarRefresco();
});
