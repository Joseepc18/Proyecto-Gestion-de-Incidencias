// misIncidencias.js — Vista maestro-detalle del usuario (lista + detalle embebido).

/* global apiFetch, obtenerToken, eliminarToken, aplicarMenuRol, mostrarToast, crearMapaIncidencias, crearChat, escaparHtml, estadoConfig, prioridadConfig */

let usuarioActual = null;
let incidenciaSeleccionada = null;
let mapa = null;

// Color del pin según el estado de la incidencia
const colorEstado = {
  PENDIENTE: "#dc2626",
  EN_PROCESO: "#d97706",
  RESUELTO: "#16a34a",
};

// Genera un código legible a partir del id (INC-0001).
function codigoIncidencia(id) {
  return "INC-" + String(id).padStart(4, "0");
}

document.addEventListener("DOMContentLoaded", async function () {
  // Guard de sesión
  if (!obtenerToken()) {
    window.location.href = "../login/login.html";
    return;
  }

  // Cargar usuario (nombre en navbar + mostrar Usuarios si es admin)
  try {
    usuarioActual = await apiFetch("/user");
    document.getElementById("nombreUsuario").textContent = usuarioActual.name;

    aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "");
  } catch {
    eliminarToken();
    window.location.href = "../login/login.html";
    return;
  }

  // Logout
  document.getElementById("btnLogout").addEventListener("click", async function (e) {
    e.preventDefault();
    try {
      await apiFetch("/logout", { method: "POST" });
    } catch {
      /* ignorar */
    }
    eliminarToken();
    window.location.href = "../login/login.html";
  });

  // Búsqueda con debounce 400ms
  let timerBusqueda = null;
  document.getElementById("filtroBusqueda").addEventListener("input", function () {
    clearTimeout(timerBusqueda);
    timerBusqueda = setTimeout(cargarLista, 400);
  });

  // Mapa de fondo
  mapa = crearMapaIncidencias("mapaMisIncidencias");
  setTimeout(function () {
    mapa.map.invalidateSize();
  }, 200);


  // Arranque
  cargarLista();
});

// Paso 2 — Trae las incidencias del usuario y pinta las tarjetas en #listaIncidencias.
async function cargarLista() {
  const contenedor = document.getElementById("listaIncidencias");
  const info = document.getElementById("listaInfo");

  const params = new URLSearchParams();
  const busqueda = document.getElementById("filtroBusqueda").value.trim();
  if (busqueda) params.set("busqueda", busqueda);

  try {
    const respuesta = await apiFetch("/incidencias?" + params.toString());
    const incidencias = respuesta.data;

    if (incidencias.length === 0) {
      contenedor.innerHTML =
        '<p class="text-muted small text-center py-4 mb-0">No se encontraron incidencias.</p>';
      info.textContent = "";
      if (mapa) mapa.pintarPines([], seleccionarIncidencia);
      return;
    }

    contenedor.innerHTML = incidencias
      .map(function (inc) {
        const est = estadoConfig[inc.estado_incidencia] || {
          clase: "",
          icono: "",
          texto: inc.estado_incidencia,
        };
        const ciudad = inc.ciudad ? inc.ciudad.nombre_ciudad : "Sin ubicación";
        const id = inc.id_incidencia;

        return (
          '<div class="incidencia-card" data-id="' +
          id +
          '" onclick="seleccionarIncidencia(' +
          id +
          ')">' +
          '<div class="d-flex justify-content-between align-items-start gap-2">' +
          '<span class="card-codigo">' +
          codigoIncidencia(id) +
          "</span>" +
          '<span class="badge ' +
          est.clase +
          '"><i class="bi ' +
          est.icono +
          ' me-1"></i>' +
          est.texto +
          "</span>" +
          "</div>" +
          '<p class="card-titulo">' +
          escaparHtml(inc.nombre_incidencia) +
          "</p>" +
          '<span class="card-ubicacion"><i class="bi bi-geo-alt me-1"></i>' +
          escaparHtml(ciudad) +
          "</span>" +
          "</div>"
        );
      })
      .join("");

    info.textContent =
      "Mostrando " + incidencias.length + " de " + respuesta.total + " incidencias";

    // Pintar los pines de las incidencias en el mapa
    if (mapa) {
      const pines = incidencias
        .filter(function (i) {
          return i.latitud_incidencia != null && i.longitud_incidencia != null;
        })
        .map(function (i) {
          return {
            id: i.id_incidencia,
            lat: Number(i.latitud_incidencia),
            lng: Number(i.longitud_incidencia),
            titulo: codigoIncidencia(i.id_incidencia) + " — " + escaparHtml(i.nombre_incidencia),
            color: colorEstado[i.estado_incidencia] || "#2563eb",
          };
        });
      mapa.pintarPines(pines, seleccionarIncidencia);
    }
  } catch (error) {
    contenedor.innerHTML =
      '<p class="text-danger small text-center py-4 mb-0">' + error.message + "</p>";
    info.textContent = "";
  }
}

// Paso 3 — Carga el resumen liviano de la incidencia en la tarjeta derecha.
async function seleccionarIncidencia(id) {
  // Marcar la tarjeta activa
  document.querySelectorAll(".incidencia-card").forEach(function (card) {
    card.classList.toggle("activa", Number(card.dataset.id) === id);
  });


  try {
    const inc = await apiFetch("/incidencias/" + id);
    incidenciaSeleccionada = id;

    // El aviso "ya está en proceso" aparece cuando no es editable.
    const esAdmin = usuarioActual.rol && usuarioActual.rol.nombre_rol === "admin";
    const editable = esAdmin || inc.estado_incidencia === "PENDIENTE";
    document.getElementById("avisoEdicion").classList.toggle("d-none", editable);

    // Cabecera
    document.getElementById("detalleCodigo").textContent = codigoIncidencia(inc.id_incidencia);
    document.getElementById("detalleTitulo").textContent = inc.nombre_incidencia;
    document.getElementById("detalleFecha").textContent =
      "Creada: " + new Date(inc.created_at).toLocaleString("es-EC");

    // Estado
    const est = estadoConfig[inc.estado_incidencia] || {
      clase: "",
      icono: "",
      texto: inc.estado_incidencia,
    };
    const spanEstado = document.getElementById("detalleEstado");
    spanEstado.className = "badge " + est.clase;
    spanEstado.innerHTML = '<i class="bi ' + est.icono + ' me-1"></i>' + est.texto;

    // Prioridad
    const pri = prioridadConfig[inc.prioridad_incidencia] || {
      clase: "",
      icono: "",
      texto: inc.prioridad_incidencia,
    };
    const spanPri = document.getElementById("detallePrioridad");
    spanPri.className = "badge " + pri.clase;
    spanPri.innerHTML = '<i class="bi ' + pri.icono + ' me-1"></i>' + pri.texto;

    // Ubicación
    const ciudad = inc.ciudad ? inc.ciudad.nombre_ciudad : "Sin ciudad";
    const direccion = inc.direccion_incidencia ? " — " + inc.direccion_incidencia : "";
    document.getElementById("detalleUbicacion").textContent = ciudad + direccion;

    // Imagen destacada (primera evidencia)
    const imgCompacta = document.getElementById("detalleImagen");
    if (inc.evidencias && inc.evidencias.length > 0) {
      imgCompacta.src = "/storage/" + inc.evidencias[0].url_evidencia;
      imgCompacta.classList.remove("d-none");
    } else {
      imgCompacta.removeAttribute("src");
      imgCompacta.classList.add("d-none");
    }

    // "Ver detalles" → página de detalle del usuario
    document.getElementById("btnVerDetalles").href = "../detalleMiIncidencia/detalle.html?id=" + id;

    // Mostrar el panel de detalle
    document.getElementById("detalleVacio").classList.add("d-none");
    document.getElementById("detalleContenido").classList.remove("d-none");

    // Enfocar el pin en el mapa
    if (mapa) mapa.enfocar(id);
  } catch (error) {
    mostrarToast("Error al cargar el detalle: " + error.message, "error");
  }
}
