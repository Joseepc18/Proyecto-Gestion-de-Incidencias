// layout.js — Inyecta el sidebar y el navbar compartidos en las páginas del panel.
// Evita repetir ese HTML en cada página. El enlace activo se marca con el
// atributo data-page del <body>. Se carga ANTES del script propio de la página.

(function () {
  const sidebar = document.getElementById("adminSidebar");
  const navbar = document.getElementById("adminNavbar");
  if (!sidebar || !navbar) return;

  const paginaActual = document.body.dataset.page || "";

  // Enlaces del menú. "oculto" = empieza en d-none y lo revela aplicarMenuRol según el rol.
  const enlaces = [
    {
      page: "inicio",
      id: "navInicio",
      href: "../inicio/inicio.html",
      icon: "bi-speedometer2",
      texto: "Inicio",
    },
    {
      page: "incidencias",
      id: "navIncidencias",
      href: "../incidencias/incidencias.html",
      icon: "bi-list-task",
      texto: "Incidencias",
      oculto: true,
    },
    {
      page: "misIncidencias",
      id: "navMisIncidencias",
      href: "../misIncidencias/misIncidencias.html",
      icon: "bi-list-task",
      texto: "Mis incidencias",
      oculto: true,
    },
    {
      page: "registrar",
      href: "../registrarIncidencias/registrar.html",
      icon: "bi-plus-circle",
      texto: "Registrar incidencia",
    },
    {
      page: "usuarios",
      id: "navUsuarios",
      href: "../usuarios/usuarios.html",
      icon: "bi-people",
      texto: "Usuarios",
      oculto: true,
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
    '<span class="brand-copy"><span class="brand-title">Incidencias UPSE</span>' +
    '<span class="brand-subtitle">Gestión georreferenciada</span></span></a></div>' +
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
    '<span class="brand-icon"><i class="bi bi-person-circle" aria-hidden="true"></i></span>' +
    '<span class="profile-name d-none d-sm-inline" id="nombreUsuario">...</span></button>' +
    '<ul class="dropdown-menu dropdown-menu-end">' +
    '<li><a class="dropdown-item" href="#" id="btnLogout">Cerrar sesión</a></li></ul>' +
    "</div></div></div>";
})();
