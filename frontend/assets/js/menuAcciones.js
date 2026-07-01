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
    '<ul class="dropdown-menu dropdown-menu-end"></ul>';

  // El label se pinta con textContent (nunca innerHTML) para que el helper sea seguro por construcción.
  const menu = dropdown.querySelector(".dropdown-menu");
  acciones.forEach(function (a) {
    const li = document.createElement("li");
    const enlace = document.createElement("a");
    enlace.href = "#";
    enlace.className = a.peligro ? "dropdown-item text-danger" : "dropdown-item";
    enlace.setAttribute("role", "button");
    if (a.icon) {
      const icono = document.createElement("i");
      icono.className = a.icon;
      enlace.appendChild(icono);
    }
    enlace.appendChild(document.createTextNode(a.label));
    enlace.addEventListener("click", function (e) {
      e.preventDefault();
      a.handler();
    });
    li.appendChild(enlace);
    menu.appendChild(li);
  });
  return dropdown;
}
