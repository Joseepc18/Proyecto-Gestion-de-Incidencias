// mapa.js — Helper reutilizable de Mapbox GL JS: mapa 3D con edificios + pines de incidencias.

/* global mapboxgl, MAPBOX_TOKEN, escaparHtml */
/* exported ciudadMasCercana, ciudadEnPunto, observarTamanoMapa */

mapboxgl.accessToken = MAPBOX_TOKEN;

// Standard Satellite: foto aérea + edificios 3D extruidos (lo más "Google Earth" que da Mapbox).
const MAPBOX_ESTILO_3D = "mapbox://styles/mapbox/standard-satellite";
// Maqueta 3D plana sin foto aérea (edificios blancos), por si se quiere ver solo el callejero.
const MAPBOX_ESTILO_MAQUETA = "mapbox://styles/mapbox/standard";

// Evita que el mapa nazca gris por medirse antes de tener su tamaño final
function observarTamanoMapa(map) {
  if (typeof ResizeObserver === "undefined") return;
  const ro = new ResizeObserver(function () {
    map.resize();
  });
  ro.observe(map.getContainer());
  map.on("remove", function () {
    ro.disconnect();
  });
}

// Distancia haversine a la ciudad del catálogo más cercana; ignora ciudades sin coordenadas
function ciudadMasCercana(ciudades, lat, lng) {
  const radioTierra = 6371;
  const aRad = function (g) {
    return (g * Math.PI) / 180;
  };
  let cercana = null;
  let menorDist = Infinity;
  ciudades.forEach(function (c) {
    if (c.latitud == null || c.longitud == null) return;
    const dLat = aRad(c.latitud - lat);
    const dLng = aRad(c.longitud - lng);
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(aRad(lat)) * Math.cos(aRad(c.latitud)) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const dist = radioTierra * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    if (dist < menorDist) {
      menorDist = dist;
      cercana = c;
    }
  });
  return cercana;
}

// Normaliza un nombre para comparar (sin tildes, minúsculas, espacios colapsados).
function normalizarNombre(s) {
  return (s || "").normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase().replace(/\s+/g, " ").trim();
}

// Ray-casting con regla par/impar: los agujeros se descuentan solos al contar cruces
function puntoEnAnillos(lng, lat, anillos) {
  let dentro = false;
  anillos.forEach(function (anillo) {
    for (let i = 0, j = anillo.length - 1; i < anillo.length; j = i++) {
      const xi = anillo[i][0];
      const yi = anillo[i][1];
      const xj = anillo[j][0];
      const yj = anillo[j][1];
      const cruza = yi > lat !== yj > lat && lng < ((xj - xi) * (lat - yi)) / (yj - yi) + xi;
      if (cruza) dentro = !dentro;
    }
  });
  return dentro;
}

// ¿El punto cae dentro de una geometría GeoJSON (Polygon o MultiPolygon)?
function puntoEnGeometria(lng, lat, geometry) {
  if (geometry.type === "Polygon") return puntoEnAnillos(lng, lat, geometry.coordinates);
  return geometry.coordinates.some(function (poly) {
    return puntoEnAnillos(lng, lat, poly);
  });
}

// Primero por el polígono de cantón que la contiene; si no cae en ninguno, fallback a la más cercana
function ciudadEnPunto(cantonesGeo, ciudades, lat, lng) {
  if (cantonesGeo && cantonesGeo.features) {
    const feat = cantonesGeo.features.find(function (f) {
      return puntoEnGeometria(lng, lat, f.geometry);
    });
    if (feat) {
      const ciudadZ = normalizarNombre(feat.properties.ciudad);
      const provZ = normalizarNombre(feat.properties.provincia);
      const match = ciudades.find(function (c) {
        return (
          normalizarNombre(c.nombre_ciudad) === ciudadZ &&
          c.provincia &&
          normalizarNombre(c.provincia.nombre_provincia) === provZ
        );
      });
      if (match) return match;
    }
  }
  return ciudadMasCercana(ciudades, lat, lng);
}

// Mapbox no tiene control nativo de cambio de estilo, así que lo añadimos a mano
function agregarControlEstilo(map) {
  const grupo = document.createElement("div");
  grupo.className = "mapboxgl-ctrl mapboxgl-ctrl-group mapa-estilo-toggle";

  const btn3D = document.createElement("button");
  btn3D.type = "button";
  btn3D.title = "Vista satélite 3D (foto aérea + edificios)";
  btn3D.setAttribute("aria-label", "Vista satélite 3D");
  btn3D.classList.add("active");
  btn3D.innerHTML = '<i class="bi bi-globe-americas" aria-hidden="true"></i>';

  const btnMaqueta = document.createElement("button");
  btnMaqueta.type = "button";
  btnMaqueta.title = "Vista de mapa con edificios";
  btnMaqueta.setAttribute("aria-label", "Vista mapa");
  btnMaqueta.innerHTML = '<i class="bi bi-buildings" aria-hidden="true"></i>';

  let estiloActual = MAPBOX_ESTILO_3D;
  function cambiarA(estilo, btnActivo, btnOtro) {
    if (estiloActual === estilo) return;
    // diff:false fuerza recarga completa: el diff por defecto no alternaba entre estilos Standard (satélite/maqueta).
    map.setStyle(estilo, { diff: false });
    estiloActual = estilo;
    btnActivo.classList.add("active");
    btnOtro.classList.remove("active");
  }
  btn3D.addEventListener("click", function () {
    cambiarA(MAPBOX_ESTILO_3D, btn3D, btnMaqueta);
  });
  btnMaqueta.addEventListener("click", function () {
    cambiarA(MAPBOX_ESTILO_MAQUETA, btnMaqueta, btn3D);
  });

  grupo.append(btn3D, btnMaqueta);
  const slot = map.getContainer().querySelector(".mapboxgl-ctrl-top-right");
  if (slot) slot.appendChild(grupo);
}

function crearMapaBase(idContenedor, opciones) {
  opciones = opciones || {};
  // Mapbox usa [lng, lat], al revés que Leaflet.
  const centro = opciones.centro || [-80.9012, -2.2267];
  const zoom = opciones.zoom || 14;

  const map = new mapboxgl.Map({
    container: idContenedor,
    style: MAPBOX_ESTILO_3D,
    center: centro,
    zoom: zoom,
    // Tope a 18: en Ecuador la foto aérea no tiene más resolución nativa
    maxZoom: 18,
    pitch: 45,
    bearing: -17,
    antialias: true,
  });

  map.addControl(new mapboxgl.NavigationControl({ visualizePitch: true }), "top-right");
  agregarControlEstilo(map);
  observarTamanoMapa(map);

  return map;
}

// Mapa de incidencias: pinta pines (coloreados por estado) y permite enfocarlos.
// eslint-disable-next-line no-unused-vars
function crearMapaIncidencias(idContenedor, opciones) {
  const map = crearMapaBase(idContenedor, opciones);
  let marcadores = {};

  function pintarPines(items, onSelect) {
    Object.values(marcadores).forEach(function (m) {
      m.remove();
    });
    marcadores = {};
    const puntos = [];

    items.forEach(function (it) {
      if (it.lat == null || it.lng == null) return;

      const el = document.createElement("div");
      el.className = "mapa-pin-incidencia";
      el.style.background = it.color || "#2563eb";
      if (onSelect) el.style.cursor = "pointer";

      const marcador = new mapboxgl.Marker({ element: el }).setLngLat([it.lng, it.lat]).addTo(map);

      // Defensa en profundidad: el popup se renderiza con HTML, así que escapamos el título.
      if (it.titulo) {
        marcador.setPopup(
          new mapboxgl.Popup({ offset: 14, closeButton: false }).setHTML(escaparHtml(it.titulo)),
        );
      }
      if (onSelect) {
        el.addEventListener("click", function (e) {
          // El click en el pin de Mapbox abre el popup por defecto; lo dejamos pasar.
          e.stopPropagation();
          onSelect(it.id);
        });
      }

      marcadores[it.id] = marcador;
      puntos.push([it.lng, it.lat]);
    });

    if (puntos.length === 1) {
      map.flyTo({ center: puntos[0], zoom: 16 });
    } else if (puntos.length > 1) {
      const bounds = new mapboxgl.LngLatBounds();
      puntos.forEach(function (p) {
        bounds.extend(p);
      });
      map.fitBounds(bounds, { padding: 60, maxZoom: 16, duration: 800 });
    }
  }

  function enfocar(id) {
    const marcador = marcadores[id];
    if (!marcador) return;
    map.flyTo({ center: marcador.getLngLat(), zoom: 16 });
    const popup = marcador.getPopup();
    if (popup && !popup.isOpen()) marcador.togglePopup();
  }

  return { map, pintarPines, enfocar };
}

// Mapa para elegir una ubicación: el clic (o arrastrar el pin) avisa las coords.
// eslint-disable-next-line no-unused-vars
function crearMapaPicker(idContenedor, onCambio, opciones) {
  const map = crearMapaBase(idContenedor, opciones);
  // Bounds en formato Mapbox: [[oeste, sur], [este, norte]].
  map.setMaxBounds([
    [-82.0, -5.5],
    [-74.5, 1.8],
  ]);
  let marcador = null;

  function poner(lat, lng) {
    if (marcador) {
      marcador.setLngLat([lng, lat]);
    } else {
      marcador = new mapboxgl.Marker({ draggable: true, color: "#2563eb" })
        .setLngLat([lng, lat])
        .addTo(map);
      marcador.on("dragend", function () {
        const p = marcador.getLngLat();
        if (onCambio) onCambio(p.lat, p.lng);
      });
    }
    if (onCambio) onCambio(lat, lng);
  }

  map.on("click", function (e) {
    poner(e.lngLat.lat, e.lngLat.lng);
  });

  function usarMiUbicacion() {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(function (pos) {
      map.flyTo({ center: [pos.coords.longitude, pos.coords.latitude], zoom: 16 });
      poner(pos.coords.latitude, pos.coords.longitude);
    });
  }

  function setUbicacion(lat, lng) {
    map.flyTo({ center: [lng, lat], zoom: 16 });
    poner(lat, lng);
  }

  return { map, usarMiUbicacion, setUbicacion };
}
