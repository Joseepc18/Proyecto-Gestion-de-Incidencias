// mapa.js — Helper reutilizable de Leaflet: mapa con satélite/calles y pines de incidencias.

// Crea el mapa con capa satelital (Esri) + calles (OSM) y control para alternar.
/* global L */

function crearMapaBase(idContenedor, opciones) {
  opciones = opciones || {};
  const centro = opciones.centro || [-2.2267, -80.9012];
  const zoom = opciones.zoom || 13;

  const map = L.map(idContenedor, { zoomControl: false }).setView(centro, zoom);
  L.control.zoom({ position: "topright" }).addTo(map);

  const satelite = L.tileLayer(
    "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
    { attribution: "Imágenes © Esri", maxZoom: 19, maxNativeZoom: 18 },
  );
  const calles = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap",
    maxZoom: 19,
  });

  (opciones.capaInicial === "calles" ? calles : satelite).addTo(map);
  L.control
    .layers({ Satélite: satelite, Calles: calles }, null, { position: "topright" })
    .addTo(map);

  return map;
}

// Mapa de incidencias: pinta pines (coloreados por estado) y permite enfocarlos.
// eslint-disable-next-line no-unused-vars
function crearMapaIncidencias(idContenedor, opciones) {
  const map = crearMapaBase(idContenedor, opciones);
  const capaPines = L.layerGroup().addTo(map);
  let marcadores = {};

  function pintarPines(items, onSelect) {
    capaPines.clearLayers();
    marcadores = {};
    const puntos = [];

    items.forEach(function (it) {
      if (it.lat == null || it.lng == null) return;
      const marcador = L.circleMarker([it.lat, it.lng], {
        radius: 9,
        color: "#ffffff",
        weight: 2,
        fillColor: it.color || "#2563eb",
        fillOpacity: 1,
      }).addTo(capaPines);

      if (it.titulo) marcador.bindPopup(it.titulo);
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
      map.fitBounds(puntos, { padding: [40, 40], maxZoom: 16 });
    }
  }

  function enfocar(id) {
    const marcador = marcadores[id];
    if (!marcador) return;
    map.setView(marcador.getLatLng(), 16, { animate: true });
    marcador.openPopup();
  }

  return { map, pintarPines, enfocar };
}

// Mapa para elegir una ubicación: el clic (o arrastrar el pin) avisa las coords.
// eslint-disable-next-line no-unused-vars
function crearMapaPicker(idContenedor, onCambio, opciones) {
  const map = crearMapaBase(idContenedor, opciones);
  map.setMaxBounds([
    [-5.5, -82.0],
    [1.8, -74.5],
  ]);
  let marcador = null;

  function poner(lat, lng) {
    if (marcador) {
      marcador.setLatLng([lat, lng]);
    } else {
      marcador = L.marker([lat, lng], { draggable: true }).addTo(map);
      marcador.on("dragend", function () {
        const p = marcador.getLatLng();
        if (onCambio) onCambio(p.lat, p.lng);
      });
    }
    if (onCambio) onCambio(lat, lng);
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
    poner(lat, lng);
  }

  return { map, usarMiUbicacion, setUbicacion };
}
