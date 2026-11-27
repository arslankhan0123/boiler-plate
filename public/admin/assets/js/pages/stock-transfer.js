/* Orchid — Stock Transfer */
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    var B = window.BizExtras;
    if (!B) return;

    var W = B.warehouses;
    function wByCode(c) { return W.find(function (w) { return w.code === c; }); }

    var transfers = [
      { id: 'TRN-2601', from: 'US-EAST', to: 'US-WEST', items: 24, value: 18420, by: 'Alex Kim',     status: 'In Transit', eta: '2026-07-24' },
      { id: 'TRN-2600', from: 'EU-CENTRAL', to: 'APAC', items: 42, value: 52180, by: 'Sarah Miller', status: 'Approved',    eta: '2026-07-28' },
      { id: 'TRN-2599', from: 'US-WEST', to: 'US-EAST', items: 12, value:  4820, by: 'James Doe',    status: 'Requested',   eta: '2026-07-25' },
      { id: 'TRN-2598', from: 'APAC',    to: 'EU-CENTRAL', items: 68, value: 78940, by: 'Ava Lee',   status: 'In Transit',  eta: '2026-07-30' },
      { id: 'TRN-2597', from: 'US-EAST', to: 'EU-CENTRAL', items: 18, value: 12680, by: 'Ryan Green',status: 'Received',    eta: '2026-07-22' },
      { id: 'TRN-2596', from: 'US-WEST', to: 'APAC',     items: 34, value: 26480, by: 'Emma Watson', status: 'Requested',   eta: '2026-07-26' },
      { id: 'TRN-2595', from: 'EU-CENTRAL', to: 'US-EAST', items:  8, value:  2140, by: 'Alex Kim',  status: 'Rejected',    eta: '—' },
      { id: 'TRN-2594', from: 'APAC',    to: 'US-WEST',   items: 22, value: 18960, by: 'Sarah Miller', status: 'Received',   eta: '2026-07-21' },
      { id: 'TRN-2593', from: 'US-EAST', to: 'US-WEST',   items: 14, value:  9820, by: 'James Doe',   status: 'Approved',    eta: '2026-07-27' },
      { id: 'TRN-2592', from: 'US-WEST', to: 'EU-CENTRAL',items:  6, value:  3140, by: 'Ava Lee',    status: 'Requested',   eta: '2026-07-28' },
      { id: 'TRN-2591', from: 'EU-CENTRAL', to: 'APAC', items: 52, value: 42180, by: 'Ryan Green',   status: 'In Transit',   eta: '2026-07-29' },
      { id: 'TRN-2590', from: 'APAC',    to: 'US-EAST',   items: 28, value: 22480, by: 'Emma Watson', status: 'Received',   eta: '2026-07-20' },
      { id: 'TRN-2589', from: 'US-EAST', to: 'APAC',      items: 16, value: 11820, by: 'Alex Kim',    status: 'Rejected',    eta: '—' },
      { id: 'TRN-2588', from: 'US-WEST', to: 'US-EAST',   items: 44, value: 38260, by: 'Sarah Miller', status: 'Received',  eta: '2026-07-19' },
      { id: 'TRN-2587', from: 'EU-CENTRAL', to: 'US-WEST', items: 10, value:  6420, by: 'James Doe',  status: 'Requested',   eta: '2026-07-26' }
    ];

    var state = { search: '', from: '', to: '', status: '' };

    // Populate warehouse selects
    var fromSel = document.querySelector('[data-strn-from]');
    var toSel = document.querySelector('[data-strn-to]');
    W.forEach(function (w) {
      var o1 = document.createElement('option'); o1.value = w.code; o1.textContent = w.name; fromSel.appendChild(o1);
      var o2 = document.createElement('option'); o2.value = w.code; o2.textContent = w.name; toSel.appendChild(o2);
    });

    function statusPill(s) {
      var map = { 'Requested': 'strn-status--requested', 'Approved': 'strn-status--approved', 'In Transit': 'strn-status--transit', 'Received': 'strn-status--received', 'Rejected': 'strn-status--rejected' };
      var ic  = { 'Requested': 'bi-inbox', 'Approved': 'bi-clipboard-check', 'In Transit': 'bi-truck', 'Received': 'bi-check-circle', 'Rejected': 'bi-x-circle' };
      return '<span class="strn-status ' + map[s] + '"><i class="bi ' + ic[s] + '"></i>' + s + '</span>';
    }
    function warehouseChip(code) {
      var w = wByCode(code); if (!w) return code;
      return '<span class="strn-warehouse" style="border-left:3px solid ' + w.color + '"><i class="bi bi-building"></i>' + w.name + '</span>';
    }

    var tbody = document.querySelector('[data-strn-tbody]');
    var meta = document.querySelector('[data-strn-meta]');

    function filtered() {
      var q = state.search.toLowerCase();
      return transfers.filter(function (t) {
        if (state.from && t.from !== state.from) return false;
        if (state.to && t.to !== state.to) return false;
        if (state.status && t.status !== state.status) return false;
        if (q && !(t.id.toLowerCase().includes(q) || t.by.toLowerCase().includes(q) || t.from.toLowerCase().includes(q) || t.to.toLowerCase().includes(q))) return false;
        return true;
      });
    }

    function updateCounts() {
      ['Requested','Approved','In Transit','Received','Rejected'].forEach(function (s) {
        var n = transfers.filter(function (t) { return t.status === s; }).length;
        var el = document.querySelector('[data-strn-count="' + s + '"]');
        if (el) el.textContent = n;
      });
    }

    function render() {
      var arr = filtered();
      if (!arr.length) {
        tbody.innerHTML = '<tr><td colspan="8"><div class="biz-empty"><i class="bi bi-truck"></i><p class="mb-0">No transfers match your filters</p></div></td></tr>';
        meta.textContent = 'No results';
        return;
      }
      tbody.innerHTML = arr.map(function (t) {
        return '<tr>' +
          '<td><a href="#" data-strn-open="' + t.id + '" class="fw-semibold text-decoration-none">' + t.id + '</a></td>' +
          '<td><div class="strn-route">' + warehouseChip(t.from) + '<i class="bi bi-arrow-right arrow"></i>' + warehouseChip(t.to) + '</div></td>' +
          '<td>' + t.items + '</td>' +
          '<td class="fw-semibold">' + B.fmtMoney(t.value) + '</td>' +
          '<td><div class="d-flex align-items-center gap-2">' + B.renderAvatar(t.by, 'xs') + '<small>' + t.by + '</small></div></td>' +
          '<td>' + statusPill(t.status) + '</td>' +
          '<td>' + (t.eta === '—' ? '<small class="text-body-secondary">—</small>' : B.fmtDate(t.eta)) + '</td>' +
          '<td class="text-end">' +
            '<button class="biz-icon-btn" type="button" data-strn-open="' + t.id + '" aria-label="View"><i class="bi bi-eye"></i></button>' +
            (t.status === 'Requested' ? '<button class="biz-icon-btn text-success" type="button" data-strn-approve="' + t.id + '" aria-label="Approve"><i class="bi bi-check2"></i></button><button class="biz-icon-btn text-danger" type="button" data-strn-reject="' + t.id + '" aria-label="Reject"><i class="bi bi-x-lg"></i></button>' : '') +
          '</td>' +
        '</tr>';
      }).join('');
      meta.textContent = 'Showing ' + arr.length + ' of ' + transfers.length + ' transfers · $' + arr.reduce(function (a, x) { return a + x.value; }, 0).toLocaleString() + ' total value';
      B.bindAvatarColors(tbody);
    }

    // Filter events
    document.querySelector('[data-strn-search]').addEventListener('input', B.debounce(function (e) { state.search = e.target.value; render(); }, 200));
    ['[data-strn-from]', '[data-strn-to]', '[data-strn-status]'].forEach(function (sel, i) {
      document.querySelector(sel).addEventListener('change', function (e) {
        state[['from', 'to', 'status'][i]] = e.target.value;
        render();
      });
    });

    // Row actions
    tbody.addEventListener('click', function (e) {
      var op = e.target.closest('[data-strn-open]');
      if (op) { openDrawer(op.getAttribute('data-strn-open')); return; }
      var ap = e.target.closest('[data-strn-approve]');
      if (ap) { var t = transfers.find(function (x) { return x.id === ap.getAttribute('data-strn-approve'); }); if (t) { t.status = 'Approved'; render(); updateCounts(); B.toast(t.id + ' approved', 'success'); } return; }
      var rj = e.target.closest('[data-strn-reject]');
      if (rj) { var t2 = transfers.find(function (x) { return x.id === rj.getAttribute('data-strn-reject'); }); if (t2) { t2.status = 'Rejected'; t2.eta = '—'; render(); updateCounts(); B.toast(t2.id + ' rejected', 'warning'); } return; }
    });

    // Drawer
    var drawerEl = document.getElementById('strnDrawer');
    var drawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);
    function openDrawer(id) {
      var t = transfers.find(function (x) { return x.id === id; }); if (!t) return;
      var wf = wByCode(t.from), wt = wByCode(t.to);
      // Fake per-transfer items pulled from products
      var itemPool = B.products.slice(0, Math.min(6, t.items));
      var items = itemPool.map(function (p) {
        var qty = Math.ceil((t.items / itemPool.length) * (0.6 + Math.random() * 0.8));
        return { sku: p.sku, name: p.name, qty: qty, value: qty * p.price };
      });
      document.getElementById('strnDrawerTitle').textContent = t.id + ' — ' + wf.name + ' → ' + wt.name;
      var body = document.querySelector('[data-strn-drawer-body]');
      body.innerHTML =
        '<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">' +
          '<div>' + statusPill(t.status) + '<small class="text-body-secondary d-block mt-1">Requested ' + B.fmtDate('2026-07-15') + ' by ' + t.by + '</small></div>' +
          '<div class="text-end"><p class="mb-0 fw-bold h4">' + B.fmtMoney(t.value) + '</p><small class="text-body-secondary">' + t.items + ' units total</small></div>' +
        '</div>' +
        '<h6 class="small text-body-secondary text-uppercase mb-2">Items</h6>' +
        '<div class="table-responsive mb-3"><table class="table orchid-table align-middle mb-0"><thead><tr><th>SKU</th><th>Product</th><th>Qty</th><th class="text-end">Value</th></tr></thead><tbody>' +
          items.map(function (i) { return '<tr><td><strong>' + i.sku + '</strong></td><td class="small">' + i.name + '</td><td>' + i.qty + '</td><td class="text-end fw-semibold">' + B.fmtMoney(i.value) + '</td></tr>'; }).join('') +
        '</tbody></table></div>' +
        '<h6 class="small text-body-secondary text-uppercase mb-2">Tracking timeline</h6>' +
        '<div class="strn-track">' +
          ['Requested','Approved','In Transit','Received'].map(function (s, i) {
            var order = { 'Requested': 0, 'Approved': 1, 'In Transit': 2, 'Received': 3, 'Rejected': 1 };
            var cur = order[t.status] || 0;
            var cls = i < cur ? 'is-done' : (i === cur ? 'is-current' : '');
            return '<div class="strn-track__item ' + cls + '"><span class="dot"></span><div class="flex-grow-1"><strong class="small">' + s + '</strong><small class="text-body-secondary d-block">' + (i <= cur ? B.fmtDate('2026-07-' + (15 + i * 2)) : 'Pending') + '</small></div></div>';
          }).join('') +
        '</div>' +
        '<h6 class="small text-body-secondary text-uppercase mt-3 mb-2">Notes</h6>' +
        '<div class="cdet-note"><div class="cdet-note__head"><strong class="small">' + t.by + '</strong><small class="text-body-secondary">Jul 15</small></div><p class="mb-0 small">Rebalancing regional inventory ahead of Q3 promotional cycle. Priority handling requested.</p></div>';
      drawer.show();
    }

    // Wizard
    var wizStep = 1;
    var wiz = { src: null, dst: null, items: {} };
    var srcPanel = document.querySelector('[data-strn-wiz-src]');
    var dstPanel = document.querySelector('[data-strn-wiz-dst]');
    var itemsPanel = document.querySelector('[data-strn-wiz-items]');
    var reviewPanel = document.querySelector('[data-strn-wiz-review]');

    function whCard(w, target, key) {
      return '<div class="col-md-6"><label class="border rounded p-3 d-flex gap-2 align-items-center w-100 mb-0" style="cursor:pointer;border-left:4px solid ' + w.color + ' !important"><input type="radio" class="form-check-input mt-0" name="wiz' + target + '" value="' + w.code + '"' + (wiz[key] === w.code ? ' checked' : '') + '><div><p class="mb-0 fw-semibold">' + w.name + '</p><small class="text-body-secondary">' + w.city + '</small></div></label></div>';
    }
    function paintWizPanels() {
      srcPanel.innerHTML = W.map(function (w) { return whCard(w, 'Src', 'src'); }).join('');
      dstPanel.innerHTML = W.map(function (w) { return whCard(w, 'Dst', 'dst'); }).join('');
      itemsPanel.innerHTML = B.products.slice(0, 8).map(function (p) {
        var q = wiz.items[p.sku] || 0;
        return '<tr><td><input type="checkbox" class="form-check-input" data-wiz-item="' + p.sku + '"' + (q > 0 ? ' checked' : '') + '></td><td><strong>' + p.sku + '</strong></td><td class="small">' + p.name + '</td><td>' + p.stock + '</td><td><input type="number" class="form-control form-control-sm" min="0" max="' + p.stock + '" value="' + q + '" data-wiz-qty="' + p.sku + '"></td></tr>';
      }).join('');
    }
    paintWizPanels();

    srcPanel.addEventListener('change', function (e) { if (e.target.name === 'wizSrc') wiz.src = e.target.value; });
    dstPanel.addEventListener('change', function (e) { if (e.target.name === 'wizDst') wiz.dst = e.target.value; });
    itemsPanel.addEventListener('change', function (e) {
      var qt = e.target.closest('[data-wiz-qty]');
      if (qt) { wiz.items[qt.getAttribute('data-wiz-qty')] = +qt.value || 0; return; }
      var ck = e.target.closest('[data-wiz-item]');
      if (ck) { if (!ck.checked) delete wiz.items[ck.getAttribute('data-wiz-item')]; else wiz.items[ck.getAttribute('data-wiz-item')] = wiz.items[ck.getAttribute('data-wiz-item')] || 1; }
    });

    function paintReview() {
      var src = wByCode(wiz.src), dst = wByCode(wiz.dst);
      var items = Object.keys(wiz.items).filter(function (k) { return wiz.items[k] > 0; });
      var total = items.reduce(function (a, k) { var p = B.products.find(function (x) { return x.sku === k; }); return a + (p ? p.price * wiz.items[k] : 0); }, 0);
      reviewPanel.innerHTML = '<div class="alert alert-light border"><div class="d-flex justify-content-between mb-2"><span class="small text-body-secondary">Route</span><strong>' + (src ? src.name : '—') + ' → ' + (dst ? dst.name : '—') + '</strong></div><div class="d-flex justify-content-between mb-2"><span class="small text-body-secondary">Items</span><strong>' + items.length + ' SKUs, ' + items.reduce(function (a, k) { return a + wiz.items[k]; }, 0) + ' units</strong></div><div class="d-flex justify-content-between"><span class="small text-body-secondary">Value</span><strong>' + B.fmtMoney(total) + '</strong></div></div>';
    }

    var steps = document.querySelectorAll('[data-strn-wiz-steps] .strn-wiz-step');
    var panels = document.querySelectorAll('[data-strn-wiz-panel]');
    var nextBtn = document.querySelector('[data-strn-wiz-next]');
    var backBtn = document.querySelector('[data-strn-wiz-back]');
    function updateWiz() {
      steps.forEach(function (s) {
        var n = +s.getAttribute('data-step');
        s.classList.toggle('is-active', n === wizStep);
        s.classList.toggle('is-done', n < wizStep);
      });
      panels.forEach(function (p) { p.hidden = +p.getAttribute('data-strn-wiz-panel') !== wizStep; });
      backBtn.disabled = wizStep === 1;
      nextBtn.textContent = wizStep === 4 ? 'Create transfer' : 'Next';
      if (wizStep === 4) { nextBtn.innerHTML = '<i class="bi bi-check2 me-1"></i>Create transfer'; paintReview(); }
      else { nextBtn.innerHTML = 'Next <i class="bi bi-arrow-right ms-1"></i>'; }
    }
    nextBtn.addEventListener('click', function () {
      if (wizStep === 1 && !wiz.src) { B.toast('Select a source warehouse', 'warning'); return; }
      if (wizStep === 2 && !wiz.dst) { B.toast('Select a destination', 'warning'); return; }
      if (wizStep === 2 && wiz.dst === wiz.src) { B.toast('Source and destination must differ', 'warning'); return; }
      if (wizStep === 3 && !Object.keys(wiz.items).filter(function (k) { return wiz.items[k] > 0; }).length) { B.toast('Add at least one item', 'warning'); return; }
      if (wizStep < 4) { wizStep++; updateWiz(); }
      else {
        // Create
        var items = Object.keys(wiz.items).filter(function (k) { return wiz.items[k] > 0; });
        var total = items.reduce(function (a, k) { var p = B.products.find(function (x) { return x.sku === k; }); return a + (p ? p.price * wiz.items[k] : 0); }, 0);
        var qty = items.reduce(function (a, k) { return a + wiz.items[k]; }, 0);
        var nId = 'TRN-' + (2601 + transfers.length + 1);
        transfers.unshift({ id: nId, from: wiz.src, to: wiz.dst, items: qty, value: total, by: 'Alex Kim', status: 'Requested', eta: '2026-07-30' });
        render(); updateCounts();
        bootstrap.Modal.getInstance(document.getElementById('strnCreateModal')).hide();
        B.toast('Transfer ' + nId + ' created', 'success');
        // reset
        wizStep = 1; wiz = { src: null, dst: null, items: {} }; paintWizPanels(); updateWiz();
      }
    });
    backBtn.addEventListener('click', function () { if (wizStep > 1) { wizStep--; updateWiz(); } });
    updateWiz();

    // Paint KPIs
    document.querySelectorAll('.biz-kpi__icon[data-bg]').forEach(function (el) {
      el.style.background = el.getAttribute('data-bg');
      el.style.color = el.getAttribute('data-fg');
    });

    updateCounts();
    render();
  });
})();
