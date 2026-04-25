<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PlexStats · Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="/public/assets/css/style.css">
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-dark bg-dark px-4 py-3">
    <div class="d-flex align-items-center gap-2">
      <i class="fas fa-film text-primary me-1"></i>
      <span class="navbar-brand mb-0 fw-bold fs-5">PlexStats</span>
    </div>
    <div class="d-flex align-items-center gap-3">
      <div class="d-flex align-items-center gap-2">
        <label for="yearSelect" class="text-white-50 mb-0 small fw-semibold text-uppercase">Año</label>
        <select id="yearSelect" class="form-select form-select-sm bg-dark text-white border-secondary" style="width:auto">
          <?php foreach ($years as $y): ?>
            <option value="<?= (int)$y ?>"<?= (int)$y === $currentYear ? ' selected' : '' ?>>
              <?= (int)$y ?>
            </option>
          <?php endforeach ?>
        </select>
      </div>
      <a href="/logout" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-right-from-bracket me-1"></i>Salir
      </a>
    </div>
  </nav>

  <div class="container-fluid px-4 py-4">

    <!-- Stat cards -->
    <div id="statsRow" class="row g-3 mb-4 d-none">
      <div class="col-auto">
        <div class="stat-card">
          <div class="stat-value" id="statTotal">—</div>
          <div class="stat-label">Peticiones del año</div>
        </div>
      </div>
      <div class="col-auto">
        <div class="stat-card">
          <div class="stat-value" id="statUsers">—</div>
          <div class="stat-label">Usuarios totales</div>
        </div>
      </div>
      <div class="col-auto">
        <div class="stat-card">
          <div class="stat-value" id="statActive">—</div>
          <div class="stat-label">Activos en el año</div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div id="loadingState" class="text-center py-5">
      <output class="spinner-border text-primary mb-3" aria-label="Cargando..."></output>
      <p class="text-muted">Consultando Overseerr...</p>
    </div>

    <!-- Error -->
    <div id="errorState" class="alert alert-danger d-none" role="alert"></div>

    <!-- Tabla -->
    <div id="tableCard" class="card shadow-sm d-none">
      <div class="card-header d-flex align-items-center justify-content-between py-3">
        <h6 class="mb-0 fw-bold">
          Peticiones por usuario —
          <span id="headerYear" class="text-primary"><?= (int)$currentYear ?></span>
        </h6>
      </div>
      <div class="card-body p-0">
        <table id="usersTable" class="table table-hover mb-0">
          <thead class="table-dark">
            <tr>
              <th class="ps-3">#</th>
              <th>Usuario</th>
              <th>Email</th>
              <th>Tipo</th>
              <th class="text-end">Peticiones <span id="thYear"><?= (int)$currentYear ?></span></th>
              <th class="text-end pe-3">Total histórico</th>
            </tr>
          </thead>
          <tbody id="usersTableBody"></tbody>
        </table>
      </div>
    </div>

  </div><!-- /container -->

  <script src="/public/assets/js/jquery.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script>var CURRENT_USER_ID = <?= (int)$currentUserId ?>;</script>
  <script src="/public/assets/js/app.js"></script>

</body>
</html>
