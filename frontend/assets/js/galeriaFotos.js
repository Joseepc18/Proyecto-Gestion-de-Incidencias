// galeriaFotos.js — Dropzone + preview de miniaturas + compresión + revocación de objectURL.

/* exported crearGaleriaFotos */

/* global imageCompression, OPCIONES_COMPRESION */

// Crea (o pinta) la galería para subir fotos. opts:
//   input     — <input type="file"> original (se le cablea change)
//   dropzone  — elemento que actúa de zona de arrastre (click no abre selector:
//               el "agregar más" y el input original siguen funcionando)
//   preview   — contenedor donde se pintan las miniaturas
//   error     — elemento <div> para mostrar mensajes (opcional)
//   cupo      — () => número de fotos que aún se pueden agregar (0 si lleno)
//   textoCupo — mensaje al superar el cupo (opcional)
//   btnSubir  — botón "Subir" (opcional; si se pasa, se cablea a onSubir)
//   onSubir   — async (archivos: File[]) llamado al pulsar btnSubir; debe llamar a limpiar() si tuvo éxito
// Devuelve { archivos: File[], limpiar, render } para que la página acceda (p. ej. el form submit).
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
    opts.dropzone.classList.toggle("d-none", archivos.length > 0 || cupo <= 0);

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

    if (archivos.length > 0 && archivos.length < cupo) {
      const agregar = document.createElement("button");
      agregar.type = "button";
      agregar.className = "foto-agregar";
      agregar.innerHTML = '<i class="bi bi-plus-lg" aria-hidden="true"></i>';
      agregar.addEventListener("click", function () {
        opts.input.click();
      });
      opts.preview.appendChild(agregar);
    }

    if (opts.btnSubir) opts.btnSubir.classList.toggle("d-none", archivos.length === 0);
  }

  opts.input.addEventListener("change", function () {
    procesar(this.files);
    this.value = "";
  });

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
