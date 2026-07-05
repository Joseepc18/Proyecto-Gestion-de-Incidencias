<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Acceso al panel | Gestión de Incidencias</title>

    <link rel="icon" href="/assets/images/favicon/favicon.ico" sizes="any" />
    <script src="/assets/js/tema-inicial.js"></script>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/assets/vendors/bootstrap-icons/bootstrap-icons.css" />
    @if (file_exists(public_path('build/manifest.json')))
      @vite(['resources/css/panel.css'])
    @else
      <link rel="stylesheet" href="/assets/css/style.css" />
    @endif
  </head>

  <body class="d-flex align-items-center justify-content-center" style="min-height: 100vh">
    <main class="w-100" style="max-width: 400px">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <div class="text-center mb-4">
            <span class="brand-icon d-inline-flex mb-2"><i class="bi bi-geo-alt-fill fs-3" aria-hidden="true"></i></span>
            <h1 class="h4 mb-1">Panel administrativo</h1>
            <p class="text-muted small mb-0">Incidencias UPSE</p>
          </div>

          @if ($errors->any())
            <div class="alert alert-danger py-2" role="alert">
              {{ $errors->first() }}
            </div>
          @endif

          <form method="POST" action="/panel/login">
            @csrf

            <div class="mb-3">
              <label for="email" class="form-label">Correo</label>
              <input
                type="email"
                id="email"
                name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}"
                required
                autofocus
              />
            </div>

            <div class="mb-3">
              <label for="password" class="form-label">Contraseña</label>
              <input
                type="password"
                id="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                required
              />
            </div>

            <div class="form-check mb-3">
              <input type="checkbox" id="remember" name="remember" class="form-check-input" />
              <label for="remember" class="form-check-label">Recordarme</label>
            </div>

            <button type="submit" class="btn btn-primary w-100">Entrar</button>
          </form>
        </div>
      </div>
    </main>
  </body>
</html>
