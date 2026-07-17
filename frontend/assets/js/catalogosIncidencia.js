// catalogosIncidencia.js — Carga de catálogos y cascadas (tipo→subtipo, provincia→ciudad)
// compartida por registrar-incidencia y la edición del detalle.

/* exported crearCatalogosIncidencia */
/* global apiFetch, agregarOpciones, itemsSubtiposDe, itemsCiudadesDe, poblarSelectCascada, ciudadEnPunto */

// ids: { tipo, subtipo, provincia, ciudad } con los id de cada <select>.
// Devuelve { estado, cargar, poblarSubtipos, poblarCiudades, autocompletarUbicacion }.
function crearCatalogosIncidencia(ids) {
  const el = (id) => document.getElementById(id);
  // tipos/ciudades ya cargados y los polígonos de cantón (para resolver la ciudad al marcar).
  const estado = { tipos: [], ciudades: [], cantonesGeo: null };

  // Rellena el select de subtipos según el tipo elegido.
  function poblarSubtipos(idTipo) {
    const tipo = estado.tipos.find((t) => t.id_tipo_incidencia === parseInt(idTipo));
    poblarSelectCascada(el(ids.subtipo), itemsSubtiposDe(tipo), "Primero selecciona un tipo");
  }

  // Rellena el select de ciudades según la provincia elegida.
  function poblarCiudades(idProvincia) {
    poblarSelectCascada(
      el(ids.ciudad),
      itemsCiudadesDe(estado.ciudades, idProvincia),
      "Primero selecciona una provincia",
    );
  }

  // Marca en el mapa → autocompleta provincia + ciudad por el cantón que contiene el punto.
  function autocompletarUbicacion(lat, lng) {
    const ciudad = ciudadEnPunto(estado.cantonesGeo, estado.ciudades, lat, lng);
    if (!ciudad) return;
    el(ids.provincia).value = ciudad.id_provincia;
    poblarCiudades(ciudad.id_provincia);
    el(ids.ciudad).value = ciudad.id_ciudad;
  }

  // Carga tipos, ciudades y provincias en sus selects y cablea las dos cascadas.
  async function cargar() {
    estado.tipos = await apiFetch("/catalogos/tipos-incidencia");
    agregarOpciones(el(ids.tipo), estado.tipos, "id_tipo_incidencia", "nombre_tipo_incidencia");

    el(ids.tipo).addEventListener("change", function () {
      poblarSubtipos(parseInt(this.value));
    });

    // Provincia/ciudad son opcionales: el panel de gestión solo reclasifica tipo/subtipo.
    if (!ids.provincia) return;

    estado.ciudades = await apiFetch("/catalogos/ciudades");
    const provincias = await apiFetch("/catalogos/provincias");
    agregarOpciones(el(ids.provincia), provincias, "id_provincia", "nombre_provincia");

    // Los polígonos de cantón se cargan en segundo plano: la ubicación se puede marcar sin ellos.
    fetch("../assets/geo/ecuador-cantones.geojson")
      .then((r) => r.json())
      .then((g) => {
        estado.cantonesGeo = g;
      })
      // Se ignora a propósito: si falla la carga de cantones, no se autocompleta provincia/ciudad y el usuario los elige a mano.
      .catch(function () {});

    el(ids.provincia).addEventListener("change", function () {
      poblarCiudades(parseInt(this.value));
    });
  }

  return { estado, cargar, poblarSubtipos, poblarCiudades, autocompletarUbicacion };
}
