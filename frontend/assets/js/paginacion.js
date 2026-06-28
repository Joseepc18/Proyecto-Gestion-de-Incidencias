// paginacion.js — Helper global para la paginación de tablas con diseño inteligente.

/**
 * Renderiza los controles de paginación en un contenedor.
 * @param {Object} options
 * @param {Object} options.respuesta - Objeto de respuesta de Laravel (con current_page, last_page, total, etc.)
 * @param {string} options.idContenedor - ID del contenedor donde se inyectará la paginación.
 * @param {Function} options.onPageChange - Callback que recibe la nueva página a cargar.
 * @param {Function} options.onPerPageChange - Callback que recibe la nueva cantidad de registros por página.
 * @param {number} options.perPage - Cantidad actual de ítems por página.
 */
/* exported renderizarPaginacion */

function renderizarPaginacion({
  respuesta,
  idContenedor,
  onPageChange,
  onPerPageChange,
  perPage = 10,
}) {
  const contenedor = document.getElementById(idContenedor);
  if (!contenedor) return;

  const current = respuesta.current_page || 1;
  const last = respuesta.last_page || 1;
  const total = respuesta.total || 0;

  if (total === 0) {
    contenedor.innerHTML = "";
    return;
  }

  // Generar botones numéricos con elipsis
  const paginas = [];
  if (last <= 7) {
    for (let i = 1; i <= last; i++) paginas.push(i);
  } else {
    paginas.push(1);
    if (current > 3) paginas.push("...");

    let start = Math.max(2, current - 1);
    let end = Math.min(last - 1, current + 1);

    if (current === 1) end = 3;
    if (current === last) start = last - 2;

    for (let i = start; i <= end; i++) paginas.push(i);

    if (current < last - 2) paginas.push("...");
    paginas.push(last);
  }

  let htmlBotones = paginas
    .map((p) => {
      if (p === "...") {
        return `<li class="page-item disabled"><span class="page-link">...</span></li>`;
      }
      const active = p === current ? "active" : "";
      return `<li class="page-item ${active}"><a class="page-link cursor-pointer" data-page="${p}">${p}</a></li>`;
    })
    .join("");

  const btnAnterior = `<li class="page-item ${current === 1 ? "disabled" : ""}">
    <a class="page-link cursor-pointer" aria-label="Anterior" data-page="${current - 1}">&laquo;</a>
  </li>`;

  const btnSiguiente = `<li class="page-item ${current === last ? "disabled" : ""}">
    <a class="page-link cursor-pointer" aria-label="Siguiente" data-page="${current + 1}">&raquo;</a>
  </li>`;

  const opcionesPerPage = [10, 25, 50, 100]
    .map(
      (val) => `<option value="${val}" ${perPage == val ? "selected" : ""}>${val} / pág</option>`,
    )
    .join("");

  contenedor.innerHTML = `
    <div class="d-flex flex-wrap justify-content-between align-items-center w-100 gap-3 mt-3">
      <div class="text-muted small">
        Mostrando ${respuesta.from || 0} al ${respuesta.to || 0} de ${total} registros
      </div>
      <div class="d-flex align-items-center gap-3">
        <select class="form-select form-select-sm w-auto select-per-page">
          ${opcionesPerPage}
        </select>
        <nav aria-label="Navegación de páginas">
          <ul class="pagination pagination-sm mb-0">
            ${btnAnterior}
            ${htmlBotones}
            ${btnSiguiente}
          </ul>
        </nav>
      </div>
    </div>
  `;

  // Attach events
  contenedor.querySelectorAll(".page-link[data-page]").forEach((el) => {
    el.addEventListener("click", (e) => {
      e.preventDefault();
      const p = parseInt(el.getAttribute("data-page"));
      if (p !== current && p >= 1 && p <= last) {
        onPageChange(p);
      }
    });
  });

  const select = contenedor.querySelector(".select-per-page");
  if (select) {
    select.addEventListener("change", (e) => {
      onPerPageChange(parseInt(e.target.value));
    });
  }
}
