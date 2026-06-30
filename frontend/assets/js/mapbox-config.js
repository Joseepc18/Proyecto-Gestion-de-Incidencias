// mapbox-config.js — Tokens públicos de Mapbox por entorno.
// Son tokens públicos (pk.*) restringidos por dominio en la consola de Mapbox,
// así que es seguro tenerlos en el frontend. Si alguien los copia, no le sirven
// fuera de incidenciasupse.site (prod) ni de localhost (local).

/* exported MAPBOX_TOKEN */

const MAPBOX_TOKEN_PROD =
  "pk.eyJ1Ijoiam9zZWVwYzE4IiwiYSI6ImNtcjByeWY3cTBnZzYycm9kNHd4ZTY4dHoifQ.jnuPqL-1moDxPlRDyL0zng";
const MAPBOX_TOKEN_LOCAL =
  "pk.eyJ1Ijoiam9zZWVwYzE4IiwiYSI6ImNtcjBzM2htazBnamEycnB1aXp0MmN4anAifQ.d28fWW7j1tn5eU0iCFu0bQ";

const MAPBOX_TOKEN =
  location.hostname === "localhost" || location.hostname === "127.0.0.1"
    ? MAPBOX_TOKEN_LOCAL
    : MAPBOX_TOKEN_PROD;
