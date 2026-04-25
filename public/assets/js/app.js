(function () {
  'use strict';

  // Paleta para avatares generados
  const PALETTE = [
    '#6366f1', '#0ea5e9', '#22c55e', '#f59e0b',
    '#ec4899', '#14b8a6', '#f97316', '#8b5cf6',
  ];

  let dt = null;

  // ── Helpers ──────────────────────────────────────────────────

  /** Escapar HTML para evitar XSS */
  function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str == null ? '' : str));
    return d.innerHTML;
  }

  /** Color determinista basado en el nombre */
  function colorFor(name) {
    let h = 0;
    for (let i = 0; i < name.length; i++) {
      h = (name.codePointAt(i) ?? 0) + ((h << 5) - h);
    }
    return PALETTE[Math.abs(h) % PALETTE.length];
  }

  /** HTML del avatar: imagen o inicial con color */
  function avatarHtml(user) {
    if (user.avatar) {
      return '<img src="' + esc(user.avatar) + '" class="user-avatar me-2" alt="" loading="lazy">';
    }
    const initial = (user.displayName || '?')[0].toUpperCase();
    const bg = colorFor(user.displayName || '');
    return '<span class="avatar-fallback me-2" style="background:' + bg + '">' + initial + '</span>';
  }

  /** Badge de tipo de usuario */
  function typeBadge(userType, permissions) {
    if ((permissions & 2) === 2) return '<span class="badge bg-danger">Admin</span>';
    if (userType === 2)          return '<span class="badge bg-info text-dark">Local</span>';
    return '<span class="badge bg-secondary">Plex</span>';
  }

  // ── Carga de datos ────────────────────────────────────────────

  function load(year) {
    document.getElementById('loadingState').classList.remove('d-none');
    document.getElementById('tableCard').classList.add('d-none');
    document.getElementById('errorState').classList.add('d-none');
    document.getElementById('statsRow').classList.add('d-none');
    document.getElementById('headerYear').textContent = year;
    document.getElementById('thYear').textContent = year;

    if (dt) {
      dt.destroy();
      dt = null;
    }
    document.getElementById('usersTableBody').innerHTML = '';

    fetch('/api/users?year=' + encodeURIComponent(year))
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (data) {
        if (data.error) throw new Error(data.error);
        render(data);
      })
      .catch(function (err) {
        document.getElementById('loadingState').classList.add('d-none');
        const el = document.getElementById('errorState');
        el.textContent = 'Error al cargar datos: ' + err.message;
        el.classList.remove('d-none');
      });
  }

  // ── Renderizado ───────────────────────────────────────────────

  function render(data) {
    const active = data.users.filter(function (u) { return u.yearRequestCount > 0; }).length;
    document.getElementById('statTotal').textContent  = data.totalYearRequests.toLocaleString('es-ES');
    document.getElementById('statUsers').textContent  = data.users.length;
    document.getElementById('statActive').textContent = active;
    document.getElementById('statsRow').classList.remove('d-none');

    const tbody = document.getElementById('usersTableBody');

    data.users.forEach(function (u, i) {
      const countCls = u.yearRequestCount === 0 ? 'req-count zero' : 'req-count text-primary';
      const rowCls   = (typeof CURRENT_USER_ID !== 'undefined' && u.id === CURRENT_USER_ID) ? ' class="row-me"' : '';
      tbody.insertAdjacentHTML('beforeend',
        '<tr' + rowCls + '>' +
          '<td class="ps-3 text-muted small">' + (i + 1) + '</td>' +
          '<td>' +
            '<div class="d-flex align-items-center">' +
              avatarHtml(u) +
              '<span class="fw-semibold">' + esc(u.displayName) + '</span>' +
            '</div>' +
          '</td>' +
          '<td class="text-muted small">' + esc(u.email) + '</td>' +
          '<td>' + typeBadge(u.userType, u.permissions) + '</td>' +
          '<td class="text-end"><span class="' + countCls + '">' + u.yearRequestCount + '</span></td>' +
          '<td class="text-end pe-3 text-muted">' + u.requestCount + '</td>' +
        '</tr>'
      );
    });

    dt = $('#usersTable').DataTable({
      paging:   false,
      info:     false,
      language: {
        search:      'Buscar:',
        zeroRecords: 'No hay resultados.',
        emptyTable:  'Sin datos',
      },
      order: [[4, 'desc']],
      columnDefs: [{ orderable: false, targets: [0] }],
    });

    document.getElementById('loadingState').classList.add('d-none');
    document.getElementById('tableCard').classList.remove('d-none');
  }

  // ── Init ──────────────────────────────────────────────────────

  document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('yearSelect');
    load(sel.value);
    sel.addEventListener('change', function () { load(sel.value); });
  });

}());
