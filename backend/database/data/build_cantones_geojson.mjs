// Genera frontend/assets/geo/ecuador-cantones.geojson para el autocompletar por point-in-polygon.
// Cruza el GeoJSON de cantones (geoBoundaries ADM2) con ubicaciones_ec.json para incrustar en cada
// polígono NUESTROS nombres canónicos (ciudad + provincia), de modo que el frontend resuelva el
// id_ciudad sin volver a hacer matching de nombres. Adelgaza el archivo (redondea coords, quita
// propiedades sobrantes) para que pese poco en el navegador.
//
// Fuente polígonos: https://github.com/wmgeolab/geoBoundaries (gbOpen ECU ADM2, simplified).
// El raw (~3 MB) NO se versiona por tamaño; descárgalo a esta carpeta antes de correr:
//   curl -L -o cantones_adm2_raw.geojson \
//     https://github.com/wmgeolab/geoBoundaries/raw/main/releaseData/gbOpen/ECU/ADM2/geoBoundaries-ECU-ADM2_simplified.geojson
// Uso: node build_cantones_geojson.mjs

import { readFileSync, writeFileSync } from 'node:fs';

const norm = (s) =>
  s
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .replace(/\s+/g, ' ')
    .trim();

// Alias: nombre normalizado del GeoJSON → nombre normalizado en nuestra BD.
const ALIAS = {
  'alfredo baquerizo moreno': 'alfredo baquerizo moreno (jujan)',
  'crnel. marcelino mariduena': 'coronel marcelino mariduena',
  empalme: 'el empalme',
  'gnral. antonio elizalde': 'general antonio elizalde',
  salitre: 'salitre (urbina jado)',
  yantzaza: 'yantzaza (yanzatza)',
};

const geo = JSON.parse(readFileSync(new URL('./cantones_adm2_raw.geojson', import.meta.url), 'utf8'));
const ub = JSON.parse(readFileSync(new URL('./ubicaciones_ec.json', import.meta.url), 'utf8'));

// Index de nuestros cantones por nombre normalizado → [{ciudad, provincia, lat, lng}] (puede haber homónimos).
const porNombre = new Map();
for (const p of ub.provincias) {
  for (const c of p.cantones) {
    const clave = norm(c.ciudad);
    if (!porNombre.has(clave)) porNombre.set(clave, []);
    porNombre.get(clave).push({
      ciudad: c.ciudad,
      provincia: p.provincia,
      lat: c.latitud,
      lng: c.longitud,
    });
  }
}

// Centroide simple de una geometría (promedio de todos sus vértices). Sirve para desempatar homónimos.
function centroide(geometry) {
  let sx = 0;
  let sy = 0;
  let n = 0;
  const anillos = geometry.type === 'MultiPolygon' ? geometry.coordinates.flat() : geometry.coordinates;
  for (const anillo of anillos) {
    for (const [x, y] of anillo) {
      sx += x;
      sy += y;
      n++;
    }
  }
  return [sx / n, sy / n];
}

const haversine = (lat1, lng1, lat2, lng2) => {
  const R = 6371;
  const r = (g) => (g * Math.PI) / 180;
  const dLat = r(lat2 - lat1);
  const dLng = r(lng2 - lng1);
  const a =
    Math.sin(dLat / 2) ** 2 + Math.cos(r(lat1)) * Math.cos(r(lat2)) * Math.sin(dLng / 2) ** 2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
};

// Redondea a 4 decimales (~11 m) y elimina vértices consecutivos repetidos tras el redondeo.
function adelgazarAnillo(anillo) {
  const out = [];
  for (const [x, y] of anillo) {
    const px = Math.round(x * 1e4) / 1e4;
    const py = Math.round(y * 1e4) / 1e4;
    const prev = out[out.length - 1];
    if (!prev || prev[0] !== px || prev[1] !== py) out.push([px, py]);
  }
  return out;
}

function adelgazarGeometria(geometry) {
  if (geometry.type === 'Polygon') {
    return { type: 'Polygon', coordinates: geometry.coordinates.map(adelgazarAnillo) };
  }
  return {
    type: 'MultiPolygon',
    coordinates: geometry.coordinates.map((poly) => poly.map(adelgazarAnillo)),
  };
}

const features = [];
const sinMatch = [];
for (const f of geo.features) {
  const clave = norm(f.properties.shapeName);
  const opciones = porNombre.get(clave) || porNombre.get(ALIAS[clave]) || null;
  if (!opciones) {
    sinMatch.push(f.properties.shapeName);
    continue;
  }
  let elegido = opciones[0];
  if (opciones.length > 1) {
    // Homónimo: el cantón cuyo punto de referencia esté más cerca del centroide del polígono.
    const [cx, cy] = centroide(f.geometry);
    let mejor = Infinity;
    for (const o of opciones) {
      if (o.lat == null) continue;
      const d = haversine(cy, cx, o.lat, o.lng);
      if (d < mejor) {
        mejor = d;
        elegido = o;
      }
    }
  }
  features.push({
    type: 'Feature',
    properties: { ciudad: elegido.ciudad, provincia: elegido.provincia },
    geometry: adelgazarGeometria(f.geometry),
  });
}

const salida = { type: 'FeatureCollection', features };
const rutaSalida = new URL('../../../frontend/assets/geo/ecuador-cantones.geojson', import.meta.url);
writeFileSync(rutaSalida, JSON.stringify(salida) + '\n');

console.log(`features escritas: ${features.length} / ${geo.features.length}`);
console.log(`sin match (caen al fallback haversine): ${sinMatch.length} → ${sinMatch.join(', ')}`);
