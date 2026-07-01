// comboboxBuscable.js — Convierte un <select> oculto en un campo de texto con autocompletado.
// El <select> real sigue siendo la fuente de verdad (otras funciones lo pueblan/leen igual que
// antes); este helper solo dibuja una lista filtrable encima y sincroniza select.value + change.

/* exported crearComboboxBuscable */

// idSelect: <select> oculto ya existente. idWrapper: contenedor con .combobox-input + .combobox-lista.
// Devuelve { resetear } para limpiar el texto cuando algo externo repuebla el select (p. ej. tras asignar).
function crearComboboxBuscable(idSelect, idWrapper) {
  const select = document.getElementById(idSelect);
  const wrapper = document.getElementById(idWrapper);
  const input = wrapper.querySelector(".combobox-input");
  const lista = wrapper.querySelector(".combobox-lista");

  // Opciones reales del select (no la de placeholder, ni las ocultas/deshabilitadas por
  // sincronizarSelects — el técnico ya elegido en el otro combobox no debe aparecer aquí).
  function opcionesDisponibles(filtro) {
    const texto = (filtro || "").trim().toLowerCase();
    return Array.from(select.options).filter(
      (op) =>
        op.value && !op.hidden && !op.disabled && op.textContent.toLowerCase().includes(texto),
    );
  }

  function pintarLista(filtro) {
    const opciones = opcionesDisponibles(filtro);
    lista.innerHTML = "";
    if (!opciones.length) {
      const vacio = document.createElement("li");
      vacio.className = "combobox-vacio";
      vacio.textContent = "Sin resultados.";
      lista.appendChild(vacio);
    } else {
      opciones.forEach(function (op) {
        const li = document.createElement("li");
        li.className = "combobox-opcion";
        li.textContent = op.textContent;
        // mousedown (no click): corre antes del blur del input, para no cerrar la lista antes de elegir.
        li.addEventListener("mousedown", function (e) {
          e.preventDefault();
          seleccionar(op.value, op.textContent);
        });
        lista.appendChild(li);
      });
    }
    lista.classList.remove("d-none");
  }

  function seleccionar(valor, texto) {
    select.value = valor;
    input.value = texto;
    lista.classList.add("d-none");
    select.dispatchEvent(new Event("change", { bubbles: true }));
  }

  // Si lo escrito no coincide con una selección real, se descarta (no se puede "elegir" texto suelto).
  function limpiarSiInvalido() {
    const actual = select.options[select.selectedIndex];
    input.value = actual && actual.value ? actual.textContent : "";
  }

  input.addEventListener("focus", function () {
    pintarLista("");
  });
  input.addEventListener("input", function () {
    pintarLista(input.value);
  });
  input.addEventListener("blur", function () {
    // Retraso corto para que el mousedown de la opción alcance a dispararse antes de ocultar la lista.
    setTimeout(function () {
      lista.classList.add("d-none");
      limpiarSiInvalido();
    }, 150);
  });

  return {
    // Limpia el texto (usarlo tras repoblar el <select>, ej. después de refrescarSelectsTecnicos).
    resetear: function () {
      input.value = "";
      lista.classList.add("d-none");
    },
  };
}
