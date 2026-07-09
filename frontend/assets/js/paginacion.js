// paginacion.js — Helper global para la paginación de tablas con diseño inteligente.

/* exported renderizarPaginacion */

// "…" solo cuando oculta más de una página; si oculta una sola, se muestra ese número
function construirPaginas(current, last) {
  if (last <= 7) {
    return Array.from({ length: last }, (_, i) => i + 1);
  }

  const claves = new Set([1, last, current, current - 1, current + 1]);
  if (current <= 3) {
    claves.add(2).add(3);
  }
  if (current >= last - 2) {
    claves.add(last - 1).add(last - 2);
  }

  const orden = [...claves].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b);

  const paginas = [];
  orden.forEach((p, i) => {
    if (i > 0) {
      const salto = p - orden[i - 1];
      if (salto === 2) {
        paginas.push(orden[i - 1] + 1);
      } else if (salto > 2) {
        paginas.push("...");
      }
    }
    paginas.push(p);
  });
  return paginas;
}

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

  const paginas = construirPaginas(current, last);

  const htmlNumeros = paginas
    .map((p) => {
      if (p === "...") {
        return `<li class="pag-ellipsis">…</li>`;
      }
      const activo = p === current ? " pag-activo" : "";
      return `<li><button class="pag-num${activo}" data-page="${p}">${p}</button></li>`;
    })
    .join("");

  const opcionesPerPage = [5, 10, 15, 20, 50]
    .map((val) => `<option value="${val}" ${perPage == val ? "selected" : ""}>${val}</option>`)
    .join("");

  contenedor.innerHTML = `
    <div class="d-flex flex-wrap justify-content-between align-items-center w-100 gap-3 mt-3">
      <div class="text-muted small">
        Mostrando ${respuesta.from || 0} al ${respuesta.to || 0} de ${total} registros
      </div>
      <div class="d-flex align-items-center gap-3">
        <select class="form-select form-select-sm w-auto select-per-page" aria-label="Registros por página">
          ${opcionesPerPage}
        </select>
        <nav class="pag-nav" aria-label="Navegación de páginas">
          <button class="pag-paso" data-page="${current - 1}" ${current === 1 ? "disabled" : ""}>
            <i class="bi bi-chevron-left" aria-hidden="true"></i> Anterior
          </button>
          <ul class="pag-numeros">${htmlNumeros}</ul>
          <button class="pag-paso" data-page="${current + 1}" ${current === last ? "disabled" : ""}>
            Siguiente <i class="bi bi-chevron-right" aria-hidden="true"></i>
          </button>
        </nav>
      </div>
    </div>
  `;

  contenedor.querySelectorAll("[data-page]").forEach((el) => {
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
