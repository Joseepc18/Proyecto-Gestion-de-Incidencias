// carruselFotos.js — Carrusel de fotos de solo lectura: una foto visible, flechas + puntos indicadores.

/* exported montarCarrusel */
/* global escaparHtml */

// Duración de la animación de deslizamiento (debe coincidir con la transición del CSS).
const CARRUSEL_DURACION_MS = 200;

// La imagen central lleva data-lightbox para reusar el visor de pantalla completa (lightbox.js)
function montarCarrusel(contenedor, evidencias, mensajeVacio) {
  // Distinto del grid de miniaturas del modo edición en este mismo contenedor (#fotosReporte)
  contenedor.className = "carrusel-fotos-wrap";
  contenedor.innerHTML = "";

  if (!evidencias.length) {
    contenedor.innerHTML = '<p class="text-muted small mb-0">' + escaparHtml(mensajeVacio) + "</p>";
    return;
  }

  let indice = 0;
  let animando = false;

  const wrap = document.createElement("div");
  wrap.className = "carrusel-fotos";

  const img = document.createElement("img");
  img.className = "carrusel-fotos-img";
  img.loading = "lazy";
  img.alt = "Evidencia";

  const btnPrev = document.createElement("button");
  btnPrev.type = "button";
  btnPrev.className = "carrusel-fotos-flecha carrusel-fotos-flecha--prev";
  btnPrev.setAttribute("aria-label", "Foto anterior");
  btnPrev.innerHTML = '<i class="bi bi-chevron-left" aria-hidden="true"></i>';

  const btnNext = document.createElement("button");
  btnNext.type = "button";
  btnNext.className = "carrusel-fotos-flecha carrusel-fotos-flecha--next";
  btnNext.setAttribute("aria-label", "Foto siguiente");
  btnNext.innerHTML = '<i class="bi bi-chevron-right" aria-hidden="true"></i>';

  const dots = document.createElement("div");
  dots.className = "carrusel-fotos-dots";
  const botonesDots = evidencias.map(function (_, i) {
    const dot = document.createElement("button");
    dot.type = "button";
    dot.className = "carrusel-fotos-dot";
    dot.setAttribute("aria-label", "Ir a la foto " + (i + 1));
    dot.addEventListener("click", function () {
      navegar(i, i > indice ? 1 : -1);
    });
    dots.appendChild(dot);
    return dot;
  });

  // Pinta la foto actual (sin animación): se usa al montar y a mitad de la animación de navegar().
  function pintar() {
    const url = "/storage/" + evidencias[indice].url_evidencia;
    img.src = url;
    img.alt = "Evidencia " + (indice + 1) + " de " + evidencias.length;
    img.dataset.lightbox = url;

    botonesDots.forEach(function (dot, i) {
      dot.classList.toggle("activo", i === indice);
    });

    const unaSola = evidencias.length <= 1;
    btnPrev.classList.toggle("d-none", unaSola);
    btnNext.classList.toggle("d-none", unaSola);
    dots.classList.toggle("d-none", unaSola);
  }

  // direccion: 1 = hacia la izquierda/siguiente, -1 = hacia la derecha/anterior
  function navegar(nuevoIndice, direccion) {
    if (animando || nuevoIndice === indice) return;
    animando = true;

    img.classList.add("carrusel-fotos-img--saliendo");
    img.style.transform = "translateX(" + direccion * -18 + "px)";
    img.style.opacity = "0";

    setTimeout(function () {
      indice = nuevoIndice;
      pintar();

      // Reposiciona del lado opuesto SIN transición, luego la retira animando: efecto de entrada.
      img.classList.remove("carrusel-fotos-img--saliendo");
      img.classList.add("carrusel-fotos-img--sin-transicion");
      img.style.transform = "translateX(" + direccion * 18 + "px)";
      // Fuerza reflow para que el navegador aplique la posición de entrada antes de animar de vuelta.
      void img.offsetWidth;
      img.classList.remove("carrusel-fotos-img--sin-transicion");
      img.style.transform = "translateX(0)";
      img.style.opacity = "1";

      setTimeout(function () {
        animando = false;
      }, CARRUSEL_DURACION_MS);
    }, CARRUSEL_DURACION_MS);
  }

  btnPrev.addEventListener("click", function () {
    navegar((indice - 1 + evidencias.length) % evidencias.length, -1);
  });
  btnNext.addEventListener("click", function () {
    navegar((indice + 1) % evidencias.length, 1);
  });

  wrap.append(btnPrev, img, btnNext);
  contenedor.append(wrap, dots);
  pintar();
}
