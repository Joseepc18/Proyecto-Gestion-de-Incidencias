// galeriaFotos.js — Dropzone + preview de miniaturas + compresión + revocación de objectURL.

/* exported crearGaleriaFotos, abrirSelectorFuenteFoto */

/* global imageCompression, OPCIONES_COMPRESION */

// Mini-menú "Tomar foto / Elegir de galería" (un solo botón que abre las dos opciones,
// igual en Android e iPhone). Sin inputCamara abre la galería directo. Reusa el estilo de confirmar.js.
function abrirSelectorFuenteFoto(inputCamara, inputGaleria) {
  if (!inputCamara) {
    inputGaleria.click();
    return;
  }

  const overlay = document.createElement("div");
  overlay.className = "confirm-overlay";
  overlay.innerHTML =
    '<div class="confirm-box">' +
    '<h3 class="confirm-titulo">Agregar foto</h3>' +
    '<div class="d-grid gap-2 mt-2">' +
    '<button class="btn btn-outline-primary" data-fuente="camara">' +
    '<i class="bi bi-camera me-1" aria-hidden="true"></i> Tomar foto</button>' +
    '<button class="btn btn-outline-primary" data-fuente="galeria">' +
    '<i class="bi bi-images me-1" aria-hidden="true"></i> Elegir de galería</button>' +
    '<button class="btn btn-link btn-sm text-muted" data-fuente="cancelar">Cancelar</button>' +
    "</div></div>";

  document.body.appendChild(overlay);
  requestAnimationFrame(function () {
    overlay.classList.add("confirm-visible");
  });

  let cerrado = false;
  function cerrar() {
    if (cerrado) return;
    cerrado = true;
    overlay.classList.remove("confirm-visible");
    setTimeout(function () {
      overlay.remove();
    }, 200);
    document.removeEventListener("keydown", alPulsarTecla);
  }

  function alPulsarTecla(e) {
    if (e.key === "Escape") cerrar();
  }
  document.addEventListener("keydown", alPulsarTecla);

  overlay.querySelectorAll("[data-fuente]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const fuente = btn.dataset.fuente;
      // El .click() del input debe salir dentro del gesto del usuario, antes de cerrar.
      if (fuente === "camara") inputCamara.click();
      else if (fuente === "galeria") inputGaleria.click();
      cerrar();
    });
  });

  let mousedownEnFondo = false;
  overlay.addEventListener("mousedown", function (e) {
    mousedownEnFondo = e.target === overlay;
  });
  overlay.addEventListener("click", function (e) {
    if (e.target === overlay && mousedownEnFondo) cerrar();
  });
}

// Sin opts.dropzone (modo compacto) la página aporta su propio botón de "agregar"
function crearGaleriaFotos(opts) {
  const archivos = [];

  async function procesar(lista) {
    if (opts.error) opts.error.classList.add("d-none");
    for (const file of Array.from(lista)) {
      if (archivos.length >= opts.cupo()) {
        if (opts.error) {
          opts.error.textContent = opts.textoCupo || "Máximo de fotos alcanzado.";
          opts.error.classList.remove("d-none");
        }
        break;
      }
      try {
        const comprimida = await imageCompression(file, OPCIONES_COMPRESION);
        archivos.push(
          new File([comprimida], file.name.replace(/\.\w+$/, ".jpg"), {
            type: "image/jpeg",
          }),
        );
      } catch {
        if (opts.error) {
          opts.error.textContent = 'No se pudo procesar "' + file.name + '".';
          opts.error.classList.remove("d-none");
        }
      }
    }
    render();
  }

  function render() {
    opts.preview.innerHTML = "";
    const cupo = opts.cupo();
    if (opts.dropzone) opts.dropzone.classList.toggle("d-none", archivos.length > 0 || cupo <= 0);

    archivos.forEach(function (file, idx) {
      const cont = document.createElement("div");
      cont.className = "position-relative";

      const img = document.createElement("img");
      img.loading = "lazy";
      img.className = "foto-preview-chica";
      const url = URL.createObjectURL(file);
      img.onload = function () {
        URL.revokeObjectURL(url);
      };
      img.src = url;

      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "btn btn-danger btn-sm position-absolute top-0 end-0 py-0 px-1";
      btn.innerHTML = "&times;";
      btn.addEventListener("click", function () {
        archivos.splice(idx, 1);
        render();
      });

      cont.appendChild(img);
      cont.appendChild(btn);
      opts.preview.appendChild(cont);
    });

    // El azulejo "+" solo cuando hay dropzone; en modo compacto la página pone el suyo.
    if (opts.dropzone && archivos.length > 0 && archivos.length < cupo) {
      const agregar = document.createElement("button");
      agregar.type = "button";
      agregar.className = "foto-agregar";
      agregar.innerHTML = '<i class="bi bi-plus-lg" aria-hidden="true"></i>';
      agregar.addEventListener("click", abrirFuente);
      opts.preview.appendChild(agregar);
    }

    if (opts.btnSubir) opts.btnSubir.classList.toggle("d-none", archivos.length === 0);
  }

  // Un solo disparador: abre el mini-menú Cámara/Galería (o la galería directa si no hay inputCamara).
  function abrirFuente() {
    abrirSelectorFuenteFoto(opts.inputCamara, opts.input);
  }

  opts.input.addEventListener("change", function () {
    procesar(this.files);
    this.value = "";
  });

  // Input aparte con capture="environment": en Android el selector normal solo abre la galería.
  if (opts.inputCamara) {
    opts.inputCamara.addEventListener("change", function () {
      procesar(this.files);
      this.value = "";
    });
  }

  if (opts.dropzone) {
    // El clic en la zona abre el menú (antes el <label for> abría la galería directo).
    opts.dropzone.addEventListener("click", abrirFuente);
    ["dragenter", "dragover"].forEach(function (ev) {
      opts.dropzone.addEventListener(ev, function (e) {
        e.preventDefault();
        opts.dropzone.classList.add("dropzone-fotos--activo");
      });
    });
    ["dragleave", "dragend"].forEach(function (ev) {
      opts.dropzone.addEventListener(ev, function () {
        opts.dropzone.classList.remove("dropzone-fotos--activo");
      });
    });
    opts.dropzone.addEventListener("drop", function (e) {
      e.preventDefault();
      opts.dropzone.classList.remove("dropzone-fotos--activo");
      procesar(e.dataTransfer.files);
    });
  }

  if (opts.btnSubir) {
    opts.btnSubir.addEventListener("click", async function () {
      if (archivos.length === 0) return;
      await opts.onSubir(archivos);
    });
  }

  render();
  return {
    archivos: archivos,
    limpiar: function () {
      archivos.length = 0;
      render();
    },
    render: render,
  };
}
