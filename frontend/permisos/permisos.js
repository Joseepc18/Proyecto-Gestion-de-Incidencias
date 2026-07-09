// permisos.js — Matriz roles × permisos (solo super_admin): checkboxes agrupados y guardado por diff.

/* global apiFetch, aplicarMenuRol, tienePermiso, mostrarToast, escaparHtml, requerirSesion, cablearLogout, etiquetaRol */

// Estado inicial (rolId -> Set de id_permiso) para calcular el diff al guardar.
let inicial = {};
let rolesData = [];

document.addEventListener("DOMContentLoaded", async function () {
  const usuarioActual = await requerirSesion();
  if (!usuarioActual) return;

  aplicarMenuRol(usuarioActual.rol ? usuarioActual.rol.nombre_rol : "", usuarioActual.permisos);

  if (!tienePermiso("permisos.administrar")) {
    window.location.href = "../inicio/inicio.html";
    return;
  }

  cablearLogout();

  document.getElementById("btnGuardar").addEventListener("click", guardar);

  await cargarMatriz();
});

// Nombre bonito de rol/grupo: primera letra en mayúscula y guiones bajos como espacios.
function titulo(texto) {
  const limpio = String(texto).replace(/_/g, " ");
  return limpio.charAt(0).toUpperCase() + limpio.slice(1);
}

async function cargarMatriz() {
  let datos;
  try {
    datos = await apiFetch("/permisos");
  } catch {
    mostrarToast("No se pudieron cargar los permisos", "error");
    return;
  }

  rolesData = datos.roles;

  // Guardamos el set inicial de cada rol para comparar al guardar.
  inicial = {};
  rolesData.forEach(function (rol) {
    inicial[rol.id_rol] = new Set(rol.permisos);
  });

  pintarCabecera(rolesData);
  pintarFilas(datos.permisos, rolesData);
  actualizarBotonGuardar();
}

// Encabezado: una columna por rol.
function pintarCabecera(roles) {
  const celdasRol = roles
    .map(function (rol) {
      return '<th class="text-center">' + escaparHtml(etiquetaRol(rol.nombre_rol)) + "</th>";
    })
    .join("");
  document.getElementById("theadPermisos").innerHTML = "<tr><th>Permiso</th>" + celdasRol + "</tr>";
}

// Filas agrupadas por prefijo de la clave (usuarios, catalogos, incidencias, permisos).
function pintarFilas(permisos, roles) {
  const grupos = {};
  permisos.forEach(function (p) {
    const prefijo = p.clave_permiso.split(".")[0];
    (grupos[prefijo] = grupos[prefijo] || []).push(p);
  });

  const colSpan = roles.length + 1;
  let html = "";

  Object.keys(grupos).forEach(function (prefijo) {
    html +=
      '<tr class="table-active"><td colspan="' +
      colSpan +
      '"><strong>' +
      escaparHtml(titulo(prefijo)) +
      "</strong></td></tr>";

    grupos[prefijo].forEach(function (p) {
      html += "<tr><td>" + escaparHtml(p.descripcion_permiso || p.clave_permiso) + "</td>";
      html += roles.map((rol) => celdaCheck(rol, p)).join("");
      html += "</tr>";
    });
  });

  const tbody = document.getElementById("tbodyPermisos");
  tbody.innerHTML = html;

  // El diff se recalcula al marcar/desmarcar cualquier checkbox.
  tbody.querySelectorAll("input[type=checkbox]").forEach(function (chk) {
    chk.addEventListener("change", actualizarBotonGuardar);
  });
}

// Una celda con el checkbox del par (rol, permiso).
function celdaCheck(rol, permiso) {
  const marcado = inicial[rol.id_rol].has(permiso.id_permiso) ? " checked" : "";
  return (
    '<td class="text-center"><input class="form-check-input" type="checkbox" data-rol="' +
    rol.id_rol +
    '" data-permiso="' +
    permiso.id_permiso +
    '"' +
    marcado +
    ' aria-label="' +
    escaparHtml(
      etiquetaRol(rol.nombre_rol) + ": " + (permiso.descripcion_permiso || permiso.clave_permiso),
    ) +
    '" /></td>'
  );
}

// Set actual de permisos marcados para un rol (leído del DOM).
function setActual(rolId) {
  const marcados = document.querySelectorAll(
    'input[type=checkbox][data-rol="' + rolId + '"]:checked',
  );
  return new Set(Array.from(marcados, (chk) => Number(chk.dataset.permiso)));
}

// ¿Cambió el set de un rol respecto al inicial? (mismo tamaño y mismos elementos).
function cambio(rolId) {
  const actual = setActual(rolId);
  const base = inicial[rolId];
  if (actual.size !== base.size) return true;
  for (const id of actual) {
    if (!base.has(id)) return true;
  }
  return false;
}

// Habilita "Guardar" solo si algún rol editable tiene cambios pendientes.
function actualizarBotonGuardar() {
  const hayCambios = rolesData.some((rol) => rol.editable && cambio(rol.id_rol));
  document.getElementById("btnGuardar").disabled = !hayCambios;
}

async function guardar() {
  const boton = document.getElementById("btnGuardar");
  boton.disabled = true;

  // Solo se envían los roles editables que realmente cambiaron.
  const pendientes = rolesData.filter((rol) => rol.editable && cambio(rol.id_rol));

  try {
    for (const rol of pendientes) {
      const permisos = Array.from(setActual(rol.id_rol));
      await apiFetch("/roles/" + rol.id_rol + "/permisos", {
        method: "PUT",
        body: JSON.stringify({ permisos }),
      });
      inicial[rol.id_rol] = new Set(permisos);
    }
    mostrarToast("Permisos actualizados", "success");
  } catch (error) {
    mostrarToast(error.message || "No se pudieron guardar los permisos", "error");
  }

  actualizarBotonGuardar();
}
