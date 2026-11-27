/* Orchid — Customer List */
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    var B = window.BizExtras;
    if (!B) return;

    var rows = B.customers.slice();
    var state = { search: '', tier: 'All', status: '', industry: '', sort: 'lastOrder-desc', selected: new Set() };

    // Populate industry filter + modal industry
    var indSel = document.querySelector('[data-cust-industry]');
    var modalInd = document.querySelector('[data-cust-modal-industry]');
    B.industries.forEach(function (i) {
      if (indSel) { var o = document.createElement('option'); o.value = i; o.textContent = i; indSel.appendChild(o); }
      if (modalInd) { var o2 = document.createElement('option'); o2.value = i; o2.textContent = i; modalInd.appendChild(o2); }
    });

    // Chip counts
    ['All','New','Regular','VIP','Enterprise'].forEach(function (t) {
      var n = t === 'All' ? rows.length : rows.filter(function (r) { return r.tier === t; }).length;
      var el = document.querySelector('[data-count="' + t + '"]');
      if (el) el.textContent = n;
    });

    function tierBadge(t) {
      var map = { 'New': 'cust-tier--new', 'Regular': 'cust-tier--regular', 'VIP': 'cust-tier--vip', 'Enterprise': 'cust-tier--enterprise' };
      var icon = { 'New': 'bi-stars', 'Regular': 'bi-person', 'VIP': 'bi-award', 'Enterprise': 'bi-building' }[t] || 'bi-person';
      return '<span class="cust-tier ' + (map[t] || '') + '"><i class="bi ' + icon + '"></i>' + t + '</span>';
    }
    function healthCell(v) {
      var color = v >= 80 ? '#10b981' : v >= 60 ? '#0ea5e9' : v >= 40 ? '#f59e0b' : '#ef4444';
      return '<div class="cust-health"><div class="biz-progress cust-health__bar"><div class="biz-progress__bar" data-bar-w="' + v + '" data-bar-color="' + color + '"></div></div><span class="cust-health__val">' + v + '</span></div>';
    }
    function statusPill(s) {
      var cls = s === 'Active' ? 'bg-success-subtle text-success' : s === 'At Risk' ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info';
      return '<span class="badge ' + cls + '">' + s + '</span>';
    }

    function filtered() {
      var q = state.search.toLowerCase().trim();
      var arr = rows.filter(function (r) {
        if (state.tier !== 'All' && r.tier !== state.tier) return false;
        if (state.status && r.status !== state.status) return false;
        if (state.industry && r.industry !== state.industry) return false;
        if (q && !(r.name.toLowerCase().includes(q) || r.email.toLowerCase().includes(q) || r.company.toLowerCase().includes(q))) return false;
        return true;
      });
      var s = state.sort.split('-');
      var key = s[0], dir = s[1] === 'asc' ? 1 : -1;
      arr.sort(function (a, b) {
        var av = a[key], bv = b[key];
        if (typeof av === 'string') return av.localeCompare(bv) * dir;
        return (av - bv) * dir;
      });
      return arr;
    }

    var tbody = document.querySelector('[data-cust-tbody]');
    var meta = document.querySelector('[data-cust-meta]');
    var bulkBar = document.querySelector('[data-cust-bulk]');
    var bulkCount = document.querySelector('[data-cust-selected]');

    function render() {
      var arr = filtered();
      if (!arr.length) {
        tbody.innerHTML = '<tr><td colspan="9"><div class="biz-empty"><i class="bi bi-search"></i><p class="mb-0">No customers match your filters</p></div></td></tr>';
        meta.textContent = 'No results';
        return;
      }
      tbody.innerHTML = arr.map(function (r) {
        var checked = state.selected.has(r.id) ? 'checked' : '';
        return '' +
          '<tr data-row-id="' + r.id + '">' +
          '<td><input type="checkbox" class="form-check-input" data-cust-check value="' + r.id + '" ' + checked + '></td>' +
          '<td><div class="d-flex align-items-center gap-2">' + B.renderAvatar(r.name, 'md') + '<div><p class="mb-0 fw-semibold">' + B.escapeHtml(r.name) + '</p><small class="text-body-secondary">' + B.escapeHtml(r.company) + ' · ' + r.email + '</small></div></div></td>' +
          '<td>' + tierBadge(r.tier) + '</td>' +
          '<td>' + healthCell(r.health) + '</td>' +
          '<td class="fw-semibold">' + B.fmtMoney(r.ltv) + '</td>' +
          '<td>' + r.orders + '</td>' +
          '<td><small class="text-body-secondary">' + B.fmtDate(r.lastOrder) + '</small></td>' +
          '<td><div class="d-flex align-items-center gap-2">' + B.renderAvatar(r.owner, 'xs') + '<small>' + r.owner + '</small></div></td>' +
          '<td class="text-end">' +
            '<button class="biz-icon-btn" type="button" data-cust-open="' + r.id + '" aria-label="Open"><i class="bi bi-eye"></i></button>' +
            '<button class="biz-icon-btn" type="button" aria-label="Email"><i class="bi bi-envelope"></i></button>' +
            '<div class="dropdown d-inline-block"><button class="biz-icon-btn" type="button" data-bs-toggle="dropdown" aria-label="More"><i class="bi bi-three-dots-vertical"></i></button>' +
              '<ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item" href="customer-details.html">View 360°</a></li><li><a class="dropdown-item" href="#">Add to segment</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item text-danger" href="#">Archive</a></li></ul>' +
            '</div>' +
          '</td>' +
          '</tr>';
      }).join('');
      meta.textContent = 'Showing ' + arr.length + ' of ' + rows.length + ' customers';
      B.bindAvatarColors(tbody);
      // Paint progress bars
      tbody.querySelectorAll('[data-bar-w]').forEach(function (b) {
        b.style.width = b.getAttribute('data-bar-w') + '%';
        b.style.background = b.getAttribute('data-bar-color');
      });
    }

    function refreshBulk() {
      bulkCount.textContent = state.selected.size;
      bulkBar.classList.toggle('is-visible', state.selected.size > 0);
    }

    // Events
    var searchInput = document.querySelector('[data-cust-search]');
    searchInput.addEventListener('input', B.debounce(function (e) { state.search = e.target.value; render(); }, 200));

    document.querySelector('[data-cust-tier-chips]').addEventListener('click', function (e) {
      var btn = e.target.closest('.biz-chip'); if (!btn) return;
      this.querySelectorAll('.biz-chip').forEach(function (n) { n.classList.remove('is-active'); });
      btn.classList.add('is-active');
      state.tier = btn.getAttribute('data-tier');
      render();
    });

    ['[data-cust-status]', '[data-cust-industry]', '[data-cust-sort]'].forEach(function (sel, i) {
      var el = document.querySelector(sel);
      el.addEventListener('change', function (e) {
        state[['status','industry','sort'][i]] = e.target.value;
        render();
      });
    });

    tbody.addEventListener('change', function (e) {
      if (e.target.matches('[data-cust-check]')) {
        var id = e.target.value;
        if (e.target.checked) state.selected.add(id); else state.selected.delete(id);
        refreshBulk();
      }
    });

    document.querySelector('[data-cust-check-all]').addEventListener('change', function (e) {
      var checked = e.target.checked;
      tbody.querySelectorAll('[data-cust-check]').forEach(function (c) {
        c.checked = checked;
        if (checked) state.selected.add(c.value); else state.selected.delete(c.value);
      });
      refreshBulk();
    });

    document.querySelectorAll('[data-bulk]').forEach(function (b) {
      b.addEventListener('click', function () {
        var kind = b.getAttribute('data-bulk');
        if (kind === 'clear') {
          state.selected.clear();
          tbody.querySelectorAll('[data-cust-check]').forEach(function (c) { c.checked = false; });
          refreshBulk();
          return;
        }
        B.toast(state.selected.size + ' customers · ' + kind + ' triggered', 'success');
      });
    });

    // Drawer
    var drawerEl = document.getElementById('custDrawer');
    var drawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);
    tbody.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-cust-open]'); if (!btn) return;
      var r = rows.find(function (x) { return x.id === btn.getAttribute('data-cust-open'); });
      if (!r) return;
      document.getElementById('custDrawerTitle').textContent = r.name;
      var head = document.querySelector('[data-cust-drawer-head]');
      head.innerHTML = '<div class="d-flex gap-3 align-items-center">' + B.renderAvatar(r.name, 'lg') + '<div><h4 class="mb-0">' + B.escapeHtml(r.name) + '</h4><p class="mb-1 small text-body-secondary">' + B.escapeHtml(r.company) + ' · ' + r.industry + '</p>' + tierBadge(r.tier) + ' ' + statusPill(r.status) + '</div></div>';
      B.bindAvatarColors(head);
      renderDrawer(r, 'overview');
      drawer.show();
    });

    function renderDrawer(r, tab) {
      var body = document.querySelector('[data-cust-drawer-body]');
      if (tab === 'overview') {
        body.innerHTML = '<div class="row g-2 small"><div class="col-6"><p class="text-body-secondary mb-0">LTV</p><p class="fw-bold mb-2">' + B.fmtMoney(r.ltv) + '</p></div><div class="col-6"><p class="text-body-secondary mb-0">Orders</p><p class="fw-bold mb-2">' + r.orders + '</p></div><div class="col-6"><p class="text-body-secondary mb-0">Health</p><p class="fw-bold mb-2">' + r.health + '/100</p></div><div class="col-6"><p class="text-body-secondary mb-0">NPS</p><p class="fw-bold mb-2">' + r.nps + '/10</p></div><div class="col-12"><p class="text-body-secondary mb-0">Email</p><p class="mb-2"><a href="mailto:' + r.email + '">' + r.email + '</a></p></div><div class="col-12"><p class="text-body-secondary mb-0">Phone</p><p class="mb-2">' + r.phone + '</p></div><div class="col-12"><p class="text-body-secondary mb-0">Location</p><p class="mb-0">' + r.city + ', ' + r.country + '</p></div></div>';
      } else if (tab === 'orders') {
        var orders = ['ORD-9812', 'ORD-9720', 'ORD-9611', 'ORD-9502', 'ORD-9401'].map(function (id, i) {
          return '<div class="d-flex justify-content-between border-bottom py-2 small"><span><strong>' + id + '</strong> · ' + B.fmtDate('2026-07-' + (22 - i * 3)) + '</span><span class="fw-semibold">' + B.fmtMoney(1200 + i * 340) + '</span></div>';
        }).join('');
        body.innerHTML = orders;
      } else if (tab === 'comms') {
        body.innerHTML = '<div class="cdet-timeline">' +
          '<div class="cdet-timeline__item"><span class="cdet-timeline__ico" data-bg="rgba(79,70,229,.16)" data-fg="#4f46e5"><i class="bi bi-envelope"></i></span><p class="mb-0 small"><strong>Sent proposal</strong> — Alex Kim</p><small class="text-body-secondary">2h ago</small></div>' +
          '<div class="cdet-timeline__item"><span class="cdet-timeline__ico" data-bg="rgba(14,165,233,.16)" data-fg="#0284c7"><i class="bi bi-telephone"></i></span><p class="mb-0 small"><strong>Discovery call</strong> — 32 min</p><small class="text-body-secondary">Yesterday</small></div>' +
          '<div class="cdet-timeline__item"><span class="cdet-timeline__ico" data-bg="rgba(16,185,129,.16)" data-fg="#059669"><i class="bi bi-check2"></i></span><p class="mb-0 small"><strong>Contract signed</strong></p><small class="text-body-secondary">Jul 12</small></div>' +
          '</div>';
      } else {
        body.innerHTML = '<div class="cdet-note"><div class="cdet-note__head"><strong>Alex Kim</strong><small class="text-body-secondary">2d ago</small></div><p class="mb-0 small">Renewal discussion scheduled for next quarter. Key contact: ' + r.name + '.</p></div>';
      }
      // Paint icons/bg
      body.querySelectorAll('[data-bg]').forEach(function (el) { el.style.background = el.getAttribute('data-bg'); });
      body.querySelectorAll('[data-fg]').forEach(function (el) { el.style.color = el.getAttribute('data-fg'); });
    }

    document.querySelectorAll('[data-drawer-tab]').forEach(function (b) {
      b.addEventListener('click', function () {
        document.querySelectorAll('[data-drawer-tab]').forEach(function (x) { x.classList.remove('is-active'); });
        b.classList.add('is-active');
        var r = rows.find(function (x) { return x.name === document.getElementById('custDrawerTitle').textContent; });
        if (r) renderDrawer(r, b.getAttribute('data-drawer-tab'));
      });
    });

    // New customer form
    var newForm = document.querySelector('[data-cust-new-form]');
    if (newForm) {
      newForm.addEventListener('submit', function (e) {
        e.preventDefault();
        B.toast('Customer created', 'success');
        bootstrap.Modal.getInstance(document.getElementById('custNewModal')).hide();
        newForm.reset();
      });
    }

    var importBtn = document.querySelector('[data-cust-import-go]');
    if (importBtn) importBtn.addEventListener('click', function () {
      bootstrap.Modal.getInstance(document.getElementById('custImportModal')).hide();
      B.toast('Import started — 0 of 348 rows processed', 'info');
    });

    var exportBtn = document.querySelector('[data-cust-export]');
    if (exportBtn) exportBtn.addEventListener('click', function () {
      B.toast('CSV export ready — check downloads', 'success');
    });

    // Sortable columns
    var table = document.querySelector('[data-cust-table]');
    B.attachSortable(table, function (key, dir) {
      state.sort = key + '-' + dir;
      var sortSel = document.querySelector('[data-cust-sort]');
      var opt = key + '-' + dir;
      if (sortSel && Array.from(sortSel.options).some(function (o) { return o.value === opt; })) sortSel.value = opt;
      render();
    });

    // Paint KPI icons
    document.querySelectorAll('.biz-kpi__icon[data-bg]').forEach(function (el) {
      el.style.background = el.getAttribute('data-bg');
      el.style.color = el.getAttribute('data-fg');
    });

    render();
  });
})();
