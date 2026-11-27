/* =====================================================
   Orchid — Users Management Page
   Data-driven table with filters, sorting, pagination,
   bulk actions, drawer editor, view modal, delete flow.
   ===================================================== */
(function () {
  'use strict';

  /* ---------- Sample data ---------- */
  const AVATARS = ['users-av-1','users-av-2','users-av-3','users-av-4','users-av-5','users-av-6','users-av-7','users-av-8'];

  const DATA_SEED = [
    { first:'Priya',   last:'Menon',      email:'priya.menon@orchid.io',       phone:'+91 98765 43210', role:'admin',  status:'active',    dept:'Engineering',  tz:'Asia/Kolkata',    lastMin:120,     joined:'2024-02-14' },
    { first:'Marcus',  last:'Chen',       email:'marcus.chen@orchid.io',       phone:'+1 415 555 0114', role:'editor', status:'active',    dept:'Design',       tz:'America/Los_Angeles', lastMin:15,  joined:'2023-08-02' },
    { first:'Sofia',   last:'García',     email:'sofia.garcia@orchid.io',      phone:'+34 612 345 678', role:'viewer', status:'pending',   dept:'Marketing',    tz:'Europe/Madrid',   lastMin:2880,    joined:'2025-01-19' },
    { first:'Yuki',    last:'Tanaka',     email:'yuki.tanaka@orchid.io',       phone:'+81 90 1234 5678',role:'editor', status:'active',    dept:'Engineering',  tz:'Asia/Tokyo',      lastMin:45,      joined:'2024-06-11' },
    { first:'Ananya',  last:'Rao',        email:'ananya.rao@orchid.io',        phone:'+91 99887 76655', role:'admin',  status:'active',    dept:'Product',      tz:'Asia/Kolkata',    lastMin:5,       joined:'2023-04-27' },
    { first:'David',   last:'Okafor',     email:'david.okafor@orchid.io',      phone:'+234 802 555 0101',role:'guest', status:'suspended', dept:'Support',      tz:'Africa/Lagos',    lastMin:20160,   joined:'2024-11-03' },
    { first:'Isabella',last:'Rossi',      email:'isabella.rossi@orchid.io',    phone:'+39 320 555 0192',role:'editor', status:'active',    dept:'Design',       tz:'Europe/Rome',     lastMin:180,     joined:'2024-03-08' },
    { first:'Emma',    last:'Watson',     email:'emma.watson@orchid.io',       phone:'+44 20 7946 0958',role:'viewer', status:'active',    dept:'Marketing',    tz:'Europe/London',   lastMin:1440,    joined:'2025-05-22' },
    { first:'James',   last:'Doe',        email:'james.doe@orchid.io',         phone:'+1 212 555 0187', role:'admin',  status:'active',    dept:'Executive',    tz:'America/New_York', lastMin:30,     joined:'2023-01-16' },
    { first:'Sarah',   last:'Miller',     email:'sarah.miller@orchid.io',      phone:'+61 2 5550 3388', role:'editor', status:'pending',   dept:'Sales',        tz:'Australia/Sydney',lastMin:4320,    joined:'2025-03-30' },
    { first:'Alex',    last:'Kim',        email:'alex.kim@orchid.io',          phone:'+1 415 555 2020', role:'admin',  status:'active',    dept:'Engineering',  tz:'America/Los_Angeles', lastMin:1,   joined:'2023-02-11' },
    { first:'Ryan',    last:'Green',      email:'ryan.green@orchid.io',        phone:'+1 416 555 0173', role:'viewer', status:'suspended', dept:'Finance',      tz:'America/Toronto', lastMin:43200,   joined:'2024-01-08' },
    { first:'Ava',     last:'Lee',        email:'ava.lee@orchid.io',           phone:'+49 30 55501212', role:'editor', status:'active',    dept:'Design',       tz:'Europe/Berlin',   lastMin:75,      joined:'2024-09-14' },
    { first:'Noah',    last:'Park',       email:'noah.park@orchid.io',         phone:'+82 10 5555 0166',role:'viewer', status:'active',    dept:'Engineering',  tz:'Asia/Seoul',      lastMin:240,     joined:'2025-06-05' },
    { first:'Zara',    last:'Hussain',    email:'zara.hussain@orchid.io',      phone:'+971 50 555 0142',role:'guest',  status:'pending',   dept:'Support',      tz:'Asia/Dubai',      lastMin:7200,    joined:'2025-04-18' },
    { first:'Liam',    last:"O'Brien",    email:'liam.obrien@orchid.io',       phone:'+353 1 555 0134', role:'admin',  status:'active',    dept:'Product',      tz:'Europe/Dublin',   lastMin:60,      joined:'2023-11-29' },
    { first:'Fatima',  last:'Al-Rashid',  email:'fatima.al-rashid@orchid.io',  phone:'+966 55 555 0189',role:'editor', status:'active',    dept:'Marketing',    tz:'Asia/Riyadh',     lastMin:360,     joined:'2024-07-21' },
    { first:'Ken',     last:'Yamamoto',   email:'ken.yamamoto@orchid.io',      phone:'+81 80 5555 4321',role:'viewer', status:'pending',   dept:'Finance',      tz:'Asia/Tokyo',      lastMin:11520,   joined:'2025-02-09' },
    { first:'Diana',   last:'Petrova',    email:'diana.petrova@orchid.io',     phone:'+7 495 555 8877', role:'guest',  status:'suspended', dept:'Sales',        tz:'Europe/Moscow',   lastMin:129600,  joined:'2024-05-12' },
    { first:'Mateo',   last:'Silva',      email:'mateo.silva@orchid.io',       phone:'+55 11 5555 9911',role:'editor', status:'active',    dept:'Engineering',  tz:'America/Sao_Paulo',lastMin:10,     joined:'2024-10-30' }
  ];

  /* ---------- State ---------- */
  const state = {
    users: [],
    filtered: [],
    selection: new Set(),
    filters: { q:'', role:'all', status:'all', date:'all' },
    sort: { key:'joined', dir:'desc' },
    page: 1,
    perPage: 10,
    archived: new Set(),
    nextId: 1
  };

  /* ---------- Utils ---------- */
  const $  = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));

  const initials = (f, l) => (f[0]||'').toUpperCase() + (l[0]||'').toUpperCase();
  const escapeHtml = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const fmtDate = iso => {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
  };
  const relTime = mins => {
    if (mins < 1) return 'Just now';
    if (mins < 60) return mins + 'm ago';
    if (mins < 60*24) return Math.floor(mins/60) + 'h ago';
    if (mins < 60*24*2) return 'Yesterday';
    if (mins < 60*24*30) return Math.floor(mins/(60*24)) + 'd ago';
    if (mins < 60*24*365) return Math.floor(mins/(60*24*30)) + 'mo ago';
    return Math.floor(mins/(60*24*365)) + 'y ago';
  };
  const avatarClass = id => AVATARS[id % AVATARS.length];

  /* ---------- Toast host ---------- */
  const toastHost = document.createElement('div');
  toastHost.className = 'users-toast-host';
  toastHost.setAttribute('role', 'status');
  toastHost.setAttribute('aria-live', 'polite');
  document.body.appendChild(toastHost);

  const ICONS = {
    success: 'bi-check-circle-fill',
    info:    'bi-info-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    danger:  'bi-x-circle-fill'
  };
  function toast(msg, kind='success') {
    const el = document.createElement('div');
    el.className = 'users-toast users-toast--' + kind;
    el.innerHTML = '<i class="bi ' + ICONS[kind] + '"></i><span>' + escapeHtml(msg) + '</span>';
    toastHost.appendChild(el);
    setTimeout(() => {
      el.classList.add('is-leaving');
      setTimeout(() => el.remove(), 220);
    }, 2600);
  }

  /* ---------- Seed ---------- */
  DATA_SEED.forEach(u => {
    state.users.push(Object.assign({}, u, { id: state.nextId++, permissions: defaultPermsFor(u.role) }));
  });

  function defaultPermsFor(role) {
    if (role === 'admin') return ['view','edit','delete','manage'];
    if (role === 'editor') return ['view','edit'];
    if (role === 'viewer') return ['view'];
    return [];
  }

  /* ---------- DOM refs ---------- */
  const tbody      = $('[data-users-tbody]');
  const skeleton   = $('[data-users-skeleton]');
  const emptyEl    = $('[data-users-empty]');
  const searchEl   = $('[data-users-search]');
  const roleFilt   = $('[data-users-filter="role"]');
  const statusFilt = $('[data-users-filter="status"]');
  const dateFilt   = $('[data-users-filter="date"]');
  const resetBtn   = $('[data-users-reset]');
  const perPageSel = $('[data-users-perpage]');
  const pagerEl    = $('[data-users-pager]');
  const metaEl     = $('[data-users-meta]');
  const bulkBar    = $('[data-users-bulk]');
  const bulkCountEl= $('[data-users-bulk-count]');
  const selectAllCb= $('[data-users-selectall]');
  const statTotal  = $('[data-users-stat="total"]');
  const statActive = $('[data-users-stat="active"]');
  const statPending= $('[data-users-stat="pending"]');
  const statSusp   = $('[data-users-stat="suspended"]');

  const drawerEl   = $('#usersDrawer');
  const drawerForm = $('[data-users-form]');
  const drawerTitle= $('[data-users-drawer-title]');
  const drawerPreview = $('[data-users-preview]');
  const drawerAvatarInput = $('[data-users-avatar-input]');
  const drawer = new bootstrap.Offcanvas(drawerEl);

  const viewModalEl = $('#usersViewModal');
  const viewModal   = new bootstrap.Modal(viewModalEl);

  const delModalEl  = $('#usersDeleteModal');
  const delModal    = new bootstrap.Modal(delModalEl);
  const delConfirmCb= $('[data-users-del-confirm]');
  const delGoBtn    = $('[data-users-del-go]');
  const delMsgEl    = $('[data-users-del-msg]');

  let deleteContext = null; // { ids: [...] }
  let editingId = null;
  let filterTimer = null;

  /* ---------- Filtering ---------- */
  function inDateRange(iso, range) {
    if (range === 'all') return true;
    const now = new Date();
    const d = new Date(iso);
    const days = (now - d) / (1000*60*60*24);
    if (range === 'today') return days < 1;
    if (range === '7d') return days <= 7;
    if (range === '30d') return days <= 30;
    return true;
  }

  function applyFilters() {
    const q = state.filters.q.trim().toLowerCase();
    state.filtered = state.users.filter(u => {
      if (state.filters.role !== 'all' && u.role !== state.filters.role) return false;
      if (state.filters.status !== 'all' && u.status !== state.filters.status) return false;
      if (!inDateRange(u.joined, state.filters.date)) return false;
      if (q) {
        const full = (u.first + ' ' + u.last).toLowerCase();
        if (!full.includes(q) && !u.email.toLowerCase().includes(q)) return false;
      }
      return true;
    });
    sortFiltered();
    if (state.page > totalPages()) state.page = 1;
  }

  function sortFiltered() {
    const { key, dir } = state.sort;
    const mul = dir === 'asc' ? 1 : -1;
    state.filtered.sort((a, b) => {
      let av, bv;
      if (key === 'name')     { av = (a.first+' '+a.last).toLowerCase(); bv = (b.first+' '+b.last).toLowerCase(); }
      else if (key === 'lastActive') { av = a.lastMin; bv = b.lastMin; }
      else if (key === 'joined') { av = a.joined; bv = b.joined; }
      else { av = a[key]; bv = b[key]; }
      if (av < bv) return -1 * mul;
      if (av > bv) return  1 * mul;
      return 0;
    });
    // Archived rows always sink to the bottom
    state.filtered.sort((a, b) => {
      const aa = state.archived.has(a.id) ? 1 : 0;
      const bb = state.archived.has(b.id) ? 1 : 0;
      return aa - bb;
    });
  }

  function totalPages() {
    return Math.max(1, Math.ceil(state.filtered.length / state.perPage));
  }

  /* ---------- Rendering ---------- */
  function renderStats() {
    const total = state.users.length;
    let a=0, p=0, s=0;
    state.users.forEach(u => {
      if (u.status === 'active')    a++;
      else if (u.status === 'pending') p++;
      else if (u.status === 'suspended') s++;
    });
    statTotal.textContent   = total;
    statActive.textContent  = a;
    statPending.textContent = p;
    statSusp.textContent    = s;
  }

  function renderRows() {
    const start = (state.page - 1) * state.perPage;
    const slice = state.filtered.slice(start, start + state.perPage);
    tbody.innerHTML = slice.map(u => rowHTML(u)).join('');

    emptyEl.classList.toggle('is-visible', state.filtered.length === 0);
    updateSelectAllState();
    renderFooter();
  }

  function rowHTML(u) {
    const isSel = state.selection.has(u.id);
    const arch = state.archived.has(u.id);
    const roleLabel = { admin:'Admin', editor:'Editor', viewer:'Viewer', guest:'Guest' }[u.role];
    const statusLabel = { active:'Active', pending:'Pending', suspended:'Suspended' }[u.status];
    return (
      '<tr data-users-row="' + u.id + '"' +
        (isSel ? ' class="is-selected"' : (arch ? ' class="archived"' : '')) + '>' +
        '<td class="users-td-check">' +
          '<div class="form-check m-0">' +
            '<input class="form-check-input" type="checkbox" aria-label="Select row" data-users-rowcheck="' + u.id + '"' + (isSel ? ' checked' : '') + '>' +
          '</div>' +
        '</td>' +
        '<td>' +
          '<div class="users-cell-user">' +
            '<span class="avatar avatar-sm ' + avatarClass(u.id) + '">' + initials(u.first, u.last) + '</span>' +
            '<div>' +
              '<p class="users-cell-name">' + escapeHtml(u.first + ' ' + u.last) + '</p>' +
              '<p class="users-cell-email">' + escapeHtml(u.email) + '</p>' +
            '</div>' +
          '</div>' +
        '</td>' +
        '<td><span class="users-role users-role--' + u.role + '">' + roleLabel + '</span></td>' +
        '<td><span class="users-status users-status--' + u.status + '"><span class="users-status__dot"></span><span>' + statusLabel + '</span></span></td>' +
        '<td class="users-meta">' + relTime(u.lastMin) + '</td>' +
        '<td class="users-meta">' + fmtDate(u.joined) + '</td>' +
        '<td class="users-td-actions">' +
          '<div class="dropdown">' +
            '<button class="users-row-action" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Row actions">' +
              '<i class="bi bi-three-dots-vertical"></i>' +
            '</button>' +
            '<ul class="dropdown-menu dropdown-menu-end">' +
              '<li><a class="dropdown-item" href="#" data-users-action="view"      data-id="' + u.id + '"><i class="bi bi-eye me-2"></i>View</a></li>' +
              '<li><a class="dropdown-item" href="#" data-users-action="edit"      data-id="' + u.id + '"><i class="bi bi-pencil me-2"></i>Edit</a></li>' +
              '<li><a class="dropdown-item" href="#" data-users-action="duplicate" data-id="' + u.id + '"><i class="bi bi-files me-2"></i>Duplicate</a></li>' +
              '<li><a class="dropdown-item" href="#" data-users-action="reset"     data-id="' + u.id + '"><i class="bi bi-key me-2"></i>Reset password</a></li>' +
              '<li><a class="dropdown-item" href="#" data-users-action="archive"   data-id="' + u.id + '"><i class="bi bi-archive me-2"></i>' + (arch ? 'Unarchive' : 'Archive') + '</a></li>' +
              '<li><hr class="dropdown-divider"></li>' +
              '<li><a class="dropdown-item text-danger" href="#" data-users-action="delete" data-id="' + u.id + '"><i class="bi bi-trash me-2"></i>Delete</a></li>' +
            '</ul>' +
          '</div>' +
        '</td>' +
      '</tr>'
    );
  }

  function renderFooter() {
    const total = state.filtered.length;
    const start = total === 0 ? 0 : (state.page - 1) * state.perPage + 1;
    const end   = Math.min(state.page * state.perPage, total);
    metaEl.textContent = 'Showing ' + start + '–' + end + ' of ' + total + ' users';

    // pager
    const pages = totalPages();
    const cur   = state.page;
    let html = '';
    html += '<li class="page-item ' + (cur === 1 ? 'disabled' : '') + '"><a class="page-link" href="#" data-users-page="prev" aria-label="Previous"><i class="bi bi-chevron-left"></i></a></li>';

    // build page numbers with ellipses
    const nums = pageNumbers(cur, pages);
    nums.forEach(n => {
      if (n === '…') {
        html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
      } else {
        html += '<li class="page-item ' + (n === cur ? 'active' : '') + '"><a class="page-link" href="#" data-users-page="' + n + '">' + n + '</a></li>';
      }
    });

    html += '<li class="page-item ' + (cur === pages ? 'disabled' : '') + '"><a class="page-link" href="#" data-users-page="next" aria-label="Next"><i class="bi bi-chevron-right"></i></a></li>';
    pagerEl.innerHTML = html;
  }

  function pageNumbers(cur, total) {
    const out = [];
    if (total <= 7) { for (let i=1;i<=total;i++) out.push(i); return out; }
    out.push(1);
    if (cur > 3) out.push('…');
    const s = Math.max(2, cur-1), e = Math.min(total-1, cur+1);
    for (let i=s;i<=e;i++) out.push(i);
    if (cur < total-2) out.push('…');
    out.push(total);
    return out;
  }

  function renderSortIndicators() {
    $$('.users-th-sort').forEach(th => {
      th.classList.remove('is-asc', 'is-desc');
      const icon = th.querySelector('.users-sort-icon');
      if (icon) icon.innerHTML = '<i class="bi bi-arrow-down-up"></i>';
      if (th.dataset.usersSort === state.sort.key) {
        th.classList.add(state.sort.dir === 'asc' ? 'is-asc' : 'is-desc');
        if (icon) icon.innerHTML = '<i class="bi bi-chevron-' + (state.sort.dir === 'asc' ? 'up' : 'down') + '"></i>';
      }
    });
  }

  /* ---------- Skeleton loading ---------- */
  function showSkeleton() {
    skeleton.classList.add('is-visible');
    tbody.style.visibility = 'hidden';
  }
  function hideSkeleton() {
    skeleton.classList.remove('is-visible');
    tbody.style.visibility = '';
  }

  function refresh({ withLoading = false } = {}) {
    if (withLoading) {
      showSkeleton();
      setTimeout(() => {
        applyFilters();
        renderRows();
        hideSkeleton();
      }, 400);
    } else {
      applyFilters();
      renderRows();
    }
  }

  /* ---------- Selection ---------- */
  function updateSelectAllState() {
    const start = (state.page - 1) * state.perPage;
    const slice = state.filtered.slice(start, start + state.perPage);
    const allSel = slice.length > 0 && slice.every(u => state.selection.has(u.id));
    const someSel = slice.some(u => state.selection.has(u.id));
    if (selectAllCb) {
      selectAllCb.checked = allSel;
      selectAllCb.indeterminate = !allSel && someSel;
    }
    bulkBar.classList.toggle('is-visible', state.selection.size > 0);
    bulkCountEl.textContent = state.selection.size;
  }

  /* ---------- Sort click ---------- */
  $$('.users-th-sort').forEach(th => {
    th.addEventListener('click', () => {
      const key = th.dataset.usersSort;
      if (state.sort.key === key) {
        state.sort.dir = state.sort.dir === 'asc' ? 'desc' : 'asc';
      } else {
        state.sort.key = key;
        state.sort.dir = 'asc';
      }
      renderSortIndicators();
      refresh();
    });
  });

  /* ---------- Filter listeners ---------- */
  searchEl.addEventListener('input', e => {
    state.filters.q = e.target.value;
    state.page = 1;
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => refresh({ withLoading:true }), 150);
  });
  roleFilt.addEventListener('change', e => { state.filters.role = e.target.value; state.page=1; refresh({ withLoading:true }); });
  statusFilt.addEventListener('change', e => { state.filters.status = e.target.value; state.page=1; refresh({ withLoading:true }); });
  dateFilt.addEventListener('change', e => { state.filters.date = e.target.value; state.page=1; refresh({ withLoading:true }); });
  resetBtn.addEventListener('click', () => {
    state.filters = { q:'', role:'all', status:'all', date:'all' };
    state.page = 1;
    searchEl.value = '';
    roleFilt.value = 'all';
    statusFilt.value = 'all';
    dateFilt.value = 'all';
    refresh({ withLoading:true });
    toast('Filters reset', 'info');
  });

  /* ---------- Per-page + pager ---------- */
  perPageSel.addEventListener('change', e => {
    state.perPage = parseInt(e.target.value, 10);
    state.page = 1;
    refresh();
  });
  pagerEl.addEventListener('click', e => {
    const a = e.target.closest('[data-users-page]');
    if (!a) return;
    e.preventDefault();
    const val = a.dataset.usersPage;
    const pages = totalPages();
    if (val === 'prev') state.page = Math.max(1, state.page - 1);
    else if (val === 'next') state.page = Math.min(pages, state.page + 1);
    else state.page = parseInt(val, 10);
    renderRows();
  });

  /* ---------- Row selection ---------- */
  tbody.addEventListener('change', e => {
    const cb = e.target.closest('[data-users-rowcheck]');
    if (!cb) return;
    const id = parseInt(cb.dataset.usersRowcheck, 10);
    if (cb.checked) state.selection.add(id); else state.selection.delete(id);
    const row = cb.closest('tr');
    if (row) row.classList.toggle('is-selected', cb.checked);
    updateSelectAllState();
  });
  selectAllCb.addEventListener('change', e => {
    const start = (state.page - 1) * state.perPage;
    const slice = state.filtered.slice(start, start + state.perPage);
    if (e.target.checked) slice.forEach(u => state.selection.add(u.id));
    else slice.forEach(u => state.selection.delete(u.id));
    renderRows();
  });

  /* ---------- Row action delegation ---------- */
  tbody.addEventListener('click', e => {
    const link = e.target.closest('[data-users-action]');
    if (!link) return;
    e.preventDefault();
    const id = parseInt(link.dataset.id, 10);
    const action = link.dataset.usersAction;
    const u = state.users.find(x => x.id === id);
    if (!u) return;
    if (action === 'view')      openViewModal(u);
    else if (action === 'edit') openDrawer(u);
    else if (action === 'duplicate') duplicateUser(u);
    else if (action === 'reset')     resetPassword(u);
    else if (action === 'archive')   toggleArchive(u);
    else if (action === 'delete')    openDeleteModal([u.id]);
  });

  function duplicateUser(u) {
    const copy = Object.assign({}, u, {
      id: state.nextId++,
      email: u.email.replace('@', '-copy@'),
      first: u.first,
      last: u.last,
      joined: new Date().toISOString().slice(0,10),
      lastMin: 0,
      permissions: u.permissions.slice()
    });
    state.users.unshift(copy);
    renderStats();
    refresh();
    toast('User duplicated', 'success');
  }
  function resetPassword(u) {
    toast('Reset link sent to ' + u.email, 'info');
  }
  function toggleArchive(u) {
    if (state.archived.has(u.id)) {
      state.archived.delete(u.id);
      toast(u.first + ' unarchived', 'info');
    } else {
      state.archived.add(u.id);
      toast(u.first + ' archived', 'warning');
    }
    refresh();
  }

  /* ---------- Bulk actions ---------- */
  bulkBar.addEventListener('click', e => {
    const btn = e.target.closest('[data-users-bulk-action]');
    if (!btn) return;
    const action = btn.dataset.usersBulkAction;
    const ids = Array.from(state.selection);
    if (ids.length === 0) return;
    if (action === 'delete') openDeleteModal(ids);
    else if (action === 'archive') {
      ids.forEach(id => state.archived.add(id));
      toast(ids.length + ' users archived', 'warning');
      state.selection.clear();
      refresh();
    }
    else if (action === 'export') {
      toast('Exported ' + ids.length + ' selected users', 'success');
    }
    else if (action === 'clear') {
      state.selection.clear();
      renderRows();
    }
    else if (action.indexOf('role:') === 0) {
      const newRole = action.split(':')[1];
      ids.forEach(id => {
        const u = state.users.find(x => x.id === id);
        if (u) { u.role = newRole; u.permissions = defaultPermsFor(newRole); }
      });
      toast('Role changed to ' + newRole + ' for ' + ids.length + ' users', 'success');
      state.selection.clear();
      renderStats();
      refresh();
    }
  });

  /* ---------- Delete flow ---------- */
  function openDeleteModal(ids) {
    deleteContext = { ids };
    delConfirmCb.checked = false;
    delGoBtn.disabled = true;
    if (ids.length === 1) {
      const u = state.users.find(x => x.id === ids[0]);
      delMsgEl.textContent = 'You are about to permanently delete ' + (u ? u.first + ' ' + u.last : 'this user') + '.';
    } else {
      delMsgEl.textContent = 'You are about to permanently delete ' + ids.length + ' users.';
    }
    delModal.show();
  }
  delConfirmCb.addEventListener('change', e => {
    delGoBtn.disabled = !e.target.checked;
  });
  delGoBtn.addEventListener('click', () => {
    if (!deleteContext) return;
    const ids = deleteContext.ids;
    state.users = state.users.filter(u => !ids.includes(u.id));
    ids.forEach(id => { state.selection.delete(id); state.archived.delete(id); });
    delModal.hide();
    toast(ids.length + ' user' + (ids.length>1?'s':'') + ' deleted', 'danger');
    deleteContext = null;
    renderStats();
    refresh();
  });

  /* ---------- Drawer (create / edit) ---------- */
  function openDrawer(u) {
    editingId = u ? u.id : null;
    drawerTitle.textContent = u ? 'Edit User' : 'Add User';
    drawerForm.reset();
    drawerForm.classList.remove('was-validated');
    // Populate
    if (u) {
      drawerForm.first.value = u.first;
      drawerForm.last.value  = u.last;
      drawerForm.email.value = u.email;
      drawerForm.phone.value = u.phone || '';
      drawerForm.role.value  = u.role;
      drawerForm.dept.value  = u.dept;
      drawerForm.tz.value    = u.tz;
      $$('input[name="status"]', drawerForm).forEach(r => r.checked = r.value === u.status);
      $$('input[name="perms"]', drawerForm).forEach(cb => cb.checked = u.permissions.includes(cb.value));
      drawerPreview.innerHTML = initials(u.first, u.last);
      drawerPreview.className = 'users-avatar-upload__preview ' + avatarClass(u.id);
    } else {
      $$('input[name="status"]', drawerForm).forEach(r => r.checked = r.value === 'active');
      drawerPreview.textContent = '?';
      drawerPreview.className = 'users-avatar-upload__preview';
    }
    drawer.show();
  }

  $('[data-users-add]').addEventListener('click', () => openDrawer(null));

  drawerAvatarInput.addEventListener('change', e => {
    const file = e.target.files && e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
      drawerPreview.innerHTML = '<img alt="Avatar preview" src="' + ev.target.result + '">';
    };
    reader.readAsDataURL(file);
  });

  drawerForm.addEventListener('submit', e => {
    e.preventDefault();
    if (!drawerForm.checkValidity()) {
      drawerForm.classList.add('was-validated');
      return;
    }
    const data = {
      first: drawerForm.first.value.trim(),
      last:  drawerForm.last.value.trim(),
      email: drawerForm.email.value.trim(),
      phone: drawerForm.phone.value.trim(),
      role:  drawerForm.role.value,
      dept:  drawerForm.dept.value,
      tz:    drawerForm.tz.value,
      status: (drawerForm.querySelector('input[name="status"]:checked') || {}).value || 'active',
      permissions: $$('input[name="perms"]:checked', drawerForm).map(cb => cb.value)
    };
    if (editingId != null) {
      const idx = state.users.findIndex(u => u.id === editingId);
      if (idx > -1) state.users[idx] = Object.assign(state.users[idx], data);
      toast('User updated', 'success');
    } else {
      const now = new Date();
      state.users.unshift(Object.assign({
        id: state.nextId++,
        lastMin: 0,
        joined: now.toISOString().slice(0,10)
      }, data));
      const sendWelcome = drawerForm.welcome.checked;
      toast('User created' + (sendWelcome ? ' · welcome email sent' : ''), 'success');
    }
    drawer.hide();
    renderStats();
    refresh();
  });

  /* ---------- View modal ---------- */
  function openViewModal(u) {
    $('[data-users-view="avatar"]').className = 'avatar avatar-lg ' + avatarClass(u.id);
    $('[data-users-view="avatar"]').textContent = initials(u.first, u.last);
    $('[data-users-view="name"]').textContent = u.first + ' ' + u.last;
    $('[data-users-view="sub"]').innerHTML = escapeHtml(u.email) + ' · ' + escapeHtml(u.dept);
    $('[data-users-view="role"]').innerHTML = '<span class="users-role users-role--'+u.role+'">'+{admin:'Admin',editor:'Editor',viewer:'Viewer',guest:'Guest'}[u.role]+'</span>';
    $('[data-users-view="status"]').innerHTML = '<span class="users-status users-status--'+u.status+'"><span class="users-status__dot"></span><span>'+{active:'Active',pending:'Pending',suspended:'Suspended'}[u.status]+'</span></span>';
    $('[data-users-view="phone"]').textContent = u.phone || '—';
    $('[data-users-view="tz"]').textContent = u.tz;
    $('[data-users-view="joined"]').textContent = fmtDate(u.joined);
    $('[data-users-view="last"]').textContent = relTime(u.lastMin);
    $('[data-users-view="perms"]').textContent = u.permissions.length ? u.permissions.map(p => p.charAt(0).toUpperCase()+p.slice(1)).join(', ') : 'None';
    // Fake activity
    const acts = [
      { i:'bi-box-arrow-in-right', t:'Signed in',              w:relTime(u.lastMin) },
      { i:'bi-pencil',             t:'Updated profile details',w:'2d ago' },
      { i:'bi-key',                t:'Changed password',       w:'1w ago' },
      { i:'bi-people',             t:'Joined team ' + u.dept,  w:'3w ago' },
      { i:'bi-check-circle',       t:'Account created',        w:fmtDate(u.joined) }
    ];
    $('[data-users-view="activity"]').innerHTML = acts.map(a => '<li><i class="bi '+a.i+'"></i><span>'+a.t+'</span><time>'+a.w+'</time></li>').join('');
    viewModalEl.dataset.currentId = u.id;
    viewModal.show();
  }

  $('[data-users-view-edit]').addEventListener('click', () => {
    const id = parseInt(viewModalEl.dataset.currentId, 10);
    const u = state.users.find(x => x.id === id);
    viewModal.hide();
    if (u) setTimeout(() => openDrawer(u), 220);
  });

  /* ---------- Import / Export ---------- */
  $('[data-users-import]').addEventListener('click', () => {
    $('[data-users-import-input]').click();
  });
  $('[data-users-import-input]').addEventListener('change', e => {
    const file = e.target.files && e.target.files[0];
    if (!file) return;
    // Fake import — add 3 dummy rows
    const dummies = [
      { first:'Imported', last:'User A', email:'imported.a@orchid.io', phone:'', role:'viewer', status:'pending', dept:'Marketing', tz:'UTC', lastMin:0, joined:new Date().toISOString().slice(0,10) },
      { first:'Imported', last:'User B', email:'imported.b@orchid.io', phone:'', role:'viewer', status:'pending', dept:'Sales',     tz:'UTC', lastMin:0, joined:new Date().toISOString().slice(0,10) },
      { first:'Imported', last:'User C', email:'imported.c@orchid.io', phone:'', role:'viewer', status:'pending', dept:'Support',   tz:'UTC', lastMin:0, joined:new Date().toISOString().slice(0,10) }
    ];
    dummies.forEach(d => state.users.unshift(Object.assign({ id: state.nextId++, permissions:['view'] }, d)));
    toast('Imported 3 users from ' + file.name, 'success');
    e.target.value = '';
    renderStats();
    refresh();
  });

  $$('[data-users-export]').forEach(a => {
    a.addEventListener('click', e => {
      e.preventDefault();
      toast('Exported as ' + a.dataset.usersExport, 'success');
    });
  });

  /* ---------- Init ---------- */
  applyFilters();
  renderStats();
  renderSortIndicators();
  renderRows();
})();
