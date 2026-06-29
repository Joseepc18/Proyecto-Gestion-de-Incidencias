// chat.js — Chat reutilizable de una incidencia (reportador ↔ admin ↔ técnico responsable).

/* global apiFetch, obtenerToken, Echo, Pusher */
/* exported crearChat */

// Etiqueta legible del rol del autor de un mensaje.
function etiquetaRol(rol) {
  const mapa = { admin: "Administrador", tecnico: "Técnico", normal: "Reportador" };
  return mapa[rol] || "Usuario";
}

// Iniciales para el avatar del chat cuando el contacto no tiene foto.
function inicialesChat(nombre) {
  const p = (nombre || "").trim().split(/\s+/);
  const a = p[0] ? p[0][0] : "";
  const b = p[1] ? p[1][0] : "";
  return (a + b).toUpperCase() || "?";
}

// Instancia única de Echo para toda la página (evita abrir varias conexiones WebSocket).
let echoSingleton = null;

// Crea (o reutiliza) el cliente de Echo apuntando al servidor Reverb por detrás de Nginx.
function obtenerEcho() {
  if (echoSingleton) return echoSingleton;
  if (typeof Echo === "undefined" || typeof Pusher === "undefined") return null;

  const esHttps = window.location.protocol === "https:";
  echoSingleton = new Echo({
    broadcaster: "reverb",
    key: "incidencias-key",
    wsHost: window.location.hostname,
    wsPort: esHttps ? 443 : 80,
    wssPort: esHttps ? 443 : 80,
    forceTLS: esHttps,
    enabledTransports: ["ws", "wss"],
    authEndpoint: "/api/broadcasting/auth",
    auth: { headers: { Authorization: "Bearer " + obtenerToken() } },
  });
  return echoSingleton;
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
  const botonEnviar = cont.querySelector(".chat-enviar");
  const spinner = cont.querySelector("#chatSpinner");
  const icono = cont.querySelector("#chatIcono");

  // Ids ya pintados, para no duplicar el mensaje propio (llega por POST y también por WebSocket).
  const idsPintados = new Set();

  // Pinta una burbuja a partir de un comentario (de la lista REST o del evento en tiempo real).
  function pintarMensaje(c, suave) {
    if (c.id_comentario && idsPintados.has(c.id_comentario)) return;
    if (c.id_comentario) idsPintados.add(c.id_comentario);

    const vacio = mensajes.querySelector(".chat-vacio");
    if (vacio) vacio.remove();

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

    // Avatar del contacto (solo en mensajes ajenos): foto de perfil o iniciales.
    if (!propio) {
      const avatar = document.createElement("span");
      avatar.className = "chat-avatar";
      const foto = c.usuario && c.usuario.foto_perfil;
      if (foto) {
        const img = document.createElement("img");
        img.src = "/storage/" + foto;
        img.alt = "";
        avatar.appendChild(img);
      } else {
        avatar.textContent = inicialesChat(c.usuario ? c.usuario.name : "");
      }
      meta.appendChild(avatar);
    }

    const metaTexto = document.createElement("span");
    metaTexto.textContent = propio
      ? "Tú · " + fecha
      : (c.usuario ? c.usuario.name : "Usuario") +
        " · " +
        etiquetaRol(c.usuario && c.usuario.rol ? c.usuario.rol.nombre_rol : "") +
        " · " +
        fecha;
    meta.appendChild(metaTexto);

    const burbuja = document.createElement("div");
    burbuja.className = "chat-burbuja";
    burbuja.textContent = c.comentario;

    fila.append(meta, burbuja);
    mensajes.appendChild(fila);
    mensajes.scrollTo({ top: mensajes.scrollHeight, behavior: suave ? "smooth" : "instant" });
  }

  // Trae el hilo completo y lo repinta. suave: scroll animado (solo al enviar, no al cargar).
  async function recargar(suave) {
    try {
      const comentarios = await apiFetch("/incidencias/" + idIncidencia + "/comentarios", {
        sinSpinner: true,
      });

      mensajes.innerHTML = "";
      idsPintados.clear();

      if (comentarios.length === 0) {
        mensajes.innerHTML = '<p class="chat-vacio">Aún no hay mensajes. Escribe el primero.</p>';
        return;
      }

      comentarios.forEach((c) => pintarMensaje(c, false));
      mensajes.scrollTo({ top: mensajes.scrollHeight, behavior: suave ? "smooth" : "instant" });
    } catch (error) {
      const p = document.createElement("p");
      p.className = "chat-vacio text-danger";
      p.textContent = error.message;
      mensajes.replaceChildren(p);
    }
  }

  texto.addEventListener("keydown", function (e) {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      form.requestSubmit();
    }
  });

  form.addEventListener("submit", async function (e) {
    e.preventDefault();
    const valor = texto.value.trim();
    if (!valor) return;

    texto.disabled = true;
    botonEnviar.disabled = true;
    spinner.classList.remove("d-none");
    icono.classList.add("d-none");

    try {
      const creado = await apiFetch("/incidencias/" + idIncidencia + "/comentarios", {
        method: "POST",
        body: JSON.stringify({ comentario: valor }),
        sinSpinner: true,
      });
      texto.value = "";
      // Pinta el propio al instante; si luego llega por WebSocket, el dedupe lo ignora.
      pintarMensaje(creado, true);
    } catch (error) {
      const p = document.createElement("p");
      p.className = "chat-vacio text-danger";
      p.textContent = "No se pudo enviar: " + error.message;
      mensajes.appendChild(p);
    } finally {
      texto.disabled = false;
      botonEnviar.disabled = false;
      spinner.classList.add("d-none");
      icono.classList.remove("d-none");
      texto.focus();
    }
  });

  // Suscripción en tiempo real: pinta los mensajes de los demás en cuanto llegan.
  let canal = null;
  const echo = obtenerEcho();
  if (echo) {
    canal = echo.private("incidencia." + idIncidencia);
    canal.listen(".ComentarioCreado", (c) => pintarMensaje(c, true));
  }

  // Cierra la suscripción del canal (lo llama la página al cambiar de incidencia).
  function detener() {
    if (canal && echoSingleton) echoSingleton.leave("incidencia." + idIncidencia);
  }

  recargar();
  return { recargar, detener };
}
