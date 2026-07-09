// chat.js — Chat reutilizable de una incidencia (reportador ↔ admin ↔ técnico responsable).

/* global apiFetch, obtenerEcho, iniciales, etiquetaRol */
/* exported crearChat */

// opts.soloLectura deshabilita el input y muestra un aviso (incidencia RESUELTO)
function crearChat(idContenedor, idIncidencia, usuario, opts) {
  const cont = document.getElementById(idContenedor);
  if (!cont) return null;
  const soloLectura = !!(opts && opts.soloLectura);

  const inputHtml = soloLectura
    ? '<p class="chat-solo-lectura">La incidencia está resuelta: el chat es solo de lectura.</p>'
    : '<form class="chat-form" id="chatForm">' +
      '<textarea class="form-control" id="chatTexto" rows="1" ' +
      'placeholder="Escribe un mensaje…" required></textarea>' +
      '<button class="chat-enviar" type="submit" aria-label="Enviar">' +
      '<span class="spinner-border spinner-border-sm d-none" id="chatSpinner"></span>' +
      '<i class="bi bi-send" id="chatIcono"></i></button></form>';

  cont.innerHTML = '<div class="chat-mensajes" id="chatMensajes"></div>' + inputHtml;

  const mensajes = cont.querySelector("#chatMensajes");
  const form = soloLectura ? null : cont.querySelector("#chatForm");
  const texto = soloLectura ? null : cont.querySelector("#chatTexto");
  const botonEnviar = soloLectura ? null : cont.querySelector(".chat-enviar");
  const spinner = soloLectura ? null : cont.querySelector("#chatSpinner");
  const icono = soloLectura ? null : cont.querySelector("#chatIcono");

  // Ids ya pintados, para no duplicar el mensaje propio (llega por POST y también por WebSocket).
  const idsPintados = new Set();
  // Día (YYYY-MM-DD local) y usuario del último mensaje pintado: para separador de fecha y agrupación.
  let ultimoDia = null;
  let ultimoUsuarioId = null;

  // Fecha legible del separador de día, ej. "28 jun 2026".
  function textoDia(d) {
    return d
      .toLocaleDateString("es-EC", { day: "2-digit", month: "short", year: "numeric" })
      .replace(/\./g, "");
  }

  // Clave del día en zona horaria local (no UTC) para comparar días entre mensajes.
  function claveDia(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const dia = String(d.getDate()).padStart(2, "0");
    return y + "-" + m + "-" + dia;
  }

  // Hora legible dentro de la burbuja, ej. "10:59 p. m.".
  function textoHora(d) {
    return d.toLocaleTimeString("es-EC", { hour: "2-digit", minute: "2-digit" });
  }

  // Pinta una burbuja a partir de un comentario (de la lista REST o del evento en tiempo real).
  function pintarMensaje(c, suave) {
    if (c.id_comentario && idsPintados.has(c.id_comentario)) return;
    if (c.id_comentario) idsPintados.add(c.id_comentario);

    const vacio = mensajes.querySelector(".chat-vacio");
    if (vacio) vacio.remove();

    const propio = c.usuario && c.usuario.id === usuario.id;
    const d = new Date(c.created_at);
    const dia = claveDia(d);
    const cambioDia = dia !== ultimoDia;
    const cambioUsuario = !c.usuario || c.usuario.id !== ultimoUsuarioId;
    // Mostrar meta (avatar + nombre) solo al inicio de cada bloque: primer mensaje, cambio de día o cambio de autor.
    const mostrarMeta = ultimoDia === null || cambioDia || cambioUsuario;

    // Separador de fecha centrado, una sola vez por día.
    if (cambioDia) {
      const sep = document.createElement("div");
      sep.className = "chat-separador-fecha";
      sep.textContent = textoDia(d);
      mensajes.appendChild(sep);
    }

    const fila = document.createElement("div");
    fila.className = "chat-fila" + (propio ? " propio" : "");

    if (mostrarMeta) {
      const meta = document.createElement("p");
      meta.className = "chat-meta";

      // Avatar del contacto (solo en mensajes ajenos): foto de perfil o iniciales.
      if (!propio) {
        const avatar = document.createElement("span");
        avatar.className = "chat-avatar";
        const foto = c.usuario && c.usuario.foto_perfil;
        if (foto) {
          // "foto" ya es la URL firmada completa que manda el backend, no una ruta cruda.
          const img = document.createElement("img");
          img.src = foto;
          img.alt = "";
          avatar.appendChild(img);
        } else {
          avatar.textContent = iniciales(c.usuario ? c.usuario.name : "");
        }
        meta.appendChild(avatar);
      }

      const metaTexto = document.createElement("span");
      metaTexto.textContent = propio
        ? "Tú"
        : (c.usuario ? c.usuario.name : "Usuario") +
          " · " +
          etiquetaRol(c.usuario && c.usuario.rol ? c.usuario.rol.nombre_rol : "");
      meta.appendChild(metaTexto);
      fila.appendChild(meta);
    }

    const burbuja = document.createElement("div");
    burbuja.className = "chat-burbuja";
    // Texto del comentario como nodo aparte (preserva saltos de línea con CSS white-space: pre-wrap).
    const spanComentario = document.createElement("span");
    spanComentario.className = "chat-texto";
    spanComentario.textContent = c.comentario;
    // Hora sutil en la esquina inferior derecha de la burbuja.
    const spanHora = document.createElement("span");
    spanHora.className = "chat-hora";
    spanHora.textContent = textoHora(d);
    burbuja.append(spanComentario, spanHora);

    fila.appendChild(burbuja);
    mensajes.appendChild(fila);

    ultimoDia = dia;
    ultimoUsuarioId = c.usuario ? c.usuario.id : null;
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
      ultimoDia = null;
      ultimoUsuarioId = null;

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

  if (texto && form) {
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
  }

  // Suscripción en tiempo real: pinta los mensajes de los demás en cuanto llegan.
  let canal = null;
  const echo = obtenerEcho();
  if (echo) {
    canal = echo.private("incidencia." + idIncidencia);
    canal.listen(".ComentarioCreado", (c) => pintarMensaje(c, true));
  }

  // Cierra la suscripción del canal (lo llama la página al cambiar de incidencia).
  function detener() {
    if (canal && echo) echo.leave("incidencia." + idIncidencia);
  }

  recargar();
  return { recargar, detener };
}
