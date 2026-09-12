/* =====================================================
   Orchid - Analytics Lists (shared toolkit + per-page hooks)
   ===================================================== */
(function () {
  'use strict';

  /* ---------- Utilities ---------- */
  const fmtMoney = (n, opts = {}) => {
    const { compact = false, cur = 'USD' } = opts;
    if (compact) {
      const abs = Math.abs(n);
      if (abs >= 1e9) return `$${(n / 1e9).toFixed(1)}B`;
      if (abs >= 1e6) return `$${(n / 1e6).toFixed(1)}M`;
      if (abs >= 1e3) return `$${(n / 1e3).toFixed(1)}K`;
      return `$${n.toFixed(0)}`;
    }
    try {
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: cur, maximumFractionDigits: 0 }).format(n);
    } catch (e) { return `$${n}`; }
  };

  const fmtDate = (d) => {
    if (!(d instanceof Date)) d = new Date(d);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  };

  const pct = (n, digits = 1) => `${Number(n).toFixed(digits)}%`;

  const orchidToast = (msg, kind = 'success') => {
    let host = document.getElementById('orchidToastHost');
    if (!host) {
      host = document.createElement('div');
      host.id = 'orchidToastHost';
      host.className = 'toast-container position-fixed bottom-0 end-0 p-3';
      host.style.zIndex = '2000';
      document.body.appendChild(host);
    }
    const color = { success: 'success', danger: 'danger', warning: 'warning', info: 'info' }[kind] || 'primary';
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${color} border-0 mb-2`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `<div class="d-flex"><div class="toast-body">${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
    host.appendChild(el);
    if (window.bootstrap && bootstrap.Toast) {
      const t = new bootstrap.Toast(el, { delay: 2600 });
      t.show();
      el.addEventListener('hidden.bs.toast', () => el.remove());
    } else {
      setTimeout(() => el.remove(), 2600);
    }
  };

  // Expand a 3-digit hex shorthand ('#fff') to full 6-digit ('#ffffff') so we
  // can safely append 2-char alpha bytes to it. Leaves 6/8-digit hex untouched.
  const normalizeHex = (c) => {
    if (typeof c !== 'string') return '#4f46e5';
    const m = c.match(/^#([0-9a-fA-F]{3})$/);
    if (m) return '#' + m[1].split('').map(ch => ch + ch).join('');
    return c;
  };

  const renderSparkline = (canvas, data, color = '#4f46e5') => {
    if (!canvas || !window.Chart) return null;
    const c6 = normalizeHex(color);
    const ctx = canvas.getContext('2d');
    return new Chart(ctx, {
      type: 'line',
      data: {
        labels: data.map((_, i) => i),
        datasets: [{
          data,
          borderColor: c6,
          borderWidth: 2,
          fill: true,
          backgroundColor: (c) => {
            const { chart } = c;
            const { ctx: c2, chartArea } = chart;
            if (!chartArea) return 'rgba(79,70,229,0.10)';
            const g = c2.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            g.addColorStop(0, `${c6}55`);
            g.addColorStop(1, `${c6}00`);
            return g;
          },
          tension: 0.35,
          pointRadius: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: { x: { display: false }, y: { display: false } },
        elements: { line: { borderCapStyle: 'round' } }
      }
    });
  };

  /* ---------- Shared dataset ---------- */
  const CUSTOMERS = [
    { name: 'Nova Systems',      industry: 'SaaS',       ltv: 128400, mrr: 4600, tenureM: 28, health: 92, lastInt: '2026-07-19', segment: 'Champion',  freq: 0.9, spend: 0.95 },
    { name: 'Peak Labs',         industry: 'Fintech',    ltv: 96500,  mrr: 3900, tenureM: 22, health: 88, lastInt: '2026-07-21', segment: 'Champion',  freq: 0.82, spend: 0.88 },
    { name: 'Aurora Health',     industry: 'Healthcare', ltv: 74200,  mrr: 3100, tenureM: 24, health: 76, lastInt: '2026-07-18', segment: 'Loyal',     freq: 0.78, spend: 0.55 },
    { name: 'Vela Retail',       industry: 'Retail',     ltv: 58900,  mrr: 2450, tenureM: 24, health: 71, lastInt: '2026-07-15', segment: 'Loyal',     freq: 0.72, spend: 0.48 },
    { name: 'Kite Manufacturing',industry: 'Manufact.',  ltv: 44300,  mrr: 1800, tenureM: 24, health: 62, lastInt: '2026-07-12', segment: 'Loyal',     freq: 0.66, spend: 0.42 },
    { name: 'Halo Media',        industry: 'Media',      ltv: 39800,  mrr: 1650, tenureM: 24, health: 58, lastInt: '2026-07-05', segment: 'At Risk',   freq: 0.32, spend: 0.68 },
    { name: 'Ember Studios',     industry: 'Media',      ltv: 32100,  mrr: 1350, tenureM: 18, health: 54, lastInt: '2026-06-30', segment: 'At Risk',   freq: 0.28, spend: 0.62 },
    { name: 'Cobalt Freight',    industry: 'Logistics',  ltv: 27500,  mrr: 1150, tenureM: 15, health: 48, lastInt: '2026-06-25', segment: 'At Risk',   freq: 0.24, spend: 0.55 },
    { name: 'Zephyr Studios',    industry: 'Media',      ltv: 21200,  mrr: 890,  tenureM: 12, health: 40, lastInt: '2026-06-10', segment: 'At Risk',   freq: 0.18, spend: 0.48 },
    { name: 'Marlin Analytics',  industry: 'SaaS',       ltv: 18700,  mrr: 780,  tenureM: 10, health: 36, lastInt: '2026-05-28', segment: 'Lost',      freq: 0.15, spend: 0.28 },
    { name: 'Onyx Consulting',   industry: 'Services',   ltv: 15400,  mrr: 640,  tenureM: 8,  health: 30, lastInt: '2026-05-14', segment: 'Lost',      freq: 0.12, spend: 0.22 },
    { name: 'Ridge Wear',        industry: 'Retail',     ltv: 11800,  mrr: 490,  tenureM: 6,  health: 24, lastInt: '2026-04-30', segment: 'Lost',      freq: 0.08, spend: 0.18 },
    { name: 'Loom Software',     industry: 'SaaS',       ltv: 89600,  mrr: 3700, tenureM: 21, health: 90, lastInt: '2026-07-22', segment: 'Champion',  freq: 0.9, spend: 0.85 },
    { name: 'Solstice Bank',     industry: 'Fintech',    ltv: 112400, mrr: 4200, tenureM: 26, health: 94, lastInt: '2026-07-20', segment: 'Champion',  freq: 0.92, spend: 0.92 },
    { name: 'Terra Insurance',   industry: 'Insurance',  ltv: 68900,  mrr: 2850, tenureM: 24, health: 70, lastInt: '2026-07-16', segment: 'Loyal',     freq: 0.68, spend: 0.5 }
  ];

  const REPS = [
    { name: 'Alex Kim',      init: 'AK', color: '#4f46e5', sales: 148500, deals: 24 },
    { name: 'Sarah Miller',  init: 'SM', color: '#22d3ee', sales: 132400, deals: 21 },
    { name: 'James Doe',     init: 'JD', color: '#10b981', sales: 118200, deals: 19 },
    { name: 'Ava Lee',       init: 'AL', color: '#f59e0b', sales: 96800,  deals: 17 },
    { name: 'Ryan Green',    init: 'RG', color: '#ef4444', sales: 82400,  deals: 15 },
    { name: 'Emma Watson',   init: 'EW', color: '#8b5cf6', sales: 74100,  deals: 13 },
    { name: 'Noah Park',     init: 'NP', color: '#ec4899', sales: 61300,  deals: 11 }
  ];

  const PRODUCTS = ['Orchid Pro', 'Orchid Business', 'Orchid Enterprise', 'Orchid Starter', 'Orchid Analytics'];

  const CAMPAIGNS = [
    { name: 'Q3 Product Launch',      channel: 'google',   budget: 45000, spent: 41200, cpa: 62,  conv: 664, roas: 3.8, status: 'active' },
    { name: 'LinkedIn Enterprise ABM',channel: 'linkedin', budget: 30000, spent: 28400, cpa: 128, conv: 222, roas: 4.2, status: 'active' },
    { name: 'Meta Retargeting',       channel: 'meta',     budget: 18000, spent: 17250, cpa: 44,  conv: 392, roas: 2.9, status: 'active' },
    { name: 'Summer Email Nurture',   channel: 'email',    budget: 8000,  spent: 6100,  cpa: 22,  conv: 277, roas: 5.6, status: 'active' },
    { name: 'Brand Awareness EU',     channel: 'meta',     budget: 22000, spent: 21800, cpa: 71,  conv: 307, roas: 2.1, status: 'active' },
    { name: 'Search - Non-Brand',     channel: 'google',   budget: 55000, spent: 53200, cpa: 58,  conv: 917, roas: 4.4, status: 'active' },
    { name: 'Search - Brand',         channel: 'google',   budget: 12000, spent: 10800, cpa: 18,  conv: 600, roas: 8.2, status: 'active' },
    { name: 'Referral Boost',         channel: 'referral', budget: 5000,  spent: 4200,  cpa: 34,  conv: 123, roas: 6.1, status: 'active' },
    { name: 'Podcast Sponsorships',   channel: 'referral', budget: 15000, spent: 15000, cpa: 88,  conv: 170, roas: 2.7, status: 'ended' },
    { name: 'Content SEO Push',       channel: 'organic',  budget: 20000, spent: 18400, cpa: 42,  conv: 438, roas: 5.1, status: 'active' },
    { name: 'Webinar Series',         channel: 'email',    budget: 10000, spent: 9200,  cpa: 36,  conv: 255, roas: 4.8, status: 'active' },
    { name: 'LinkedIn Thought Ldr',   channel: 'linkedin', budget: 18000, spent: 17600, cpa: 142, conv: 124, roas: 3.4, status: 'active' },
    { name: 'Winter Promo',           channel: 'meta',     budget: 25000, spent: 24700, cpa: 51,  conv: 484, roas: 3.6, status: 'active' },
    { name: 'Partner Co-Marketing',   channel: 'referral', budget: 8000,  spent: 6800,  cpa: 40,  conv: 170, roas: 4.4, status: 'paused' },
    { name: 'Newsletter Sponsorships',channel: 'email',    budget: 6000,  spent: 5400,  cpa: 30,  conv: 180, roas: 5.2, status: 'active' }
  ];

  const REVENUE_MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const MRR_HISTORY = [612, 634, 658, 681, 702, 728, 748, 762, 781, 798, 812, 828]; // in thousands
  const MRR_MOVEMENT = { start: 812, new: 42, expansion: 18, contraction: -8, churn: -14, end: 850 };

  /* ---------- Expose ---------- */
  window.AnalyticsList = {
    fmtMoney, fmtDate, pct, orchidToast, renderSparkline,
    CUSTOMERS, REPS, PRODUCTS, CAMPAIGNS, REVENUE_MONTHS, MRR_HISTORY, MRR_MOVEMENT
  };

  /* ---------- Chart defaults ---------- */
  if (window.Chart) {
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--orchid-text-muted').trim() || '#6b7385';
  }

  /* =====================================================
     REPORTS PAGE
     ===================================================== */
  const initReportsPage = () => {
    const root = document.querySelector('[data-anlist-page="reports"]');
    if (!root) return;

    const REPORTS = [
      { id: 1, cat: 'my',       title: 'Weekly Revenue',           desc: 'Weekly bookings vs targets across teams.',       type: 'line',   owner: 'Alex Kim',     ownerInit: 'AK', last: '2026-07-22', next: '2026-07-29' },
      { id: 2, cat: 'my',       title: 'MRR by Segment',            desc: 'Recurring revenue split by customer segment.',    type: 'bar',    owner: 'Alex Kim',     ownerInit: 'AK', last: '2026-07-21', next: '2026-07-28' },
      { id: 3, cat: 'shared',   title: 'Customer Cohort Retention', desc: 'Retention heatmap by signup month.',              type: 'heat',   owner: 'Sarah Miller', ownerInit: 'SM', last: '2026-07-20', next: '—' },
      { id: 4, cat: 'shared',   title: 'Sales by Region',           desc: 'Regional performance for the current quarter.',   type: 'donut',  owner: 'James Doe',    ownerInit: 'JD', last: '2026-07-19', next: '2026-07-26' },
      { id: 5, cat: 'my',       title: 'Support SLA Compliance',    desc: 'First-response and resolution SLA %.',            type: 'bar',    owner: 'Alex Kim',     ownerInit: 'AK', last: '2026-07-18', next: '2026-07-25' },
      { id: 6, cat: 'templates',title: 'Executive Summary',         desc: 'High-level KPIs for weekly leadership review.',   type: 'mixed',  owner: 'Template',     ownerInit: 'T',  last: '—',           next: '—' },
      { id: 7, cat: 'shared',   title: 'Campaign ROAS',             desc: 'Return on ad spend by campaign channel.',         type: 'bar',    owner: 'Ava Lee',      ownerInit: 'AL', last: '2026-07-17', next: '2026-07-24' },
      { id: 8, cat: 'scheduled',title: 'Board Deck Metrics',        desc: 'Monthly metrics compiled for the board deck.',    type: 'mixed',  owner: 'Alex Kim',     ownerInit: 'AK', last: '2026-07-01', next: '2026-08-01' },
      { id: 9, cat: 'shared',   title: 'Product Adoption',          desc: 'Feature adoption curves for the last 90 days.',   type: 'line',   owner: 'Emma Watson',  ownerInit: 'EW', last: '2026-07-15', next: '—' },
      { id: 10,cat: 'my',       title: 'Pipeline Health',           desc: 'Aging opportunities by stage and owner.',         type: 'bar',    owner: 'Alex Kim',     ownerInit: 'AK', last: '2026-07-20', next: '2026-07-27' },
      { id: 11,cat: 'scheduled',title: 'Daily Ops Digest',          desc: 'End-of-day operational status snapshot.',         type: 'mixed',  owner: 'Ryan Green',   ownerInit: 'RG', last: '2026-07-22', next: '2026-07-23' },
      { id: 12,cat: 'templates',title: 'Churn Autopsy',             desc: 'Detailed breakdown of last month\'s churn.',      type: 'mixed',  owner: 'Template',     ownerInit: 'T',  last: '—',           next: '—' },
      { id: 13,cat: 'shared',   title: 'NPS by Segment',            desc: 'Net promoter score across customer segments.',    type: 'bar',    owner: 'Sarah Miller', ownerInit: 'SM', last: '2026-07-14', next: '2026-07-21' },
      { id: 14,cat: 'my',       title: 'Deal Velocity',             desc: 'Average days per stage over 12 months.',          type: 'line',   owner: 'Alex Kim',     ownerInit: 'AK', last: '2026-07-19', next: '2026-07-26' },
      { id: 15,cat: 'shared',   title: 'Support Ticket Volume',     desc: 'Ticket volume trends by category and priority.',  type: 'bar',    owner: 'Ryan Green',   ownerInit: 'RG', last: '2026-07-21', next: '—' },
      { id: 16,cat: 'templates',title: 'Marketing Funnel',          desc: 'End-to-end funnel from ad impression to paid.',   type: 'funnel', owner: 'Template',     ownerInit: 'T',  last: '—',           next: '—' },
      { id: 17,cat: 'scheduled',title: 'Financial Close',           desc: 'Monthly close package for the finance team.',     type: 'mixed',  owner: 'Noah Park',    ownerInit: 'NP', last: '2026-07-05', next: '2026-08-05' },
      { id: 18,cat: 'shared',   title: 'Referral Program',          desc: 'Attribution & LTV for referred customers.',       type: 'donut',  owner: 'Ava Lee',      ownerInit: 'AL', last: '2026-07-16', next: '2026-07-23' }
    ];

    const typeMeta = {
      line:   { icon: 'bi-graph-up',       label: 'Line' },
      bar:    { icon: 'bi-bar-chart',      label: 'Bar' },
      donut:  { icon: 'bi-pie-chart',      label: 'Donut' },
      mixed:  { icon: 'bi-bar-chart-line', label: 'Mixed' },
      heat:   { icon: 'bi-grid-3x3-gap',   label: 'Heatmap' },
      funnel: { icon: 'bi-funnel',         label: 'Funnel' }
    };

    const thumbSvg = (type) => {
      // Small inline SVG previews per type
      switch (type) {
        case 'line':
          return `<svg viewBox="0 0 100 40" xmlns="http://www.w3.org/2000/svg"><polyline fill="none" stroke="#4f46e5" stroke-width="2" points="0,32 15,24 30,28 45,14 60,18 75,8 90,12 100,6"/><polyline fill="none" stroke="#22d3ee" stroke-width="2" stroke-dasharray="3 2" points="0,36 15,30 30,32 45,22 60,26 75,18 90,20 100,14"/></svg>`;
        case 'bar':
          return `<svg viewBox="0 0 100 40" xmlns="http://www.w3.org/2000/svg"><g fill="#4f46e5"><rect x="4"  y="18" width="8" height="20" rx="2"/><rect x="18" y="10" width="8" height="28" rx="2"/><rect x="32" y="22" width="8" height="16" rx="2"/><rect x="46" y="6"  width="8" height="32" rx="2"/><rect x="60" y="14" width="8" height="24" rx="2"/><rect x="74" y="20" width="8" height="18" rx="2"/><rect x="88" y="12" width="8" height="26" rx="2"/></g></svg>`;
        case 'donut':
          return `<svg viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg"><circle cx="20" cy="20" r="14" fill="none" stroke="#e9ecf3" stroke-width="6"/><circle cx="20" cy="20" r="14" fill="none" stroke="#4f46e5" stroke-width="6" stroke-dasharray="55 100" transform="rotate(-90 20 20)"/><circle cx="20" cy="20" r="14" fill="none" stroke="#22d3ee" stroke-width="6" stroke-dasharray="25 100" stroke-dashoffset="-55" transform="rotate(-90 20 20)"/></svg>`;
        case 'mixed':
          return `<svg viewBox="0 0 100 40" xmlns="http://www.w3.org/2000/svg"><g fill="#c7d2fe"><rect x="4"  y="20" width="8" height="18" rx="2"/><rect x="18" y="14" width="8" height="24" rx="2"/><rect x="32" y="24" width="8" height="14" rx="2"/><rect x="46" y="10" width="8" height="28" rx="2"/><rect x="60" y="18" width="8" height="20" rx="2"/><rect x="74" y="22" width="8" height="16" rx="2"/><rect x="88" y="16" width="8" height="22" rx="2"/></g><polyline fill="none" stroke="#4f46e5" stroke-width="2" points="8,30 22,20 36,26 50,12 64,20 78,16 92,22"/></svg>`;
        case 'heat':
          return `<svg viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg"><g><rect x="1"  y="1"  width="11" height="11" fill="#4f46e5"/><rect x="14" y="1"  width="11" height="11" fill="#818cf8"/><rect x="27" y="1"  width="11" height="11" fill="#a5b4fc"/><rect x="40" y="1"  width="11" height="11" fill="#c7d2fe"/><rect x="1"  y="14" width="11" height="11" fill="#4338ca"/><rect x="14" y="14" width="11" height="11" fill="#4f46e5"/><rect x="27" y="14" width="11" height="11" fill="#818cf8"/><rect x="40" y="14" width="11" height="11" fill="#a5b4fc"/><rect x="1"  y="27" width="11" height="11" fill="#312e81"/><rect x="14" y="27" width="11" height="11" fill="#4338ca"/><rect x="27" y="27" width="11" height="11" fill="#4f46e5"/><rect x="40" y="27" width="11" height="11" fill="#818cf8"/></g></svg>`;
        case 'funnel':
          return `<svg viewBox="0 0 100 40" xmlns="http://www.w3.org/2000/svg"><g fill="#4f46e5"><rect x="6"  y="2"  width="88" height="6" rx="2"/><rect x="14" y="10" width="72" height="6" rx="2"/><rect x="22" y="18" width="56" height="6" rx="2"/><rect x="30" y="26" width="40" height="6" rx="2"/><rect x="38" y="34" width="24" height="4" rx="2"/></g></svg>`;
        default: return '';
      }
    };

    let state = { cat: 'all', view: 'grid', q: '' };

    const catCounts = () => {
      const c = { all: REPORTS.length, my: 0, shared: 0, templates: 0, scheduled: 0 };
      REPORTS.forEach(r => c[r.cat] = (c[r.cat] || 0) + 1);
      return c;
    };

    const filtered = () => {
      const q = state.q.trim().toLowerCase();
      return REPORTS.filter(r => (state.cat === 'all' || r.cat === state.cat)
        && (!q || r.title.toLowerCase().includes(q) || r.desc.toLowerCase().includes(q)));
    };

    const renderGrid = () => {
      const list = filtered();
      const holder = root.querySelector('[data-anlist-hook="grid"]');
      const tableHolder = root.querySelector('[data-anlist-hook="table"]');
      holder.classList.toggle('d-none', state.view !== 'grid');
      tableHolder.classList.toggle('d-none', state.view !== 'table');

      if (!list.length) {
        holder.innerHTML = `<div class="analytics-list-empty"><i class="bi bi-file-earmark-x analytics-list-empty__icon"></i><p class="mb-0">No reports match your search.</p></div>`;
        tableHolder.innerHTML = holder.innerHTML;
        return;
      }

      if (state.view === 'grid') {
        holder.innerHTML = list.map(r => `
          <div class="rep-card" data-rep-id="${r.id}">
            <div class="rep-card__thumb">
              <span class="rep-card__type"><i class="bi ${typeMeta[r.type].icon}"></i>${typeMeta[r.type].label}</span>
              ${thumbSvg(r.type)}
            </div>
            <div class="rep-card__body">
              <p class="rep-card__title">${r.title}</p>
              <p class="rep-card__desc">${r.desc}</p>
              <div class="rep-card__meta">
                <span class="rep-card__avatar">${r.ownerInit}</span>
                <span>${r.owner}</span>
                <span class="ms-auto">${r.last === '—' ? 'Never run' : 'Ran ' + r.last}</span>
              </div>
              <div class="rep-card__actions">
                <button type="button" class="btn btn-primary btn-sm" data-rep-run="${r.id}"><i class="bi bi-play-fill"></i> Run</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-rep-edit="${r.id}"><i class="bi bi-pencil"></i> Edit</button>
              </div>
            </div>
          </div>`).join('');
      } else {
        tableHolder.innerHTML = `
          <div class="table-responsive">
            <table class="analytics-list-table">
              <thead><tr><th>Name</th><th>Type</th><th>Owner</th><th>Last run</th><th>Next scheduled</th><th class="text-end">Actions</th></tr></thead>
              <tbody>
                ${list.map(r => `
                  <tr>
                    <td><strong>${r.title}</strong><div class="text-body-secondary small">${r.desc}</div></td>
                    <td><span class="rep-typechip"><i class="bi ${typeMeta[r.type].icon}"></i>${typeMeta[r.type].label}</span></td>
                    <td><span class="rep-card__avatar me-1">${r.ownerInit}</span>${r.owner}</td>
                    <td>${r.last}</td>
                    <td>${r.next}</td>
                    <td class="text-end">
                      <button type="button" class="btn btn-sm btn-primary me-1" data-rep-run="${r.id}"><i class="bi bi-play-fill"></i></button>
                      <button type="button" class="btn btn-sm btn-outline-secondary" data-rep-edit="${r.id}"><i class="bi bi-pencil"></i></button>
                    </td>
                  </tr>`).join('')}
              </tbody>
            </table>
          </div>`;
      }
    };

    const renderCats = () => {
      const counts = catCounts();
      root.querySelectorAll('[data-rep-cat]').forEach(btn => {
        const k = btn.dataset.repCat;
        btn.classList.toggle('is-active', k === state.cat);
        const cnt = btn.querySelector('.rep-side__count');
        if (cnt) cnt.textContent = counts[k] || 0;
      });
    };

    // Initial skeleton then render
    const holder = root.querySelector('[data-anlist-hook="grid"]');
    holder.innerHTML = Array.from({ length: 6 }).map(() =>
      `<div class="rep-card"><div class="analytics-list-skeleton analytics-list-skeleton--thumb"></div><div class="rep-card__body"><div class="analytics-list-skeleton analytics-list-skeleton--line-lg"></div><div class="analytics-list-skeleton analytics-list-skeleton--line-md"></div><div class="analytics-list-skeleton analytics-list-skeleton--line-sm"></div></div></div>`).join('');
    setTimeout(() => { renderCats(); renderGrid(); }, 400);

    // Category clicks
    root.addEventListener('click', (e) => {
      const catBtn = e.target.closest('[data-rep-cat]');
      if (catBtn) { state.cat = catBtn.dataset.repCat; renderCats(); renderGrid(); return; }

      const vtoggle = e.target.closest('[data-rep-view]');
      if (vtoggle) {
        state.view = vtoggle.dataset.repView;
        root.querySelectorAll('[data-rep-view]').forEach(b => b.classList.toggle('active', b.dataset.repView === state.view));
        renderGrid();
        return;
      }

      const runBtn = e.target.closest('[data-rep-run]');
      if (runBtn) {
        const id = parseInt(runBtn.dataset.repRun, 10);
        const rep = REPORTS.find(r => r.id === id);
        openRunModal(rep);
        return;
      }
      const editBtn = e.target.closest('[data-rep-edit]');
      if (editBtn) {
        const id = parseInt(editBtn.dataset.repEdit, 10);
        const rep = REPORTS.find(r => r.id === id);
        orchidToast(`Editing report: <strong>${rep.title}</strong>`, 'info');
        return;
      }
    });

    // Search
    const search = root.querySelector('[data-rep-search]');
    if (search) search.addEventListener('input', () => { state.q = search.value; renderGrid(); });

    /* --- Report result modal --- */
    const openRunModal = (rep) => {
      const modalEl = document.getElementById('repRunModal');
      const bodyEl = modalEl.querySelector('.modal-body');
      const titleEl = modalEl.querySelector('.modal-title');
      titleEl.textContent = rep.title;
      bodyEl.innerHTML = `<div class="rep-runmodal__loader"><div class="rep-runmodal__spinner"></div><p class="mb-0">Executing query…</p></div>`;
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();
      setTimeout(() => {
        bodyEl.innerHTML = `
          <div class="row g-3">
            <div class="col-lg-8">
              <div class="analytics-list-card">
                <div class="analytics-list-card__head"><h6 class="analytics-list-card__title">${rep.title}</h6><small class="text-body-secondary">${fmtDate(new Date())}</small></div>
                <div class="analytics-list-card__body"><div class="analytics-list-chart"><canvas id="repResultChart"></canvas></div></div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="analytics-list-card">
                <div class="analytics-list-card__head"><h6 class="analytics-list-card__title">Summary</h6></div>
                <div class="analytics-list-card__body">
                  <table class="analytics-list-table">
                    <thead><tr><th>Metric</th><th class="text-end">Value</th></tr></thead>
                    <tbody>
                      <tr><td>Rows</td><td class="text-end fw-semibold">1,284</td></tr>
                      <tr><td>Sum</td><td class="text-end fw-semibold">${fmtMoney(482000, { compact: true })}</td></tr>
                      <tr><td>Avg</td><td class="text-end fw-semibold">${fmtMoney(376)}</td></tr>
                      <tr><td>Max</td><td class="text-end fw-semibold">${fmtMoney(24800)}</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>`;
        const ctx = document.getElementById('repResultChart').getContext('2d');
        new Chart(ctx, {
          type: rep.type === 'bar' ? 'bar' : 'line',
          data: {
            labels: REVENUE_MONTHS,
            datasets: [{
              label: rep.title,
              data: [42, 55, 61, 68, 74, 82, 78, 85, 92, 98, 105, 112],
              borderColor: '#4f46e5',
              backgroundColor: rep.type === 'bar' ? 'rgba(79,70,229,.8)' : 'rgba(79,70,229,.15)',
              tension: .35, fill: true, borderWidth: 2
            }]
          },
          options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
      }, 900);
    };

    /* --- Wizard --- */
    const wizModal = document.getElementById('repWizardModal');
    if (wizModal) {
      let step = 0;
      const stepsEls = wizModal.querySelectorAll('.rep-wizard__step');
      const panels = wizModal.querySelectorAll('[data-rep-panel]');
      const renderStep = () => {
        stepsEls.forEach((el, i) => {
          el.classList.toggle('is-active', i === step);
          el.classList.toggle('is-done', i < step);
        });
        panels.forEach((el, i) => el.classList.toggle('d-none', i !== step));
        wizModal.querySelector('[data-rep-prev]').disabled = step === 0;
        const nextBtn = wizModal.querySelector('[data-rep-next]');
        nextBtn.textContent = step === stepsEls.length - 1 ? 'Create Report' : 'Next';
      };
      wizModal.addEventListener('click', (e) => {
        if (e.target.matches('[data-rep-next]')) {
          if (step === stepsEls.length - 1) {
            const modal = bootstrap.Modal.getInstance(wizModal);
            modal.hide();
            orchidToast('New report saved to <strong>My Reports</strong>', 'success');
            step = 0; renderStep();
          } else { step++; renderStep(); }
        }
        if (e.target.matches('[data-rep-prev]')) { step = Math.max(0, step - 1); renderStep(); }
      });
      wizModal.addEventListener('shown.bs.modal', () => { step = 0; renderStep(); });
      renderStep();
    }
  };

  /* =====================================================
     SALES ANALYTICS PAGE
     ===================================================== */
  const initSalesAnalytics = () => {
    const root = document.querySelector('[data-anlist-page="sales"]');
    if (!root) return;

    const charts = {};

    // Sparklines
    ['spark1','spark2','spark3','spark4'].forEach((id, i) => {
      const c = document.getElementById(id);
      const series = [
        [8,9,10,9,11,12,13,14,15,16,17,18],
        [4,5,6,5,7,8,9,8,10,11,12,13],
        [12,11,13,14,13,15,16,15,17,18,17,19],
        [22,21,20,22,20,19,21,20,18,19,17,16]
      ][i];
      renderSparkline(c, series, ['#4f46e5','#10b981','#f59e0b','#ef4444'][i]);
    });

    // Sales Trend (mixed bar + line)
    charts.trend = new Chart(document.getElementById('sanlTrend'), {
      type: 'bar',
      data: {
        labels: REVENUE_MONTHS,
        datasets: [
          { label: 'Bookings', data: [180,220,205,240,265,258,290,312,298,335,360,382], backgroundColor: 'rgba(79,70,229,.75)', borderRadius: 6, order: 2 },
          { type: 'line', label: 'Deals Won', data: [22,26,25,29,31,29,34,37,35,40,42,44], borderColor: '#22d3ee', backgroundColor: '#22d3ee', tension: .35, borderWidth: 2, pointRadius: 3, yAxisID: 'y1', order: 1 }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        scales: {
          y:  { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { callback: v => '$' + v + 'K' } },
          y1: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { color: '#22d3ee' } }
        }
      }
    });

    // Sales by product donut
    charts.product = new Chart(document.getElementById('sanlProduct'), {
      type: 'doughnut',
      data: {
        labels: PRODUCTS,
        datasets: [{ data: [285,220,180,120,95], backgroundColor: ['#4f46e5','#22d3ee','#10b981','#f59e0b','#8b5cf6'], borderWidth: 0 }]
      },
      options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'right' } } }
    });

    // Win/Loss stacked
    charts.winloss = new Chart(document.getElementById('sanlWinLoss'), {
      type: 'bar',
      data: {
        labels: REVENUE_MONTHS,
        datasets: [
          { label: 'Won',  data: [18,22,20,25,28,26,30,33,32,36,38,42], backgroundColor: '#10b981' },
          { label: 'Lost', data: [8,9,10,7,9,11,8,10,9,11,10,9],       backgroundColor: 'rgba(239,68,68,.65)' }
        ]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } },
        scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, grid: { color: 'rgba(0,0,0,.05)' } } } }
    });

    // Rep leaderboard
    const repHolder = root.querySelector('[data-anlist-hook="reps"]');
    const maxSales = Math.max(...REPS.map(r => r.sales));
    repHolder.innerHTML = REPS.map(r => `
      <div class="sanl-rep">
        <span class="sanl-rep__avatar" style="background:${r.color}">${r.init}</span>
        <div class="sanl-rep__meta">
          <p class="sanl-rep__name">${r.name}</p>
          <div class="sanl-rep__bar"><div class="sanl-rep__fill" data-w="${Math.round(r.sales/maxSales*100)}"></div></div>
        </div>
        <span class="sanl-rep__val">${fmtMoney(r.sales, { compact: true })}</span>
      </div>`).join('');
    requestAnimationFrame(() => {
      repHolder.querySelectorAll('.sanl-rep__fill').forEach(el => el.style.width = el.dataset.w + '%');
    });

    // Cycle-time funnel
    const funnelHolder = root.querySelector('[data-anlist-hook="cycle"]');
    const stages = [
      { name: 'Lead', days: 4, pct: 22 },
      { name: 'Qualified', days: 9, pct: 40 },
      { name: 'Proposal', days: 14, pct: 62 },
      { name: 'Negotiation', days: 21, pct: 82 },
      { name: 'Close', days: 32, pct: 100 }
    ];
    funnelHolder.innerHTML = stages.map(s => `
      <div class="sanl-funnel__row">
        <span class="sanl-funnel__label">${s.name}</span>
        <div class="sanl-funnel__bar"><div class="sanl-funnel__fill" data-w="${s.pct}">${s.days} days</div></div>
        <span class="sanl-funnel__days">${s.pct}%</span>
      </div>`).join('');
    requestAnimationFrame(() => {
      funnelHolder.querySelectorAll('.sanl-funnel__fill').forEach(el => el.style.width = el.dataset.w + '%');
    });

    // Top deals table
    const dealNames = ['Nova Systems - Enterprise Renewal','Peak Labs - Expansion','Aurora Health - New','Vela Retail - Multi-year','Kite Manufacturing - Add-on','Solstice Bank - Upgrade','Loom Software - New','Terra Insurance - Renewal','Halo Media - New','Ember Studios - Upgrade','Cobalt Freight - Expansion','Zephyr Studios - New','Marlin Analytics - Renewal','Onyx Consulting - New','Ridge Wear - Expansion','Orchid Trust - New','Vector Cloud - Renewal','Meridian Tools - Add-on','Aster Health - New','Delta Labs - Renewal'];
    const dealsTbody = root.querySelector('[data-anlist-hook="deals"] tbody');
    dealsTbody.innerHTML = dealNames.map((n, i) => {
      const amt = 24000 + Math.round(Math.random() * 76000);
      const rep = REPS[i % REPS.length];
      const stage = ['Won','Won','Won','Negotiation','Proposal'][i % 5];
      const cls = stage === 'Won' ? 'active' : stage === 'Negotiation' ? 'draft' : 'scheduled';
      return `<tr>
        <td><strong>${n}</strong></td>
        <td>${fmtMoney(amt)}</td>
        <td><span class="sanl-rep__avatar" style="background:${rep.color};width:22px;height:22px;font-size:.65rem">${rep.init}</span> ${rep.name}</td>
        <td><span class="analytics-list-status analytics-list-status--${cls}">${stage}</span></td>
        <td class="text-body-secondary">2026-07-${(i%28)+1}</td>
      </tr>`;
    }).join('');

    // Filter changes toast
    root.querySelectorAll('[data-anlist-filter]').forEach(el => el.addEventListener('change', () => {
      orchidToast('Filters applied — charts refreshed', 'info');
      // Slightly randomize datasets to visualize update
      Object.values(charts).forEach(c => {
        if (!c) return;
        c.data.datasets.forEach(ds => ds.data = ds.data.map(v => Math.max(1, Math.round(v * (0.9 + Math.random() * 0.25)))));
        c.update();
      });
    }));
  };

  /* =====================================================
     CUSTOMER ANALYTICS PAGE
     ===================================================== */
  const initCustomerAnalytics = () => {
    const root = document.querySelector('[data-anlist-page="customer"]');
    if (!root) return;

    // KPI sparklines
    ['cspark1','cspark2','cspark3','cspark4'].forEach((id, i) => {
      const c = document.getElementById(id);
      const series = [
        [220,235,240,258,270,282,290,302,312,325,340,352],
        [12,15,14,18,16,20,22,19,24,25,27,29],
        [8,10,9,11,10,12,11,13,14,12,13,15],
        [102,104,103,105,106,107,109,110,111,112,113,115]
      ][i];
      renderSparkline(c, series, ['#4f46e5','#10b981','#ef4444','#22d3ee'][i]);
    });

    // Segments matrix (positioning dots by freq × spend)
    const matrix = root.querySelector('[data-anlist-hook="matrix"]');
    const dots = CUSTOMERS.map(c => {
      const cls = c.segment === 'Champion' ? '' : c.segment === 'Loyal' ? 'dot-loyal' : c.segment === 'At Risk' ? 'dot-atrisk' : 'dot-lost';
      return `<span class="canl-matrix__dot ${cls}" data-tip="${c.name}" data-x="${c.freq * 100}" data-y="${(1 - c.spend) * 100}" title="${c.name} · LTV ${fmtMoney(c.ltv,{compact:true})}"></span>`;
    }).join('');
    matrix.insertAdjacentHTML('beforeend', dots);
    requestAnimationFrame(() => {
      matrix.querySelectorAll('.canl-matrix__dot').forEach(d => {
        d.style.left = d.dataset.x + '%';
        d.style.bottom = (100 - parseFloat(d.dataset.y)) + '%';
        d.style.transform = 'translate(-50%, 50%)';
      });
    });

    // LTV histogram
    const buckets = [0,0,0,0,0,0,0,0];
    CUSTOMERS.forEach(c => {
      const b = Math.min(7, Math.floor(c.ltv / 20000));
      buckets[b]++;
    });
    new Chart(document.getElementById('canlLtv'), {
      type: 'bar',
      data: {
        labels: ['0-20K','20-40K','40-60K','60-80K','80-100K','100-120K','120-140K','140K+'],
        datasets: [{ label: 'Customers', data: buckets, backgroundColor: 'rgba(79,70,229,.75)', borderRadius: 6 }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' } }, x: { grid: { display: false } } } }
    });

    // Cohort table (retention heatmap)
    const cohortMonths = ['2026-Jan','2026-Feb','2026-Mar','2026-Apr','2026-May','2026-Jun','2026-Jul'];
    const cohortBody = root.querySelector('[data-anlist-hook="cohort"] tbody');
    cohortBody.innerHTML = cohortMonths.map((m, r) => {
      const cells = [100, 92, 84, 78, 72, 68, 64].slice(0, cohortMonths.length - r);
      return `<tr><th scope="row">${m}</th>` + cells.map(v => {
        const alpha = (v / 100) * 0.9;
        return `<td class="canl-cohort__cell" data-r="${v}" data-alpha="${alpha.toFixed(2)}">${v}%</td>`;
      }).join('') + `<td colspan="${r}" class="canl-cohort__cell" data-r="0" data-alpha="0"></td></tr>`;
    }).join('');
    cohortBody.querySelectorAll('.canl-cohort__cell').forEach(td => {
      const a = parseFloat(td.dataset.alpha) || 0;
      if (a > 0) td.style.background = `rgba(79,70,229,${a})`;
      if (a > 0.55) td.style.color = '#fff';
    });

    // Customer table
    const tbody = root.querySelector('[data-anlist-hook="customers"] tbody');
    tbody.innerHTML = CUSTOMERS.map(c => {
      const hc = c.health >= 75 ? 'good' : c.health >= 50 ? 'ok' : 'bad';
      return `<tr>
        <td><strong>${c.name}</strong></td>
        <td>${c.industry}</td>
        <td class="fw-semibold">${fmtMoney(c.ltv, { compact: true })}</td>
        <td>${fmtMoney(c.mrr)}</td>
        <td>${c.tenureM} mo</td>
        <td><span class="canl-health canl-health--${hc}"><span class="canl-health__dot"></span>${c.health}</span></td>
        <td class="text-body-secondary">${c.lastInt}</td>
      </tr>`;
    }).join('');

    // Filter matrix clicks
    root.querySelectorAll('.canl-matrix__quadrant').forEach(q => q.addEventListener('click', () => {
      root.querySelectorAll('.canl-matrix__quadrant').forEach(x => x.classList.remove('is-active'));
      q.classList.add('is-active');
      const seg = q.dataset.seg;
      orchidToast(`Filtered by segment: <strong>${seg}</strong>`, 'info');
      const rows = tbody.querySelectorAll('tr');
      CUSTOMERS.forEach((c, i) => {
        rows[i].classList.toggle('d-none', c.segment !== seg);
      });
    }));

    // Reset filters
    const resetBtn = root.querySelector('[data-anlist-reset]');
    if (resetBtn) resetBtn.addEventListener('click', () => {
      root.querySelectorAll('.canl-matrix__quadrant').forEach(x => x.classList.remove('is-active'));
      tbody.querySelectorAll('tr').forEach(r => r.classList.remove('d-none'));
      orchidToast('Filters reset', 'success');
    });

    root.querySelectorAll('[data-anlist-filter]').forEach(el => el.addEventListener('change', () => orchidToast('Cohort filter updated', 'info')));
  };

  /* =====================================================
     REVENUE ANALYTICS
     ===================================================== */
  const initRevenueAnalytics = () => {
    const root = document.querySelector('[data-anlist-page="revenue"]');
    if (!root) return;
    const charts = {};

    // KPI sparklines
    renderSparkline(document.getElementById('rspark1'), MRR_HISTORY, '#fff');
    renderSparkline(document.getElementById('rspark2'), MRR_HISTORY.map(v => v * 12), '#10b981');
    renderSparkline(document.getElementById('rspark3'), [22,24,26,28,32,30,34,36,38,42,44,46], '#22d3ee');
    renderSparkline(document.getElementById('rspark4'), [8,10,9,12,10,14,11,13,10,12,11,14], '#ef4444');

    // MRR waterfall
    const mm = MRR_MOVEMENT;
    charts.waterfall = new Chart(document.getElementById('ranlWaterfall'), {
      type: 'bar',
      data: {
        labels: ['Start MRR','New','Expansion','Contraction','Churn','End MRR'],
        datasets: [{
          data: [mm.start, mm.new, mm.expansion, mm.contraction, mm.churn, mm.end],
          backgroundColor: ['#6b7385','#10b981','#22d3ee','#f59e0b','#ef4444','#4f46e5'],
          borderRadius: 6
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: (c) => '$' + c.parsed.y + 'K' } } },
        scales: { y: { beginAtZero: false, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { callback: v => '$' + v + 'K' } }, x: { grid: { display: false } } }
      }
    });

    // Revenue by plan (stacked area)
    charts.plans = new Chart(document.getElementById('ranlPlans'), {
      type: 'line',
      data: {
        labels: REVENUE_MONTHS,
        datasets: [
          { label: 'Starter',    data: [80,84,88,92,96,102,108,114,118,124,128,134], borderColor: '#22d3ee', backgroundColor: 'rgba(34,211,238,.35)', fill: true, tension: .35 },
          { label: 'Business',   data: [220,232,244,258,272,286,302,318,332,348,362,378], borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,.35)', fill: true, tension: .35 },
          { label: 'Enterprise', data: [312,318,326,331,334,340,338,330,331,326,322,318], borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.35)', fill: true, tension: .35 }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { stacked: true, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { callback: v => '$' + v + 'K' } }, x: { grid: { display: false } } }
      }
    });

    // Cohort MRR retention
    const cohortMonths = ['2026-Jan','2026-Feb','2026-Mar','2026-Apr','2026-May','2026-Jun','2026-Jul'];
    const cbody = root.querySelector('[data-anlist-hook="cohort"] tbody');
    cbody.innerHTML = cohortMonths.map((m, r) => {
      const cells = [100, 104, 108, 112, 118, 122, 128].slice(0, cohortMonths.length - r);
      return `<tr><th scope="row">${m}</th>` + cells.map(v => {
        const alpha = Math.min(1, (v - 90) / 40);
        return `<td class="canl-cohort__cell" data-r="${v}" data-alpha="${alpha.toFixed(2)}">${v}%</td>`;
      }).join('') + `</tr>`;
    }).join('');
    cbody.querySelectorAll('.canl-cohort__cell').forEach(td => {
      const a = parseFloat(td.dataset.alpha) || 0;
      td.style.background = `rgba(16,185,129,${Math.max(0.12, a)})`;
      if (a > 0.55) td.style.color = '#fff';
    });

    // Top expansions
    const expList = root.querySelector('[data-anlist-hook="expansions"]');
    const expansions = CUSTOMERS.filter(c => c.segment === 'Champion' || c.segment === 'Loyal').slice(0, 6);
    expList.innerHTML = expansions.map(c => `
      <div class="ranl-lists__item">
        <span class="ranl-lists__logo">${c.name.split(' ').map(w => w[0]).slice(0,2).join('')}</span>
        <div><p class="ranl-lists__name">${c.name}</p><span class="ranl-lists__meta">${c.industry} · MRR ${fmtMoney(c.mrr)}</span></div>
        <span class="ranl-lists__delta ranl-lists__delta--up">+${Math.round(c.spend * 40 + 10)}%</span>
      </div>`).join('');

    // Top churns
    const churnList = root.querySelector('[data-anlist-hook="churns"]');
    const churns = CUSTOMERS.filter(c => c.segment === 'At Risk' || c.segment === 'Lost').slice(0, 6);
    churnList.innerHTML = churns.map(c => `
      <div class="ranl-lists__item">
        <span class="ranl-lists__logo" style="background:#ef4444">${c.name.split(' ').map(w => w[0]).slice(0,2).join('')}</span>
        <div><p class="ranl-lists__name">${c.name}</p><span class="ranl-lists__meta">${c.industry} · was ${fmtMoney(c.mrr)}/mo</span></div>
        <span class="ranl-lists__delta ranl-lists__delta--down">−${fmtMoney(c.mrr, { compact: true })}</span>
      </div>`).join('');

    // ARR by industry donut
    const inds = {};
    CUSTOMERS.forEach(c => inds[c.industry] = (inds[c.industry] || 0) + c.mrr * 12);
    charts.industry = new Chart(document.getElementById('ranlIndustry'), {
      type: 'doughnut',
      data: {
        labels: Object.keys(inds),
        datasets: [{ data: Object.values(inds), backgroundColor: ['#4f46e5','#22d3ee','#10b981','#f59e0b','#8b5cf6','#ef4444','#ec4899','#6b7385'], borderWidth: 0 }]
      },
      options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'right' } } }
    });

    root.querySelectorAll('[data-anlist-filter]').forEach(el => el.addEventListener('change', () => {
      orchidToast('Revenue filters updated', 'info');
      Object.values(charts).forEach(c => {
        if (!c) return;
        c.data.datasets.forEach(ds => ds.data = ds.data.map(v => Math.max(1, Math.round(v * (0.92 + Math.random() * 0.18)))));
        c.update();
      });
    }));
  };

  /* =====================================================
     MARKETING ANALYTICS
     ===================================================== */
  const initMarketingAnalytics = () => {
    const root = document.querySelector('[data-anlist-page="marketing"]');
    if (!root) return;

    // KPIs sparklines
    renderSparkline(document.getElementById('mspark1'), [12,14,15,16,18,17,19,20,22,21,23,25].map(x=>x*100), '#4f46e5');
    renderSparkline(document.getElementById('mspark2'), [8,9,10,12,11,13,14,15,16,17,18,20], '#10b981');
    renderSparkline(document.getElementById('mspark3'), [4,5,6,5,7,8,9,8,10,11,12,13], '#f59e0b');
    renderSparkline(document.getElementById('mspark4'), [220,240,255,268,282,295,312,326,340,352,365,378], '#22d3ee');

    // Channel performance horizontal bar
    const channels = {};
    CAMPAIGNS.forEach(c => channels[c.channel] = (channels[c.channel] || 0) + c.conv);
    const chanLabels = Object.keys(channels).map(k => k[0].toUpperCase() + k.slice(1));
    const chanChart = new Chart(document.getElementById('manlChannel'), {
      type: 'bar',
      data: {
        labels: chanLabels,
        datasets: [{ label: 'Conversions', data: Object.values(channels), backgroundColor: ['#2563eb','#0a66c2','#1877f2','#059669','#b45309','#7c3aed','#4b5563'], borderRadius: 6 }]
      },
      options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' } }, y: { grid: { display: false } } }
      }
    });

    // Funnel
    const funnelHolder = root.querySelector('[data-anlist-hook="funnel"]');
    const stages = [
      { name: 'Impressions', val: 2450000 },
      { name: 'Clicks',      val: 128400 },
      { name: 'Signups',     val: 18200 },
      { name: 'Trials',      val: 6420 },
      { name: 'Paid',        val: 2148 }
    ];
    let funnelHtml = '';
    stages.forEach((s, i) => {
      const w = 100 - i * 15;
      funnelHtml += `<div class="manl-funnel__stage" data-w="${w}">
        <span>${s.name}</span><strong>${s.val.toLocaleString()}</strong>
      </div>`;
      if (i < stages.length - 1) {
        const conv = ((stages[i + 1].val / s.val) * 100).toFixed(1);
        funnelHtml += `<span class="manl-funnel__conv">↓ ${conv}%</span>`;
      }
    });
    funnelHolder.innerHTML = `<div class="manl-funnel">${funnelHtml}</div>`;
    requestAnimationFrame(() => {
      funnelHolder.querySelectorAll('.manl-funnel__stage').forEach(el => el.style.width = el.dataset.w + '%');
    });

    // Campaigns table
    const campBody = root.querySelector('[data-anlist-hook="campaigns"] tbody');
    campBody.innerHTML = CAMPAIGNS.map(c => `
      <tr>
        <td><strong>${c.name}</strong></td>
        <td><span class="manl-channel-chip chip-${c.channel}">${c.channel[0].toUpperCase() + c.channel.slice(1)}</span></td>
        <td>${fmtMoney(c.budget)}</td>
        <td>${fmtMoney(c.spent)}</td>
        <td>${fmtMoney(c.cpa)}</td>
        <td>${c.conv.toLocaleString()}</td>
        <td><strong>${c.roas.toFixed(1)}×</strong></td>
        <td><span class="analytics-list-status analytics-list-status--${c.status}">${c.status[0].toUpperCase() + c.status.slice(1)}</span></td>
      </tr>`).join('');

    // Attribution donut
    const models = {
      first: { google: 32, linkedin: 18, meta: 22, email: 10, organic: 12, referral: 4, direct: 2 },
      last:  { google: 24, linkedin: 20, meta: 18, email: 16, organic: 10, referral: 8, direct: 4 },
      linear:{ google: 26, linkedin: 19, meta: 20, email: 13, organic: 11, referral: 6, direct: 5 },
      ushape:{ google: 28, linkedin: 20, meta: 19, email: 14, organic: 10, referral: 6, direct: 3 }
    };
    let currentModel = 'last';
    const drawAttr = () => {
      const data = models[currentModel];
      if (window._manlAttr) window._manlAttr.destroy();
      window._manlAttr = new Chart(document.getElementById('manlAttr'), {
        type: 'doughnut',
        data: { labels: Object.keys(data).map(k => k[0].toUpperCase() + k.slice(1)),
          datasets: [{ data: Object.values(data), backgroundColor: ['#2563eb','#0a66c2','#1877f2','#b45309','#059669','#7c3aed','#4b5563'], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'right' } } }
      });
    };
    drawAttr();

    // Content list
    const contentList = root.querySelector('[data-anlist-hook="content"]');
    const content = [
      { title: 'The Ultimate Guide to Orchid Analytics',  sessions: 24800, goals: 1240, eng: '4:32' },
      { title: 'How Nova Systems Scaled with Orchid',     sessions: 18600, goals: 998,  eng: '5:18' },
      { title: 'Product Update - Cohorts 2.0',           sessions: 15200, goals: 812,  eng: '3:44' },
      { title: 'Report Templates for SaaS Founders',     sessions: 13400, goals: 704,  eng: '4:12' },
      { title: 'MRR Waterfall Charts Explained',         sessions: 11800, goals: 648,  eng: '3:56' },
      { title: 'Customer Health Scoring Best Practices', sessions: 10500, goals: 590,  eng: '4:22' },
      { title: 'Marketing Attribution Deep Dive',        sessions:  9800, goals: 522,  eng: '4:48' },
      { title: 'Comparing Analytics Vendors 2026',       sessions:  8900, goals: 462,  eng: '5:05' },
      { title: 'Webinar - Retention Playbook',           sessions:  7600, goals: 402,  eng: '6:14' },
      { title: 'Getting Started with the Orchid API',     sessions:  6200, goals: 340,  eng: '3:28' }
    ];
    contentList.innerHTML = content.map((c, i) => `
      <li>
        <span class="manl-content-list__rank">${i + 1}</span>
        <div><p class="manl-content-list__title">${c.title}</p><span class="manl-content-list__meta">${c.sessions.toLocaleString()} sessions · ${c.eng} avg</span></div>
        <span class="manl-content-list__val">${c.goals}</span>
        <span class="manl-content-list__meta">goals</span>
      </li>`).join('');

    // Filter changes
    const attrSelect = root.querySelector('[data-anlist-attr]');
    if (attrSelect) attrSelect.addEventListener('change', () => {
      currentModel = attrSelect.value;
      drawAttr();
      orchidToast(`Attribution model: <strong>${attrSelect.options[attrSelect.selectedIndex].text}</strong>`, 'info');
    });
    root.querySelectorAll('[data-anlist-filter]').forEach(el => el.addEventListener('change', () => {
      orchidToast('Marketing filters applied', 'info');
      chanChart.data.datasets[0].data = chanChart.data.datasets[0].data.map(v => Math.max(10, Math.round(v * (0.9 + Math.random() * 0.25))));
      chanChart.update();
    }));
  };

  /* ---------- Bootstrap on DOM ready ---------- */
  const onReady = () => {
    initReportsPage();
    initSalesAnalytics();
    initCustomerAnalytics();
    initRevenueAnalytics();
    initMarketingAnalytics();
    document.querySelectorAll('[data-orchid-year]').forEach(el => el.textContent = new Date().getFullYear());
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', onReady); else onReady();
})();
