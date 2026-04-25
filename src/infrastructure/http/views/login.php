<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PlexStats · Acceso</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="/public/assets/css/style.css">
</head>
<body class="login-body d-flex align-items-center justify-content-center min-vh-100">

  <div class="login-card card shadow-lg">
    <div class="card-body p-5">

      <div class="text-center mb-4">
        <div class="login-icon mb-2">🎬</div>
        <h2 class="fw-bold mb-1">PlexStats</h2>
        <p class="text-muted small">Estadísticas de peticiones</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 small mb-4">
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <a href="/auth/plex/init" class="btn btn-plex w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm-1.25 17.5V6.5l7.5 5.5-7.5 5.5z"/>
        </svg>
        Entrar con Plex
      </a>

    </div>
  </div>

</body>
</html>
