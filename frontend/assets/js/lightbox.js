// lightbox.js — Visor de imágenes a pantalla completa; se abre con [data-lightbox="url"].

/* exported abrirLightbox */

function abrirLightbox(url) {
  const overlay = document.createElement("div");
  overlay.className = "lightbox-overlay";
  overlay.setAttribute("role", "dialog");
  overlay.setAttribute("aria-modal", "true");
  overlay.setAttribute("aria-label", "Imagen ampliada");

  const btnCerrar = document.createElement("button");
  btnCerrar.className = "lightbox-close";
  btnCerrar.setAttribute("aria-label", "Cerrar");
  btnCerrar.innerHTML = "&times;";

  const img = document.createElement("img");
  img.className = "lightbox-img";
  img.src = url;
  img.alt = "Evidencia ampliada";

  overlay.appendChild(btnCerrar);
  overlay.appendChild(img);
  document.body.appendChild(overlay);
  requestAnimationFrame(function () {
    overlay.classList.add("lightbox-visible");
  });
  // Foco al botón de cerrar (accesibilidad, igual que los modales).
  setTimeout(function () {
    btnCerrar.focus();
  }, 50);

  function cerrar() {
    overlay.classList.remove("lightbox-visible");
    document.removeEventListener("keydown", alPresionarTecla);
    setTimeout(function () {
      overlay.remove();
    }, 200);
  }

  function alPresionarTecla(e) {
    if (e.key === "Escape") {
      cerrar();
    }
  }

  overlay.addEventListener("click", function (e) {
    if (e.target === overlay || e.target === btnCerrar) {
      cerrar();
    }
  });
  document.addEventListener("keydown", alPresionarTecla);
}

// Delegación: un solo listener atiende cualquier imagen con [data-lightbox].
document.addEventListener("click", function (e) {
  const el = e.target.closest("[data-lightbox]");
  if (el) {
    e.preventDefault();
    abrirLightbox(el.getAttribute("data-lightbox"));
  }
});
