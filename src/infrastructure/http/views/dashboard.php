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

  <div id="mainView" class="container-fluid px-4 py-4">

    <!-- Stat cards -->
    <div id="statsRow" class="row g-3 mb-4 d-none">
      <div class="col-auto">
        <div class="stat-card">
          <div class="stat-value" id="statTotal">—</div>
          <div class="stat-label">Solicitudes del año</div>
        </div>
      </div>
      <div class="col-auto">
        <div class="stat-card">
          <div class="stat-value" id="statWatched">—</div>
          <div class="stat-label">Visualizaciones totales</div>
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

    <!-- Tabla principal -->
    <div id="tableCard" class="card shadow-sm d-none">
      <div class="card-header d-flex align-items-center justify-content-between py-3">
        <h6 class="mb-0 fw-bold">
          Solicitudes por usuario —
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
              <th class="text-end">Solicitudes <span id="thYear"><?= (int)$currentYear ?></span></th>
              <th class="text-end">Visto (total)</th>
              <th class="text-end pe-3">Total histórico</th>
            </tr>
          </thead>
          <tbody id="usersTableBody"></tbody>
        </table>
      </div>
    </div>

  </div><!-- /mainView -->

  <!-- ── Panel de detalle de usuario ───────────────────────── -->
  <div id="detailView" class="container-fluid px-4 py-4 d-none">

    <!-- Cabecera del panel -->
    <div class="d-flex align-items-center gap-3 mb-4">
      <button id="detailBack" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Volver
      </button>
      <div id="detailUserInfo" class="d-flex align-items-center gap-2"></div>
      <span class="text-muted small ms-auto">
        Solicitudes de <strong id="detailYear"></strong>
      </span>
    </div>

    <!-- Resumen del usuario -->
    <div class="d-flex gap-3 mb-4">
      <div class="stat-card">
        <div class="stat-value" id="detailTotal">—</div>
        <div class="stat-label">Solicitudes</div>
      </div>
      <div class="stat-card">
        <div class="stat-value text-success" id="detailWatched">—</div>
        <div class="stat-label">Vistas</div>
      </div>
      <div class="stat-card">
        <div class="stat-value text-muted" id="detailUnwatched">—</div>
        <div class="stat-label">No vistas</div>
      </div>
    </div>

    <!-- Estado cargando detalle -->
    <div id="detailLoading" class="text-center py-5 d-none">
      <output class="spinner-border text-primary mb-3" aria-label="Cargando..."></output>
      <p class="text-muted">Cargando solicitudes...</p>
    </div>

    <!-- Controles de vista y búsqueda -->
    <div id="detailControls" class="d-flex align-items-center gap-2 mb-3 d-none">
      <input id="detailSearch" type="search" class="form-control form-control-sm" placeholder="Buscar..." style="max-width:220px">
      <div class="ms-auto btn-group btn-group-sm" aria-label="Tamaño de vista">
        <button type="button" class="btn btn-outline-secondary view-btn active" data-view="lg" title="Grande">
          <i class="fas fa-th-large"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary view-btn" data-view="sm" title="Pequeño">
          <i class="fas fa-th"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary view-btn" data-view="list" title="Lista">
          <i class="fas fa-list"></i>
        </button>
      </div>
    </div>

    <!-- Grid de solicitudes -->
    <div id="requestsGrid" class="row g-3 view-lg"></div>

  </div><!-- /detailView -->

  <script src="/public/assets/js/jquery.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script>var CURRENT_USER_ID = <?= (int)$currentUserId ?>;</script>
  <script src="/public/assets/js/app.js"></script>

</body>
</html>
