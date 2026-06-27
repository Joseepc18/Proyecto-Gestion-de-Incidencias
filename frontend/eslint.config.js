// eslint.config.js — Reglas de ESLint para el JS del frontend; solo corre en CI.

const js = require("@eslint/js");
const globals = require("globals");

module.exports = [
  // 1) Archivos que NO revisamos: vendors minificados y este propio config (Node).
  {
    ignores: [
      "assets/js/bootstrap.bundle.min.js",
      "assets/js/main.js",
      "assets/js/browser-image-compression.js",
      "assets/vendors/**",
      "eslint.config.js",
    ],
  },

  // 2) Reglas para NUESTRO JavaScript
  {
    files: ["**/*.js"],
    languageOptions: {
      ecmaVersion: "latest",
      // usamos <script> globales, no módulos import/export
      sourceType: "script",
      globals: {
        // document, window, localStorage, fetch, JSON, etc.
        ...globals.browser,
      },
    },
    rules: {
      ...js.configs.recommended.rules,
    },
  },
];
