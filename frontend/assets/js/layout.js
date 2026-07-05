// layout.js — Inyecta el sidebar y navbar compartidos; el enlace activo se marca con data-page del <body>.

/* global aplicarMenuRol, escaparHtml */

(function () {
  const sidebar = document.getElementById("adminSidebar");
  const navbar = document.getElementById("adminNavbar");
  if (!sidebar || !navbar) return;

  const paginaActual = document.body.dataset.page || "";

  const cfg = window.APP_CONFIG || {};
  // Se inyectan como HTML más abajo: se escapan por si APP_CONFIG llega a ser dinámico.
  const appNombre = escaparHtml(cfg.nombre || "Incidencias UPSE");
  const appSubtitulo = escaparHtml(cfg.subtitulo || "Gestión georreferenciada");

  const enlaces = [
    {
      page: "inicio",
      id: "navInicio",
      href: "../inicio/inicio.html",
      icon: "bi-speedometer2",
      texto: "Inicio",
      oculto: true,
    },
    {
      page: "gestion-incidencias",
      id: "navIncidencias",
      href: "../gestion-incidencias/gestion-incidencias.html",
      icon: "bi-list-task",
      texto: "Incidencias",
      oculto: true,
    },
    {
      page: "papelera",
      id: "navPapelera",
      href: "../papelera/papelera.html",
      icon: "bi-trash3",
      texto: "Papelera",
      oculto: true,
    },
    {
      page: "mis-incidencias",
      id: "navMisIncidencias",
      href: "../mis-incidencias/mis-incidencias.html",
      icon: "bi-list-task",
      texto: "Mis incidencias",
      oculto: true,
    },
    {
      page: "registrar",
      id: "navRegistrar",
      href: "../registrar/registrar.html",
      icon: "bi-plus-circle",
      texto: "Registrar incidencia",
      oculto: true,
    },
    {
      page: "permisos",
      id: "navPermisos",
      href: "../permisos/permisos.html",
      icon: "bi-shield-lock",
      texto: "Permisos",
      oculto: true,
    },
    {
      page: "notificaciones",
      href: "../notificaciones/notificaciones.html",
      icon: "bi-mailbox",
      texto: "Notificaciones",
    },
  ];

  const navHtml = enlaces
    .map(function (e) {
      const activo = e.page === paginaActual;
      const clases = "nav-link" + (activo ? " active" : "") + (e.oculto ? " d-none" : "");
      return (
        '<a class="' +
        clases +
        '"' +
        (e.id ? ' id="' + e.id + '"' : "") +
        ' href="' +
        e.href +
        '"' +
        (activo ? ' aria-current="page"' : "") +
        ">" +
        '<span class="nav-icon"><i class="bi ' +
        e.icon +
        '" aria-hidden="true"></i></span>' +
        '<span class="nav-text">' +
        e.texto +
        "</span></a>"
      );
    })
    .join("");

  sidebar.innerHTML =
    '<div class="sidebar-header">' +
    '<a class="brand-mark" href="../inicio/inicio.html">' +
    '<span class="brand-icon"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i></span>' +
    '<span class="brand-copy"><span class="brand-title">' +
    appNombre +
    "</span>" +
    '<span class="brand-subtitle">' +
    appSubtitulo +
    "</span></span></a></div>" +
    '<nav class="sidebar-nav">' +
    navHtml +
    "</nav>" +
    '<div class="sidebar-footer"><span class="status-dot"></span>' +
    '<span class="sidebar-footer-text">Sistema activo</span></div>';

  navbar.innerHTML =
    '<div class="container-fluid px-3 px-lg-4">' +
    '<button class="sidebar-toggle" type="button" data-sidebar-toggle ' +
    'aria-controls="adminSidebar" aria-expanded="true" aria-label="Mostrar/ocultar menú">' +
    "<span></span><span></span><span></span></button>" +
    '<div class="navbar-actions ms-auto">' +
    '<div class="dropdown">' +
    '<button class="icon-button" type="button" id="btnNotificaciones" ' +
    'data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" ' +
    'aria-label="Notificaciones" title="Notificaciones">' +
    '<i class="bi bi-bell" aria-hidden="true"></i>' +
    '<span class="notification-badge d-none" id="notifBadge">0</span></button>' +
    '<div class="dropdown-menu dropdown-menu-end notification-menu p-0">' +
    '<div class="notification-header">' +
    '<span class="notification-title">Notificaciones</span>' +
    '<button type="button" class="notification-clear d-none" id="btnMarcarTodas">' +
    "Marcar todas</button></div>" +
    '<div class="notification-list" id="notifLista"></div></div></div>' +
    '<button class="icon-button theme-toggle" type="button" data-theme-toggle ' +
    'aria-label="Cambiar tema" title="Cambiar tema">' +
    '<i class="bi bi-moon-stars" data-theme-icon aria-hidden="true"></i></button>' +
    '<div class="dropdown">' +
    '<button class="profile-button dropdown-toggle" type="button" ' +
    'data-bs-toggle="dropdown" aria-expanded="false">' +
    '<span class="brand-icon" id="navbarAvatar"><i class="bi bi-person-circle" aria-hidden="true"></i></span>' +
    '<span class="profile-name d-none d-sm-inline" id="nombreUsuario">...</span></button>' +
    '<ul class="dropdown-menu dropdown-menu-end">' +
    '<li><a class="dropdown-item" href="../perfil/perfil.html"><i class="bi bi-person me-2"></i>Mi perfil</a></li>' +
    '<li><hr class="dropdown-divider" /></li>' +
    '<li><a class="dropdown-item" href="#" id="btnLogout">Cerrar sesión</a></li></ul>' +
    "</div></div></div>";

  window.pintarAvatarNavbar = function (foto) {
    const cont = document.getElementById("navbarAvatar");
    if (!cont) return;
    if (foto) {
      // Construido por DOM (no innerHTML): la ruta de la foto nunca se interpola como HTML.
      const img = document.createElement("img");
      img.src = "/storage/" + encodeURIComponent(foto);
      img.alt = "Foto de perfil";
      img.className = "navbar-avatar-img";
      img.loading = "lazy";
      cont.replaceChildren(img);
    } else {
      const icono = document.createElement("i");
      icono.className = "bi bi-person-circle";
      icono.setAttribute("aria-hidden", "true");
      cont.replaceChildren(icono);
    }
  };

  window.pintarAvatarNavbar(localStorage.getItem("perfil_foto") || "");

  // Pinta el menú del rol cacheado de inmediato: evita el parpadeo hasta que /user responda.
  if (typeof aplicarMenuRol === "function") {
    aplicarMenuRol(localStorage.getItem("rol_usuario") || "");
  }
})();
