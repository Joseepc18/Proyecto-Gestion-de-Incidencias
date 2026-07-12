// mapa.js — Helper reutilizable de Leaflet: mapa 2D satelital con pines de incidencias y selector de ubicación.

/* global L, escaparHtml, normalizarTexto */
/* exported ciudadEnPunto */

// Foto aérea real (Esri World Imagery): satélite gratis, sin token ni cuenta.
const TILES_SATELITE =
  "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}";
// Mapa callejero (OpenStreetMap), por si se quiere ver calles y nombres en vez de la foto.
const TILES_CALLEJERO = "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png";

// Evita que el mapa nazca gris por medirse antes de tener su tamaño final
function observarTamanoMapa(map) {
  if (typeof ResizeObserver === "undefined") return;
  const ro = new ResizeObserver(function () {
    map.invalidateSize();
  });
  ro.observe(map.getContainer());
  map.on("unload", function () {
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
      const ciudadZ = normalizarTexto(feat.properties.ciudad);
      const provZ = normalizarTexto(feat.properties.provincia);
      const match = ciudades.find(function (c) {
        return (
          normalizarTexto(c.nombre_ciudad) === ciudadZ &&
          c.provincia &&
          normalizarTexto(c.provincia.nombre_provincia) === provZ
        );
      });
      if (match) return match;
    }
  }
  return ciudadMasCercana(ciudades, lat, lng);
}

// Botón para alternar entre satélite y callejero (Leaflet no lo trae nativo con este estilo).
function agregarControlEstilo(map, capaSatelite, capaCalle) {
  const control = L.control({ position: "topright" });
  control.onAdd = function () {
    const grupo = L.DomUtil.create("div", "leaflet-bar mapa-estilo-toggle");

    const btnSat = L.DomUtil.create("button", "active", grupo);
    btnSat.type = "button";
    btnSat.title = "Vista satélite";
    btnSat.setAttribute("aria-label", "Vista satélite");
    btnSat.innerHTML = '<i class="bi bi-globe-americas" aria-hidden="true"></i>';

    const btnCalle = L.DomUtil.create("button", "", grupo);
    btnCalle.type = "button";
    btnCalle.title = "Vista callejero";
    btnCalle.setAttribute("aria-label", "Vista callejero");
    btnCalle.innerHTML = '<i class="bi bi-map" aria-hidden="true"></i>';

    L.DomEvent.disableClickPropagation(grupo);

    btnSat.addEventListener("click", function () {
      if (map.hasLayer(capaSatelite)) return;
      map.addLayer(capaSatelite);
      map.removeLayer(capaCalle);
      btnSat.classList.add("active");
      btnCalle.classList.remove("active");
    });
    btnCalle.addEventListener("click", function () {
      if (map.hasLayer(capaCalle)) return;
      map.addLayer(capaCalle);
      map.removeLayer(capaSatelite);
      btnCalle.classList.add("active");
      btnSat.classList.remove("active");
    });

    return grupo;
  };
  control.addTo(map);
}

function crearMapaBase(idContenedor, opciones) {
  opciones = opciones || {};
  // Leaflet usa [lat, lng] (al revés que Mapbox).
  const centro = opciones.centro || [-2.2267, -80.9012];
  const zoom = opciones.zoom || 14;

  // zoomControl: false porque el de Leaflet nace arriba-izquierda, donde paneles como el feed de
  // "Mis incidencias" lo tapan; se agrega abajo, a la derecha, apilado bajo el botón satélite/callejero.
  const map = L.map(idContenedor, { center: centro, zoom: zoom, zoomControl: false });

  const satelite = L.tileLayer(TILES_SATELITE, {
    attribution: "Imágenes © Esri",
    maxZoom: 19,
  }).addTo(map);
  const callejero = L.tileLayer(TILES_CALLEJERO, {
    attribution: "© OpenStreetMap",
    maxZoom: 19,
  });

  agregarControlEstilo(map, satelite, callejero);
  L.control.zoom({ position: "topright" }).addTo(map);
  observarTamanoMapa(map);

  return map;
}

// Mapa de incidencias: pinta pines (coloreados por estado) y permite enfocarlos.
// eslint-disable-next-line no-unused-vars
function crearMapaIncidencias(idContenedor, opciones) {
  const map = crearMapaBase(idContenedor, opciones);
  let marcadores = {};
  // Id del pin resaltado (seleccionado desde el feed); se reaplica tras cada pintarPines().
  let idSeleccionado = null;

  function marcarSeleccionado(id) {
    idSeleccionado = id;
    Object.keys(marcadores).forEach(function (key) {
      const el = marcadores[key].getElement();
      if (!el) return;
      el.classList.toggle("mapa-pin-wrapper--seleccionado", key === String(id));
    });
  }

  function pintarPines(items, onSelect) {
    Object.values(marcadores).forEach(function (m) {
      map.removeLayer(m);
    });
    marcadores = {};
    const puntos = [];

    items.forEach(function (it) {
      if (it.lat == null || it.lng == null) return;

      const color = it.color || "#2563eb";
      const icono = L.divIcon({
        className: "mapa-pin-wrapper",
        html: '<span class="mapa-pin-incidencia" style="background:' + color + '"></span>',
        iconSize: [18, 18],
        iconAnchor: [9, 9],
        popupAnchor: [0, -10],
      });

      const marcador = L.marker([it.lat, it.lng], { icon: icono }).addTo(map);

      // Defensa en profundidad: el popup se renderiza con HTML, así que escapamos el título.
      if (it.titulo) marcador.bindPopup(escaparHtml(it.titulo));
      if (onSelect) {
        marcador.on("click", function () {
          onSelect(it.id);
        });
      }

      marcadores[it.id] = marcador;
      puntos.push([it.lat, it.lng]);
    });

    if (puntos.length === 1) {
      map.setView(puntos[0], 16);
    } else if (puntos.length > 1) {
      map.fitBounds(L.latLngBounds(puntos), { padding: [60, 60], maxZoom: 16 });
    }

    // Los pines se recrean enteros arriba; si había uno resaltado, se reaplica al nuevo elemento.
    if (idSeleccionado != null) marcarSeleccionado(idSeleccionado);
  }

  function enfocar(id) {
    const marcador = marcadores[id];
    if (!marcador) return;
    map.setView(marcador.getLatLng(), 16);
    marcador.openPopup();
    marcarSeleccionado(id);
  }

  return { map, pintarPines, enfocar };
}

// Mapa para elegir una ubicación: el clic (o arrastrar el pin) avisa las coords.
// eslint-disable-next-line no-unused-vars
function crearMapaPicker(idContenedor, onCambio, opciones) {
  const map = crearMapaBase(idContenedor, opciones);
  // Bounds en formato Leaflet: [[sur, oeste], [norte, este]].
  map.setMaxBounds(
    L.latLngBounds([
      [-5.5, -82.0],
      [1.8, -74.5],
    ]),
  );
  let marcador = null;

  // notificar=false para colocar el marcador sin disparar onCambio (carga inicial en edición,
  // que no debe recalcular ni sobrescribir la ciudad ya guardada).
  function poner(lat, lng, notificar) {
    if (notificar === undefined) notificar = true;
    if (marcador) {
      marcador.setLatLng([lat, lng]);
    } else {
      marcador = L.marker([lat, lng], { draggable: true }).addTo(map);
      marcador.on("dragend", function () {
        const p = marcador.getLatLng();
        if (onCambio) onCambio(p.lat, p.lng);
      });
    }
    if (notificar && onCambio) onCambio(lat, lng);
  }

  map.on("click", function (e) {
    poner(e.latlng.lat, e.latlng.lng);
  });

  function usarMiUbicacion() {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(function (pos) {
      map.setView([pos.coords.latitude, pos.coords.longitude], 16);
      poner(pos.coords.latitude, pos.coords.longitude);
    });
  }

  function setUbicacion(lat, lng) {
    map.setView([lat, lng], 16);
    poner(lat, lng, false);
  }

  return { map, usarMiUbicacion, setUbicacion };
}
