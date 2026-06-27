// chat.js — Chat reutilizable de una incidencia (reportador ↔ admin ↔ técnico responsable).

/* global apiFetch */
/* exported crearChat */

// Etiqueta legible del rol del autor de un mensaje.
function etiquetaRol(rol) {
  const mapa = { admin: "Administrador", tecnico: "Técnico", normal: "Reportador" };
  return mapa[rol] || "Usuario";
}

// idContenedor: div del chat; idIncidencia: hilo; usuario: autenticado (para "Tú").
function crearChat(idContenedor, idIncidencia, usuario) {
  const cont = document.getElementById(idContenedor);
  if (!cont) return null;

  cont.innerHTML =
    '<div class="chat-mensajes" id="chatMensajes"></div>' +
    '<form class="chat-form" id="chatForm">' +
    '<textarea class="form-control" id="chatTexto" rows="1" ' +
    'placeholder="Escribe un mensaje…" required></textarea>' +
    '<button class="chat-enviar" type="submit" aria-label="Enviar">' +
    '<span class="spinner-border spinner-border-sm d-none" id="chatSpinner"></span>' +
    '<i class="bi bi-send" id="chatIcono"></i></button></form>';

  const mensajes = cont.querySelector("#chatMensajes");
  const form = cont.querySelector("#chatForm");
  const texto = cont.querySelector("#chatTexto");
  const spinner = cont.querySelector("#chatSpinner");
  const icono = cont.querySelector("#chatIcono");

  // Trae el hilo y lo pinta como burbujas.
  async function recargar() {
    try {
      const comentarios = await apiFetch("/incidencias/" + idIncidencia + "/comentarios", {
        sinSpinner: true,
      });

      if (comentarios.length === 0) {
        mensajes.innerHTML = '<p class="chat-vacio">Aún no hay mensajes. Escribe el primero.</p>';
        return;
      }

      mensajes.innerHTML = "";
      comentarios.forEach(function (c) {
        const propio = c.usuario && c.usuario.id === usuario.id;
        const fecha = new Date(c.created_at).toLocaleString("es-EC", {
          day: "2-digit",
          month: "short",
          hour: "2-digit",
          minute: "2-digit",
        });

        const fila = document.createElement("div");
        fila.className = "chat-fila" + (propio ? " propio" : "");

        const meta = document.createElement("p");
        meta.className = "chat-meta";
        meta.textContent = propio
          ? "Tú · " + fecha
          : (c.usuario ? c.usuario.name : "Usuario") +
            " · " +
            etiquetaRol(c.usuario && c.usuario.rol ? c.usuario.rol.nombre_rol : "") +
            " · " +
            fecha;

        const burbuja = document.createElement("div");
        burbuja.className = "chat-burbuja";
        burbuja.textContent = c.comentario;

        fila.append(meta, burbuja);
        mensajes.appendChild(fila);
      });

      // Baja el scroll al último mensaje.
      mensajes.scrollTop = mensajes.scrollHeight;
    } catch (error) {
      mensajes.innerHTML = '<p class="chat-vacio text-danger">' + error.message + "</p>";
    }
  }

  // Enviar un mensaje.
  form.addEventListener("submit", async function (e) {
    e.preventDefault();
    const valor = texto.value.trim();
    if (!valor) return;

    texto.disabled = true;
    spinner.classList.remove("d-none");
    icono.classList.add("d-none");

    try {
      await apiFetch("/incidencias/" + idIncidencia + "/comentarios", {
        method: "POST",
        body: JSON.stringify({ comentario: valor }),
        sinSpinner: true,
      });
      texto.value = "";
      await recargar();
    } catch (error) {
      mensajes.insertAdjacentHTML(
        "beforeend",
        '<p class="chat-vacio text-danger">No se pudo enviar: ' + error.message + "</p>",
      );
    } finally {
      texto.disabled = false;
      spinner.classList.add("d-none");
      icono.classList.remove("d-none");
      texto.focus();
    }
  });

  recargar();
  return { recargar };
}
