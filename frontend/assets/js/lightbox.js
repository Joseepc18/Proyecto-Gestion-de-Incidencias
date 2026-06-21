// lightbox.js — Visor de imágenes a pantalla completa (para las evidencias).
// Cualquier elemento con [data-lightbox="url"] abre el visor al hacer clic.
// Se cierra con la X, clic en el fondo o tecla Esc.

/* exported abrirLightbox */

function abrirLightbox(url) {
  const overlay = document.createElement("div");
  overlay.className = "lightbox-overlay";

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

  // Cierra al hacer clic en el fondo o en la X (no al clic sobre la imagen).
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
