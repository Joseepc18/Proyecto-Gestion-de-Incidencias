<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('titulo', 'Panel') | Gestión de Incidencias</title>

    <link rel="icon" href="/assets/images/favicon/favicon.ico" sizes="any" />
    {{-- tema-inicial y los vendors los sirve Nginx desde el árbol estático (compartidos con las páginas JS). --}}
    <script src="/assets/js/tema-inicial.js"></script>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/assets/vendors/bootstrap-icons/bootstrap-icons.css" />
    {{-- style.css y panel.js empaquetados con Vite (minificados + hash) cuando hay build; si no, raw para dev. --}}
    @if (file_exists(public_path('build/manifest.json')))
      @vite(['resources/css/panel.css', 'resources/js/panel.js'])
    @else
      <link rel="stylesheet" href="/assets/css/style.css" />
      <script defer src="/assets/js/panel.js"></script>
    @endif
  </head>

  <body data-page="{{ $pagina ?? '' }}">
    <div class="admin-shell">
      <div class="sidebar-backdrop" data-sidebar-close></div>

      <aside class="admin-sidebar" id="adminSidebar" aria-label="Navegación principal">
        <div class="sidebar-header">
          <a class="brand-mark" href="/panel">
            <span class="brand-icon"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i></span>
            <span class="brand-copy">
              <span class="brand-title">Incidencias UPSE</span>
              <span class="brand-subtitle">Panel administrativo</span>
            </span>
          </a>
        </div>
        <nav class="sidebar-nav">
          @if (auth()->user()->tienePermiso('usuarios.administrar'))
            <a class="nav-link {{ ($pagina ?? '') === 'usuarios' ? 'active' : '' }}"
               href="{{ route('panel.usuarios') }}"
               @if (($pagina ?? '') === 'usuarios') aria-current="page" @endif>
              <span class="nav-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
              <span class="nav-text">Usuarios</span>
            </a>
          @endif
          @if (auth()->user()->tienePermiso('catalogos.administrar'))
            <a class="nav-link {{ ($pagina ?? '') === 'catalogos' ? 'active' : '' }}"
               href="{{ route('panel.catalogos') }}"
               @if (($pagina ?? '') === 'catalogos') aria-current="page" @endif>
              <span class="nav-icon"><i class="bi bi-diagram-3" aria-hidden="true"></i></span>
              <span class="nav-text">Tipos de incidencia</span>
            </a>
          @endif
        </nav>
        <div class="sidebar-footer">
          <span class="status-dot"></span>
          <span class="sidebar-footer-text">Sistema activo</span>
        </div>
      </aside>

      <div class="admin-main">
        <nav class="navbar admin-navbar navbar-expand" id="adminNavbar">
          <div class="container-fluid px-3 px-lg-4">
            <button class="sidebar-toggle" type="button" data-sidebar-toggle
                    aria-controls="adminSidebar" aria-expanded="true" aria-label="Mostrar/ocultar menú">
              <span></span><span></span><span></span>
            </button>
            <div class="navbar-actions ms-auto">
              <button class="icon-button theme-toggle" type="button" data-theme-toggle
                      aria-label="Cambiar tema" title="Cambiar tema">
                <i class="bi bi-moon-stars" data-theme-icon aria-hidden="true"></i>
              </button>
              <div class="dropdown">
                <button class="profile-button dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                  <span class="brand-icon"><i class="bi bi-person-circle" aria-hidden="true"></i></span>
                  <span class="profile-name d-none d-sm-inline">{{ auth()->user()->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                    <form method="POST" action="{{ route('panel.logout') }}">
                      @csrf
                      <button type="submit" class="dropdown-item">Cerrar sesión</button>
                    </form>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </nav>

        <main class="dashboard-content">
          <div class="container-fluid px-3 px-lg-4 py-4">
            @yield('contenido')
          </div>
        </main>
      </div>
    </div>

    @if (session('exito') || session('error'))
      <div id="panelFlash" hidden
           data-mensaje="{{ session('exito') ?? session('error') }}"
           data-tipo="{{ session('exito') ? 'success' : 'error' }}"></div>
    @endif

    <script defer src="/assets/js/bootstrap.bundle.min.js"></script>
    {{-- Diálogos propios reutilizados del frontend (nada de alert()/confirm() del navegador). --}}
    <script defer src="/assets/js/toast.js"></script>
    <script defer src="/assets/js/confirmar.js"></script>
    <script defer src="/assets/js/password.js"></script>
    {{-- panel.js va por @vite en el <head> (o su fallback raw en dev). --}}
    {{-- Toggles de tema y sidebar (versión mínima del panel, sin el main.js de la plantilla). --}}
    <script>
      document.querySelector("[data-theme-toggle]")?.addEventListener("click", function () {
        const html = document.documentElement;
        const nuevo = html.getAttribute("data-theme") === "dark" ? "light" : "dark";
        html.setAttribute("data-theme", nuevo);
        html.setAttribute("data-bs-theme", nuevo);
        try {
          localStorage.setItem("adminHMD.colorTheme", nuevo);
        } catch {
          /* localStorage no disponible */
        }
      });
      document.querySelector("[data-sidebar-toggle]")?.addEventListener("click", function () {
        document.querySelector(".admin-shell")?.classList.toggle("sidebar-open");
      });
      document.querySelector("[data-sidebar-close]")?.addEventListener("click", function () {
        document.querySelector(".admin-shell")?.classList.remove("sidebar-open");
      });
    </script>
  </body>
</html>
