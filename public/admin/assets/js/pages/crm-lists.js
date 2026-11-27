/* =====================================================
   Orchid — CRM Lists (Leads / Contacts / Companies)
   Single script, auto-detects page via body/DOM signals.
   ===================================================== */
(function () {
  'use strict';

  /* ---------- Utils ---------- */
  const $  = (sel, root) => (root || document).querySelector(sel);
  const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));
  const escapeHtml = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const initials = (a, b) => ((a || '')[0] || '').toUpperCase() + ((b || '')[0] || '').toUpperCase();
  const fmtMoney = n => {
    if (n == null) return '—';
    if (n >= 1e9) return '$' + (n/1e9).toFixed(1) + 'B';
    if (n >= 1e6) return '$' + (n/1e6).toFixed(1) + 'M';
    if (n >= 1e3) return '$' + (n/1e3).toFixed(1) + 'k';
    return '$' + n.toLocaleString();
  };
  const fmtMoneyFull = n => n == null ? '—' : '$' + Number(n).toLocaleString();
  const fmtNumber = n => n == null ? '—' : Number(n).toLocaleString();
  const relTime = mins => {
    if (mins < 1) return 'Just now';
    if (mins < 60) return mins + 'm ago';
    if (mins < 60*24) return Math.floor(mins/60) + 'h ago';
    if (mins < 60*24*2) return 'Yesterday';
    if (mins < 60*24*30) return Math.floor(mins/(60*24)) + 'd ago';
    if (mins < 60*24*365) return Math.floor(mins/(60*24*30)) + 'mo ago';
    return Math.floor(mins/(60*24*365)) + 'y ago';
  };
  const debounce = (fn, ms) => { let t; return function(...a){ clearTimeout(t); t = setTimeout(() => fn.apply(this, a), ms); }; };
  const GRADS = ['crm-grad-1','crm-grad-2','crm-grad-3','crm-grad-4','crm-grad-5','crm-grad-6','crm-grad-7','crm-grad-8'];
  const gradFor = seed => GRADS[Math.abs(hash(String(seed))) % GRADS.length];
  function hash(s) { let h = 0; for (let i = 0; i < s.length; i++) { h = (h << 5) - h + s.charCodeAt(i); h |= 0; } return h; }

  /* ---------- Toasts ---------- */
  const toastHost = (() => {
    let n = document.querySelector('.crm-toast-region');
    if (!n) {
      n = document.createElement('div');
      n.className = 'crm-toast-region';
      n.setAttribute('role', 'status');
      n.setAttribute('aria-live', 'polite');
      document.body.appendChild(n);
    }
    return n;
  })();
  const ICON = { success:'bi-check-circle-fill', info:'bi-info-circle-fill', warning:'bi-exclamation-triangle-fill', danger:'bi-x-circle-fill' };
  function toast(msg, kind) {
    kind = kind || 'info';
    const el = document.createElement('div');
    el.className = 'crm-toast crm-toast--' + kind;
    el.innerHTML = '<i class="bi ' + (ICON[kind] || ICON.info) + '"></i><div>' + escapeHtml(msg) + '</div>';
    toastHost.appendChild(el);
    setTimeout(() => { el.style.transition = 'opacity .25s, transform .25s'; el.style.opacity = '0'; el.style.transform = 'translateX(20px)'; }, 3200);
    setTimeout(() => el.remove(), 3500);
  }

  /* ---------- Page detection ---------- */
  const PAGE =
    $('[data-leads-hot]') ? 'leads' :
    $('[data-contacts-grid]') ? 'contacts' :
    $('[data-companies-drawer-hero]') ? 'companies' :
    null;
  if (!PAGE) return;

  /* ---------- Sample data ---------- */
  const COMPANIES = ['Vertex Robotics','Meridian Health','Northwind Analytics','Aurora Labs','Titan Media','Cirrus Cloud','Ember Retail','Kestrel Energy','Nova Financial','Pelagic Ventures'];

  const LEADS_SEED = [
    { first:'Emma',    last:'Watson',    email:'emma@vertexrobotics.com',  company:'Vertex Robotics',     source:'website',  status:'qualified',   score:88, owner:'Alex Kim',    lastMin:30,    value:24000 },
    { first:'James',   last:'Doe',       email:'james@meridianhealth.com', company:'Meridian Health',     source:'referral', status:'new',         score:62, owner:'Priya Menon', lastMin:120,   value:14500 },
    { first:'Sofia',   last:'García',    email:'sofia@northwind.io',       company:'Northwind Analytics', source:'linkedin', status:'contacted',   score:74, owner:'Marcus Chen', lastMin:5,     value:31200 },
    { first:'Yuki',    last:'Tanaka',    email:'yuki@auroralabs.jp',       company:'Aurora Labs',         source:'event',    status:'qualified',   score:91, owner:'Alex Kim',    lastMin:60,    value:52000 },
    { first:'Ryan',    last:'Green',     email:'ryan@titanmedia.tv',       company:'Titan Media',         source:'website',  status:'new',         score:44, owner:'Sofia García',lastMin:1440,  value:6800 },
    { first:'Ava',     last:'Lee',       email:'ava@cirrus.cloud',         company:'Cirrus Cloud',        source:'linkedin', status:'contacted',   score:71, owner:'James Doe',   lastMin:180,   value:18400 },
    { first:'Diego',   last:'Ramírez',   email:'diego@emberretail.mx',     company:'Ember Retail',        source:'email',    status:'unqualified', score:22, owner:'Marcus Chen', lastMin:4320,  value:3200 },
    { first:'Fatima',  last:'Al-Rashid', email:'fatima@kestrel.energy',    company:'Kestrel Energy',      source:'referral', status:'qualified',   score:85, owner:'Alex Kim',    lastMin:15,    value:41000 },
    { first:'Noah',    last:'Park',      email:'noah@novafinancial.kr',    company:'Nova Financial',      source:'website',  status:'contacted',   score:66, owner:'Priya Menon', lastMin:240,   value:12200 },
    { first:'Isabella',last:'Rossi',     email:'isabella@pelagic.vc',      company:'Pelagic Ventures',    source:'event',    status:'qualified',   score:82, owner:'Sofia García',lastMin:75,    value:28500 },
    { first:'Liam',    last:"O'Brien",   email:'liam@vertexrobotics.com',  company:'Vertex Robotics',     source:'referral', status:'new',         score:58, owner:'Marcus Chen', lastMin:2880,  value:9800 },
    { first:'Zara',    last:'Hussain',   email:'zara@meridianhealth.com',  company:'Meridian Health',     source:'linkedin', status:'contacted',   score:79, owner:'James Doe',   lastMin:45,    value:22000 },
    { first:'Ken',     last:'Yamamoto',  email:'ken@northwind.io',         company:'Northwind Analytics', source:'website',  status:'qualified',   score:83, owner:'Alex Kim',    lastMin:90,    value:34500 },
    { first:'Diana',   last:'Petrova',   email:'diana@auroralabs.jp',      company:'Aurora Labs',         source:'email',    status:'unqualified', score:31, owner:'Priya Menon', lastMin:7200,  value:4500 },
    { first:'Mateo',   last:'Silva',     email:'mateo@titanmedia.tv',      company:'Titan Media',         source:'website',  status:'contacted',   score:68, owner:'Marcus Chen', lastMin:360,   value:16800 },
    { first:'Chloe',   last:'Baker',     email:'chloe@cirrus.cloud',       company:'Cirrus Cloud',        source:'linkedin', status:'qualified',   score:90, owner:'Sofia García',lastMin:20,    value:47000 },
    { first:'Omar',    last:'Farah',     email:'omar@emberretail.mx',      company:'Ember Retail',        source:'referral', status:'new',         score:52, owner:'James Doe',   lastMin:1200,  value:7900 },
    { first:'Hannah',  last:'Nguyen',    email:'hannah@kestrel.energy',    company:'Kestrel Energy',      source:'event',    status:'contacted',   score:76, owner:'Alex Kim',    lastMin:150,   value:20500 },
    { first:'Ethan',   last:'Wright',    email:'ethan@novafinancial.kr',   company:'Nova Financial',      source:'website',  status:'qualified',   score:87, owner:'Priya Menon', lastMin:25,    value:38000 },
    { first:'Layla',   last:'Ahmed',     email:'layla@pelagic.vc',         company:'Pelagic Ventures',    source:'email',    status:'unqualified', score:28, owner:'Marcus Chen', lastMin:8640,  value:2800 }
  ];

  const CONTACTS_SEED = [
    { first:'Emma',    last:'Watson',    title:'Product Manager',   company:'Vertex Robotics',     email:'emma.watson@vertex.io',      phone:'+44 20 7946 0958',  tags:['vip','decision'],  lastMin:60 },
    { first:'James',   last:'Doe',       title:'CTO',               company:'Meridian Health',     email:'james.doe@meridian.io',      phone:'+1 212 555 0187',   tags:['decision','champion'], lastMin:120 },
    { first:'Sofia',   last:'García',    title:'Head of Marketing', company:'Northwind Analytics', email:'sofia@northwind.io',         phone:'+34 612 345 678',   tags:['prospect'],        lastMin:1440 },
    { first:'Yuki',    last:'Tanaka',    title:'VP Engineering',    company:'Aurora Labs',         email:'yuki@auroralabs.jp',         phone:'+81 90 1234 5678',  tags:['champion','vip'],  lastMin:45 },
    { first:'Ryan',    last:'Green',     title:'Marketing Lead',    company:'Titan Media',         email:'ryan.green@titan.tv',        phone:'+1 416 555 0173',   tags:['prospect'],        lastMin:2880 },
    { first:'Ava',     last:'Lee',       title:'Cloud Architect',   company:'Cirrus Cloud',        email:'ava.lee@cirrus.cloud',       phone:'+49 30 55501212',   tags:['decision'],        lastMin:180 },
    { first:'Diego',   last:'Ramírez',   title:'Store Ops',         company:'Ember Retail',        email:'diego@emberretail.mx',       phone:'+52 55 5555 1010',  tags:['risk'],            lastMin:8640 },
    { first:'Fatima',  last:'Al-Rashid', title:'Sustainability Dir',company:'Kestrel Energy',      email:'fatima@kestrel.energy',      phone:'+971 50 555 0142',  tags:['vip','champion'],  lastMin:30 },
    { first:'Noah',    last:'Park',      title:'CFO',               company:'Nova Financial',      email:'noah.park@nova.kr',          phone:'+82 10 5555 0166',  tags:['decision','vip'],  lastMin:240 },
    { first:'Isabella',last:'Rossi',     title:'Partner',           company:'Pelagic Ventures',    email:'isabella@pelagic.vc',        phone:'+39 320 555 0192',  tags:['champion'],        lastMin:75 },
    { first:'Liam',    last:"O'Brien",   title:'Solutions Engineer',company:'Vertex Robotics',     email:'liam@vertex.io',             phone:'+353 1 555 0134',   tags:['prospect'],        lastMin:1200 },
    { first:'Zara',    last:'Hussain',   title:'Head of Data',      company:'Meridian Health',     email:'zara@meridian.io',           phone:'+971 50 555 0189',  tags:['prospect'],        lastMin:20 },
    { first:'Ken',     last:'Yamamoto',  title:'Analytics Lead',    company:'Northwind Analytics', email:'ken@northwind.io',           phone:'+81 80 5555 4321',  tags:['decision'],        lastMin:90 },
    { first:'Diana',   last:'Petrova',   title:'Support Manager',   company:'Aurora Labs',         email:'diana@auroralabs.jp',        phone:'+7 495 555 8877',   tags:['risk'],            lastMin:4320 },
    { first:'Mateo',   last:'Silva',     title:'Content Director',  company:'Titan Media',         email:'mateo@titan.tv',             phone:'+55 11 5555 9911',  tags:['prospect'],        lastMin:360 },
    { first:'Chloe',   last:'Baker',     title:'CEO',               company:'Cirrus Cloud',        email:'chloe@cirrus.cloud',         phone:'+1 415 555 2020',   tags:['vip','decision','champion'], lastMin:15 },
    { first:'Omar',    last:'Farah',     title:'Merchandising',     company:'Ember Retail',        email:'omar@emberretail.mx',        phone:'+52 55 5555 3030',  tags:['prospect'],        lastMin:600 },
    { first:'Hannah',  last:'Nguyen',    title:'Ops Manager',       company:'Kestrel Energy',      email:'hannah@kestrel.energy',      phone:'+1 604 555 8080',   tags:['champion'],        lastMin:150 },
    { first:'Ethan',   last:'Wright',    title:'Investment Analyst',company:'Nova Financial',      email:'ethan@nova.kr',              phone:'+82 10 5555 7070',  tags:['decision'],        lastMin:25 },
    { first:'Layla',   last:'Ahmed',     title:'Deal Sourcing',     company:'Pelagic Ventures',    email:'layla@pelagic.vc',           phone:'+971 50 555 6060',  tags:['risk','prospect'], lastMin:5040 },
    { first:'Priya',   last:'Menon',     title:'VP Sales',          company:'Vertex Robotics',     email:'priya@vertex.io',            phone:'+91 98765 43210',   tags:['vip','champion'],  lastMin:8 },
    { first:'Marcus',  last:'Chen',      title:'Growth Lead',       company:'Nova Financial',      email:'marcus@nova.kr',             phone:'+1 415 555 0114',   tags:['prospect'],        lastMin:200 }
  ];

  const COMPANIES_SEED = [
    { name:'Vertex Robotics',     industry:'tech',         employees:520,  size:'201-1000', revenue:74000000,  location:'San Francisco, USA', region:'NA',    owner:'Alex Kim',    website:'vertex.io',        openDeals:186000, contacts:12, lastMin:20 },
    { name:'Meridian Health',     industry:'healthcare',   employees:2400, size:'1000+',    revenue:340000000, location:'Boston, USA',        region:'NA',    owner:'Priya Menon', website:'meridian.io',      openDeals:412000, contacts:18, lastMin:120 },
    { name:'Northwind Analytics', industry:'tech',         employees:180,  size:'51-200',   revenue:22000000,  location:'Madrid, Spain',      region:'EU',    owner:'Marcus Chen', website:'northwind.io',     openDeals:88000,  contacts:9,  lastMin:2880 },
    { name:'Aurora Labs',         industry:'tech',         employees:65,   size:'51-200',   revenue:9500000,   location:'Tokyo, Japan',       region:'APAC',  owner:'Sofia García',website:'auroralabs.jp',    openDeals:120000, contacts:7,  lastMin:60 },
    { name:'Titan Media',         industry:'media',        employees:1100, size:'1000+',    revenue:180000000, location:'London, UK',         region:'EU',    owner:'James Doe',   website:'titan.tv',         openDeals:65000,  contacts:14, lastMin:1440 },
    { name:'Cirrus Cloud',        industry:'tech',         employees:340,  size:'201-1000', revenue:52000000,  location:'Berlin, Germany',    region:'EU',    owner:'Alex Kim',    website:'cirrus.cloud',     openDeals:210000, contacts:11, lastMin:45 },
    { name:'Ember Retail',        industry:'retail',       employees:2800, size:'1000+',    revenue:210000000, location:'Mexico City, MX',    region:'LATAM', owner:'Marcus Chen', website:'emberretail.mx',   openDeals:24000,  contacts:6,  lastMin:8640 },
    { name:'Kestrel Energy',      industry:'energy',       employees:940,  size:'201-1000', revenue:410000000, location:'Dubai, UAE',         region:'MEA',   owner:'Priya Menon', website:'kestrel.energy',   openDeals:295000, contacts:10, lastMin:180 },
    { name:'Nova Financial',      industry:'finance',      employees:1800, size:'1000+',    revenue:620000000, location:'Seoul, South Korea', region:'APAC',  owner:'Sofia García',website:'nova.kr',          openDeals:380000, contacts:15, lastMin:30 },
    { name:'Pelagic Ventures',    industry:'finance',      employees:24,   size:'11-50',    revenue:1200000,   location:'Singapore, SG',      region:'APAC',  owner:'James Doe',   website:'pelagic.vc',       openDeals:44000,  contacts:5,  lastMin:75 },
    { name:'Halcyon Systems',     industry:'manufacturing',employees:5200, size:'1000+',    revenue:890000000, location:'Detroit, USA',       region:'NA',    owner:'Alex Kim',    website:'halcyon.mfg',      openDeals:520000, contacts:22, lastMin:240 },
    { name:'Bramble Foods',       industry:'retail',       employees:410,  size:'201-1000', revenue:64000000,  location:'Toronto, Canada',    region:'NA',    owner:'Priya Menon', website:'bramble.food',     openDeals:38000,  contacts:8,  lastMin:1560 },
    { name:'Solstice Media',      industry:'media',        employees:88,   size:'51-200',   revenue:14000000,  location:'Sydney, Australia',  region:'APAC',  owner:'Marcus Chen', website:'solstice.tv',      openDeals:52000,  contacts:6,  lastMin:360 },
    { name:'Ironclad Insurance',  industry:'finance',      employees:6800, size:'1000+',    revenue:1200000000,location:'Zurich, Switzerland',region:'EU',    owner:'Sofia García',website:'ironclad.ch',      openDeals:820000, contacts:19, lastMin:15 },
    { name:'Petal Health',        industry:'healthcare',   employees:145,  size:'51-200',   revenue:18000000,  location:'Amsterdam, NL',      region:'EU',    owner:'James Doe',   website:'petalhealth.nl',   openDeals:98000,  contacts:8,  lastMin:90 },
    { name:'Quantum Grid',        industry:'energy',       employees:320,  size:'201-1000', revenue:88000000,  location:'Oslo, Norway',       region:'EU',    owner:'Alex Kim',    website:'quantumgrid.no',   openDeals:172000, contacts:9,  lastMin:600 },
    { name:'Fjord Labs',          industry:'tech',         employees:12,   size:'11-50',    revenue:800000,    location:'Copenhagen, DK',     region:'EU',    owner:'Priya Menon', website:'fjord.io',         openDeals:28000,  contacts:4,  lastMin:120 },
    { name:'Kite & Kin',          industry:'retail',       employees:8,    size:'1-10',     revenue:420000,    location:'Portland, USA',      region:'NA',    owner:'Marcus Chen', website:'kiteandkin.co',    openDeals:12000,  contacts:3,  lastMin:2400 },
    { name:'Terra Foundry',       industry:'manufacturing',employees:820,  size:'201-1000', revenue:145000000, location:'São Paulo, Brazil',  region:'LATAM', owner:'Sofia García',website:'terrafoundry.br',  openDeals:230000, contacts:12, lastMin:180 },
    { name:'Nimbus AI',           industry:'tech',         employees:42,   size:'11-50',    revenue:5800000,   location:'Tel Aviv, Israel',   region:'MEA',   owner:'James Doe',   website:'nimbus.ai',        openDeals:74000,  contacts:5,  lastMin:20 }
  ];

  /* ============================================================
     COMMON: Base list controller (shared filter/sort/pager/select)
     ============================================================ */
  function createController(cfg) {
    const state = {
      rows: cfg.data.map((r, i) => Object.assign({ id: i + 1 }, r)),
      filtered: [],
      selection: new Set(),
      filters: Object.assign({ q: '' }, cfg.initialFilters || {}),
      sort: cfg.initialSort || { key: 'name', dir: 'asc' },
      page: 1,
      perPage: Number(($('[data-crm-perpage]') || {}).value) || 10,
      loading: false
    };

    const search    = $('[data-crm-search]');
    const resetBtns = $$('[data-crm-reset]');
    const perPage   = $('[data-crm-perpage]');
    const bulk      = $('[data-crm-bulk]');
    const bulkCnt   = $('[data-crm-bulk-count]');
    const selectAll = $('[data-crm-selectall]');
    const meta      = $('[data-crm-meta]');
    const pager     = $('[data-crm-pager]');

    function applyFilters() {
      const q = state.filters.q.trim().toLowerCase();
      state.filtered = state.rows.filter(r => {
        if (q && !cfg.matchQuery(r, q)) return false;
        return cfg.match(r, state.filters);
      });
      const { key, dir } = state.sort;
      state.filtered.sort((a, b) => {
        const av = cfg.getSort(a, key);
        const bv = cfg.getSort(b, key);
        if (av == null && bv == null) return 0;
        if (av == null) return 1;
        if (bv == null) return -1;
        let cmp;
        if (typeof av === 'number' && typeof bv === 'number') cmp = av - bv;
        else cmp = String(av).localeCompare(String(bv), undefined, { numeric: true, sensitivity: 'base' });
        return dir === 'asc' ? cmp : -cmp;
      });
      const totalPages = Math.max(1, Math.ceil(state.filtered.length / state.perPage));
      if (state.page > totalPages) state.page = totalPages;
    }

    function paged() {
      const start = (state.page - 1) * state.perPage;
      return state.filtered.slice(start, start + state.perPage);
    }

    function render() {
      cfg.render(paged(), state);
      renderMeta();
      renderPager();
      renderSortIcons();
      renderSelectAll();
      renderBulk();
    }

    function renderMeta() {
      if (!meta) return;
      const total = state.filtered.length;
      if (!total) { meta.textContent = 'Showing 0 of 0 ' + cfg.noun; return; }
      const start = (state.page - 1) * state.perPage + 1;
      const end = Math.min(total, state.page * state.perPage);
      meta.textContent = 'Showing ' + start + '–' + end + ' of ' + total + ' ' + cfg.noun;
    }

    function renderPager() {
      if (!pager) return;
      const totalPages = Math.max(1, Math.ceil(state.filtered.length / state.perPage));
      const p = state.page;
      const out = [];
      out.push('<button type="button" class="btn" data-crm-page="' + (p - 1) + '"' + (p <= 1 ? ' disabled' : '') + ' aria-label="Previous page"><i class="bi bi-chevron-left"></i></button>');
      const pages = pageNumbers(p, totalPages);
      pages.forEach(n => {
        if (n === '…') out.push('<button type="button" class="btn" disabled>…</button>');
        else out.push('<button type="button" class="btn' + (n === p ? ' is-active' : '') + '" data-crm-page="' + n + '">' + n + '</button>');
      });
      out.push('<button type="button" class="btn" data-crm-page="' + (p + 1) + '"' + (p >= totalPages ? ' disabled' : '') + ' aria-label="Next page"><i class="bi bi-chevron-right"></i></button>');
      pager.innerHTML = out.join('');
    }

    function pageNumbers(cur, total) {
      const list = [];
      if (total <= 7) { for (let i = 1; i <= total; i++) list.push(i); return list; }
      list.push(1);
      if (cur > 3) list.push('…');
      const from = Math.max(2, cur - 1), to = Math.min(total - 1, cur + 1);
      for (let i = from; i <= to; i++) list.push(i);
      if (cur < total - 2) list.push('…');
      list.push(total);
      return list;
    }

    function renderSortIcons() {
      $$('.crm-th-sort').forEach(th => {
        th.classList.remove('is-asc', 'is-desc');
        if (th.dataset.crmSort === state.sort.key) th.classList.add(state.sort.dir === 'asc' ? 'is-asc' : 'is-desc');
      });
    }

    function renderSelectAll() {
      if (!selectAll) return;
      const visible = paged();
      const allSelected = visible.length > 0 && visible.every(r => state.selection.has(r.id));
      selectAll.checked = allSelected;
      selectAll.indeterminate = !allSelected && visible.some(r => state.selection.has(r.id));
    }

    function renderBulk() {
      if (!bulk) return;
      const n = state.selection.size;
      bulk.classList.toggle('is-visible', n > 0);
      if (bulkCnt) bulkCnt.textContent = String(n);
    }

    async function requestRender(withSkeleton) {
      if (withSkeleton && cfg.skeleton) {
        state.loading = true;
        cfg.skeleton();
        await new Promise(res => setTimeout(res, 400));
        state.loading = false;
      }
      applyFilters();
      render();
    }

    /* ---------- Event wiring ---------- */
    if (search) {
      search.addEventListener('input', debounce(e => {
        state.filters.q = e.target.value;
        state.page = 1;
        requestRender(true);
      }, 200));
    }
    resetBtns.forEach(b => b.addEventListener('click', () => {
      if (search) search.value = '';
      state.filters = Object.assign({ q: '' }, cfg.initialFilters || {});
      state.sort = cfg.initialSort || state.sort;
      state.page = 1;
      state.selection.clear();
      cfg.onReset && cfg.onReset();
      requestRender(true);
    }));
    if (perPage) perPage.addEventListener('change', e => {
      state.perPage = Number(e.target.value) || 10;
      state.page = 1;
      requestRender(false);
    });
    if (selectAll) selectAll.addEventListener('change', e => {
      const visible = paged();
      if (e.target.checked) visible.forEach(r => state.selection.add(r.id));
      else visible.forEach(r => state.selection.delete(r.id));
      render();
    });
    if (pager) pager.addEventListener('click', e => {
      const btn = e.target.closest('button[data-crm-page]');
      if (!btn || btn.disabled) return;
      const n = Number(btn.dataset.crmPage);
      const totalPages = Math.max(1, Math.ceil(state.filtered.length / state.perPage));
      if (n < 1 || n > totalPages) return;
      state.page = n;
      render();
    });

    /* Sort */
    $$('.crm-th-sort').forEach(th => {
      th.addEventListener('click', () => {
        const key = th.dataset.crmSort;
        if (state.sort.key === key) state.sort.dir = state.sort.dir === 'asc' ? 'desc' : 'asc';
        else { state.sort.key = key; state.sort.dir = 'asc'; }
        requestRender(false);
      });
    });

    /* Row select delegation */
    document.addEventListener('change', e => {
      const cb = e.target.closest('[data-crm-row-select]');
      if (!cb) return;
      const id = Number(cb.dataset.crmRowSelect);
      if (cb.checked) state.selection.add(id); else state.selection.delete(id);
      renderSelectAll();
      renderBulk();
      const card = cb.closest('.contacts-card');
      if (card) card.classList.toggle('is-selected', cb.checked);
    });

    /* Bulk actions */
    if (bulk) bulk.addEventListener('click', e => {
      const btn = e.target.closest('[data-crm-bulk-action]');
      if (!btn) return;
      e.preventDefault();
      const [action, arg] = btn.dataset.crmBulkAction.split(':');
      handleBulk(action, arg);
    });

    function handleBulk(action, arg) {
      const n = state.selection.size;
      if (!n && action !== 'clear') { toast('Select at least one ' + cfg.singular, 'warning'); return; }
      switch (action) {
        case 'clear':
          state.selection.clear();
          render();
          break;
        case 'delete':
          openDeleteConfirm(n + ' ' + cfg.noun, () => {
            state.rows = state.rows.filter(r => !state.selection.has(r.id));
            state.selection.clear();
            requestRender(false);
            toast(n + ' ' + cfg.noun + ' deleted', 'success');
          });
          break;
        case 'export':
          exportSelected();
          break;
        case 'assign':
          state.rows.forEach(r => { if (state.selection.has(r.id)) r.owner = arg; });
          state.selection.clear();
          render();
          toast('Assigned ' + n + ' ' + cfg.noun + ' to ' + arg, 'success');
          break;
        case 'status':
          state.rows.forEach(r => { if (state.selection.has(r.id)) r.status = arg; });
          state.selection.clear();
          requestRender(false);
          toast('Status updated for ' + n + ' ' + cfg.noun, 'success');
          break;
        case 'tag':
          state.rows.forEach(r => {
            if (state.selection.has(r.id) && Array.isArray(r.tags) && r.tags.indexOf(arg) < 0) r.tags.push(arg);
          });
          state.selection.clear();
          render();
          toast('Tag added to ' + n + ' ' + cfg.noun, 'success');
          break;
        default:
          toast('Action: ' + action, 'info');
      }
    }

    function exportSelected() {
      const rows = state.rows.filter(r => state.selection.has(r.id));
      if (!rows.length) return;
      const keys = Object.keys(rows[0]).filter(k => k !== 'id');
      const csv = [keys.join(',')].concat(rows.map(r => keys.map(k => JSON.stringify(r[k] == null ? '' : r[k])).join(','))).join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = cfg.noun + '-export-' + Date.now() + '.csv';
      a.click();
      URL.revokeObjectURL(url);
      toast('Exported ' + rows.length + ' ' + cfg.noun, 'success');
    }

    /* Row action delegation */
    document.addEventListener('click', e => {
      const btn = e.target.closest('[data-crm-row-action]');
      if (!btn) return;
      e.preventDefault();
      const [action, idStr] = btn.dataset.crmRowAction.split(':');
      const id = Number(idStr);
      const row = state.rows.find(r => r.id === id);
      if (!row) return;
      cfg.onRowAction && cfg.onRowAction(action, row, api);
    });

    const api = { state, requestRender, render, applyFilters, exportSelected, openDeleteConfirm };
    return api;
  }

  /* ---------- Shared delete modal helper ---------- */
  let deleteResolver = null;
  function openDeleteConfirm(what, onConfirm) {
    const modalEl = document.getElementById('crmDeleteModal');
    if (!modalEl) { if (confirm('Delete ' + what + '?')) onConfirm(); return; }
    const msg = modalEl.querySelector('[data-crm-delete-msg]');
    if (msg) msg.textContent = 'Are you sure you want to delete ' + what + '? This action cannot be undone.';
    deleteResolver = onConfirm;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  }
  const deleteModal = document.getElementById('crmDeleteModal');
  if (deleteModal) {
    const btn = deleteModal.querySelector('[data-crm-delete-confirm]');
    if (btn) btn.addEventListener('click', () => {
      if (deleteResolver) deleteResolver();
      deleteResolver = null;
      bootstrap.Modal.getInstance(deleteModal).hide();
    });
  }

  /* ============================================================
     LEADS PAGE
     ============================================================ */
  function initLeads() {
    const tbody = $('[data-crm-tbody]');
    const empty = $('[data-crm-empty]');
    const scoreValLbl = $('[data-leads-score-val]');
    const scoreInput  = $('[data-leads-filter="score"]');

    const filters = { source: 'all', status: 'all', owner: 'all', score: 0 };
    const ctrl = createController({
      data: LEADS_SEED,
      noun: 'leads',
      singular: 'lead',
      initialFilters: filters,
      initialSort: { key: 'lastMin', dir: 'asc' },
      matchQuery: (r, q) => (r.first + ' ' + r.last + ' ' + r.email + ' ' + r.company).toLowerCase().indexOf(q) >= 0,
      match: (r, f) => {
        if (f.source !== 'all' && r.source !== f.source) return false;
        if (f.status !== 'all' && r.status !== f.status) return false;
        if (f.owner  !== 'all' && r.owner  !== f.owner)  return false;
        if (Number(f.score) > 0 && r.score < Number(f.score)) return false;
        return true;
      },
      getSort: (r, k) => {
        if (k === 'name') return (r.first + ' ' + r.last).toLowerCase();
        if (k === 'last') return -r.lastMin;
        return r[k];
      },
      skeleton: () => renderSkeleton(9),
      render: renderLeads,
      onReset: () => {
        if (scoreInput) scoreInput.value = '0';
        if (scoreValLbl) scoreValLbl.textContent = '0';
        $$('[data-leads-filter]').forEach(sel => { if (sel.tagName === 'SELECT') sel.value = 'all'; });
      },
      onRowAction: (action, row) => {
        if (action === 'convert') openConvert(row);
        else if (action === 'edit') toast('Edit form for ' + row.first + ' ' + row.last, 'info');
        else if (action === 'delete') ctrl.openDeleteConfirm(row.first + ' ' + row.last, () => {
          ctrl.state.rows = ctrl.state.rows.filter(x => x.id !== row.id);
          ctrl.requestRender(false);
          toast('Lead deleted', 'success');
        });
        else if (action === 'view') toast('Opening ' + row.first + '\'s profile…', 'info');
      }
    });

    $$('[data-leads-filter]').forEach(el => {
      const key = el.dataset.leadsFilter;
      el.addEventListener(el.type === 'range' ? 'input' : 'change', e => {
        const v = e.target.value;
        ctrl.state.filters[key] = key === 'score' ? Number(v) : v;
        if (key === 'score' && scoreValLbl) scoreValLbl.textContent = String(v);
        ctrl.state.page = 1;
        ctrl.requestRender(true);
      });
    });

    function renderSkeleton(cols) {
      if (!tbody) return;
      const rows = [];
      for (let i = 0; i < 6; i++) {
        rows.push('<tr>' + '<td class="crm-td-check"><span class="crm-skel-cell crm-skel-cell--xs"></span></td>' +
          '<td><div class="d-flex align-items-center gap-2"><span class="crm-skel-avatar"></span><span class="crm-skel-cell"></span></div></td>' +
          Array.from({ length: cols - 3 }, () => '<td><span class="crm-skel-cell"></span></td>').join('') +
          '<td class="text-end"><span class="crm-skel-cell crm-skel-cell--60p"></span></td>' +
        '</tr>');
      }
      tbody.innerHTML = rows.join('');
      if (empty) empty.hidden = true;
    }

    function renderLeads(rows, state) {
      if (!tbody) return;
      if (!rows.length) {
        tbody.innerHTML = '';
        if (empty) empty.hidden = false;
        return;
      }
      if (empty) empty.hidden = true;
      tbody.innerHTML = rows.map(r => {
        const scoreClass = r.score >= 80 ? 'leads-score--hot' : r.score >= 60 ? 'leads-score--warm' : r.score >= 40 ? '' : 'leads-score--cold';
        const grad = gradFor(r.company);
        const sourceIcon = { website:'bi-globe', referral:'bi-people', linkedin:'bi-linkedin', email:'bi-envelope', event:'bi-calendar-event' }[r.source];
        const isSel = state.selection.has(r.id);
        return '<tr>' +
          '<td class="crm-td-check"><div class="form-check m-0"><input class="form-check-input" type="checkbox" data-crm-row-select="' + r.id + '"' + (isSel ? ' checked' : '') + ' aria-label="Select ' + escapeHtml(r.first + ' ' + r.last) + '"></div></td>' +
          '<td><div class="leads-cell"><span class="crm-avatar-tile ' + grad + '">' + initials(r.first, r.last) + '</span><div><p class="leads-cell__name">' + escapeHtml(r.first + ' ' + r.last) + '</p><span class="leads-cell__co">' + escapeHtml(r.company) + '</span></div></div></td>' +
          '<td><span class="leads-source leads-source--' + r.source + '"><i class="bi ' + sourceIcon + '"></i>' + capitalize(r.source === 'email' ? 'Cold Email' : r.source) + '</span></td>' +
          '<td><div class="leads-score ' + scoreClass + '"><div class="leads-score__bar"><div class="leads-score__fill" data-fill="' + r.score + '"></div></div><span class="leads-score__val">' + r.score + '</span></div></td>' +
          '<td><span class="leads-status leads-status--' + r.status + '">' + r.status + '</span></td>' +
          '<td><span class="leads-owner"><span class="crm-avatar-sm ' + gradFor(r.owner) + '">' + initials(r.owner.split(' ')[0], r.owner.split(' ')[1] || '') + '</span>' + escapeHtml(r.owner) + '</span></td>' +
          '<td class="crm-nowrap text-body-secondary">' + relTime(r.lastMin) + '</td>' +
          '<td class="leads-value crm-mono">' + fmtMoneyFull(r.value) + '</td>' +
          '<td class="text-end"><div class="crm-actions">' +
            '<button class="crm-action-btn" type="button" data-crm-row-action="convert:' + r.id + '" aria-label="Convert to opportunity" title="Convert"><i class="bi bi-arrow-repeat"></i></button>' +
            '<button class="crm-action-btn" type="button" data-crm-row-action="edit:' + r.id + '" aria-label="Edit" title="Edit"><i class="bi bi-pencil"></i></button>' +
            '<button class="crm-action-btn crm-action-btn--danger" type="button" data-crm-row-action="delete:' + r.id + '" aria-label="Delete" title="Delete"><i class="bi bi-trash"></i></button>' +
          '</div></td>' +
        '</tr>';
      }).join('');
      // Set score bar widths
      $$('.leads-score__fill[data-fill]', tbody).forEach(el => { el.style.width = el.dataset.fill + '%'; });
    }

    /* Top sources sidebar */
    function renderTopSources() {
      const host = $('[data-leads-top-sources]');
      if (!host) return;
      const counts = {};
      LEADS_SEED.forEach(l => counts[l.source] = (counts[l.source] || 0) + 1);
      const total = LEADS_SEED.length;
      const label = { website:'Website', referral:'Referral', linkedin:'LinkedIn', email:'Cold Email', event:'Event' };
      const icon  = { website:'bi-globe', referral:'bi-people', linkedin:'bi-linkedin', email:'bi-envelope', event:'bi-calendar-event' };
      const list = Object.keys(counts).sort((a, b) => counts[b] - counts[a]);
      host.innerHTML = list.map(k => {
        const pct = Math.round(counts[k] / total * 100);
        return '<div class="leads-side-source"><span class="leads-side-source__name"><i class="bi ' + icon[k] + ' text-primary"></i>' + label[k] + '</span><span class="leads-side-source__meta"><span class="leads-side-source__bar"><span class="leads-side-source__fill" data-fill="' + pct + '"></span></span><span class="leads-side-source__count">' + counts[k] + '</span></span></div>';
      }).join('');
      $$('.leads-side-source__fill[data-fill]', host).forEach(el => { el.style.width = el.dataset.fill + '%'; });
    }

    /* Hot leads */
    function renderHot() {
      const host = $('[data-leads-hot]');
      if (!host) return;
      const hot = [].concat(LEADS_SEED).sort((a, b) => b.score - a.score).slice(0, 5);
      host.innerHTML = hot.map(l => '<div class="leads-hot"><span class="crm-avatar-tile leads-hot__av ' + gradFor(l.company) + '">' + initials(l.first, l.last) + '</span><div class="leads-hot__meta"><p class="leads-hot__name">' + escapeHtml(l.first + ' ' + l.last) + '</p><small>' + escapeHtml(l.company) + '</small></div><span class="leads-hot__score">' + l.score + '</span></div>').join('');
    }

    /* Conversion chart */
    function renderChart() {
      const cv = $('[data-leads-chart="conversion"]');
      if (!cv || typeof Chart === 'undefined') return;
      const grad = cv.getContext('2d').createLinearGradient(0, 0, 0, 92);
      grad.addColorStop(0, 'rgba(79,70,229,0.35)');
      grad.addColorStop(1, 'rgba(79,70,229,0)');
      new Chart(cv, {
        type: 'line',
        data: {
          labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
          datasets: [{
            data: [22, 28, 25, 34, 30, 38, 32],
            borderColor: '#4f46e5',
            backgroundColor: grad,
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false }, tooltip: { displayColors: false } },
          scales: { x: { display: false }, y: { display: false, beginAtZero: true } }
        }
      });
    }

    /* Convert modal */
    function openConvert(row) {
      const modalEl = document.getElementById('leadConvertModal');
      if (!modalEl) return;
      const n = $('[data-leads-convert-name]', modalEl);
      const op = $('[data-leads-convert-oppname]', modalEl);
      const am = $('[data-leads-convert-amount]', modalEl);
      if (n) n.textContent = row.first + ' ' + row.last;
      if (op) op.value = row.company + ' — ' + row.first + ' ' + row.last;
      if (am) am.value = row.value;
      const btn = $('[data-leads-convert-confirm]', modalEl);
      if (btn) {
        btn.onclick = () => {
          row.status = 'qualified';
          ctrl.render();
          bootstrap.Modal.getInstance(modalEl).hide();
          toast('Converted ' + row.first + ' to opportunity', 'success');
        };
      }
      bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    /* Import / export CSV toolbar buttons */
    const impBtn = $('[data-leads-import]');
    const expBtn = $('[data-leads-export]');
    if (impBtn) impBtn.addEventListener('click', () => toast('CSV import queued — parser not wired in demo', 'info'));
    if (expBtn) expBtn.addEventListener('click', () => {
      const keys = ['first','last','email','company','source','status','score','owner','value'];
      const csv = [keys.join(',')].concat(ctrl.state.filtered.map(r => keys.map(k => JSON.stringify(r[k] == null ? '' : r[k])).join(','))).join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = 'leads-' + Date.now() + '.csv'; a.click();
      URL.revokeObjectURL(url);
      toast('Exported ' + ctrl.state.filtered.length + ' leads', 'success');
    });

    /* New Lead save */
    const newSave = $('[data-leads-new-save]');
    if (newSave) newSave.addEventListener('click', () => {
      const form = $('[data-leads-new-form]');
      if (!form) return;
      const first = $('#newLeadFirst').value.trim();
      const last  = $('#newLeadLast').value.trim();
      const email = $('#newLeadEmail').value.trim();
      if (!first || !last || !email) { toast('First name, last name and email are required', 'warning'); return; }
      const row = {
        id: (ctrl.state.rows[ctrl.state.rows.length - 1]?.id || 0) + 1,
        first, last, email,
        company: $('#newLeadCompany').value.trim() || '—',
        source: $('#newLeadSource').value,
        status: 'new',
        score: 50,
        owner: 'Alex Kim',
        lastMin: 0,
        value: Number($('#newLeadValue').value) || 0
      };
      ctrl.state.rows.unshift(row);
      ctrl.requestRender(false);
      bootstrap.Modal.getInstance(document.getElementById('leadNewModal')).hide();
      form.reset();
      toast('Lead created', 'success');
    });

    ctrl.requestRender(true);
    renderTopSources();
    renderHot();
    renderChart();
  }

  /* ============================================================
     CONTACTS PAGE
     ============================================================ */
  function initContacts() {
    const gridHost   = $('[data-contacts-grid]');
    const tableWrap  = $('[data-contacts-table]');
    const tbody      = tableWrap ? $('[data-crm-tbody]', tableWrap) : null;
    const empty      = $('[data-crm-empty]');
    const alpha      = $('[data-contacts-alpha]');
    let view = 'grid';
    const activeTags = new Set();

    const filters = { company: 'all', sort: 'recent', letter: null };
    const ctrl = createController({
      data: CONTACTS_SEED,
      noun: 'contacts',
      singular: 'contact',
      initialFilters: filters,
      initialSort: { key: 'name', dir: 'asc' },
      matchQuery: (r, q) => (r.first + ' ' + r.last + ' ' + r.email + ' ' + r.company + ' ' + r.phone + ' ' + r.title).toLowerCase().indexOf(q) >= 0,
      match: (r, f) => {
        if (f.company !== 'all' && r.company !== f.company) return false;
        if (f.letter && String(r.last[0] || '').toUpperCase() !== f.letter) return false;
        if (activeTags.size) {
          for (const t of activeTags) if (r.tags.indexOf(t) < 0) return false;
        }
        return true;
      },
      getSort: (r, k) => {
        if (k === 'name') return (r.last + ' ' + r.first).toLowerCase();
        if (k === 'last') return -r.lastMin;
        if (k === 'title') return (r.title || '').toLowerCase();
        return (r[k] || '').toString().toLowerCase();
      },
      skeleton,
      render,
      onReset: () => {
        activeTags.clear();
        $$('.crm-chip', $('[data-contacts-tags]')).forEach(c => c.classList.remove('is-active'));
        $$('.contacts-alpha__btn').forEach(b => b.classList.remove('is-active'));
        $$('[data-contacts-filter]').forEach(s => s.value = s.dataset.contactsFilter === 'sort' ? 'recent' : 'all');
        ctrl && (ctrl.state.filters.letter = null);
      },
      onRowAction: (action, row) => {
        if (action === 'message') toast('Composing message to ' + row.first + '…', 'info');
        else if (action === 'call')  toast('Calling ' + row.first + '…', 'info');
        else if (action === 'edit')  toast('Edit form for ' + row.first, 'info');
        else if (action === 'delete') ctrl.openDeleteConfirm(row.first + ' ' + row.last, () => {
          ctrl.state.rows = ctrl.state.rows.filter(x => x.id !== row.id);
          ctrl.requestRender(false);
          toast('Contact deleted', 'success');
        });
      }
    });

    /* Sync sort with filter dropdown */
    $$('[data-contacts-filter]').forEach(el => {
      el.addEventListener('change', e => {
        const key = el.dataset.contactsFilter;
        if (key === 'sort') {
          const v = e.target.value;
          if (v === 'name') ctrl.state.sort = { key: 'name', dir: 'asc' };
          else if (v === 'company') ctrl.state.sort = { key: 'company', dir: 'asc' };
          else ctrl.state.sort = { key: 'last', dir: 'asc' };
        } else {
          ctrl.state.filters[key] = e.target.value;
        }
        ctrl.state.page = 1;
        ctrl.requestRender(true);
      });
    });

    /* Tag chips */
    const tagsHost = $('[data-contacts-tags]');
    if (tagsHost) tagsHost.addEventListener('click', e => {
      const chip = e.target.closest('.crm-chip[data-contacts-tag]');
      if (!chip) return;
      const t = chip.dataset.contactsTag;
      if (activeTags.has(t)) { activeTags.delete(t); chip.classList.remove('is-active'); }
      else { activeTags.add(t); chip.classList.add('is-active'); }
      ctrl.state.page = 1;
      ctrl.requestRender(true);
    });

    /* View toggle */
    $$('[data-contacts-view]').forEach(b => b.addEventListener('click', () => {
      const v = b.dataset.contactsView;
      view = v;
      $$('[data-contacts-view]').forEach(x => { x.classList.toggle('is-active', x === b); x.setAttribute('aria-pressed', String(x === b)); });
      if (gridHost)  gridHost.hidden = v !== 'grid';
      if (tableWrap) tableWrap.hidden = v !== 'table';
      ctrl.render();
    }));

    /* Alphabet index */
    if (alpha) {
      const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
      const present = new Set(CONTACTS_SEED.map(c => String(c.last[0] || '').toUpperCase()));
      const btns = ['<span class="contacts-alpha__label">Jump</span>',
        '<button type="button" class="contacts-alpha__btn is-active" data-contacts-letter="">All</button>']
        .concat(letters.map(L => '<button type="button" class="contacts-alpha__btn"' + (present.has(L) ? '' : ' disabled') + ' data-contacts-letter="' + L + '">' + L + '</button>'));
      alpha.innerHTML = btns.join('');
      alpha.addEventListener('click', e => {
        const b = e.target.closest('.contacts-alpha__btn');
        if (!b || b.disabled) return;
        $$('.contacts-alpha__btn', alpha).forEach(x => x.classList.remove('is-active'));
        b.classList.add('is-active');
        ctrl.state.filters.letter = b.dataset.contactsLetter || null;
        ctrl.state.page = 1;
        ctrl.requestRender(true);
      });
    }

    function skeleton() {
      if (view === 'grid' && gridHost) {
        const cards = [];
        for (let i = 0; i < 6; i++) {
          cards.push('<div class="contacts-card"><span class="crm-skel-avatar crm-skel-avatar--lg"></span><span class="crm-skel-cell crm-skel-cell--60pc"></span><span class="crm-skel-cell crm-skel-cell--40pc"></span></div>');
        }
        gridHost.innerHTML = cards.join('');
      }
      if (view === 'table' && tbody) {
        const rows = [];
        for (let i = 0; i < 6; i++) rows.push('<tr>' + Array.from({length: 9}, () => '<td><span class="crm-skel-cell"></span></td>').join('') + '</tr>');
        tbody.innerHTML = rows.join('');
      }
      if (empty) empty.hidden = true;
    }

    function render(rows, state) {
      const has = rows.length > 0;
      if (empty) empty.hidden = has;
      if (view === 'grid' && gridHost) { gridHost.hidden = false; renderGrid(rows, state); if (tableWrap) tableWrap.hidden = true; }
      else if (view === 'table' && tableWrap) { tableWrap.hidden = false; renderTable(rows, state); if (gridHost) gridHost.hidden = true; }
    }

    function renderGrid(rows, state) {
      if (!gridHost) return;
      gridHost.innerHTML = rows.map(r => {
        const isSel = state.selection.has(r.id);
        const tags = r.tags.map(t => '<span class="crm-tag crm-tag--' + t + '">' + tagLabel(t) + '</span>').join('');
        const photoUrl = 'https://i.pravatar.cc/160?u=orchid-' + r.id;
        const fullName = escapeHtml(r.first + ' ' + r.last);
        return '<div class="contacts-card' + (isSel ? ' is-selected' : '') + '">' +
          '<div class="form-check contacts-card__check"><input class="form-check-input" type="checkbox" data-crm-row-select="' + r.id + '"' + (isSel ? ' checked' : '') + ' aria-label="Select ' + fullName + '"></div>' +
          '<img class="contacts-card__photo" src="' + photoUrl + '" alt="Portrait of ' + fullName + '" loading="lazy" width="80" height="80">' +
          '<div class="contacts-card__body">' +
            '<p class="contacts-card__name">' + fullName + '</p>' +
            '<p class="contacts-card__title">' + escapeHtml(r.title) + '</p>' +
            '<p class="contacts-card__company">' + escapeHtml(r.company) + '</p>' +
            '<div class="contacts-card__meta"><div><i class="bi bi-envelope"></i>' + escapeHtml(r.email) + '</div><div><i class="bi bi-telephone"></i>' + escapeHtml(r.phone) + '</div><div><i class="bi bi-clock"></i>Last contacted ' + relTime(r.lastMin) + '</div></div>' +
            '<div class="contacts-card__tags">' + tags + '</div>' +
            '<div class="contacts-card__actions">' +
              '<button class="btn btn-outline-secondary btn-sm" type="button" data-crm-row-action="message:' + r.id + '" aria-label="Message"><i class="bi bi-envelope"></i></button>' +
              '<button class="btn btn-outline-secondary btn-sm" type="button" data-crm-row-action="call:' + r.id + '" aria-label="Call"><i class="bi bi-telephone"></i></button>' +
              '<button class="btn btn-outline-secondary btn-sm" type="button" data-crm-row-action="edit:' + r.id + '" aria-label="Video meeting"><i class="bi bi-camera-video"></i></button>' +
            '</div>' +
          '</div>' +
        '</div>';
      }).join('');
    }

    function renderTable(rows, state) {
      if (!tbody) return;
      tbody.innerHTML = rows.map(r => {
        const grad = gradFor(r.first + r.last);
        const isSel = state.selection.has(r.id);
        const tags = r.tags.map(t => '<span class="crm-tag crm-tag--' + t + '">' + tagLabel(t) + '</span>').join(' ');
        return '<tr>' +
          '<td class="crm-td-check"><div class="form-check m-0"><input class="form-check-input" type="checkbox" data-crm-row-select="' + r.id + '"' + (isSel ? ' checked' : '') + ' aria-label="Select ' + escapeHtml(r.first + ' ' + r.last) + '"></div></td>' +
          '<td><div class="leads-cell"><span class="crm-avatar-tile crm-avatar-tile--sm ' + grad + '">' + initials(r.first, r.last) + '</span><div><p class="leads-cell__name">' + escapeHtml(r.first + ' ' + r.last) + '</p></div></div></td>' +
          '<td>' + escapeHtml(r.company) + '</td>' +
          '<td class="text-body-secondary">' + escapeHtml(r.title) + '</td>' +
          '<td class="text-body-secondary">' + escapeHtml(r.email) + '</td>' +
          '<td class="text-body-secondary crm-nowrap">' + escapeHtml(r.phone) + '</td>' +
          '<td>' + tags + '</td>' +
          '<td class="text-body-secondary crm-nowrap">' + relTime(r.lastMin) + '</td>' +
          '<td class="text-end"><div class="crm-actions">' +
            '<button class="crm-action-btn" type="button" data-crm-row-action="message:' + r.id + '" aria-label="Message"><i class="bi bi-envelope"></i></button>' +
            '<button class="crm-action-btn" type="button" data-crm-row-action="edit:' + r.id + '" aria-label="Edit"><i class="bi bi-pencil"></i></button>' +
            '<button class="crm-action-btn crm-action-btn--danger" type="button" data-crm-row-action="delete:' + r.id + '" aria-label="Delete"><i class="bi bi-trash"></i></button>' +
          '</div></td>' +
        '</tr>';
      }).join('');
    }

    function tagLabel(t) {
      return { vip:'VIP', decision:'Decision Maker', prospect:'Prospect', champion:'Champion', risk:'At Risk' }[t] || t;
    }

    /* Add Contact save */
    const cAddSave = $('[data-contacts-add-save]');
    if (cAddSave) cAddSave.addEventListener('click', () => {
      const first = $('#cAddFirst').value.trim();
      const last  = $('#cAddLast').value.trim();
      const email = $('#cAddEmail').value.trim();
      if (!first || !last || !email) { toast('First name, last name and email are required', 'warning'); return; }
      const row = {
        id: (ctrl.state.rows[ctrl.state.rows.length - 1]?.id || 0) + 1,
        first, last, email,
        title: $('#cAddTitle').value.trim() || '—',
        company: $('#cAddCompany').value.trim() || '—',
        phone: $('#cAddPhone').value.trim(),
        tags: [$('#cAddTag').value],
        lastMin: 0
      };
      ctrl.state.rows.unshift(row);
      ctrl.requestRender(false);
      const off = bootstrap.Offcanvas.getInstance(document.getElementById('contactAddOff'));
      if (off) off.hide();
      $('[data-contacts-add-form]').reset();
      toast('Contact added', 'success');
    });

    ctrl.requestRender(true);
  }

  /* ============================================================
     COMPANIES PAGE
     ============================================================ */
  function initCompanies() {
    const tbody = $('[data-crm-tbody]');
    const empty = $('[data-crm-empty]');
    const openRows = new Set();

    const filters = { industry:'all', size:'all', revenue:'all', region:'all' };
    const ctrl = createController({
      data: COMPANIES_SEED,
      noun: 'companies',
      singular: 'company',
      initialFilters: filters,
      initialSort: { key: 'name', dir: 'asc' },
      matchQuery: (r, q) => (r.name + ' ' + r.location + ' ' + r.website + ' ' + r.industry).toLowerCase().indexOf(q) >= 0,
      match: (r, f) => {
        if (f.industry !== 'all' && r.industry !== f.industry) return false;
        if (f.size     !== 'all' && r.size     !== f.size)     return false;
        if (f.region   !== 'all' && r.region   !== f.region)   return false;
        if (f.revenue  !== 'all') {
          const rv = r.revenue;
          if (f.revenue === '0-1m'    && rv >= 1e6)   return false;
          if (f.revenue === '1m-10m'  && (rv < 1e6   || rv >= 1e7))  return false;
          if (f.revenue === '10m-100m'&& (rv < 1e7   || rv >= 1e8))  return false;
          if (f.revenue === '100m+'   && rv < 1e8)    return false;
        }
        return true;
      },
      getSort: (r, k) => {
        if (k === 'location') return (r.location || '').toLowerCase();
        if (k === 'industry') return r.industry;
        if (typeof r[k] === 'number') return r[k];
        return (r[k] || '').toString().toLowerCase();
      },
      skeleton,
      render,
      onReset: () => {
        $$('[data-companies-filter]').forEach(s => s.value = 'all');
      },
      onRowAction: (action, row) => {
        if (action === 'view')   openDrawer(row);
        else if (action === 'edit')   toast('Edit form for ' + row.name, 'info');
        else if (action === 'delete') ctrl.openDeleteConfirm(row.name, () => {
          ctrl.state.rows = ctrl.state.rows.filter(x => x.id !== row.id);
          ctrl.requestRender(false);
          toast('Company deleted', 'success');
        });
      }
    });

    $$('[data-companies-filter]').forEach(el => {
      el.addEventListener('change', e => {
        ctrl.state.filters[el.dataset.companiesFilter] = e.target.value;
        ctrl.state.page = 1;
        ctrl.requestRender(true);
      });
    });

    function skeleton() {
      if (!tbody) return;
      const rows = [];
      for (let i = 0; i < 6; i++) rows.push('<tr>' + Array.from({ length: 9 }, () => '<td><span class="crm-skel-cell"></span></td>').join('') + '</tr>');
      tbody.innerHTML = rows.join('');
      if (empty) empty.hidden = true;
    }

    function industryIcon(i) {
      return { tech:'bi-cpu', healthcare:'bi-heart-pulse', finance:'bi-cash-coin', retail:'bi-bag', manufacturing:'bi-gear-wide-connected', media:'bi-broadcast', energy:'bi-lightning-charge' }[i] || 'bi-building';
    }
    function industryLabel(i) { return { tech:'Tech', healthcare:'Healthcare', finance:'Finance', retail:'Retail', manufacturing:'Manufacturing', media:'Media', energy:'Energy' }[i] || i; }

    function render(rows, state) {
      if (!tbody) return;
      if (!rows.length) { tbody.innerHTML = ''; if (empty) empty.hidden = false; return; }
      if (empty) empty.hidden = true;
      const html = [];
      rows.forEach(r => {
        const grad = gradFor(r.name);
        const isSel = state.selection.has(r.id);
        const isOpen = openRows.has(r.id);
        html.push('<tr>' +
          '<td class="crm-td-check"><div class="form-check m-0"><input class="form-check-input" type="checkbox" data-crm-row-select="' + r.id + '"' + (isSel ? ' checked' : '') + ' aria-label="Select ' + escapeHtml(r.name) + '"></div></td>' +
          '<td><button type="button" class="companies-expand' + (isOpen ? ' is-open' : '') + '" data-companies-expand="' + r.id + '" aria-label="Toggle detail row" aria-expanded="' + isOpen + '"><i class="bi bi-chevron-right"></i></button></td>' +
          '<td><div class="companies-cell"><span class="companies-logo ' + grad + '">' + r.name.charAt(0) + '</span><div><p class="companies-cell__name">' + escapeHtml(r.name) + '</p><span class="companies-cell__url">' + escapeHtml(r.website) + '</span></div></div></td>' +
          '<td><span class="companies-industry companies-industry--' + r.industry + '"><i class="bi ' + industryIcon(r.industry) + '"></i>' + industryLabel(r.industry) + '</span></td>' +
          '<td><span class="companies-employees"><i class="bi bi-people"></i>' + fmtNumber(r.employees) + '</span></td>' +
          '<td class="companies-revenue crm-mono">' + fmtMoney(r.revenue) + '</td>' +
          '<td class="text-body-secondary crm-nowrap">' + escapeHtml(r.location) + '</td>' +
          '<td><span class="leads-owner"><span class="crm-avatar-sm ' + gradFor(r.owner) + '">' + initials(r.owner.split(' ')[0], r.owner.split(' ')[1] || '') + '</span>' + escapeHtml(r.owner) + '</span></td>' +
          '<td class="text-end"><div class="crm-actions">' +
            '<button class="crm-action-btn" type="button" data-crm-row-action="view:' + r.id + '" aria-label="View" title="View"><i class="bi bi-eye"></i></button>' +
            '<button class="crm-action-btn" type="button" data-crm-row-action="edit:' + r.id + '" aria-label="Edit" title="Edit"><i class="bi bi-pencil"></i></button>' +
            '<button class="crm-action-btn crm-action-btn--danger" type="button" data-crm-row-action="delete:' + r.id + '" aria-label="Delete" title="Delete"><i class="bi bi-trash"></i></button>' +
          '</div></td>' +
        '</tr>');
        html.push('<tr class="companies-detail-row' + (isOpen ? ' is-open' : '') + '" data-companies-detail-row="' + r.id + '"><td colspan="9"><div class="companies-detail">' + renderDetail(r) + '</div></td></tr>');
      });
      tbody.innerHTML = html.join('');
    }

    function renderDetail(r) {
      const kpis =
        '<div class="companies-detail__panel"><h6><i class="bi bi-graph-up-arrow text-primary"></i>KPIs</h6>' +
        '<div class="companies-detail__kpis">' +
          '<div class="companies-kpi"><p class="companies-kpi__label">Open Deals</p><p class="companies-kpi__value">' + fmtMoney(r.openDeals) + '</p><p class="companies-kpi__delta">Weighted pipeline</p></div>' +
          '<div class="companies-kpi"><p class="companies-kpi__label">Contacts</p><p class="companies-kpi__value">' + r.contacts + '</p><p class="companies-kpi__delta">People at this account</p></div>' +
          '<div class="companies-kpi"><p class="companies-kpi__label">Last Interaction</p><p class="companies-kpi__value">' + Math.max(1, Math.round(r.lastMin/60/24)) + 'd</p><p class="companies-kpi__delta">' + relTime(r.lastMin) + '</p></div>' +
        '</div></div>';

      const deals =
        '<div class="companies-detail__panel"><h6><i class="bi bi-cash-stack text-primary"></i>Recent deals</h6>' +
        [
          { name:'Q3 Enterprise expansion', stage:'proposal', amt: Math.round(r.openDeals * 0.4) },
          { name:'Renewal — annual seat',   stage:'negot',    amt: Math.round(r.openDeals * 0.3) },
          { name:'Pilot upgrade',           stage:'won',      amt: Math.round(r.openDeals * 0.2) }
        ].map(d => '<div class="companies-deal"><span>' + d.name + '</span><span class="companies-deal__stage companies-deal__stage--' + d.stage + '">' + d.stage + '</span><span class="companies-deal__amt">' + fmtMoney(d.amt) + '</span></div>').join('') +
        '</div>';

      const primaryContacts = CONTACTS_SEED.filter(c => c.company === r.name).slice(0, 4);
      const contacts =
        '<div class="companies-detail__panel"><h6><i class="bi bi-person-lines-fill text-primary"></i>Primary contacts</h6>' +
        (primaryContacts.length ? primaryContacts.map(c => '<div class="companies-primary"><span class="crm-avatar-tile crm-avatar-tile--xs ' + gradFor(c.first + c.last) + '">' + initials(c.first, c.last) + '</span><div class="flex-grow-1"><p class="companies-primary__name">' + escapeHtml(c.first + ' ' + c.last) + '</p><small>' + escapeHtml(c.title) + '</small></div></div>').join('') : '<p class="text-body-secondary small mb-0">No linked contacts</p>') +
        '</div>';

      return '<div class="companies-detail__grid">' + kpis + deals + contacts + '</div>';
    }

    /* Expand delegation */
    if (tbody) tbody.addEventListener('click', e => {
      const btn = e.target.closest('[data-companies-expand]');
      if (!btn) return;
      const id = Number(btn.dataset.companiesExpand);
      const row = document.querySelector('[data-companies-detail-row="' + id + '"]');
      const isOpen = openRows.has(id);
      if (isOpen) { openRows.delete(id); btn.classList.remove('is-open'); btn.setAttribute('aria-expanded','false'); if (row) row.classList.remove('is-open'); }
      else { openRows.add(id); btn.classList.add('is-open'); btn.setAttribute('aria-expanded','true'); if (row) row.classList.add('is-open'); }
    });

    /* Drawer */
    function openDrawer(r) {
      const el = document.getElementById('companyDrawer');
      if (!el) return;
      const hero  = $('[data-companies-drawer-hero]', el);
      const ov    = $('[data-companies-drawer-overview]', el);
      const cts   = $('[data-companies-drawer-contacts]', el);
      const deals = $('[data-companies-drawer-deals]', el);
      const act   = $('[data-companies-drawer-activity]', el);
      const notes = $('[data-companies-drawer-notes]', el);
      const noteBtn = $('[data-companies-drawer-note]', el);
      const noteIn  = $('#drwNoteInput', el);
      const title = $('#companyDrawerTitle');
      if (title) title.textContent = r.name;
      const grad = gradFor(r.name);
      if (hero) hero.innerHTML = '<span class="companies-logo ' + grad + '">' + r.name.charAt(0) + '</span>' +
        '<div><h4 class="mb-1">' + escapeHtml(r.name) + '</h4><small class="text-body-secondary"><i class="bi bi-geo-alt me-1"></i>' + escapeHtml(r.location) + ' · <a href="#" class="text-decoration-none">' + escapeHtml(r.website) + '</a></small></div>';
      if (ov) ov.innerHTML =
        '<div class="companies-detail__kpis mb-3">' +
          '<div class="companies-kpi"><p class="companies-kpi__label">Revenue</p><p class="companies-kpi__value">' + fmtMoney(r.revenue) + '</p></div>' +
          '<div class="companies-kpi"><p class="companies-kpi__label">Employees</p><p class="companies-kpi__value">' + fmtNumber(r.employees) + '</p></div>' +
          '<div class="companies-kpi"><p class="companies-kpi__label">Open pipeline</p><p class="companies-kpi__value">' + fmtMoney(r.openDeals) + '</p></div>' +
        '</div>' +
        '<p class="small text-body-secondary mb-2"><strong>Industry:</strong> ' + industryLabel(r.industry) + '</p>' +
        '<p class="small text-body-secondary mb-2"><strong>Region:</strong> ' + r.region + '</p>' +
        '<p class="small text-body-secondary mb-0"><strong>Primary owner:</strong> ' + escapeHtml(r.owner) + '</p>';

      const linked = CONTACTS_SEED.filter(c => c.company === r.name);
      if (cts) cts.innerHTML = linked.length
        ? linked.map(c => '<div class="companies-primary"><span class="crm-avatar-tile crm-avatar-tile--sm ' + gradFor(c.first + c.last) + '">' + initials(c.first, c.last) + '</span><div class="flex-grow-1"><p class="companies-primary__name">' + escapeHtml(c.first + ' ' + c.last) + '</p><small>' + escapeHtml(c.title) + ' · ' + escapeHtml(c.email) + '</small></div></div>').join('')
        : '<p class="text-body-secondary small">No contacts linked to this company yet.</p>';

      if (deals) deals.innerHTML =
        [
          { name:'Q3 Enterprise expansion', stage:'proposal', amt: Math.round(r.openDeals * 0.4) },
          { name:'Renewal — annual seat',   stage:'negot',    amt: Math.round(r.openDeals * 0.3) },
          { name:'Pilot upgrade',           stage:'won',      amt: Math.round(r.openDeals * 0.2) },
          { name:'Cross-sell add-on',       stage:'proposal', amt: Math.round(r.openDeals * 0.1) }
        ].map(d => '<div class="companies-deal"><span>' + d.name + '</span><span class="companies-deal__stage companies-deal__stage--' + d.stage + '">' + d.stage + '</span><span class="companies-deal__amt">' + fmtMoney(d.amt) + '</span></div>').join('');

      if (act) act.innerHTML =
        '<div class="mb-2"><i class="bi bi-envelope text-primary me-2"></i><strong>Alex Kim</strong> emailed proposal — <small class="text-body-secondary">' + relTime(r.lastMin) + '</small></div>' +
        '<div class="mb-2"><i class="bi bi-telephone text-primary me-2"></i><strong>Priya Menon</strong> discovery call — <small class="text-body-secondary">' + relTime(r.lastMin + 1440) + '</small></div>' +
        '<div class="mb-2"><i class="bi bi-calendar-event text-primary me-2"></i>Renewal meeting scheduled — <small class="text-body-secondary">' + relTime(r.lastMin + 4320) + '</small></div>' +
        '<div><i class="bi bi-file-text text-primary me-2"></i>Contract v2 uploaded — <small class="text-body-secondary">' + relTime(r.lastMin + 8640) + '</small></div>';

      if (notes) notes.innerHTML = (r._notes || []).map(n => '<div class="companies-drawer__note">' + escapeHtml(n.text) + '<br><small>' + n.when + '</small></div>').join('') || '<p class="text-body-secondary small">No notes yet.</p>';
      if (noteBtn && noteIn) noteBtn.onclick = () => {
        const v = noteIn.value.trim();
        if (!v) { toast('Type a note first', 'warning'); return; }
        r._notes = r._notes || [];
        r._notes.unshift({ text: v, when: new Date().toLocaleString() });
        noteIn.value = '';
        if (notes) notes.innerHTML = r._notes.map(n => '<div class="companies-drawer__note">' + escapeHtml(n.text) + '<br><small>' + n.when + '</small></div>').join('');
        toast('Note added', 'success');
      };

      bootstrap.Offcanvas.getOrCreateInstance(el).show();
    }

    /* Add company save */
    const btn = $('[data-companies-add-save]');
    if (btn) btn.addEventListener('click', () => {
      const name = $('#coName').value.trim();
      if (!name) { toast('Company name is required', 'warning'); return; }
      const row = {
        id: (ctrl.state.rows[ctrl.state.rows.length - 1]?.id || 0) + 1,
        name,
        industry: $('#coIndustry').value,
        employees: Number($('#coEmployees').value.split('-')[0]) || 50,
        size: $('#coEmployees').value,
        revenue: Number($('#coRevenue').value) || 0,
        location: $('#coLocation').value.trim() || '—',
        region: $('#coRegion').value,
        owner: 'Alex Kim',
        website: $('#coWebsite').value.trim().replace(/^https?:\/\//, '') || '—',
        openDeals: 0,
        contacts: 0,
        lastMin: 0
      };
      ctrl.state.rows.unshift(row);
      ctrl.requestRender(false);
      bootstrap.Modal.getInstance(document.getElementById('companyAddModal')).hide();
      $('[data-companies-add-form]').reset();
      toast('Company added', 'success');
    });

    ctrl.requestRender(true);
  }

  function capitalize(s) { return String(s || '').charAt(0).toUpperCase() + String(s || '').slice(1); }

  /* ---------- Boot ---------- */
  document.addEventListener('DOMContentLoaded', () => {
    if (PAGE === 'leads')     initLeads();
    if (PAGE === 'contacts')  initContacts();
    if (PAGE === 'companies') initCompanies();
  });
})();
