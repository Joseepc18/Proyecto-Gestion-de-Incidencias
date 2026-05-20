# Guía de Aprendizaje — Proyecto Incidencias UPSE
> Escrito para alguien que sabe HTML básico y está aprendiendo backend por primera vez.
> Se actualiza a medida que avanza el proyecto.

---

## ÍNDICE

### BLOQUE 1 — Panorama general
- [El gran panorama](#el-gran-panorama)
- [Vocabulario técnico en simple](#vocabulario-técnico-en-simple)

### BLOQUE 2 — HTML y CSS
- [HTML — Etiquetas, atributos y Bootstrap usados en el proyecto](#html-etiquetas-atributos-y-bootstrap-usados-en-el-proyecto)
- [Bootstrap 5 — Clases y componentes usados en el proyecto](#bootstrap-5-clases-y-componentes-usados-en-el-proyecto)

### BLOQUE 3 — JavaScript: fundamentos del lenguaje
- [JavaScript — var, let, const y el scope](#javascript-var-let-const-y-el-scope)
- [JavaScript — Operadores especiales](#javascript-operadores-especiales)
- [JavaScript — El DOM y cómo manipular la página](#javascript-el-dom-y-cómo-manipular-la-página)
- [JavaScript — Métodos de Array y String más usados](#javascript-métodos-de-array-y-string-más-usados)
- [JavaScript — Expresiones regulares (Regex)](#javascript-expresiones-regulares-regex)
- [JavaScript — async/await y el ciclo de una petición](#javascript-asyncawait-y-el-ciclo-de-una-petición)

### BLOQUE 4 — JavaScript: APIs del navegador y patrones del proyecto
- [JavaScript — fetch, FormData, localStorage, URLSearchParams, Date, Math, Object](#javascript-fetch-formdata-localstorage-urlsearchparams-date-math-object)
- [FormData vs JSON — y cómo apiFetch maneja los dos](#formdata-vs-json-y-cómo-apifetch-maneja-los-dos)
- [Validaciones del lado del cliente — por qué y cómo](#validaciones-del-lado-del-cliente-por-qué-y-cómo)
- [Compartir código entre páginas — archivo JS reutilizable](#compartir-código-entre-páginas-archivo-js-reutilizable)
- [Filtros en el frontend — cómo se conectan al backend](#filtros-en-el-frontend-cómo-se-conectan-al-backend)
- [JavaScript del Frontend — Patrones usados en este proyecto](#javascript-del-frontend-patrones-usados-en-este-proyecto)
- [El frontend completo — páginas y su flujo](#el-frontend-completo-páginas-y-su-flujo)
- [Modales Ver y Editar — cómo funcionan técnicamente](#modales-ver-y-editar-cómo-funcionan-técnicamente)

### BLOQUE 5 — JavaScript: librerías (Leaflet y Chart.js)
- [Mapas con Leaflet.js — Cómo funciona el mapa interactivo](#mapas-con-leafletjs-cómo-funciona-el-mapa-interactivo)
- [JavaScript — Leaflet.js referencia completa](#javascript-leafletjs-referencia-completa)
- [Gráficas con Chart.js](#gráficas-con-chartjs)
- [JavaScript — Chart.js referencia completa](#javascript-chartjs-referencia-completa)

### BLOQUE 6 — PHP: fundamentos del lenguaje
- [PHP en 5 minutos](#php-en-5-minutos)
- [PHP — Sintaxis avanzada y operadores especiales](#php-sintaxis-avanzada-y-operadores-especiales)
- [PHP — `$request` y todos sus métodos](#php-$request-y-todos-sus-métodos)

### BLOQUE 7 — Laravel: arquitectura y componentes base
- [Qué es Laravel](#qué-es-laravel)
- [Cómo viaja una petición](#cómo-viaja-una-petición)
- [Las Rutas](#las-rutas)
- [Los Modelos](#los-modelos)
- [Los Controladores](#los-controladores)
- [El Middleware](#el-middleware)

### BLOQUE 8 — Laravel: base de datos y Eloquent
- [Consultas a la Base de Datos](#consultas-a-la-base-de-datos)
- [PHP — Eloquent avanzado](#php-eloquent-avanzado)
- [PHP — Eloquent: todos los métodos de consulta y CRUD](#php-eloquent-todos-los-métodos-de-consulta-y-crud)
- [PHP — Facades de Laravel: Hash, Cache, Storage, Auth, DB](#php-facades-de-laravel-hash-cache-storage-auth-db)
- [PHP — Carbon: fechas y tiempos](#php-carbon-fechas-y-tiempos)
- [PHP — Schema Builder: métodos de Blueprint](#php-schema-builder-métodos-de-blueprint)
- [Seeders — los datos de prueba](#seeders-los-datos-de-prueba)

### BLOQUE 9 — Laravel: funcionalidades del sistema
- [Roles y control de acceso — los 3 niveles del sistema](#roles-y-control-de-acceso-los-3-niveles-del-sistema)
- [Gestión de usuarios — usuarios.html + UserController](#gestión-de-usuarios-usuarioshtml-+-usercontroller)
- [Sistema de asignaciones de técnicos](#sistema-de-asignaciones-de-técnicos)
- [PHPUnit — cómo se testea el backend](#phpunit-cómo-se-testea-el-backend)

### BLOQUE 10 — Base de datos: SQL y PostgreSQL
- [PostgreSQL — SQL nativo en detalle](#postgresql-sql-nativo-en-detalle)
- [SQL — Funciones de agregación y CASE WHEN](#sql-funciones-de-agregación-y-case-when)
- [Trigger SQL — historial de estados automático](#trigger-sql-historial-de-estados-automático)
- [Vistas y funciones SQL avanzadas](#vistas-y-funciones-sql-avanzadas)

### BLOQUE 11 — Infraestructura: Docker, Redis y pruebas de carga
- [Redis](#redis)
- [Docker](#docker)
- [Docker Compose — estructura y sintaxis del docker-compose.yml](#docker-compose-estructura-y-sintaxis-del-docker-composeyml)
- [Artillery — pruebas de carga](#artillery-pruebas-de-carga)

### BLOQUE 12 — Referencia rápida de comandos
- [Comandos del proyecto](#comandos-del-proyecto)

---

---

# BLOQUE 1 — Panorama general

## 1. El gran panorama

Imagina que el sistema es un restaurante:

```
CLIENTE (tú en el navegador)
Abres index.html, llenas un formulario, haces clic
         |
         | Petición HTTP (es como gritar el pedido)
         v
NGINX (el mesero)
Recibe tu pedido y lo lleva a quien corresponde.
Si pides una página HTML --> te da el archivo directamente
Si pides datos de la API --> lo reenvía al backend
         |
         v
LARAVEL / PHP (la cocina)
Aquí se procesa toda la lógica:
¿Quién eres? ¿Tienes permiso? ¿Qué datos necesitas?
         |
    _____|_____
   |           |
   v           v
PostgreSQL    Redis
(la despensa) (los post-its en la nevera)
Datos que     Datos temporales,
duran         ultra rápidos
para siempre
```

**Regla de oro: el frontend NUNCA toca la base de datos directamente.**
Siempre le pide los datos al backend, y el backend decide qué darle y a quién.

---


## 12. Vocabulario técnico en simple

| Término         | Qué significa en simple                                      |
|-----------------|--------------------------------------------------------------|
| **API REST**    | Una forma estándar de comunicar frontend y backend via URLs |
| **Endpoint**    | Una URL específica de la API (ej: `/api/incidencias`)        |
| **JSON**        | Formato de texto para intercambiar datos entre sistemas      |
| **Token**       | Una clave temporal que prueba que ya hiciste login           |
| **Middleware**  | Código que se ejecuta antes de llegar al controlador         |
| **Eloquent**    | El sistema de Laravel para hablar con la BD sin SQL puro     |
| **Migración**   | Archivo PHP que crea o modifica tablas en la BD              |
| **Seeder**      | Archivo PHP que inserta datos de prueba en la BD             |
| **Relación**    | Conexión entre dos tablas (hasMany, belongsTo, etc.)         |
| **Caché**       | Guardar resultados temporalmente para no recalcularlos       |
| **CRUD**        | Create, Read, Update, Delete — las 4 operaciones básicas     |
| **HTTP Status** | Número que indica si una petición fue exitosa o no           |
| **Request**     | La petición que llega al servidor (URL + datos + headers)    |
| **Response**    | La respuesta que el servidor devuelve                        |
| **Namespace**   | Carpeta lógica para organizar clases sin que se repitan      |
| **fillable**    | Lista de campos que Laravel puede llenar (medida de seguridad) |
| **nullable**    | Un campo que puede estar vacío o nulo                        |
| **exists:**     | Validación que verifica que un ID exista en otra tabla       |

---

---



---

# BLOQUE 2 — HTML y CSS

## 31. HTML — Etiquetas, atributos y Bootstrap usados en el proyecto

### ¿Qué es HTML y cómo funciona?

HTML (HyperText Markup Language) es el lenguaje que describe la **estructura** de una página.
El navegador lee el archivo `.html` y construye el **DOM** (árbol de elementos en memoria).
CSS define el aspecto visual y JavaScript le da comportamiento interactivo.

```
Archivo HTML  →  Navegador lo analiza  →  Crea el DOM (árbol de nodos)
                                                  ↓
                                       JavaScript puede leer y modificar ese árbol
                                       CSS puede estilizar cada nodo
```

---

### Etiquetas de estructura

```html
<!-- <!DOCTYPE html> declara que es un documento HTML5.
     Sin esto el navegador entra en "modo compatible" (quirks mode) y puede comportarse diferente. -->
<!DOCTYPE html>
<html lang="es">

<!-- <head> contiene información SOBRE la página, no visible en pantalla.
     Aquí van: título de la pestaña, estilos CSS, metas de SEO, etc. -->
<head>
    <!-- charset=UTF-8: permite mostrar tildes, ñ, emojis, etc. sin errores. -->
    <meta charset="UTF-8">
    <!-- viewport: hace que la página se adapte al ancho del dispositivo (responsive). -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <title>: texto que aparece en la pestaña del navegador. -->
    <title>Gestión de Incidencias</title>

    <!-- <link>: importar recursos externos sin necesidad de descargarlos.
         rel="stylesheet": indica que es una hoja de estilos CSS.
         href: URL del archivo CSS (puede ser local o de un CDN). -->
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<!-- <body>: todo lo que el usuario ve en pantalla va aquí. -->
<body>
    <!-- Contenido de la página -->

    <!-- <script>: importar JavaScript.
         Siempre al final del body (o con defer) para que el DOM ya exista cuando JS se ejecute.
         src="archivo.js": carga JS externo. Sin src: JS en línea. -->
    <script src="js/api.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>
```

---

### Etiquetas de contenido más usadas

```html
<!-- <div>: contenedor genérico sin significado semántico.
     Se usa para agrupar elementos y aplicarles estilos o comportamiento. -->
<div class="contenedor">
    <!-- <span>: contenedor inline (no hace salto de línea).
         Útil para dar estilo a una parte del texto. -->
    <p>El estado es <span class="badge bg-warning">Pendiente</span></p>
</div>

<!-- <h1> a <h6>: títulos. h1 = más importante, h6 = menos importante.
     Solo debe haber UN h1 por página para buenas prácticas de SEO. -->
<h1>Sistema de Incidencias</h1>
<h3>Lista de reportes</h3>

<!-- <p>: párrafo de texto. -->
<p>Descripción de la incidencia aquí.</p>

<!-- <a>: enlace.
     href: a dónde va. Puede ser URL, ruta relativa, o "#" para nada.
     target="_blank": abre en nueva pestaña. -->
<a href="detalle.html?id=5">Ver detalle</a>
<a href="https://google.com" target="_blank">Google</a>

<!-- <img>: imagen.
     src: ruta de la imagen.
     alt: texto alternativo (accesibilidad + cuando la imagen no carga). -->
<img src="foto.jpg" alt="Foto del bache">

<!-- <ul>: lista sin orden (puntos).
     <ol>: lista ordenada (números).
     <li>: cada elemento de la lista. -->
<ul>
    <li>Pendiente</li>
    <li>En proceso</li>
    <li>Resuelto</li>
</ul>
```

---

### Formularios — la parte más importante del proyecto

```html
<!-- <form>: contenedor de un formulario.
     action: a dónde enviar (en este proyecto NO se usa, enviamos con JS).
     method: GET o POST (en este proyecto NO se usa, lo controlamos con fetch).
     En el proyecto se usa solo como contenedor visual y para acceder a los campos. -->
<form id="form-nueva-incidencia">

    <!-- <label>: etiqueta visible del campo.
         for="id-del-input": conecta la etiqueta con su input.
         Al hacer clic en la etiqueta, el foco va al input. Mejora accesibilidad. -->
    <label for="titulo" class="form-label">Título</label>

    <!-- <input>: campo de entrada de texto.
         type="text": texto libre.
         type="email": valida formato de email (con @ y dominio).
         type="password": oculta lo que se escribe con ******.
         type="number": solo acepta números.
         type="file": permite subir archivos (accept restringe tipos).
         type="hidden": campo invisible, guarda valores sin mostrarlos (ej: latitud/longitud).
         type="checkbox": casilla de verificación.
         
         id: para document.getElementById() en JS y para el for del label.
         name: nombre del campo al enviarlo (menos usado aquí que en PHP clásico).
         placeholder: texto de ayuda gris dentro del input.
         required: HTML nativo que bloquea submit si está vacío (validación básica).
         value: valor inicial del campo.
         disabled: deshabilita el campo (visible pero no editable ni enviable).
         readonly: solo lectura (editable visualmente pero no se puede cambiar). -->
    <input
        type="text"
        id="titulo"
        name="titulo"
        class="form-control"
        placeholder="Describe el problema brevemente"
        required
    >

    <!-- <textarea>: campo de texto multilínea.
         rows: cuántas filas de altura tiene por defecto. -->
    <textarea id="descripcion" class="form-control" rows="3" placeholder="Detalle el problema..."></textarea>

    <!-- <select>: lista desplegable.
         <option>: cada opción de la lista.
         value: el valor que se envía (puede diferir del texto visible).
         selected: la opción seleccionada por defecto. -->
    <select id="prioridad" class="form-select">
        <option value="">-- Selecciona prioridad --</option>
        <option value="baja">Baja</option>
        <option value="media" selected>Media</option>
        <option value="alta">Alta</option>
    </select>

    <!-- <input type="file">: para subir archivos.
         accept: tipos de archivo permitidos (MIME types o extensiones). -->
    <input type="file" id="foto" class="form-control" accept="image/jpeg,image/png">

    <!-- Campos ocultos: el usuario no los ve, JS los llena automáticamente. -->
    <input type="hidden" id="latitud" name="latitud">
    <input type="hidden" id="longitud" name="longitud">

    <!-- <button>:
         type="submit": envía el formulario (recarga la página si no se previene con JS).
         type="button": NO envía el formulario. Se recomienda para botones manejados con JS.
         type="reset": limpia el formulario.
         En el proyecto casi siempre se usa type="button" para controlar con addEventListener. -->
    <button type="button" id="btn-guardar" class="btn btn-primary">Guardar</button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
</form>
```

---

### Atributos especiales HTML5

```html
<!-- data-* (atributos de datos personalizados):
     Se puede guardar cualquier dato en el HTML y leerlo desde JS.
     Útil para pasar el ID de un elemento a un botón sin variables globales. -->
<button
    class="btn btn-sm btn-danger"
    data-id="42"
    data-nombre="Bache Avenida Principal"
    onclick="eliminar(this)"
>
    Eliminar
</button>

<script>
function eliminar(boton) {
    // .dataset accede a todos los atributos data-* como un objeto
    const id     = boton.dataset.id;      // "42"
    const nombre = boton.dataset.nombre;  // "Bache Avenida Principal"
}
</script>

<!-- id vs class:
     id: identificador ÚNICO en la página. Solo puede existir UN elemento con ese id.
         JS lo busca con document.getElementById('mi-id').
     class: puede repetirse en múltiples elementos.
         CSS lo estiliza con .mi-clase { ... }
         JS lo busca con document.querySelectorAll('.mi-clase'). -->
<div id="contenedor-unico">   <!-- solo uno en toda la página -->
    <div class="tarjeta">...</div>  <!-- puede haber muchas tarjetas -->
    <div class="tarjeta">...</div>
</div>

<!-- style (estilos en línea): aplica CSS directamente al elemento.
     Se usa poco, prefiriéndose clases CSS reutilizables.
     En el proyecto se usa para mostrar/ocultar elementos programáticamente. -->
<div id="link-usuarios" style="display:none">Usuarios</div>
<!-- JS puede cambiar esto con: element.style.display = '' (mostrar) -->
```

---

### Tablas HTML — para mostrar listas de datos

```html
<!-- Estructura completa de una tabla HTML:
     <table>: contenedor principal.
     <thead>: encabezado (fila de títulos de columna).
     <tbody>: cuerpo (filas de datos).
     <tr>: fila (table row).
     <th>: celda de encabezado (negrita, centrada por defecto).
     <td>: celda de datos normal. -->
<table class="table table-striped table-hover">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Título</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody id="tabla-incidencias">
        <!-- JS rellena este tbody dinámicamente con innerHTML -->
        <!-- Ejemplo de lo que genera JS: -->
        <tr>
            <td>1</td>
            <td>Bache en Av. Principal</td>
            <td><span class="badge bg-warning">Pendiente</span></td>
            <td>
                <button class="btn btn-sm btn-info">Ver</button>
                <button class="btn btn-sm btn-warning">Editar</button>
            </td>
        </tr>
    </tbody>
</table>
```

---

### El elemento `<canvas>` — para gráficas y mapas

```html
<!-- <canvas>: lienzo en blanco donde JavaScript dibuja.
     A diferencia de <div>, un <canvas> NO contiene elementos HTML.
     JavaScript dibuja píxeles directamente en él.
     
     Chart.js dibuja gráficas en <canvas>.
     Leaflet dibuja mapas en <div> (no necesita canvas). -->
<canvas id="grafica-estado" width="400" height="300"></canvas>

<!-- JS lo usa así: -->
<script>
const ctx = document.getElementById('grafica-estado'); // obtiene el canvas
const grafica = new Chart(ctx, { type: 'bar', data: {...} });
</script>
```

---


## 41. Bootstrap 5 — Clases y componentes usados en el proyecto

### ¿Qué es Bootstrap?

Bootstrap es un framework CSS que provee clases predefinidas para estilizar elementos
sin escribir CSS personalizado. Se incluye como CDN en el `<head>`:

```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<!-- Bootstrap JS (para modales, dropdowns, tooltips): -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
```

---

### Sistema de grilla (Grid)

Bootstrap divide la pantalla en 12 columnas. Puedes combinarlas.

```html
<!-- container: centra el contenido con márgenes automáticos. -->
<!-- container-fluid: ancho completo (sin márgenes). -->
<div class="container">

    <!-- row: fila del grid. Los col van dentro de row. -->
    <div class="row">

        <!-- col-md-6: en pantallas medianas (768px+), ocupa 6/12 = 50% del ancho. -->
        <!-- col-12: en pantallas pequeñas (default), ocupa 12/12 = 100% (full width). -->
        <div class="col-12 col-md-6">
            <!-- Contenido de la mitad izquierda -->
        </div>
        <div class="col-12 col-md-6">
            <!-- Contenido de la mitad derecha -->
        </div>
    </div>

    <!-- col-md-4: 4/12 = 33% → tres columnas iguales -->
    <div class="row">
        <div class="col-md-4"> Card 1 </div>
        <div class="col-md-4"> Card 2 </div>
        <div class="col-md-4"> Card 3 </div>
    </div>
</div>

<!-- Breakpoints de Bootstrap 5:
     Sin prefijo (col-6): siempre aplica (mobile first)
     col-sm-*: ≥ 576px (teléfonos grandes)
     col-md-*: ≥ 768px (tablets)
     col-lg-*: ≥ 992px (laptops)
     col-xl-*: ≥ 1200px (monitores grandes) -->
```

---

### Clases de utilidad más usadas

```html
<!-- ESPACIADO (margin y padding):
     m = margin, p = padding
     t = top, b = bottom, s = start(izq), e = end(der), x = horizontal, y = vertical
     Número 0-5: 0 = 0, 1 = 0.25rem, 2 = 0.5rem, 3 = 1rem, 4 = 1.5rem, 5 = 3rem -->
<div class="mb-3">  <!-- margin-bottom: 1rem -->
<div class="mt-4">  <!-- margin-top: 1.5rem -->
<div class="p-3">   <!-- padding: 1rem por todos lados -->
<div class="px-4">  <!-- padding izquierda y derecha: 1.5rem -->
<div class="my-2">  <!-- margin arriba y abajo: 0.5rem -->
<div class="ms-auto"> <!-- margin-start: auto (empuja el elemento a la derecha) -->

<!-- DISPLAY (mostrar/ocultar):
     d-none: display:none (oculto)
     d-block: display:block
     d-flex: display:flex
     d-inline: display:inline
     Con breakpoint: d-md-none (oculto en tablets+), d-md-block (visible en tablets+) -->
<div class="d-none">    <!-- oculto siempre -->
<div class="d-none d-md-block">  <!-- oculto en móvil, visible en tablet+ -->

<!-- FLEXBOX (alinear elementos):
     justify-content-*: entre, around, evenly, start, end, center
     align-items-*: start, end, center, stretch
     flex-wrap: wrap en múltiples líneas
     gap-2: espacio entre elementos del flex -->
<div class="d-flex justify-content-between align-items-center gap-2">
    <span>Izquierda</span>
    <span>Derecha</span>
</div>

<!-- TEXTO:
     text-center / text-start / text-end: alineación
     text-muted: gris claro (texto secundario)
     fw-bold: negrita
     text-truncate: corta el texto con ... si es muy largo -->
<p class="text-center text-muted">Texto gris centrado</p>
<p class="fw-bold">Negrita</p>

<!-- COLORES (background y texto):
     bg-primary (azul), bg-success (verde), bg-danger (rojo),
     bg-warning (amarillo), bg-info (celeste), bg-dark (negro), bg-light (gris claro)
     text-primary, text-success, text-danger, text-warning, text-white, text-dark -->
<div class="bg-primary text-white p-3">Fondo azul, texto blanco</div>

<!-- BORDER Y SOMBRA:
     border: borde gris por todos lados
     border-primary: borde azul
     rounded: bordes redondeados
     rounded-circle: círculo perfecto
     shadow: sombra suave
     shadow-sm: sombra más pequeña -->
<div class="border rounded shadow-sm p-3">Tarjeta con borde y sombra</div>
```

---

### Botones

```html
<!-- btn: clase base obligatoria para todos los botones Bootstrap.
     btn-*: color/estilo del botón. -->
<button class="btn btn-primary">Azul (principal)</button>
<button class="btn btn-secondary">Gris (secundario)</button>
<button class="btn btn-success">Verde (éxito)</button>
<button class="btn btn-danger">Rojo (peligro/eliminar)</button>
<button class="btn btn-warning">Amarillo (advertencia)</button>
<button class="btn btn-info">Celeste (información)</button>
<button class="btn btn-light">Claro</button>
<button class="btn btn-dark">Oscuro</button>
<button class="btn btn-outline-primary">Borde azul sin relleno</button>

<!-- Tamaños:
     btn-lg: botón grande
     btn-sm: botón pequeño (se usa en tablas donde el espacio es limitado) -->
<button class="btn btn-danger btn-sm">Eliminar</button>

<!-- Botón deshabilitado:
     disabled: no se puede hacer clic, apariencia opaca.
     JS puede activarlo/desactivarlo: boton.disabled = true/false -->
<button class="btn btn-primary" disabled>Guardando...</button>

<!-- Botón que abre un modal (data-bs-*):
     data-bs-toggle="modal": indica que este botón abre un modal.
     data-bs-target="#id-del-modal": cuál modal abrir. -->
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#miModal">
    Abrir modal
</button>
```

---

### Modales (ventanas emergentes)

```html
<!-- Estructura completa de un modal de Bootstrap:
     modal: clase base, oculto por defecto.
     fade: animación de entrada/salida.
     modal-dialog: centra el modal.
     modal-dialog-centered: centrado vertical también.
     modal-dialog-scrollable: el body del modal tiene scroll si el contenido es largo.
     modal-lg / modal-xl / modal-sm: tamaños. -->
<div class="modal fade" id="miModal" tabindex="-1" aria-labelledby="tituloModal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <!-- modal-header: barra superior del modal con título y X -->
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModal">Título del modal</h5>
                <!-- data-bs-dismiss="modal": cierra el modal al hacer clic -->
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- modal-body: contenido principal del modal -->
            <div class="modal-body">
                <p>Contenido aquí</p>
                <form id="mi-formulario">...</form>
            </div>

            <!-- modal-footer: botones de acción -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Abrir/cerrar el modal desde JavaScript:
     Útil cuando se necesita abrir el modal programáticamente (no con un botón fijo). -->
<script>
// Abrir:
const modal = new bootstrap.Modal(document.getElementById('miModal'));
modal.show();

// O con la referencia guardada:
let modalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('miModal'));
modalInstance.show();
modalInstance.hide();

// Evento cuando el modal TERMINA de abrirse (la animación completó):
document.getElementById('miModal').addEventListener('shown.bs.modal', () => {
    // El modal ya es visible → inicializar mapa de Leaflet aquí
    inicializarMapa();
});

// Evento cuando el modal se CIERRA:
document.getElementById('miModal').addEventListener('hidden.bs.modal', () => {
    limpiarFormulario();
});
</script>
```

---

### Alertas y Badges

```html
<!-- ALERTAS: mensajes de estado para el usuario.
     alert + alert-* define el color.
     d-none: oculta la alerta hasta que JS la muestre. -->
<div class="alert alert-success" role="alert">
    ✓ Incidencia creada correctamente
</div>
<div class="alert alert-danger" role="alert">
    ✗ Error al guardar los datos
</div>
<div class="alert alert-warning" role="alert">
    ⚠ La foto supera el tamaño máximo
</div>
<div class="alert alert-info" role="alert">
    ℹ Cargando datos...
</div>

<!-- Alerta con botón para cerrar (dismissible): -->
<div class="alert alert-success alert-dismissible fade show" role="alert">
    Operación exitosa
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<!-- Desde JS se muestra así: -->
<div id="alerta-form" class="d-none alert mb-3"></div>
<script>
function mostrarError(msg) {
    const alerta = document.getElementById('alerta-form');
    alerta.textContent = msg;
    alerta.className   = 'alert alert-danger mb-3'; // reemplaza d-none con alert-danger
}
function ocultarAlerta() {
    const alerta = document.getElementById('alerta-form');
    alerta.className   = 'd-none alert mb-3'; // vuelve a ocultar
    alerta.textContent = '';
}
</script>

<!-- BADGES: etiquetas pequeñas para mostrar estado o conteo.
     badge + bg-* para el color.
     rounded-pill: bordes muy redondeados (como pastilla). -->
<span class="badge bg-warning text-dark">Pendiente</span>
<span class="badge bg-info">En proceso</span>
<span class="badge bg-success">Resuelto</span>
<span class="badge bg-danger rounded-pill" id="contador-notif">3</span>
```

---

### Tablas con Bootstrap

```html
<!-- table: clase base para estilos de tabla.
     table-striped: filas alternadas en gris claro (fácil de leer).
     table-hover: la fila se resalta al pasar el mouse.
     table-bordered: borde en todas las celdas.
     table-sm: filas más compactas (menos padding). -->
<div class="table-responsive">
    <!-- table-responsive: añade scroll horizontal en pantallas pequeñas -->
    <table class="table table-striped table-hover">
        <thead class="table-dark">    <!-- encabezado oscuro -->
        <!-- O: table-primary, table-secondary, table-light -->
            <tr>
                <th>#</th>
                <th>Título</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody id="tabla-incidencias-body">
            <!-- JS rellena este tbody -->
        </tbody>
    </table>
</div>
```

---

### Cards (tarjetas)

```html
<!-- Cards: contenedores con borde, sombra y estructura predefinida.
     Se usan en el dashboard para mostrar métricas. -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0">Total de Incidencias</h6>
    </div>
    <div class="card-body text-center">
        <h2 class="display-4 fw-bold text-primary" id="total-incidencias">0</h2>
        <p class="text-muted mb-0">registradas en el sistema</p>
    </div>
</div>
```

---

### Formularios con Bootstrap

```html
<!-- form-label: estilo para etiquetas de formulario. -->
<!-- form-control: estilo para inputs de texto, textarea, file. -->
<!-- form-select: estilo para select dropdowns. -->
<!-- form-check: estilo para checkboxes y radios. -->

<div class="mb-3">
    <label for="titulo" class="form-label fw-semibold">Título</label>
    <input type="text" class="form-control" id="titulo" placeholder="Ej: Bache en Av. Principal">
    <!-- Texto de ayuda debajo del input: -->
    <div class="form-text text-muted">Máximo 255 caracteres.</div>
</div>

<div class="mb-3">
    <label for="estado" class="form-label fw-semibold">Estado</label>
    <select class="form-select" id="estado">
        <option value="">-- Seleccionar --</option>
        <option value="pendiente">Pendiente</option>
        <option value="en_proceso">En proceso</option>
        <option value="resuelto">Resuelto</option>
    </select>
</div>

<!-- is-invalid: marca el input con borde rojo (error de validación). -->
<!-- invalid-feedback: texto de error que se muestra bajo el input. -->
<div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" class="form-control is-invalid" id="email">
    <div class="invalid-feedback">
        Por favor ingresa un email válido.
    </div>
</div>
<!-- is-valid: borde verde (campo correcto). -->
```

---

### Navbar y Sidebar

```html
<!-- Navbar: barra de navegación horizontal. -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <!-- Marca/logo del sitio -->
        <a class="navbar-brand" href="#">Sistema Incidencias</a>

        <!-- Botón hamburguesa para móviles -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Links del navbar -->
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.html">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="incidencias.html">Incidencias</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
```

---

### FontAwesome — íconos

```html
<!-- FontAwesome provee íconos como clases CSS. Se carga en el head: -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Usar íconos con la clase fa + nombre-del-icono: -->
<i class="fas fa-home"></i>        <!-- casa (sólido) -->
<i class="fas fa-user"></i>        <!-- persona -->
<i class="fas fa-bell"></i>        <!-- campana (notificaciones) -->
<i class="fas fa-edit"></i>        <!-- lápiz (editar) -->
<i class="fas fa-trash"></i>       <!-- papelera (eliminar) -->
<i class="fas fa-plus"></i>        <!-- + (agregar) -->
<i class="fas fa-map-marker-alt"></i> <!-- pin de mapa -->
<i class="fas fa-check"></i>       <!-- palomita (éxito) -->
<i class="fas fa-times"></i>       <!-- X (cerrar) -->
<i class="fas fa-eye"></i>         <!-- ojo (ver) -->
<i class="fas fa-sign-out-alt"></i> <!-- salir -->
<i class="fas fa-cog"></i>         <!-- engranaje (configuración) -->

<!-- Los iconos heredan el color y tamaño del texto circundante.
     Se pueden cambiar con clases de Bootstrap o CSS: -->
<i class="fas fa-bell text-warning"></i>   <!-- campana amarilla -->
<i class="fas fa-check text-success fs-4"></i> <!-- palomita verde, tamaño más grande -->
```

---

---



---

# BLOQUE 3 — JavaScript: fundamentos del lenguaje

## 42. JavaScript — var, let, const y el scope

### Las tres formas de declarar variables

```javascript
// VAR (forma antigua, evitar en código nuevo):
// - Scope de función: visible en toda la función donde se declara.
// - Hoisting: se "eleva" al inicio de la función (existe pero es undefined antes de la línea).
// - Puede redeclararse con var en el mismo scope.
var nombre = 'José';
var nombre = 'Carlos'; // sin error, sobreescribe silenciosamente

// LET (forma moderna, para variables que cambian):
// - Scope de bloque { }: solo existe dentro del bloque donde se declara.
// - No se puede redeclarar en el mismo scope.
// - Se puede reasignar (cambiar el valor).
let estado = 'pendiente';
estado = 'resuelto'; // ✅ permitido
// let estado = 'otro'; ❌ SyntaxError: ya fue declarada

// CONST (para valores que no cambian):
// - Scope de bloque, igual que let.
// - No se puede reasignar (la referencia es constante).
// - PERO si es un objeto o array, su CONTENIDO sí puede cambiar.
const API_URL = '/api';
// API_URL = '/otra'; ❌ TypeError: Assignment to constant variable

const usuario = { nombre: 'José' };
usuario.nombre = 'Carlos'; // ✅ el objeto se puede modificar
// usuario = {}; ❌ no se puede reasignar la variable a otro objeto

const incidencias = [];
incidencias.push({ id: 1 }); // ✅ el array se puede modificar
// incidencias = []; ❌ no se puede reasignar
```

---

### Scope (ámbito de las variables)

```javascript
// SCOPE DE BLOQUE (let/const): la variable solo existe dentro de {}
{
    let x = 10;
    const y = 20;
    console.log(x); // 10
    console.log(y); // 20
}
// console.log(x); ❌ ReferenceError: x is not defined (fuera del bloque)

// SCOPE DE FUNCIÓN: variables dentro de function() no son accesibles afuera.
function saludar() {
    const mensaje = 'Hola'; // solo existe dentro de saludar()
    console.log(mensaje);
}
saludar();
// console.log(mensaje); ❌ ReferenceError

// SCOPE GLOBAL: declaradas fuera de cualquier función o bloque.
// Accesibles desde cualquier parte del archivo.
// Las variables globales también son propiedades de window en el navegador.
let marcadorGlobal = null; // global — se usa en múltiples funciones
let mapaVer = null;        // global — para reutilizar el mapa entre aperturas del modal

// Dentro de funciones se puede acceder a variables globales:
function actualizarMarcador(lat, lng) {
    if (marcadorGlobal) {
        marcadorGlobal.setLatLng([lat, lng]); // accede a la variable global
    }
}

// HOISTING: var se "eleva" al inicio de su función, let/const no.
console.log(a); // undefined (hoisted, pero no tiene valor aún)
var a = 5;
console.log(a); // 5

// console.log(b); ❌ ReferenceError: Cannot access 'b' before initialization
let b = 5;
```

---


## 32. JavaScript — Operadores especiales

### El operador ternario `?`

El operador ternario es un `if/else` en una sola línea.
Sintaxis: `condicion ? valorSiVerdadero : valorSiFalso`

```javascript
// Forma larga con if/else:
let mensaje;
if (usuario.role.nombre === 'admin') {
    mensaje = 'Bienvenido, administrador';
} else {
    mensaje = 'Bienvenido, ciudadano';
}

// Forma corta con ternario:
const mensaje = usuario.role.nombre === 'admin'
    ? 'Bienvenido, administrador'
    : 'Bienvenido, ciudadano';

// En HTML dinámico (muy común en este proyecto):
fila.innerHTML = `
    <span class="badge ${incidencia.estado === 'resuelto' ? 'bg-success' : 'bg-warning'}">
        ${incidencia.estado}
    </span>
`;
// Si está resuelto → 'bg-success' (verde)
// Si no → 'bg-warning' (amarillo)

// Con tres opciones anidadas (ternario anidado):
const clase = estado === 'resuelto'  ? 'bg-success'
            : estado === 'en_proceso' ? 'bg-info'
            :                          'bg-warning'; // default: pendiente
```

---

### El operador `??` (Nullish Coalescing — "si es null o undefined")

Devuelve el lado derecho solo si el lado izquierdo es `null` o `undefined`.
Diferente de `||` que también reacciona a `0`, `""`, `false`.

```javascript
// ?? vs ||:
const a = null;
const b = 0;
const c = '';

console.log(a ?? 'default');  // 'default'  (a es null → da default)
console.log(b ?? 'default');  // 0          (b es 0, NO es null → da b)
console.log(c ?? 'default');  // ''         (c es vacío, NO es null → da c)

console.log(b || 'default');  // 'default'  (b es 0 = falsy → da default)
console.log(c || 'default');  // 'default'  (c es '' = falsy → da default)

// En el proyecto se usa para valores que pueden ser null/undefined:
const tiempoDias = datos.tiempo_promedio ?? 0;
// Si el backend no mandó tiempo_promedio (null), usa 0
// Si mandó 0 (ninguna incidencia resuelta), mantiene el 0 (no lo reemplaza)

const nombreUsuario = usuario?.name ?? 'Usuario desconocido';
```

---

### El operador `?.` (Optional Chaining — "si existe, accede")

Evita errores cuando un objeto puede ser `null` o `undefined`.
Sin `?.` → `TypeError: Cannot read properties of null`.
Con `?.` → devuelve `undefined` silenciosamente.

```javascript
// Sin optional chaining (código frágil):
const rolNombre = usuario.role.nombre; // ERROR si usuario.role es null

// Con optional chaining (código seguro):
const rolNombre = usuario?.role?.nombre;
// Si usuario es null → undefined (sin error)
// Si usuario existe pero role es null → undefined (sin error)
// Si ambos existen → el valor real de nombre

// En el proyecto:
const esAdmin = usuario?.role?.nombre === 'admin'; // seguro aunque role sea null

// Con métodos:
const primerComentario = incidencia?.comentarios?.find(c => c.id === 1);
// Si incidencia o comentarios es null, devuelve undefined sin error

// Con arrays:
const ciudadNombre = datos?.ciudad?.nombre ?? 'Sin ciudad';
// Si ciudad no existe → 'Sin ciudad'
```

---

### Operador `||` para valores por defecto

Antes de `??`, se usaba `||` para valores por defecto.
La diferencia: `||` actúa con cualquier valor "falsy" (false, 0, "", null, undefined, NaN).

```javascript
const nombre = usuario.nombre || 'Anónimo';
// Si nombre es null, undefined, '', 0, false → 'Anónimo'
// Problema: si nombre es "" (string vacío intencional), también da 'Anónimo'

// Hoy en día se prefiere ?? para valores que específicamente pueden ser null/undefined,
// y || para booleanos o cuando cualquier valor falsy debe dar el default.
```

---

### Arrow functions `=>`

Las arrow functions son una forma corta de escribir funciones anónimas.

```javascript
// Función tradicional:
function sumar(a, b) {
    return a + b;
}

// Arrow function equivalente:
const sumar = (a, b) => {
    return a + b;
};

// Arrow function con return implícito (sin llaves, sin return):
const sumar = (a, b) => a + b;

// Con un solo parámetro (se pueden omitir los paréntesis):
const doble = n => n * 2;
const doble = (n) => n * 2; // equivalente

// Sin parámetros (los paréntesis son obligatorios):
const saludar = () => 'Hola';

// En el proyecto, las arrow functions aparecen mucho en:
// 1. Callbacks de eventos:
boton.addEventListener('click', () => { cargarIncidencias(); });

// 2. .map() y .filter():
const nombres = usuarios.map(u => u.name);
const admins  = usuarios.filter(u => u.role?.nombre === 'admin');

// 3. async arrow functions:
const cargar = async () => {
    const datos = await apiFetch('/incidencias');
    // ...
};

// 4. Dentro de setTimeout y setInterval:
setInterval(() => cargarNotificaciones(), 60000);
```

---

### Template Literals (backticks)

Los template literals permiten incrustar expresiones dentro de strings.
Se escriben con ` ` (acento grave / backtick) en lugar de ' ' o " ".

```javascript
const titulo = 'Bache en la avenida';
const estado = 'pendiente';
const id     = 42;

// String concatenado (forma antigua, difícil de leer):
const html = '<tr><td>' + id + '</td><td>' + titulo + '</td><td>' + estado + '</td></tr>';

// Template literal (forma moderna, mucho más legible):
const html = `<tr><td>${id}</td><td>${titulo}</td><td>${estado}</td></tr>`;

// Multilinea (sin concatenar strings ni \n):
const tarjeta = `
    <div class="card">
        <div class="card-body">
            <h5>${titulo}</h5>
            <p>Estado: ${estado}</p>
        </div>
    </div>
`;

// Cualquier expresión JavaScript va dentro de ${ }:
const badge = `<span class="badge ${estado === 'resuelto' ? 'bg-success' : 'bg-warning'}">${estado}</span>`;
const fecha = `Creado el ${new Date(incidencia.created_at).toLocaleDateString('es-EC')}`;
```

---

### Destructuring (desestructuración)

Extrae propiedades de objetos o elementos de arrays en una sola línea.

```javascript
// Sin destructuring:
const nombre = usuario.name;
const email  = usuario.email;
const rol    = usuario.role?.nombre;

// Con destructuring de objeto:
const { name: nombre, email, role } = usuario;
// name: nombre → renombra la propiedad 'name' a 'nombre'
// email → nombre igual en ambos lados
// role → extrae la propiedad anidada completa

// Destructuring de array:
const coordenadas = [-2.2289, -80.8994];
const [lat, lng] = coordenadas; // lat = -2.2289, lng = -80.8994

// En el proyecto (se usa mucho en arrow functions):
usuarios.forEach(({ id, name, role }) => {
    console.log(id, name, role?.nombre);
});

// Con valores por defecto:
const { estado = 'pendiente', prioridad = 'media' } = incidencia;
```

---

### Spread operator `...`

Expande un array u objeto en sus elementos individuales.

```javascript
// Con arrays:
const numeros = [1, 2, 3];
const mas     = [4, 5, 6];
const todos   = [...numeros, ...mas]; // [1, 2, 3, 4, 5, 6]

// Con objetos (combinar dos objetos):
const base    = { estado: 'pendiente', prioridad: 'media' };
const extra   = { titulo: 'Bache', ciudad_id: 5 };
const completo = { ...base, ...extra };
// { estado: 'pendiente', prioridad: 'media', titulo: 'Bache', ciudad_id: 5 }

// Para clonar sin mutar el original:
const copia = { ...incidenciaOriginal }; // objeto nuevo, no el mismo en memoria

// En funciones con número variable de argumentos:
function sumarTodos(...numeros) {
    return numeros.reduce((acc, n) => acc + n, 0);
}
sumarTodos(1, 2, 3, 4); // 10
```

---


## 33. JavaScript — El DOM y cómo manipular la página

### ¿Qué es el DOM?

El DOM (Document Object Model) es la representación en memoria del HTML de la página.
Es un árbol de nodos que JavaScript puede leer y modificar en tiempo real.

```
document                     ← nodo raíz
└── <html>
    ├── <head>
    │   └── <title>
    └── <body>
        ├── <nav>
        ├── <div id="contenedor">
        │   ├── <h1>
        │   └── <p class="texto">
        └── <script>
```

---

### Seleccionar elementos

```javascript
// document.getElementById('id'): busca el elemento con ese id exacto.
// Es el método más rápido y específico.
// Devuelve: el elemento DOM o null si no existe.
const titulo = document.getElementById('detalle-titulo');

// document.querySelector('selector CSS'): busca el PRIMERO que coincida.
// Acepta cualquier selector CSS: clase, id, etiqueta, atributo, etc.
// Devuelve: el primer elemento o null.
const boton   = document.querySelector('#btn-guardar');       // por id
const badge   = document.querySelector('.badge.bg-success');  // por clases
const input   = document.querySelector('input[type="email"]'); // por atributo
const primera = document.querySelector('tr');                 // por etiqueta

// document.querySelectorAll('selector CSS'): busca TODOS los que coincidan.
// Devuelve: NodeList (parecido a un array, pero no es array puro).
const botones  = document.querySelectorAll('.btn-eliminar');
const filas    = document.querySelectorAll('tbody tr');

// Convertir NodeList a Array (para usar .map(), .filter(), etc.):
const arrayBotones = Array.from(botones);
// O también: [...botones]

// Buscar dentro de un elemento (no en todo el documento):
const contenedor = document.getElementById('tabla-body');
const celdas     = contenedor.querySelectorAll('td');
```

---

### Leer y modificar contenido

```javascript
const elemento = document.getElementById('mi-parrafo');

// .textContent: lee/escribe solo el TEXTO (sin HTML).
// Más seguro que innerHTML porque no interpreta HTML (evita XSS).
elemento.textContent = 'Nuevo texto';
console.log(elemento.textContent); // 'Nuevo texto'

// .innerHTML: lee/escribe HTML completo (interpreta etiquetas).
// Útil para insertar HTML dinámico. Cuidado con XSS si el contenido viene del usuario.
elemento.innerHTML = '<strong>Texto en negrita</strong>';

// .value: para inputs, selects y textareas.
const input = document.getElementById('titulo');
const valor = input.value;        // leer lo que el usuario escribió
input.value = 'Valor predefinido'; // rellenar el campo

// Para checkboxes:
const checkbox = document.getElementById('activo');
const marcado  = checkbox.checked; // true o false
checkbox.checked = true; // marcarlo programáticamente

// Para selects:
const select = document.getElementById('estado');
const opcion = select.value;        // valor de la opción seleccionada
select.value = 'en_proceso';        // seleccionar una opción por su value
```

---

### Modificar estilos y clases

```javascript
const elemento = document.getElementById('alerta');

// .style.propiedad: CSS en línea (sobrescribe cualquier clase).
elemento.style.display    = 'none';   // ocultar
elemento.style.display    = '';       // mostrar (quita el inline, vuelve al CSS)
elemento.style.color      = 'red';
elemento.style.backgroundColor = '#f0f0f0'; // camelCase, no kebab-case

// .classList: la forma correcta de manejar clases CSS.
// .classList.add('clase'): agrega una clase.
elemento.classList.add('d-none');        // ocultar con Bootstrap

// .classList.remove('clase'): quita una clase.
elemento.classList.remove('d-none');     // mostrar

// .classList.toggle('clase'): agrega si no está, quita si está.
elemento.classList.toggle('active');

// .classList.contains('clase'): verifica si tiene la clase (true/false).
if (elemento.classList.contains('d-none')) {
    // está oculto
}

// .classList.replace('vieja', 'nueva'): reemplaza una clase por otra.
elemento.classList.replace('btn-success', 'btn-warning');

// Reasignar className (reemplaza TODAS las clases):
elemento.className = 'alert alert-danger mb-3';
```

---

### Crear e insertar elementos

```javascript
// document.createElement('etiqueta'): crea un elemento nuevo (aún no está en la página).
const nuevaFila = document.createElement('tr');
nuevaFila.innerHTML = `<td>1</td><td>Bache</td>`;

// .appendChild(elemento): inserta el elemento al FINAL del padre.
const tbody = document.getElementById('tabla-body');
tbody.appendChild(nuevaFila);

// .prepend(elemento): inserta al INICIO del padre.
// .insertBefore(nuevo, referencia): inserta antes de otro elemento.
// .remove(): elimina el elemento del DOM.

// La forma más rápida en el proyecto: reemplazar todo el innerHTML.
// Útil para actualizar toda una tabla o lista de una vez.
tbody.innerHTML = datos.map(inc => `
    <tr>
        <td>${inc.id}</td>
        <td>${inc.titulo}</td>
    </tr>
`).join('');
// .join(''): une el array de strings en un solo string sin separadores
```

---

### Eventos

```javascript
// .addEventListener('evento', función): escuchar un evento.
// Es el método moderno, preferido sobre onclick="" en el HTML.

// Evento de clic:
document.getElementById('btn-guardar').addEventListener('click', guardarIncidencia);
document.getElementById('btn-guardar').addEventListener('click', () => { guardarIncidencia(); });

// Evento de submit en formulario:
document.getElementById('mi-form').addEventListener('submit', (e) => {
    e.preventDefault(); // cancela la recarga de página que haría el submit por defecto
    guardarIncidencia();
});

// Evento al cargar la página (cuando el DOM ya está listo):
document.addEventListener('DOMContentLoaded', () => {
    // Aquí inicializar todo: cargar datos, configurar eventos, etc.
    // El DOM ya existe, pero las imágenes pueden aún estar cargando.
    cargarIncidencias();
});

// Evento de cambio en select o input:
document.getElementById('select-estado').addEventListener('change', (e) => {
    const nuevoValor = e.target.value; // e.target es el elemento que disparó el evento
    filtrarPorEstado(nuevoValor);
});

// Evento de tecla:
document.getElementById('busqueda').addEventListener('keyup', (e) => {
    if (e.key === 'Enter') { // verificar qué tecla se presionó
        buscar();
    }
});

// Evento de entrada de texto (cada vez que el usuario escribe):
document.getElementById('busqueda').addEventListener('input', () => {
    buscarEnTiempoReal();
});
```

---

### Navegación y URL

```javascript
// Redirigir a otra página:
window.location.href = 'login.html';
window.location.href = `detalle.html?id=${idIncidencia}`;

// Leer la URL actual:
console.log(window.location.href);    // URL completa
console.log(window.location.pathname); // solo la ruta: '/incidencias.html'
console.log(window.location.search);  // los parámetros: '?id=7&estado=pendiente'

// Leer parámetros de la URL:
// Si la URL es: detalle.html?id=7&nombre=bache
const params = new URLSearchParams(window.location.search);
const id      = params.get('id');      // '7' (siempre string)
const nombre  = params.get('nombre'); // 'bache'
const noExiste = params.get('otro'); // null si no está

// Volver a la página anterior (como el botón Atrás del navegador):
window.history.back();

// Recargar la página:
window.location.reload();
```

---


## 34. JavaScript — Métodos de Array y String más usados

### Métodos de Array

```javascript
const incidencias = [
    { id: 1, titulo: 'Bache', estado: 'pendiente',  prioridad: 'alta' },
    { id: 2, titulo: 'Cable', estado: 'en_proceso', prioridad: 'media' },
    { id: 3, titulo: 'Árbol', estado: 'resuelto',   prioridad: 'baja' },
];

// .map(fn): transforma cada elemento → devuelve NUEVO array de la misma longitud.
// El original no se modifica.
const titulos  = incidencias.map(inc => inc.titulo);
// ['Bache', 'Cable', 'Árbol']

const htmlFilas = incidencias.map(inc => `<tr><td>${inc.titulo}</td></tr>`);
// ['<tr><td>Bache</td></tr>', '<tr><td>Cable</td></tr>', '<tr><td>Árbol</td></tr>']

// .filter(fn): filtra elementos → devuelve NUEVO array con los que cumplen la condición.
const pendientes = incidencias.filter(inc => inc.estado === 'pendiente');
// [{ id: 1, titulo: 'Bache', ... }]

const altas = incidencias.filter(inc => inc.prioridad === 'alta' || inc.prioridad === 'media');

// .find(fn): busca el PRIMERO que cumpla la condición → devuelve el elemento o undefined.
const bache = incidencias.find(inc => inc.id === 1);
// { id: 1, titulo: 'Bache', ... }

const noExiste = incidencias.find(inc => inc.id === 999);
// undefined

// .some(fn): ¿algún elemento cumple la condición? → devuelve true o false.
const hayAltas = incidencias.some(inc => inc.prioridad === 'alta');
// true (porque hay una con prioridad 'alta')

const esTecnicoAsignado = asignaciones.some(a => a.usuario_id === usuarioActual.id);

// .every(fn): ¿TODOS los elementos cumplen la condición? → devuelve true o false.
const todasResueltas = incidencias.every(inc => inc.estado === 'resuelto');
// false (no todas están resueltas)

// .forEach(fn): ejecuta una función para cada elemento. No devuelve nada.
// Es como un for-of pero funcional.
incidencias.forEach(inc => {
    console.log(inc.titulo);
});

// .join('separador'): une todos los elementos en un string.
const lista = ['Pendiente', 'En proceso', 'Resuelto'];
lista.join(', ');  // 'Pendiente, En proceso, Resuelto'
lista.join('');    // 'PendienteEn procesoResuelto' (sin separador)
lista.join(' | '); // 'Pendiente | En proceso | Resuelto'

// .includes(valor): ¿el array contiene ese valor? → true o false.
const permitidos = ['jpeg', 'png', 'jpg'];
permitidos.includes('pdf'); // false
permitidos.includes('png'); // true

// .length: cuántos elementos tiene el array.
console.log(incidencias.length); // 3

// .push(elemento): agrega al final. MODIFICA el original.
incidencias.push({ id: 4, titulo: 'Semáforo', estado: 'pendiente' });

// .pop(): elimina el último. MODIFICA el original.
// .shift(): elimina el primero. MODIFICA el original.
// .splice(índice, cantidad): elimina/reemplaza elementos. MODIFICA el original.

// .slice(inicio, fin): extrae una porción SIN modificar el original.
const primerosDos = incidencias.slice(0, 2); // [incidencia0, incidencia1]

// .sort(fn): ordena. Por defecto ordena como strings.
incidencias.sort((a, b) => b.id - a.id); // ordena por id de mayor a menor

// .reduce(fn, valorInicial): reduce todo el array a un solo valor.
const total = [10, 20, 30].reduce((acumulado, n) => acumulado + n, 0); // 60
```

---

### Métodos de String

```javascript
const estado = 'En_Proceso';
const email  = '  admin@sistema.com  ';

// .toLowerCase(): convierte a minúsculas.
estado.toLowerCase(); // 'en_proceso'

// .toUpperCase(): convierte a mayúsculas.
estado.toUpperCase(); // 'EN_PROCESO'

// .trim(): elimina espacios al inicio y al final.
email.trim(); // 'admin@sistema.com'
// .trimStart() / .trimEnd(): solo uno de los lados.

// .includes('texto'): ¿contiene ese texto? → true o false.
'admin@sistema.com'.includes('@'); // true
'hola mundo'.includes('mundo');   // true

// .startsWith('texto'): ¿empieza con ese texto?
'Bearer token123'.startsWith('Bearer'); // true

// .endsWith('texto'): ¿termina con ese texto?
'foto.jpg'.endsWith('.jpg'); // true
'foto.png'.endsWith('.jpg'); // false

// .split('separador'): divide el string en un array.
'admin,tecnico,normal'.split(','); // ['admin', 'tecnico', 'normal']
'admin@sistema.com'.split('@');    // ['admin', 'sistema.com']

// .replace('buscar', 'reemplazar'): reemplaza la PRIMERA ocurrencia.
'hola hola'.replace('hola', 'adios'); // 'adios hola'

// .replaceAll('buscar', 'reemplazar'): reemplaza TODAS las ocurrencias.
'hola hola'.replaceAll('hola', 'adios'); // 'adios adios'

// .slice(inicio, fin): extrae una porción del string.
'incidencia_pendiente'.slice(0, 10); // 'incidencia'
'admin@sistema.com'.slice(-3);       // 'com' (desde el final)

// .length: cuántos caracteres tiene.
'hola'.length; // 4

// parseInt() y parseFloat(): convertir string a número.
parseInt('42');        // 42
parseInt('42.9');      // 42 (trunca decimales)
parseFloat('42.9');    // 42.9
parseInt('no número'); // NaN (Not a Number)

// isNaN(): verificar si un valor NO es un número.
isNaN('42');  // false (porque '42' se puede convertir a número)
isNaN('abc'); // true

// Number(): convierte cualquier valor a número.
Number('42');   // 42
Number('');     // 0
Number(null);   // 0
Number(true);   // 1
Number(false);  // 0
Number('abc');  // NaN

// String interpolation (template literal) — la forma más usada:
const titulo   = 'Bache';
const mensaje  = `Incidencia: ${titulo}`; // 'Incidencia: Bache'
const mayuscula = `${titulo.toUpperCase()}`; // 'BACHE'
```

---


## 35. JavaScript — Expresiones regulares (Regex)

### ¿Qué es una expresión regular?

Una expresión regular (regex o regexp) es un **patrón de texto** que describe
qué forma debe tener un string. Se usa para validar, buscar y reemplazar texto.

```javascript
// Una regex en JavaScript se escribe entre barras diagonales: /patrón/
// O con el constructor: new RegExp('patrón')

// Ejemplos básicos:
const esNumero  = /^\d+$/;          // solo dígitos (uno o más)
const esEmail   = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; // formato básico de email
const tieneLetra = /[a-zA-Z]/;      // contiene al menos una letra
```

---

### Caracteres especiales de regex

```javascript
// ANCLAJES — posición en el string:
// ^  → inicio del string
// $  → final del string
// /^hola$/  →  SOLO el string 'hola' (no 'hola mundo', no '¡hola!')

// CUANTIFICADORES — cuántas veces puede repetirse:
// *  → 0 o más veces
// +  → 1 o más veces (al menos uno)
// ?  → 0 o 1 vez (opcional)
// {n}   → exactamente n veces
// {n,}  → n o más veces
// {n,m} → entre n y m veces

// CLASES DE CARACTERES:
// \d → cualquier dígito (0-9)
// \D → cualquier NO dígito
// \w → letra, dígito o guión bajo [a-zA-Z0-9_]
// \W → cualquier NO \w
// \s → espacio, tabulación, salto de línea
// \S → cualquier NO espacio
// .  → cualquier carácter EXCEPTO salto de línea
// [abc]  → 'a' o 'b' o 'c'
// [a-z]  → cualquier letra minúscula
// [^abc] → cualquier carácter EXCEPTO 'a', 'b', 'c'
// [^\s@] → cualquier carácter que NO sea espacio ni @

// GRUPOS Y ALTERNATIVAS:
// (abc)  → grupo de captura
// (a|b)  → 'a' o 'b'

// FLAGS (modificadores al final):
// /patrón/g → global (buscar todas las coincidencias, no solo la primera)
// /patrón/i → case-insensitive (no distingue mayúsculas)
// /patrón/m → multiline (^ y $ aplican a cada línea)
// /patrón/gi → global + insensitive

// Ejemplos usados en el proyecto:
const emailRegex    = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
// Explicación:
// ^         → inicio
// [^\s@]+   → uno o más caracteres que no sean espacio ni @
// @         → el símbolo @ literal
// [^\s@]+   → uno o más caracteres que no sean espacio ni @
// \.        → punto literal (\ escapa el . para que no sea "cualquier carácter")
// [^\s@]+   → uno o más caracteres que no sean espacio ni @
// $         → final

const passwordRegex = /^(?=.*[A-Z])(?=.*\d).{6,}$/;
// Explicación:
// ^                → inicio
// (?=.*[A-Z])      → lookahead: debe existir al menos una mayúscula en algún lugar
// (?=.*\d)         → lookahead: debe existir al menos un dígito en algún lugar
// .{6,}            → al menos 6 caracteres de cualquier tipo
// $                → final
```

---

### Métodos de Regex

```javascript
// .test(string): ¿el string coincide con el patrón? → true o false
// El más usado para validaciones.
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
emailRegex.test('admin@sistema.com'); // true
emailRegex.test('no-es-email');       // false

// En el proyecto para validar formularios:
function validarFormulario() {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        mostrarError('Formato de email inválido.');
        return false;
    }

    const passRegex = /^(?=.*[A-Z])(?=.*\d).{6,}$/;
    if (!passRegex.test(password)) {
        mostrarError('La contraseña debe tener al menos 6 caracteres, una mayúscula y un número.');
        return false;
    }
    return true;
}

// .match(regex): busca coincidencias → devuelve array o null.
const texto = 'El bache está en la Av. 9 de Octubre y la Calle 10';
const numeros = texto.match(/\d+/g); // con flag /g busca todos
// ['9', '10']

// .replace(regex, reemplazo): reemplaza las coincidencias.
const limpio = 'Hola   Mundo'.replace(/\s+/g, ' '); // 'Hola Mundo'
// \s+ → uno o más espacios; /g → todos los grupos de espacios

// .split(regex): divide usando el patrón como separador.
'a1b2c3'.split(/\d/); // ['a', 'b', 'c', '']

// .search(regex): devuelve el índice de la primera coincidencia, o -1.
'admin@sistema.com'.search(/@/); // 5 (la @ está en el índice 5)
'sin-arroba'.search(/@/);        // -1 (no encontrado)
```

---


## 36. JavaScript — async/await y el ciclo de una petición

### ¿Qué es una Promise?

Una Promise (promesa) representa una operación que tomará tiempo.
En vez de bloquear el código, puedes continuar y "esperar" el resultado después.

```javascript
// Estados de una Promise:
// - pending:  esperando el resultado (la operación aún no terminó)
// - fulfilled: la operación terminó con éxito (tiene un valor)
// - rejected:  la operación falló (tiene un error)

// Sin async/await (con .then() y .catch()):
fetch('/api/incidencias')
    .then(respuesta => respuesta.json())  // cuando termina → convertir a JSON
    .then(datos => { mostrarDatos(datos); }) // cuando termina → mostrar
    .catch(error => { console.error(error); }); // si falla → capturar error

// Con async/await (la forma moderna y más legible):
async function cargarIncidencias() {
    try {
        const respuesta = await fetch('/api/incidencias'); // espera la respuesta HTTP
        const datos     = await respuesta.json();          // espera parsear el JSON
        mostrarDatos(datos);
    } catch (error) {
        console.error('Error:', error);
    }
}
```

---

### La función `apiFetch` del proyecto

```javascript
// api.js define apiFetch() que simplifica todas las peticiones al backend.
// Maneja automáticamente: token, Content-Type, errores HTTP, JSON parsing.

async function apiFetch(endpoint, options = {}) {
    // Construir cabeceras con el token de autenticación
    const cabeceras = {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
    };

    // Si el body NO es FormData, agregar Content-Type JSON
    if (!(options.body instanceof FormData)) {
        cabeceras['Content-Type'] = 'application/json';
    }

    // Hacer la petición
    const respuesta = await fetch(`/api${endpoint}`, {
        ...options,
        headers: { ...cabeceras, ...(options.headers || {}) },
    });

    // Si el servidor respondió con error (4xx, 5xx):
    if (!respuesta.ok) {
        const error = await respuesta.json().catch(() => ({}));
        throw new Error(error.mensaje || `Error ${respuesta.status}`);
    }

    // Si no hay contenido (respuesta 204 No Content):
    if (respuesta.status === 204) return null;

    return respuesta.json(); // parsear y devolver el JSON
}

// Cómo se usa en el resto del JS:
const datos = await apiFetch('/incidencias');           // GET
const nueva = await apiFetch('/incidencias', {          // POST
    method: 'POST',
    body: JSON.stringify({ titulo: 'Bache', ... }),
});
await apiFetch(`/incidencias/${id}`, { method: 'DELETE' }); // DELETE
```

---

### try/catch — manejo de errores

```javascript
// try: intenta ejecutar el código.
// catch: si algo lanza un error (throw), lo captura aquí.
// finally: siempre se ejecuta, haya error o no.

async function guardarIncidencia() {
    try {
        const resultado = await apiFetch('/incidencias', {
            method: 'POST',
            body: JSON.stringify(datos),
        });
        // Si llega aquí = éxito
        mostrarMensaje('Incidencia creada correctamente');

    } catch (error) {
        // error.message contiene el mensaje del throw en apiFetch
        mostrarError(`Error: ${error.message}`);

    } finally {
        // Siempre se ejecuta (con o sin error)
        botonGuardar.disabled = false; // re-habilitar el botón
    }
}
```

---

### setInterval y setTimeout

```javascript
// setTimeout(función, milisegundos): ejecuta la función UNA VEZ después del tiempo dado.
setTimeout(() => {
    ocultarAlerta();
}, 3000); // 3000ms = 3 segundos

// setInterval(función, milisegundos): ejecuta la función REPETIDAMENTE cada N milisegundos.
// Se usa para actualizar notificaciones automáticamente cada minuto.
const intervalo = setInterval(() => {
    cargarNotificaciones();
}, 60000); // 60000ms = 1 minuto

// clearInterval(intervalo): detener el interval (para no tener memory leaks).
clearInterval(intervalo);

// En el proyecto, las notificaciones se cargan cada 60 segundos automáticamente:
document.addEventListener('DOMContentLoaded', () => {
    if (!localStorage.getItem('token')) return;
    cargarNotificaciones();           // primera carga inmediata
    setInterval(cargarNotificaciones, 60000); // luego cada minuto
});
```

---

### JSON.parse y JSON.stringify

```javascript
// JSON.stringify(objeto): convierte objeto JavaScript → string JSON.
// Necesario al enviar datos al backend con fetch.
const datos = { titulo: 'Bache', estado: 'pendiente', ciudad_id: 5 };
const jsonString = JSON.stringify(datos);
// '{"titulo":"Bache","estado":"pendiente","ciudad_id":5}'

// Con formato legible (para debugging):
console.log(JSON.stringify(datos, null, 2));
// {
//   "titulo": "Bache",
//   "estado": "pendiente",
//   "ciudad_id": 5
// }

// JSON.parse(string): convierte string JSON → objeto JavaScript.
// Necesario al leer de localStorage (que solo guarda strings).
const usuarioGuardado = '{"id":1,"name":"José","role":{"nombre":"admin"}}';
const usuario = JSON.parse(usuarioGuardado);
usuario.name;       // 'José'
usuario.role.nombre; // 'admin'

// Leer el usuario guardado al hacer login:
const usuario = JSON.parse(localStorage.getItem('usuario') || '{}');
// Si no hay usuario en localStorage → {} (objeto vacío, evita error de parse)
```

---



---

# BLOQUE 4 — JavaScript: APIs del navegador y patrones del proyecto

## 43. JavaScript — fetch, FormData, localStorage, URLSearchParams, Date, Math, Object

### fetch() — peticiones HTTP nativas del navegador

```javascript
// fetch(url, opciones): envía una petición HTTP y devuelve una Promise.
// Es el método nativo del navegador (no necesita importarse).

// GET (sin body):
const respuesta = await fetch('https://api.ejemplo.com/datos');
// respuesta es un objeto Response (no los datos todavía)

// Propiedades del objeto Response:
respuesta.ok;      // true si el status es 200-299, false si es 4xx o 5xx
respuesta.status;  // número del código HTTP (200, 401, 422, 500, etc.)
respuesta.statusText; // texto del código ('OK', 'Not Found', etc.)
respuesta.headers; // objeto Headers con las cabeceras de la respuesta

// Métodos para leer el cuerpo de la respuesta (solo se pueden llamar UNA vez):
const datos    = await respuesta.json();  // parsea el body como JSON → objeto JS
const texto    = await respuesta.text();  // body como string de texto
const binario  = await respuesta.blob();  // body como blob (archivos binarios)

// POST con JSON:
const resp = await fetch('/api/incidencias', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',  // decirle al servidor qué tipo de dato enviamos
        'Accept': 'application/json',         // decirle que esperamos JSON de vuelta
        'Authorization': 'Bearer TOKEN123',   // token de autenticación
    },
    body: JSON.stringify({ titulo: 'Bache', estado: 'pendiente' }),
    // body siempre debe ser string (o FormData, Blob, etc.)
});

// POST con FormData (para subir archivos):
const form = new FormData();
form.append('titulo', 'Bache');
form.append('foto', archivoInput.files[0]);

const resp2 = await fetch('/api/incidencias', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer TOKEN123',
        // NO se pone Content-Type: el navegador lo agrega automáticamente con el boundary
    },
    body: form,
});

// PUT (actualizar):
await fetch(`/api/incidencias/${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer TOKEN' },
    body: JSON.stringify({ estado_actual: 'resuelto' }),
});

// DELETE:
await fetch(`/api/incidencias/${id}`, {
    method: 'DELETE',
    headers: { 'Authorization': 'Bearer TOKEN' },
});

// Manejo de errores con fetch:
// IMPORTANTE: fetch NO lanza error si el server responde 404 o 500.
// Solo lanza error si hay un problema de red (sin conexión).
try {
    const resp = await fetch('/api/incidencias');
    if (!resp.ok) {  // ← debes verificar .ok manualmente
        const errorBody = await resp.json();
        throw new Error(errorBody.mensaje || `Error HTTP ${resp.status}`);
    }
    const datos = await resp.json();
} catch (error) {
    // Aquí llega si: no hay conexión O si hicimos throw manualmente
    console.error('Falló la petición:', error.message);
}
```

---

### FormData — enviar archivos y datos de formularios

```javascript
// FormData: clase para construir datos en formato multipart/form-data.
// Necesaria cuando se quieren enviar archivos junto con otros campos.
// NO se puede usar JSON.stringify con FormData.

// Crear FormData vacío y agregar campos:
const formData = new FormData();
formData.append('titulo', 'Bache en la Avenida');
formData.append('descripcion', 'Un bache grande');
formData.append('ciudad_id', '5');         // los números se envían como string
formData.append('foto', archivoInput.files[0]); // el archivo real

// Crear FormData desde un formulario HTML (agrega todos los campos automáticamente):
const formulario = document.getElementById('mi-formulario');
const formData2  = new FormData(formulario);
// Si el formulario tiene inputs con name="titulo", name="foto", etc.,
// FormData los captura todos automáticamente.

// Agregar más campos al FormData del formulario:
formData2.append('estado_actual', 'pendiente');

// Leer un valor de FormData:
formData.get('titulo');  // 'Bache en la Avenida'

// Verificar si un campo existe:
formData.has('foto');    // true o false

// Eliminar un campo:
formData.delete('descripcion');

// Iterar sobre todos los campos:
for (const [clave, valor] of formData.entries()) {
    console.log(clave, valor);
}

// instanceof FormData: verificar si una variable es FormData.
formData instanceof FormData; // true
'texto'   instanceof FormData; // false
// Se usa en apiFetch() para decidir si agregar Content-Type: application/json
```

---

### localStorage — guardar datos en el navegador

```javascript
// localStorage: almacenamiento persistente en el navegador.
// Los datos sobreviven al cerrar el navegador (a diferencia de sessionStorage).
// Solo almacena strings. Objetos y arrays deben convertirse con JSON.stringify/parse.
// Límite: ~5-10 MB según el navegador.
// Es por origen (dominio): no se comparte entre dominios distintos.

// GUARDAR:
localStorage.setItem('clave', 'valor');
localStorage.setItem('token', 'eyJhbGci...');
localStorage.setItem('usuario', JSON.stringify({ id: 1, name: 'José', role: { nombre: 'admin' } }));
// JSON.stringify convierte el objeto a string para que localStorage lo pueda guardar.

// LEER:
const token   = localStorage.getItem('token');          // 'eyJhbGci...'
const userStr = localStorage.getItem('usuario');        // '{"id":1,"name":"José",...}'
const usuario = JSON.parse(localStorage.getItem('usuario') || '{}');
// || '{}': si no hay usuario guardado, parsea {} en lugar de null (evita error)

// ELIMINAR un item:
localStorage.removeItem('token');
localStorage.removeItem('usuario');

// ELIMINAR TODO:
localStorage.clear(); // borra todo el localStorage del dominio

// VERIFICAR si existe:
if (localStorage.getItem('token')) {
    // hay token guardado
} else {
    window.location.href = 'login.html'; // redirigir si no hay sesión
}

// sessionStorage: igual que localStorage pero se borra al cerrar la pestaña.
sessionStorage.setItem('temporal', 'valor');
sessionStorage.getItem('temporal');
sessionStorage.removeItem('temporal');
```

---

### URLSearchParams — manejar parámetros de URL

```javascript
// URLSearchParams: clase para construir y leer parámetros de URL (?key=value&key2=value2).

// CONSTRUIR parámetros (para enviar al backend):
const params = new URLSearchParams();
params.append('estado',   'pendiente');  // agrega un parámetro
params.append('prioridad','alta');
params.append('busqueda', 'bache grande');

params.toString();
// 'estado=pendiente&prioridad=alta&busqueda=bache+grande'
// (los espacios se codifican como +, los caracteres especiales como %XX)

// Construir URL con los parámetros:
const url = `/incidencias?${params.toString()}`;
// '/incidencias?estado=pendiente&prioridad=alta&busqueda=bache+grande'

// Si no hay parámetros, no agregar el ?:
const queryString = params.toString();
const urlFinal    = `/incidencias${queryString ? '?' + queryString : ''}`;

// set() vs append():
params.set('estado', 'resuelto');    // reemplaza si ya existe, agrega si no
params.append('estado', 'resuelto'); // siempre agrega (puede haber duplicados)

// LEER parámetros de la URL actual:
// Si la URL es: /detalle.html?id=7&desde=incidencias
const urlParams = new URLSearchParams(window.location.search);
// window.location.search = '?id=7&desde=incidencias'

urlParams.get('id');     // '7' (siempre es string, incluso si el valor es número)
urlParams.get('desde');  // 'incidencias'
urlParams.get('noExiste'); // null (no undefined)

// Convertir a número:
const id = parseInt(urlParams.get('id')); // 7 (número)

// Verificar si existe un parámetro:
urlParams.has('id');    // true
urlParams.has('otro');  // false

// Iterar:
for (const [clave, valor] of urlParams.entries()) {
    console.log(clave, valor);
}
```

---

### Date — fechas y horas

```javascript
// new Date(): crea un objeto con la fecha y hora actual.
const ahora = new Date();
console.log(ahora); // Wed May 18 2026 14:30:00 GMT-0500

// new Date(string): parsea una fecha desde string.
const fecha = new Date('2026-05-01T10:00:00');
const fecha2 = new Date('2026-05-01'); // solo fecha (hora = medianoche UTC)

// new Date(timestamp): desde timestamp Unix en milisegundos.
const fecha3 = new Date(1714568400000);

// Métodos para mostrar fechas al usuario:
const inc = { created_at: '2026-05-01T14:30:00.000000Z' };

// .toLocaleDateString('locale', opciones): solo la fecha, formato local.
new Date(inc.created_at).toLocaleDateString('es-EC');
// '01/05/2026' (día/mes/año en Ecuador)

new Date(inc.created_at).toLocaleDateString('es-EC', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
}); // '01 de mayo de 2026'

// .toLocaleString('locale'): fecha y hora.
new Date(inc.created_at).toLocaleString('es-EC');
// '01/05/2026, 09:30:00' (ajustado a zona horaria local)

// .toISOString(): formato ISO 8601 (usado para enviar al backend).
new Date().toISOString(); // '2026-05-18T19:30:00.000Z'

// Obtener partes de la fecha:
const d = new Date();
d.getFullYear();  // 2026
d.getMonth();     // 4 (⚠ los meses van de 0 a 11: enero=0, mayo=4)
d.getMonth() + 1; // 5 (así se obtiene el mes "real")
d.getDate();      // 18 (día del mes)
d.getDay();       // 1 (día de la semana: 0=domingo, 1=lunes, ..., 6=sábado)
d.getHours();     // 14 (hora, 0-23)
d.getMinutes();   // 30
d.getSeconds();   // 0
d.getTime();      // timestamp en milisegundos desde 1970

// Comparar fechas:
const fecha1 = new Date('2026-05-01');
const fecha2b = new Date('2026-06-01');
fecha1 < fecha2b;  // true
fecha1 > fecha2b;  // false
fecha1.getTime() === fecha2b.getTime(); // false (son distintas)
```

---

### Math — funciones matemáticas

```javascript
// Math es un objeto con constantes y métodos matemáticos. No se instancia.

// Redondear:
Math.round(4.5);   // 5 (redondea al más cercano)
Math.round(4.4);   // 4
Math.floor(4.9);   // 4 (siempre hacia abajo)
Math.ceil(4.1);    // 5 (siempre hacia arriba)
Math.trunc(4.9);   // 4 (elimina la parte decimal, sin redondear)
Math.trunc(-4.9);  // -4 (a diferencia de floor que daría -5)

// Valor absoluto y potencias:
Math.abs(-5);      // 5  (valor absoluto)
Math.pow(2, 10);   // 1024 (2 elevado a 10)
Math.sqrt(16);     // 4  (raíz cuadrada)

// Mínimo y máximo de un conjunto de valores:
Math.min(3, 1, 4, 1, 5); // 1
Math.max(3, 1, 4, 1, 5); // 5
Math.min(...[3, 1, 4]);   // con spread operator para pasar un array

// Número aleatorio:
Math.random();          // número entre 0 (incluido) y 1 (excluido): ej 0.7345
Math.random() * 100;    // entre 0 y 100
Math.floor(Math.random() * 100); // entero entre 0 y 99

// En el seeder JS para coordenadas con desplazamiento aleatorio:
const latBase = -2.2289;
const lat = latBase + (Math.random() - 0.5) / 100;
// Math.random() - 0.5 → número entre -0.5 y 0.5
// / 100 → entre -0.005 y 0.005 grados (~500 metros de variación)

// Constantes:
Math.PI;  // 3.141592653589793
Math.E;   // 2.718281828459045
```

---

### Object — métodos para trabajar con objetos

```javascript
const incidencia = {
    id: 1,
    titulo: 'Bache',
    estado: 'pendiente',
    prioridad: 'alta',
};

// Object.keys(obj): array con los nombres de las propiedades.
Object.keys(incidencia);
// ['id', 'titulo', 'estado', 'prioridad']

// Object.values(obj): array con los valores de las propiedades.
Object.values(incidencia);
// [1, 'Bache', 'pendiente', 'alta']

// Object.entries(obj): array de pares [clave, valor].
Object.entries(incidencia);
// [['id', 1], ['titulo', 'Bache'], ['estado', 'pendiente'], ['prioridad', 'alta']]

// Útil para iterar sobre un objeto:
Object.entries(incidencia).forEach(([clave, valor]) => {
    console.log(`${clave}: ${valor}`);
});

// Object.assign(destino, fuente): copia propiedades de fuente a destino.
// Modifica el destino y lo retorna.
const base   = { estado: 'pendiente', prioridad: 'media' };
const extras = { titulo: 'Bache', ciudad_id: 5 };
const merged = Object.assign({}, base, extras);
// { estado: 'pendiente', prioridad: 'media', titulo: 'Bache', ciudad_id: 5 }

// Alternativa moderna con spread (preferida):
const merged2 = { ...base, ...extras };

// Object.freeze(obj): congela un objeto (nadie puede modificarlo).
const CONFIG = Object.freeze({ API_URL: '/api', VERSION: '1.0' });
// CONFIG.API_URL = 'otro'; // silenciosamente ignorado (o TypeError en strict mode)

// typeof: el tipo de una variable.
typeof 'texto';    // 'string'
typeof 42;         // 'number'
typeof true;       // 'boolean'
typeof undefined;  // 'undefined'
typeof null;       // 'object' ← ¡trampa clásica! null es primitivo, no objeto
typeof {};         // 'object'
typeof [];         // 'object' ← los arrays también son 'object'
typeof function(){}; // 'function'

// Para verificar si es un array (typeof no sirve):
Array.isArray([]);   // true
Array.isArray({});   // false

// null vs undefined:
// null: ausencia INTENCIONAL de un valor (alguien lo asignó explícitamente)
// undefined: variable declarada pero sin valor asignado aún
let x;
console.log(x); // undefined (declarada pero sin valor)
let y = null;
console.log(y); // null (explícitamente sin valor)

// Verificar null o undefined:
if (valor === null) { ... }      // solo null
if (valor === undefined) { ... } // solo undefined
if (valor == null) { ... }       // null O undefined (== sin estricto)
if (valor != null) { ... }       // NO es null NI undefined
```

---

### window — el objeto global del navegador

```javascript
// window es el objeto global en el navegador.
// Todas las variables globales son propiedades de window.
// Muchas funciones que parece que no tienen objeto son en realidad window.método().

// Diálogos:
window.alert('Mensaje al usuario');  // muestra un popup con OK
// o simplemente: alert('Mensaje');

window.confirm('¿Estás seguro?');    // popup con OK/Cancelar → true/false
const confirmado = confirm('¿Eliminar esta incidencia?');
if (confirmado) { eliminar(id); }

window.prompt('Ingresa tu nombre'); // popup con input → string o null si cancela

// Navegación:
window.location.href = 'login.html'; // navegar a otra página
window.location.reload();            // recargar la página actual
window.history.back();               // ir a la página anterior
window.history.forward();            // ir a la siguiente página

// Tamaño de la ventana:
window.innerWidth;   // ancho del viewport en píxeles
window.innerHeight;  // alto del viewport en píxeles

// Scroll:
window.scrollTo(0, 0);        // scroll al inicio de la página
window.scrollTo({ top: 500, behavior: 'smooth' }); // scroll suave

// Consola (para debugging):
console.log('mensaje normal');   // mensaje informativo
console.error('error grave');    // mensaje de error (rojo en DevTools)
console.warn('advertencia');     // mensaje de advertencia (amarillo)
console.table([{ id: 1 }, { id: 2 }]); // muestra array como tabla
console.group('Grupo');          // agrupa mensajes siguientes
console.groupEnd();              // cierra el grupo
```

---


## 17. FormData vs JSON — y cómo apiFetch maneja los dos

### El problema original

`apiFetch()` siempre ponía `Content-Type: application/json`. Eso funciona
para la mayoría de peticiones, pero rompe el envío de archivos:

```
Petición normal:    Content-Type: application/json       ✅
Petición con foto:  Content-Type: application/json       ❌ (el servidor no puede leer el archivo)
Petición con foto:  Content-Type: multipart/form-data    ✅ (lo pone el navegador automáticamente)
```

El truco: cuando el body es `FormData`, el navegador necesita poner el Content-Type
él mismo porque incluye un "boundary" único que solo él conoce:
```
Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryXYZ123
```
Si nosotros lo forzamos a `application/json`, el servidor recibe datos binarios
pero espera JSON, y todo falla.

### La solución en api.js

```javascript
// Detectar si el body es FormData
if (!(options.body instanceof FormData)) {
    // Caso normal (JSON): nosotros ponemos el Content-Type
    cabeceras['Content-Type'] = 'application/json';
}
// Caso archivo (FormData): NO ponemos Content-Type
// → el navegador lo pone solo con el boundary correcto
```

`instanceof` comprueba si un objeto fue creado con una clase específica:
```javascript
const formData = new FormData();
formData instanceof FormData  // → true
'hola' instanceof FormData    // → false
null instanceof FormData      // → false
```

### Ahora incidencias.js puede usar apiFetch normalmente

Antes (fetch manual):
```javascript
const token = localStorage.getItem('token');
const peticion = await fetch('/api/incidencias', {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
    body: formData
});
const respuesta = await peticion.json();
if (peticion.ok) { ... } else { ... }
```

Ahora (apiFetch):
```javascript
await apiFetch('/incidencias', { method: 'POST', body: formData });
// Si llega aquí = éxito (apiFetch lanza excepción si hay error)
```

Mucho más corto y usa el mismo patrón que el resto del proyecto.

---


## 16. Validaciones del lado del cliente — por qué y cómo

### El problema con solo validar en el backend

El backend valida los datos que llegan. Pero si el usuario comete un error
(no seleccionó ciudad, foto muy grande), el formulario viaja al servidor,
el servidor responde con un error 422, y el usuario ve... un `alert()` con
texto JSON crudo. No es una buena experiencia.

**Solución:** validar en el frontend primero, mostrar el error de forma visual,
y solo enviar al backend cuando todo está correcto.

### Estructura: un div de alertas dentro del formulario

En el HTML, se agrega un div vacío que normalmente está oculto:
```html
<div id="alerta-formulario" class="d-none alert mb-3"></div>
```

Cuando hay un error, el JS le pone la clase `alert-danger` y el mensaje:
```javascript
function mostrarErrorFormulario(mensaje) {
    const alerta = document.getElementById('alerta-formulario');
    alerta.textContent = mensaje;
    alerta.className   = 'alert alert-danger mb-3'; // rojo Bootstrap
}
```

Cuando todo está bien o el modal se cierra:
```javascript
function ocultarErrorFormulario() {
    alerta.className   = 'd-none alert mb-3'; // vuelve a estar oculto
    alerta.textContent = '';
}
```

### La función de validación devuelve true/false

```javascript
function validarFormulario() {
    if (!titulo || titulo.length < 5) {
        mostrarErrorFormulario('El título debe tener al menos 5 caracteres.');
        return false; // detener aquí, no seguir revisando
    }
    // ... más validaciones ...
    return true; // todo bien
}

// En guardarIncidencia():
if (!validarFormulario()) return; // si falló, no continuar
```

El patrón de "salir al primer error" (early return) es más fácil de leer
que un if gigante con todas las condiciones anidadas.

### Validar coordenadas con un rango geográfico

Ecuador ocupa aproximadamente:
- Latitud: entre -5.5 (sur) y 1.8 (norte)
- Longitud: entre -82.0 (oeste, Galápagos) y -74.5 (este)

```javascript
const lat = parseFloat(document.getElementById('latitud').value);
const lng = parseFloat(document.getElementById('longitud').value);

if (lat < -5.5 || lat > 1.8 || lng < -82.0 || lng > -74.5) {
    mostrarErrorFormulario('Las coordenadas están fuera de Ecuador.');
    return false;
}
```

Si el mapa está centrado en Ecuador y el usuario no sale del país, esta
validación nunca fallará. Pero protege contra datos inválidos si alguien
intenta pasar coordenadas manualmente.

### Validar archivos antes de subirlos

El `<input type="file">` expone los archivos en `.files[0]`:
```javascript
const foto = document.getElementById('foto').files[0];

// Verificar que se seleccionó algo
if (!foto) {
    mostrarErrorFormulario('Selecciona una foto.');
    return false;
}

// Verificar el tipo de archivo (MIME type)
if (!['image/jpeg', 'image/png'].includes(foto.type)) {
    mostrarErrorFormulario('Solo JPG o PNG.');
    return false;
}

// Verificar el tamaño (en bytes): 2MB = 2 × 1024 × 1024
if (foto.size > 2 * 1024 * 1024) {
    mostrarErrorFormulario('La foto no puede pesar más de 2MB.');
    return false;
}
```

**Importante:** estas validaciones son en el cliente. Alguien técnico podría
saltárselas con DevTools. Por eso el backend TAMBIÉN valida. Son dos capas
de defensa: la del frontend para UX, la del backend para seguridad.

---


## 18. Compartir código entre páginas — archivo JS reutilizable

### El problema

`notificaciones.js` necesita correr en 3 páginas distintas (dashboard, incidencias, detalle).
Si pusiéramos el código de notificaciones dentro de cada uno de esos archivos JS, estaríamos
repitiendo el mismo código 3 veces. Cualquier arreglo habría que hacerlo en 3 lugares.

### La solución: archivo JS compartido

Creamos `notificaciones.js` como un archivo independiente que se auto-inicializa:

```javascript
// Al cargar la página, si hay token, empezar a mostrar notificaciones
document.addEventListener('DOMContentLoaded', () => {
    if (!localStorage.getItem('token')) return;
    cargarNotificaciones();
    setInterval(cargarNotificaciones, 60000);
});
```

Y lo incluimos en cada HTML que lo necesite, **después de api.js** (porque usa `apiFetch`):

```html
<script src="js/api.js"></script>
<script src="js/notificaciones.js"></script>  <!-- compartido -->
<script src="js/dashboard.js"></script>        <!-- específico de esta página -->
```

### Por qué marcarLeida debe ser global

La lista de notificaciones se construye con `innerHTML`, generando botones así:
```html
<button onclick="marcarLeida(5, this)">...</button>
```

El `onclick` inline busca la función `marcarLeida` en el ámbito global (`window`).
Si la función estuviera dentro de `DOMContentLoaded`, no sería global y el navegador daría error.

```javascript
// ✅ CORRECTO: declaración de función → es global automáticamente
async function marcarLeida(id, boton) { ... }

// ❌ NO sirve para onclick inline: solo existe dentro del DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    const marcarLeida = async (id, boton) => { ... };
});
```

### Optimistic update — actualizar la UI antes de esperar al backend

Cuando el usuario hace clic en una notificación, cambiamos su apariencia inmediatamente
(sin esperar que el servidor responda). Esto hace que la interfaz se sienta instantánea:

```javascript
// Cambiar apariencia al instante (el usuario ve el cambio de inmediato)
boton.classList.remove('bg-primary', 'bg-opacity-10');
boton.classList.add('text-muted');

// LUEGO avisar al backend (puede tardar 200-500ms, pero el usuario ya vio el cambio)
await apiFetch(`/notificaciones/${id}/leer`, { method: 'PUT' });
```

Si el backend fallara, la próxima recarga de `cargarNotificaciones()` devolvería
la notificación a su estado real. Para una funcionalidad tan simple, este trade-off vale la pena.

---


## 14. Filtros en el frontend — cómo se conectan al backend

### El flujo completo

El backend ya tenía los filtros listos desde la Fase 1:
```
GET /api/incidencias?estado=pendiente&prioridad=alta&busqueda=bache
```

El paso 2.3 conecta el formulario HTML con esa URL. Así:

```
Usuario elige "Pendiente" en el select
          ↓
Hace clic en "Filtrar" (o presiona Enter)
          ↓
incidencias.js lee los valores del formulario
          ↓
Construye la query string: "?estado=pendiente"
          ↓
Llama a apiFetch('/incidencias?estado=pendiente')
          ↓
El backend filtra y devuelve solo las incidencias pendientes
          ↓
La tabla se actualiza sin recargar la página
```

### URLSearchParams — construir la URL de filtros

`URLSearchParams` es una clase nativa de JavaScript para manejar parámetros de URL:

```javascript
const filtros = new URLSearchParams();

// Solo agrega el parámetro si tiene valor real
if (estado)    filtros.append('estado', estado);
if (prioridad) filtros.append('prioridad', prioridad);
if (busqueda)  filtros.append('busqueda', busqueda);

// .toString() devuelve la cadena lista para la URL
console.log(filtros.toString()); // "estado=pendiente&busqueda=bache"

// Construir la URL final
const url = `/incidencias${filtros.toString() ? '?' + filtros.toString() : ''}`;
// Si hay filtros: "/incidencias?estado=pendiente"
// Si no hay filtros: "/incidencias"  (no pone el ? solo)
```

### Por qué no usamos `<form action="...">`

En el HTML clásico, un `<form>` con `action` recarga la página al hacer submit.
Aquí usamos un botón `type="button"` (no `type="submit"`) para evitar eso.
El click del botón lo manejamos con JavaScript:

```javascript
document.getElementById('btn-filtrar').addEventListener('click', cargarIncidencias);
```

---


## 13. JavaScript del Frontend — Patrones usados en este proyecto

### Cómo leer parámetros de la URL

Cuando haces clic en "Ver" de una incidencia, la URL queda así:
`detalle.html?id=7`

El `?id=7` es un parámetro de consulta. Para leerlo en JS:

```javascript
const params       = new URLSearchParams(window.location.search);
const idIncidencia = params.get('id'); // devuelve "7" como texto
```

### Cómo mostrar datos en pantalla sin recargar

Una vez que tienes los datos del backend, los pegas en el HTML así:

```javascript
// HTML tiene: <h3 id="detalle-titulo">Cargando...</h3>
document.getElementById('detalle-titulo').textContent = inc.titulo;
// Ahora el <h3> muestra el titulo real de la incidencia
```

### Cómo construir HTML desde un array con .map()

Cuando el backend devuelve una lista, la conviertes en HTML con .map():

```javascript
contenedor.innerHTML = comentarios.map(c => `
    <div class="border p-2">
        <strong>${c.usuario.name}</strong>
        <p>${c.texto_comentario}</p>
    </div>
`).join(''); // .join('') une todos los strings en uno solo
```

### Cómo evitar que un formulario recargue la página

Por defecto, hacer submit recarga la página. Para evitarlo:

```javascript
form.addEventListener('submit', async (e) => {
    e.preventDefault(); // cancela la recarga de página
    // aquí va tu código
});
```

### Por qué api.js debe cargarse ANTES que detalle.js

detalle.js usa la función apiFetch() que está definida en api.js.
Si detalle.js carga primero, intenta usar apiFetch que aún no existe y falla.

```html
<!-- CORRECTO -->
<script src="js/api.js"></script>
<script src="js/detalle.js"></script>
```

---

---


## 24. El frontend completo — páginas y su flujo

### Mapa mental de páginas

```
login.html         → solo accesible sin token
register.html      → solo accesible sin token
      ↓ (login exitoso, token guardado en localStorage)
dashboard.html     → métricas generales (gráficas, contadores)
      ↓
incidencias.html   → lista de incidencias con filtros
  ├── Modal VER (incidencias.html)
  │     → muestra detalle completo sin navegar
  ├── Modal EDITAR (incidencias.html)
  │     → formulario de edición con campos según rol
  └── 3-dot dropdown → también permite Eliminar (admin)
      ↓
detalle.html?id=X  → página de solo lectura (acceso directo por URL)
editar.html?id=X   → página de edición completa (acceso directo por URL)
usuarios.html      → solo admin, gestión de cuentas
```

### ¿Por qué hay tanto modal Y también páginas separadas?

Los **modales** (Ver y Editar desde incidencias.html) son para la experiencia
fluida: el usuario no sale de la lista. Muy cómodo.

Las **páginas** (`detalle.html`, `editar.html`) siguen existiendo para:
- Acceso directo via URL (alguien te comparte el link `detalle.html?id=5`)
- Casos donde se navega desde otras páginas

### Qué guarda localStorage y por qué

```javascript
// Al hacer login, el backend devuelve esto y el frontend lo guarda:
localStorage.setItem('token', 'el-token-sanctum');
localStorage.setItem('usuario', JSON.stringify({
    id: 1,
    name: 'José Admin',
    email: 'admin@sistema.com',
    role: { id: 1, nombre: 'admin' }
}));

// Al cerrar sesión, se borran:
localStorage.removeItem('token');
localStorage.removeItem('usuario');
// Redirige a login.html
```

Cada página protegida verifica al cargar:
```javascript
const token = localStorage.getItem('token');
if (!token) {
    window.location.href = 'login.html'; // redirigir si no hay token
    return;
}
```

### El flujo de permisos en el modal Editar

```javascript
// 1. Obtener rol del usuario logueado
const esAdmin   = usuario.role.nombre === 'admin';
const esTecnico = usuario.role.nombre === 'tecnico';
const esDueno   = inc.usuario_id === usuario.id; // ¿reportó esta incidencia?

// 2. Si es técnico, verificar si está ASIGNADO a esta incidencia específica
let esTecnicoAsignado = false;
if (esTecnico) {
    const asig = await apiFetch(`/incidencias/${id}/asignaciones`);
    esTecnicoAsignado = asig.data.some(a => a.usuario_id === usuario.id);
}

// 3. Calcular permisos
const puedeEditarDatos     = esAdmin || (esDueno && enPendiente);
//    ↑ Admin edita siempre. Dueño solo si la incidencia sigue pendiente.

const puedeEditarEstado    = esAdmin || esTecnicoAsignado || (esDueno && enPendiente);
//    ↑ Admin, técnico asignado, o dueño si pendiente.

const puedeEditarPrioridad = esAdmin || esTecnicoAsignado;
//    ↑ Solo admin o técnico asignado. Los ciudadanos no tocan la prioridad.

const puedeAsignar         = esAdmin;
//    ↑ Solo el admin puede asignar/quitar técnicos.
```

---


## 25. Modales Ver y Editar — cómo funcionan técnicamente

### El problema del mapa en modales

Leaflet no puede calcular el tamaño de un contenedor que tiene `display:none`
(que es lo que Bootstrap hace a los modales cerrados). Si intentas inicializar
el mapa con el modal cerrado, el mapa queda con tamaño 0.

**Solución: evento `shown.bs.modal`**

Bootstrap dispara este evento cuando el modal termina de abrirse (después
de la animación). Es el momento correcto para inicializar el mapa.

```javascript
// En incidencias.js, dentro de DOMContentLoaded:
document.getElementById('verIncidenciaModal').addEventListener('shown.bs.modal', () => {
    if (verPendingMap) {
        // Los datos llegaron ANTES de que el modal terminara de abrirse
        inicializarMapaVer(verPendingMap.lat, verPendingMap.lng);
        verPendingMap = null;
    } else if (mapaVer) {
        // El modal ya fue abierto antes, solo recalcular tamaño
        mapaVer.invalidateSize();
    }
});
```

### La variable `verPendingMap`

El modal se abre (con la animación de 300ms) y SIMULTÁNEAMENTE se hace la
petición a la API. En localhost, la API responde en ~5-50ms (antes de que
termine la animación). En ese caso:

```
T=0ms:   modal.show()     → empieza animación
T=20ms:  API devuelve datos con lat/lng
            → modal.classList aún NO tiene 'show'
            → guardamos en verPendingMap = { lat, lng }
T=300ms: 'shown.bs.modal' se dispara
            → verPendingMap tiene valor → inicializamos el mapa
```

Sin `verPendingMap`, si los datos llegaran antes que el evento, el mapa
nunca se inicializaría.

### `editarCatalogoCargado` — evitar cargar el catálogo dos veces

El modal Editar necesita la lista de provincias y categorías para llenar
los selects. Estas listas no cambian (no se añaden provincias en medio de
una sesión). No tiene sentido pedir esos datos al servidor cada vez que
el usuario abre el modal Editar.

```javascript
let editarCatalogoCargado = false; // variable global

// Dentro de abrirModalEditar():
if (!editarCatalogoCargado) {
    editarCatalogoCargado = true;   // marcarlo ANTES del await (evitar race condition)
    await cargarCatalogosEditar();  // solo la PRIMERA vez se llama al servidor
}
rellenarDatosEditar(inc); // siempre se rellena con los datos de la incidencia actual
```

La segunda vez que el usuario abre el modal Editar, las provincias y
categorías ya están en los `<select>`. Solo se rellena con los nuevos valores.

### El botón único "Guardar cambios"

En lugar de 3 botones (uno para datos, otro para estado, otro para prioridad),
hay un solo botón que reúne todo:

```javascript
document.getElementById('editar-btn-guardar').onclick = async () => {
    const cuerpo = {};

    // Solo incluye campos según los permisos del usuario
    if (puedeEditarDatos) {
        cuerpo.titulo      = document.getElementById('editar-titulo').value;
        cuerpo.descripcion = document.getElementById('editar-descripcion').value;
        cuerpo.ciudad_id   = document.getElementById('editar-ciudad').value;
        cuerpo.subtipo_id  = document.getElementById('editar-subtipo').value;
    }
    if (puedeEditarEstado) {
        cuerpo.estado_actual = document.getElementById('editar-select-estado').value;
    }
    if (puedeEditarPrioridad) {
        cuerpo.prioridad = ... // del select correspondiente
    }

    // Una sola llamada PUT al backend con todo
    await apiFetch(`/incidencias/${id}`, { method: 'PUT', body: JSON.stringify(cuerpo) });
};
```

El backend acepta todos esos campos en una sola llamada gracias a:
```php
$incidencia->update($request->only([
    'titulo', 'descripcion', 'estado_actual', 'prioridad', ...
]));
// ->only([...]) ignora campos que no estén en la lista (seguridad)
```

---



---

# BLOQUE 5 — JavaScript: librerías (Leaflet y Chart.js)

## 19. Mapas con Leaflet.js — Cómo funciona el mapa interactivo

### ¿Qué es Leaflet.js?

Leaflet es una biblioteca JavaScript gratuita para poner mapas en páginas web.
Usa imágenes de **OpenStreetMap** (el Google Maps del mundo libre, sin API key).

### Cómo se incluye en el proyecto

Se carga desde un CDN (servidor externo), como Bootstrap:

```html
<!-- En el <head> del HTML -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Antes de tu script JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
```

Cuando Leaflet carga, crea la variable global `L` que usamos en nuestro JS.

### Crear un mapa básico

```javascript
// Crear el mapa en el elemento con id="mapa-detalle"
// setView([latitud, longitud], nivelDeZoom)
// Zoom 15 = vista de cuadra. Zoom 1 = mundo completo. Zoom 20 = edificio
const mapa = L.map('mapa-detalle').setView([-2.2289, -80.8994], 15);

// Cargar las imágenes del mapa desde OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(mapa);
```

### Poner un marcador (pin) en el mapa

```javascript
// L.marker([lat, lng]) crea un pin en esas coordenadas
const marcador = L.marker([-2.2289, -80.8994]).addTo(mapa);

// bindPopup = el globito que aparece cuando haces clic en el pin
// openPopup = abrirlo automáticamente al cargar
marcador.bindPopup('📍 Ubicación del problema').openPopup();
```

### Detectar clic en el mapa (mapa interactivo)

```javascript
// El evento 'click' de Leaflet te da las coordenadas donde se hizo clic
mapa.on('click', (evento) => {
    const lat = evento.latlng.lat; // latitud del punto clicado
    const lng = evento.latlng.lng; // longitud del punto clicado

    // Poner un pin en ese punto
    L.marker([lat, lng]).addTo(mapa);

    // Llenar campos ocultos del formulario
    document.getElementById('latitud').value  = lat;
    document.getElementById('longitud').value = lng;
});
```

### El problema del modal (display:none)

El mapa NECESITA que su contenedor sea visible para calcular el tamaño.
Si el contenedor tiene `display:none` (como un modal cerrado), el mapa queda roto.

**Solución:** esperar al evento `shown.bs.modal` de Bootstrap, que se dispara
justo DESPUÉS de que el modal ya es visible:

```javascript
document.getElementById('miModal').addEventListener('shown.bs.modal', () => {
    // Ahora el modal SÍ es visible → crear el mapa aquí
    const mapa = L.map('mapa-formulario').setView([...], 13);
});
```

Si ya existe el mapa y solo se volvió a abrir el modal:
```javascript
mapa.invalidateSize(); // fuerza a Leaflet a recalcular el tamaño del mapa
```

### Mover un pin existente vs crear uno nuevo

```javascript
// En vez de crear un nuevo marcador con cada clic (que acumula pines),
// reutilizamos el mismo y lo movemos:
if (marcador) {
    marcador.setLatLng([lat, lng]); // mover el pin existente
} else {
    marcador = L.marker([lat, lng]).addTo(mapa); // crear uno nuevo
}
```

### Qué usamos en este proyecto

| Página | Comportamiento del mapa |
|--------|------------------------|
| `detalle.html` | Pin fijo en la ubicación del problema (zoom/pan libre, no se puede mover el pin) |
| `incidencias.html` (modal) | Mapa donde el usuario hace clic para elegir la ubicación; GPS centra el mapa automáticamente |

---

---


## 44. JavaScript — Leaflet.js referencia completa

### El objeto global `L`

Cuando se carga `leaflet.js` en el HTML, crea el objeto global `L`.
Todos los métodos de Leaflet empiezan con `L.` (excepto los métodos de instancias).

```javascript
// Crear el mapa:
// L.map('id-del-div'): crea un mapa en el div con ese id.
// El div debe tener altura definida en CSS o el mapa será invisible.
const mapa = L.map('mapa-detalle', {
    center: [-2.2289, -80.8994], // [lat, lng] del centro inicial
    zoom: 15,                    // nivel de zoom inicial (1-20)
    zoomControl: true,           // mostrar botones +/- de zoom (default: true)
    dragging: true,              // permitir arrastrar el mapa (default: true)
    scrollWheelZoom: true,       // hacer zoom con la rueda del mouse
});

// Forma abreviada (la más usada en el proyecto):
const mapa = L.map('mapa-detalle').setView([-2.2289, -80.8994], 15);
// .setView([lat, lng], zoom): mueve el mapa a esa posición con ese zoom.

// Niveles de zoom de referencia:
// 1  → mundo entero
// 5  → continente
// 10 → ciudad
// 13 → barrio
// 15 → calles individuales
// 18 → edificios
// 20 → máximo detalle
```

---

### TileLayer — las imágenes del mapa

```javascript
// El mapa base son imágenes (tiles) cargadas desde un servidor de mapas.
// L.tileLayer(url, opciones): carga tiles desde esa URL.
// {z} = nivel de zoom, {x} y {y} = coordenadas del tile.
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    // attribution: texto de crédito que aparece en la esquina del mapa (obligatorio en OSM).
    maxZoom: 19, // zoom máximo permitido con este tileset
}).addTo(mapa);
// .addTo(mapa): agrega la capa al mapa (sin esto no se ve nada)
```

---

### Marcadores (pins)

```javascript
// L.marker([lat, lng]): crea un marcador en esas coordenadas.
const marcador = L.marker([-2.2289, -80.8994]);
marcador.addTo(mapa); // agrega al mapa

// Forma abreviada:
const marcador = L.marker([-2.2289, -80.8994]).addTo(mapa);

// Popup (globito con información al hacer clic):
marcador.bindPopup('<b>Bache en la Avenida</b><br>Prioridad: Alta');
// bindPopup(contenido): asocia un popup al marcador.
// El contenido puede ser HTML.

marcador.openPopup();  // abre el popup automáticamente
marcador.closePopup(); // cierra el popup

// Tooltip (texto que aparece al pasar el mouse, sin clic):
marcador.bindTooltip('Haz clic para ver detalles');
marcador.unbindTooltip();

// Mover un marcador existente (en lugar de crear uno nuevo):
marcador.setLatLng([-2.2300, -80.9000]);
// Más eficiente que borrar y recrear el marcador.

// Eliminar un marcador del mapa:
marcador.remove();
// O: mapa.removeLayer(marcador);

// Marcador con ícono personalizado:
const iconoRojo = L.icon({
    iconUrl: 'img/marcador-rojo.png', // imagen del ícono
    iconSize: [32, 32],               // [ancho, alto] en píxeles
    iconAnchor: [16, 32],             // punto de anclaje (donde toca el mapa)
    popupAnchor: [0, -32],            // dónde sale el popup relativo al ícono
});
const marcadorRojo = L.marker([lat, lng], { icon: iconoRojo }).addTo(mapa);
```

---

### Eventos del mapa

```javascript
// mapa.on('evento', función): escuchar eventos del mapa.

// Clic en el mapa:
mapa.on('click', (e) => {
    const lat = e.latlng.lat; // latitud donde se hizo clic
    const lng = e.latlng.lng; // longitud donde se hizo clic
    console.log(`Clic en: ${lat}, ${lng}`);

    // Poner o mover un marcador:
    if (marcador) {
        marcador.setLatLng([lat, lng]);
    } else {
        marcador = L.marker([lat, lng]).addTo(mapa);
    }

    // Llenar campos del formulario:
    document.getElementById('latitud').value  = lat.toFixed(6);
    document.getElementById('longitud').value = lng.toFixed(6);
});

// Zoom cambiado:
mapa.on('zoomend', () => {
    console.log('Zoom actual:', mapa.getZoom());
});

// Mapa movido:
mapa.on('moveend', () => {
    const centro = mapa.getCenter();
    console.log(`Centro: ${centro.lat}, ${centro.lng}`);
});
```

---

### Métodos del mapa

```javascript
// .setView([lat, lng], zoom): mover el mapa a una posición con zoom específico.
mapa.setView([-2.2289, -80.8994], 15);

// .flyTo([lat, lng], zoom): igual que setView pero con animación suave.
mapa.flyTo([-2.2289, -80.8994], 15);

// .getZoom(): obtener el nivel de zoom actual.
const zoomActual = mapa.getZoom();

// .getCenter(): obtener el centro actual del mapa.
const centro = mapa.getCenter();
console.log(centro.lat, centro.lng);

// .invalidateSize(): recalcular el tamaño del mapa.
// NECESARIO cuando el contenedor del mapa estaba oculto (modal cerrado) y luego se muestra.
mapa.invalidateSize();

// .fitBounds(bounds): ajustar el zoom para mostrar un área específica.
const bounds = L.latLngBounds([
    [-2.25, -81.0], // esquina suroeste
    [-2.20, -80.8], // esquina noreste
]);
mapa.fitBounds(bounds);

// .remove(): destruir el mapa completamente.
// Necesario para evitar el error "Map container is already initialized".
mapa.remove();
// Luego de remove() se puede crear un nuevo mapa en el mismo div.
```

---

### Círculos y polígonos

```javascript
// L.circle([lat, lng], opciones): círculo centrado en las coordenadas.
const area = L.circle([-2.2289, -80.8994], {
    color: 'red',           // color del borde
    fillColor: '#f03',      // color del relleno
    fillOpacity: 0.3,       // opacidad del relleno (0-1)
    radius: 500,            // radio en metros
}).addTo(mapa);

// L.polygon(puntos): polígono con vértices en esas coordenadas.
const zona = L.polygon([
    [-2.220, -80.890],
    [-2.225, -80.895],
    [-2.230, -80.885],
]).addTo(mapa);

// L.polyline(puntos): línea que conecta los puntos.
const ruta = L.polyline([
    [-2.220, -80.890],
    [-2.225, -80.895],
], { color: 'blue', weight: 3 }).addTo(mapa);
```

---


## 15. Gráficas con Chart.js

### ¿Qué es Chart.js?

Chart.js es una biblioteca JavaScript que dibuja gráficas dentro de un elemento `<canvas>` de HTML.
Se carga desde un CDN igual que Bootstrap y Leaflet:

```html
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
```

Cuando carga, crea la clase global `Chart` que usamos en nuestro JS.

### El elemento canvas

A diferencia de un `<div>`, un `<canvas>` es un lienzo en blanco donde JavaScript dibuja píxeles.
Chart.js necesita este elemento para pintar la gráfica:

```html
<!-- El canvas es solo un contenedor vacío; Chart.js lo rellena -->
<canvas id="grafica-estado"></canvas>
```

### Crear una gráfica de barras

```javascript
// new Chart(elemento, configuración)
const grafica = new Chart(
    document.getElementById('grafica-estado'), // el canvas donde dibujar
    {
        type: 'bar',  // tipo: 'bar', 'line', 'doughnut', 'pie', 'radar', etc.
        data: {
            labels: ['Pendientes', 'En Proceso', 'Resueltas'], // etiquetas del eje X
            datasets: [{
                label: 'Cantidad',
                data: [3, 5, 2],             // los valores de cada barra
                backgroundColor: [
                    'rgba(255, 193, 7, 0.85)',  // amarillo
                    'rgba(13, 202, 240, 0.85)', // celeste
                    'rgba(25, 135, 84, 0.85)',  // verde
                ]
            }]
        },
        options: {
            responsive: true,    // se ajusta al tamaño del contenedor
            scales: {
                y: { beginAtZero: true } // el eje Y empieza en 0
            }
        }
    }
);
```

### Crear una gráfica de dona (doughnut)

```javascript
const graficaTipo = new Chart(
    document.getElementById('grafica-tipo'),
    {
        type: 'doughnut', // dona = pie con agujero en el centro
        data: {
            labels: ['Infraestructura', 'Servicios', 'Otros'],
            datasets: [{
                data: [5, 3, 2],  // los valores de cada segmento
                backgroundColor: ['rgba(13,110,253,0.85)', 'rgba(25,135,84,0.85)', 'rgba(255,193,7,0.85)']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' } // leyenda debajo de la gráfica
            }
        }
    }
);
```

### Actualizar datos sin recrear la gráfica

Si recreáramos la gráfica cada 30 segundos (con `new Chart()`) habría un parpadeo visible
y además Chart.js lanzaría el error "Canvas already in use".

La solución correcta: guardar la instancia en una variable y usar `chart.update()`:

```javascript
let graficaEstado = null; // variable global → null = aún no existe

function actualizarGrafica(nuevosNumeros) {
    if (!graficaEstado) {
        // Primera vez: crear la gráfica
        graficaEstado = new Chart(...);
    } else {
        // Siguientes veces: solo cambiar los datos
        graficaEstado.data.datasets[0].data = nuevosNumeros;
        graficaEstado.update(); // Chart.js redibuaja solo lo que cambió
    }
}
```

### Por qué Chart.js debe cargarse antes que dashboard.js

La misma regla que con Leaflet: si dashboard.js corre antes de que Chart.js cargue,
la clase `Chart` no existe aún y habrá un error. El orden en el HTML importa:

```html
<script src="chart.js"></script>   <!-- primero la biblioteca -->
<script src="dashboard.js"></script> <!-- luego tu código que la usa -->
```

---


## 45. JavaScript — Chart.js referencia completa

### Crear una gráfica

```javascript
// new Chart(elemento, configuración): crea una gráfica en un <canvas>.
const ctx = document.getElementById('mi-canvas');
const grafica = new Chart(ctx, {
    type: 'bar',   // tipo de gráfica (ver abajo)
    data: { ... },
    options: { ... },
});
```

---

### Tipos de gráficas

```javascript
// 'bar': barras verticales
// 'horizontalBar': barras horizontales (en Chart.js v4 se hace con indexAxis: 'y')
// 'line': líneas
// 'pie': pastel (circular completo)
// 'doughnut': dona (pastel con hueco al centro)
// 'radar': araña (polígono con ejes)
// 'polarArea': área polar
// 'bubble': burbujas
// 'scatter': dispersión (puntos)

// Barras horizontales en Chart.js v4:
{
    type: 'bar',
    options: {
        indexAxis: 'y', // ← esto hace que las barras sean horizontales
    }
}
```

---

### Estructura de `data`

```javascript
data: {
    labels: ['Enero', 'Febrero', 'Marzo'],  // etiquetas del eje X (o secciones del pie)

    datasets: [
        // Cada dataset es una serie de datos (una línea, un conjunto de barras, etc.)
        {
            label: 'Incidencias',    // nombre de la serie (aparece en la leyenda)
            data: [10, 25, 15],      // valores (un valor por cada label)

            // Colores de fondo:
            backgroundColor: [       // puede ser un array (un color por barra) o un solo valor
                'rgba(255, 99, 132, 0.8)',   // rojo
                'rgba(54, 162, 235, 0.8)',   // azul
                'rgba(255, 206, 86, 0.8)',   // amarillo
            ],
            // rgba(r, g, b, alpha): alpha = opacidad (0-1)
            // Equivalente con hex: '#FF6384'

            // Color del borde de las barras/líneas:
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1,      // grosor del borde en píxeles

            // Solo para gráficas de línea:
            fill: false,         // false = no rellenar el área bajo la línea
            tension: 0.4,        // curvatura de la línea (0 = recta, 1 = muy curva)
            pointRadius: 5,      // tamaño de los puntos en la línea
        },
        // Se pueden agregar múltiples datasets para comparar series:
        {
            label: 'Resueltas',
            data: [5, 20, 12],
            backgroundColor: 'rgba(75, 192, 192, 0.8)',
        }
    ],
},
```

---

### Opciones de configuración

```javascript
options: {
    responsive: true,         // la gráfica se redimensiona con el contenedor
    maintainAspectRatio: false, // permite cambiar el aspect ratio libremente

    plugins: {
        legend: {
            display: true,         // mostrar la leyenda
            position: 'bottom',    // 'top', 'bottom', 'left', 'right'
        },
        title: {
            display: true,
            text: 'Incidencias por Estado',
            font: { size: 16 },
        },
        tooltip: {
            enabled: true, // mostrar tooltip al pasar el mouse
        },
    },

    scales: {                // solo para bar, line, scatter (no para pie/doughnut)
        x: {
            title: {
                display: true,
                text: 'Mes',
            },
        },
        y: {
            beginAtZero: true,   // el eje Y empieza en 0 (no en el valor mínimo)
            title: {
                display: true,
                text: 'Cantidad',
            },
            ticks: {
                stepSize: 5,     // incrementos del eje Y
                precision: 0,    // sin decimales en las etiquetas
            },
        },
    },

    animation: {
        duration: 500,   // milisegundos de la animación de entrada
    },
},
```

---

### Actualizar y destruir gráficas

```javascript
// Actualizar datos sin recrear la gráfica (evita parpadeo y el error "canvas already in use"):
grafica.data.labels = ['Nuevo1', 'Nuevo2', 'Nuevo3'];
grafica.data.datasets[0].data = [5, 10, 15];
grafica.update(); // Chart.js redibuja solo lo que cambió

// Agregar un nuevo dataset:
grafica.data.datasets.push({
    label: 'Nuevo dataset',
    data: [1, 2, 3],
    backgroundColor: 'blue',
});
grafica.update();

// Destruir la gráfica (libera la memoria y el canvas):
grafica.destroy();
// Después de destroy(), el canvas queda libre para crear otra gráfica.

// Patrón correcto con variable global:
let instanciaGrafica = null;

function actualizarGrafica(labels, valores) {
    if (instanciaGrafica) {
        // Ya existe → actualizar datos
        instanciaGrafica.data.labels = labels;
        instanciaGrafica.data.datasets[0].data = valores;
        instanciaGrafica.update();
    } else {
        // No existe → crear por primera vez
        instanciaGrafica = new Chart(document.getElementById('mi-canvas'), {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Datos', data: valores }] },
        });
    }
}
```

---



---

# BLOQUE 6 — PHP: fundamentos del lenguaje

## 2. PHP en 5 minutos

Si ya sabes HTML, esto te va a parecer familiar. PHP es un lenguaje que corre
en el servidor (no en el navegador como JavaScript).

```php
<?php
// Esto es PHP. Todo archivo PHP empieza con <?php

// Las variables llevan $ adelante — diferente a JavaScript
$nombre = "Jose";
$edad   = 20;

// Concatenar texto (unir palabras)
echo "Hola " . $nombre;   // Imprime: Hola Jose

// Condicional — igual que en JS pero con $ en las variables
if ($edad >= 18) {
    echo "Es mayor de edad";
} else {
    echo "Es menor de edad";
}

// Array (lista) — como un array en JS
$frutas = ["manzana", "pera", "uva"];

// Array asociativo — como un objeto en JS
$usuario = [
    "nombre" => "Jose",
    "email"  => "jose@email.com",
    "edad"   => 20
];
echo $usuario["nombre"]; // Imprime: Jose

// Función — igual que en JS
function saludar($nombre) {
    return "Hola " . $nombre;
}
```

**Diferencias clave con JavaScript:**

| JavaScript       | PHP                    |
|-----------------|------------------------|
| `let x = 5`     | `$x = 5`               |
| `console.log(x)`| `echo $x`              |
| `x.nombre`      | `$x["nombre"]`         |
| Corre en el navegador | Corre en el servidor |

---


## 37. PHP — Sintaxis avanzada y operadores especiales

### El operador `=>` en PHP

En PHP, `=>` se usa en DOS contextos distintos:

**Contexto 1: Arrays asociativos (clave → valor)**
```php
// Array asociativo: como un objeto en JavaScript
$usuario = [
    'nombre' => 'José',      // clave   => valor
    'email'  => 'j@mail.com',
    'edad'   => 20,
];

// Acceder al valor:
echo $usuario['nombre']; // José

// En los seeders se usa mucho para insertar datos:
DB::table('roles')->insert([
    'nombre'     => 'admin',  // campo => valor
    'created_at' => now(),
]);
```

**Contexto 2: Arrow functions de PHP (PHP 7.4+)**
```php
// Arrow function: función anónima corta con return implícito.
// Sintaxis: fn(parámetros) => expresión
// Equivalente a: function(parámetros) { return expresión; }

// Función tradicional (larga):
$doble = function($n) {
    return $n * 2;
};

// Arrow function equivalente:
$doble = fn($n) => $n * 2;

// En el proyecto, aparece mucho en consultas Eloquent:
$query->where('estado_actual', 'pendiente');

// Con closures en whereHas:
$query->whereHas('role', function($q) {
    $q->where('nombre', 'normal');
});

// Con arrow function (más corto):
$query->whereHas('role', fn($q) => $q->where('nombre', 'normal'));

// En rutas:
Route::get('/user', fn(Request $request) => $request->user()->load('role'));
// El => devuelve directamente el resultado de $request->user()->load('role')
```

---

### Operador `??` (Null Coalescing) en PHP

```php
// Equivalente al ?? de JavaScript.
// Devuelve el lado derecho si el izquierdo es null o no existe.

$valor = $datos['clave'] ?? 'valor_por_defecto';
// Si $datos['clave'] existe y no es null → lo devuelve
// Si no existe o es null → devuelve 'valor_por_defecto'

// Muy útil con métodos que pueden retornar null:
$tiempo = DB::selectOne('SELECT calcular_tiempo_resolucion(?) AS dias', [(int) $id]);
$dias   = $tiempo?->dias ?? 0;
// Si $tiempo es null → 0
// Si $tiempo existe pero ->dias es null → 0
// Si existe y tiene valor → ese valor

// Encadenar múltiples ??:
$valor = $a ?? $b ?? $c ?? 'default';
// Devuelve el primer valor que no sea null
```

---

### Operador `?->` (Nullsafe Operator) en PHP 8.0+

```php
// Si el lado izquierdo es null, devuelve null sin error.
// Sin ?->: TypeError si el objeto es null.
// Con ?->: devuelve null silenciosamente.

// Sin nullsafe (código frágil):
$rolNombre = $user->role->nombre; // ERROR si $user->role es null

// Con nullsafe (código seguro):
$rolNombre = $user?->role?->nombre;
// Si $user es null → null
// Si $user existe pero role es null → null
// Si ambos existen → el valor de nombre

// En el proyecto:
$esAdmin = $user?->role && strtolower($user->role->nombre) === 'admin';

// Con métodos:
$primerComentario = $incidencia?->comentarios()?->first();
```

---

### Type hints (declaraciones de tipo)

```php
// PHP permite declarar qué tipo deben tener los parámetros y el retorno.
// Ventajas:
//   - El IDE puede detectar errores antes de correr el código.
//   - PHP lanza TypeError si se pasa el tipo incorrecto.
//   - Documenta la intención del código.

// Tipos básicos: bool, int, float, string, array, object, void, mixed
// Tipos de Laravel: Request, Response, Collection, Builder, etc.

// : bool → esta función siempre retorna true o false
private function esAdmin(Request $request): bool
{
    $user = $request->user();
    return $user && $user->role && strtolower($user->role->nombre) === 'admin';
}

// : void → esta función no retorna nada
public function run(): void
{
    // hace cosas pero no retorna valor
}

// Parámetro tipado:
public function store(Request $request): JsonResponse
// Request $request → el parámetro $request debe ser una instancia de Request
// : JsonResponse  → la función retorna una instancia de JsonResponse

// Tipos nullable (puede ser null): ?TipoNombre
public function find(int $id): ?Incidencia
// Puede retornar una Incidencia o null (si no se encontró)
```

---

### `$this` — referencia al objeto actual

```php
// Dentro de una clase, $this se refiere a la instancia actual del objeto.
// Es como 'this' en JavaScript.

class IncidenciaController extends Controller
{
    // Función privada de apoyo
    private function esAdmin(Request $request): bool
    {
        return /* ... */;
    }

    public function index(Request $request)
    {
        // $this->esAdmin() llama a la función del mismo objeto
        if (!$this->esAdmin($request)) {
            // solo mostrar las suyas
        }
    }

    public function store(Request $request)
    {
        // $this también accede a propiedades del objeto:
        $formato = $this->formato; // si hubiera una propiedad $formato
    }
}

// En Modelos de Eloquent:
class Incidencia extends Model
{
    public function usuario()
    {
        // $this se refiere a la instancia del modelo (la incidencia específica)
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
```

---

### Closures vs Arrow functions en PHP

```php
// CLOSURE (función anónima tradicional):
// - Puede usar múltiples líneas.
// - Necesita 'use' para capturar variables externas.
// - Devuelve con 'return' explícito.

$estado = 'pendiente';

$query->where(function($q) use ($estado) {
    // 'use ($estado)' captura la variable $estado del scope externo.
    // Sin 'use', $estado no sería accesible dentro de la función.
    $q->where('estado_actual', $estado)
      ->orWhere('estado_actual', 'en_proceso');
});

// ARROW FUNCTION (fn()):
// - Solo una expresión (sin llaves, sin return).
// - Captura automáticamente las variables del scope externo (sin 'use').
// - Retorno implícito de la expresión.

$query->where(fn($q) => $q->where('estado_actual', $estado));
// $estado se captura automáticamente, no necesita 'use'
```

---


## 38. PHP — `$request` y todos sus métodos

### ¿Qué es `$request`?

`$request` (instancia de `Illuminate\Http\Request`) representa la petición HTTP
que llegó al servidor. Contiene TODO lo que el cliente envió:
URL, parámetros GET, cuerpo POST, archivos, headers, cookies, usuario autenticado.

Laravel lo inyecta automáticamente en los métodos del controlador al tiparlo:
```php
public function store(Request $request)
{
    // $request ya tiene todos los datos de la petición
}
```

---

### Leer datos del cuerpo de la petición

```javascript
// El frontend envía:
await apiFetch('/incidencias', {
    method: 'POST',
    body: JSON.stringify({
        titulo: 'Bache grande',
        descripcion: 'En la esquina de...',
        ciudad_id: 5,
        activo: true,
    }),
});
```

```php
// El backend lee esos datos con $request:

// Forma 1: propiedad dinámica (la más usada en el proyecto)
$titulo    = $request->titulo;       // 'Bache grande'
$ciudadId  = $request->ciudad_id;   // 5

// Forma 2: ->input('campo', 'default')
$titulo    = $request->input('titulo', '');
// Si 'titulo' no viene → '' (string vacío, no null)

// Diferencias:
// $request->campo          → null si no existe
// $request->input('campo', 'x') → 'x' si no existe

// Leer todos los datos como array:
$todos = $request->all();
// ['titulo' => 'Bache grande', 'descripcion' => '...', 'ciudad_id' => 5]
```

---

### `$request->filled()` — campo presente y no vacío

```php
// $request->filled('campo'): true si el campo:
//   - Existe en la petición Y
//   - No es null Y
//   - No es string vacío ''
// Útil para actualizaciones parciales: "solo cambia lo que llegó".

if ($request->filled('titulo')) {
    $user->name = $request->titulo;
}
if ($request->filled('password')) {
    $user->password = Hash::make($request->password);
}
$user->save();

// Diferencias con otros métodos:
// $request->has('campo')     → true si el campo EXISTE (aunque sea null o '')
// $request->filled('campo')  → true si existe Y no está vacío
// $request->missing('campo') → true si el campo NO existe
```

---

### `$request->validate()` — validar los datos

```php
// validate() verifica que los datos cumplan las reglas.
// Si alguna falla → Laravel devuelve automáticamente un error 422 con los mensajes.
// Si todas pasan → devuelve el array de datos validados.

$datosValidados = $request->validate([
    'titulo'      => 'required|string|max:255',
    // required    → el campo debe existir y no estar vacío
    // string      → debe ser texto (no número, no array)
    // max:255     → máximo 255 caracteres

    'descripcion' => 'nullable|string',
    // nullable    → puede ser null o no venir; si viene, debe ser string

    'latitud'     => 'required|numeric|between:-5.5,1.8',
    // numeric     → debe ser número (entero o decimal)
    // between:x,y → debe estar entre x e y

    'subtipo_id'  => 'required|integer|exists:subtipos_incidencia,id',
    // integer     → debe ser número entero
    // exists:tabla,columna → ese valor debe existir en esa tabla y columna de la BD

    'email'       => 'required|email|unique:users,email',
    // email       → formato válido de email
    // unique:tabla,columna → no debe existir ese valor ya en la tabla

    'email_editar' => 'required|email|unique:users,email,' . $user->id,
    // unique con excepción: ignora el registro con ese id (para no rechazar el propio email)

    'password'    => 'required|string|min:6|confirmed',
    // min:6       → mínimo 6 caracteres
    // confirmed   → debe existir también el campo 'password_confirmation' con el mismo valor

    'foto'        => 'required|image|mimes:jpeg,png,jpg|max:2048',
    // image       → debe ser un archivo de imagen
    // mimes:x,y   → tipos de archivo permitidos (extensión)
    // max:2048    → tamaño máximo en kilobytes (2048 KB = 2 MB)

    'rol'         => 'required|in:admin,normal,tecnico',
    // in:a,b,c   → el valor debe ser uno de esos

    'campo'       => 'sometimes|string',
    // sometimes  → solo valida si el campo está presente en la petición
]);
```

---

### Otros métodos útiles de `$request`

```php
// $request->boolean('campo'): convierte a booleano.
// '1', 'true', 'on', 'yes' → true
// '0', 'false', 'off', 'no', '' → false
// Útil para checkboxes y filtros de tipo sí/no.
$soloMias = $request->boolean('solo_mias'); // true o false

// $request->only(['campo1', 'campo2']): devuelve SOLO esos campos.
// Usado para no aceptar campos inesperados (seguridad).
$datos = $request->only(['titulo', 'descripcion', 'estado_actual', 'prioridad']);
// Si el cliente envía también 'usuario_id', es ignorado

// $request->except(['campo']): todos los campos EXCEPTO los listados.
$datos = $request->except(['_token', 'submit']);

// $request->file('campo'): obtiene el archivo subido.
// Devuelve una instancia de UploadedFile o null.
$foto = $request->file('foto');
if ($foto) {
    $ruta = $foto->store('evidencias', 'public');
    // store('carpeta', 'disco'): guarda el archivo y retorna la ruta relativa
}

// $request->hasFile('campo'): ¿se subió un archivo?
if ($request->hasFile('foto')) { ... }

// $request->user(): el usuario autenticado actualmente.
// Retorna null si no hay usuario autenticado.
$usuarioActual = $request->user();
$idActual      = $request->user()->id;

// $request->method(): el método HTTP de la petición.
$metodo = $request->method(); // 'GET', 'POST', 'PUT', 'DELETE', etc.

// $request->url(): la URL completa de la petición.
$url = $request->url(); // 'http://localhost/api/incidencias'

// $request->ip(): la IP del cliente.
$ip = $request->ip(); // '192.168.1.5'

// $request->header('nombre'): leer un header HTTP específico.
$auth  = $request->header('Authorization'); // 'Bearer token123'
$tipo  = $request->header('Content-Type');  // 'application/json'

// $request->query('campo', 'default'): leer parámetros de la URL (query string).
// Si la URL es: /api/incidencias?estado=pendiente&pagina=2
$estado = $request->query('estado');   // 'pendiente'
$pagina = $request->query('pagina', 1); // 2 (o 1 si no viene)
```

---



---

# BLOQUE 7 — Laravel: arquitectura y componentes base

## 3. Qué es Laravel

Laravel es un **framework** de PHP. Un framework es como un kit de construcción:
en vez de hacer todo desde cero, ya viene con piezas listas para las cosas
más comunes (login, base de datos, validaciones, emails, etc.).

**Sin Laravel** tendrías que escribir esto para consultar la base de datos:
```php
$conexion = new PDO("pgsql:host=db;dbname=incidencias", "admin", "password");
$stmt = $conexion->prepare("SELECT * FROM incidencias WHERE id = ?");
$stmt->execute([5]);
$resultado = $stmt->fetch();
```

**Con Laravel** escribes simplemente:
```php
$incidencia = Incidencia::find(5);
```

Ambas líneas hacen exactamente lo mismo. Laravel esconde toda la complejidad.

### Carpetas importantes de Laravel en este proyecto:

```
backend/
|-- app/
|   |-- Http/
|   |   |-- Controllers/    <-- Las funciones que responden cada URL
|   |   +-- Middleware/     <-- Los "guardias" que filtran peticiones
|   +-- Models/             <-- Las clases que representan tablas de la BD
|-- routes/
|   +-- api.php             <-- El mapa de URLs <-> funciones
|-- database/
|   +-- migrations/         <-- Instrucciones para crear las tablas
+-- .env                    <-- Configuración privada (contraseñas, puertos)
```

---


## 4. Cómo viaja una petición

Ejemplo real: el frontend pide la lista de incidencias.

```
PASO 1: El JS del frontend hace esto:
        fetch('http://localhost/api/incidencias', {
            headers: { 'Authorization': 'Bearer TOKEN123' }
        })

PASO 2: Nginx recibe la petición y la reenvía al backend Laravel

PASO 3: Laravel revisa routes/api.php y encuentra:
        "Esta URL la maneja la función index() del IncidenciaController"

PASO 4: Antes de llegar ahí, pasa por el guardia 'auth:sanctum':
        --> "¿El TOKEN123 es válido?" --> Sí --> continúa al controlador
        --> "¿El TOKEN123 es válido?" --> No --> devuelve error 401

PASO 5: Llega a IncidenciaController, función index()
        --> Consulta la base de datos
        --> Arma la respuesta en JSON

PASO 6: La respuesta viaja de vuelta al navegador:
        { "status": "success", "data": [ ...lista de incidencias... ] }

PASO 7: El JS del frontend recibe el JSON y lo muestra en pantalla
```

---


## 5. Las Rutas

**Archivo:** `backend/routes/api.php`

Las rutas son el **directorio** del sistema. Le dicen a Laravel qué función
ejecutar cuando llega cada URL.

```php
// ── RUTA PÚBLICA ─────────────────────────────────────────────────────────────
// No necesita token. Cualquiera puede acceder.
Route::post('/login', [AuthController::class, 'login']);
// Traducción: "cuando llegue POST /api/login, ejecuta AuthController->login()"


// ── RUTAS PROTEGIDAS ──────────────────────────────────────────────────────────
// Todo lo que está DENTRO de este grupo requiere un token válido.
// Si no tienes token --> Laravel devuelve error 401 automáticamente.
Route::middleware('auth:sanctum')->group(function () {

    // Quién soy yo (el usuario logueado)
    Route::get('/user', fn(Request $request) => $request->user()->load('role'));

    // Dashboard con métricas
    Route::get('/dashboard', [DashboardController::class, 'obtenerMetricas']);

    // Catálogos para llenar formularios
    Route::get('/tipos-incidencia', [CatalogoController::class, 'tiposIncidencia']);
    Route::get('/ciudades',         [CatalogoController::class, 'ciudades']);

    // Notificaciones
    Route::get('/notificaciones',           [NotificacionController::class, 'index']);
    Route::put('/notificaciones/{id}/leer', [NotificacionController::class, 'marcarLeida']);

    // CRUD de incidencias
    // apiResource genera AUTOMÁTICAMENTE estas 5 rutas:
    //   GET    /incidencias        --> index()   (listar todas)
    //   POST   /incidencias        --> store()   (crear nueva)
    //   GET    /incidencias/{id}   --> show()    (ver una)
    //   PUT    /incidencias/{id}   --> update()  (editar una)
    //   DELETE /incidencias/{id}   --> destroy() (eliminar una)
    Route::apiResource('incidencias', IncidenciaController::class);

    // Rutas adicionales de cada incidencia
    Route::get('/incidencias/{id}/historial',    [IncidenciaController::class, 'historial']);
    Route::get('/incidencias/{id}/comentarios',  [ComentarioController::class, 'index']);
    Route::post('/incidencias/{id}/comentarios', [ComentarioController::class, 'store']);
    // ...
});
```

**¿Qué es `{id}` en la URL?**
Es un hueco variable. Si la URL real es `/api/incidencias/7`, el `{id}` vale `7`.
Laravel lo captura automáticamente y te lo pasa a la función como parámetro.

---


## 6. Los Modelos

**Carpeta:** `backend/app/Models/`

Un Modelo es una **clase PHP que representa una tabla de la base de datos**.
Cada fila de la tabla = un objeto del modelo en PHP.

```php
// Modelo Incidencia.php — representa la tabla "incidencias"
class Incidencia extends Model
{
    protected $table = 'incidencias'; // nombre exacto de la tabla en la BD

    // Los campos que se pueden llenar al crear o editar.
    // Si un campo NO está aquí, Laravel lo ignora aunque llegue en la petición.
    // Es una medida de seguridad: evita que alguien cambie campos que no debe.
    protected $fillable = [
        'titulo', 'descripcion', 'latitud', 'longitud',
        'prioridad', 'estado_actual', 'ciudad_id',
        'subtipo_id', 'usuario_id', 'ruta_archivo', 'fecha_resolucion'
    ];

    // ── RELACIONES ────────────────────────────────────────────────────────────
    // Le dicen a Laravel cómo están conectadas las tablas entre sí.
    // Son como los JOIN de SQL pero escritos en PHP, mucho más legibles.

    // Una incidencia PERTENECE A un usuario (el que la reportó)
    // "usuario_id" en incidencias apunta al "id" de users
    public function usuario() {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // Una incidencia PERTENECE A un subtipo (ej: "Bache" pertenece a "Vialidad")
    public function subtipo() {
        return $this->belongsTo(SubtipoIncidencia::class, 'subtipo_id');
    }

    // Una incidencia TIENE MUCHOS comentarios
    public function comentarios() {
        return $this->hasMany(Comentario::class, 'incidencia_id');
    }

    // Una incidencia TIENE MUCHAS asignaciones de técnicos
    public function asignaciones() {
        return $this->hasMany(AsignacionIncidencia::class, 'incidencia_id');
    }
}
```

**Los tres tipos de relación más usados:**

| Relación    | Significado            | Ejemplo en este proyecto                |
|-------------|------------------------|-----------------------------------------|
| `belongsTo` | "Yo pertenezco a otro" | Una incidencia pertenece a un usuario   |
| `hasMany`   | "Yo tengo muchos"      | Un usuario tiene muchas incidencias     |
| `hasOne`    | "Yo tengo exactamente uno" | Un usuario tiene un perfil          |

---


## 7. Los Controladores

**Carpeta:** `backend/app/Http/Controllers/Api/`

Un controlador es un archivo con funciones. Cada función = una acción.
Son la **cocina** donde ocurre el trabajo real.

---

### AuthController.php — El Login

```php
public function login(Request $request)
{
    // PASO 1: Validar que lleguen los datos correctos
    // Si no llegan --> Laravel devuelve error 422 automáticamente
    // 'required' = es obligatorio que llegue este campo
    // 'email'    = debe tener formato de email (con @ y dominio)
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required'
    ]);

    // PASO 2: Buscar al usuario en la BD por su email
    // User::where('email', $valor) = SELECT * FROM users WHERE email = ?
    // ->first()                    = traer solo el primero (o null si no existe)
    $user = User::where('email', $request->email)->first();

    // PASO 3: Verificar si existe y si la contraseña es correcta
    // Hash::check() compara texto plano con la versión encriptada en la BD
    // Nunca guardamos contraseñas en texto puro, siempre encriptadas
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['mensaje' => 'Credenciales incorrectas.'], 401);
    }

    // PASO 4: Crear el token de acceso (la "pulsera VIP" del sistema)
    // El frontend guarda este token y lo manda en cada petición
    $token = $user->createToken('token_acceso_upse')->plainTextToken;

    // PASO 5: Responder con los datos del usuario y su token
    return response()->json([
        'mensaje' => '¡Bienvenido!',
        'usuario' => $user,
        'token'   => $token
    ], 200); // 200 = OK, todo salió bien
}
```

**Códigos de estado HTTP — los más importantes:**

| Código | Nombre        | Cuándo lo usamos                              |
|--------|---------------|-----------------------------------------------|
| 200    | OK            | Todo bien, aquí están los datos               |
| 201    | Created       | Se creó algo nuevo (nueva incidencia, etc.)   |
| 401    | Unauthorized  | No tienes token o el token venció             |
| 403    | Forbidden     | Tienes token pero no tienes permiso           |
| 404    | Not Found     | Lo que buscas no existe en la BD              |
| 422    | Unprocessable | Los datos que mandaste no pasaron validación  |
| 500    | Server Error  | Algo falló dentro del servidor                |

---

### IncidenciaController.php — El más importante

```php
// FUNCIÓN PRIVADA DE APOYO
// No responde a ninguna URL. La usamos internamente para no repetir código.
// Devuelve true si el usuario logueado es admin, false si no lo es.
private function esAdmin(Request $request): bool
{
    $user = $request->user();
    return $user && $user->role && strtolower($user->role->nombre) === 'admin';
}

// ── GET /api/incidencias ──────────────────────────────────────────────────────
// Lista las incidencias. Admins ven todas, usuarios normales solo las suyas.
public function index(Request $request)
{
    // Construimos la consulta poco a poco (sin ejecutarla todavía)
    // with([...]) = trae datos relacionados en la misma consulta
    //              Evita hacer 100 consultas separadas
    $query = Incidencia::with(['usuario', 'subtipo.tipo', 'ciudad']);

    // Si NO es admin, mostrar solo sus propias incidencias
    if (!$this->esAdmin($request)) {
        $query->where('usuario_id', $request->user()->id);
    }

    // Filtros opcionales desde la URL
    // Ejemplo: GET /api/incidencias?estado=pendiente&ciudad_id=3
    if ($request->filled('estado')) {      // ¿llegó el parámetro 'estado'?
        $query->where('estado_actual', $request->estado);
    }
    if ($request->filled('busqueda')) {
        // ilike = búsqueda sin importar mayúsculas (solo PostgreSQL)
        // % son comodines: %bache% encuentra "hay un bache en la esquina"
        $query->where('titulo', 'ilike', '%' . $request->busqueda . '%');
    }
    // ... más filtros ...

    // Ejecutar la consulta y devolver en JSON
    return response()->json(['status' => 'success', 'data' => $query->get()]);
}

// ── POST /api/incidencias ─────────────────────────────────────────────────────
// Crear una nueva incidencia
public function store(Request $request)
{
    // Validar campos obligatorios
    // 'exists:subtipos_incidencia,id' = ese ID debe existir en la tabla
    // 'image|mimes:jpeg,png,jpg'      = solo acepta imágenes
    // 'max:2048'                       = máximo 2 MB
    $request->validate([
        'titulo'     => 'required|string|max:255',
        'descripcion'=> 'nullable|string',
        'latitud'    => 'required|numeric',
        'longitud'   => 'required|numeric',
        'subtipo_id' => 'required|exists:subtipos_incidencia,id',
        'ciudad_id'  => 'required|exists:ciudades,id',
        'foto'       => 'required|image|mimes:jpeg,png,jpg|max:2048',
    ]);

    // Guardar la foto en el disco del servidor
    // Queda en: storage/app/public/evidencias/nombrearchivo.jpg
    $rutaFoto = $request->file('foto')->store('evidencias', 'public');

    // Crear el registro en la BD
    // Auth::id() = ID del usuario que está logueado en este momento
    $incidencia = Incidencia::create([
        'titulo'        => $request->titulo,
        'estado_actual' => 'pendiente',   // siempre empieza en pendiente
        'prioridad'     => 'media',       // valor por defecto
        'ruta_archivo'  => $rutaFoto,
        'usuario_id'    => Auth::id(),    // quién la está creando
        // ... demás campos ...
    ]);

    return response()->json(['data' => $incidencia], 201); // 201 = Created
}

// ── PUT /api/incidencias/{id} ─────────────────────────────────────────────────
// Actualizar una incidencia (cambiar estado, prioridad, etc.)
public function update(Request $request, string $id)
{
    $incidencia = Incidencia::find($id); // buscar por ID

    if (!$incidencia) {
        return response()->json(['mensaje' => 'No encontrada'], 404);
    }

    // CONTROL DE ACCESO
    // OPCIÓN A (activa): solo admin o el dueño original pueden editar
    $tienePermiso = $this->esAdmin($request) || $incidencia->usuario_id === $request->user()->id;

    // OPCIÓN B (comentada): también permite al técnico "responsable" cambiar estado
    // $esResponsable = $incidencia->asignaciones()
    //     ->where('usuario_id', $request->user()->id)
    //     ->where('rol_asignado', 'responsable')
    //     ->exists();
    // $tienePermiso = ... || $esResponsable;

    if (!$tienePermiso) {
        return response()->json(['mensaje' => 'No autorizado'], 403);
    }

    $estadoAnterior = $incidencia->estado_actual; // guardar antes de cambiar

    // Actualizar solo los campos permitidos
    // ->only([...]) = ignora todo lo que no esté en esta lista (seguridad)
    $incidencia->update($request->only([
        'titulo', 'descripcion', 'estado_actual', 'prioridad', 'fecha_resolucion'
    ]));

    // NOTIFICACIONES AUTOMÁTICAS
    // Si el estado cambió --> avisar a los técnicos asignados
    if ($request->filled('estado_actual') && $request->estado_actual !== $estadoAnterior) {
        foreach ($incidencia->asignaciones()->get() as $asignacion) {
            Notificacion::create([
                'usuario_id' => $asignacion->usuario_id,
                'mensaje'    => "La incidencia '{$incidencia->titulo}' cambió de '{$estadoAnterior}' a '{$incidencia->estado_actual}'.",
                'leido'      => false,
            ]);
        }
    }

    return response()->json(['mensaje' => 'Actualizada', 'data' => $incidencia]);
}
```

---

### DashboardController.php — Las estadísticas

```php
public function obtenerMetricas(Request $request)
{
    // Cache::remember('clave', segundos, función)
    // Pregunta: ¿hay algo guardado en Redis con la clave 'dashboard_metrics'?
    //   SI hay --> lo devuelve directamente (no toca la BD, ultra rápido)
    //   NO hay --> ejecuta la función, guarda el resultado 300 segundos (5 min)
    $datos = Cache::remember('dashboard_metrics', 300, function () {

        // Contar registros por estado
        $total      = Incidencia::count();
        $pendientes = Incidencia::where('estado_actual', 'pendiente')->count();

        // Consulta con JOIN para agrupar por tipo
        // DB::table() se usa cuando la consulta es compleja y Eloquent queda difícil
        $porTipo = DB::table('incidencias')
            ->join('subtipos_incidencia', 'incidencias.subtipo_id', '=', 'subtipos_incidencia.id')
            ->join('tipos_incidencia', 'subtipos_incidencia.tipo_id', '=', 'tipos_incidencia.id')
            ->select('tipos_incidencia.nombre as tipo', DB::raw('count(*) as total'))
            ->groupBy('tipos_incidencia.nombre')
            ->get();

        // Tiempo promedio de resolución en días
        // EXTRACT(EPOCH FROM ...) convierte tiempo a segundos
        // / 86400 convierte segundos a días (60 seg * 60 min * 24 hrs = 86400)
        $tiempoPromedio = DB::table('incidencias')
            ->whereNotNull('fecha_resolucion')
            ->selectRaw("AVG(EXTRACT(EPOCH FROM (fecha_resolucion::timestamp - created_at::timestamp)) / 86400) as promedio_dias")
            ->value('promedio_dias');

        return [
            'estadisticas'  => compact('total', 'pendientes'),
            'por_tipo'      => $porTipo,
            'tiempo_promedio_resolucion_dias' => round($tiempoPromedio ?? 0, 2),
            // ... más datos ...
        ];
    });

    return response()->json(['exito' => true, 'datos' => $datos]);
}
```

---


## 8. El Middleware

**Archivo:** `backend/app/Http/Middleware/CheckAdmin.php`

Un middleware es código que se ejecuta **antes** de llegar al controlador.
Es el guardia de seguridad en la puerta.

```php
public function handle(Request $request, Closure $next)
{
    $user = $request->user(); // El usuario logueado

    // Si no hay usuario, no tiene rol, o su rol no es 'admin' --> bloquear
    if (!$user || !$user->role || strtolower($user->role->nombre) !== 'admin') {
        return response()->json(['mensaje' => 'Acceso no autorizado.'], 403);
    }

    // Todo OK --> dejar pasar al controlador
    return $next($request);
}
```

**Flujo con middleware:**
```
Petición llega
     |
     v
Middleware auth:sanctum --> ¿Tiene token válido?
     |                          NO --> 401
     v (SÍ)
Middleware admin (si aplica) --> ¿Es admin?
     |                               NO --> 403
     v (SÍ)
Controlador --> hace el trabajo
     |
     v
Respuesta JSON
```

---



---

# BLOQUE 8 — Laravel: base de datos y Eloquent

## 9. Consultas a la Base de Datos

Estos son los patrones que más vas a ver en el proyecto:

```php
// BUSCAR UNO POR ID
$incidencia = Incidencia::find(5);
// SQL: SELECT * FROM incidencias WHERE id = 5 LIMIT 1
// Si no existe, devuelve null

// BUSCAR TODOS
$todas = Incidencia::all();
// SQL: SELECT * FROM incidencias

// BUSCAR CON CONDICIÓN
$pendientes = Incidencia::where('estado_actual', 'pendiente')->get();
// SQL: SELECT * FROM incidencias WHERE estado_actual = 'pendiente'

// CREAR UN REGISTRO
Incidencia::create(['titulo' => 'Bache', 'estado_actual' => 'pendiente']);
// SQL: INSERT INTO incidencias (titulo, estado_actual) VALUES ('Bache', 'pendiente')

// ACTUALIZAR UN REGISTRO
$incidencia->update(['estado_actual' => 'resuelto']);
// SQL: UPDATE incidencias SET estado_actual = 'resuelto' WHERE id = X

// ELIMINAR UN REGISTRO
$incidencia->delete();
// SQL: DELETE FROM incidencias WHERE id = X

// CONTAR
$total = Incidencia::count();
// SQL: SELECT COUNT(*) FROM incidencias

// ORDENAR Y LIMITAR
$recientes = Incidencia::orderBy('created_at', 'desc')->take(5)->get();
// SQL: SELECT * FROM incidencias ORDER BY created_at DESC LIMIT 5

// TRAER CON DATOS RELACIONADOS (evita múltiples consultas)
$incidencias = Incidencia::with(['usuario', 'subtipo.tipo', 'ciudad'])->get();
// Trae las incidencias Y los datos de usuario, subtipo y ciudad en una sola consulta
```

---


## 39. PHP — Eloquent avanzado

### `whereHas()` — filtrar por relación

```php
// whereHas('relacion', función): filtra modelos que tengan una relación que cumpla la condición.
// Genera un EXISTS en SQL.

// "Incidencias asignadas al técnico con id = 5":
$incidencias = Incidencia::whereHas('asignaciones', function($q) {
    $q->where('usuario_id', 5);
})->get();
// SQL: SELECT * FROM incidencias WHERE EXISTS (
//         SELECT 1 FROM asignaciones_incidencia
//         WHERE incidencias.id = asignaciones_incidencia.incidencia_id
//         AND usuario_id = 5
//      )

// Con arrow function:
$incidencias = Incidencia::whereHas('asignaciones', fn($q) =>
    $q->where('usuario_id', $request->user()->id)
)->get();

// "Usuarios con rol 'normal'":
$ciudadanos = User::whereHas('role', fn($q) => $q->where('nombre', 'normal'))->get();

// doesntHave(): lo opuesto — modelos que NO tienen esa relación.
$sinAsignar = Incidencia::doesntHave('asignaciones')->get();
```

---

### `firstOrCreate()` — buscar o crear

```php
// Busca un registro con la condición. Si no existe, lo crea.
// Retorna siempre el modelo (ya sea encontrado o creado).
// Es seguro para ejecutarse múltiples veces (idempotente).

// Con un array (busca y crea con los mismos campos):
$pais = Pais::firstOrCreate(['nombre' => 'Ecuador']);
// Si 'Ecuador' existe → lo retorna
// Si no existe → lo crea y lo retorna

// Con dos arrays (busca por el primero, crea con ambos):
SubtipoIncidencia::firstOrCreate(
    ['nombre' => 'Bache o Socavón', 'tipo_id' => $tipo->id], // condición de búsqueda
    ['descripcion' => 'Huecos en la vía']                    // campos extras al crear
);
// Si existe un subtipo con ese nombre Y tipo_id → lo retorna (sin cambiar descripcion)
// Si no existe → lo crea con nombre, tipo_id Y descripcion

// firstOrNew(): igual pero NO guarda en la BD (debes llamar ->save() después).
```

---

### `paginate()` — paginación

```php
// paginate(N): divide los resultados en páginas de N registros.
// En la URL: GET /api/incidencias?page=2 → devuelve la página 2.
$resultado = Incidencia::with(['usuario', 'subtipo'])->paginate(10);

// El objeto paginado tiene:
$resultado->items();        // array de registros de la página actual
$resultado->currentPage(); // número de la página actual
$resultado->lastPage();     // número de la última página
$resultado->total();        // total de registros sin paginar
$resultado->perPage();      // registros por página
$resultado->hasMorePages(); // true si hay más páginas

// En la respuesta JSON se arma así:
return response()->json([
    'status' => 'success',
    'data'   => $resultado->items(),
    'meta'   => [
        'current_page' => $resultado->currentPage(),
        'last_page'    => $resultado->lastPage(),
        'total'        => $resultado->total(),
        'per_page'     => $resultado->perPage(),
    ],
]);
```

---

### `->load()` vs `with()` — cargar relaciones

```php
// with(['relacion']): eager loading ANTES de ejecutar la consulta (en el SELECT).
// Se usa cuando construyes la consulta desde cero.
$incidencias = Incidencia::with(['usuario', 'subtipo.tipo'])->get();
// SQL: SELECT ... FROM incidencias
//      SELECT ... FROM users WHERE id IN (...)  ← una sola consulta para todos
//      SELECT ... FROM subtipos WHERE id IN (...)

// ->load(['relacion']): carga relaciones en un modelo QUE YA EXISTE.
// Se usa después de find(), create(), update(), etc.
$incidencia = Incidencia::find(5);
$incidencia->load(['usuario', 'comentarios']); // carga las relaciones después

// Útil en store() → crear el registro y luego cargar sus relaciones para la respuesta:
$incidencia = Incidencia::create([...]);
$incidencia->load(['usuario', 'subtipo.tipo', 'ciudad']);
return response()->json(['data' => $incidencia], 201);

// ->refresh(): recarga el modelo desde la BD (por si un trigger lo modificó).
$incidencia->refresh();
// Si el trigger fn_fecha_resolucion_automatica llenó fecha_resolucion,
// $incidencia->fecha_resolucion tendrá el valor actualizado después de ->refresh().
```

---

### `->exists()` vs `->get()` vs `->first()`

```php
// ->exists(): ¿hay algún registro que cumpla la condición? → true o false.
// No trae los datos, solo verifica si existe. Más eficiente que ->get().
$yaAsignado = Incidencia::where('id', $incidenciaId)
                        ->whereHas('asignaciones', fn($q) => $q->where('usuario_id', $userId))
                        ->exists();
// SQL: SELECT EXISTS(SELECT 1 FROM ...)

// ->first(): trae el PRIMER registro que cumpla la condición, o null.
$incidencia = Incidencia::where('usuario_id', $userId)->first();
// SQL: SELECT * FROM incidencias WHERE usuario_id = ? LIMIT 1

// ->firstOrFail(): igual que ->first() pero lanza ModelNotFoundException (404) si no hay.
$incidencia = Incidencia::findOrFail($id);
// SQL: SELECT * FROM incidencias WHERE id = ? LIMIT 1
// Si no existe → respuesta 404 automática

// ->get(): trae TODOS los registros que cumplan la condición. Devuelve Collection.
$todas = Incidencia::where('estado_actual', 'pendiente')->get();
// SQL: SELECT * FROM incidencias WHERE estado_actual = 'pendiente'

// ->count(): cuenta registros. No trae datos.
$total = Incidencia::where('estado_actual', 'resuelto')->count();
// SQL: SELECT COUNT(*) FROM incidencias WHERE estado_actual = 'resuelto'

// ->value('columna'): trae solo el valor de una columna del primer resultado.
$promedio = DB::table('incidencias')->whereNotNull('fecha_resolucion')->value('promedio_dias');
// SQL: SELECT promedio_dias FROM incidencias WHERE fecha_resolucion IS NOT NULL LIMIT 1
```

---


## 49. PHP — Eloquent: todos los métodos de consulta y CRUD

### Métodos estáticos (se llaman en la clase)

```php
// ── BUSCAR ────────────────────────────────────────────────────────────────────
Incidencia::all();
// SELECT * FROM incidencias → Collection de todos los registros

Incidencia::find(5);
// SELECT * FROM incidencias WHERE id = 5 LIMIT 1
// Retorna: el modelo o null si no existe

Incidencia::findOrFail(5);
// Igual que find() pero lanza ModelNotFoundException (404) si no existe
// Equivalente a: Incidencia::findOrFail(5) → throw 404 automático en Laravel

Incidencia::where('estado_actual', 'pendiente')->get();
// SELECT * FROM incidencias WHERE estado_actual = 'pendiente'
// Retorna: Collection (puede estar vacía, pero nunca es null)

Incidencia::where('estado_actual', 'pendiente')->first();
// ... WHERE estado_actual = 'pendiente' LIMIT 1
// Retorna: el modelo o null

Incidencia::where('estado_actual', 'pendiente')->firstOrFail();
// Igual que first() pero lanza 404 si no hay resultado

// Múltiples condiciones:
Incidencia::where('estado_actual', 'pendiente')
          ->where('prioridad', 'alta')
          ->get();
// WHERE estado_actual = 'pendiente' AND prioridad = 'alta'

Incidencia::where('estado_actual', 'pendiente')
          ->orWhere('estado_actual', 'en_proceso')
          ->get();
// WHERE estado_actual = 'pendiente' OR estado_actual = 'en_proceso'

Incidencia::whereIn('estado_actual', ['pendiente', 'en_proceso'])->get();
// WHERE estado_actual IN ('pendiente', 'en_proceso')

Incidencia::whereNotIn('estado_actual', ['resuelto'])->get();
// WHERE estado_actual NOT IN ('resuelto')

Incidencia::whereNull('fecha_resolucion')->get();
// WHERE fecha_resolucion IS NULL

Incidencia::whereNotNull('fecha_resolucion')->get();
// WHERE fecha_resolucion IS NOT NULL

Incidencia::whereBetween('created_at', [$inicio, $fin])->get();
// WHERE created_at BETWEEN ? AND ?

Incidencia::where('titulo', 'like', '%bache%')->get();
// WHERE titulo LIKE '%bache%'

Incidencia::where('titulo', 'ilike', '%bache%')->get();
// WHERE titulo ILIKE '%bache%' (solo PostgreSQL, case-insensitive)

// ── ORDENAR Y LIMITAR ─────────────────────────────────────────────────────────
Incidencia::orderBy('created_at', 'desc')->get();
// ORDER BY created_at DESC

Incidencia::latest()->get();
// ORDER BY created_at DESC (shorthand)

Incidencia::oldest()->get();
// ORDER BY created_at ASC

Incidencia::orderBy('prioridad')->orderBy('created_at', 'desc')->get();
// ORDER BY prioridad ASC, created_at DESC (ordenar por múltiples columnas)

Incidencia::take(5)->get();
// LIMIT 5 → solo los primeros 5 resultados

Incidencia::limit(5)->get();
// Equivalente a take(5)

Incidencia::skip(10)->take(5)->get();
// OFFSET 10 LIMIT 5 → página 3 si hay 5 por página

// ── CONTAR Y AGREGAR ──────────────────────────────────────────────────────────
Incidencia::count();
// SELECT COUNT(*) FROM incidencias

Incidencia::where('estado_actual', 'pendiente')->count();
// SELECT COUNT(*) FROM incidencias WHERE estado_actual = 'pendiente'

Incidencia::sum('dias_resolucion');
// SELECT SUM(dias_resolucion) FROM incidencias

Incidencia::avg('dias_resolucion');
// SELECT AVG(dias_resolucion) FROM incidencias

Incidencia::max('created_at');
// SELECT MAX(created_at) FROM incidencias

Incidencia::min('created_at');
// SELECT MIN(created_at) FROM incidencias

// ── CREAR ─────────────────────────────────────────────────────────────────────
$incidencia = Incidencia::create([
    'titulo'       => 'Bache',
    'estado_actual'=> 'pendiente',
    'usuario_id'   => $userId,
]);
// INSERT INTO incidencias (titulo, estado_actual, usuario_id, created_at, updated_at)
// VALUES ('Bache', 'pendiente', ?, NOW(), NOW())
// Solo inserta los campos que están en $fillable del modelo.

Incidencia::insert([
    ['titulo' => 'A', 'estado_actual' => 'pendiente'],
    ['titulo' => 'B', 'estado_actual' => 'pendiente'],
]);
// Inserta múltiples filas de una vez (sin disparar eventos de Eloquent).

// firstOrCreate(): buscar o crear.
$tipo = TipoIncidencia::firstOrCreate(
    ['nombre' => 'Vialidad'],        // buscar por estos campos
    ['descripcion' => 'Problemas de vías'] // extra solo al crear
);

// firstOrNew(): igual que firstOrCreate() pero no guarda automáticamente.
$incidencia = Incidencia::firstOrNew(['titulo' => 'Test']);
$incidencia->estado_actual = 'pendiente';
$incidencia->save(); // guardar manualmente

// updateOrCreate(): actualizar si existe, crear si no.
Incidencia::updateOrCreate(
    ['titulo' => 'Bache existente'],  // buscar por estos campos
    ['estado_actual' => 'resuelto'],  // campos a actualizar o asignar al crear
);

// ── ACTUALIZAR ────────────────────────────────────────────────────────────────
// Opción 1: cargar modelo y usar ->update()
$incidencia = Incidencia::find(5);
$incidencia->update(['estado_actual' => 'resuelto', 'prioridad' => 'alta']);
// UPDATE incidencias SET estado_actual = 'resuelto', prioridad = 'alta', updated_at = NOW()
// WHERE id = 5

// Opción 2: asignar propiedades y usar ->save()
$incidencia = Incidencia::find(5);
$incidencia->estado_actual = 'resuelto';
$incidencia->save();
// UPDATE incidencias SET estado_actual = 'resuelto', updated_at = NOW() WHERE id = 5
// Solo actualiza los campos que cambiaron (dirty fields).

// Opción 3: actualizar en masa (sin cargar el modelo).
Incidencia::where('estado_actual', 'pendiente')
          ->where('created_at', '<', now()->subDays(30))
          ->update(['prioridad' => 'alta']);
// UPDATE incidencias SET prioridad = 'alta' WHERE ... (sin disparar eventos de Eloquent)

// ── ELIMINAR ──────────────────────────────────────────────────────────────────
$incidencia = Incidencia::find(5);
$incidencia->delete();
// DELETE FROM incidencias WHERE id = 5

Incidencia::destroy(5);           // eliminar por ID (puede ser array: destroy([1,2,3]))
Incidencia::destroy([1, 2, 3]);   // eliminar múltiples por IDs

Incidencia::where('estado_actual', 'resuelto')
          ->where('created_at', '<', now()->subYear())
          ->delete();
// DELETE masivo (sin cargar modelos)

// ── EAGER LOADING (WITH) ──────────────────────────────────────────────────────
// Cargar relaciones junto con el modelo (evita el problema N+1).
Incidencia::with('usuario')->get();
// Hace 2 SQL: uno para incidencias, uno para todos los users relacionados.
// SIN with: haría 1 SQL para incidencias + N SQL (uno por cada incidencia para el usuario).

Incidencia::with(['usuario', 'subtipo', 'ciudad'])->get();
// Carga 3 relaciones en 4 SQL totales (mucho mejor que N*3 SQL).

Incidencia::with(['usuario', 'subtipo.tipo'])->get();
// Carga usuario y subtipo. Para subtipo, también carga su tipo.
// El punto '.' indica relación anidada (subtipo pertenece a tipo).

Incidencia::with(['comentarios' => function($q) {
    $q->orderBy('created_at', 'desc')->take(5);
}])->get();
// Carga solo los últimos 5 comentarios por incidencia.
```

---


## 46. PHP — Facades de Laravel: Hash, Cache, Storage, Auth, DB

### ¿Qué es una Facade?

Una Facade es una clase estática de Laravel que provee una interfaz simple
para acceder a servicios complejos del framework. Se usan con `::` (doble dos puntos).

```php
// Importar la facade al inicio del archivo:
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
```

---

### Hash — encriptar contraseñas

```php
// Hash::make(texto): crea un hash bcrypt irreversible del texto.
// SIEMPRE usar esto para guardar contraseñas. NUNCA guardar en texto plano.
$hashContrasena = Hash::make('mi_password_123');
// '$2y$10$tRx5....' (string bcrypt, diferente cada vez)

// Hash::check(texto, hash): verifica si el texto coincide con el hash.
// Retorna true o false. No "desencripta" — recalcula el hash y compara.
$valido = Hash::check('mi_password_123', $hashContrasena); // true
$valido = Hash::check('contraseña_mal', $hashContrasena);  // false

// En AuthController::login():
if (!Hash::check($request->password, $user->password)) {
    return response()->json(['mensaje' => 'Credenciales incorrectas.'], 401);
}

// Hash::needsRehash(hash): verifica si el hash necesita actualizarse
// (si cambió el costo de bcrypt en la configuración).
if (Hash::needsRehash($user->password)) {
    $user->password = Hash::make($request->password);
    $user->save();
}
```

---

### Cache — guardar resultados temporalmente

```php
// Cache::remember('clave', segundos, función):
//   1. ¿Hay algo en Redis con esa clave? → devuélvelo directamente.
//   2. Si no hay → ejecuta la función, guarda el resultado con la clave y devuélvelo.
// Es el patrón cache-or-compute (caché o calcular).
$datos = Cache::remember('dashboard_stats', 300, function () {
    // Esta función SOLO se ejecuta si no hay caché.
    // Los 300 segundos = 5 minutos de vigencia.
    return [
        'total'      => Incidencia::count(),
        'pendientes' => Incidencia::where('estado_actual', 'pendiente')->count(),
    ];
});

// Cache::put('clave', valor, segundos): guarda manualmente en caché.
Cache::put('mi_dato', 'valor', 600); // guarda por 10 minutos

// Cache::get('clave', 'default'): lee de caché.
$valor = Cache::get('mi_dato');          // null si no existe
$valor = Cache::get('mi_dato', 'vacio'); // 'vacio' si no existe

// Cache::has('clave'): ¿existe esta clave en caché?
if (Cache::has('dashboard_stats')) { ... }

// Cache::forget('clave'): elimina una clave del caché.
Cache::forget('dashboard_stats');
// Útil para invalidar el caché cuando los datos cambian.

// Cache::flush(): elimina TODO el caché.
// ¡Cuidado! Afecta a todas las claves de todos los usuarios.

// Cache::forever('clave', valor): guarda sin expiración (hasta que se borre manualmente).
Cache::forever('configuracion_global', $config);
```

---

### Storage — sistema de archivos

```php
// Storage::disk('nombre'): selecciona un disco de almacenamiento configurado en filesystems.php.
// 'public': archivos accesibles desde el navegador (storage/app/public/).
// 'local':  archivos privados (solo accesibles desde PHP).
// 's3':     Amazon S3 (nube).

// Guardar un archivo subido:
$ruta = $request->file('foto')->store('evidencias', 'public');
// Guarda en: storage/app/public/evidencias/
// La ruta devuelta es relativa: 'evidencias/abc123.jpg'
// La URL pública es: http://servidor/storage/evidencias/abc123.jpg

// storeAs('carpeta', 'nombre.ext', 'disco'): guardar con nombre específico.
$ruta = $request->file('foto')->storeAs('evidencias', 'foto_' . time() . '.jpg', 'public');

// Storage::disk('public')->exists('ruta'): ¿el archivo existe?
if (Storage::disk('public')->exists($ruta)) { ... }

// Storage::disk('public')->delete('ruta'): eliminar un archivo.
Storage::disk('public')->delete($incidencia->ruta_archivo);
// Usado antes de eliminar el registro de BD para no dejar archivos huérfanos.

// Storage::disk('public')->url('ruta'): obtener la URL pública.
$url = Storage::disk('public')->url($ruta);
// 'http://localhost/storage/evidencias/abc123.jpg'

// Storage::disk('public')->get('ruta'): leer el contenido de un archivo.
$contenido = Storage::disk('public')->get('archivo.txt');

// Storage::disk('public')->put('ruta', contenido): guardar contenido directamente.
Storage::disk('public')->put('reportes/reporte.txt', $contenido);

// Storage::disk('public')->move('origen', 'destino'): mover un archivo.
Storage::disk('public')->move('temporal/abc.jpg', 'evidencias/abc.jpg');
```

---

### Auth — usuario autenticado

```php
// Auth::id(): ID del usuario actualmente autenticado.
// Más corto que $request->user()->id cuando ya tienes acceso a Auth.
$miId = Auth::id(); // null si no hay sesión activa

// Auth::user(): el usuario autenticado completo (objeto User o null).
$usuario = Auth::user();

// Auth::check(): ¿hay un usuario autenticado? → true o false.
if (Auth::check()) {
    // hay sesión activa
}

// Auth::guest(): ¿no hay usuario autenticado? → true o false.
if (Auth::guest()) {
    return redirect('login');
}

// En los controladores con Sanctum se prefiere $request->user() porque
// Auth::user() puede tener problemas con múltiples guards.
// Ambos devuelven lo mismo en la mayoría de los casos.
```

---

### DB — consultas directas a la base de datos

```php
// Cuando Eloquent no es suficiente (SQL complejo, vistas, procedimientos), se usa DB.

// DB::table('tabla'): inicia un Query Builder (sin Eloquent).
// Devuelve objetos stdClass, no modelos Eloquent.
$incidencias = DB::table('incidencias')
    ->where('estado_actual', 'pendiente')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get(); // devuelve Collection de stdClass

// .join(): hacer un JOIN manual.
$datos = DB::table('incidencias')
    ->join('users', 'incidencias.usuario_id', '=', 'users.id')
    ->join('ciudades', 'incidencias.ciudad_id', '=', 'ciudades.id')
    ->select('incidencias.titulo', 'users.name', 'ciudades.nombre as ciudad')
    ->get();

// .select(): qué columnas traer.
// .selectRaw(): columna con expresión SQL.
$datos = DB::table('incidencias')
    ->selectRaw("estado_actual, COUNT(*) as total")
    ->groupBy('estado_actual')
    ->get();

// .whereRaw(): condición WHERE con SQL crudo.
->whereRaw("EXTRACT(YEAR FROM created_at) = ?", [2026])

// DB::raw('SQL'): incluir SQL crudo dentro de un Query Builder.
->select(DB::raw('COUNT(*) as total, AVG(dias) as promedio'))

// DB::statement('SQL'): ejecutar SQL que no retorna datos (CREATE, ALTER, INSERT).
DB::statement("ALTER TABLE users ADD COLUMN nuevo_campo VARCHAR(100)");

// DB::unprepared('SQL'): SQL sin binding de parámetros (para CREATE FUNCTION, triggers).
DB::unprepared("
    CREATE OR REPLACE FUNCTION mi_funcion() RETURNS trigger AS \$\$
    BEGIN ... END;
    \$\$ LANGUAGE plpgsql;
");
// Se usa en lugar de DB::statement() cuando el SQL contiene $$

// DB::select('SQL', [parametros]): ejecutar SELECT y retornar array de stdClass.
$resultado = DB::select("SELECT * FROM incidencias WHERE id = ?", [5]);

// DB::selectOne('SQL', [parametros]): igual pero retorna solo la primera fila o null.
$fila = DB::selectOne("SELECT calcular_tiempo_resolucion(?) AS dias", [(int) $id]);
// $fila->dias: acceder a la columna del resultado

// DB::insert('SQL', [parametros]): ejecutar INSERT, retorna true/false.
DB::insert("INSERT INTO roles (nombre) VALUES (?)", ['nuevo_rol']);

// DB::update('SQL', [parametros]): ejecutar UPDATE, retorna filas afectadas.
$afectadas = DB::update("UPDATE incidencias SET estado_actual = ? WHERE id = ?", ['resuelto', 5]);

// DB::delete('SQL', [parametros]): ejecutar DELETE, retorna filas eliminadas.
$eliminadas = DB::delete("DELETE FROM notificaciones WHERE usuario_id = ?", [$userId]);

// DB::transaction(función): ejecutar varias operaciones en una transacción.
// Si algo falla → rollback automático.
DB::transaction(function () {
    Incidencia::create([...]);
    Notificacion::create([...]);
    // Si alguna lanza excepción → ambas se revierten
});

// DB::beginTransaction() / DB::commit() / DB::rollBack(): manual.
DB::beginTransaction();
try {
    Incidencia::create([...]);
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
}
```

---


## 47. PHP — Carbon: fechas y tiempos

### ¿Qué es Carbon?

Carbon es la biblioteca de fechas que usa Laravel.
`now()` devuelve un objeto Carbon con la fecha y hora actual.
Todos los campos `created_at` y `updated_at` de Eloquent son automáticamente Carbon.

```php
// Importar Carbon:
use Carbon\Carbon;

// Crear instancias:
$ahora     = Carbon::now();             // fecha y hora actual (zona horaria del servidor)
$ahora     = now();                     // helper de Laravel, equivalente a Carbon::now()
$hoy       = Carbon::today();          // hoy a las 00:00:00
$manana    = Carbon::tomorrow();       // mañana a las 00:00:00
$ayer      = Carbon::yesterday();      // ayer a las 00:00:00

// Desde string:
$fecha     = Carbon::parse('2026-05-01');
$fecha     = Carbon::parse('2026-05-01 14:30:00');
$fecha     = Carbon::createFromFormat('d/m/Y', '01/05/2026');

// Aritmética de fechas (no modifica el original):
$enUnMes    = now()->addMonth();       // suma 1 mes
$enDiez     = now()->addDays(10);      // suma 10 días
$enUnaHora  = now()->addHours(1);      // suma 1 hora
$enTreinta  = now()->addMinutes(30);   // suma 30 minutos

$haceDiez   = now()->subDays(10);      // resta 10 días
$haceUnMes  = now()->subMonth();       // resta 1 mes

// ->copy(): crea una copia del objeto para no modificar el original.
$original = Carbon::now();
$futuro   = $original->copy()->addDays(5); // $original no cambia
$pasado   = $original->copy()->subDays(5);

// Comparar fechas:
$fecha1 = Carbon::parse('2026-01-01');
$fecha2 = Carbon::parse('2026-12-31');

$fecha1->isBefore($fecha2);  // true
$fecha1->isAfter($fecha2);   // false
$fecha1->isSameDay($fecha2); // false

// Diferencias:
$inicio = Carbon::parse('2026-05-01');
$fin    = Carbon::parse('2026-05-15');
$fin->diffInDays($inicio);    // 14 (días de diferencia)
$fin->diffInHours($inicio);   // 336 (horas de diferencia)
$fin->diffInMonths($inicio);  // 0

// Formatear para mostrar al usuario:
$fecha = Carbon::parse('2026-05-01 14:30:00');
$fecha->format('d/m/Y');          // '01/05/2026'
$fecha->format('d/m/Y H:i');      // '01/05/2026 14:30'
$fecha->format('Y-m-d');          // '2026-05-01' (formato para BD)
$fecha->toDateString();           // '2026-05-01'
$fecha->toDateTimeString();       // '2026-05-01 14:30:00'
$fecha->toISOString();            // '2026-05-01T14:30:00.000000Z'

// Verificar estados:
$fecha->isToday();     // ¿es hoy?
$fecha->isPast();      // ¿ya pasó?
$fecha->isFuture();    // ¿aún no ha llegado?
$fecha->isWeekend();   // ¿es sábado o domingo?

// En el seeder se usa para crear fechas históricas:
$fechaReporte   = Carbon::now()->subDays(30);  // hace 30 días
$fechaResolucion = $fechaReporte->copy()->addDays(rand(1, 10)); // 1-10 días después
```

---


## 48. PHP — Schema Builder: métodos de Blueprint

### Los métodos de `$table->` al crear tablas

```php
Schema::create('mi_tabla', function (Blueprint $table) {

    // ── COLUMNAS DE ID ────────────────────────────────────────────────────────
    $table->id();
    // Crea: id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
    // Equivalente a: $table->unsignedBigInteger('id')->autoIncrement()->primary()

    $table->uuid('id')->primary();
    // Crea: id CHAR(36) NOT NULL → para usar UUIDs como clave primaria

    // ── COLUMNAS DE TEXTO ─────────────────────────────────────────────────────
    $table->string('nombre');           // VARCHAR(255) NOT NULL
    $table->string('codigo', 10);       // VARCHAR(10) NOT NULL
    $table->text('descripcion');        // TEXT NOT NULL
    $table->mediumText('contenido');    // MEDIUMTEXT (~16 MB)
    $table->longText('payload');        // LONGTEXT (~4 GB)
    $table->char('codigo_pais', 2);     // CHAR(2) — longitud fija

    // ── COLUMNAS NUMÉRICAS ────────────────────────────────────────────────────
    $table->integer('cantidad');         // INT
    $table->unsignedInteger('edad');     // INT UNSIGNED (no negativo)
    $table->bigInteger('monto');         // BIGINT
    $table->unsignedBigInteger('user_id'); // BIGINT UNSIGNED (para FK manuales)
    $table->float('precio', 8, 2);      // FLOAT con 8 dígitos totales, 2 decimales
    $table->double('latitud');           // DOUBLE PRECISION
    $table->decimal('monto', 10, 2);    // DECIMAL exacto (para dinero)
    $table->tinyInteger('activo');       // TINYINT (0-127)
    $table->unsignedTinyInteger('intentos'); // TINYINT UNSIGNED (0-255)
    $table->smallInteger('orden');       // SMALLINT

    // ── COLUMNAS BOOLEANAS Y FECHAS ───────────────────────────────────────────
    $table->boolean('leido');            // BOOLEAN (TRUE/FALSE o TINYINT(1))
    $table->date('fecha_nacimiento');    // DATE (solo fecha, sin hora)
    $table->time('hora_inicio');         // TIME (solo hora)
    $table->dateTime('programado_para'); // DATETIME
    $table->timestamp('ultimo_acceso');  // TIMESTAMP
    $table->timestamps();
    // Crea DOS columnas: created_at TIMESTAMP NULL y updated_at TIMESTAMP NULL
    // Eloquent las llena automáticamente

    $table->softDeletes();
    // Crea: deleted_at TIMESTAMP NULL
    // Para "soft delete" (marcar como eliminado sin borrar de la BD)

    // ── MODIFICADORES ─────────────────────────────────────────────────────────
    ->nullable()            // permite NULL (por defecto las columnas son NOT NULL)
    ->default('pendiente')  // valor por defecto si no se especifica en INSERT
    ->default(false)
    ->default(0)
    ->unsigned()            // sin signo (solo positivo) — en columnas numéricas
    ->useCurrent()          // DEFAULT CURRENT_TIMESTAMP
    ->index()               // crea un índice simple en esa columna
    ->unique()              // crea un índice UNIQUE en esa columna
    ->primary()             // esta columna es la clave primaria
    ->comment('Descripción') // comentario de la columna en la BD

    // ── CLAVES FORÁNEAS ───────────────────────────────────────────────────────
    // Forma 1: columna + restricción por separado
    $table->unsignedBigInteger('usuario_id');
    $table->foreign('usuario_id')
          ->references('id')
          ->on('users')
          ->onDelete('cascade');   // ON DELETE CASCADE
          // ->onDelete('set null') // ON DELETE SET NULL
          // ->onDelete('restrict') // ON DELETE RESTRICT (default)

    // Forma 2: shorthand (más moderno)
    $table->foreignId('usuario_id')       // BIGINT UNSIGNED
          ->constrained('users')          // REFERENCES users(id)
          ->onDelete('cascade');

    // foreignId('campo') asume que la tabla referenciada es el plural del campo sin _id.
    // 'usuario_id' → buscaría tabla 'usuarios' (puede no coincidir con nombre real)
    // Con ->constrained('users'): especifica explícitamente la tabla.

    // ── COLUMNAS ESPECIALES ───────────────────────────────────────────────────
    $table->morphs('tokenable');
    // Crea DOS columnas para polimorfismo:
    //   tokenable_type VARCHAR(255) NOT NULL (nombre de la clase)
    //   tokenable_id   BIGINT UNSIGNED NOT NULL (id del modelo)
    // + un índice compuesto en ambas columnas
    // Usado en personal_access_tokens para que múltiples modelos puedan tener tokens.

    $table->rememberToken();
    // Crea: remember_token VARCHAR(100) NULL
    // Para el "recordarme" de Laravel auth con sesiones.

    // ── ÍNDICES ───────────────────────────────────────────────────────────────
    $table->index('estado_actual');                    // índice simple
    $table->index(['estado_actual', 'usuario_id']);    // índice compuesto
    $table->unique('email');                           // índice único
    $table->unique(['provincia_id', 'nombre']);        // único compuesto (ambos juntos)
    $table->primary(['incidencia_id', 'usuario_id']); // clave primaria compuesta
});

// Modificar tablas existentes:
Schema::table('users', function (Blueprint $table) {
    $table->string('telefono')->nullable()->after('email'); // agregar columna
    $table->dropColumn('telefono');                         // eliminar columna
    $table->renameColumn('viejo', 'nuevo');                 // renombrar columna
    $table->dropIndex('users_email_unique');                // eliminar índice
    $table->dropForeign(['usuario_id']);                    // eliminar FK
});

// Eliminar tabla:
Schema::dropIfExists('nombre_tabla'); // no lanza error si no existe
Schema::drop('nombre_tabla');         // lanza error si no existe
```

---


## 29. Seeders — los datos de prueba

### ¿Qué es un seeder?

Un seeder es un archivo PHP que inserta datos predefinidos en la base de datos.
Se usa para tener datos de prueba que sean siempre iguales y reproducibles.

### Los seeders del proyecto

```
DatabaseSeeder.php      → llama a todos los demás en orden
  └── UbicacionSeeder       → países, provincias y ciudades de Ecuador
  └── RolesYUsuariosSeeder  → 3 roles + usuarios de prueba
  └── IncidenciaSeeder      → incidencias de ejemplo
```

### UbicacionSeeder — datos reales de Ecuador

Inserta todas las provincias de Ecuador y sus ciudades principales:
```php
// Ejemplo de lo que inserta:
// Provincias: Guayas, Pichincha, Santa Elena, El Oro, ...
// Ciudades:   Guayaquil, Quito, La Libertad, Salinas, ...
```

### RolesYUsuariosSeeder — las cuentas de prueba

```php
// Crear los 3 roles
$adminRolId  = DB::table('roles')->insertGetId(['nombre' => 'admin']);
$normalRolId = DB::table('roles')->insertGetId(['nombre' => 'normal']);
$tecnicoRolId = DB::table('roles')->insertGetId(['nombre' => 'tecnico']);

// Crear usuarios de prueba
$usuarios = [
    ['name' => 'José Admin',    'email' => 'admin@sistema.com',    'rol_id' => $adminRolId],
    ['name' => 'Dayron Usuario','email' => 'dayron@sistema.com',   'rol_id' => $normalRolId],
    ['name' => 'Carlos Técnico','email' => 'tecnico1@sistema.com', 'rol_id' => $tecnicoRolId],
    ['name' => 'María Técnico', 'email' => 'tecnico2@sistema.com', 'rol_id' => $tecnicoRolId],
];
```

**Contraseña para todos los usuarios de prueba:** `password123`

### Cuándo correr los seeders

```bash
# Recrear todo desde cero (borra datos reales, útil en desarrollo)
docker-compose exec backend php artisan migrate:fresh --seed

# Solo insertar datos sin borrar tablas
docker-compose exec backend php artisan db:seed

# Solo un seeder específico
docker-compose exec backend php artisan db:seed --class=RolesYUsuariosSeeder
```

**Advertencia:** `migrate:fresh` borra TODOS los tokens de Sanctum.
Cualquier token guardado en el navegador o en `artillery.yml` deja de funcionar.
Después de un `migrate:fresh` hay que hacer login de nuevo para obtener un token nuevo.

---



---

# BLOQUE 9 — Laravel: funcionalidades del sistema

## 21. Roles y control de acceso — los 3 niveles del sistema

### ¿Por qué hay roles?

No todos los usuarios deben poder hacer lo mismo. Un ciudadano que reporta
un bache no debería poder borrar incidencias de otros ni ver las de todos.
Los roles resuelven esto.

### Los 3 roles del proyecto

| Rol | Quién es | Qué puede hacer |
|-----|----------|-----------------|
| `normal` | Ciudadano común, se registra solo | Ver y gestionar SUS incidencias, añadir comentarios |
| `tecnico` | Empleado municipal, lo crea el admin | Cambiar estado y prioridad de incidencias ASIGNADAS a él |
| `admin` | Administrador del sistema | TODO: ver todas las incidencias, gestionar usuarios, asignar técnicos, eliminar |

### Cómo se guarda el rol en la base de datos

Hay una tabla `roles` con filas:
```
id | nombre
---|--------
 1 | admin
 2 | normal
 3 | tecnico
```

Y la tabla `users` tiene una columna `rol_id` que apunta a esa tabla:
```
id | name          | email                    | rol_id
---|---------------|--------------------------|-------
 1 | José Admin    | admin@sistema.com        |  1
 2 | Dayron Usuario| dayron@gmail.com         |  2
 3 | Carlos Técnico| tecnico1@sistema.com     |  3
```

Es una relación `belongsTo`: el usuario pertenece a un rol.

### Cómo se crea un técnico

Los técnicos NO se pueden registrar solos desde el formulario de registro.
`AuthController::register()` siempre asigna el rol `normal`:

```php
$rolNormal = Role::where('nombre', 'normal')->first();
$user = User::create([...  'rol_id' => $rolNormal->id]);
```

Para crear un técnico, el admin debe ir a **Gestión de Usuarios** y elegir
el rol "Técnico" en el formulario. O se crean por seeder en desarrollo.

### Cómo se verifica el rol en el backend

**Opción 1 — Middleware `admin`:**
Protege rutas completas. Si no eres admin, devuelve 403 antes de llegar al controlador.

```php
// En routes/api.php:
Route::middleware('admin')->group(function () {
    Route::post('/usuarios', [UserController::class, 'store']);   // solo admin
    Route::delete('/usuarios/{user}', [UserController::class, 'destroy']); // solo admin
});

// En CheckAdmin.php (el middleware):
if (strtolower($user->role->nombre) !== 'admin') {
    return response()->json(['mensaje' => 'Acceso no autorizado.'], 403);
}
```

**Opción 2 — Verificación dentro del controlador:**
Para lógica más fina (ej: mostrar solo TUS incidencias).

```php
// En IncidenciaController:
private function esAdmin(Request $request): bool
{
    return $user->role && strtolower($user->role->nombre) === 'admin';
}

// En index():
if (!$this->esAdmin($request)) {
    $query->where('usuario_id', $request->user()->id); // solo las suyas
}
```

### Cómo se verifica el rol en el frontend

El objeto usuario guardado en `localStorage` incluye el rol:
```json
{
  "id": 1,
  "name": "José Admin",
  "email": "admin@sistema.com",
  "role": { "id": 1, "nombre": "admin" }
}
```

El JS lo lee así:
```javascript
const usuario = JSON.parse(localStorage.getItem('usuario'));
const esAdmin   = usuario.role && usuario.role.nombre === 'admin';
const esTecnico = usuario.role && usuario.role.nombre === 'tecnico';
```

**Importante:** esto solo controla la interfaz visual (qué botones se muestran).
El backend SIEMPRE verifica el rol en cada petición. Si alguien manipula el
`localStorage`, el servidor igual le dirá 403.

---


## 22. Gestión de usuarios — usuarios.html + UserController

### ¿Por qué existe esta página?

El sistema tiene 3 roles pero solo el `admin` puede crear técnicos.
`usuarios.html` es el panel de administración de cuentas: crear, editar y eliminar usuarios.
Solo los admins pueden verla (el enlace en el sidebar está oculto para los demás).

### Cómo el sidebar se muestra/oculta según el rol

En cada HTML (dashboard, incidencias, detalle) hay este código:
```html
<!-- En el sidebar, oculto por defecto -->
<div class="sb-sidenav-menu-heading" id="heading-admin" style="display:none">Administración</div>
<a class="nav-link" href="usuarios.html" id="link-usuarios" style="display:none">
    <i class="fas fa-users-cog"></i> Usuarios
</a>
```

Y en el script inline al final:
```javascript
const u = JSON.parse(localStorage.getItem('usuario') || '{}');
if (u.role && u.role.nombre === 'admin') {
    document.getElementById('heading-admin').style.display = '';  // mostrar
    document.getElementById('link-usuarios').style.display  = '';  // mostrar
}
```

Si no es admin, esos elementos quedan con `display:none` y no aparecen.

### Los endpoints de usuarios

```
GET    /api/usuarios          → listar todos (admins y técnicos lo usan)
POST   /api/usuarios          → crear usuario (solo admin)
PUT    /api/usuarios/{id}     → editar usuario (solo admin)
DELETE /api/usuarios/{id}     → eliminar usuario (solo admin)
```

### UserController.php — cómo funciona

```php
// GET /api/usuarios — listar todos con su rol
public function index()
{
    return response()->json([
        'status' => 'success',
        'data'   => User::with('role')->get(), // trae el rol en la misma consulta
    ]);
}

// POST /api/usuarios — crear un usuario nuevo
public function store(Request $request)
{
    $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|string|min:6',
        'rol'      => 'required|string',  // "admin", "normal" o "tecnico"
    ]);

    $role = Role::where('nombre', $request->rol)->first();

    $user = User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => Hash::make($request->password), // siempre encriptado
        'rol_id'   => $role->id,
    ]);

    return response()->json(['status' => 'success', 'data' => $user->load('role')], 201);
}

// PUT /api/usuarios/{id} — editar (solo los campos que lleguen)
public function update(Request $request, User $user)
{
    // filled() = el campo llegó Y no está vacío
    // Solo actualiza lo que se mandó, ignora lo que no llegó
    if ($request->filled('name'))  $user->name  = $request->name;
    if ($request->filled('email')) $user->email = $request->email;
    if ($request->filled('password')) $user->password = Hash::make($request->password);
    if ($request->filled('rol')) {
        $role = Role::where('nombre', $request->rol)->first();
        if ($role) $user->rol_id = $role->id;
    }
    $user->save();
    return response()->json(['status' => 'success', 'data' => $user->load('role')]);
}
```

### Cómo el frontend abre el modal editar vs crear

```javascript
// Variable global: si está vacío = estamos creando, si tiene valor = editando
let usuarioEditandoId = null;

function prepararModalNuevo() {
    usuarioEditandoId = null;   // modo CREAR
    document.getElementById('form-usuario').reset();
    // ...
}

function prepararModalEditar(id, nombre, email, rol) {
    usuarioEditandoId = id;     // modo EDITAR
    document.getElementById('u-nombre').value = nombre;
    // ...
}

async function guardarUsuario(e) {
    e.preventDefault();
    if (usuarioEditandoId) {
        // EDITAR: PUT al ID existente
        await apiFetch(`/usuarios/${usuarioEditandoId}`, { method: 'PUT', body: ... });
    } else {
        // CREAR: POST sin ID
        await apiFetch('/usuarios', { method: 'POST', body: ... });
    }
}
```

---


## 23. Sistema de asignaciones de técnicos

### ¿Para qué sirve?

Cuando una incidencia llega, el admin debe asignarle un técnico para que la resuelva.
La tabla `asignaciones_incidencia` conecta incidencias con técnicos.

### La tabla en la base de datos

```
asignaciones_incidencia
  id          → identificador único
  incidencia_id → cuál incidencia
  usuario_id  → cuál técnico (debe ser rol=tecnico)
  rol_asignado → "responsable" (el que resuelve) o "apoyo" (asistente)
```

Una incidencia puede tener varios técnicos asignados (uno responsable, varios de apoyo).

### Los endpoints de asignaciones

```
GET    /api/incidencias/{id}/asignaciones       → ver quiénes están asignados
POST   /api/incidencias/{id}/asignaciones       → asignar un técnico
DELETE /api/incidencias/{id}/asignaciones/{aid} → quitar un técnico
```

### Cómo el frontend filtra solo técnicos en el dropdown

```javascript
async function cargarTecnicosEditar() {
    const resp = await apiFetch('/usuarios');               // trae TODOS los usuarios
    const tecnicos = resp.data.filter(u =>                 // filtra solo los técnicos
        u.role && u.role.nombre === 'tecnico'
    );
    // Solo los técnicos aparecen en el select de asignación
    tecnicos.forEach(u => {
        const o = document.createElement('option');
        o.value = u.id; o.textContent = u.name;
        select.appendChild(o);
    });
}
```

### Notificaciones automáticas al cambiar estado

Cuando el admin cambia el estado de una incidencia, el backend automáticamente
crea una notificación para CADA técnico asignado:

```php
// En IncidenciaController::update():
if ($request->filled('estado_actual') && $request->estado_actual !== $estadoAnterior) {
    foreach ($incidencia->asignaciones()->get() as $asignacion) {
        Notificacion::create([
            'usuario_id' => $asignacion->usuario_id,
            'mensaje'    => "La incidencia '{$incidencia->titulo}' cambió de '{$estadoAnterior}' a '{$incidencia->estado_actual}'.",
            'leido'      => false,
        ]);
    }
}
```

El técnico verá el número rojo en la campana del navbar. Al hacer clic, verá
el mensaje. Al leerlo, el badge desaparece.

---


## 28. PHPUnit — cómo se testea el backend

### ¿Qué es PHPUnit?

PHPUnit es el framework de testing estándar de PHP. Laravel lo incluye por defecto.
Los tests están en `backend/tests/Feature/IncidenciaApiTest.php`.

### Qué tipo de tests tenemos

Son **Feature Tests** (tests de integración): no testean funciones sueltas,
sino el flujo completo de una petición HTTP a través de toda la aplicación.

```
Test envía petición HTTP → pasa por Middleware → Controlador → BD → respuesta
```

### Estructura de un test

```php
class IncidenciaApiTest extends TestCase
{
    use RefreshDatabase; // recrear la BD limpia antes de CADA test

    private $token; // token reutilizable entre tests

    protected function setUp(): void
    {
        parent::setUp();

        // Crear un admin de prueba
        $adminRole = Role::create(['nombre' => 'admin']);
        $admin = User::factory()->create(['rol_id' => $adminRole->id]);
        $this->token = $admin->createToken('test')->plainTextToken;
    }

    public function test_listar_incidencias_requiere_autenticacion(): void
    {
        // Sin token → 401
        $respuesta = $this->getJson('/api/incidencias');
        $respuesta->assertStatus(401);
    }

    public function test_admin_puede_listar_incidencias(): void
    {
        // Con token de admin → 200
        $respuesta = $this->withToken($this->token)
                         ->getJson('/api/incidencias');
        $respuesta->assertStatus(200)
                  ->assertJsonStructure(['status', 'data']); // verificar estructura
    }
}
```

### Comandos para correr los tests

```bash
# Correr todos los tests con formato legible
docker-compose exec backend php artisan test --testdox

# Solo los tests de incidencias
docker-compose exec backend php artisan test --filter=IncidenciaApiTest
```

### RefreshDatabase — por qué la BD de tests está limpia

`RefreshDatabase` envuelve cada test en una transacción y la revierte al final.
Así los tests no se "ensucian" entre sí. Cada test empieza con BD vacía.

---



---

# BLOQUE 10 — Base de datos: SQL y PostgreSQL

## 40. PostgreSQL — SQL nativo en detalle

### Tipos de datos usados en el proyecto

```sql
-- BIGSERIAL: entero grande (BIGINT) que se autoincrementa automáticamente.
-- PostgreSQL crea una secuencia interna y asigna el siguiente valor en cada INSERT.
-- Equivalente MySQL: BIGINT AUTO_INCREMENT.
id BIGSERIAL PRIMARY KEY

-- VARCHAR(n): cadena de texto de hasta n caracteres.
-- Para texto corto con límite conocido. Más eficiente que TEXT para textos cortos.
nombre VARCHAR(100) NOT NULL
email  VARCHAR(255) NOT NULL

-- TEXT: cadena de texto sin límite de longitud.
-- Para textos largos: descripciones, mensajes de error, comentarios.
descripcion TEXT
error_detalle TEXT NOT NULL

-- BOOLEAN: true o false.
leido BOOLEAN NOT NULL DEFAULT FALSE

-- NUMERIC(p, s): número decimal con precisión exacta.
-- p = total de dígitos, s = dígitos después del punto.
-- NUMERIC(10, 8): hasta 10 dígitos totales con 8 decimales → 99.99999999
latitud  NUMERIC(10, 8) NOT NULL
longitud NUMERIC(11, 8) NOT NULL

-- TIMESTAMP: fecha y hora sin zona horaria.
-- NULL: puede no tener valor (campos opcionales).
created_at TIMESTAMP NULL
fecha_resolucion TIMESTAMP NULL

-- BIGINT: entero grande (sin autoincremento).
-- Usado para columnas de clave foránea (FK).
usuario_id BIGINT NOT NULL
```

---

### Restricciones de integridad

```sql
-- PRIMARY KEY: clave primaria. Debe ser único y no puede ser NULL.
-- Identifica unívocamente cada fila de la tabla.
id BIGSERIAL PRIMARY KEY

-- NOT NULL: el campo no puede estar vacío.
-- Un INSERT sin este campo o con NULL en él fallará.
nombre VARCHAR(100) NOT NULL

-- UNIQUE: no puede haber dos filas con el mismo valor en esta columna.
-- PostgreSQL crea automáticamente un índice UNIQUE.
email VARCHAR(255) NOT NULL UNIQUE

-- DEFAULT valor: valor por defecto si no se especifica en el INSERT.
estado_actual VARCHAR(20) NOT NULL DEFAULT 'pendiente'
leido BOOLEAN NOT NULL DEFAULT FALSE

-- CHECK (condición): restricción que verifica que los valores cumplan la condición.
-- PostgreSQL rechaza cualquier INSERT o UPDATE que viole el CHECK.
estado_actual VARCHAR(20) NOT NULL DEFAULT 'pendiente'
    CHECK (estado_actual IN ('pendiente', 'en_proceso', 'resuelto'))
prioridad VARCHAR(20) NOT NULL DEFAULT 'media'
    CHECK (prioridad IN ('baja', 'media', 'alta'))

-- REFERENCES tabla(columna): clave foránea.
-- El valor en esta columna debe existir en la tabla referenciada.
-- Si se intenta insertar un valor que no existe → error de FK.
usuario_id BIGINT NOT NULL REFERENCES users(id)
ciudad_id  BIGINT NOT NULL REFERENCES ciudades(id)

-- ON DELETE CASCADE: si se borra la fila referenciada, borrar también esta.
-- Ejemplo: si se borra una incidencia, sus comentarios se borran automáticamente.
incidencia_id BIGINT NOT NULL REFERENCES incidencias(id) ON DELETE CASCADE

-- ON DELETE SET NULL: si se borra la fila referenciada, poner NULL en esta columna.
-- Ejemplo: si se borra un usuario, usuario_id en bitacora_errores queda NULL.
-- La fila con el error NO se borra, solo pierde la referencia al usuario.
usuario_id BIGINT REFERENCES users(id) ON DELETE SET NULL
```

---

### ALTER TABLE — modificar tablas existentes

```sql
-- ALTER TABLE ... ADD COLUMN: agrega una columna a una tabla que ya existe.
-- Se usa cuando la tabla ya tiene datos y no se puede recrear.
ALTER TABLE users
    ADD COLUMN rol_id BIGINT NULL REFERENCES roles(id) ON DELETE SET NULL;
-- Después de ejecutar esto, todos los usuarios existentes tendrán rol_id = NULL.

-- ALTER TABLE ... DROP COLUMN: elimina una columna.
ALTER TABLE users DROP COLUMN IF EXISTS rol_id;
-- IF EXISTS: no lanza error si la columna ya fue eliminada.

-- ALTER TABLE ... RENAME COLUMN: renombrar una columna.
ALTER TABLE incidencias RENAME COLUMN estado TO estado_actual;

-- DROP TABLE ... CASCADE: elimina la tabla.
-- CASCADE: también elimina las tablas que tengan FK hacia esta tabla.
DROP TABLE IF EXISTS incidencias CASCADE;
-- IF EXISTS: no lanza error si la tabla no existe.
```

---

### Funciones SQL específicas de PostgreSQL

```sql
-- NOW(): fecha y hora actuales del servidor de base de datos.
-- Se usa en triggers y procedimientos para asignar timestamps.
INSERT INTO historial_estados (created_at) VALUES (NOW());

-- EXTRACT(EPOCH FROM intervalo): extrae los segundos de un intervalo de tiempo.
-- EPOCH es el número de segundos desde el 1 de enero de 1970.
-- Para calcular días entre dos timestamps:
EXTRACT(EPOCH FROM (fecha_resolucion::timestamp - created_at::timestamp)) / 86400
-- (t2 - t1)          → intervalo de tiempo
-- EXTRACT(EPOCH ...) → convierte el intervalo a segundos
-- / 86400            → divide entre segundos del día (60*60*24) = días

-- ::timestamp: conversión de tipo (cast). Asegura que se trate como timestamp.
fecha_resolucion::timestamp

-- ROUND(número, decimales): redondea a N decimales.
ROUND(AVG(dias)::numeric, 2) -- redondea a 2 decimales

-- ILIKE: LIKE pero sin distinción de mayúsculas/minúsculas (solo PostgreSQL).
-- LIKE: busca exactamente el patrón (sensible a mayúsculas).
-- ILIKE: busca ignorando mayúsculas.
WHERE titulo ILIKE '%bache%'
-- Encuentra: 'Bache grande', 'BACHE en la avenida', 'hay un bache aquí'

-- % en LIKE/ILIKE: comodín que coincide con cualquier texto.
'%bache%'  → cualquier string que CONTENGA 'bache'
'bache%'   → cualquier string que EMPIECE CON 'bache'
'%bache'   → cualquier string que TERMINE CON 'bache'

-- IS DISTINCT FROM: comparación NULL-safe. Diferente a <> que no funciona con NULL.
-- NULL <> NULL   → NULL (resultado indeterminado)
-- NULL IS DISTINCT FROM NULL → false (son iguales porque ambos son NULL)
-- NULL IS DISTINCT FROM 5    → true (son diferentes)
IF v_reportador_id IS DISTINCT FROM NEW.usuario_id THEN ... END IF;
```

---

### Triggers — código automático en la BD

```sql
-- Un trigger tiene dos partes:
-- 1. La FUNCIÓN que contiene la lógica (RETURNS trigger).
-- 2. El TRIGGER que la conecta a un evento en una tabla.

-- PARTE 1: Crear la función
CREATE OR REPLACE FUNCTION fn_nombre_funcion()
RETURNS trigger  -- firma obligatoria para funciones de trigger
AS $$            -- $$ delimita el cuerpo de la función
DECLARE
    v_variable TIPO;  -- variables locales de la función
BEGIN
    -- Aquí va la lógica.
    -- NEW: la fila CON los nuevos valores (disponible en INSERT y UPDATE).
    -- OLD: la fila CON los valores anteriores (disponible en UPDATE y DELETE).
    
    IF NEW.campo IS DISTINCT FROM OLD.campo THEN
        -- Solo actuar si el campo realmente cambió
        INSERT INTO otra_tabla (...) VALUES (NEW.id, ...);
    END IF;
    
    RETURN NEW;  -- OBLIGATORIO en BEFORE triggers (retorna la fila modificada).
                 -- En AFTER triggers retorna NEW o NULL (no importa cuál).
END;
$$ LANGUAGE plpgsql;  -- lenguaje usado (PL/pgSQL = procedural PostgreSQL)

-- PARTE 2: Registrar el trigger
CREATE TRIGGER nombre_trigger
BEFORE UPDATE ON incidencias  -- BEFORE: se ejecuta ANTES de guardar el cambio.
                              -- AFTER: se ejecuta DESPUÉS de guardarlo.
FOR EACH ROW                  -- se ejecuta una vez por cada fila afectada.
EXECUTE FUNCTION fn_nombre_funcion();

-- Tipos de trigger según el momento:
-- BEFORE: puede modificar los valores antes de que se guarden (modifica NEW).
-- AFTER: se usa cuando solo necesitas reaccionar al cambio (no modificar la fila).
-- Cuándo usar cada uno:
-- BEFORE: fn_fecha_resolucion_automatica (modifica fecha_resolucion en la misma fila).
-- AFTER: fn_registrar_cambio_estado (inserta en otra tabla, no modifica la incidencia).

-- Eliminar un trigger:
DROP TRIGGER IF EXISTS nombre_trigger ON incidencias;
DROP FUNCTION IF EXISTS fn_nombre_funcion();
```

---

### Procedimientos almacenados (PROCEDURE)

```sql
-- Un PROCEDURE es un bloque de código SQL con nombre que se llama explícitamente.
-- Diferencias con FUNCTION:
--   FUNCTION: retorna un valor con RETURN. Se llama con SELECT.
--   PROCEDURE: no retorna valor. Se llama con CALL.
--   PROCEDURE puede hacer COMMIT/ROLLBACK (control de transacciones).

CREATE OR REPLACE PROCEDURE nombre_procedimiento(
    parametro1 TIPO,    -- parámetros de entrada
    parametro2 TIPO DEFAULT 'valor_default'  -- con valor por defecto
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_variable TIPO;
BEGIN
    -- Validaciones y lógica
    SELECT columna INTO v_variable FROM tabla WHERE id = parametro1;
    
    -- NOT FOUND: true si el SELECT anterior no encontró ninguna fila.
    IF NOT FOUND THEN
        RAISE EXCEPTION 'El registro % no existe', parametro1;
        -- RAISE EXCEPTION: lanza un error que cancela el procedimiento.
        -- El mensaje puede incluir valores con %.
    END IF;
    
    -- EXISTS: verifica si una subconsulta devuelve al menos una fila.
    IF EXISTS (SELECT 1 FROM tabla WHERE columna = parametro1) THEN
        RAISE EXCEPTION 'Ya existe';
    END IF;
    
    -- INSERT con SELECT: inserta una fila por cada fila del SELECT.
    INSERT INTO notificaciones (usuario_id, mensaje)
    SELECT usuario_id, 'Tu mensaje'
    FROM asignaciones
    WHERE incidencia_id = parametro1;
END;
$$;

-- Llamar el procedimiento desde PHP:
DB::statement('CALL nombre_procedimiento(?, ?)', [(int) $id, (int) $userId]);
-- Se usa (int) para forzar el tipo a INTEGER (evitar errores de tipo en PostgreSQL).

-- Eliminar:
DROP PROCEDURE IF EXISTS nombre_procedimiento(INTEGER, INTEGER);
-- Deben especificarse los tipos de los parámetros para identificar la firma.
```

---

### Vistas (VIEW) e Índices (INDEX)

```sql
-- VISTA: consulta SQL guardada con nombre. No almacena datos, ejecuta el SELECT en tiempo real.
CREATE OR REPLACE VIEW nombre_vista AS
SELECT
    i.id,
    i.titulo,
    u.name AS reportado_por,
    t.nombre AS tipo
FROM incidencias i
LEFT JOIN users u ON i.usuario_id = u.id
LEFT JOIN subtipos_incidencia si ON i.subtipo_id = si.id
LEFT JOIN tipos_incidencia t ON si.tipo_id = t.id;

-- Usar la vista como si fuera una tabla:
SELECT * FROM nombre_vista WHERE tipo = 'Vialidad';
-- En Laravel: DB::table('nombre_vista')->where('tipo', 'Vialidad')->get();

-- LEFT JOIN vs INNER JOIN:
-- INNER JOIN: solo incluye filas que tienen coincidencia en AMBAS tablas.
-- LEFT JOIN: incluye TODAS las filas de la tabla izquierda, con NULL en las columnas
--            de la derecha si no hay coincidencia. Más seguro para datos opcionales.

-- GROUP BY: agrupa filas con el mismo valor en una columna para aplicar funciones de agregación.
SELECT tipo, COUNT(*) AS total, AVG(dias) AS promedio
FROM incidencias
GROUP BY tipo;  -- un resultado por cada valor único de 'tipo'
-- Funciones de agregación: COUNT(), SUM(), AVG(), MIN(), MAX()

-- ÍNDICE: estructura que acelera la búsqueda en una columna.
-- Sin índice: full table scan (leer todas las filas).
-- Con índice: búsqueda directa en árbol B+ (logarítmica).

CREATE INDEX IF NOT EXISTS idx_incidencias_estado ON incidencias (estado_actual);
-- IF NOT EXISTS: no lanza error si el índice ya existe.
-- idx_nombre: convención de nombres: idx_tabla_columna.

-- Cuándo crear índices:
-- ✅ En columnas frecuentes en WHERE: estado_actual, usuario_id, incidencia_id.
-- ✅ En FK (columnas de clave foránea).
-- ✅ En columnas usadas en ORDER BY y JOIN.
-- ❌ No en tablas muy pequeñas (< 1000 filas, el scan es más rápido que el índice).
-- ❌ No en columnas que se escriben muy frecuentemente (el índice se actualiza con cada write).

DROP INDEX IF EXISTS idx_incidencias_estado;
-- Para eliminar el índice (solo el índice, los datos no se tocan).
```

---


## 50. SQL — Funciones de agregación y CASE WHEN

### Funciones de agregación

```sql
-- Las funciones de agregación calculan un valor sobre un CONJUNTO de filas.
-- Se usan con GROUP BY para calcular por grupo, o sin él para toda la tabla.

COUNT(*):           -- cuenta todas las filas (incluye NULL)
COUNT(columna):     -- cuenta filas donde la columna NO es NULL
COUNT(DISTINCT col): -- cuenta valores únicos de esa columna
SUM(columna):       -- suma todos los valores de la columna
AVG(columna):       -- promedio (ignora NULL automáticamente)
MAX(columna):       -- el valor más alto
MIN(columna):       -- el valor más bajo

-- Ejemplos:
SELECT COUNT(*) FROM incidencias;
-- total de incidencias

SELECT COUNT(*) FROM incidencias WHERE estado_actual = 'pendiente';
-- total de incidencias pendientes

SELECT estado_actual, COUNT(*) as total
FROM incidencias
GROUP BY estado_actual;
-- total por cada estado: una fila por 'pendiente', una por 'en_proceso', una por 'resuelto'

SELECT AVG(EXTRACT(EPOCH FROM (fecha_resolucion - created_at)) / 86400) AS dias_promedio
FROM incidencias
WHERE fecha_resolucion IS NOT NULL;
-- promedio de días de resolución (solo para incidencias ya resueltas)
```

---

### CASE WHEN — condicionales dentro de SQL

```sql
-- CASE WHEN es el if/else de SQL.
-- Permite retornar valores diferentes según condiciones.

-- Forma simple:
SELECT
    titulo,
    CASE estado_actual
        WHEN 'pendiente'  THEN 'Sin atender'
        WHEN 'en_proceso' THEN 'En curso'
        WHEN 'resuelto'   THEN 'Finalizado'
        ELSE 'Desconocido'
    END AS estado_legible
FROM incidencias;

-- Forma buscada (más flexible, permite condiciones complejas):
SELECT
    titulo,
    CASE
        WHEN estado_actual = 'resuelto' AND prioridad = 'alta' THEN 'Urgente resuelto'
        WHEN estado_actual = 'resuelto'                        THEN 'Resuelto'
        WHEN prioridad = 'alta'                                THEN 'Urgente pendiente'
        ELSE 'Normal pendiente'
    END AS categoria
FROM incidencias;

-- CASE WHEN con COUNT (el patrón usado en la vista v_metricas_por_tipo):
SELECT
    ti.nombre AS tipo,
    COUNT(*) AS total,
    COUNT(CASE WHEN i.estado_actual = 'pendiente'  THEN 1 END) AS pendientes,
    COUNT(CASE WHEN i.estado_actual = 'en_proceso' THEN 1 END) AS en_proceso,
    COUNT(CASE WHEN i.estado_actual = 'resuelto'   THEN 1 END) AS resueltas
FROM incidencias i
JOIN subtipos_incidencia si ON i.subtipo_id = si.id
JOIN tipos_incidencia ti ON si.tipo_id = ti.id
GROUP BY ti.nombre;

-- Explicación de COUNT(CASE WHEN ...):
-- CASE WHEN i.estado_actual = 'pendiente' THEN 1 END
--   → devuelve 1 si es pendiente, NULL si no
-- COUNT(NULL) → no cuenta
-- COUNT(1)    → cuenta
-- Resultado: cuenta solo las filas que cumplan la condición.

-- COALESCE(valor1, valor2, ...): retorna el primer valor que NO sea NULL.
-- Equivalente SQL del ?? de JavaScript.
SELECT COALESCE(fecha_resolucion, created_at) AS fecha_referencia FROM incidencias;
-- Si fecha_resolucion es NULL → usa created_at

SELECT COALESCE(AVG(dias), 0) AS promedio FROM incidencias;
-- Si no hay incidencias (AVG daría NULL) → retorna 0

-- NULLIF(valor1, valor2): retorna NULL si valor1 = valor2, sino retorna valor1.
-- Útil para evitar división por cero.
SELECT 100 / NULLIF(total, 0) AS porcentaje FROM estadisticas;
-- Si total = 0 → NULLIF devuelve NULL → la división es NULL (no error)
-- Si total > 0 → la división se hace normalmente
```

---

### JOINs — tipos y cuándo usar cada uno

```sql
-- Tenemos: incidencias (puede tener usuario_id nulo o FK válida) y users.

-- INNER JOIN: solo devuelve filas con coincidencia en AMBAS tablas.
SELECT i.titulo, u.name
FROM incidencias i
INNER JOIN users u ON i.usuario_id = u.id;
-- Excluye las incidencias donde usuario_id no existe en users.

-- LEFT JOIN: todas las filas de la tabla IZQUIERDA, con NULL en las columnas de la derecha si no hay coincidencia.
SELECT i.titulo, u.name
FROM incidencias i
LEFT JOIN users u ON i.usuario_id = u.id;
-- Incluye TODAS las incidencias. Si una incidencia no tiene usuario → u.name = NULL.
-- Es el más seguro cuando la relación puede ser opcional.

-- RIGHT JOIN: todas las filas de la tabla DERECHA (menos usado).
-- FULL OUTER JOIN: todas las filas de ambas tablas, NULL donde no hay coincidencia.

-- SELF JOIN: una tabla unida consigo misma (ej: empleados con su jefe).
SELECT e.nombre, jefe.nombre AS nombre_jefe
FROM empleados e
LEFT JOIN empleados jefe ON e.jefe_id = jefe.id;

-- En el proyecto se usan LEFT JOINs en las vistas porque algunas relaciones pueden ser NULL.
-- Se usan INNER JOINs (JOIN a secas) en las vistas de métricas donde la relación siempre existe.
```

---

### Subqueries (subconsultas)

```sql
-- Una subconsulta es un SELECT dentro de otro SELECT, WHERE, o FROM.

-- En WHERE (para filtrar con el resultado de otra consulta):
SELECT * FROM incidencias
WHERE usuario_id IN (
    SELECT id FROM users WHERE rol_id = (
        SELECT id FROM roles WHERE nombre = 'normal'
    )
);
-- Trae incidencias de usuarios con rol 'normal'.
-- En el proyecto se usa whereHas() de Eloquent en lugar de subconsultas en PHP.

-- En FROM (como tabla temporal):
SELECT promedio.tipo, promedio.dias
FROM (
    SELECT ti.nombre AS tipo, AVG(dias) AS dias
    FROM incidencias i
    JOIN subtipos_incidencia si ON i.subtipo_id = si.id
    JOIN tipos_incidencia ti ON si.tipo_id = ti.id
    WHERE i.fecha_resolucion IS NOT NULL
    GROUP BY ti.nombre
) AS promedio
WHERE promedio.dias > 5;

-- EXISTS (como en los procedimientos almacenados):
IF EXISTS (
    SELECT 1 FROM asignaciones_incidencia
    WHERE incidencia_id = p_id AND usuario_id = p_usuario
) THEN
    RAISE EXCEPTION 'Ya está asignado';
END IF;
-- SELECT 1: no importa qué retorna, solo si retorna algo (es más rápido que SELECT *).
-- EXISTS termina en la primera coincidencia encontrada.
```

---

> Este archivo crece con el proyecto.

---


## 26. Trigger SQL — historial de estados automático

### ¿Qué es un trigger?

Un trigger es código SQL que el motor de la base de datos ejecuta automáticamente
cuando ocurre algo en una tabla (un INSERT, UPDATE o DELETE).

En este proyecto: **cuando cambia `estado_actual` en `incidencias`, PostgreSQL
automáticamente guarda el cambio en `historial_estados`.**

No necesitamos hacerlo en el código PHP. La base de datos lo hace sola.

### Cómo está definido

Está en la migración `2026_05_09_200500_create_trigger_historial_estados.php`:

```php
DB::unprepared("
    -- Función que se ejecutará cuando el trigger se active
    CREATE OR REPLACE FUNCTION registrar_cambio_estado()
    RETURNS TRIGGER AS \$\$
    BEGIN
        -- NEW = la fila con los nuevos valores después del UPDATE
        -- OLD = la fila con los valores anteriores al UPDATE
        IF NEW.estado_actual <> OLD.estado_actual THEN
            -- Solo si el estado REALMENTE cambió (evitar registros duplicados)
            INSERT INTO historial_estados (incidencia_id, estado_anterior, estado_nuevo, created_at, updated_at)
            VALUES (NEW.id, OLD.estado_actual, NEW.estado_actual, NOW(), NOW());
        END IF;
        RETURN NEW;
    END;
    \$\$ LANGUAGE plpgsql;

    -- Asociar la función al evento UPDATE en la tabla incidencias
    CREATE TRIGGER trigger_historial_estados
    AFTER UPDATE ON incidencias           -- 'después de un UPDATE'
    FOR EACH ROW                          -- 'para cada fila modificada'
    EXECUTE FUNCTION registrar_cambio_estado();
");
```

### Por qué es importante

Sin trigger, cada vez que el backend cambia el estado tendría que hacer
DOS operaciones: actualizar `incidencias` Y insertar en `historial_estados`.
Si falla la segunda operación, el historial queda incompleto.

Con el trigger, es **una sola operación atómica**: PostgreSQL garantiza que
ambas cosas pasan juntas o ninguna pasa.

---


## 27. Vistas y funciones SQL avanzadas

### ¿Por qué SQL avanzado en un proyecto Laravel?

La rúbrica del proyecto exigía demostrar conocimiento de:
- Vistas SQL (tablas virtuales)
- Funciones almacenadas (lógica en la BD)
- Índices (optimización)

Están definidas en `2026_05_16_170000_create_vistas_funcion_indices_sql.php`.

### Vista: `vista_incidencias_completa`

Una vista es como una consulta guardada con nombre. En vez de escribir el
JOIN complejo cada vez, se consulta la vista como si fuera una tabla:

```sql
CREATE VIEW vista_incidencias_completa AS
SELECT
    i.id,
    i.titulo,
    i.estado_actual,
    i.prioridad,
    u.name AS nombre_reportador,
    c.nombre AS ciudad,
    t.nombre AS tipo
FROM incidencias i
LEFT JOIN users u ON u.id = i.usuario_id
LEFT JOIN ciudades c ON c.id = i.ciudad_id
LEFT JOIN subtipos_incidencia si ON si.id = i.subtipo_id
LEFT JOIN tipos_incidencia t ON t.id = si.tipo_id;

-- Ahora puedes hacer simplemente:
SELECT * FROM vista_incidencias_completa WHERE estado_actual = 'pendiente';
-- En vez del JOIN complejo
```

### Función almacenada: `contar_incidencias_por_estado`

```sql
CREATE FUNCTION contar_incidencias_por_estado(estado_param VARCHAR)
RETURNS INTEGER AS $$
BEGIN
    RETURN (
        SELECT COUNT(*) FROM incidencias
        WHERE estado_actual = estado_param
    );
END;
$$ LANGUAGE plpgsql;

-- Uso: SELECT contar_incidencias_por_estado('pendiente'); → devuelve 5
```

### Índices: para búsquedas más rápidas

```sql
-- Sin índice: PostgreSQL revisa CADA fila de la tabla (lento con millones de filas)
-- Con índice: salta directo a las filas relevantes (rápido)

CREATE INDEX idx_incidencias_estado  ON incidencias(estado_actual);
CREATE INDEX idx_incidencias_usuario ON incidencias(usuario_id);
```

---



---

# BLOQUE 11 — Infraestructura: Docker, Redis y pruebas de carga

## 10. Redis

Redis es una base de datos que guarda todo en la memoria RAM (no en disco).
Es mucho más rápida que PostgreSQL pero los datos son temporales.

**¿Para qué lo usamos?** Para el dashboard. Calcular estadísticas implica
varias consultas pesadas. Con Redis lo calculamos UNA VEZ y lo guardamos
5 minutos. Si 50 personas abren el dashboard en esos 5 minutos, solo se
hace el cálculo UNA vez, no 50 veces.

```
Sin Redis:
Persona abre dashboard --> 6 consultas SQL --> tarda 500ms --> respuesta

Con Redis (primera persona):
Persona abre dashboard --> 6 consultas SQL --> guarda en Redis --> respuesta

Con Redis (las siguientes personas, dentro de 5 min):
Persona abre dashboard --> lee de Redis (sin tocar la BD) --> tarda 10ms --> respuesta
```

---


## 11. Docker

Docker permite que todo el sistema corra dentro de **contenedores**,
que son como mini-computadoras virtuales dentro de tu PC.

```
docker-compose.yml agrupa:

  Contenedor "db"       --> PostgreSQL (la base de datos)
  Contenedor "redis"    --> Redis (la caché)
  Contenedor "backend"  --> Laravel (el backend, 2 copias)
  Contenedor "frontend" --> Nginx (sirve los archivos HTML)
  Contenedor "pgadmin"  --> Interfaz visual para ver la BD
```

Todos viven en la misma red virtual y pueden comunicarse entre sí.

**Comandos esenciales:**
```bash
# Levantar todo
docker-compose up -d --build

# Ver qué está corriendo
docker-compose ps

# Ejecutar comandos dentro del contenedor backend
docker-compose exec backend php artisan migrate:fresh --seed

# Ver errores del backend
docker-compose logs backend

# Apagar todo
docker-compose down
```

---


## 51. Docker Compose — estructura y sintaxis del docker-compose.yml

Docker Compose es la herramienta que levanta varios contenedores a la vez con un solo comando.
Un **contenedor** es como una mini-computadora virtual aislada que corre un solo programa
(la base de datos, el backend, etc.). El archivo `docker-compose.yml` describe qué
contenedores levantar y cómo deben comunicarse entre sí.

---

### La estructura general del archivo

```yaml
services:          # lista de contenedores que vas a levantar
  nombre_servicio: # tú le pones el nombre (db, backend, redis...)
    image: ...     # qué imagen Docker usar
    ports: ...     # qué puertos exponer
    environment:   # variables de entorno (contraseñas, configuración)
    volumes: ...   # dónde guardar los datos persistentes
    depends_on:    # qué otros servicios deben arrancar primero

volumes:           # declara los volúmenes nombrados que usas arriba
  nombre_volumen:
```

---

### `image` vs `build` — cuándo usar cada uno

```yaml
# image: usa una imagen lista de Docker Hub (no necesitas código propio)
db:
  image: postgres:15       # descarga PostgreSQL versión 15 de Docker Hub
                            # el formato es nombre:version

redis:
  image: redis:7-alpine    # alpine = versión mínima, pesa menos

# build: construye la imagen desde un Dockerfile en tu proyecto
backend:
  build: ./backend          # busca el Dockerfile dentro de la carpeta ./backend
                             # úsalo cuando el contenedor corre TU código (Laravel)
```

**Regla simple:** si es un servicio estándar (PostgreSQL, Redis, Nginx) → `image`.
Si es tu propio código (el backend Laravel) → `build`.

---

### `ports` — exponer puertos al exterior

```yaml
ports:
  - "5432:5432"   # "puerto_en_tu_PC:puerto_dentro_del_contenedor"
  - "8000:8000"
  - "5050:80"     # PgAdmin corre en el puerto 80 dentro del contenedor,
                  # pero tú lo abres en localhost:5050
```

- El número de la **izquierda** es el que escribes en el navegador (`localhost:5050`)
- El número de la **derecha** es el que usa el programa dentro del contenedor
- Si son iguales, ambos son el mismo número (lo más común)

---

### `environment` — variables de entorno

```yaml
db:
  environment:
    POSTGRES_DB:       incidencias_db   # nombre de la base de datos a crear
    POSTGRES_USER:     postgres         # usuario de la BD
    POSTGRES_PASSWORD: secret           # contraseña de la BD

backend:
  environment:
    DB_CONNECTION: pgsql
    DB_HOST:       db        # ← NO es localhost, es el NOMBRE del servicio "db"
    DB_PORT:       5432
    DB_DATABASE:   incidencias_db
    DB_USERNAME:   postgres
    DB_PASSWORD:   secret
```

**Por qué `DB_HOST=db` y no `localhost`:**
Dentro de Docker Compose, cada contenedor tiene su propio `localhost`.
Si el backend pusiera `DB_HOST=localhost`, estaría buscando PostgreSQL
dentro de su propio contenedor, donde no existe.
Docker Compose crea una red virtual entre contenedores donde cada uno
es alcanzable por su **nombre de servicio**. Por eso `db` funciona como hostname.

---

### `volumes` — datos persistentes

Sin volúmenes, cuando apagas `docker-compose down`, **todos los datos se pierden**.
Los volúmenes guardan los datos fuera del contenedor para que sobrevivan reinicios.

```yaml
db:
  volumes:
    - pgdata:/var/lib/postgresql/data
    #   ↑                ↑
    #   nombre del       ruta DENTRO del contenedor donde PostgreSQL
    #   volumen          guarda sus datos

volumes:         # declara los volúmenes nombrados al final del archivo
  pgdata:        # Docker crea y gestiona este volumen automáticamente
```

```bash
# Ver los volúmenes creados
docker volume ls

# CUIDADO: esto borra los datos de la BD
docker-compose down -v    # -v elimina también los volúmenes
docker-compose down       # sin -v conserva los datos
```

---

### `depends_on` y `healthcheck` — orden de arranque

El problema sin `depends_on`: Docker levanta todos los contenedores casi al mismo tiempo.
El backend puede intentar conectarse a la BD antes de que PostgreSQL esté listo,
y falla con error de conexión aunque la BD esté configurada correctamente.

```yaml
db:
  image: postgres:15
  healthcheck:
    test: ["CMD-SHELL", "pg_isready -U postgres"]
    # pg_isready: comando de PostgreSQL que retorna OK cuando la BD acepta conexiones
    interval: 10s     # intenta cada 10 segundos
    timeout: 5s       # espera máximo 5 segundos la respuesta
    retries: 5        # si falla 5 veces seguidas, marca el servicio como "unhealthy"

backend:
  depends_on:
    db:
      condition: service_healthy   # espera hasta que db pase el healthcheck
  # sin esto usarías: depends_on: [db]  (solo espera que arranque, no que esté lista)
```

---

### `deploy.replicas` — escalar horizontalmente `[EXTRA]`

```yaml
backend:
  deploy:
    replicas: 2    # levanta 2 copias idénticas del backend
```

Con 2 réplicas, si una copia falla o está muy cargada, la otra sigue respondiendo.
Esto es **escalamiento horizontal** — más copias del mismo servicio en lugar de
una más potente (**escalamiento vertical**).

---

### Resumen del docker-compose.yml completo del proyecto

```yaml
services:

  db:                              # contenedor de PostgreSQL
    image: postgres:15
    environment:
      POSTGRES_DB:       incidencias_db
      POSTGRES_USER:     postgres
      POSTGRES_PASSWORD: secret
    ports:
      - "5432:5432"
    volumes:
      - pgdata:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U postgres"]
      interval: 10s
      timeout: 5s
      retries: 5

  backend:                         # contenedor del backend Laravel
    build: ./backend               # construye desde el Dockerfile de ./backend
    ports:
      - "8000:8000"
    environment:
      DB_CONNECTION: pgsql
      DB_HOST:       db            # nombre del servicio de arriba, no localhost
      DB_DATABASE:   incidencias_db
      DB_USERNAME:   postgres
      DB_PASSWORD:   secret
      CACHE_DRIVER:  redis
      REDIS_HOST:    redis         # nombre del servicio redis
    depends_on:
      db:
        condition: service_healthy
    command: php artisan serve --host=0.0.0.0 --port=8000
    deploy:
      replicas: 2                  # [EXTRA] 2 copias del backend

  redis:                           # [EXTRA] contenedor de caché
    image: redis:7-alpine
    ports:
      - "6379:6379"

  pgadmin:                         # [EXTRA] interfaz visual para la BD
    image: dpage/pgadmin4
    environment:
      PGADMIN_DEFAULT_EMAIL:    admin@admin.com
      PGADMIN_DEFAULT_PASSWORD: admin
    ports:
      - "5050:80"                  # abre en localhost:5050
    depends_on:
      - db

volumes:
  pgdata:                          # volumen para persistir los datos de PostgreSQL
```

---

### Comandos esenciales del día a día

```bash
# Levantar todo en segundo plano (la -d es de "detached")
docker-compose up -d

# Levantar y reconstruir las imágenes (úsalo cuando cambias código del backend)
docker-compose up -d --build

# Ver el estado de cada contenedor
docker-compose ps

# Ver logs de un servicio específico en tiempo real
docker-compose logs -f backend

# Ejecutar un comando dentro del contenedor backend
docker-compose exec backend php artisan migrate
docker-compose exec backend php artisan test

# Apagar todo sin borrar datos
docker-compose down

# Apagar todo Y borrar los volúmenes (borra la BD)
docker-compose down -v
```

---

### Por qué el backend usa `--host=0.0.0.0`

```yaml
command: php artisan serve --host=0.0.0.0 --port=8000
```

Por defecto `php artisan serve` escucha solo en `127.0.0.1` (solo desde dentro del mismo
contenedor). Con `0.0.0.0` escucha en todas las interfaces de red, lo que permite que
peticiones desde fuera del contenedor (tu navegador en Windows) lleguen al servidor.
> Si algo no queda claro, preguntar y se agrega la explicación aquí.

## 30. Artillery — pruebas de carga

### ¿Qué es Artillery y para qué sirve?

Artillery es una herramienta que simula muchos usuarios usando el sistema al mismo tiempo.
Sirve para verificar que el sistema aguanta carga sin caerse ni volverse lento.

### El archivo artillery.yml

```yaml
config:
  target: "http://localhost"   # la URL base del sistema
  phases:
    - duration: 30             # durante 30 segundos
      arrivalRate: 2           # llegan 2 usuarios nuevos por segundo (60 usuarios)
      name: "Carga inicial"
    - duration: 60             # luego 60 segundos más
      arrivalRate: 5           # llegan 5 por segundo (300 usuarios)
      name: "Carga sostenida"

scenarios:
  - name: "Consulta de endpoints autenticados"
    flow:
      - get:
          url: "/api/incidencias"
          headers:
            Authorization: "Bearer EL_TOKEN_AQUI"
      - get:
          url: "/api/dashboard"
          headers:
            Authorization: "Bearer EL_TOKEN_AQUI"
```

### Por qué necesita un token hardcodeado

Artillery simula usuarios pero no puede "hacer login" fácilmente en el script YAML.
La solución práctica: generar un token de un usuario real y pegarlo en el archivo.

**Problema:** si se corre `migrate:fresh --seed`, ese token deja de ser válido y
hay que actualizarlo en `artillery.yml`.

### Cómo correr el test de carga

```bash
# Correr el test y guardar resultados
artillery run artillery.yml --output reporte.json

# Ver el reporte en HTML
artillery report reporte.json --output reporte.html
```

El reporte muestra:
- Tiempo de respuesta promedio (p50, p95, p99)
- Peticiones por segundo (RPS)
- Errores (si el servidor se sobrecargó)

---

---



---

# BLOQUE 12 — Referencia rápida de comandos

## 20. Comandos del proyecto

Esta sección es tu hoja de referencia rápida. Todos los comandos se ejecutan desde la carpeta raíz del proyecto (`Proyecto-Integrador-UPSE-2026`).

---

### Docker Compose — arrancar y detener el sistema

```bash
# Arrancar todos los servicios (base de datos, backend, frontend, redis, pgadmin)
docker-compose up -d

# Ver qué servicios están corriendo y su estado
docker-compose ps

# Detener todos los servicios (sin borrar datos)
docker-compose down

# Ver los logs del backend en tiempo real
docker-compose logs -f backend

# Ver logs de todos los servicios
docker-compose logs -f
```

> **¿Qué significa `-d`?** "detached" — corre en segundo plano. Sin `-d` bloquea la terminal mostrando logs.

---

### Artisan — comandos dentro del backend Laravel

Todos estos comandos se ejecutan **dentro del contenedor** con `docker-compose exec backend`:

```bash
# Correr todos los tests (con salida detallada)
docker-compose exec backend php artisan test --testdox

# Recrear las tablas y poblar con datos de prueba (¡borra todo!)
docker-compose exec backend php artisan migrate:fresh --seed

# Solo poblar con datos (sin borrar tablas)
docker-compose exec backend php artisan db:seed

# Poblar solo un seeder específico
docker-compose exec backend php artisan db:seed --class=UbicacionSeeder

# Cachear configuración, rutas y vistas (hace el sistema más rápido)
docker-compose exec backend php artisan optimize

# Limpiar toda la caché (necesario al cambiar .env o rutas)
docker-compose exec backend php artisan config:clear
docker-compose exec backend php artisan route:clear
docker-compose exec backend php artisan cache:clear

# Ver todas las rutas registradas
docker-compose exec backend php artisan route:list
```

---

### Git — guardar y versionar el código

```bash
# Ver qué archivos cambiaron
git status

# Ver los cambios en detalle
git diff

# Agregar archivos al commit
git add nombre-del-archivo.php
git add .                          # agrega TODOS los cambios (usar con cuidado)

# Crear un commit
git commit -m "descripción del cambio"

# Ver el historial de commits
git log --oneline

# Subir cambios al repositorio remoto (GitHub)
git push origin develop

# Bajar cambios del repositorio remoto
git pull origin develop

# Cambiar de rama
git checkout main
git checkout develop

# Crear una rama nueva
git checkout -b nombre-de-la-rama

# Fusionar develop en main (para la entrega final)
git checkout main
git merge develop
```

---

### Artillery — pruebas de carga

```bash
# Ejecutar el test de carga y guardar resultados en JSON
artillery run artillery.yml --output reporte.json

# Ver la versión instalada
artillery version
```

---

### npm — paquetes de Node.js

```bash
# Instalar un paquete globalmente
npm install -g nombre-del-paquete

# Ver paquetes globales instalados
npm list -g --depth=0

# Verificar versión de Node y npm
node --version
npm --version
```

---

### WSL2 — subsistema Linux en Windows

```bash
# Ver distribuciones instaladas
wsl --list

# Entrar a Ubuntu (cuando esté instalado)
wsl

# Desde dentro de WSL: ir al directorio del proyecto
cd ~/proyecto

# Salir de WSL
exit
```

---

### PgAdmin — base de datos visual

| Acción | Cómo hacerlo |
|--------|-------------|
| Abrir PgAdmin | Ir a `http://localhost:5050` en el navegador |
| Login | Email: `admin@sistema.com` / Contraseña: `password123` |
| Ejecutar SQL | Click derecho en la BD → **Herramienta de Consulta** |
| Ver el ERD | Click derecho en el schema `public` → **ERD For Database** |
| Ver tablas | Expandir: Servers → incidencias_db → Databases → gestion_incidencias → Schemas → public → Tables |

---

---



---

> Este archivo crece con el proyecto.
