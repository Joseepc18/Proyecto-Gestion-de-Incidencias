// eslint.config.js — Reglas de revisión del JavaScript del frontend.
// Solo corre en CI (GitHub Actions), nunca en el navegador.

const js = require("@eslint/js");
const globals = require("globals");

module.exports = [
  // 1) Archivos que NO revisamos: JS de terceros (plantilla / minificados)
  //    y este mismo archivo de config (es de Node, no del navegador).
  {
    ignores: ["assets/js/bootstrap.bundle.min.js", "assets/js/main.js", "eslint.config.js"],
  },

  // 2) Reglas para NUESTRO JavaScript
  {
    files: ["**/*.js"],
    languageOptions: {
      ecmaVersion: "latest",
      sourceType: "script", // usamos <script> globales, no módulos import/export
      globals: {
        ...globals.browser, // document, window, localStorage, fetch, JSON, etc.
      },
    },
    rules: {
      ...js.configs.recommended.rules,
    },
  },
];
