(function () {
  'use strict';

  const PALETTE = [
    '#6366f1', '#0ea5e9', '#22c55e', '#f59e0b',
    '#ec4899', '#14b8a6', '#f97316', '#8b5cf6',
  ];

  let dt          = null;
  let currentYear = null;
  let currentView = (document.cookie.match(/(?:^|; )plexstats_view=([^;]+)/) || [])[1] || 'lg';
  if (currentView === 'md') currentView = 'lg';
  let allRequests = [];
  let usersCache  = [];

  // -- Helpers --

  function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str == null ? '' : str));
    return d.innerHTML;
  }

  function colorFor(name) {
    let h = 0;
    for (let i = 0; i < name.length; i++) {
      h = (name.codePointAt(i) ?? 0) + ((h << 5) - h);
    }
    return PALETTE[Math.abs(h) % PALETTE.length];
  }

  function avatarHtml(user) {
    if (user.avatar) {
      return '<img src="' + esc(user.avatar) + '" class="user-avatar me-2" alt="" loading="lazy">';
    }
    const initial = (user.displayName || '?')[0].toUpperCase();
    const bg = colorFor(user.displayName || '');
    return '<span class="avatar-fallback me-2" style="background:' + bg + '">' + initial + '</span>';
  }

  function typeBadge(userType, permissions) {
    if ((permissions & 2) === 2) return '<span class="badge bg-danger">Admin</span>';
    if (userType === 2)          return '<span class="badge bg-info text-dark">Local</span>';
    return '<span class="badge bg-secondary">Plex</span>';
  }

  // -- Vista principal --

  function showMainView() {
    document.getElementById('detailView').classList.add('d-none');
    document.getElementById('mainView').classList.remove('d-none');
    document.getElementById('loadingState').classList.add('d-none');
    document.getElementById('tableCard').classList.remove('d-none');
    document.getElementById('statsRow').classList.remove('d-none');
    document.getElementById('errorState').classList.add('d-none');
  }

  function showLoadingView() {
    document.getElementById('detailView').classList.add('d-none');
    document.getElementById('mainView').classList.remove('d-none');
    document.getElementById('loadingState').classList.remove('d-none');
    document.getElementById('tableCard').classList.add('d-none');
    document.getElementById('errorState').classList.add('d-none');
    document.getElementById('statsRow').classList.add('d-none');
  }

  // -- Pipeline de carga en 3 pasos --

  function load(year) {
    currentYear = year;
    history.replaceState(null, '', location.pathname + location.search);
    showLoadingView();
    document.getElementById('headerYear').textContent = year;
    document.getElementById('thYear').textContent = year;

    if (dt) {
      dt.destroy();
      dt = null;
    }
    document.getElementById('usersTableBody').innerHTML = '';

    // Paso 1: usuarios
    fetch('/api/users')
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (data) {
        if (data.error) throw new Error(data.error);
        renderUsersTable(data.users);

        // Paso 2: conteo de solicitudes del año
        return fetch('/api/request-counts?year=' + encodeURIComponent(year));
      })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (data) {
        if (data.error) throw new Error(data.error);
        updateRequestCounts(data.byUser, data.total, data.active);

        // Paso 3: conteo de visionado
        return fetch('/api/watch-counts?year=' + encodeURIComponent(year));
      })
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (data) {
        if (data.error) throw new Error(data.error);
        updateWatchCounts(data.byUser, data.total);
        initDataTable();
      })
      .catch(function (err) {
        document.getElementById('loadingState').classList.add('d-none');
        document.getElementById('tableCard').classList.add('d-none');
        document.getElementById('statsRow').classList.add('d-none');
        const el = document.getElementById('errorState');
        el.textContent = 'Error al cargar datos: ' + err.message;
        el.classList.remove('d-none');
      });
  }

  function renderUsersTable(users) {
    usersCache = users;
    const spinner = '<span class="spinner-border spinner-border-sm text-muted" aria-hidden="true"></span>';
    const tbody   = document.getElementById('usersTableBody');

    users.forEach(function (u, i) {
      const rowCls = (typeof CURRENT_USER_ID !== 'undefined' && u.id === CURRENT_USER_ID) ? ' class="row-me"' : '';
      tbody.insertAdjacentHTML('beforeend',
        '<tr' + rowCls + ' style="cursor:pointer" data-user-id="' + u.id + '" data-user-name="' + esc(u.displayName) + '" data-avatar="' + esc(u.avatar) + '">' +
          '<td class="ps-3 text-muted small">' + (i + 1) + '</td>' +
          '<td>' +
            '<div class="d-flex align-items-center">' +
              avatarHtml(u) +
              '<span class="fw-semibold">' + esc(u.displayName) + '</span>' +
            '</div>' +
          '</td>' +
          '<td class="text-muted small">' + esc(u.email) + '</td>' +
          '<td>' + typeBadge(u.userType, u.permissions) + '</td>' +
          '<td class="text-end"><span id="req-' + u.id + '" class="req-count">' + spinner + '</span></td>' +
          '<td class="text-end"><span id="watch-' + u.id + '" class="req-count">' + spinner + '</span></td>' +
          '<td class="text-end pe-3 text-muted">' + u.requestCount + '</td>' +
        '</tr>'
      );
    });

    document.getElementById('statUsers').textContent   = users.length;
    document.getElementById('statActive').textContent  = '—';
    document.getElementById('statTotal').textContent   = '—';
    document.getElementById('statWatched').textContent = '—';
    document.getElementById('statsRow').classList.remove('d-none');
    document.getElementById('loadingState').classList.add('d-none');
    document.getElementById('tableCard').classList.remove('d-none');

    var initMatch = window.location.hash.match(/^#user-(\d+)$/);
    if (initMatch) {
      var initUser = usersCache.find(function (u) { return u.id === Number.parseInt(initMatch[1], 10); });
      if (initUser) loadDetail(initUser.id, initUser.displayName, initUser.avatar);
    }
  }

  function updateRequestCounts(byUser, total, active) {
    document.querySelectorAll('[id^="req-"]').forEach(function (span) {
      const uid   = span.id.slice(4);
      const count = byUser[uid] || 0;
      span.className   = 'req-count' + (count === 0 ? ' zero' : ' text-primary');
      span.textContent = count;
    });
    document.getElementById('statTotal').textContent  = total.toLocaleString('es-ES');
    document.getElementById('statActive').textContent = active;
  }

  function updateWatchCounts(byUser, total) {
    document.querySelectorAll('[id^="watch-"]').forEach(function (span) {
      const uid   = span.id.slice(6);
      const count = byUser[uid] || 0;
      span.className   = 'req-count' + (count === 0 ? ' zero' : ' text-success');
      span.textContent = count;
    });
    document.getElementById('statWatched').textContent = total.toLocaleString('es-ES');
  }

  function initDataTable() {
    if (dt) { dt.destroy(); dt = null; }
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
  }

  // -- Vista de detalle --

  function loadDetail(userId, userName, avatar) {
    const dv = document.getElementById('detailView');

    document.getElementById('tableCard').classList.add('d-none');
    document.getElementById('statsRow').classList.add('d-none');
    document.getElementById('mainView').classList.add('d-none');
    dv.classList.remove('d-none');

    const avatarEl = avatar
      ? '<img src="' + esc(avatar) + '" class="user-avatar me-2" alt="">'
      : '<span class="avatar-fallback me-2" style="background:' + colorFor(userName) + '">' + (userName[0] || '?').toUpperCase() + '</span>';

    document.getElementById('detailUserInfo').innerHTML =
      avatarEl + '<span class="fw-bold fs-5">' + esc(userName) + '</span>';
    document.getElementById('detailYear').textContent      = currentYear;
    document.getElementById('detailTotal').textContent     = '--';
    document.getElementById('detailWatched').textContent   = '--';
    document.getElementById('detailUnwatched').textContent = '--';
    document.getElementById('requestsGrid').innerHTML      = '';
    document.getElementById('detailControls').classList.add('d-none');
    document.getElementById('detailLoading').classList.remove('d-none');
    document.getElementById('detailSearch').value = '';
    allRequests = [];

    fetch('/api/user-requests?userId=' + userId + '&year=' + encodeURIComponent(currentYear))
      .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(function (data) {
        if (data.error) throw new Error(data.error);
        renderDetail(data);
      })
      .catch(function (err) {
        document.getElementById('detailLoading').classList.add('d-none');
        document.getElementById('requestsGrid').innerHTML =
          '<div class="col-12"><div class="alert alert-danger">Error: ' + esc(err.message) + '</div></div>';
      });
  }

  function renderDetail(data) {
    document.getElementById('detailLoading').classList.add('d-none');

    allRequests = data.requests || [];
    const watched = allRequests.filter(function (r) { return r.watched; }).length;

    document.getElementById('detailTotal').textContent     = allRequests.length.toLocaleString('es-ES');
    document.getElementById('detailWatched').textContent   = watched.toLocaleString('es-ES');
    document.getElementById('detailUnwatched').textContent = (allRequests.length - watched).toLocaleString('es-ES');

    if (allRequests.length > 0) {
      document.getElementById('detailControls').classList.remove('d-none');
    }

    renderGrid(allRequests);
  }

  function renderGrid(reqs) {
    const grid = document.getElementById('requestsGrid');
    grid.innerHTML = '';

    grid.className = 'row g-3 view-' + currentView;

    if (reqs.length === 0) {
      grid.innerHTML = '<div class="col-12 text-muted">No hay solicitudes para este año.</div>';
      return;
    }

    const isList = currentView === 'list';

    reqs.forEach(function (r) {
      const poster = r.posterPath
        ? 'https://image.tmdb.org/t/p/w185' + r.posterPath
        : null;

      const iconName = r.mediaType === 'tv' ? 'tv' : 'film';
      let posterHtml;
      if (poster) {
        posterHtml = '<img src="' + esc(poster) + '" class="request-poster" alt="" loading="lazy">';
      } else {
        posterHtml = '<div class="request-poster-placeholder d-flex align-items-center justify-content-center">'
          + '<i class="fas fa-' + iconName + ' fa-2x"></i></div>';
      }

      const watchBadge = r.watched
        ? '<span class="badge bg-success position-absolute top-0 end-0 m-1"><i class="fas fa-check"></i></span>'
        : '';

      const typeBadgeHtml = r.mediaType === 'tv'
        ? '<span class="badge bg-info text-dark">Serie</span>'
        : '<span class="badge bg-primary">Pel&iacute;cula</span>';

      const watchStatusIcon = r.watched
        ? '<span class="watch-status-icon text-success"><i class="fas fa-check-circle"></i></span>'
        : '';

      let cardHtml;
      if (isList) {
        cardHtml =
          '<div class="request-card' + (r.watched ? ' request-card--watched' : '') + '">' +
            '<div class="request-poster-wrap position-relative">' + posterHtml + '</div>' +
            '<div class="request-card-body">' +
              '<div class="request-title" title="' + esc(r.title) + '">' + esc(r.title) + '</div>' +
              typeBadgeHtml +
              watchStatusIcon +
            '</div>' +
          '</div>';
      } else {
        cardHtml =
          '<div class="request-card' + (r.watched ? ' request-card--watched' : '') + '">' +
            '<div class="request-poster-wrap position-relative">' +
              posterHtml +
              watchBadge +
            '</div>' +
            '<div class="request-card-body">' +
              '<div class="request-title" title="' + esc(r.title) + '">' + esc(r.title) + '</div>' +
              '<div class="mt-1">' + typeBadgeHtml + '</div>' +
            '</div>' +
          '</div>';
      }

      grid.insertAdjacentHTML('beforeend',
        '<div class="req-item">' + cardHtml + '</div>'
      );
    });
  }

  function filterGrid(query) {
    const q = query.trim().toLowerCase();
    const filtered = q
      ? allRequests.filter(function (r) { return r.title.toLowerCase().includes(q); })
      : allRequests;
    renderGrid(filtered);
  }

  function setView(view) {
    currentView = view;
    document.cookie = 'plexstats_view=' + view + '; path=/; max-age=31536000; samesite=Lax';
    document.querySelectorAll('.view-btn').forEach(function (btn) {
      btn.classList.toggle('active', btn.dataset.view === view);
    });
    const q = document.getElementById('detailSearch').value;
    filterGrid(q);
  }

  // -- Init --

  document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('yearSelect');
    load(sel.value);
    sel.addEventListener('change', function () { load(sel.value); });

    document.getElementById('usersTableBody').addEventListener('click', function (e) {
      const tr = e.target.closest('tr');
      if (!tr) return;
      const userId = tr.dataset.userId;
      if (userId) window.location.hash = 'user-' + userId;
    });

    document.getElementById('detailBack').addEventListener('click', function () {
      history.back();
    });

    window.addEventListener('hashchange', function () {
      var m = window.location.hash.match(/^#user-(\d+)$/);
      if (m) {
        var user = usersCache.find(function (u) { return u.id === Number.parseInt(m[1], 10); });
        if (user) loadDetail(user.id, user.displayName, user.avatar);
      } else {
        document.getElementById('detailView').classList.add('d-none');
        showMainView();
      }
    });

    document.querySelectorAll('.view-btn').forEach(function (btn) {
      btn.classList.toggle('active', btn.dataset.view === currentView);
      btn.addEventListener('click', function () { setView(btn.dataset.view); });
    });

    document.getElementById('detailSearch').addEventListener('input', function () {
      filterGrid(this.value);
    });
  });

}());