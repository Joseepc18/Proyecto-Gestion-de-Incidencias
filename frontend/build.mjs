// build.mjs — Build de producción del SPA estático (sin framework).
// Copia el árbol a dist/ y, por página: minifica + hashea style.css, agrupa los <script> propios
// consecutivos en un bundle clásico (preserva orden y scope global, sin refactor a módulos) y deja
// los vendors verbatim. tema-inicial.js se inyecta inline en el <head> (evita un request bloqueante).
// Resultado: caché real (hash) sin tener que purgar Cloudflare por .js/.css.

import { readFileSync, writeFileSync, rmSync, mkdirSync, cpSync, readdirSync } from "node:fs";
import { createHash } from "node:crypto";
import { dirname, join, relative, basename, normalize, sep } from "node:path";
import { fileURLToPath } from "node:url";
import * as esbuild from "esbuild";

const root = dirname(fileURLToPath(import.meta.url));
const dist = join(root, "dist");
const buildDirRel = "assets/build";
const buildDirAbs = join(dist, buildDirRel);

// Nombres/rutas que NO se copian a dist (fuentes de build, deps, configs).
const EXCLUDE = new Set([
  "dist",
  "node_modules",
  "build.mjs",
  "package.json",
  "package-lock.json",
  "eslint.config.js",
  ".prettierrc.json",
  ".prettierignore",
  ".gitignore",
]);

// Vendors de terceros: se quedan verbatim (ya minificados, con url() a fuentes/imágenes relativas).
const VENDOR_JS = new Set(["bootstrap.bundle.min.js", "browser-image-compression.js"]);
const esVendor = (absPath) => {
  const p = absPath.split(sep).join("/");
  return p.includes("/vendors/") || VENDOR_JS.has(basename(p));
};

// Ruta URL absoluta (desde la raíz web) de un archivo dentro de dist/frontend.
const urlDesdeRaiz = (absEnRoot) => "/" + relative(root, absEnRoot).split(sep).join("/");

function copiarArbol(src, dst) {
  mkdirSync(dst, { recursive: true });
  for (const e of readdirSync(src, { withFileTypes: true })) {
    if (EXCLUDE.has(e.name)) continue;
    const s = join(src, e.name);
    const d = join(dst, e.name);
    if (e.isDirectory()) copiarArbol(s, d);
    else cpSync(s, d);
  }
}

// Escribe un asset con hash de contenido y devuelve su URL absoluta (dedup por contenido).
const escritos = new Set();
function emitir(base, ext, contenido) {
  const hash = createHash("sha256").update(contenido).digest("hex").slice(0, 8);
  const archivo = `${base}.${hash}.${ext}`;
  const abs = join(buildDirAbs, archivo);
  if (!escritos.has(archivo)) {
    writeFileSync(abs, contenido);
    escritos.add(archivo);
  }
  return `/${buildDirRel}/${archivo}`;
}

// style.css minificado+hasheado una sola vez (compartido por todas las páginas).
let styleUrlCache = null;
async function styleUrl() {
  if (styleUrlCache) return styleUrlCache;
  const css = readFileSync(join(root, "assets/css/style.css"));
  const { code } = await esbuild.transform(css, { loader: "css", minify: true });
  styleUrlCache = emitir("style", "css", code);
  return styleUrlCache;
}

// Concatena en orden y minifica un grupo de scripts propios como UN script clásico (globals intactos).
async function bundle(base, idx, rutas) {
  const fuente = rutas.map((r) => readFileSync(r, "utf8")).join("\n;\n");
  const { code } = await esbuild.transform(fuente, { loader: "js", minify: true });
  return emitir(`${base}-${idx}`, "js", code);
}

function listarHtml(dir, acc = []) {
  for (const e of readdirSync(dir, { withFileTypes: true })) {
    const p = join(dir, e.name);
    if (e.isDirectory()) listarHtml(p, acc);
    else if (e.name.endsWith(".html")) acc.push(p);
  }
  return acc;
}

async function procesarHtml(archivo, temaInline) {
  let html = readFileSync(archivo, "utf8");
  const pageDir = dirname(relative(dist, archivo));
  const pageName = basename(archivo, ".html");

  // 1) tema-inicial.js → inline bloqueante en el <head> (debe correr antes del paint).
  html = html.replace(
    /<script[^>]*src="[^"]*tema-inicial\.js"[^>]*><\/script>/,
    `<script>${temaInline}</script>`,
  );

  // 2) recoger los <script src> restantes en orden de documento.
  const scriptRe = /<script\b[^>]*\bsrc="([^"]+)"[^>]*><\/script>/g;
  const items = [];
  let m;
  while ((m = scriptRe.exec(html))) items.push(m[1]);

  // 3) reconstruir: agrupar propios consecutivos en un bundle, vendors sueltos, MISMO orden.
  const tags = [];
  let grupo = [];
  let idx = 0;
  const flush = async () => {
    if (!grupo.length) return;
    tags.push(`<script defer src="${await bundle(pageName, idx++, grupo)}"></script>`);
    grupo = [];
  };
  for (const src of items) {
    const abs = normalize(join(root, pageDir, src));
    if (esVendor(abs)) {
      await flush();
      tags.push(`<script defer src="${urlDesdeRaiz(abs)}"></script>`);
    } else {
      grupo.push(abs);
    }
  }
  await flush();

  // 4) sustituir: el primer <script src> pasa a ser el bloque nuevo; los demás se eliminan.
  let primero = true;
  html = html.replace(scriptRe, () => {
    if (primero) {
      primero = false;
      return tags.join("\n    ");
    }
    return "";
  });

  // 5) style.css → versión hasheada; bootstrap/bootstrap-icons quedan verbatim.
  const sUrl = await styleUrl();
  html = html.replace(
    /<link\b[^>]*href="[^"]*\/style\.css"[^>]*>/,
    `<link rel="stylesheet" href="${sUrl}" />`,
  );

  writeFileSync(archivo, html);
}

async function main() {
  rmSync(dist, { recursive: true, force: true });
  copiarArbol(root, dist);
  mkdirSync(buildDirAbs, { recursive: true });

  const { code: temaInline } = await esbuild.transform(
    readFileSync(join(root, "assets/js/tema-inicial.js"), "utf8"),
    { loader: "js", minify: true },
  );

  const paginas = listarHtml(dist);
  for (const p of paginas) await procesarHtml(p, temaInline.trim());

  console.log(
    `Build listo: ${paginas.length} páginas, ${escritos.size} assets hasheados en dist/${buildDirRel}/`,
  );
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
