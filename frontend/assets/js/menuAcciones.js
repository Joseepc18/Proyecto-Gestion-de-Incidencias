// menuAcciones.js — Menú desplegable de 3 puntos reutilizable (Editar / Eliminar / ...).

/* exported crearMenuAcciones */

// acciones: [{ icon, label, handler, peligro }] — peligro pinta el texto en rojo.
// icon es una clase Bootstrap Icons ya completa (p. ej. "bi bi-pencil me-2").
function crearMenuAcciones(acciones) {
  const dropdown = document.createElement("div");
  dropdown.className = "dropdown";
  dropdown.innerHTML =
    '<button class="btn btn-light btn-sm" data-bs-toggle="dropdown" aria-expanded="false">' +
    '<i class="bi bi-three-dots-vertical"></i></button>' +
    '<ul class="dropdown-menu dropdown-menu-end">' +
    acciones
      .map(function (a) {
        const clase = a.peligro ? "dropdown-item text-danger" : "dropdown-item";
        const icono = a.icon ? '<i class="' + a.icon + '"></i>' : "";
        return (
          '<li><a href="#" class="' + clase + '" role="button">' + icono + a.label + "</a></li>"
        );
      })
      .join("") +
    "</ul>";

  const items = dropdown.querySelectorAll(".dropdown-item");
  acciones.forEach(function (a, i) {
    items[i].addEventListener("click", function (e) {
      e.preventDefault();
      a.handler();
    });
  });
  return dropdown;
}
