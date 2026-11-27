/* =====================================================
   Orchid — Sales Lists (Quotations / Orders / Payments / Refunds)
   Shared toolkit + per-page controllers.
   Namespaces: window.SalesLists (shared), page-specific inits.
   ===================================================== */
(function () {
  'use strict';

  /* ----------------- Shared toolkit ----------------- */

  const AVATAR_COLORS = [
    ['bg-primary-subtle', 'text-primary'],
    ['bg-info-subtle',    'text-info'],
    ['bg-success-subtle', 'text-success'],
    ['bg-warning-subtle', 'text-warning'],
    ['bg-danger-subtle',  'text-danger'],
    ['bg-purple-subtle',  'text-purple']
  ];

  function initials(name) {
    if (!name) return '?';
    return name.trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase();
  }
  function avatarClass(name) {
    let hash = 0;
    for (let i = 0; i < (name || '').length; i++) hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
    const c = AVATAR_COLORS[hash % AVATAR_COLORS.length];
    return c.join(' ');
  }

  function escapeHtml(v) {
    if (v == null) return '';
    return String(v)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function fmtMoney(n, currency) {
    const c = currency || 'USD';
    try {
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: c, maximumFractionDigits: 2 }).format(n);
    } catch (e) {
      return '$' + Number(n).toFixed(2);
    }
  }
  function fmtCompactMoney(n) {
    if (n == null) return '';
    if (Math.abs(n) >= 1000000) return '$' + (n / 1000000).toFixed(1) + 'M';
    if (Math.abs(n) >= 1000)    return '$' + (n / 1000).toFixed(1) + 'k';
    return fmtMoney(n);
  }
  function fmtDate(d) {
    if (!d) return '';
    const date = d instanceof Date ? d : new Date(d);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' });
  }
  function fmtDateTime(d) {
    const date = d instanceof Date ? d : new Date(d);
    return date.toLocaleString('en-US', { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' });
  }
  function relativeTime(d) {
    const date = d instanceof Date ? d : new Date(d);
    const diff = Date.now() - date.getTime();
    const sec = Math.round(diff / 1000);
    const min = Math.round(sec / 60);
    const hr  = Math.round(min / 60);
    const day = Math.round(hr / 24);
    if (Math.abs(sec) < 60)  return sec < 5 ? 'just now' : sec + 's ago';
    if (Math.abs(min) < 60)  return min + 'm ago';
    if (Math.abs(hr)  < 24)  return hr  + 'h ago';
    if (Math.abs(day) < 30)  return day + 'd ago';
    return fmtDate(date);
  }

  function daysBetween(from, to) {
    const a = from instanceof Date ? from : new Date(from);
    const b = to   instanceof Date ? to   : new Date(to);
    const ms = b.getTime() - a.getTime();
    return Math.ceil(ms / (1000 * 60 * 60 * 24));
  }

  function debounce(fn, wait) {
    let t;
    return function () {
      const ctx = this, args = arguments;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(ctx, args); }, wait);
    };
  }

  /* ----------------- Toast system ----------------- */
  function ensureToastWrap() {
    let wrap = document.querySelector('[data-sales-toast-wrap]');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.className = 'sales-toast-wrap';
      wrap.setAttribute('data-sales-toast-wrap', '');
      wrap.setAttribute('role', 'status');
      wrap.setAttribute('aria-live', 'polite');
      document.body.appendChild(wrap);
    }
    return wrap;
  }
  function orchidToast(msg, opts) {
    const options = opts || {};
    const wrap = ensureToastWrap();
    const kind = options.kind || 'info';
    const iconMap = { success: 'check-circle-fill', danger: 'x-circle-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' };
    const el = document.createElement('div');
    el.className = 'sales-toast sales-toast--' + kind;
    el.innerHTML =
      '<div class="sales-toast__icon"><i class="bi bi-' + iconMap[kind] + '"></i></div>' +
      '<div class="sales-toast__body">' +
        (options.title ? '<strong>' + escapeHtml(options.title) + '</strong>' : '') +
        escapeHtml(msg) +
      '</div>' +
      '<button class="sales-toast__close" type="button" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>';
    wrap.appendChild(el);
    const close = () => { el.style.opacity = '0'; el.style.transform = 'translateY(-4px)'; setTimeout(() => el.remove(), 200); };
    el.querySelector('.sales-toast__close').addEventListener('click', close);
    setTimeout(close, options.duration || 4200);
  }

  /* ----------------- Status pill helpers ----------------- */
  function statusMap(status) {
    const s = (status || '').toLowerCase();
    const map = {
      draft:      { cls: 'sales-pill--muted',   dot: true, label: 'Draft' },
      sent:       { cls: 'sales-pill--info',    dot: true, label: 'Sent' },
      accepted:   { cls: 'sales-pill--success', dot: true, label: 'Accepted' },
      declined:   { cls: 'sales-pill--danger',  dot: true, label: 'Declined' },
      expired:    { cls: 'sales-pill--danger',  dot: true, label: 'Expired' },
      succeeded:  { cls: 'sales-pill--success', dot: true, label: 'Succeeded' },
      pending:    { cls: 'sales-pill--warning', dot: true, label: 'Pending' },
      failed:     { cls: 'sales-pill--danger',  dot: true, label: 'Failed' },
      disputed:   { cls: 'sales-pill--purple',  dot: true, label: 'Disputed' },
      requested:  { cls: 'sales-pill--warning', dot: true, label: 'Requested' },
      approved:   { cls: 'sales-pill--success', dot: true, label: 'Approved' },
      rejected:   { cls: 'sales-pill--danger',  dot: true, label: 'Rejected' },
      processed:  { cls: 'sales-pill--info',    dot: true, label: 'Processed' }
    };
    return map[s] || { cls: 'sales-pill--muted', dot: true, label: status || '—' };
  }
  function renderStatusPill(status) {
    const m = statusMap(status);
    return '<span class="sales-pill ' + m.cls + '">' +
      (m.dot ? '<span class="sales-pill__dot"></span>' : '') +
      escapeHtml(m.label) +
      '</span>';
  }

  /* ----------------- Shared datasets ----------------- */
  const CUSTOMERS = [
    { id: 'C001', name: 'Vertex Robotics',        contact: 'Nadia Chen',      email: 'nadia@vertex.io' },
    { id: 'C002', name: 'Meridian Health',        contact: 'Owen Blake',      email: 'owen@meridian.health' },
    { id: 'C003', name: 'Aurora Labs',            contact: 'Priya Sharma',    email: 'priya@auroralabs.com' },
    { id: 'C004', name: 'Nova Textiles',          contact: 'Marcus Vega',     email: 'marcus@novatextiles.co' },
    { id: 'C005', name: 'Halcyon Studio',         contact: 'Ella Winters',    email: 'ella@halcyon.studio' },
    { id: 'C006', name: 'Kestrel Aerospace',      contact: 'James Doe',       email: 'james@kestrel.aero' },
    { id: 'C007', name: 'Orion Foods',            contact: 'Sarah Miller',    email: 'sarah@orionfoods.com' },
    { id: 'C008', name: 'Sable & Co.',            contact: 'Ryan Green',      email: 'ryan@sableco.com' },
    { id: 'C009', name: 'Bright Signal Media',    contact: 'Ava Lee',         email: 'ava@brightsignal.tv' },
    { id: 'C010', name: 'Argent Financial',       contact: 'Kenji Tanaka',    email: 'kenji@argent.fin' },
    { id: 'C011', name: 'Palladium Auto',         contact: 'Diego Ortiz',     email: 'diego@palladium.auto' },
    { id: 'C012', name: 'Emberline Cosmetics',    contact: 'Sofia Rivera',    email: 'sofia@emberline.beauty' },
    { id: 'C013', name: 'Cascade Outdoor',        contact: 'Liam Fisher',     email: 'liam@cascade.co' },
    { id: 'C014', name: 'Pinecrest Publishing',   contact: 'Grace Kim',       email: 'grace@pinecrest.pub' }
  ];

  const PRODUCTS = [
    { sku: 'AUD-EARB-01', name: 'Halo Wireless Earbuds Pro',   price: 189 },
    { sku: 'BAG-WKD-02',  name: 'Nomad Weekender Bag',         price: 145 },
    { sku: 'ACC-KEY-05',  name: 'Aether Mechanical Keyboard',  price: 219 },
    { sku: 'HOM-LAMP-11', name: 'Solstice Desk Lamp',          price: 89 },
    { sku: 'WEAR-JKT-08', name: 'Ridge Softshell Jacket',      price: 249 },
    { sku: 'TECH-STD-03', name: 'Prism Standing Desk 60"',     price: 649 },
    { sku: 'AUD-SPKR-07', name: 'Loft Bookshelf Speakers',     price: 399 },
    { sku: 'ACC-BAG-14',  name: 'Marlow Leather Backpack',     price: 210 },
    { sku: 'HOM-MUG-22',  name: 'Ceramic Pour-Over Set',       price: 62 },
    { sku: 'TECH-MON-04', name: 'Vertex 27" 4K Monitor',       price: 549 },
    { sku: 'AUD-HDPH-06', name: 'Meridian Studio Headphones',  price: 279 },
    { sku: 'HOM-CHR-19',  name: 'Ember Lounge Chair',          price: 899 }
  ];

  const SALESPEOPLE = [
    { id: 'S1', name: 'Alex Kim',      role: 'Senior AE' },
    { id: 'S2', name: 'Sarah Miller',  role: 'AE' },
    { id: 'S3', name: 'James Doe',     role: 'AE' },
    { id: 'S4', name: 'Ava Lee',       role: 'Junior AE' },
    { id: 'S5', name: 'Ryan Green',    role: 'AE' }
  ];

  /* ----------------- Sort helper ----------------- */
  function attachSort(tableEl, dataset, rerender, defaults) {
    const state = { key: (defaults && defaults.key) || null, dir: (defaults && defaults.dir) || 'asc' };
    const heads = tableEl.querySelectorAll('.sales-th-sortable');
    heads.forEach(function (th) {
      th.addEventListener('click', function () {
        const key = th.getAttribute('data-sort-key');
        if (state.key === key) {
          state.dir = state.dir === 'asc' ? 'desc' : 'asc';
        } else {
          state.key = key;
          state.dir = 'asc';
        }
        heads.forEach(h => h.classList.remove('is-asc', 'is-desc'));
        th.classList.add(state.dir === 'asc' ? 'is-asc' : 'is-desc');
        dataset.sort(function (a, b) {
          let av = a[key], bv = b[key];
          if (av instanceof Date || (typeof av === 'string' && /\d{4}-\d{2}-\d{2}/.test(av))) {
            av = new Date(av).getTime(); bv = new Date(bv).getTime();
          }
          if (typeof av === 'string') av = av.toLowerCase();
          if (typeof bv === 'string') bv = bv.toLowerCase();
          if (av < bv) return state.dir === 'asc' ? -1 : 1;
          if (av > bv) return state.dir === 'asc' ? 1 : -1;
          return 0;
        });
        rerender();
      });
    });
    return state;
  }

  /* ----------------- Selection helper ----------------- */
  function attachSelection(container, opts) {
    const selectAllEl = container.querySelector('[data-sales-select-all]');
    const bulkEl      = container.querySelector('[data-sales-bulk]');
    const bulkCountEl = container.querySelector('[data-sales-bulk-count]');
    const selected = new Set();

    function refreshBulk() {
      const total = container.querySelectorAll('[data-sales-select]').length;
      if (bulkEl) bulkEl.classList.toggle('is-active', selected.size > 0);
      if (bulkCountEl) bulkCountEl.textContent = String(selected.size);
      if (selectAllEl) {
        selectAllEl.checked = selected.size > 0 && selected.size === total;
        selectAllEl.indeterminate = selected.size > 0 && selected.size < total;
      }
    }

    container.addEventListener('change', function (e) {
      const t = e.target;
      if (t && t.matches('[data-sales-select]')) {
        const id = t.getAttribute('data-sales-id');
        const row = t.closest('tr') || t.closest('[data-sales-row]');
        if (t.checked) selected.add(id); else selected.delete(id);
        if (row) row.classList.toggle('is-selected', t.checked);
        refreshBulk();
      }
      if (t && t.matches('[data-sales-select-all]')) {
        const checked = t.checked;
        container.querySelectorAll('[data-sales-select]').forEach(function (cb) {
          cb.checked = checked;
          const id = cb.getAttribute('data-sales-id');
          const row = cb.closest('tr') || cb.closest('[data-sales-row]');
          if (checked) selected.add(id); else selected.delete(id);
          if (row) row.classList.toggle('is-selected', checked);
        });
        refreshBulk();
      }
    });

    return {
      get: () => Array.from(selected),
      clear: () => { selected.clear(); refreshBulk(); },
      refresh: refreshBulk
    };
  }

  /* ----------------- Skeleton loader ----------------- */
  function withSkeleton(container, skeletonHtml, done) {
    container.innerHTML = skeletonHtml;
    setTimeout(done, 400);
  }

  /* ------------- expose ------------- */
  window.SalesLists = {
    initials, avatarClass, escapeHtml,
    fmtMoney, fmtCompactMoney, fmtDate, fmtDateTime, relativeTime, daysBetween,
    debounce, orchidToast, renderStatusPill, statusMap,
    CUSTOMERS, PRODUCTS, SALESPEOPLE,
    attachSort, attachSelection, withSkeleton
  };

  /* ============================================================
     ============= PAGE: QUOTATIONS ==============================
     ============================================================ */
  function initQuotations(root) {
    const S = window.SalesLists;

    // Sample quotations
    const STATUSES = ['draft', 'sent', 'accepted', 'expired'];
    const today = new Date('2026-07-23');

    function daysFromToday(n) { const d = new Date(today); d.setDate(d.getDate() + n); return d.toISOString().slice(0,10); }

    const QUOTES = [
      { id: 'QUO-2026-1042', client: 0,  issued: daysFromToday(-2),  validUntil: daysFromToday(12), amount: 18450.00, status: 'sent',     sp: 0, items: 6 },
      { id: 'QUO-2026-1041', client: 1,  issued: daysFromToday(-4),  validUntil: daysFromToday(2),  amount:  8620.50, status: 'sent',     sp: 1, items: 3 },
      { id: 'QUO-2026-1040', client: 2,  issued: daysFromToday(-6),  validUntil: daysFromToday(24), amount: 42980.00, status: 'sent',     sp: 0, items: 12 },
      { id: 'QUO-2026-1039', client: 3,  issued: daysFromToday(-9),  validUntil: daysFromToday(-1), amount:  3210.00, status: 'expired',  sp: 2, items: 2 },
      { id: 'QUO-2026-1038', client: 4,  issued: daysFromToday(-11), validUntil: daysFromToday(19), amount: 12750.00, status: 'accepted', sp: 3, items: 5 },
      { id: 'QUO-2026-1037', client: 5,  issued: daysFromToday(-14), validUntil: daysFromToday(16), amount: 96420.00, status: 'sent',     sp: 0, items: 18 },
      { id: 'QUO-2026-1036', client: 6,  issued: daysFromToday(-16), validUntil: daysFromToday(14), amount:  5490.00, status: 'draft',    sp: 4, items: 3 },
      { id: 'QUO-2026-1035', client: 7,  issued: daysFromToday(-18), validUntil: daysFromToday(-3), amount:  2180.00, status: 'expired',  sp: 2, items: 1 },
      { id: 'QUO-2026-1034', client: 8,  issued: daysFromToday(-21), validUntil: daysFromToday(9),  amount: 15680.00, status: 'accepted', sp: 1, items: 7 },
      { id: 'QUO-2026-1033', client: 9,  issued: daysFromToday(-24), validUntil: daysFromToday(6),  amount: 28450.00, status: 'sent',     sp: 3, items: 9 },
      { id: 'QUO-2026-1032', client: 10, issued: daysFromToday(-27), validUntil: daysFromToday(3),  amount:118920.00, status: 'sent',     sp: 0, items: 22 },
      { id: 'QUO-2026-1031', client: 11, issued: daysFromToday(-30), validUntil: daysFromToday(1),  amount:  6740.00, status: 'draft',    sp: 4, items: 4 },
      { id: 'QUO-2026-1030', client: 12, issued: daysFromToday(-32), validUntil: daysFromToday(30), amount:  9820.00, status: 'draft',    sp: 2, items: 5 },
      { id: 'QUO-2026-1029', client: 13, issued: daysFromToday(-35), validUntil: daysFromToday(-8), amount:  4530.00, status: 'expired',  sp: 1, items: 2 }
    ];

    let data = QUOTES.slice();
    const state = { search: '', statuses: new Set(STATUSES), sp: 'all', validFrom: '', validTo: '' };

    const bodyEl   = root.querySelector('[data-quot-body]');
    const emptyEl  = root.querySelector('[data-quot-empty]');
    const countEl  = root.querySelector('[data-quot-count]');
    const tableWrap= root.querySelector('[data-quot-table]');
    const kpiEls   = root.querySelectorAll('[data-quot-kpi]');

    // KPIs
    function updateKpis() {
      STATUSES.forEach(function (s) {
        const rows = QUOTES.filter(q => q.status === s);
        const kpi = root.querySelector('[data-quot-kpi="' + s + '"]');
        if (!kpi) return;
        kpi.querySelector('[data-quot-kpi-count]').textContent = String(rows.length);
        kpi.querySelector('[data-quot-kpi-value]').textContent = S.fmtCompactMoney(rows.reduce((a,b)=>a+b.amount,0));
      });
    }

    function countdownFor(validUntil) {
      const days = S.daysBetween(today, validUntil);
      if (days < 0) return { cls: 'sales-countdown--muted', icon: 'x-circle',  text: 'Expired ' + Math.abs(days) + 'd ago' };
      if (days <= 3) return { cls: 'sales-countdown--red',   icon: 'exclamation-triangle-fill', text: days === 0 ? 'Due today' : days + 'd left' };
      if (days <= 10) return { cls: 'sales-countdown--amber', icon: 'clock-fill', text: days + 'd left' };
      return { cls: 'sales-countdown--green', icon: 'check-circle-fill', text: days + 'd left' };
    }

    function filtered() {
      const q = state.search.toLowerCase();
      return data.filter(function (r) {
        const cust = S.CUSTOMERS[r.client];
        if (!state.statuses.has(r.status)) return false;
        if (state.sp !== 'all' && String(r.sp) !== state.sp) return false;
        if (q && !(r.id.toLowerCase().includes(q) || cust.name.toLowerCase().includes(q))) return false;
        if (state.validFrom && r.validUntil < state.validFrom) return false;
        if (state.validTo   && r.validUntil > state.validTo) return false;
        return true;
      });
    }

    function render() {
      const rows = filtered();
      countEl.textContent = rows.length + ' quote' + (rows.length === 1 ? '' : 's');
      if (!rows.length) {
        tableWrap.classList.add('d-none');
        emptyEl.classList.remove('d-none');
        return;
      }
      tableWrap.classList.remove('d-none');
      emptyEl.classList.add('d-none');

      bodyEl.innerHTML = rows.map(function (r) {
        const cust = S.CUSTOMERS[r.client];
        const sp   = S.SALESPEOPLE[r.sp];
        const cd   = countdownFor(r.validUntil);
        return '' +
          '<tr data-quot-row data-id="' + r.id + '">' +
            '<td><input class="form-check-input" type="checkbox" data-sales-select data-sales-id="' + r.id + '" aria-label="Select ' + r.id + '"></td>' +
            '<td>' +
              '<a href="#" class="sales-doc-link" data-quot-view="' + r.id + '">' + r.id + '</a>' +
              '<div class="text-body-secondary small">' + S.escapeHtml(cust.name) + '</div>' +
            '</td>' +
            '<td class="sales-mono">' + S.fmtDate(r.issued) + '</td>' +
            '<td>' +
              '<div>' + S.fmtDate(r.validUntil) + '</div>' +
              '<span class="sales-countdown ' + cd.cls + '"><i class="bi bi-' + cd.icon + '"></i>' + cd.text + '</span>' +
            '</td>' +
            '<td class="sales-money">' + S.fmtMoney(r.amount) + '</td>' +
            '<td>' + S.renderStatusPill(r.status) + '</td>' +
            '<td>' +
              '<div class="d-flex align-items-center gap-2">' +
                '<span class="sales-avatar-sm ' + S.avatarClass(sp.name) + '">' + S.initials(sp.name) + '</span>' +
                '<div class="small"><div class="fw-medium">' + S.escapeHtml(sp.name) + '</div><div class="text-body-secondary sales-xsm">' + S.escapeHtml(sp.role) + '</div></div>' +
              '</div>' +
            '</td>' +
            '<td class="text-end">' +
              '<div class="d-inline-flex gap-1">' +
                '<button class="sales-icon-btn" type="button" title="View" data-quot-view="' + r.id + '"><i class="bi bi-eye"></i></button>' +
                '<button class="sales-icon-btn sales-icon-btn--success" type="button" title="Convert to Order" data-quot-convert="' + r.id + '"><i class="bi bi-arrow-right-circle"></i></button>' +
                '<button class="sales-icon-btn" type="button" title="Send" data-quot-send="' + r.id + '"><i class="bi bi-send"></i></button>' +
                '<button class="sales-icon-btn" type="button" title="Duplicate" data-quot-dup="' + r.id + '"><i class="bi bi-files"></i></button>' +
                '<button class="sales-icon-btn sales-icon-btn--danger" type="button" title="Delete" data-quot-del="' + r.id + '"><i class="bi bi-trash"></i></button>' +
              '</div>' +
            '</td>' +
          '</tr>';
      }).join('');
    }

    // Skeleton on first paint
    const skel = Array.from({ length: 6 }).map(() =>
      '<tr class="sales-skel-row"><td colspan="8"><span class="sales-skel"></span></td></tr>'
    ).join('');
    S.withSkeleton(bodyEl, skel, function () {
      render();
      updateKpis();
    });

    /* Filters */
    const searchEl = root.querySelector('[data-quot-search]');
    if (searchEl) searchEl.addEventListener('input', S.debounce(function () { state.search = searchEl.value; render(); }, 220));

    const spEl = root.querySelector('[data-quot-sp]');
    if (spEl) spEl.addEventListener('change', function () { state.sp = spEl.value; render(); });

    const vfEl = root.querySelector('[data-quot-valid-from]');
    const vtEl = root.querySelector('[data-quot-valid-to]');
    if (vfEl) vfEl.addEventListener('change', function () { state.validFrom = vfEl.value; render(); });
    if (vtEl) vtEl.addEventListener('change', function () { state.validTo = vtEl.value; render(); });

    // Status multi-select
    root.querySelectorAll('[data-quot-status]').forEach(function (cb) {
      cb.checked = true;
      cb.addEventListener('change', function () {
        if (cb.checked) state.statuses.add(cb.value); else state.statuses.delete(cb.value);
        const label = root.querySelector('[data-quot-status-label]');
        if (label) label.textContent = state.statuses.size === STATUSES.length ? 'All statuses' : (state.statuses.size + ' selected');
        render();
      });
    });

    // Reset
    const resetBtn = root.querySelector('[data-quot-reset]');
    if (resetBtn) resetBtn.addEventListener('click', function () {
      state.search = ''; state.sp = 'all'; state.validFrom = ''; state.validTo = '';
      state.statuses = new Set(STATUSES);
      root.querySelectorAll('[data-quot-status]').forEach(cb => cb.checked = true);
      const label = root.querySelector('[data-quot-status-label]');
      if (label) label.textContent = 'All statuses';
      if (searchEl) searchEl.value = '';
      if (spEl) spEl.value = 'all';
      if (vfEl) vfEl.value = ''; if (vtEl) vtEl.value = '';
      render();
      S.orchidToast('Filters reset', { kind: 'info' });
    });

    // Sort
    S.attachSort(root.querySelector('[data-quot-table] table'), data, render);

    // Selection
    const selection = S.attachSelection(root);

    // Bulk actions
    root.querySelectorAll('[data-quot-bulk]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const action = btn.getAttribute('data-quot-bulk');
        const ids = selection.get();
        if (!ids.length) { S.orchidToast('No quotes selected', { kind: 'warning' }); return; }
        if (action === 'delete') {
          data = data.filter(r => !ids.includes(r.id));
          selection.clear(); render(); updateKpis();
          S.orchidToast(ids.length + ' quote(s) deleted', { kind: 'danger' });
        } else if (action === 'send') {
          S.orchidToast(ids.length + ' quote(s) sent to clients', { kind: 'success' });
        } else if (action === 'export') {
          S.orchidToast('Exporting ' + ids.length + ' quotes as CSV…', { kind: 'info' });
        }
      });
    });

    /* Row actions (delegation) */
    let convertPendingId = null;
    const convertModalEl = document.getElementById('quotConvertModal');
    const convertModal = convertModalEl ? new bootstrap.Modal(convertModalEl) : null;

    bodyEl.addEventListener('click', function (e) {
      const v = e.target.closest('[data-quot-view]');
      if (v) { e.preventDefault(); openDrawer(v.getAttribute('data-quot-view')); return; }
      const c = e.target.closest('[data-quot-convert]');
      if (c) { convertPendingId = c.getAttribute('data-quot-convert'); document.getElementById('quotConvertLabel').textContent = 'Convert ' + convertPendingId + ' to an order?'; convertModal && convertModal.show(); return; }
      const s = e.target.closest('[data-quot-send]');
      if (s) { const id = s.getAttribute('data-quot-send'); const q = data.find(r => r.id === id); if (q) { q.status = 'sent'; render(); updateKpis(); S.orchidToast(id + ' sent to client', { kind: 'success' }); } return; }
      const d = e.target.closest('[data-quot-dup]');
      if (d) { const id = d.getAttribute('data-quot-dup'); const src = data.find(r => r.id === id); if (src) { const copy = Object.assign({}, src, { id: 'QUO-2026-' + (1050 + Math.floor(Math.random()*99)), status: 'draft' }); data.unshift(copy); render(); updateKpis(); S.orchidToast('Duplicated as ' + copy.id, { kind: 'info' }); } return; }
      const del = e.target.closest('[data-quot-del]');
      if (del) { const id = del.getAttribute('data-quot-del'); data = data.filter(r => r.id !== id); render(); updateKpis(); S.orchidToast('Deleted ' + id, { kind: 'danger' }); return; }
    });

    const confirmBtn = document.getElementById('quotConvertConfirm');
    if (confirmBtn) confirmBtn.addEventListener('click', function () {
      const q = data.find(r => r.id === convertPendingId);
      if (q) {
        q.status = 'accepted';
        render(); updateKpis();
        const newOrd = 'ORD-2026-' + (2100 + Math.floor(Math.random()*799));
        S.orchidToast('Converted to ' + newOrd, { kind: 'success', title: 'Order created' });
      }
      convertModal.hide();
    });

    /* Drawer */
    const drawerEl = document.getElementById('quotDrawer');
    const drawer = drawerEl ? new bootstrap.Offcanvas(drawerEl) : null;

    function openDrawer(id) {
      const q = data.find(r => r.id === id);
      if (!q) return;
      const cust = S.CUSTOMERS[q.client];
      const sp = S.SALESPEOPLE[q.sp];
      document.getElementById('quotDrawerTitle').textContent = q.id;
      document.getElementById('quotDrawerSub').textContent = cust.name + ' · ' + cust.contact;
      document.getElementById('quotDrawerStatus').innerHTML = S.renderStatusPill(q.status);

      // Fake items derived from products
      const itemCount = q.items;
      const items = [];
      let subtotal = 0;
      for (let i = 0; i < itemCount; i++) {
        const p = S.PRODUCTS[(i + q.client) % S.PRODUCTS.length];
        const qty = 1 + ((i * 3 + q.client) % 5);
        const lineTotal = p.price * qty;
        subtotal += lineTotal;
        items.push({ p, qty, lineTotal });
      }
      // Normalize to quote amount
      const factor = q.amount / (subtotal || 1);
      items.forEach(it => it.lineTotal = it.lineTotal * factor);
      const tax = q.amount * 0.085;
      const grand = q.amount + tax;

      const tbody = document.getElementById('quotDrawerItems');
      tbody.innerHTML = items.map(function (it) {
        return '<tr><td>' + S.escapeHtml(it.p.name) + '<div class="text-body-secondary sales-xsm-2">' + it.p.sku + '</div></td>' +
               '<td class="text-center sales-mono">' + it.qty + '</td>' +
               '<td class="text-end sales-mono">' + S.fmtMoney(it.p.price) + '</td>' +
               '<td class="text-end sales-money sales-money--sm">' + S.fmtMoney(it.lineTotal) + '</td></tr>';
      }).join('');

      document.getElementById('quotDrawerSubtotal').textContent = S.fmtMoney(q.amount);
      document.getElementById('quotDrawerTax').textContent = S.fmtMoney(tax);
      document.getElementById('quotDrawerGrand').textContent = S.fmtMoney(grand);

      // Activity log
      const acts = document.getElementById('quotDrawerActivity');
      const issued = new Date(q.issued);
      const evts = [
        { who: sp.name, what: 'created this quotation', when: issued },
        { who: sp.name, what: 'emailed to ' + cust.contact, when: new Date(issued.getTime() + 3600 * 1000 * 2) },
        { who: cust.contact, what: 'opened the quote', when: new Date(issued.getTime() + 3600 * 1000 * 5) },
        { who: sp.name, what: 'sent follow-up reminder', when: new Date(issued.getTime() + 86400 * 1000 * 3) }
      ];
      acts.innerHTML = evts.map(function (e) {
        return '<li><strong>' + S.escapeHtml(e.who) + '</strong> ' + S.escapeHtml(e.what) + '<br><time>' + S.relativeTime(e.when) + '</time></li>';
      }).join('');

      drawer && drawer.show();
    }

    // Drawer "Send to client" button
    const drawerSendBtn = document.getElementById('quotDrawerSend');
    if (drawerSendBtn) drawerSendBtn.addEventListener('click', function () {
      S.orchidToast('Quote sent to client', { kind: 'success' });
      drawer && drawer.hide();
    });
  }

  /* ============================================================
     ============= PAGE: ORDERS ==================================
     ============================================================ */
  function initOrders(root) {
    const S = window.SalesLists;
    const today = new Date('2026-07-23');
    function daysFromToday(n) { const d = new Date(today); d.setDate(d.getDate() + n); return d.toISOString().slice(0,10); }

    const FULFILL = ['pending', 'confirmed', 'production', 'shipped', 'delivered'];
    const CARRIERS = ['FedEx', 'UPS', 'DHL', 'USPS'];

    const ORDERS = [
      { id: 'ORD-2026-2148', date: daysFromToday(-1),  cust: 8,  itemsN: 4,  total:  842.50, fulfill: 'pending',    pay: 'unpaid',  carrier: null,   track: null,             products: [0, 3] },
      { id: 'ORD-2026-2147', date: daysFromToday(-1),  cust: 2,  itemsN: 12, total: 3218.00, fulfill: 'confirmed',  pay: 'paid',    carrier: null,   track: null,             products: [5, 6, 10] },
      { id: 'ORD-2026-2146', date: daysFromToday(-2),  cust: 0,  itemsN: 3,  total:  567.00, fulfill: 'production', pay: 'partial', carrier: null,   track: null,             products: [1, 8] },
      { id: 'ORD-2026-2145', date: daysFromToday(-3),  cust: 6,  itemsN: 8,  total: 1980.00, fulfill: 'shipped',    pay: 'paid',    carrier: 'FedEx', track: '785492001238',   products: [0, 4] },
      { id: 'ORD-2026-2144', date: daysFromToday(-4),  cust: 4,  itemsN: 5,  total:  945.00, fulfill: 'shipped',    pay: 'paid',    carrier: 'UPS',   track: '1Z999AA10123456784', products: [2, 3] },
      { id: 'ORD-2026-2143', date: daysFromToday(-5),  cust: 11, itemsN: 7,  total: 1620.00, fulfill: 'delivered',  pay: 'paid',    carrier: 'DHL',   track: 'DHL2938471982',  products: [7, 9] },
      { id: 'ORD-2026-2142', date: daysFromToday(-6),  cust: 5,  itemsN: 22, total: 8760.00, fulfill: 'delivered',  pay: 'paid',    carrier: 'FedEx', track: '785492001500',   products: [5, 11, 6] },
      { id: 'ORD-2026-2141', date: daysFromToday(-7),  cust: 9,  itemsN: 2,  total:  278.00, fulfill: 'pending',    pay: 'unpaid',  carrier: null,   track: null,             products: [10] },
      { id: 'ORD-2026-2140', date: daysFromToday(-8),  cust: 1,  itemsN: 6,  total: 1345.00, fulfill: 'confirmed',  pay: 'paid',    carrier: null,   track: null,             products: [3, 8] },
      { id: 'ORD-2026-2139', date: daysFromToday(-10), cust: 3,  itemsN: 14, total: 2890.00, fulfill: 'production', pay: 'partial', carrier: null,   track: null,             products: [0, 7] },
      { id: 'ORD-2026-2138', date: daysFromToday(-12), cust: 7,  itemsN: 4,  total:  620.00, fulfill: 'delivered',  pay: 'refunded',carrier: 'USPS',  track: '9400111899223144',products: [8, 9] },
      { id: 'ORD-2026-2137', date: daysFromToday(-14), cust: 10, itemsN: 9,  total: 4210.00, fulfill: 'shipped',    pay: 'paid',    carrier: 'FedEx', track: '785492001612',   products: [5, 11] },
      { id: 'ORD-2026-2136', date: daysFromToday(-16), cust: 12, itemsN: 3,  total:  489.00, fulfill: 'delivered',  pay: 'paid',    carrier: 'UPS',   track: '1Z999AA10123456901', products: [1, 8] },
      { id: 'ORD-2026-2135', date: daysFromToday(-18), cust: 13, itemsN: 6,  total: 1782.00, fulfill: 'delivered',  pay: 'paid',    carrier: 'DHL',   track: 'DHL2938472999',  products: [6, 10] }
    ];

    let data = ORDERS.slice();
    const state = { search: '', fulfill: 'all', pay: 'all', carrier: 'all', dateFrom: '', dateTo: '' };

    const bodyEl  = root.querySelector('[data-ord-body]');
    const emptyEl = root.querySelector('[data-ord-empty]');
    const countEl = root.querySelector('[data-ord-count]');
    const tableWrap= root.querySelector('[data-ord-table]');

    function updatePipeline() {
      FULFILL.forEach(function (f) {
        const rows = data.filter(r => r.fulfill === f);
        const el = root.querySelector('[data-ord-stage="' + f + '"]');
        if (!el) return;
        el.querySelector('[data-ord-stage-count]').textContent = rows.length;
        const rev = rows.reduce((a,b)=>a+b.total, 0);
        el.querySelector('[data-ord-stage-rev]').textContent = S.fmtCompactMoney(rev);
      });
      const total = data.reduce((a,b)=>a+b.total, 0);
      const totalEl = root.querySelector('[data-ord-total-rev]');
      const totalCntEl = root.querySelector('[data-ord-total-count]');
      if (totalEl) totalEl.textContent = S.fmtMoney(total);
      if (totalCntEl) totalCntEl.textContent = data.length;
    }

    function iconForFulfill(f) {
      return ({ pending: 'hourglass-split', confirmed: 'check2-circle', production: 'gear', shipped: 'truck', delivered: 'box2-heart' })[f] || 'circle';
    }

    function filtered() {
      const q = state.search.toLowerCase();
      const active = state.fulfill === 'all' ? null : root.querySelector('[data-ord-stage].is-active');
      return data.filter(function (r) {
        const cust = S.CUSTOMERS[r.cust];
        if (state.fulfill !== 'all' && r.fulfill !== state.fulfill) return false;
        if (state.pay !== 'all' && r.pay !== state.pay) return false;
        if (state.carrier !== 'all' && (r.carrier || '') !== state.carrier) return false;
        if (q && !(r.id.toLowerCase().includes(q) || cust.name.toLowerCase().includes(q))) return false;
        if (state.dateFrom && r.date < state.dateFrom) return false;
        if (state.dateTo && r.date > state.dateTo) return false;
        return true;
      });
    }

    function render() {
      const rows = filtered();
      countEl.textContent = rows.length + ' order' + (rows.length === 1 ? '' : 's');
      if (!rows.length) {
        tableWrap.classList.add('d-none');
        emptyEl.classList.remove('d-none');
        return;
      }
      tableWrap.classList.remove('d-none');
      emptyEl.classList.add('d-none');

      bodyEl.innerHTML = rows.map(function (r) {
        const cust = S.CUSTOMERS[r.cust];
        const initials = S.initials(cust.name);
        const isShipped = r.fulfill === 'shipped' || r.fulfill === 'delivered';
        return '' +
          '<tr data-ord-row data-id="' + r.id + '">' +
            '<td><input class="form-check-input" type="checkbox" data-sales-select data-sales-id="' + r.id + '" aria-label="Select ' + r.id + '"></td>' +
            '<td>' +
              '<a href="#" class="sales-doc-link" data-ord-view="' + r.id + '">' + r.id + '</a>' +
              '<div class="text-body-secondary small">' + S.fmtDate(r.date) + '</div>' +
            '</td>' +
            '<td>' +
              '<div class="d-flex align-items-center gap-2">' +
                '<span class="sales-avatar-sm ' + S.avatarClass(cust.name) + '">' + initials + '</span>' +
                '<div><div class="fw-medium">' + S.escapeHtml(cust.name) + '</div><div class="text-body-secondary sales-xsm">' + S.escapeHtml(cust.contact) + '</div></div>' +
              '</div>' +
            '</td>' +
            '<td class="sales-mono text-center">' + r.itemsN + '</td>' +
            '<td class="sales-money">' + S.fmtMoney(r.total) + '</td>' +
            '<td><span class="ord-fulfill ord-fulfill--' + r.fulfill + '"><i class="bi bi-' + iconForFulfill(r.fulfill) + '"></i>' + r.fulfill + '</span></td>' +
            '<td><span class="ord-payment ord-payment--' + r.pay + '">' + r.pay + '</span></td>' +
            '<td>' +
              (r.carrier
                ? '<div class="ord-shipping"><div class="ord-shipping__carrier">' + r.carrier + '</div><div class="ord-shipping__track">' + r.track + '</div></div>'
                : '<span class="ord-shipping ord-shipping--none small">Not yet shipped</span>') +
            '</td>' +
            '<td class="text-end">' +
              '<div class="d-inline-flex gap-1">' +
                '<span class="sales-row-hover">' +
                  (!isShipped ? '<button class="sales-icon-btn sales-icon-btn--success" type="button" title="Mark shipped" data-ord-ship="' + r.id + '"><i class="bi bi-truck"></i></button>' : '') +
                  '<button class="sales-icon-btn" type="button" title="Print packing slip" data-ord-print="' + r.id + '"><i class="bi bi-printer"></i></button>' +
                '</span>' +
                '<button class="sales-icon-btn" type="button" title="View" data-ord-view="' + r.id + '"><i class="bi bi-eye"></i></button>' +
                '<div class="dropdown d-inline-block">' +
                  '<button class="sales-icon-btn" type="button" data-bs-toggle="dropdown" aria-label="More"><i class="bi bi-three-dots-vertical"></i></button>' +
                  '<ul class="dropdown-menu dropdown-menu-end">' +
                    '<li><a class="dropdown-item" href="#" data-ord-view="' + r.id + '"><i class="bi bi-eye me-2"></i>View details</a></li>' +
                    '<li><a class="dropdown-item" href="#" data-ord-reship="' + r.id + '"><i class="bi bi-arrow-repeat me-2"></i>Reship</a></li>' +
                    '<li><a class="dropdown-item" href="#" data-ord-refund="' + r.id + '"><i class="bi bi-cash-coin me-2"></i>Refund</a></li>' +
                    '<li><hr class="dropdown-divider"></li>' +
                    '<li><a class="dropdown-item text-danger" href="#" data-ord-cancel="' + r.id + '"><i class="bi bi-x-circle me-2"></i>Cancel order</a></li>' +
                  '</ul>' +
                '</div>' +
              '</div>' +
            '</td>' +
          '</tr>';
      }).join('');
    }

    // Skeleton
    const skel = Array.from({ length: 6 }).map(() =>
      '<tr class="sales-skel-row"><td colspan="9"><span class="sales-skel"></span></td></tr>'
    ).join('');
    S.withSkeleton(bodyEl, skel, function () {
      render();
      updatePipeline();
    });

    // Filters
    const searchEl = root.querySelector('[data-ord-search]');
    if (searchEl) searchEl.addEventListener('input', S.debounce(function () { state.search = searchEl.value; render(); }, 220));
    const fEl = root.querySelector('[data-ord-fulfill-filter]');
    const pEl = root.querySelector('[data-ord-pay-filter]');
    const cEl = root.querySelector('[data-ord-carrier-filter]');
    const dfEl = root.querySelector('[data-ord-date-from]');
    const dtEl = root.querySelector('[data-ord-date-to]');
    if (fEl) fEl.addEventListener('change', function () { state.fulfill = fEl.value; syncStageActive(); render(); });
    if (pEl) pEl.addEventListener('change', function () { state.pay = pEl.value; render(); });
    if (cEl) cEl.addEventListener('change', function () { state.carrier = cEl.value; render(); });
    if (dfEl) dfEl.addEventListener('change', function () { state.dateFrom = dfEl.value; render(); });
    if (dtEl) dtEl.addEventListener('change', function () { state.dateTo = dtEl.value; render(); });

    function syncStageActive() {
      root.querySelectorAll('[data-ord-stage]').forEach(function (el) {
        el.classList.toggle('is-active', el.getAttribute('data-ord-stage') === state.fulfill);
      });
    }

    // Pipeline click filters by stage
    root.querySelectorAll('[data-ord-stage]').forEach(function (el) {
      el.addEventListener('click', function () {
        const stage = el.getAttribute('data-ord-stage');
        state.fulfill = (state.fulfill === stage) ? 'all' : stage;
        if (fEl) fEl.value = state.fulfill;
        syncStageActive();
        render();
      });
    });

    const resetBtn = root.querySelector('[data-ord-reset]');
    if (resetBtn) resetBtn.addEventListener('click', function () {
      state.search = ''; state.fulfill = 'all'; state.pay = 'all'; state.carrier = 'all'; state.dateFrom = ''; state.dateTo = '';
      if (searchEl) searchEl.value = '';
      if (fEl) fEl.value = 'all'; if (pEl) pEl.value = 'all'; if (cEl) cEl.value = 'all';
      if (dfEl) dfEl.value = ''; if (dtEl) dtEl.value = '';
      syncStageActive(); render();
      S.orchidToast('Filters reset', { kind: 'info' });
    });

    // Sort
    S.attachSort(root.querySelector('[data-ord-table] table'), data, render);

    // Selection
    const selection = S.attachSelection(root);

    // Bulk actions
    root.querySelectorAll('[data-ord-bulk]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const action = btn.getAttribute('data-ord-bulk');
        const ids = selection.get();
        if (!ids.length) { S.orchidToast('No orders selected', { kind: 'warning' }); return; }
        if (action === 'ship') {
          ids.forEach(function (id) { const r = data.find(x => x.id === id); if (r && (r.fulfill === 'pending' || r.fulfill === 'confirmed' || r.fulfill === 'production')) { r.fulfill = 'shipped'; r.carrier = r.carrier || CARRIERS[Math.floor(Math.random()*CARRIERS.length)]; r.track = r.track || 'TRK' + Math.floor(Math.random()*1e10); } });
          selection.clear(); render(); updatePipeline();
          S.orchidToast(ids.length + ' order(s) marked shipped', { kind: 'success' });
        } else if (action === 'export') {
          S.orchidToast('Exporting ' + ids.length + ' orders…', { kind: 'info' });
        } else if (action === 'print') {
          S.orchidToast('Printing ' + ids.length + ' packing slips…', { kind: 'info' });
        }
      });
    });

    // Row action delegation
    bodyEl.addEventListener('click', function (e) {
      const v = e.target.closest('[data-ord-view]');
      if (v) { e.preventDefault(); openDetail(v.getAttribute('data-ord-view')); return; }
      const s = e.target.closest('[data-ord-ship]');
      if (s) { const r = data.find(x => x.id === s.getAttribute('data-ord-ship')); if (r) { r.fulfill = 'shipped'; r.carrier = r.carrier || CARRIERS[Math.floor(Math.random()*CARRIERS.length)]; r.track = r.track || 'TRK' + Math.floor(Math.random()*1e10); render(); updatePipeline(); S.orchidToast(r.id + ' marked shipped via ' + r.carrier, { kind: 'success' }); } return; }
      const p = e.target.closest('[data-ord-print]');
      if (p) { S.orchidToast('Packing slip for ' + p.getAttribute('data-ord-print') + ' sent to printer', { kind: 'info' }); return; }
      const re = e.target.closest('[data-ord-reship]');
      if (re) { e.preventDefault(); S.orchidToast('Reshipment created for ' + re.getAttribute('data-ord-reship'), { kind: 'info' }); return; }
      const rf = e.target.closest('[data-ord-refund]');
      if (rf) { e.preventDefault(); const r = data.find(x => x.id === rf.getAttribute('data-ord-refund')); if (r) { r.pay = 'refunded'; render(); S.orchidToast('Refund initiated for ' + r.id, { kind: 'warning' }); } return; }
      const cn = e.target.closest('[data-ord-cancel]');
      if (cn) { e.preventDefault(); const id = cn.getAttribute('data-ord-cancel'); data = data.filter(x => x.id !== id); render(); updatePipeline(); S.orchidToast('Cancelled ' + id, { kind: 'danger' }); return; }
    });

    // Detail modal
    const modalEl = document.getElementById('ordDetailModal');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

    function openDetail(id) {
      const r = data.find(x => x.id === id);
      if (!r) return;
      const cust = S.CUSTOMERS[r.cust];
      document.getElementById('ordDetailId').textContent = r.id;
      document.getElementById('ordDetailDate').textContent = S.fmtDate(r.date);
      document.getElementById('ordDetailCustomer').innerHTML = '<strong>' + S.escapeHtml(cust.name) + '</strong><br>' + S.escapeHtml(cust.contact) + '<br><span class="text-body-secondary">' + S.escapeHtml(cust.email) + '</span>';
      document.getElementById('ordDetailShip').innerHTML = '<strong>Shipping address</strong><br>' + S.escapeHtml(cust.contact) + '<br>1420 Market St, Suite ' + (100 + r.cust) + '<br>' + (['San Francisco','Chicago','Austin','New York','Seattle'][r.cust % 5]) + ', USA';

      const tbody = document.getElementById('ordDetailItems');
      const lineDefs = r.products.map(function (pi, idx) {
        const p = S.PRODUCTS[pi];
        const qty = Math.max(1, Math.ceil(r.itemsN / r.products.length) + idx);
        return { p, qty, gross: p.price * qty };
      });
      const grossTotal = lineDefs.reduce(function (a, b) { return a + b.gross; }, 0) || 1;
      const factor = r.total / grossTotal;
      tbody.innerHTML = lineDefs.map(function (d) {
        const adjLine = d.gross * factor;
        return '<tr><td>' + S.escapeHtml(d.p.name) + '<div class="text-body-secondary small">' + d.p.sku + '</div></td>' +
               '<td class="text-center sales-mono">' + d.qty + '</td>' +
               '<td class="text-end sales-mono">' + S.fmtMoney(d.p.price) + '</td>' +
               '<td class="text-end sales-money sales-money--sm">' + S.fmtMoney(adjLine) + '</td></tr>';
      }).join('');
      document.getElementById('ordDetailTotal').textContent = S.fmtMoney(r.total);

      // Timeline
      const tl = document.getElementById('ordDetailTimeline');
      const stages = FULFILL.slice(0, FULFILL.indexOf(r.fulfill) + 1);
      tl.innerHTML = stages.map(function (st, i) {
        return '<li><strong>' + st.charAt(0).toUpperCase() + st.slice(1) + '</strong> <span class="text-body-secondary small">' + S.fmtDate(new Date(new Date(r.date).getTime() + i * 86400000)) + '</span></li>';
      }).join('');

      document.getElementById('ordDetailFulfill').innerHTML = '<span class="ord-fulfill ord-fulfill--' + r.fulfill + '"><i class="bi bi-' + iconForFulfill(r.fulfill) + '"></i>' + r.fulfill + '</span>';
      document.getElementById('ordDetailPay').innerHTML = '<span class="ord-payment ord-payment--' + r.pay + '">' + r.pay + '</span>';

      modal && modal.show();
    }
  }

  /* ============================================================
     ============= PAGE: PAYMENTS ================================
     ============================================================ */
  function initPayments(root) {
    const S = window.SalesLists;
    const today = new Date('2026-07-23');
    function daysFromToday(n) { const d = new Date(today); d.setDate(d.getDate() + n); return d; }
    function ts(day, h, m) { const d = daysFromToday(day); d.setHours(h, m, 0, 0); return d; }
    function feeCard(amount) { return +(amount * 0.029 + 0.30).toFixed(2); }
    function feeBank() { return 5.00; }

    const METHODS = ['card', 'ach', 'wire', 'paypal', 'stripe'];
    const METHOD_META = {
      card:   { label: 'Credit Card', icon: 'credit-card-2-back-fill', cls: 'sales-method-icon--card' },
      ach:    { label: 'ACH',         icon: 'bank',                    cls: 'sales-method-icon--ach' },
      wire:   { label: 'Wire',        icon: 'send-check',              cls: 'sales-method-icon--wire' },
      paypal: { label: 'PayPal',      icon: 'paypal',                  cls: 'sales-method-icon--paypal' },
      stripe: { label: 'Stripe',      icon: 'stripe',                  cls: 'sales-method-icon--stripe' }
    };

    const PAYMENTS = [
      { id: 'TX-98421', date: ts(0, 10, 24), cust: 0,  invoice: 'INV-2026-0819', method: 'card',   amount: 1240.00, status: 'succeeded' },
      { id: 'TX-98420', date: ts(0,  9, 12), cust: 2,  invoice: 'INV-2026-0818', method: 'stripe', amount:  489.00, status: 'succeeded' },
      { id: 'TX-98419', date: ts(0,  8, 45), cust: 5,  invoice: 'INV-2026-0817', method: 'wire',   amount: 8760.00, status: 'pending'   },
      { id: 'TX-98418', date: ts(-1, 15, 31), cust: 3, invoice: 'INV-2026-0816', method: 'card',   amount:  318.50, status: 'failed'    },
      { id: 'TX-98417', date: ts(-1, 12, 5),  cust: 6, invoice: 'INV-2026-0815', method: 'ach',    amount: 3210.00, status: 'succeeded' },
      { id: 'TX-98416', date: ts(-1, 10, 40), cust: 8, invoice: 'INV-2026-0814', method: 'paypal', amount:  142.00, status: 'disputed'  },
      { id: 'TX-98415', date: ts(-2,  9, 22), cust: 4, invoice: 'INV-2026-0813', method: 'card',   amount:  945.00, status: 'succeeded' },
      { id: 'TX-98414', date: ts(-3, 16, 10), cust: 11,invoice: 'INV-2026-0812', method: 'stripe', amount: 1620.00, status: 'succeeded' },
      { id: 'TX-98413', date: ts(-4, 11, 55), cust: 9, invoice: 'INV-2026-0811', method: 'card',   amount:  278.00, status: 'succeeded' },
      { id: 'TX-98412', date: ts(-5, 14, 2),  cust: 1, invoice: 'INV-2026-0810', method: 'ach',    amount: 1345.00, status: 'succeeded' },
      { id: 'TX-98411', date: ts(-6, 10, 17), cust: 7, invoice: 'INV-2026-0809', method: 'card',   amount:  620.00, status: 'succeeded' },
      { id: 'TX-98410', date: ts(-7, 15, 48), cust: 10,invoice: 'INV-2026-0808', method: 'wire',   amount: 4210.00, status: 'succeeded' },
      { id: 'TX-98409', date: ts(-9, 12, 12), cust: 12,invoice: 'INV-2026-0807', method: 'card',   amount:  489.00, status: 'succeeded' },
      { id: 'TX-98408', date: ts(-11, 9, 34), cust: 13,invoice: 'INV-2026-0806', method: 'stripe', amount: 1782.00, status: 'succeeded' },
      { id: 'TX-98407', date: ts(-14, 8, 51), cust: 5, invoice: 'INV-2026-0805', method: 'card',   amount:  920.00, status: 'succeeded' },
      { id: 'TX-98406', date: ts(-18, 13, 22), cust: 0,invoice: 'INV-2026-0804', method: 'paypal', amount:  360.00, status: 'succeeded' }
    ];

    PAYMENTS.forEach(function (p) {
      p.fee = (p.method === 'wire' || p.method === 'ach') ? feeBank() : feeCard(p.amount);
      p.net = +(p.amount - p.fee).toFixed(2);
    });

    let data = PAYMENTS.slice();
    const state = { search: '', methods: new Set(METHODS), status: 'all', dateFrom: '', dateTo: '' };

    const bodyEl = root.querySelector('[data-pay-body]');
    const emptyEl = root.querySelector('[data-pay-empty]');
    const countEl = root.querySelector('[data-pay-count]');
    const tableWrap = root.querySelector('[data-pay-table]');

    // Metrics
    function isToday(d) { return d.toDateString() === today.toDateString(); }
    function isSameMonth(d) { return d.getFullYear() === today.getFullYear() && d.getMonth() === today.getMonth(); }
    function isSameYear(d) { return d.getFullYear() === today.getFullYear(); }

    function updateMetrics() {
      const succ = PAYMENTS.filter(p => p.status === 'succeeded');
      const todayVal = succ.filter(p => isToday(p.date)).reduce((a,b)=>a+b.amount,0);
      const mtdVal   = succ.filter(p => isSameMonth(p.date)).reduce((a,b)=>a+b.amount,0);
      const ytdVal   = succ.filter(p => isSameYear(p.date)).reduce((a,b)=>a+b.amount,0);
      root.querySelector('[data-pay-metric="today"]').textContent = S.fmtMoney(todayVal);
      root.querySelector('[data-pay-metric="mtd"]').textContent   = S.fmtMoney(mtdVal);
      root.querySelector('[data-pay-metric="ytd"]').textContent   = S.fmtMoney(ytdVal);
    }

    function filtered() {
      const q = state.search.toLowerCase();
      return data.filter(function (r) {
        const cust = S.CUSTOMERS[r.cust];
        if (!state.methods.has(r.method)) return false;
        if (state.status !== 'all' && r.status !== state.status) return false;
        if (q && !(r.id.toLowerCase().includes(q) || cust.name.toLowerCase().includes(q) || r.invoice.toLowerCase().includes(q))) return false;
        const iso = r.date.toISOString().slice(0,10);
        if (state.dateFrom && iso < state.dateFrom) return false;
        if (state.dateTo && iso > state.dateTo) return false;
        return true;
      });
    }

    function render() {
      const rows = filtered();
      countEl.textContent = rows.length + ' transaction' + (rows.length === 1 ? '' : 's');
      if (!rows.length) {
        tableWrap.classList.add('d-none');
        emptyEl.classList.remove('d-none');
        return;
      }
      tableWrap.classList.remove('d-none');
      emptyEl.classList.add('d-none');

      bodyEl.innerHTML = rows.map(function (r) {
        const cust = S.CUSTOMERS[r.cust];
        const m = METHOD_META[r.method];
        return '' +
          '<tr data-pay-row data-id="' + r.id + '">' +
            '<td><input class="form-check-input" type="checkbox" data-sales-select data-sales-id="' + r.id + '" aria-label="Select ' + r.id + '"></td>' +
            '<td>' +
              '<div class="d-flex align-items-center gap-2">' +
                '<span class="sales-method-icon ' + m.cls + '"><i class="bi bi-' + m.icon + '"></i></span>' +
                '<div><div class="pay-txid fw-semibold">' + r.id + '</div><div class="text-body-secondary small">' + m.label + '</div></div>' +
              '</div>' +
            '</td>' +
            '<td class="sales-mono">' + S.fmtDate(r.date) + '<div class="text-body-secondary sales-xsm">' + r.date.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'}) + '</div></td>' +
            '<td>' +
              '<div class="fw-medium">' + S.escapeHtml(cust.name) + '</div>' +
              '<a href="#" class="text-body-secondary small">' + r.invoice + '</a>' +
            '</td>' +
            '<td>' + m.label + '</td>' +
            '<td class="sales-money">' + S.fmtMoney(r.amount) + '</td>' +
            '<td class="pay-fee">−' + S.fmtMoney(r.fee) + '</td>' +
            '<td class="pay-net">' + S.fmtMoney(r.net) + '</td>' +
            '<td>' + S.renderStatusPill(r.status) + '</td>' +
            '<td class="text-end">' +
              '<div class="d-inline-flex gap-1">' +
                '<button class="sales-icon-btn" type="button" title="Receipt" data-pay-receipt="' + r.id + '"><i class="bi bi-receipt"></i></button>' +
                '<button class="sales-icon-btn sales-icon-btn--danger" type="button" title="Refund" data-pay-refund="' + r.id + '"><i class="bi bi-arrow-return-left"></i></button>' +
                '<button class="sales-icon-btn" type="button" title="Dispute" data-pay-dispute="' + r.id + '"><i class="bi bi-shield-exclamation"></i></button>' +
              '</div>' +
            '</td>' +
          '</tr>';
      }).join('');
    }

    const skel = Array.from({ length: 6 }).map(() =>
      '<tr class="sales-skel-row"><td colspan="10"><span class="sales-skel"></span></td></tr>'
    ).join('');
    S.withSkeleton(bodyEl, skel, function () {
      render();
      updateMetrics();
      drawSparklines();
    });

    // Method chips
    root.querySelectorAll('[data-pay-method-chip]').forEach(function (chip) {
      chip.classList.add('is-active');
      chip.addEventListener('click', function () {
        const m = chip.getAttribute('data-pay-method-chip');
        if (state.methods.has(m)) { state.methods.delete(m); chip.classList.remove('is-active'); }
        else { state.methods.add(m); chip.classList.add('is-active'); }
        render();
      });
    });

    // Filters
    const searchEl = root.querySelector('[data-pay-search]');
    if (searchEl) searchEl.addEventListener('input', S.debounce(function () { state.search = searchEl.value; render(); }, 220));
    const statusEl = root.querySelector('[data-pay-status]');
    if (statusEl) statusEl.addEventListener('change', function () { state.status = statusEl.value; render(); });
    const dfEl = root.querySelector('[data-pay-date-from]');
    const dtEl = root.querySelector('[data-pay-date-to]');
    if (dfEl) dfEl.addEventListener('change', function () { state.dateFrom = dfEl.value; render(); });
    if (dtEl) dtEl.addEventListener('change', function () { state.dateTo = dtEl.value; render(); });

    const resetBtn = root.querySelector('[data-pay-reset]');
    if (resetBtn) resetBtn.addEventListener('click', function () {
      state.search = ''; state.status = 'all'; state.dateFrom = ''; state.dateTo = ''; state.methods = new Set(METHODS);
      root.querySelectorAll('[data-pay-method-chip]').forEach(c => c.classList.add('is-active'));
      if (searchEl) searchEl.value = ''; if (statusEl) statusEl.value = 'all';
      if (dfEl) dfEl.value = ''; if (dtEl) dtEl.value = '';
      render(); S.orchidToast('Filters reset', { kind: 'info' });
    });

    // Sort
    S.attachSort(root.querySelector('[data-pay-table] table'), data, render);

    // Selection & bulk
    const selection = S.attachSelection(root);
    root.querySelectorAll('[data-pay-bulk]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const action = btn.getAttribute('data-pay-bulk');
        const ids = selection.get();
        if (!ids.length) { S.orchidToast('No transactions selected', { kind: 'warning' }); return; }
        if (action === 'reconcile') {
          const modalEl = document.getElementById('payReconcileModal');
          if (modalEl) { document.getElementById('payReconcileCount').textContent = ids.length; new bootstrap.Modal(modalEl).show(); }
        } else if (action === 'ledger') {
          S.orchidToast('Ledger export for ' + ids.length + ' rows downloaded', { kind: 'success' });
        } else if (action === 'receipts') {
          S.orchidToast('Sent ' + ids.length + ' receipts by email', { kind: 'success' });
        }
      });
    });

    // Row action handlers
    let pendingRefund = null;
    bodyEl.addEventListener('click', function (e) {
      const rc = e.target.closest('[data-pay-receipt]');
      if (rc) { S.orchidToast('Receipt for ' + rc.getAttribute('data-pay-receipt') + ' downloaded', { kind: 'success', title: 'Downloaded' }); return; }
      const rf = e.target.closest('[data-pay-refund]');
      if (rf) {
        pendingRefund = rf.getAttribute('data-pay-refund');
        const p = data.find(x => x.id === pendingRefund);
        if (p) {
          document.getElementById('payRefundTxid').textContent = p.id;
          document.getElementById('payRefundAmount').value = p.amount.toFixed(2);
          document.getElementById('payRefundOriginal').textContent = S.fmtMoney(p.amount);
          new bootstrap.Modal(document.getElementById('payRefundModal')).show();
        }
        return;
      }
      const dp = e.target.closest('[data-pay-dispute]');
      if (dp) {
        const p = data.find(x => x.id === dp.getAttribute('data-pay-dispute'));
        if (p) {
          document.getElementById('payDisputeTxid').textContent = p.id;
          document.getElementById('payDisputeAmount').textContent = S.fmtMoney(p.amount);
          bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('payDisputeDrawer')).show();
        }
        return;
      }
    });

    // Confirm refund
    const confirmRefund = document.getElementById('payRefundConfirm');
    if (confirmRefund) confirmRefund.addEventListener('click', function () {
      const amt = parseFloat(document.getElementById('payRefundAmount').value || '0');
      const p = data.find(x => x.id === pendingRefund);
      if (p) {
        S.orchidToast('Refunded ' + S.fmtMoney(amt) + ' from ' + p.id, { kind: 'warning' });
      }
      bootstrap.Modal.getInstance(document.getElementById('payRefundModal')).hide();
    });

    // Reconcile button (matches)
    const reconcileConfirm = document.getElementById('payReconcileConfirm');
    if (reconcileConfirm) reconcileConfirm.addEventListener('click', function () {
      const n = document.getElementById('payReconcileCount').textContent;
      S.orchidToast(n + ' transactions reconciled', { kind: 'success' });
      bootstrap.Modal.getInstance(document.getElementById('payReconcileModal')).hide();
    });

    // Dispute submit
    const disputeSubmit = document.getElementById('payDisputeSubmit');
    if (disputeSubmit) disputeSubmit.addEventListener('click', function () {
      S.orchidToast('Dispute evidence submitted', { kind: 'info' });
      bootstrap.Offcanvas.getInstance(document.getElementById('payDisputeDrawer')).hide();
    });

    // File drop preview
    const dropZone = root.querySelector('[data-pay-drop]');
    const fileInput = root.querySelector('[data-pay-file]');
    if (dropZone && fileInput) {
      dropZone.addEventListener('click', function () { fileInput.click(); });
      fileInput.addEventListener('change', function () {
        if (fileInput.files && fileInput.files[0]) {
          dropZone.innerHTML = '<i class="bi bi-file-earmark-check-fill"></i><div class="mt-2"><strong>' + fileInput.files[0].name + '</strong></div><div class="text-body-secondary small">Ready to reconcile</div>';
        }
      });
    }

    // Sparklines
    function drawSparklines() {
      if (typeof Chart === 'undefined') return;
      const specs = [
        { key: 'today',  data: [30, 45, 28, 60, 50, 78, 92] },
        { key: 'mtd',    data: [140,180,120,210,190,230,260,280,310,290] },
        { key: 'ytd',    data: [42,58,71,64,88,102,120,140,168,190,220,245] }
      ];
      specs.forEach(function (spec) {
        const cvs = root.querySelector('[data-pay-spark="' + spec.key + '"]');
        if (!cvs) return;
        const grad = cvs.getContext('2d').createLinearGradient(0,0,0,44);
        grad.addColorStop(0, 'rgba(79,70,229,.35)');
        grad.addColorStop(1, 'rgba(79,70,229,0)');
        new Chart(cvs, {
          type: 'line',
          data: {
            labels: spec.data.map((_,i)=>i+1),
            datasets: [{ data: spec.data, borderColor: '#4f46e5', backgroundColor: grad, borderWidth: 2, fill: true, tension: .35, pointRadius: 0 }]
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: { x: { display: false }, y: { display: false } }
          }
        });
      });
    }
  }

  /* ============================================================
     ============= PAGE: REFUNDS =================================
     ============================================================ */
  function initRefunds(root) {
    const S = window.SalesLists;
    const today = new Date('2026-07-23');
    function daysFromToday(n) { const d = new Date(today); d.setDate(d.getDate() + n); return d; }

    const REASONS = {
      damaged:   { label: 'Damaged item',      cls: 'ref-reason--damaged',   icon: 'exclamation-octagon-fill' },
      wrong:     { label: 'Wrong product',     cls: 'ref-reason--wrong',     icon: 'x-diamond-fill' },
      desc:      { label: 'Not as described',  cls: 'ref-reason--desc',      icon: 'chat-square-quote-fill' },
      duplicate: { label: 'Duplicate charge',  cls: 'ref-reason--duplicate', icon: 'files' },
      remorse:   { label: "Buyer's remorse",   cls: 'ref-reason--remorse',   icon: 'emoji-frown' },
      defective: { label: 'Defective',         cls: 'ref-reason--defective', icon: 'tools' }
    };

    const REFUNDS = [
      { id: 'RFD-9012', requested: daysFromToday(-1),  cust: 0,  order: 'ORD-2026-2145', reason: 'damaged',   amount:  842.50, status: 'requested', narrative: 'Package arrived crushed. Two Halo Earbud units are non-functional. Photos attached showing damage to outer box and one earbud casing.' },
      { id: 'RFD-9011', requested: daysFromToday(-1),  cust: 3,  order: 'ORD-2026-2144', reason: 'wrong',     amount:  318.50, status: 'requested', narrative: 'Received Prism Standing Desk instead of the Loft Bookshelf Speakers I ordered. Order number and item mismatch confirmed by our warehouse team.' },
      { id: 'RFD-9010', requested: daysFromToday(-2),  cust: 5,  order: 'ORD-2026-2142', reason: 'duplicate', amount: 8760.00, status: 'requested', narrative: 'Duplicate charge appeared on card statement two hours after the original transaction. Confirmed only one order was placed.' },
      { id: 'RFD-9009', requested: daysFromToday(-3),  cust: 8,  order: 'ORD-2026-2141', reason: 'desc',      amount:  278.00, status: 'requested', narrative: 'Product description stated silicone material — actual product is hard plastic. Not what was represented on the site.' },
      { id: 'RFD-9008', requested: daysFromToday(-4),  cust: 2,  order: 'ORD-2026-2147', reason: 'defective', amount: 3218.00, status: 'approved',  narrative: 'Monitor exhibits severe backlight bleed and dead pixels. Confirmed defective by technical support after remote diagnosis.' },
      { id: 'RFD-9007', requested: daysFromToday(-5),  cust: 6,  order: 'ORD-2026-2140', reason: 'wrong',     amount: 1345.00, status: 'approved',  narrative: 'Wrong SKU shipped. Customer ordered ACC-KEY-05 but received ACC-KEY-03 (previous generation).' },
      { id: 'RFD-9006', requested: daysFromToday(-6),  cust: 4,  order: 'ORD-2026-2144', reason: 'remorse',   amount:  945.00, status: 'rejected',  narrative: 'Customer requested refund after 45-day return window closed. Return policy denies refund but store credit was offered.' },
      { id: 'RFD-9005', requested: daysFromToday(-8),  cust: 11, order: 'ORD-2026-2143', reason: 'damaged',   amount:  620.00, status: 'processed', narrative: 'Furniture leg snapped during unboxing. Manufacturer defect confirmed. Full refund processed and item scheduled for pickup.' },
      { id: 'RFD-9004', requested: daysFromToday(-10), cust: 9,  order: 'ORD-2026-2141', reason: 'desc',      amount:  489.00, status: 'processed', narrative: 'Item color differs significantly from product photos. Customer accepted 50% partial refund and kept item.' },
      { id: 'RFD-9003', requested: daysFromToday(-12), cust: 1,  order: 'ORD-2026-2140', reason: 'duplicate', amount:  145.00, status: 'processed', narrative: 'Payment system glitch caused duplicate authorization. Second charge refunded within same billing cycle.' },
      { id: 'RFD-9002', requested: daysFromToday(-14), cust: 7,  order: 'ORD-2026-2138', reason: 'defective', amount:  310.00, status: 'processed', narrative: 'Wireless charging feature fails intermittently. Firmware update did not resolve issue. Partial refund issued as compensation.' },
      { id: 'RFD-9001', requested: daysFromToday(-18), cust: 10, order: 'ORD-2026-2137', reason: 'remorse',   amount:  210.00, status: 'rejected',  narrative: 'Customer changed mind on aesthetic grounds. Item was used/opened. Denied per return policy on personal-use items.' }
    ];

    let data = REFUNDS.slice();
    let selected = REFUNDS[0].id;
    const state = { tab: 'all', search: '', reason: 'all', amtMin: '', amtMax: '', dateFrom: '', dateTo: '' };

    const listEl = root.querySelector('[data-ref-list]');
    const detailEl = root.querySelector('[data-ref-detail]');
    const countEl = root.querySelector('[data-ref-count]');

    function tabCount(tab) {
      if (tab === 'all') return data.length;
      return data.filter(r => r.status === tab).length;
    }

    function updateTabBadges() {
      ['all','requested','approved','rejected','processed'].forEach(function (t) {
        const b = root.querySelector('[data-ref-tab="' + t + '"] .badge');
        if (b) b.textContent = tabCount(t);
      });
    }

    function filtered() {
      const q = state.search.toLowerCase();
      return data.filter(function (r) {
        if (state.tab !== 'all' && r.status !== state.tab) return false;
        if (state.reason !== 'all' && r.reason !== state.reason) return false;
        if (state.amtMin && r.amount < parseFloat(state.amtMin)) return false;
        if (state.amtMax && r.amount > parseFloat(state.amtMax)) return false;
        const iso = r.requested.toISOString().slice(0,10);
        if (state.dateFrom && iso < state.dateFrom) return false;
        if (state.dateTo && iso > state.dateTo) return false;
        if (q) {
          const cust = S.CUSTOMERS[r.cust];
          if (!(r.id.toLowerCase().includes(q) || r.order.toLowerCase().includes(q) || cust.name.toLowerCase().includes(q))) return false;
        }
        return true;
      });
    }

    function render() {
      const rows = filtered();
      countEl.textContent = rows.length + ' refund' + (rows.length === 1 ? '' : 's');
      if (!rows.length) {
        listEl.innerHTML = '<div class="sales-empty"><div class="sales-empty__icon"><i class="bi bi-inbox"></i></div><h6>No refunds match</h6><p>Try adjusting your filters or clearing the search.</p></div>';
        renderDetail(null);
        return;
      }
      listEl.innerHTML = rows.map(function (r) {
        const cust = S.CUSTOMERS[r.cust];
        const reason = REASONS[r.reason];
        return '' +
          '<div class="ref-card ' + (r.id === selected ? 'is-selected' : '') + '" data-ref-select="' + r.id + '" data-sales-row>' +
            '<span class="ref-card__avatar ' + S.avatarClass(cust.name) + '">' + S.initials(cust.name) + '</span>' +
            '<div class="ref-card__main">' +
              '<div class="ref-card__row">' +
                '<span class="ref-card__name">' + S.escapeHtml(cust.name) + '</span>' +
                '<span class="ref-card__order">' + r.order + '</span>' +
                S.renderStatusPill(r.status) +
              '</div>' +
              '<div class="ref-card__meta">' +
                '<span><i class="bi bi-calendar-event me-1"></i>' + S.fmtDate(r.requested) + '</span>' +
                '<span>· ' + S.relativeTime(r.requested) + '</span>' +
              '</div>' +
              '<div class="ref-card__bottom">' +
                '<span class="ref-reason ' + reason.cls + '"><i class="bi bi-' + reason.icon + '"></i>' + reason.label + '</span>' +
                '<div class="d-flex align-items-center gap-2">' +
                  '<span class="ref-card__amount">' + S.fmtMoney(r.amount) + '</span>' +
                  '<button class="btn btn-sm sales-btn-primary" type="button" data-ref-review="' + r.id + '"><i class="bi bi-eye me-1"></i>Review</button>' +
                '</div>' +
              '</div>' +
            '</div>' +
          '</div>';
      }).join('');

      // ensure selected refund is visible
      const sel = rows.find(r => r.id === selected) || rows[0];
      if (sel) { selected = sel.id; renderDetail(sel); }
      updateTabBadges();
    }

    function renderDetail(r) {
      if (!r) {
        detailEl.innerHTML = '<div class="ref-empty-detail"><div><i class="bi bi-inbox"></i><div>Select a refund to review its details</div></div></div>';
        return;
      }
      const cust = S.CUSTOMERS[r.cust];
      const reason = REASONS[r.reason];
      detailEl.innerHTML = '' +
        '<div class="ref-detail">' +
          '<div class="ref-detail__head">' +
            '<div class="d-flex justify-content-between align-items-start gap-2 mb-2">' +
              '<div>' +
                '<div class="text-body-secondary small">' + r.id + '</div>' +
                '<h5 class="mb-0 mt-1">' + S.fmtMoney(r.amount) + ' refund</h5>' +
              '</div>' +
              S.renderStatusPill(r.status) +
            '</div>' +
            '<span class="ref-reason ' + reason.cls + '"><i class="bi bi-' + reason.icon + '"></i>' + reason.label + '</span>' +
          '</div>' +
          '<div class="ref-detail__body">' +
            '<div class="ref-section">' +
              '<h6>Customer</h6>' +
              '<div class="ref-contact">' +
                '<span class="sales-avatar-sm ' + S.avatarClass(cust.name) + '">' + S.initials(cust.name) + '</span>' +
                '<div class="ref-contact__info flex-grow-1">' +
                  '<strong>' + S.escapeHtml(cust.name) + '</strong>' +
                  '<small>' + S.escapeHtml(cust.contact) + ' · ' + S.escapeHtml(cust.email) + '</small>' +
                '</div>' +
                '<button class="sales-icon-btn" type="button" title="Email" data-ref-email="' + r.id + '"><i class="bi bi-envelope"></i></button>' +
              '</div>' +
            '</div>' +

            '<div class="ref-section">' +
              '<h6>Original order</h6>' +
              '<div class="ref-order-summary">' +
                '<div class="ref-order-summary__row"><span>Order</span><strong>' + r.order + '</strong></div>' +
                '<div class="ref-order-summary__row"><span>Purchase date</span><strong>' + S.fmtDate(new Date(r.requested.getTime() - 12 * 86400000)) + '</strong></div>' +
                '<div class="ref-order-summary__row"><span>Items</span><strong>3 units</strong></div>' +
                '<div class="ref-order-summary__row"><span>Order total</span><strong>' + S.fmtMoney(r.amount * 1.15) + '</strong></div>' +
                '<div class="ref-order-summary__row"><span>Refund requested</span><strong class="text-primary">' + S.fmtMoney(r.amount) + '</strong></div>' +
              '</div>' +
            '</div>' +

            '<div class="ref-section">' +
              '<h6>Reason narrative</h6>' +
              '<div class="ref-narrative">' + S.escapeHtml(r.narrative) + '</div>' +
            '</div>' +

            '<div class="ref-section">' +
              '<h6>Evidence attachments</h6>' +
              '<div class="ref-evidence">' +
                '<div class="ref-evidence__thumb ref-evidence__thumb--img-1" data-name="damage-1.jpg"></div>' +
                '<div class="ref-evidence__thumb ref-evidence__thumb--img-2" data-name="unbox.jpg"></div>' +
                '<div class="ref-evidence__thumb ref-evidence__thumb--img-3" data-name="closeup.jpg"></div>' +
                '<div class="ref-evidence__thumb ref-evidence__thumb--img-4" data-name="packaging.jpg"></div>' +
                '<div class="ref-evidence__thumb ref-evidence__thumb--doc" data-name="receipt.pdf"><i class="bi bi-file-earmark-pdf"></i></div>' +
                '<div class="ref-evidence__thumb ref-evidence__thumb--doc" data-name="notes.pdf"><i class="bi bi-file-earmark-text"></i></div>' +
              '</div>' +
            '</div>' +

            (r.status === 'requested' ? (
              '<div class="ref-section">' +
                '<h6>Decision</h6>' +
                '<label class="form-label small mb-1">Partial refund amount (optional)</label>' +
                '<div class="input-group input-group-sm mb-2">' +
                  '<span class="input-group-text">$</span>' +
                  '<input type="number" step="0.01" class="form-control" value="' + r.amount.toFixed(2) + '" data-ref-partial-amount aria-label="Partial refund amount">' +
                '</div>' +
                '<label class="form-label small mb-1">Note for customer</label>' +
                '<textarea class="form-control form-control-sm" rows="3" placeholder="Add an internal or customer-facing note…" data-ref-note></textarea>' +
              '</div>'
            ) : (
              '<div class="ref-section">' +
                '<h6>Resolution</h6>' +
                '<p class="small text-body-secondary mb-0">This refund has already been ' + r.status + '. No further action required.</p>' +
              '</div>'
            )) +
          '</div>' +
          '<div class="ref-detail__foot">' +
            (r.status === 'requested' ? (
              '<div class="d-flex gap-2">' +
                '<button class="btn btn-outline-danger flex-grow-1" type="button" data-ref-reject="' + r.id + '"><i class="bi bi-x-lg me-1"></i>Reject</button>' +
                '<button class="btn sales-btn-primary flex-grow-1" type="button" data-ref-approve="' + r.id + '"><i class="bi bi-check2 me-1"></i>Approve refund</button>' +
              '</div>'
            ) : (
              '<button class="btn btn-outline-secondary w-100" type="button" data-ref-reopen="' + r.id + '"><i class="bi bi-arrow-counterclockwise me-1"></i>Reopen case</button>'
            )) +
          '</div>' +
        '</div>';
    }

    // Tabs
    root.querySelectorAll('[data-ref-tab]').forEach(function (t) {
      t.addEventListener('click', function () {
        state.tab = t.getAttribute('data-ref-tab');
        root.querySelectorAll('[data-ref-tab]').forEach(x => x.classList.toggle('is-active', x === t));
        render();
      });
    });

    // Filters
    const searchEl = root.querySelector('[data-ref-search]');
    if (searchEl) searchEl.addEventListener('input', S.debounce(function () { state.search = searchEl.value; render(); }, 220));
    const reasonEl = root.querySelector('[data-ref-reason]');
    if (reasonEl) reasonEl.addEventListener('change', function () { state.reason = reasonEl.value; render(); });
    const amtMinEl = root.querySelector('[data-ref-amt-min]');
    const amtMaxEl = root.querySelector('[data-ref-amt-max]');
    if (amtMinEl) amtMinEl.addEventListener('input', S.debounce(function () { state.amtMin = amtMinEl.value; render(); }, 220));
    if (amtMaxEl) amtMaxEl.addEventListener('input', S.debounce(function () { state.amtMax = amtMaxEl.value; render(); }, 220));
    const dfEl = root.querySelector('[data-ref-date-from]');
    const dtEl = root.querySelector('[data-ref-date-to]');
    if (dfEl) dfEl.addEventListener('change', function () { state.dateFrom = dfEl.value; render(); });
    if (dtEl) dtEl.addEventListener('change', function () { state.dateTo = dtEl.value; render(); });

    const resetBtn = root.querySelector('[data-ref-reset]');
    if (resetBtn) resetBtn.addEventListener('click', function () {
      state.search = ''; state.reason = 'all'; state.amtMin = ''; state.amtMax = ''; state.dateFrom = ''; state.dateTo = '';
      if (searchEl) searchEl.value = ''; if (reasonEl) reasonEl.value = 'all';
      if (amtMinEl) amtMinEl.value = ''; if (amtMaxEl) amtMaxEl.value = '';
      if (dfEl) dfEl.value = ''; if (dtEl) dtEl.value = '';
      render(); S.orchidToast('Filters reset', { kind: 'info' });
    });

    // List click handlers
    listEl.addEventListener('click', function (e) {
      const card = e.target.closest('[data-ref-select]');
      const btn  = e.target.closest('[data-ref-review]');
      if (btn) { selected = btn.getAttribute('data-ref-review'); render(); return; }
      if (card) { selected = card.getAttribute('data-ref-select'); render(); return; }
    });

    // Detail-panel actions
    detailEl.addEventListener('click', function (e) {
      const ap = e.target.closest('[data-ref-approve]');
      if (ap) {
        const id = ap.getAttribute('data-ref-approve');
        const r = data.find(x => x.id === id);
        const amt = parseFloat((detailEl.querySelector('[data-ref-partial-amount]') || { value: r ? r.amount : 0 }).value);
        if (r) {
          r.status = 'approved';
          r.amount = amt || r.amount;
          render();
          S.orchidToast('Approved refund of ' + S.fmtMoney(r.amount) + ' for ' + r.id, { kind: 'success', title: 'Refund approved' });
        }
        return;
      }
      const rj = e.target.closest('[data-ref-reject]');
      if (rj) {
        const id = rj.getAttribute('data-ref-reject');
        const r = data.find(x => x.id === id);
        if (r) { r.status = 'rejected'; render(); S.orchidToast('Rejected ' + r.id, { kind: 'danger' }); }
        return;
      }
      const ro = e.target.closest('[data-ref-reopen]');
      if (ro) {
        const r = data.find(x => x.id === ro.getAttribute('data-ref-reopen'));
        if (r) { r.status = 'requested'; render(); S.orchidToast('Reopened ' + r.id, { kind: 'info' }); }
        return;
      }
      const em = e.target.closest('[data-ref-email]');
      if (em) { S.orchidToast('Email drafted to customer', { kind: 'info' }); return; }
    });

    // Initial render (with skeleton feel)
    listEl.innerHTML = Array.from({ length: 4 }).map(() =>
      '<div class="ref-card"><span class="sales-skel sales-skel--circle"></span><div class="ref-card__main flex-grow-1"><span class="sales-skel mb-2"></span><span class="sales-skel sales-skel--sm"></span><span class="sales-skel sales-w-60 mt-2"></span></div></div>'
    ).join('');
    detailEl.innerHTML = '<div class="ref-detail"><div class="ref-detail__body"><span class="sales-skel sales-skel--lg mb-3"></span><span class="sales-skel mb-2"></span><span class="sales-skel sales-skel--sm mb-4"></span><span class="sales-skel mb-2"></span><span class="sales-skel mb-2"></span><span class="sales-skel mb-2"></span></div></div>';
    setTimeout(function () { render(); }, 400);
  }

  /* ----------------- Auto-init based on data-sales-page ----------------- */
  document.addEventListener('DOMContentLoaded', function () {
    const page = document.querySelector('[data-sales-page]');
    if (!page) return;
    const kind = page.getAttribute('data-sales-page');
    if (kind === 'quotations') initQuotations(page);
    else if (kind === 'orders') initOrders(page);
    else if (kind === 'payments') initPayments(page);
    else if (kind === 'refunds') initRefunds(page);
  });
})();
