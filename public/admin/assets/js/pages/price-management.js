/* Orchid — Price Management */
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    var B = window.BizExtras;
    if (!B) return;

    var rules = [
      { id: 'r1',  name: 'VIP 15% Discount',              type: 'Percentage discount', scope: 'All products',              value: '15%',   priority: 1,  status: 'Active',    since: '2026-01-04' },
      { id: 'r2',  name: 'Enterprise Volume Tiered',      type: 'Tiered',              scope: 'Category: Cameras',         value: 'Tiered', priority: 2, status: 'Active',    since: '2026-02-11' },
      { id: 'r3',  name: 'Summer Sale — Apparel',         type: 'Percentage discount', scope: 'Category: Apparel',         value: '20%',   priority: 3,  status: 'Scheduled', since: '2026-08-01' },
      { id: 'r4',  name: 'Bundle: Camera + SD Card',      type: 'Bundle',              scope: 'Products: 2',               value: '$50 off', priority: 4, status: 'Active',   since: '2026-03-22' },
      { id: 'r5',  name: 'Buy One Get One — Peripherals', type: 'BOGO',                scope: 'Category: Peripherals',     value: 'BOGO',  priority: 5,  status: 'Active',    since: '2026-04-08' },
      { id: 'r6',  name: 'APAC Regional Pricing',         type: 'Percentage discount', scope: 'All products · APAC only',  value: '-8%',   priority: 6,  status: 'Active',    since: '2025-11-14' },
      { id: 'r7',  name: 'New Customer $25 Off',          type: 'Fixed amount',        scope: 'All products',              value: '$25',   priority: 7,  status: 'Active',    since: '2026-05-01' },
      { id: 'r8',  name: 'Aperture Brand — 10%',          type: 'Percentage discount', scope: 'Brand: Aperture',           value: '10%',   priority: 8,  status: 'Active',    since: '2026-06-11' },
      { id: 'r9',  name: 'Holiday Flash Sale — Q4',       type: 'Percentage discount', scope: 'All products',              value: '25%',   priority: 9,  status: 'Scheduled', since: '2026-11-24' },
      { id: 'r10', name: 'Clearance — MonoDisplay 27"',   type: 'Fixed amount',        scope: 'Product: MON-27U',          value: '$100',  priority: 10, status: 'Paused',    since: '2026-05-30' },
      { id: 'r11', name: 'Coffee Subscription — 5-off',   type: 'Fixed amount',        scope: 'Products: 1 · recurring',   value: '$5',    priority: 11, status: 'Active',    since: '2026-04-15' },
      { id: 'r12', name: 'Legacy Q1 Promo',               type: 'Percentage discount', scope: 'Category: Storage',         value: '30%',   priority: 12, status: 'Expired',   since: '2026-01-15' }
    ];

    var currentId = 'r1';
    var listEl = document.querySelector('[data-prc-list]');

    function typeIcon(t) { return { 'Percentage discount': 'bi-percent', 'Fixed amount': 'bi-currency-dollar', 'BOGO': 'bi-gift', 'Tiered': 'bi-bar-chart-steps', 'Bundle': 'bi-box-seam' }[t] || 'bi-tag'; }
    function statusPill(s) {
      var map = { 'Active': 'prc-status--active', 'Paused': 'prc-status--paused', 'Scheduled': 'prc-status--scheduled', 'Expired': 'prc-status--expired' };
      return '<span class="biz-pill ' + map[s] + '">' + s + '</span>';
    }

    function renderList() {
      listEl.innerHTML = rules.map(function (r) {
        return '<div class="prc-rule ' + (r.id === currentId ? 'is-active' : '') + '" data-id="' + r.id + '" draggable="true">' +
          '<div class="prc-rule__head">' +
            '<span class="prc-rule__handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>' +
            '<span class="prc-rule__prio">' + r.priority + '</span>' +
            '<div class="flex-grow-1"><p class="prc-rule__title">' + B.escapeHtml(r.name) + '</p>' +
              '<div class="prc-rule__meta"><span class="tag"><i class="bi ' + typeIcon(r.type) + '"></i> ' + r.type + '</span><span class="tag">' + r.scope + '</span><span class="tag fw-bold">' + r.value + '</span></div></div>' +
            statusPill(r.status) +
          '</div></div>';
      }).join('');
    }

    function renderDetail() {
      var r = rules.find(function (x) { return x.id === currentId; });
      if (!r) return;
      document.querySelector('[data-prc-detail-prio]').textContent = r.priority;
      document.querySelector('[data-prc-detail-name]').textContent = r.name;
      document.querySelector('[data-prc-detail-sub]').textContent = r.type + ' · ' + r.scope;
      document.querySelector('[data-prc-detail-name-input]').value = r.name;
      var typeSel = document.querySelector('[data-prc-detail-type]');
      Array.from(typeSel.options).forEach(function (o) { o.selected = o.value === r.type || o.text === r.type; });
      document.querySelector('[data-prc-detail-priority]').value = r.priority;
      document.querySelector('[data-prc-detail-active]').checked = r.status === 'Active';
      var since = document.querySelector('[data-prc-detail-since]');
      if (since) since.textContent = B.fmtDate(r.since);
      // approx SKU matches based on scope
      var matches = r.scope.includes('All') ? 1842 : r.scope.includes('Category') ? 218 : r.scope.includes('Brand') ? 342 : r.scope.includes('Product') ? 1 : 96;
      document.querySelector('[data-prc-detail-matches]').textContent = matches.toLocaleString();
    }

    listEl.addEventListener('click', function (e) {
      var card = e.target.closest('[data-id]'); if (!card) return;
      currentId = card.getAttribute('data-id');
      renderList(); renderDetail();
    });

    // Search
    var searchInput = document.querySelector('[data-prc-search]');
    searchInput.addEventListener('input', B.debounce(function (e) {
      var q = e.target.value.toLowerCase();
      listEl.querySelectorAll('.prc-rule').forEach(function (n) {
        var t = n.textContent.toLowerCase();
        n.style.display = (!q || t.includes(q)) ? '' : 'none';
      });
    }, 180));

    // Drag reorder
    B.attachDnD(listEl, '.prc-rule', function (order) {
      rules.sort(function (a, b) { return order.indexOf(a.id) - order.indexOf(b.id); });
      rules.forEach(function (r, i) { r.priority = i + 1; });
      renderList(); renderDetail();
      B.toast('Rule priority updated', 'success');
    });

    // Save
    document.querySelector('[data-prc-save]').addEventListener('click', function () {
      var r = rules.find(function (x) { return x.id === currentId; });
      r.name = document.querySelector('[data-prc-detail-name-input]').value;
      r.type = document.querySelector('[data-prc-detail-type]').value;
      r.priority = +document.querySelector('[data-prc-detail-priority]').value || r.priority;
      r.status = document.querySelector('[data-prc-detail-active]').checked ? 'Active' : 'Paused';
      renderList(); renderDetail();
      B.toast('Rule saved', 'success');
    });

    document.querySelector('[data-prc-add-cond]').addEventListener('click', function () {
      var wrap = document.querySelector('[data-prc-conds]');
      var el = document.createElement('div');
      el.className = 'prc-cond';
      el.innerHTML = '<div class="prc-cond__hd"><span><i class="bi bi-plus-square me-1"></i>Custom condition</span><button class="biz-icon-btn" type="button" aria-label="Remove"><i class="bi bi-x-lg"></i></button></div><div class="row g-2"><div class="col-md-4"><select class="form-select form-select-sm"><option>equals</option><option>contains</option></select></div><div class="col-md-8"><input class="form-control form-control-sm" placeholder="Value"></div></div>';
      wrap.appendChild(el);
    });
    document.querySelector('[data-prc-conds]').addEventListener('click', function (e) {
      var b = e.target.closest('.biz-icon-btn');
      if (b) { var cond = b.closest('.prc-cond'); if (cond) cond.remove(); }
    });

    /* ---------- Wizard ---------- */
    var wizStep = 1;
    var totalSteps = 5;
    var wizPanels = document.querySelectorAll('[data-prc-wiz-panel]');
    var wizSteps = document.querySelectorAll('[data-prc-wiz-steps] .prc-wiz-step');
    var wNext = document.querySelector('[data-prc-wiz-next]');
    var wBack = document.querySelector('[data-prc-wiz-back]');
    var wReview = document.querySelector('[data-prc-wiz-review]');
    var wCondsWrap = document.querySelector('[data-prc-wiz-conds]');

    function updateWiz() {
      wizSteps.forEach(function (s) {
        var n = +s.getAttribute('data-step');
        s.classList.toggle('is-active', n === wizStep);
      });
      wizPanels.forEach(function (p) { p.hidden = +p.getAttribute('data-prc-wiz-panel') !== wizStep; });
      wBack.disabled = wizStep === 1;
      wNext.innerHTML = wizStep === totalSteps ? '<i class="bi bi-check2 me-1"></i>Create rule' : 'Next <i class="bi bi-arrow-right ms-1"></i>';
      if (wizStep === 5) {
        wReview.innerHTML = '<div class="alert alert-light border small">' +
          '<div class="d-flex justify-content-between mb-1"><span class="text-body-secondary">Name</span><strong>' + (document.querySelector('[data-prc-wiz-name]').value || 'New rule') + '</strong></div>' +
          '<div class="d-flex justify-content-between mb-1"><span class="text-body-secondary">Type</span><strong>' + document.querySelector('[data-prc-wiz-type]').value + '</strong></div>' +
          '<div class="d-flex justify-content-between mb-1"><span class="text-body-secondary">Scope</span><strong>' + document.querySelector('[data-prc-wiz-scope]').value + '</strong></div>' +
          '<div class="d-flex justify-content-between"><span class="text-body-secondary">Discount</span><strong>' + document.querySelector('[data-prc-wiz-dval]').value + ' (' + document.querySelector('[data-prc-wiz-dtype]').value + ')</strong></div>' +
          '</div>';
      }
    }
    wNext.addEventListener('click', function () {
      if (wizStep === 1 && !document.querySelector('[data-prc-wiz-name]').value.trim()) { B.toast('Give the rule a name', 'warning'); return; }
      if (wizStep < totalSteps) { wizStep++; updateWiz(); }
      else {
        var name = document.querySelector('[data-prc-wiz-name]').value || 'New rule';
        var newRule = { id: 'r' + (rules.length + 1), name: name, type: document.querySelector('[data-prc-wiz-type]').value, scope: document.querySelector('[data-prc-wiz-scope]').value, value: document.querySelector('[data-prc-wiz-dval]').value + '%', priority: rules.length + 1, status: 'Active', since: new Date().toISOString().slice(0, 10) };
        rules.push(newRule);
        renderList();
        bootstrap.Modal.getInstance(document.getElementById('prcCreateModal')).hide();
        B.toast('Rule "' + name + '" created', 'success');
        // reset
        wizStep = 1; updateWiz();
        document.querySelector('[data-prc-wiz-name]').value = '';
      }
    });
    wBack.addEventListener('click', function () { if (wizStep > 1) { wizStep--; updateWiz(); } });

    document.querySelector('[data-prc-wiz-add-cond]').addEventListener('click', function () {
      var el = document.createElement('div');
      el.className = 'prc-cond';
      el.innerHTML = '<div class="prc-cond__hd"><span><i class="bi bi-funnel me-1"></i>Condition</span><button class="biz-icon-btn" type="button" aria-label="Remove"><i class="bi bi-x-lg"></i></button></div><div class="row g-2"><div class="col-md-4"><select class="form-select form-select-sm"><option>Customer tier</option><option>Cart total</option><option>Region</option><option>Product tag</option></select></div><div class="col-md-3"><select class="form-select form-select-sm"><option>is</option><option>is not</option><option>&gt;=</option></select></div><div class="col-md-5"><input class="form-control form-control-sm" placeholder="Value"></div></div>';
      wCondsWrap.appendChild(el);
    });
    wCondsWrap.addEventListener('click', function (e) {
      var b = e.target.closest('.biz-icon-btn'); if (b) b.closest('.prc-cond').remove();
    });

    // Paint KPI icons
    document.querySelectorAll('.biz-kpi__icon[data-bg]').forEach(function (el) {
      el.style.background = el.getAttribute('data-bg');
      el.style.color = el.getAttribute('data-fg');
    });

    renderList();
    renderDetail();
    updateWiz();
  });
})();
