// Cruza dos datasets reales para generar el JSON canónico que consume UbicacionSeeder.
// Fuentes (crudas en esta misma carpeta para reproducibilidad):
//   - cantones_latlng.gist.js  → coords por cantón (name, latlng), SIN provincia.
//     https://gist.github.com/c4rlosviteri/74261f2f03f59b9ac4042bc22dbe922f
//   - provincias_raw.sql + cantones_raw.sql → jerarquía provincia→cantón.
//     https://github.com/vfabianfarias/Datos-Geograficos-Ecuador
// Cruce por nombre de cantón normalizado (sin tildes, minúsculas). NO se inventan coordenadas:
// el cantón que no cruza queda sin lat/lng y se registra en sin_coordenadas.
// Uso: node build_ubicaciones.mjs   (regenera ubicaciones_ec.json)

import { readFileSync, writeFileSync } from 'node:fs';

const normalizar = (s) =>
  s
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .replace(/\s+/g, ' ')
    .trim();

// Nombres canónicos de las 24 provincias (con tildes correctas, igual que el seeder previo).
// Indexados por el id de provincia de vfabian (provincias_raw.sql). El 25 (Zonas No Delimitadas) se omite.
const PROVINCIAS = {
  1: 'Azuay',
  2: 'Bolívar',
  3: 'Cañar',
  4: 'Carchi',
  5: 'Cotopaxi',
  6: 'Chimborazo',
  7: 'El Oro',
  8: 'Esmeraldas',
  9: 'Guayas',
  10: 'Imbabura',
  11: 'Loja',
  12: 'Los Ríos',
  13: 'Manabí',
  14: 'Morona Santiago',
  15: 'Napo',
  16: 'Pastaza',
  17: 'Pichincha',
  18: 'Tungurahua',
  19: 'Zamora Chinchipe',
  20: 'Galápagos',
  21: 'Sucumbíos',
  22: 'Orellana',
  23: 'Santo Domingo de los Tsáchilas',
  24: 'Santa Elena',
};

// 1) Coords por cantón desde el gist (es JS: objetos {name, type, latlng:[lat,lng]}).
const gist = readFileSync(new URL('./cantones_latlng.gist.js', import.meta.url), 'utf8');
const coordsPorNombre = new Map();
const reObj = /name:\s*"([^"]+)"[\s\S]*?latlng:\s*\[\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*\]/g;
let m;
while ((m = reObj.exec(gist)) !== null) {
  const clave = normalizar(m[1]);
  if (!coordsPorNombre.has(clave)) {
    coordsPorNombre.set(clave, { lat: Number(m[2]), lng: Number(m[3]) });
  }
}

// Alias para cantones cuyo nombre oficial (vfabian) difiere del nombre común del gist.
const ALIAS = {
  'San Jacinto de Yaguachi': 'Yaguachi',
  'Alfredo Baquerizo Moreno (Juján)': 'Alfredo Baquerizo Moreno',
  'Salitre (Urbina Jado)': 'Salitre',
  'Baños de Agua Santa': 'Baños',
  'San Pedro de Pelileo': 'Pelileo',
  'Santiago de Píllaro': 'Píllaro',
  'Yantzaza (Yanzatza)': 'Yantzaza',
};

// Busca coords probando: nombre exacto → alias → nombre desambiguado por provincia (el gist
// distingue cantones homónimos con sufijo "(Provincia)", p. ej. "Bolívar (Carchi)").
const buscarCoords = (nombre, provincia) =>
  coordsPorNombre.get(normalizar(nombre)) ??
  (ALIAS[nombre] ? coordsPorNombre.get(normalizar(ALIAS[nombre])) : undefined) ??
  coordsPorNombre.get(normalizar(`${nombre} (${provincia})`)) ??
  null;

// 2) Jerarquía: (id, 'Cantón', id_provincia) desde cantones_raw.sql.
const cantonesSql = readFileSync(new URL('./cantones_raw.sql', import.meta.url), 'utf8');
const porProvincia = new Map();
const sinCoordenadas = [];
const reFila = /\(\s*\d+\s*,\s*'((?:[^']|'')+)'\s*,\s*(\d+)\s*\)/g;
while ((m = reFila.exec(cantonesSql)) !== null) {
  const nombre = m[1].replace(/''/g, "'");
  const idProv = Number(m[2]);
  const provincia = PROVINCIAS[idProv];
  if (!provincia) continue;

  const coords = buscarCoords(nombre, provincia);
  if (!coords) sinCoordenadas.push({ provincia, ciudad: nombre });

  if (!porProvincia.has(provincia)) porProvincia.set(provincia, []);
  porProvincia.get(provincia).push({
    ciudad: nombre,
    latitud: coords ? coords.lat : null,
    longitud: coords ? coords.lng : null,
  });
}

const provincias = Object.values(PROVINCIAS).map((provincia) => ({
  provincia,
  cantones: porProvincia.get(provincia) ?? [],
}));

const totalCantones = provincias.reduce((n, p) => n + p.cantones.length, 0);
const salida = {
  pais: 'Ecuador',
  generado: new Date().toISOString().slice(0, 10),
  fuentes: {
    coordenadas: 'https://gist.github.com/c4rlosviteri/74261f2f03f59b9ac4042bc22dbe922f',
    jerarquia: 'https://github.com/vfabianfarias/Datos-Geograficos-Ecuador',
  },
  total_provincias: provincias.length,
  total_cantones: totalCantones,
  total_sin_coordenadas: sinCoordenadas.length,
  sin_coordenadas: sinCoordenadas,
  provincias,
};

writeFileSync(new URL('./ubicaciones_ec.json', import.meta.url), JSON.stringify(salida, null, 2) + '\n');
console.log(
  `provincias=${provincias.length} cantones=${totalCantones} sin_coords=${sinCoordenadas.length}`,
);
console.log('sin coords:', sinCoordenadas.map((c) => `${c.ciudad} (${c.provincia})`).join(', '));
