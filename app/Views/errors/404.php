<!doctype html>
<html lang="es">
<head>
  <?php $pageTitle = 'Pagina no encontrada'; $includeTheme = true; include APP_BASE_PATH . '/views/partials/bootstrap-head.php'; ?>
</head>
<body class="bg-light">
  <main class="container py-5">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <h1 class="h4 mb-2">Pagina no encontrada</h1>
        <p class="text-muted mb-3">No existe una ruta MVC para <code><?= htmlspecialchars((string)($page ?? ''), ENT_QUOTES, 'UTF-8') ?></code>.</p>
        <a class="btn btn-primary" href="/redmine/?page=dashboard">Volver al dashboard</a>
      </div>
    </div>
  </main>
  <?php include APP_BASE_PATH . '/views/partials/bootstrap-scripts.php'; ?>
</body>
</html>
