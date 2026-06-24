// notificaciones.js — Campana del navbar. Lista las notificaciones del usuario,
// muestra el contador sin leer y permite marcarlas como leídas. Se carga en las
// páginas del panel, después de layout.js (que inyecta el botón en el navbar).

/* global apiFetch, obtenerToken */

document.addEventListener("DOMContentLoaded", function () {
  const boton = document.getElementById("btnNotificaciones");
  const badge = document.getElementById("notifBadge");
  const lista = document.getElementById("notifLista");
  const btnTodas = document.getElementById("btnMarcarTodas");
  if (!boton || !lista || !obtenerToken()) return;

  // Convierte una fecha ISO en texto relativo ("hace 5 min", "hace 2 h"...).
  function tiempoRelativo(iso) {
    const fecha = new Date(iso);
    const seg = Math.floor((Date.now() - fecha.getTime()) / 1000);
    if (seg < 60) return "hace un momento";
    if (seg < 3600) return "hace " + Math.floor(seg / 60) + " min";
    if (seg < 86400) return "hace " + Math.floor(seg / 3600) + " h";
    if (seg < 604800) return "hace " + Math.floor(seg / 86400) + " d";
    return fecha.toLocaleDateString("es-EC");
  }

  // Pinta el contador del badge (se oculta en 0, muestra "9+" si pasa de 9).
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

  // Dibuja la lista. Cada item es un botón para poder marcarlo como leído.
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

  // Trae las notificaciones del backend. sinSpinner: el polling no debe mover la ruedita.
  async function cargar() {
    try {
      const data = await apiFetch("/notificaciones", { sinSpinner: true });
      pintarBadge(data.no_leidas);
      pintarLista(data.notificaciones);
    } catch {
      /* silencioso: si falla, la campana solo no se actualiza */
    }
  }

  // Click en un item: si no estaba leído, lo marca en el backend y refresca.
  lista.addEventListener("click", async function (evento) {
    const item = evento.target.closest(".notification-item");
    if (!item || !item.classList.contains("no-leida")) return;

    try {
      await apiFetch("/notificaciones/" + item.dataset.id + "/leida", {
        method: "PATCH",
        sinSpinner: true,
      });
      cargar();
    } catch {
      /* silencioso */
    }
  });

  btnTodas.addEventListener("click", async function () {
    try {
      await apiFetch("/notificaciones/leer-todas", { method: "PATCH", sinSpinner: true });
      cargar();
    } catch {
      /* silencioso */
    }
  });

  cargar();
  // Refresco periódico para enterarse de novedades sin recargar la página.
  setInterval(cargar, 30000);
});
