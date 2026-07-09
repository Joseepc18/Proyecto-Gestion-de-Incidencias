// eslint.config.js — Reglas de ESLint para el JS del frontend; solo corre en CI.

const js = require("@eslint/js");
const globals = require("globals");

module.exports = [
  {
    ignores: [
      "dist/**",
      "assets/js/bootstrap.bundle.min.js",
      "assets/js/browser-image-compression.js",
      "assets/vendors/**",
      "eslint.config.js",
    ],
  },

  {
    files: ["**/*.js"],
    languageOptions: {
      ecmaVersion: "latest",
      sourceType: "script",
      globals: {
        ...globals.browser,
      },
    },
    rules: {
      ...js.configs.recommended.rules,
    },
  },

  // build.mjs corre en Node como módulo ESM (script de build, no navegador).
  {
    files: ["build.mjs"],
    languageOptions: {
      ecmaVersion: "latest",
      sourceType: "module",
      globals: {
        ...globals.node,
      },
    },
    rules: {
      ...js.configs.recommended.rules,
    },
  },
];
