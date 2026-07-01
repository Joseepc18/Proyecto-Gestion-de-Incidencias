// eslint.config.js — Reglas de ESLint para el JS del frontend; solo corre en CI.

const js = require("@eslint/js");
const globals = require("globals");

module.exports = [
  {
    ignores: [
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
];
