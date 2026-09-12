/* =====================================================
   Orchid — Pipeline Pages (deals / sales-pipeline / opportunities)
   Shared toolkit + per-page hooks
   ===================================================== */
(function () {
  'use strict';

  /* -------------------------------------------------------
     Shared utilities
     ------------------------------------------------------- */
  const STAGES = [
    { id: 'prospect',    label: 'Prospect',    color: '#64748b', prob: 15 },
    { id: 'qualified',   label: 'Qualified',   color: '#0ea5e9', prob: 35 },
    { id: 'proposal',    label: 'Proposal',    color: '#f59e0b', prob: 55 },
    { id: 'negotiation', label: 'Negotiation', color: '#8b5cf6', prob: 75 },
    { id: 'won',         label: 'Won',         color: '#10b981', prob: 100 },
    { id: 'lost',        label: 'Lost',        color: '#ef4444', prob: 0 }
  ];
  const STAGE_MAP = STAGES.reduce((m, s) => { m[s.id] = s; return m; }, {});

  const OWNERS = [
    { id: 'priya',  name: 'Priya Menon',   initials: 'PM', color: 'bg-primary'   },
    { id: 'marcus', name: 'Marcus Chen',   initials: 'MC', color: 'bg-info'      },
    { id: 'sofia',  name: 'Sofía García',  initials: 'SG', color: 'bg-success'   },
    { id: 'yuki',   name: 'Yuki Tanaka',   initials: 'YT', color: 'bg-warning'   },
    { id: 'ananya', name: 'Ananya Rao',    initials: 'AR', color: 'bg-danger'    }
  ];
  const OWNER_MAP = OWNERS.reduce((m, o) => { m[o.id] = o; return m; }, {});

  const TAGS = ['enterprise', 'renewal', 'expansion', 'inbound', 'strategic', 'sme'];
  const PRIORITY = ['hot', 'warm', 'cold'];

  const fmtMoney = (n) => {
    if (n == null || isNaN(n)) return '$0';
    if (n >= 1_000_000) return '$' + (n / 1_000_000).toFixed(n >= 10_000_000 ? 0 : 1) + 'M';
    if (n >= 1_000)     return '$' + (n / 1_000).toFixed(n >= 10_000 ? 0 : 1) + 'K';
    return '$' + n.toLocaleString('en-US');
  };
  const fmtMoneyFull = (n) => '$' + (n || 0).toLocaleString('en-US');
  const fmtDate = (d) => {
    if (!d) return '—';
    const dt = (d instanceof Date) ? d : new Date(d);
    return dt.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
  };
  const daysBetween = (a, b) => Math.round((new Date(b) - new Date(a)) / 86400000);
  const daysUntil = (d) => daysBetween(new Date(), d);
  const escapeHtml = (str) => String(str == null ? '' : str)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

  /* -------------------------------------------------------
     Toast host (single instance)
     ------------------------------------------------------- */
  function ensureToastHost() {
    let host = document.getElementById('pipelineToastHost');
    if (host) return host;
    host = document.createElement('div');
    host.id = 'pipelineToastHost';
    host.className = 'toast-container position-fixed top-0 end-0 p-3';
    host.style.zIndex = '1090';
    document.body.appendChild(host);
    return host;
  }
  function orchidToast(message, variant) {
    const host = ensureToastHost();
    variant = variant || 'primary';
    const iconMap = {
      success: 'bi-check-circle-fill',
      danger:  'bi-exclamation-triangle-fill',
      warning: 'bi-exclamation-circle-fill',
      info:    'bi-info-circle-fill',
      primary: 'bi-bell-fill'
    };
    const wrap = document.createElement('div');
    wrap.className = 'toast align-items-center border-0 text-bg-' + variant;
    wrap.setAttribute('role', 'alert');
    wrap.setAttribute('aria-live', 'assertive');
    wrap.setAttribute('aria-atomic', 'true');
    wrap.innerHTML =
      '<div class="d-flex">' +
        '<div class="toast-body d-flex align-items-center gap-2">' +
          '<i class="bi ' + (iconMap[variant] || iconMap.primary) + '"></i>' +
          '<span>' + escapeHtml(message) + '</span>' +
        '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
      '</div>';
    host.appendChild(wrap);
    const t = new bootstrap.Toast(wrap, { delay: 3200 });
    t.show();
    wrap.addEventListener('hidden.bs.toast', () => wrap.remove());
  }

  /* -------------------------------------------------------
     Shared dataset — 30 deals
     ------------------------------------------------------- */
  const RAW_DEALS = [
    ['Vertex Robotics — Q4 Retainer',        'Vertex Robotics',       'v', 84000,  'negotiation', 'priya',  'hot',  '2026-08-14', 'enterprise'],
    ['Aurora Labs — Onboarding Package',     'Aurora Labs',           'a', 42500,  'proposal',    'marcus', 'warm', '2026-08-30', 'inbound'],
    ['Meridian Health — SSO Migration',      'Meridian Health',       'm', 128000, 'qualified',   'sofia',  'hot',  '2026-09-12', 'strategic'],
    ['Northwind Analytics — Multi-year',     'Northwind Analytics',   'n', 310000, 'negotiation', 'yuki',   'hot',  '2026-08-08', 'enterprise'],
    ['Titan Media — Data Pipeline',          'Titan Media',           't', 96000,  'proposal',    'ananya', 'warm', '2026-09-22', 'expansion'],
    ['Cirrus Cloud — Enterprise Tier',       'Cirrus Cloud',          'c', 245000, 'qualified',   'priya',  'warm', '2026-10-05', 'enterprise'],
    ['Halcyon Retail — POS Integration',     'Halcyon Retail',        'h', 58000,  'proposal',    'marcus', 'warm', '2026-08-25', 'inbound'],
    ['Orchid Software — Renewal 2026',        'Orchid Software',        'o', 72000,  'won',         'sofia',  'hot',  '2026-07-10', 'renewal'],
    ['Nova Systems — Security Audit',        'Nova Systems',          'n', 34500,  'prospect',    'yuki',   'cold', '2026-11-01', 'inbound'],
    ['Pinnacle Group — Consulting Package',  'Pinnacle Group',        'p', 118000, 'negotiation', 'ananya', 'hot',  '2026-08-18', 'strategic'],
    ['BrightWave Studios — Design System',   'BrightWave Studios',    'b', 26000,  'proposal',    'priya',  'warm', '2026-09-04', 'sme'],
    ['Kepler Finance — Reporting Suite',     'Kepler Finance',        'k', 152000, 'qualified',   'marcus', 'warm', '2026-09-18', 'enterprise'],
    ['Umbra Security — Firewall Upgrade',    'Umbra Security',        'u', 88000,  'prospect',    'sofia',  'cold', '2026-10-22', 'inbound'],
    ['Zenith Manufacturing — IoT Rollout',   'Zenith Manufacturing',  'z', 275000, 'proposal',    'yuki',   'hot',  '2026-08-12', 'enterprise'],
    ['Ember Airlines — Loyalty Platform',    'Ember Airlines',        'e', 198000, 'negotiation', 'ananya', 'warm', '2026-08-28', 'strategic'],
    ['Solstice Energy — Grid Analytics',     'Solstice Energy',       's', 340000, 'qualified',   'priya',  'hot',  '2026-09-30', 'enterprise'],
    ['Arcadia Publishing — CMS Migration',   'Arcadia Publishing',    'a', 44000,  'won',         'marcus', 'warm', '2026-07-18', 'renewal'],
    ['Delta Logistics — Fleet Dashboard',    'Delta Logistics',       'd', 67500,  'proposal',    'sofia',  'warm', '2026-09-08', 'expansion'],
    ['Riverbend Foods — Inventory System',   'Riverbend Foods',       'r', 52000,  'qualified',   'yuki',   'warm', '2026-10-10', 'sme'],
    ['Peak Digital — Marketing Automation',  'Peak Digital',          'p', 38000,  'prospect',    'ananya', 'cold', '2026-11-14', 'inbound'],
    ['Fable Studios — Streaming Backend',    'Fable Studios',         'f', 165000, 'negotiation', 'priya',  'hot',  '2026-08-22', 'strategic'],
    ['Aegis Insurance — Claims Portal',      'Aegis Insurance',       'a', 220000, 'proposal',    'marcus', 'hot',  '2026-09-15', 'enterprise'],
    ['Junction Realty — CRM Setup',          'Junction Realty',       'j', 18000,  'won',         'sofia',  'warm', '2026-07-25', 'sme'],
    ['Lumen Analytics — Data Warehouse',     'Lumen Analytics',       'l', 145000, 'qualified',   'yuki',   'warm', '2026-10-02', 'expansion'],
    ['Cobalt HR — Benefits Portal',          'Cobalt HR',             'c', 62000,  'proposal',    'ananya', 'warm', '2026-09-25', 'inbound'],
    ['Mosaic Learning — LMS Custom Build',   'Mosaic Learning',       'm', 8500,   'prospect',    'priya',  'cold', '2026-12-01', 'sme'],
    ['Terrain Networks — VPN Rollout',       'Terrain Networks',      't', 78000,  'negotiation', 'marcus', 'warm', '2026-08-19', 'expansion'],
    ['Vantage Foods — Supply Chain',         'Vantage Foods',         'v', 132000, 'qualified',   'sofia',  'warm', '2026-09-27', 'enterprise'],
    ['Ironclad Legal — Contracts DB',        'Ironclad Legal',        'i', 92000,  'proposal',    'yuki',   'hot',  '2026-08-31', 'strategic'],
    ['Cascade Media — Ad Platform',          'Cascade Media',         'c', 450000, 'negotiation', 'ananya', 'hot',  '2026-08-05', 'enterprise']
  ];

  function buildDataset() {
    const today = new Date('2026-07-22');
    return RAW_DEALS.map((row, i) => {
      const [name, company, letter, amount, stage, ownerId, priority, close, tag] = row;
      const created = new Date(today.getTime() - (Math.floor(Math.random() * 90) + 8) * 86400000);
      const prob = STAGE_MAP[stage].prob;
      return {
        id: 'D-' + (1000 + i),
        name: name,
        company: company,
        logoLetter: letter,
        amount: amount,
        stage: stage,
        owner: ownerId,
        priority: priority,
        closeDate: close,
        tag: tag,
        probability: prob,
        weighted: Math.round(amount * prob / 100),
        created: created.toISOString().slice(0, 10),
        age: daysBetween(created, today),
        score: Math.min(100, Math.max(20, prob + (priority === 'hot' ? 20 : priority === 'warm' ? 5 : -10) + (Math.floor(Math.random() * 10) - 5)))
      };
    });
  }
  const DEALS = buildDataset();

  /* -------------------------------------------------------
     Confirm modal
     ------------------------------------------------------- */
  function ensureConfirmModal() {
    let el = document.getElementById('pipelineConfirmModal');
    if (el) return el;
    el = document.createElement('div');
    el.id = 'pipelineConfirmModal';
    el.className = 'modal fade';
    el.setAttribute('tabindex', '-1');
    el.setAttribute('aria-hidden', 'true');
    el.innerHTML =
      '<div class="modal-dialog modal-dialog-centered modal-sm">' +
        '<div class="modal-content">' +
          '<div class="modal-header border-0 pb-0">' +
            '<h6 class="modal-title" data-confirm-title>Confirm</h6>' +
            '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
          '</div>' +
          '<div class="modal-body pt-2"><p class="mb-0 text-body-secondary small" data-confirm-body>Are you sure?</p></div>' +
          '<div class="modal-footer border-0 pt-0">' +
            '<button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>' +
            '<button type="button" class="btn btn-sm btn-danger" data-confirm-ok>Confirm</button>' +
          '</div>' +
        '</div>' +
      '</div>';
    document.body.appendChild(el);
    return el;
  }
  function confirmAction(opts) {
    const el = ensureConfirmModal();
    el.querySelector('[data-confirm-title]').textContent = opts.title || 'Confirm';
    el.querySelector('[data-confirm-body]').textContent  = opts.body  || 'Are you sure?';
    const okBtn = el.querySelector('[data-confirm-ok]');
    okBtn.textContent = opts.okLabel || 'Confirm';
    const modal = bootstrap.Modal.getOrCreateInstance(el);
    const handler = () => { okBtn.removeEventListener('click', handler); modal.hide(); opts.onConfirm && opts.onConfirm(); };
    okBtn.addEventListener('click', handler);
    modal.show();
  }

  /* -------------------------------------------------------
     Expose to page-specific inits
     ------------------------------------------------------- */
  window.OrchidPipeline = {
    STAGES, STAGE_MAP, OWNERS, OWNER_MAP, TAGS, PRIORITY,
    DEALS,
    fmtMoney, fmtMoneyFull, fmtDate, daysBetween, daysUntil, escapeHtml,
    orchidToast, confirmAction
  };

  /* =======================================================
     PAGE: DEALS
     ======================================================= */
  function initDealsPage() {
    const root = document.querySelector('[data-pipeline-page="deals"]');
    if (!root) return;

    const state = {
      deals: DEALS.slice(),
      search: '',
      stageFilter: '',
      ownerFilter: '',
      priorityFilter: '',
      groupBy: 'none',
      sortKey: 'closeDate',
      sortDir: 'asc',
      selected: new Set()
    };

    /* --- KPIs + sparklines --- */
    function renderKPIs() {
      const open = state.deals.filter(d => d.stage !== 'won' && d.stage !== 'lost');
      const weighted = open.reduce((s, d) => s + d.weighted, 0);
      const cycles = state.deals.filter(d => d.stage === 'won').map(d => d.age);
      const avgCycle = cycles.length ? Math.round(cycles.reduce((a, b) => a + b, 0) / cycles.length) : 0;
      const wonCount = state.deals.filter(d => d.stage === 'won').length;
      const closedCount = state.deals.filter(d => d.stage === 'won' || d.stage === 'lost').length;
      const winRate = closedCount ? Math.round((wonCount / closedCount) * 100) : 0;

      root.querySelector('[data-kpi="open"]').textContent = open.length;
      root.querySelector('[data-kpi="weighted"]').textContent = fmtMoney(weighted);
      root.querySelector('[data-kpi="cycle"]').textContent = avgCycle + 'd';
      root.querySelector('[data-kpi="winrate"]').textContent = winRate + '%';

      drawSpark(root.querySelector('[data-spark="open"]'),     [12,14,15,13,17,19,18], '#4f46e5');
      drawSpark(root.querySelector('[data-spark="weighted"]'), [8,10,9,12,14,15,17],   '#22d3ee');
      drawSpark(root.querySelector('[data-spark="cycle"]'),    [24,22,25,21,20,18,17], '#f59e0b');
      drawSpark(root.querySelector('[data-spark="winrate"]'),  [58,60,62,61,64,66,68], '#10b981');
    }
    function drawSpark(canvas, data, color) {
      if (!canvas || !window.Chart) return;
      if (canvas._chart) canvas._chart.destroy();
      canvas._chart = new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: { labels: data.map((_, i) => i), datasets: [{ data, borderColor: color, backgroundColor: color + '22', fill: true, tension: .4, pointRadius: 0, borderWidth: 1.75 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { enabled: false } }, scales: { x: { display: false }, y: { display: false } } }
      });
    }

    /* --- Table --- */
    const tbody = root.querySelector('[data-deals-tbody]');

    function filtered() {
      const q = state.search.toLowerCase();
      return state.deals.filter(d => {
        if (state.stageFilter && d.stage !== state.stageFilter) return false;
        if (state.ownerFilter && d.owner !== state.ownerFilter) return false;
        if (state.priorityFilter && d.priority !== state.priorityFilter) return false;
        if (q && !(d.name + ' ' + d.company).toLowerCase().includes(q)) return false;
        return true;
      });
    }
    function sorted(list) {
      const k = state.sortKey, dir = state.sortDir === 'asc' ? 1 : -1;
      return list.slice().sort((a, b) => {
        let av = a[k], bv = b[k];
        if (k === 'closeDate') { av = new Date(av); bv = new Date(bv); }
        if (typeof av === 'string') return av.localeCompare(bv) * dir;
        return (av - bv) * dir;
      });
    }
    function rowHTML(d) {
      const s = STAGE_MAP[d.stage];
      const o = OWNER_MAP[d.owner];
      const daysToClose = daysUntil(d.closeDate);
      const ageLabel = d.age + 'd';
      const closeLabel = fmtDate(d.closeDate);
      const closeCls = daysToClose < 0 ? 'text-danger' : daysToClose <= 14 ? 'text-warning' : 'text-body-secondary';
      const isSel = state.selected.has(d.id);
      return (
        '<tr data-deal-row="' + d.id + '"' + (isSel ? ' class="is-selected"' : '') + '>' +
          '<td><input type="checkbox" class="form-check-input" data-deal-select="' + d.id + '"' + (isSel ? ' checked' : '') + ' aria-label="Select deal"></td>' +
          '<td>' +
            '<div class="d-flex align-items-center gap-2">' +
              '<span class="pipeline-company-logo pipeline-company-logo--' + d.logoLetter + '">' + d.logoLetter.toUpperCase() + '</span>' +
              '<div><p class="mb-0 fw-semibold small">' + escapeHtml(d.name) + '</p>' +
              '<small class="text-body-secondary">' + escapeHtml(d.company) + '</small></div>' +
            '</div>' +
          '</td>' +
          '<td><span class="pipeline-chip pipeline-chip--' + s.id + '"><span class="pipeline-chip__dot"></span>' + s.label + '</span></td>' +
          '<td class="fw-semibold">' + fmtMoney(d.amount) + '</td>' +
          '<td>' +
            '<span class="deals-prob-bar"><span class="deals-prob-bar__fill" data-fill-width="' + d.probability + '"></span></span>' +
            '<small class="text-body-secondary">' + d.probability + '%</small>' +
          '</td>' +
          '<td class="' + closeCls + '">' + closeLabel + '</td>' +
          '<td><span class="avatar avatar-xs text-white ' + o.color + '" title="' + escapeHtml(o.name) + '">' + o.initials + '</span></td>' +
          '<td class="text-body-secondary small">' + ageLabel + '</td>' +
          '<td class="text-end">' +
            '<span class="deals-quick-actions">' +
              '<button class="btn btn-sm btn-icon" type="button" data-deal-advance="' + d.id + '" aria-label="Advance stage" title="Advance stage"><i class="bi bi-arrow-right-circle"></i></button>' +
              '<button class="btn btn-sm btn-icon" type="button" data-deal-log="' + d.id + '" aria-label="Log activity" title="Log activity"><i class="bi bi-journal-plus"></i></button>' +
              '<button class="btn btn-sm btn-icon" type="button" data-deal-edit="' + d.id + '" aria-label="Edit" title="Edit"><i class="bi bi-pencil"></i></button>' +
            '</span>' +
            '<div class="dropdown d-inline-block">' +
              '<button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions"><i class="bi bi-three-dots-vertical"></i></button>' +
              '<ul class="dropdown-menu dropdown-menu-end">' +
                '<li><a class="dropdown-item" href="#" data-deal-won="' + d.id + '"><i class="bi bi-trophy me-2"></i>Mark as Won</a></li>' +
                '<li><a class="dropdown-item text-danger" href="#" data-deal-lost="' + d.id + '"><i class="bi bi-x-circle me-2"></i>Mark as Lost</a></li>' +
              '</ul>' +
            '</div>' +
          '</td>' +
        '</tr>'
      );
    }
    function groupHeaderHTML(label, count, subtotal) {
      return '<tr class="deals-group-header"><td colspan="9">' + escapeHtml(label) +
        '<span class="deals-group-header__count">(' + count + ' · ' + fmtMoney(subtotal) + ')</span></td></tr>';
    }
    function renderTable() {
      let list = sorted(filtered());
      if (!list.length) {
        tbody.innerHTML = '<tr><td colspan="9"><div class="pipeline-empty-state"><div class="pipeline-empty-state__icon"><i class="bi bi-inbox"></i></div>No deals match the current filters.</div></td></tr>';
        applyFills();
        return;
      }
      let html = '';
      if (state.groupBy === 'none') {
        html = list.map(rowHTML).join('');
      } else {
        const groups = {};
        list.forEach(d => {
          const key = state.groupBy === 'stage' ? STAGE_MAP[d.stage].label
                   : state.groupBy === 'owner' ? OWNER_MAP[d.owner].name
                   : d.company;
          (groups[key] = groups[key] || []).push(d);
        });
        Object.keys(groups).sort().forEach(k => {
          const subtotal = groups[k].reduce((s, d) => s + d.amount, 0);
          html += groupHeaderHTML(k, groups[k].length, subtotal);
          html += groups[k].map(rowHTML).join('');
        });
      }
      tbody.innerHTML = html;
      applyFills();
    }
    function applyFills() {
      tbody.querySelectorAll('[data-fill-width]').forEach(el => {
        el.style.width = el.dataset.fillWidth + '%';
      });
    }

    /* --- Forecast panel --- */
    let gaugeChart;
    function renderForecast() {
      const won = state.deals.filter(d => d.stage === 'won').reduce((s, d) => s + d.amount, 0);
      const committed = state.deals.filter(d => d.stage === 'negotiation').reduce((s, d) => s + d.weighted, 0);
      const best = state.deals.filter(d => d.stage !== 'lost').reduce((s, d) => s + d.weighted, 0);
      const quota = 850000;
      const pct = Math.min(100, Math.round((won + committed) / quota * 100));

      root.querySelector('[data-forecast-won]').textContent = fmtMoney(won);
      root.querySelector('[data-forecast-committed]').textContent = fmtMoney(committed);
      root.querySelector('[data-forecast-best]').textContent = fmtMoney(best);
      root.querySelector('[data-forecast-quota]').textContent = fmtMoney(quota);

      const bars = root.querySelectorAll('.deals-forecast-bar__fill');
      bars[0].style.width = Math.min(100, won / quota * 100) + '%';
      bars[1].style.width = Math.min(100, committed / quota * 100) + '%';
      bars[2].style.width = Math.min(100, best / quota * 100) + '%';

      const canvas = root.querySelector('[data-forecast-gauge]');
      if (canvas && window.Chart) {
        if (gaugeChart) gaugeChart.destroy();
        gaugeChart = new Chart(canvas.getContext('2d'), {
          type: 'doughnut',
          data: { datasets: [{ data: [pct, 100 - pct], backgroundColor: ['#4f46e5', 'rgba(148,163,184,.18)'], borderWidth: 0, circumference: 220, rotation: 250 }] },
          options: { responsive: true, maintainAspectRatio: false, cutout: '78%', plugins: { legend: { display: false }, tooltip: { enabled: false } } }
        });
        root.querySelector('[data-forecast-pct]').textContent = pct + '%';
      }

      // At-risk = past close date, not won/lost
      const today = new Date('2026-07-22');
      const risky = state.deals.filter(d => new Date(d.closeDate) < today && d.stage !== 'won' && d.stage !== 'lost')
        .concat(state.deals.filter(d => d.priority === 'hot' && d.probability < 50))
        .slice(0, 5);
      const list = root.querySelector('[data-atrisk-list]');
      if (!risky.length) {
        list.innerHTML = '<li class="text-body-secondary small text-center py-3">No at-risk deals</li>';
      } else {
        list.innerHTML = risky.map(d => (
          '<li class="deals-atrisk-list__item">' +
            '<i class="bi bi-exclamation-triangle-fill text-danger"></i>' +
            '<div class="deals-atrisk-list__body">' +
              '<p class="deals-atrisk-list__name">' + escapeHtml(d.name) + '</p>' +
              '<span class="deals-atrisk-list__meta">' + fmtMoney(d.amount) + ' · closes ' + fmtDate(d.closeDate) + '</span>' +
            '</div>' +
          '</li>'
        )).join('');
      }
    }

    function renderAll() { renderKPIs(); renderTable(); renderForecast(); }

    /* --- Events --- */
    root.querySelector('[data-deals-search]').addEventListener('input', (e) => {
      state.search = e.target.value; renderTable();
    });
    root.querySelector('[data-deals-stage-filter]').addEventListener('change', (e) => {
      state.stageFilter = e.target.value; renderTable();
    });
    root.querySelector('[data-deals-owner-filter]').addEventListener('change', (e) => {
      state.ownerFilter = e.target.value; renderTable();
    });
    root.querySelector('[data-deals-priority-filter]').addEventListener('change', (e) => {
      state.priorityFilter = e.target.value; renderTable();
    });
    root.querySelector('[data-deals-group]').addEventListener('change', (e) => {
      state.groupBy = e.target.value; renderTable();
    });

    root.querySelectorAll('th[data-deals-sort]').forEach(th => {
      th.addEventListener('click', () => {
        const k = th.dataset.dealsSort;
        if (state.sortKey === k) state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
        else { state.sortKey = k; state.sortDir = 'asc'; }
        root.querySelectorAll('th[data-deals-sort]').forEach(x => x.classList.remove('is-sort-asc', 'is-sort-desc'));
        th.classList.add(state.sortDir === 'asc' ? 'is-sort-asc' : 'is-sort-desc');
        renderTable();
      });
    });

    tbody.addEventListener('click', (e) => {
      const sel = e.target.closest('[data-deal-select]');
      if (sel) {
        const id = sel.dataset.dealSelect;
        if (sel.checked) state.selected.add(id); else state.selected.delete(id);
        sel.closest('tr').classList.toggle('is-selected', sel.checked);
        return;
      }
      const advance = e.target.closest('[data-deal-advance]');
      if (advance) {
        e.preventDefault();
        const id = advance.dataset.dealAdvance;
        const deal = state.deals.find(x => x.id === id);
        const seq = ['prospect', 'qualified', 'proposal', 'negotiation', 'won'];
        const idx = seq.indexOf(deal.stage);
        if (idx >= 0 && idx < seq.length - 1) {
          deal.stage = seq[idx + 1];
          deal.probability = STAGE_MAP[deal.stage].prob;
          deal.weighted = Math.round(deal.amount * deal.probability / 100);
          orchidToast(deal.name + ' → ' + STAGE_MAP[deal.stage].label, 'success');
          renderAll();
        }
        return;
      }
      const log = e.target.closest('[data-deal-log]');
      if (log) { e.preventDefault(); orchidToast('Activity logged', 'info'); return; }
      const edit = e.target.closest('[data-deal-edit]');
      if (edit) { e.preventDefault(); orchidToast('Opening editor…', 'primary'); return; }
      const won = e.target.closest('[data-deal-won]');
      if (won) {
        e.preventDefault();
        const deal = state.deals.find(x => x.id === won.dataset.dealWon);
        deal.stage = 'won'; deal.probability = 100; deal.weighted = deal.amount;
        orchidToast(deal.name + ' marked as Won', 'success');
        renderAll();
        return;
      }
      const lost = e.target.closest('[data-deal-lost]');
      if (lost) {
        e.preventDefault();
        const deal = state.deals.find(x => x.id === lost.dataset.dealLost);
        confirmAction({
          title: 'Mark as Lost',
          body: 'Move "' + deal.name + '" to Lost? This can be reversed.',
          okLabel: 'Mark Lost',
          onConfirm: () => {
            deal.stage = 'lost'; deal.probability = 0; deal.weighted = 0;
            orchidToast(deal.name + ' marked as Lost', 'danger');
            renderAll();
          }
        });
      }
    });

    // Skeleton then render
    tbody.innerHTML = Array.from({ length: 6 }).map(() =>
      '<tr><td colspan="9"><div class="pipeline-skeleton"></div></td></tr>'
    ).join('');
    setTimeout(renderAll, 240);
  }

  /* =======================================================
     PAGE: SALES PIPELINE (KANBAN)
     ======================================================= */
  function initKanbanPage() {
    const root = document.querySelector('[data-pipeline-page="pipeline"]');
    if (!root) return;

    const state = {
      deals: DEALS.slice(),
      search: '',
      owner: '',
      tag: '',
      closeFrom: '',
      closeTo: ''
    };
    const COLS = ['prospect', 'qualified', 'proposal', 'negotiation', 'won'];
    const board = root.querySelector('[data-kanban-board]');

    function filtered() {
      const q = state.search.toLowerCase();
      return state.deals.filter(d => {
        if (state.owner && d.owner !== state.owner) return false;
        if (state.tag && d.tag !== state.tag) return false;
        if (state.closeFrom && new Date(d.closeDate) < new Date(state.closeFrom)) return false;
        if (state.closeTo && new Date(d.closeDate) > new Date(state.closeTo)) return false;
        if (q && !(d.name + ' ' + d.company).toLowerCase().includes(q)) return false;
        return true;
      });
    }
    function cardHTML(d) {
      const o = OWNER_MAP[d.owner];
      const days = daysUntil(d.closeDate);
      let dueCls = ''; let dueLabel = fmtDate(d.closeDate);
      if (days < 0) { dueCls = 'kanban-card__due--overdue'; dueLabel = Math.abs(days) + 'd overdue'; }
      else if (days <= 14) { dueCls = 'kanban-card__due--soon'; dueLabel = 'in ' + days + 'd'; }
      return (
        '<article class="kanban-card" draggable="true" data-kanban-card="' + d.id + '">' +
          '<div class="kanban-card__head">' +
            '<span class="pipeline-company-logo pipeline-company-logo--' + d.logoLetter + '">' + d.logoLetter.toUpperCase() + '</span>' +
            '<p class="kanban-card__name">' + escapeHtml(d.name) + '</p>' +
            '<span class="pipeline-priority-dot pipeline-priority-dot--' + d.priority + '" title="' + d.priority + '"></span>' +
          '</div>' +
          '<div class="kanban-card__meta">' +
            '<span class="kanban-card__amount">' + fmtMoney(d.amount) + '</span>' +
            '<span class="badge bg-secondary-subtle text-secondary text-uppercase">' + escapeHtml(d.tag) + '</span>' +
          '</div>' +
          '<div class="kanban-card__footer">' +
            '<span class="avatar avatar-xs text-white ' + o.color + '" title="' + escapeHtml(o.name) + '">' + o.initials + '</span>' +
            '<span class="kanban-card__due ' + dueCls + '">' + dueLabel + '</span>' +
          '</div>' +
        '</article>'
      );
    }
    function renderBoard() {
      const list = filtered();
      board.innerHTML = COLS.map(stageId => {
        const s = STAGE_MAP[stageId];
        const items = list.filter(d => d.stage === stageId);
        const total = items.reduce((sum, d) => sum + d.amount, 0);
        const bodyHTML = items.length
          ? items.map(cardHTML).join('')
          : '<div class="kanban-column__body--empty">Drop deals here</div>';
        return (
          '<section class="kanban-column" data-kanban-column="' + stageId + '">' +
            '<header class="kanban-column__header">' +
              '<div class="kanban-column__title-row">' +
                '<span class="kanban-column__dot" data-dot-color="' + s.color + '"></span>' +
                '<h3 class="kanban-column__title">' + s.label + '</h3>' +
                '<span class="kanban-column__count" data-col-count>' + items.length + '</span>' +
                '<button class="kanban-column__add" type="button" data-kanban-add="' + stageId + '" aria-label="Add deal"><i class="bi bi-plus-lg"></i></button>' +
              '</div>' +
              '<span class="kanban-column__total" data-col-total>' + fmtMoney(total) + '</span>' +
            '</header>' +
            '<div class="kanban-column__body">' + bodyHTML + '</div>' +
          '</section>'
        );
      }).join('');
      // apply dot colors from data attribute
      board.querySelectorAll('[data-dot-color]').forEach(el => {
        el.style.backgroundColor = el.dataset.dotColor;
      });
      bindDragDrop();
    }
    function updateColumnCounts() {
      COLS.forEach(stageId => {
        const col = board.querySelector('[data-kanban-column="' + stageId + '"]');
        if (!col) return;
        const items = state.deals.filter(d => d.stage === stageId);
        col.querySelector('[data-col-count]').textContent = items.length;
        col.querySelector('[data-col-total]').textContent = fmtMoney(items.reduce((s, d) => s + d.amount, 0));
      });
    }

    /* --- HTML5 native drag & drop --- */
    let draggingId = null;
    function bindDragDrop() {
      board.querySelectorAll('[data-kanban-card]').forEach(card => {
        card.addEventListener('dragstart', (e) => {
          draggingId = card.dataset.kanbanCard;
          card.classList.add('is-dragging');
          e.dataTransfer.effectAllowed = 'move';
          e.dataTransfer.setData('text/plain', draggingId);
        });
        card.addEventListener('dragend', () => {
          card.classList.remove('is-dragging');
          draggingId = null;
          board.querySelectorAll('.kanban-column.is-drop-target').forEach(c => c.classList.remove('is-drop-target'));
        });
      });
      board.querySelectorAll('.kanban-column').forEach(col => {
        col.addEventListener('dragover', (e) => {
          e.preventDefault();
          e.dataTransfer.dropEffect = 'move';
          col.classList.add('is-drop-target');
        });
        col.addEventListener('dragleave', (e) => {
          if (!col.contains(e.relatedTarget)) col.classList.remove('is-drop-target');
        });
        col.addEventListener('drop', (e) => {
          e.preventDefault();
          col.classList.remove('is-drop-target');
          const id = e.dataTransfer.getData('text/plain') || draggingId;
          if (!id) return;
          const deal = state.deals.find(x => x.id === id);
          const newStage = col.dataset.kanbanColumn;
          if (!deal || deal.stage === newStage) return;
          deal.stage = newStage;
          deal.probability = STAGE_MAP[newStage].prob;
          deal.weighted = Math.round(deal.amount * deal.probability / 100);
          renderBoard();
          orchidToast(deal.name + ' moved to ' + STAGE_MAP[newStage].label, 'success');
        });
      });
    }

    /* --- Filters --- */
    root.querySelector('[data-kanban-search]').addEventListener('input', (e) => { state.search = e.target.value; renderBoard(); });
    root.querySelector('[data-kanban-owner]').addEventListener('change', (e) => { state.owner = e.target.value; renderBoard(); });
    root.querySelector('[data-kanban-tag]').addEventListener('change', (e) => { state.tag = e.target.value; renderBoard(); });
    root.querySelector('[data-kanban-from]').addEventListener('change', (e) => { state.closeFrom = e.target.value; renderBoard(); });
    root.querySelector('[data-kanban-to]').addEventListener('change', (e) => { state.closeTo = e.target.value; renderBoard(); });

    /* --- Add deal (FAB + column +) --- */
    const offcanvasEl = root.querySelector('#kanbanAddOffcanvas');
    const offcanvas = offcanvasEl ? bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl) : null;

    root.addEventListener('click', (e) => {
      const add = e.target.closest('[data-kanban-add], [data-kanban-fab]');
      if (add) { e.preventDefault(); offcanvas && offcanvas.show(); }
    });
    root.querySelector('[data-kanban-add-form]').addEventListener('submit', (e) => {
      e.preventDefault();
      const form = e.currentTarget;
      const name = form.querySelector('[name=name]').value.trim();
      const company = form.querySelector('[name=company]').value.trim();
      const amount = parseInt(form.querySelector('[name=amount]').value, 10) || 0;
      const ownerId = form.querySelector('[name=owner]').value;
      const priority = form.querySelector('[name=priority]').value;
      const close = form.querySelector('[name=close]').value;
      const tag = form.querySelector('[name=tag]').value;
      if (!name || !company || !amount) { orchidToast('Please fill required fields', 'warning'); return; }
      const letter = (company[0] || 'a').toLowerCase();
      const validLetter = 'iceoua'.includes(letter) ? letter : ['i','c','e','o','u','a'][company.length % 6];
      const newDeal = {
        id: 'D-' + Date.now(),
        name, company, logoLetter: validLetter, amount,
        stage: 'prospect', owner: ownerId, priority,
        closeDate: close || '2026-12-31', tag,
        probability: 15, weighted: Math.round(amount * .15),
        created: '2026-07-22', age: 0, score: 30
      };
      state.deals.unshift(newDeal);
      renderBoard();
      form.reset();
      offcanvas && offcanvas.hide();
      orchidToast('Deal added to Prospect', 'success');
    });

    // Skeleton then render
    board.innerHTML = '<div class="w-100 py-5"><div class="pipeline-skeleton pipeline-skeleton--wide"></div><div class="pipeline-skeleton pipeline-skeleton--med"></div></div>';
    setTimeout(renderBoard, 220);
  }

  /* =======================================================
     PAGE: OPPORTUNITIES
     ======================================================= */
  function initOpportunitiesPage() {
    const root = document.querySelector('[data-pipeline-page="opportunities"]');
    if (!root) return;

    const state = {
      deals: DEALS.slice(),
      search: '',
      stageFilter: '',
      ownerFilter: '',
      sortKey: 'amount',
      sortDir: 'desc',
      selected: new Set(),
      expanded: new Set()
    };
    const tbody = root.querySelector('[data-opps-tbody]');
    const trendCharts = {};

    function renderHero() {
      const total = state.deals.reduce((s, d) => s + d.amount, 0);
      const weighted = state.deals.reduce((s, d) => s + d.weighted, 0);
      root.querySelector('[data-hero-total]').textContent = fmtMoneyFull(total);
      root.querySelector('[data-hero-weighted]').textContent = fmtMoneyFull(weighted);
      root.querySelector('[data-hero-count]').textContent = state.deals.length;

      // Distribution bar
      const bar = root.querySelector('[data-hero-distribution]');
      const stageOrder = ['prospect','qualified','proposal','negotiation','won','lost'];
      const totals = stageOrder.map(s => ({ id: s, val: state.deals.filter(d => d.stage === s).reduce((sum, d) => sum + d.amount, 0) }));
      const sum = totals.reduce((s, t) => s + t.val, 0) || 1;
      bar.innerHTML = totals.map(t => {
        const pct = (t.val / sum * 100).toFixed(1);
        return '<span data-stage-fill="' + t.id + '" data-fill-pct="' + pct + '" title="' + STAGE_MAP[t.id].label + ' — ' + fmtMoney(t.val) + '"></span>';
      }).join('');
      bar.querySelectorAll('[data-stage-fill]').forEach(el => {
        el.style.width = el.dataset.fillPct + '%';
        el.style.backgroundColor = STAGE_MAP[el.dataset.stageFill].color;
      });
      const legend = root.querySelector('[data-hero-legend]');
      legend.innerHTML = stageOrder.map(s => (
        '<span><i data-legend-color="' + STAGE_MAP[s].color + '"></i>' + STAGE_MAP[s].label + '</span>'
      )).join('');
      legend.querySelectorAll('[data-legend-color]').forEach(el => { el.style.backgroundColor = el.dataset.legendColor; });
    }

    function filtered() {
      const q = state.search.toLowerCase();
      return state.deals.filter(d => {
        if (state.stageFilter && d.stage !== state.stageFilter) return false;
        if (state.ownerFilter && d.owner !== state.ownerFilter) return false;
        if (q && !(d.name + ' ' + d.company).toLowerCase().includes(q)) return false;
        return true;
      });
    }
    function sorted(list) {
      const k = state.sortKey, dir = state.sortDir === 'asc' ? 1 : -1;
      return list.slice().sort((a, b) => {
        let av = a[k], bv = b[k];
        if (k === 'closeDate') { av = new Date(av); bv = new Date(bv); }
        if (typeof av === 'string') return av.localeCompare(bv) * dir;
        return (av - bv) * dir;
      });
    }
    function scoreClass(s) { return s >= 70 ? 'opps-score--high' : s >= 45 ? 'opps-score--med' : 'opps-score--low'; }

    function stageDropdown(d) {
      const s = STAGE_MAP[d.stage];
      return (
        '<div class="dropdown">' +
          '<button class="pipeline-chip pipeline-chip--' + s.id + '" type="button" data-bs-toggle="dropdown" aria-expanded="false">' +
            '<span class="pipeline-chip__dot"></span>' + s.label +
            '<i class="bi bi-chevron-down small ms-1"></i>' +
          '</button>' +
          '<ul class="dropdown-menu">' +
            STAGES.filter(x => x.id !== 'lost').map(x =>
              '<li><a class="dropdown-item small" href="#" data-opps-stage-change="' + d.id + '" data-opps-stage-to="' + x.id + '">' +
                '<span class="pipeline-chip__dot" data-swatch="' + x.color + '"></span> ' + x.label + '</a></li>'
            ).join('') +
            '<li><hr class="dropdown-divider"></li>' +
            '<li><a class="dropdown-item small text-danger" href="#" data-opps-stage-change="' + d.id + '" data-opps-stage-to="lost">Mark as Lost</a></li>' +
          '</ul>' +
        '</div>'
      );
    }

    function rowHTML(d) {
      const o = OWNER_MAP[d.owner];
      const isSel = state.selected.has(d.id);
      const isExp = state.expanded.has(d.id);
      return (
        '<tr class="opps-row' + (isSel ? ' is-selected' : '') + '" data-opps-row="' + d.id + '">' +
          '<td><input type="checkbox" class="form-check-input" data-opps-select="' + d.id + '"' + (isSel ? ' checked' : '') + ' aria-label="Select opportunity"></td>' +
          '<td>' +
            '<div class="d-flex align-items-center gap-2">' +
              '<button class="opps-expand-btn' + (isExp ? ' is-open' : '') + '" type="button" data-opps-expand="' + d.id + '" aria-label="Toggle detail"><i class="bi bi-chevron-right"></i></button>' +
              '<span class="pipeline-company-logo pipeline-company-logo--' + d.logoLetter + '">' + d.logoLetter.toUpperCase() + '</span>' +
              '<div><p class="mb-0 fw-semibold small">' + escapeHtml(d.name) + '</p>' +
              '<small class="text-body-secondary">' + escapeHtml(d.company) + ' · ' + escapeHtml(o.name) + '</small></div>' +
            '</div>' +
          '</td>' +
          '<td>' + stageDropdown(d) + '</td>' +
          '<td class="fw-semibold">' + fmtMoney(d.amount) + '</td>' +
          '<td>' + d.probability + '%</td>' +
          '<td class="opps-weighted">' + fmtMoney(d.weighted) + '</td>' +
          '<td class="text-body-secondary">' + fmtDate(d.closeDate) + '</td>' +
          '<td><span class="opps-score ' + scoreClass(d.score) + '">' + d.score + '</span></td>' +
        '</tr>' +
        '<tr class="opps-expand-row"><td colspan="8">' +
          '<div class="opps-expand-panel' + (isExp ? ' is-open' : '') + '" data-opps-panel="' + d.id + '">' +
            '<div class="opps-expand-panel__inner">' +
              '<div>' +
                '<h6 class="small text-body-secondary text-uppercase mb-2">Stage history</h6>' +
                stageTimelineHTML(d) +
              '</div>' +
              '<div>' +
                '<h6 class="small text-body-secondary text-uppercase mb-2">Weighted revenue trend</h6>' +
                '<div class="opps-mini-chart"><canvas data-opps-trend="' + d.id + '"></canvas></div>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</td></tr>'
      );
    }
    function stageTimelineHTML(d) {
      const seq = ['prospect', 'qualified', 'proposal', 'negotiation', 'won'];
      const currentIdx = seq.indexOf(d.stage);
      const doneUntil = d.stage === 'lost' ? -1 : currentIdx;
      return '<ol class="opps-timeline">' + seq.map((sid, i) => {
        const cls = i < doneUntil ? 'is-done' : (i === doneUntil ? 'is-current' : '');
        return '<li class="opps-timeline__step ' + cls + '"><span class="opps-timeline__dot"></span><span class="opps-timeline__label">' + STAGE_MAP[sid].label + '</span></li>';
      }).join('') + '</ol>';
    }

    function renderTable() {
      const list = sorted(filtered());
      if (!list.length) {
        tbody.innerHTML = '<tr><td colspan="8"><div class="pipeline-empty-state"><div class="pipeline-empty-state__icon"><i class="bi bi-clipboard-x"></i></div>No opportunities match filters.</div></td></tr>';
        return;
      }
      tbody.innerHTML = list.map(rowHTML).join('');
      // apply swatch colors
      tbody.querySelectorAll('[data-swatch]').forEach(el => { el.style.backgroundColor = el.dataset.swatch; });
      // Draw any open trend charts
      list.forEach(d => { if (state.expanded.has(d.id)) drawTrend(d); });
    }
    function drawTrend(d) {
      const canvas = tbody.querySelector('[data-opps-trend="' + d.id + '"]');
      if (!canvas || !window.Chart) return;
      if (trendCharts[d.id]) { trendCharts[d.id].destroy(); }
      const base = d.weighted || 5000;
      const series = Array.from({ length: 8 }, (_, i) => Math.round(base * (0.5 + i * 0.08 + (Math.sin(i + d.amount % 7) * 0.05))));
      series[7] = d.weighted;
      trendCharts[d.id] = new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
          labels: ['W1','W2','W3','W4','W5','W6','W7','Now'],
          datasets: [{ label: 'Weighted $', data: series, borderColor: STAGE_MAP[d.stage].color, backgroundColor: STAGE_MAP[d.stage].color + '22', fill: true, tension: .4, pointRadius: 2, borderWidth: 2 }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { y: { ticks: { callback: (v) => fmtMoney(v) } } }
        }
      });
    }

    function updateBulkBar() {
      const bar = root.querySelector('[data-opps-bulk]');
      bar.classList.toggle('is-visible', state.selected.size > 0);
      bar.querySelector('[data-opps-bulk-count]').textContent = state.selected.size;
    }
    function renderAll() { renderHero(); renderTable(); updateBulkBar(); }

    /* Events */
    root.querySelector('[data-opps-search]').addEventListener('input', (e) => { state.search = e.target.value; renderTable(); });
    root.querySelector('[data-opps-stage]').addEventListener('change', (e) => { state.stageFilter = e.target.value; renderTable(); });
    root.querySelector('[data-opps-owner]').addEventListener('change', (e) => { state.ownerFilter = e.target.value; renderTable(); });

    root.querySelectorAll('th[data-opps-sort]').forEach(th => {
      th.addEventListener('click', () => {
        const k = th.dataset.oppsSort;
        if (state.sortKey === k) state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
        else { state.sortKey = k; state.sortDir = 'desc'; }
        root.querySelectorAll('th[data-opps-sort]').forEach(x => x.classList.remove('is-sort-asc', 'is-sort-desc'));
        th.classList.add(state.sortDir === 'asc' ? 'is-sort-asc' : 'is-sort-desc');
        renderTable();
      });
    });

    tbody.addEventListener('click', (e) => {
      const sel = e.target.closest('[data-opps-select]');
      if (sel) {
        const id = sel.dataset.oppsSelect;
        if (sel.checked) state.selected.add(id); else state.selected.delete(id);
        updateBulkBar();
        return;
      }
      const exp = e.target.closest('[data-opps-expand]');
      if (exp) {
        const id = exp.dataset.oppsExpand;
        const isOpen = state.expanded.has(id);
        if (isOpen) state.expanded.delete(id); else state.expanded.add(id);
        exp.classList.toggle('is-open', !isOpen);
        const panel = tbody.querySelector('[data-opps-panel="' + id + '"]');
        panel.classList.toggle('is-open', !isOpen);
        if (!isOpen) {
          const d = state.deals.find(x => x.id === id);
          setTimeout(() => drawTrend(d), 60);
        }
        return;
      }
      const change = e.target.closest('[data-opps-stage-change]');
      if (change) {
        e.preventDefault();
        const id = change.dataset.oppsStageChange;
        const to = change.dataset.oppsStageTo;
        const d = state.deals.find(x => x.id === id);
        if (to === 'lost') {
          confirmAction({
            title: 'Mark as Lost',
            body: 'Mark "' + d.name + '" as Lost?',
            okLabel: 'Mark Lost',
            onConfirm: () => { applyStageChange(d, to); }
          });
        } else {
          applyStageChange(d, to);
        }
      }
    });
    function applyStageChange(d, to) {
      d.stage = to;
      d.probability = STAGE_MAP[to].prob;
      d.weighted = Math.round(d.amount * d.probability / 100);
      renderAll();
      orchidToast(d.name + ' → ' + STAGE_MAP[to].label + (to === 'lost' ? '' : ' (weighted recomputed)'), to === 'lost' ? 'danger' : 'success');
    }

    /* Bulk actions */
    root.querySelector('[data-opps-bulk-stage]').addEventListener('change', (e) => {
      const to = e.target.value; if (!to) return;
      state.selected.forEach(id => {
        const d = state.deals.find(x => x.id === id);
        if (d) { d.stage = to; d.probability = STAGE_MAP[to].prob; d.weighted = Math.round(d.amount * d.probability / 100); }
      });
      const count = state.selected.size;
      state.selected.clear();
      renderAll();
      e.target.value = '';
      orchidToast(count + ' opportunities moved to ' + STAGE_MAP[to].label, 'success');
    });
    root.querySelector('[data-opps-bulk-clear]').addEventListener('click', () => {
      state.selected.clear(); renderAll();
    });

    /* Skeleton then render */
    tbody.innerHTML = Array.from({ length: 5 }).map(() =>
      '<tr><td colspan="8"><div class="pipeline-skeleton"></div></td></tr>'
    ).join('');
    setTimeout(renderAll, 240);
  }

  /* -------------------------------------------------------
     Boot
     ------------------------------------------------------- */
  document.addEventListener('DOMContentLoaded', () => {
    initDealsPage();
    initKanbanPage();
    initOpportunitiesPage();
  });
})();
