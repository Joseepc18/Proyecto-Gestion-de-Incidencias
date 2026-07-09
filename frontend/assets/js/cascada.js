// cascada.js — Helpers de cascada para selects de formulario (tipo→subtipo, provincia→ciudad).

/* exported poblarSelectCascada, itemsSubtiposDe, itemsCiudadesDe, agregarOpciones */

// Añade <option> desde una lista a un select (conserva el placeholder que ya trae el HTML).
function agregarOpciones(select, items, valueKey, textKey) {
  items.forEach(function (it) {
    const op = document.createElement("option");
    op.value = it[valueKey];
    op.textContent = it[textKey];
    select.appendChild(op);
  });
}

// Devuelve true si el select quedó habilitado con opciones
function poblarSelectCascada(selectHijo, items, opcionVacio) {
  selectHijo.innerHTML = "";
  if (!items || items.length === 0) {
    selectHijo.disabled = true;
    selectHijo.innerHTML = '<option value="">' + opcionVacio + "</option>";
    return false;
  }
  selectHijo.disabled = false;
  selectHijo.innerHTML = '<option value="">Seleccionar...</option>';
  items.forEach(function (it) {
    const op = document.createElement("option");
    op.value = it.value;
    op.textContent = it.text;
    selectHijo.appendChild(op);
  });
  return true;
}

// Proyecta los subtipos de un tipo a [{value, text}].
function itemsSubtiposDe(tipo) {
  if (!tipo || !tipo.subtipos) return [];
  return tipo.subtipos.map(function (s) {
    return { value: s.id_subtipo_incidencia, text: s.nombre_subtipo_incidencia };
  });
}

// Proyecta las ciudades de una provincia (lista plana con id_provincia) a [{value, text}].
function itemsCiudadesDe(ciudades, provinciaId) {
  return ciudades
    .filter(function (c) {
      return c.id_provincia === parseInt(provinciaId);
    })
    .map(function (c) {
      return { value: c.id_ciudad, text: c.nombre_ciudad };
    });
}
