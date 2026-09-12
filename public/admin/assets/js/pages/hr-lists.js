/* =====================================================
   Orchid — HR Lists (Employees / Attendance / Leave /
   Payroll / Recruitment / Performance)
   Single script, auto-detects page.
   ===================================================== */
(function () {
  'use strict';

  /* ---------- Utils ---------- */
  const $  = (sel, root) => (root || document).querySelector(sel);
  const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const initials = (a, b) => ((a || '')[0] || '').toUpperCase() + ((b || '')[0] || '').toUpperCase();
  const debounce = (fn, ms) => { let t; return function(...a){ clearTimeout(t); t = setTimeout(() => fn.apply(this, a), ms); }; };

  const fmtMoney = n => {
    if (n == null) return '—';
    return '$' + Number(n).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  };
  const fmtMoneyK = n => {
    if (n == null) return '—';
    if (n >= 1e6) return '$' + (n/1e6).toFixed(2) + 'M';
    if (n >= 1e3) return '$' + (n/1e3).toFixed(1) + 'k';
    return '$' + n.toLocaleString();
  };
  const pad = n => String(n).padStart(2, '0');
  const fmtDate = d => {
    if (!(d instanceof Date)) d = new Date(d);
    const m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${m[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;
  };
  const fmtDateShort = d => {
    if (!(d instanceof Date)) d = new Date(d);
    const m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${m[d.getMonth()]} ${d.getDate()}`;
  };
  const iso = d => (d instanceof Date ? d : new Date(d)).toISOString().slice(0,10);
  const daysBetween = (a, b) => {
    const d1 = new Date(a), d2 = new Date(b);
    return Math.round((d2 - d1) / 86400000) + 1;
  };
  const relativeTime = mins => {
    if (mins < 1) return 'Just now';
    if (mins < 60) return mins + 'm ago';
    if (mins < 60*24) return Math.floor(mins/60) + 'h ago';
    if (mins < 60*24*2) return 'Yesterday';
    if (mins < 60*24*30) return Math.floor(mins/(60*24)) + 'd ago';
    if (mins < 60*24*365) return Math.floor(mins/(60*24*30)) + 'mo ago';
    return Math.floor(mins/(60*24*365)) + 'y ago';
  };

  /* ---------- Toasts ---------- */
  const toastHost = (() => {
    let n = document.querySelector('.hr-toast-region');
    if (!n) {
      n = document.createElement('div');
      n.className = 'hr-toast-region';
      n.setAttribute('role','status');
      n.setAttribute('aria-live','polite');
      document.body.appendChild(n);
    }
    return n;
  })();
  const ICON = { success:'bi-check-circle-fill', info:'bi-info-circle-fill', warning:'bi-exclamation-triangle-fill', danger:'bi-x-circle-fill' };
  function orchidToast(msg, kind) {
    kind = kind || 'info';
    const el = document.createElement('div');
    el.className = 'hr-toast hr-toast--' + kind;
    el.innerHTML = '<i class="bi ' + (ICON[kind] || ICON.info) + '"></i><div>' + esc(msg) + '</div>';
    toastHost.appendChild(el);
    setTimeout(() => { el.style.transition = 'opacity .3s, transform .3s'; el.style.opacity = '0'; el.style.transform = 'translateX(20px)'; }, 3200);
    setTimeout(() => el.remove(), 3600);
  }
  window.orchidToast = orchidToast;

  /* ---------- Renderers ---------- */
  const DEPT_KEY = {
    'Engineering':'eng','Design':'design','Product':'product',
    'Marketing':'marketing','Sales':'sales','People Ops':'people',
    'Data':'data','Support':'support'
  };
  function deptClass(dept) { return 'hr-avatar--dept-' + (DEPT_KEY[dept] || 'eng'); }
  function deptChipClass(dept) { return 'hr-dept-chip--' + (DEPT_KEY[dept] || 'eng'); }
  // Deterministic real-photo avatar (pravatar). Same employee id → same face.
  // The initials sit underneath as a graceful fallback if the image ever fails
  // (the .hr-avatar span keeps its gradient background + text; the <img>
  //  overlay is removed by our global 'error' listener below).
  function photoUrl(p) {
    const seed = String(p.id || (p.first + p.last) || 'x').toLowerCase().replace(/[^a-z0-9]/g, '');
    return 'https://i.pravatar.cc/128?u=orchid-' + seed;
  }
  function renderAvatar(p, size) {
    const cls = size ? ('hr-avatar--' + size) : '';
    const alt = esc((p.first || '') + ' ' + (p.last || '')).trim() || 'Employee';
    return '<span class="hr-avatar ' + deptClass(p.dept) + ' ' + cls + '">' +
             initials(p.first, p.last) +
             '<img class="hr-avatar__img" src="' + photoUrl(p) + '" alt="' + alt + '" loading="lazy">' +
           '</span>';
  }

  // Defensive: if a pravatar image 404s, remove it so the underlying
  // gradient + initials show through instead of a broken-image icon.
  document.addEventListener('error', function (e) {
    const el = e.target;
    if (!el || el.tagName !== 'IMG' || !el.matches('.hr-avatar__img')) return;
    el.remove();
  }, true); // capture — <img> error events do not bubble
  function renderStars(rating, max) {
    max = max || 5;
    const full = Math.round(rating);
    let out = '<span class="hr-stars" aria-label="' + rating + ' out of ' + max + '">';
    for (let i = 1; i <= max; i++) {
      out += '<i class="bi ' + (i <= full ? 'bi-star-fill' : 'bi-star') + (i > full ? ' dim' : '') + '"></i>';
    }
    out += '</span>';
    return out;
  }

  /* ---------- Shared employees dataset ---------- */
  const EMPLOYEES = [
    { id:'E01', first:'Priya',    last:'Menon',      email:'priya.menon@orchid.io',    dept:'Engineering', role:'Backend Engineer II', loc:'Bengaluru, IN', type:'Full-time', status:'active',    joined:'2022-03-14', tenureMo:40, phone:'+91 98765 43210' },
    { id:'E02', first:'Marcus',   last:'Chen',       email:'marcus.chen@orchid.io',    dept:'Engineering', role:'Staff Engineer',      loc:'San Francisco, US', type:'Full-time', status:'active', joined:'2020-08-01', tenureMo:60, phone:'+1 415 555 0114' },
    { id:'E03', first:'Sofia',    last:'García',     email:'sofia.garcia@orchid.io',   dept:'Design',      role:'Senior UX Designer',  loc:'Barcelona, ES', type:'Full-time', status:'active',    joined:'2021-06-22', tenureMo:49, phone:'+34 612 345 678' },
    { id:'E04', first:'Yuki',     last:'Tanaka',     email:'yuki.tanaka@orchid.io',    dept:'Product',     role:'Product Manager',     loc:'Tokyo, JP', type:'Full-time', status:'active',        joined:'2023-01-10', tenureMo:30, phone:'+81 90 1234 5678' },
    { id:'E05', first:'Ananya',   last:'Rao',        email:'ananya.rao@orchid.io',     dept:'People Ops',  role:'Talent Partner',      loc:'Mumbai, IN', type:'Full-time', status:'active',       joined:'2022-11-01', tenureMo:32, phone:'+91 90000 12345' },
    { id:'E06', first:'David',    last:'Okafor',     email:'david.okafor@orchid.io',   dept:'Sales',       role:'Account Executive',   loc:'Lagos, NG', type:'Full-time', status:'active',        joined:'2023-05-18', tenureMo:26, phone:'+234 803 000 1234' },
    { id:'E07', first:'Isabella', last:'Rossi',      email:'isabella.rossi@orchid.io', dept:'Marketing',   role:'Product Marketing Manager', loc:'Milan, IT', type:'Full-time', status:'on-leave', joined:'2021-04-05', tenureMo:51, phone:'+39 320 555 0192' },
    { id:'E08', first:'Ken',      last:'Yamamoto',   email:'ken.yamamoto@orchid.io',   dept:'Data',        role:'Head of Data',        loc:'Osaka, JP', type:'Full-time', status:'active',        joined:'2019-10-14', tenureMo:69, phone:'+81 80 5555 4321' },
    { id:'E09', first:'Fatima',   last:'Al-Rashid',  email:'fatima.alrashid@orchid.io',dept:'Product',     role:'Group PM',            loc:'Dubai, AE', type:'Full-time', status:'active',        joined:'2020-05-01', tenureMo:63, phone:'+971 50 555 0142' },
    { id:'E10', first:'Liam',     last:"O'Brien",    email:'liam.obrien@orchid.io',    dept:'Engineering', role:'Frontend Engineer',   loc:'Dublin, IE', type:'Full-time', status:'probation',    joined:'2026-04-15', tenureMo:3, phone:'+353 1 555 0134' },
    { id:'E11', first:'Zara',     last:'Hussain',    email:'zara.hussain@orchid.io',   dept:'Support',     role:'Customer Success Lead',loc:'Toronto, CA', type:'Full-time', status:'active',       joined:'2022-07-11', tenureMo:36, phone:'+1 416 555 0173' },
    { id:'E12', first:'Noah',     last:'Park',       email:'noah.park@orchid.io',      dept:'Sales',       role:'Sales Development Rep',loc:'Seoul, KR', type:'Full-time', status:'active',        joined:'2024-02-19', tenureMo:17, phone:'+82 10 5555 0166' },
    { id:'E13', first:'Diana',    last:'Petrova',    email:'diana.petrova@orchid.io',  dept:'Support',     role:'Support Engineer',    loc:'Berlin, DE', type:'Contract', status:'active',         joined:'2024-09-02', tenureMo:10, phone:'+49 30 5555 8877' },
    { id:'E14', first:'Mateo',    last:'Silva',      email:'mateo.silva@orchid.io',    dept:'Marketing',   role:'Content Strategist',  loc:'São Paulo, BR', type:'Full-time', status:'active',     joined:'2023-08-21', tenureMo:23, phone:'+55 11 5555 9911' },
    { id:'E15', first:'Emma',     last:'Watson',     email:'emma.watson@orchid.io',    dept:'Design',      role:'Design Lead',         loc:'London, UK', type:'Full-time', status:'active',        joined:'2019-02-04', tenureMo:77, phone:'+44 20 7946 0958' },
    { id:'E16', first:'James',    last:'Doe',        email:'james.doe@orchid.io',      dept:'Engineering', role:'Engineering Manager', loc:'New York, US', type:'Full-time', status:'active',      joined:'2018-11-12', tenureMo:80, phone:'+1 212 555 0187' },
    { id:'E17', first:'Sarah',    last:'Miller',     email:'sarah.miller@orchid.io',   dept:'Product',     role:'Product Designer',    loc:'Sydney, AU', type:'Full-time', status:'active',        joined:'2022-01-30', tenureMo:42, phone:'+61 4 5555 1010' },
    { id:'E18', first:'Ryan',     last:'Green',      email:'ryan.green@orchid.io',     dept:'Marketing',   role:'Growth Marketer',     loc:'Toronto, CA', type:'Part-time', status:'active',       joined:'2024-06-01', tenureMo:13, phone:'+1 604 555 8080' },
    { id:'E19', first:'Ava',      last:'Lee',        email:'ava.lee@orchid.io',        dept:'Sales',       role:'Enterprise AE',       loc:'Singapore, SG', type:'Full-time', status:'on-leave',   joined:'2021-09-13', tenureMo:46, phone:'+65 8555 2020' },
    { id:'E20', first:'Alex',     last:'Kim',        email:'alex.kim@orchid.io',       dept:'Engineering', role:'CTO',                 loc:'San Francisco, US', type:'Full-time', status:'active', joined:'2017-05-22', tenureMo:98, phone:'+1 415 555 0100' },
    { id:'E21', first:'Chloe',    last:'Baker',      email:'chloe.baker@orchid.io',    dept:'People Ops',  role:'HR Business Partner', loc:'London, UK', type:'Full-time', status:'active',        joined:'2023-03-06', tenureMo:28, phone:'+44 20 5555 3030' },
    { id:'E22', first:'Omar',     last:'Farah',      email:'omar.farah@orchid.io',     dept:'Data',        role:'Data Analyst',        loc:'Cairo, EG', type:'Full-time', status:'active',         joined:'2024-11-11', tenureMo:8, phone:'+20 100 555 6060' },
    { id:'E23', first:'Hannah',   last:'Nguyen',     email:'hannah.nguyen@orchid.io',  dept:'Data',        role:'Data Engineer',       loc:'Vancouver, CA', type:'Full-time', status:'active',     joined:'2022-05-25', tenureMo:38, phone:'+1 604 555 8080' },
    { id:'E24', first:'Ethan',    last:'Wright',     email:'ethan.wright@orchid.io',   dept:'Support',     role:'Technical Writer',    loc:'Austin, US', type:'Intern', status:'probation',        joined:'2026-05-15', tenureMo:2, phone:'+1 512 555 4040' }
  ];
  function empById(id) { return EMPLOYEES.find(e => e.id === id); }

  /* ---------- Page detection ---------- */
  const PAGE =
    $('[data-emp-grid]') ? 'employees' :
    $('[data-att-heat]') ? 'attendance' :
    $('[data-leave-list]') ? 'leave' :
    $('[data-pay-runs]') ? 'payroll' :
    $('[data-rec-kanban]') ? 'recruitment' :
    $('[data-perf-list]') ? 'performance' :
    null;
  if (!PAGE) return;

  /* =====================================================
     EMPLOYEES
     ===================================================== */
  if (PAGE === 'employees') {
    const state = { view:'grid', search:'', depts:new Set(), status:'', empType:'', sort:'recent', selected:new Set() };

    // KPIs
    (function kpis() {
      const total = EMPLOYEES.length;
      const active = EMPLOYEES.filter(e => e.status === 'active').length;
      const onLeave = EMPLOYEES.filter(e => e.status === 'on-leave').length;
      const now = new Date();
      const newThis = EMPLOYEES.filter(e => {
        const d = new Date(e.joined);
        return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
      }).length;
      $('[data-emp-kpi-total]').textContent = total;
      $('[data-emp-kpi-active]').textContent = active;
      $('[data-emp-kpi-leave]').textContent = onLeave;
      $('[data-emp-kpi-new]').textContent = newThis || 3;
    })();

    function filtered() {
      let list = EMPLOYEES.slice();
      const q = state.search.toLowerCase();
      if (q) list = list.filter(e => (e.first + ' ' + e.last + ' ' + e.email + ' ' + e.role).toLowerCase().includes(q));
      if (state.depts.size) list = list.filter(e => state.depts.has(e.dept));
      if (state.status) list = list.filter(e => e.status === state.status);
      if (state.empType) list = list.filter(e => e.type === state.empType);
      if (state.sort === 'alpha') list.sort((a,b) => a.first.localeCompare(b.first));
      if (state.sort === 'tenure') list.sort((a,b) => b.tenureMo - a.tenureMo);
      if (state.sort === 'recent') list.sort((a,b) => new Date(b.joined) - new Date(a.joined));
      return list;
    }

    function statusPill(s) {
      const map = { active:['success','Active'], 'on-leave':['warning','On leave'], probation:['info','Probation'], terminated:['danger','Terminated'] };
      const [k, txt] = map[s] || ['muted', s];
      return `<span class="hr-status hr-status--${k}">${txt}</span>`;
    }

    function renderGrid() {
      const host = $('[data-emp-grid]');
      const list = filtered();
      if (!list.length) { host.innerHTML = emptyState('No employees'); return; }
      host.innerHTML = list.map(e => `
        <div class="emp-card" data-emp-id="${e.id}">
          <span class="emp-card__hero" aria-hidden="true"></span>
          <div class="emp-card__avatar-wrap">${renderAvatar(e, 'lg')}</div>
          <h6 class="emp-card__name">${esc(e.first)} ${esc(e.last)}</h6>
          <p class="emp-card__role">${esc(e.role)}</p>
          <span class="hr-dept-chip ${deptChipClass(e.dept)}">${esc(e.dept)}</span>
          <span class="emp-card__email">${esc(e.email)}</span>
          <div class="emp-card__actions">
            <button class="hr-icon-btn" type="button" aria-label="Email" data-emp-action="mail"><i class="bi bi-envelope"></i></button>
            <button class="hr-icon-btn" type="button" aria-label="Chat" data-emp-action="chat"><i class="bi bi-chat-dots"></i></button>
            <button class="hr-icon-btn" type="button" aria-label="Video" data-emp-action="video"><i class="bi bi-camera-video"></i></button>
          </div>
        </div>
      `).join('');
      $('[data-emp-count]').textContent = list.length + ' of ' + EMPLOYEES.length;
    }

    function renderTable() {
      const host = $('[data-emp-table]');
      const list = filtered();
      if (!list.length) { host.innerHTML = emptyState('No employees'); return; }
      host.innerHTML = `
        <table class="hr-table">
          <thead><tr>
            <th class="hr-th-check"><input type="checkbox" class="hr-check" data-emp-selall></th>
            <th>Employee</th><th>Department</th><th>Role</th><th>Location</th><th>Status</th><th>Joined</th><th class="text-end">Actions</th>
          </tr></thead>
          <tbody>
            ${list.map(e => `
              <tr data-emp-id="${e.id}">
                <td><input type="checkbox" class="hr-check" data-emp-sel="${e.id}"></td>
                <td><div class="hr-name">${renderAvatar(e)}<div class="hr-name__meta"><p class="hr-name__title">${esc(e.first)} ${esc(e.last)}</p><span class="hr-name__sub">${esc(e.email)}</span></div></div></td>
                <td><span class="hr-dept-chip ${deptChipClass(e.dept)}">${esc(e.dept)}</span></td>
                <td>${esc(e.role)}</td>
                <td class="text-body-secondary">${esc(e.loc)}</td>
                <td>${statusPill(e.status)}</td>
                <td class="text-body-secondary">${fmtDateShort(e.joined)}</td>
                <td class="text-end"><div class="hr-actions">
                  <button class="hr-icon-btn" type="button" aria-label="View" data-emp-action="view"><i class="bi bi-eye"></i></button>
                  <button class="hr-icon-btn" type="button" aria-label="Edit" data-emp-action="edit"><i class="bi bi-pencil"></i></button>
                  <button class="hr-icon-btn hr-icon-btn--danger" type="button" aria-label="Remove" data-emp-action="del"><i class="bi bi-trash"></i></button>
                </div></td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
      $('[data-emp-count]').textContent = list.length + ' of ' + EMPLOYEES.length;
    }

    function render() {
      $('[data-emp-view="grid"]').hidden  = state.view !== 'grid';
      $('[data-emp-view="table"]').hidden = state.view !== 'table';
      if (state.view === 'grid') renderGrid(); else renderTable();
      updateBulk();
    }

    function updateBulk() {
      const bulk = $('[data-emp-bulk]');
      if (!bulk) return;
      if (state.selected.size) { bulk.classList.add('is-visible'); $('[data-emp-bulk-count]').textContent = state.selected.size; }
      else bulk.classList.remove('is-visible');
    }

    // Wire filters
    $('[data-emp-search]').addEventListener('input', debounce(e => { state.search = e.target.value; render(); }, 220));
    $('[data-emp-status]').addEventListener('change', e => { state.status = e.target.value; render(); });
    $('[data-emp-type]').addEventListener('change', e => { state.empType = e.target.value; render(); });
    $('[data-emp-sort]').addEventListener('change', e => { state.sort = e.target.value; render(); });
    $$('[data-emp-dept]').forEach(chip => {
      chip.addEventListener('click', () => {
        const d = chip.getAttribute('data-emp-dept');
        if (state.depts.has(d)) { state.depts.delete(d); chip.classList.remove('is-active'); }
        else { state.depts.add(d); chip.classList.add('is-active'); }
        render();
      });
    });
    $('[data-emp-reset]').addEventListener('click', () => {
      state.search = ''; state.depts.clear(); state.status = ''; state.empType = ''; state.sort = 'recent';
      $('[data-emp-search]').value = ''; $('[data-emp-status]').value = ''; $('[data-emp-type]').value = ''; $('[data-emp-sort]').value = 'recent';
      $$('[data-emp-dept]').forEach(c => c.classList.remove('is-active'));
      render();
      orchidToast('Filters cleared', 'info');
    });

    $$('[data-emp-view-toggle]').forEach(btn => {
      btn.addEventListener('click', () => {
        state.view = btn.getAttribute('data-emp-view-toggle');
        $$('[data-emp-view-toggle]').forEach(b => b.classList.toggle('is-active', b === btn));
        render();
      });
    });

    // Delegated actions
    document.addEventListener('click', e => {
      const act = e.target.closest('[data-emp-action]');
      if (!act) return;
      const t = act.getAttribute('data-emp-action');
      const row = act.closest('[data-emp-id]');
      const id = row && row.getAttribute('data-emp-id');
      const emp = id ? empById(id) : null;
      if (t === 'mail' && emp) orchidToast('Email drafted to ' + emp.first, 'info');
      if (t === 'chat' && emp) orchidToast('Chat opened with ' + emp.first, 'info');
      if (t === 'video' && emp) orchidToast('Meeting scheduled with ' + emp.first, 'success');
      if (t === 'view' && emp) orchidToast('Viewing profile: ' + emp.first + ' ' + emp.last, 'info');
      if (t === 'edit' && emp) orchidToast('Editing ' + emp.first, 'info');
      if (t === 'del' && emp) orchidToast('Removed ' + emp.first, 'danger');
    });

    // Selection
    document.addEventListener('change', e => {
      if (e.target.matches('[data-emp-sel]')) {
        const id = e.target.getAttribute('data-emp-sel');
        if (e.target.checked) state.selected.add(id); else state.selected.delete(id);
        updateBulk();
      }
      if (e.target.matches('[data-emp-selall]')) {
        const chk = e.target.checked;
        $$('[data-emp-sel]').forEach(c => { c.checked = chk; const id = c.getAttribute('data-emp-sel'); if (chk) state.selected.add(id); else state.selected.delete(id); });
        updateBulk();
      }
    });

    $('[data-emp-bulk-clear]').addEventListener('click', () => {
      state.selected.clear();
      $$('[data-emp-sel],[data-emp-selall]').forEach(c => c.checked = false);
      updateBulk();
    });
    $('[data-emp-bulk-mail]').addEventListener('click', () => orchidToast('Bulk email sent to ' + state.selected.size + ' employees', 'success'));
    $('[data-emp-bulk-export]').addEventListener('click', () => orchidToast('Exported ' + state.selected.size + ' employees', 'success'));

    // Add form
    $('[data-emp-add-form]').addEventListener('submit', e => {
      e.preventDefault();
      const fd = new FormData(e.target);
      orchidToast('Added ' + (fd.get('first') || 'employee'), 'success');
      const oc = bootstrap.Offcanvas.getInstance($('#empAddOffcanvas'));
      if (oc) oc.hide();
      e.target.reset();
    });

    // Sortable columns (for table)
    document.addEventListener('click', e => {
      const th = e.target.closest('.hr-th--sortable');
      if (!th || state.view !== 'table') return;
      const key = th.getAttribute('data-sort-key');
      const dir = th.classList.contains('hr-th--asc') ? 'desc' : 'asc';
      $$('.hr-th--sortable').forEach(t => t.classList.remove('hr-th--asc','hr-th--desc'));
      th.classList.add('hr-th--' + dir);
      // Quick reorder
      const list = filtered();
      list.sort((a,b) => {
        const va = a[key] || '', vb = b[key] || '';
        return dir === 'asc' ? String(va).localeCompare(String(vb)) : String(vb).localeCompare(String(va));
      });
    });

    // Skeleton
    setTimeout(render, 400);
    $('[data-emp-grid]').innerHTML = skeletonGrid(8);
  }

  /* =====================================================
     ATTENDANCE
     ===================================================== */
  if (PAGE === 'attendance') {
    // Generate 90 days heatmap data
    const today = new Date('2026-07-23');
    const days = [];
    for (let i = 89; i >= 0; i--) {
      const d = new Date(today); d.setDate(d.getDate() - i);
      const wd = d.getDay();
      const isWeekend = wd === 0 || wd === 6;
      const rate = isWeekend ? 0 : (0.7 + Math.random() * 0.3);
      days.push({ date: iso(d), weekend: isWeekend, rate, present: Math.floor(180 * rate), absent: Math.floor(180 * (1-rate) * 0.6), late: Math.floor(180 * (1-rate) * 0.3), wfh: Math.floor(180 * rate * 0.2) });
    }

    // KPIs
    (function kpis() {
      const t = days[days.length - 1];
      $('[data-att-kpi-present]').textContent = 168;
      $('[data-att-kpi-late]').textContent = 8;
      $('[data-att-kpi-absent]').textContent = 4;
      $('[data-att-kpi-wfh]').textContent = 42;
    })();

    // Heatmap render — 90 days grid, 7 rows (dow) x ~13 cols
    (function heatmap() {
      const host = $('[data-att-heat]');
      // pad so weekdays align
      const first = new Date(days[0].date);
      const pad = first.getDay(); // 0 sun
      let html = '';
      for (let i = 0; i < pad; i++) html += '<div class="att-heat__cell att-heat__cell--future" aria-hidden="true"></div>';
      days.forEach(d => {
        let lvl = 'l0';
        if (d.weekend) lvl = 'l0';
        else if (d.rate > 0.9) lvl = 'l4';
        else if (d.rate > 0.85) lvl = 'l3';
        else if (d.rate > 0.8) lvl = 'l2';
        else if (d.rate > 0.7) lvl = 'l1';
        else lvl = 'warn';
        if (d.rate < 0.65 && !d.weekend) lvl = 'bad';
        html += `<div class="att-heat__cell att-heat__cell--${lvl}" title="${d.date} · ${Math.round(d.rate*100)}%" data-att-day="${d.date}" tabindex="0" role="button" aria-label="${d.date} attendance ${Math.round(d.rate*100)}%"></div>`;
      });
      host.innerHTML = html;

      host.addEventListener('click', e => {
        const cell = e.target.closest('[data-att-day]');
        if (!cell) return;
        const dateStr = cell.getAttribute('data-att-day');
        const day = days.find(d => d.date === dateStr);
        if (!day) return;
        $('[data-att-modal-date]').textContent = fmtDate(dateStr);
        $('[data-att-modal-body]').innerHTML = `
          <div class="row g-2 mb-3">
            <div class="col-3"><div class="hr-card hr-card--pad text-center"><small class="text-body-secondary d-block">Present</small><strong class="h5">${day.present}</strong></div></div>
            <div class="col-3"><div class="hr-card hr-card--pad text-center"><small class="text-body-secondary d-block">Late</small><strong class="h5">${day.late}</strong></div></div>
            <div class="col-3"><div class="hr-card hr-card--pad text-center"><small class="text-body-secondary d-block">Absent</small><strong class="h5">${day.absent}</strong></div></div>
            <div class="col-3"><div class="hr-card hr-card--pad text-center"><small class="text-body-secondary d-block">WFH</small><strong class="h5">${day.wfh}</strong></div></div>
          </div>
          <h6 class="mb-2">Attendance log</h6>
          <div class="table-responsive">
            <table class="hr-table">
              <thead><tr><th>Employee</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr></thead>
              <tbody>
                ${EMPLOYEES.slice(0, 8).map((e,i) => `
                  <tr><td><div class="hr-name">${renderAvatar(e)}<div class="hr-name__meta"><p class="hr-name__title">${esc(e.first)} ${esc(e.last)}</p><span class="hr-name__sub">${esc(e.dept)}</span></div></div></td>
                  <td>${['08:52','09:14','08:45','09:32','—','08:38','09:05','08:59'][i]}</td>
                  <td>${['17:35','18:02','17:20','17:45','—','17:50','18:10','17:28'][i]}</td>
                  <td>${statusOnly(['success','warning','success','danger','danger','success','info','success'][i], ['Present','Late','Present','Absent','Absent','Present','WFH','Present'][i])}</td></tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        `;
        const modal = new bootstrap.Modal($('#attDayModal'));
        modal.show();
      });

      function statusOnly(k, t) { return `<span class="hr-status hr-status--${k}">${t}</span>`; }
    })();

    // Table
    const attState = { search:'', dept:'', month:'', status:'', selected:new Set() };
    const empAtt = EMPLOYEES.slice(0, 20).map(e => {
      const total = 22, absent = Math.floor(Math.random() * 3), late = Math.floor(Math.random() * 4), wfh = Math.floor(Math.random() * 5), present = total - absent;
      const pct = Math.round((present / total) * 100);
      return { ...e, present, absent, late, wfh, avgIn:['08:52','09:14','08:45','09:32','08:58','08:38','09:05','08:59','09:11','08:42'][EMPLOYEES.indexOf(e) % 10], pct };
    });

    function filteredAtt() {
      let list = empAtt.slice();
      const q = attState.search.toLowerCase();
      if (q) list = list.filter(e => (e.first + ' ' + e.last).toLowerCase().includes(q));
      if (attState.dept) list = list.filter(e => e.dept === attState.dept);
      return list;
    }

    function renderAtt() {
      const host = $('[data-att-table]');
      const list = filteredAtt();
      if (!list.length) { host.innerHTML = emptyState('No records'); return; }
      host.innerHTML = `
        <table class="hr-table">
          <thead><tr>
            <th class="hr-th-check"><input type="checkbox" class="hr-check" data-att-selall></th>
            <th>Employee</th><th>Present</th><th>Late</th><th>Absent</th><th>WFH</th><th>Avg check-in</th><th class="hr-th--att">Attendance %</th><th class="text-end">Actions</th>
          </tr></thead>
          <tbody>
            ${list.map(e => `
              <tr data-att-id="${e.id}">
                <td><input type="checkbox" class="hr-check" data-att-sel="${e.id}"></td>
                <td><div class="hr-name">${renderAvatar(e)}<div class="hr-name__meta"><p class="hr-name__title">${esc(e.first)} ${esc(e.last)}</p><span class="hr-name__sub">${esc(e.dept)}</span></div></div></td>
                <td><strong>${e.present}</strong></td>
                <td>${e.late}</td>
                <td class="${e.absent > 1 ? 'text-danger fw-semibold' : ''}">${e.absent}</td>
                <td>${e.wfh}</td>
                <td>${e.avgIn}</td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="hr-progress"><div class="hr-progress__bar ${e.pct > 90 ? 'hr-progress__bar--success' : e.pct > 75 ? '' : 'hr-progress__bar--warning'}" data-w="${e.pct}"></div></div>
                    <small class="fw-semibold">${e.pct}%</small>
                  </div>
                </td>
                <td class="text-end"><div class="hr-actions">
                  <button class="hr-icon-btn" type="button" aria-label="View log" data-att-action="view"><i class="bi bi-list-ul"></i></button>
                  <button class="hr-icon-btn" type="button" aria-label="Mark present" data-att-action="mark"><i class="bi bi-check2-square"></i></button>
                </div></td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
      updateAttBulk();
    }

    function updateAttBulk() {
      const bulk = $('[data-att-bulk]');
      if (!bulk) return;
      if (attState.selected.size) { bulk.classList.add('is-visible'); $('[data-att-bulk-count]').textContent = attState.selected.size; }
      else bulk.classList.remove('is-visible');
    }

    $('[data-att-search]').addEventListener('input', debounce(e => { attState.search = e.target.value; renderAtt(); }, 220));
    $('[data-att-dept]').addEventListener('change', e => { attState.dept = e.target.value; renderAtt(); });
    $('[data-att-month]') && $('[data-att-month]').addEventListener('change', () => renderAtt());
    $('[data-att-reset]').addEventListener('click', () => {
      attState.search = ''; attState.dept = '';
      $('[data-att-search]').value = ''; $('[data-att-dept]').value = '';
      renderAtt();
      orchidToast('Filters cleared', 'info');
    });
    document.addEventListener('change', e => {
      if (e.target.matches('[data-att-sel]')) {
        const id = e.target.getAttribute('data-att-sel');
        if (e.target.checked) attState.selected.add(id); else attState.selected.delete(id);
        updateAttBulk();
      }
      if (e.target.matches('[data-att-selall]')) {
        const chk = e.target.checked;
        $$('[data-att-sel]').forEach(c => { c.checked = chk; const id = c.getAttribute('data-att-sel'); if (chk) attState.selected.add(id); else attState.selected.delete(id); });
        updateAttBulk();
      }
    });
    document.addEventListener('click', e => {
      const act = e.target.closest('[data-att-action]');
      if (!act) return;
      const t = act.getAttribute('data-att-action');
      if (t === 'mark') orchidToast('Marked present', 'success');
      if (t === 'view') orchidToast('Attendance log opened', 'info');
    });
    $('[data-att-bulk-mark]').addEventListener('click', () => orchidToast('Marked ' + attState.selected.size + ' employees present', 'success'));
    $('[data-att-bulk-export]').addEventListener('click', () => orchidToast('Report exported for ' + attState.selected.size + ' employees', 'success'));

    setTimeout(renderAtt, 400);
    $('[data-att-table]').innerHTML = skeletonTable(8, 9);
  }

  /* =====================================================
     LEAVE MANAGEMENT
     ===================================================== */
  if (PAGE === 'leave') {
    const REQUESTS = [
      { id:'L01', empId:'E03', type:'annual',   from:'2026-08-05', to:'2026-08-12', reason:'Family trip to the coast — planned since Q1', status:'pending',  coverage:'Marcus Chen', docs:1 },
      { id:'L02', empId:'E07', type:'parental', from:'2026-07-01', to:'2026-10-30', reason:'Maternity leave — 16 weeks paid + 2 unpaid', status:'approved', coverage:'Sarah Miller', docs:3 },
      { id:'L03', empId:'E10', type:'sick',     from:'2026-07-22', to:'2026-07-24', reason:'Flu symptoms — doctor cert attached', status:'approved', coverage:'James Doe', docs:1 },
      { id:'L04', empId:'E19', type:'annual',   from:'2026-07-20', to:'2026-07-27', reason:'Annual leave — pre-booked long weekend', status:'approved', coverage:'David Okafor', docs:0 },
      { id:'L05', empId:'E12', type:'casual',   from:'2026-07-25', to:'2026-07-25', reason:'Personal errand — half day', status:'pending',  coverage:'David Okafor', docs:0 },
      { id:'L06', empId:'E06', type:'sick',     from:'2026-07-21', to:'2026-07-21', reason:'Migraine — will work remote if possible', status:'pending',  coverage:'—', docs:0 },
      { id:'L07', empId:'E14', type:'annual',   from:'2026-08-15', to:'2026-08-22', reason:'Wedding — cousin', status:'pending',  coverage:'Isabella Rossi', docs:0 },
      { id:'L08', empId:'E22', type:'unpaid',   from:'2026-09-01', to:'2026-09-14', reason:'Personal sabbatical requested', status:'pending',  coverage:'Ken Yamamoto', docs:1 },
      { id:'L09', empId:'E11', type:'casual',   from:'2026-07-30', to:'2026-07-30', reason:'Bank appointment', status:'approved', coverage:'Ethan Wright', docs:0 },
      { id:'L10', empId:'E17', type:'annual',   from:'2026-09-10', to:'2026-09-17', reason:'Vacation to Bali', status:'pending',  coverage:'Emma Watson', docs:0 },
      { id:'L11', empId:'E05', type:'sick',     from:'2026-07-18', to:'2026-07-19', reason:'Recovery day', status:'approved', coverage:'Chloe Baker', docs:1 },
      { id:'L12', empId:'E04', type:'annual',   from:'2026-10-01', to:'2026-10-08', reason:'Family trip to Kyoto', status:'pending',  coverage:'Fatima Al-Rashid', docs:0 },
      { id:'L13', empId:'E13', type:'casual',   from:'2026-07-26', to:'2026-07-26', reason:'Moving day', status:'rejected', coverage:'—', docs:0 },
      { id:'L14', empId:'E18', type:'unpaid',   from:'2026-08-01', to:'2026-08-05', reason:'Freelance obligation', status:'rejected', coverage:'—', docs:0 },
      { id:'L15', empId:'E23', type:'sick',     from:'2026-07-15', to:'2026-07-16', reason:'Fever', status:'approved', coverage:'Omar Farah', docs:1 },
      { id:'L16', empId:'E21', type:'parental', from:'2026-11-01', to:'2027-02-28', reason:'Paternity leave', status:'pending',  coverage:'Ananya Rao', docs:2 },
      { id:'L17', empId:'E08', type:'annual',   from:'2026-09-20', to:'2026-09-26', reason:'Data conference + break', status:'approved', coverage:'Hannah Nguyen', docs:0 },
      { id:'L18', empId:'E15', type:'annual',   from:'2026-08-24', to:'2026-08-31', reason:'Family visit UK', status:'pending',  coverage:'Sofia García', docs:0 }
    ];

    const state = { tab:'pending', search:'', type:'', dept:'' };

    function counts() {
      return {
        pending: REQUESTS.filter(r => r.status === 'pending').length,
        approved: REQUESTS.filter(r => r.status === 'approved').length,
        rejected: REQUESTS.filter(r => r.status === 'rejected').length
      };
    }

    function filtered() {
      let list = REQUESTS.filter(r => r.status === state.tab);
      const q = state.search.toLowerCase();
      if (q) list = list.filter(r => {
        const e = empById(r.empId);
        return e && (e.first + ' ' + e.last + ' ' + r.reason).toLowerCase().includes(q);
      });
      if (state.type) list = list.filter(r => r.type === state.type);
      if (state.dept) list = list.filter(r => empById(r.empId).dept === state.dept);
      return list;
    }

    function typeChip(t) {
      const map = { casual:'Casual', sick:'Sick', annual:'Annual', unpaid:'Unpaid', parental:'Parental' };
      return `<span class="leave-chip leave-chip--${t}">${map[t] || t}</span>`;
    }

    function render() {
      const host = $('[data-leave-list]');
      const list = filtered();
      const c = counts();
      $('[data-leave-count-pending]').textContent = c.pending;
      $('[data-leave-count-approved]').textContent = c.approved;
      $('[data-leave-count-rejected]').textContent = c.rejected;

      if (!list.length) { host.innerHTML = emptyState('No requests'); return; }
      host.innerHTML = list.map(r => {
        const e = empById(r.empId);
        const dur = daysBetween(r.from, r.to);
        return `
          <div class="leave-card" data-leave-id="${r.id}">
            <div class="leave-card__head">
              ${renderAvatar(e)}
              <div class="leave-card__meta">
                <p class="leave-card__name">${esc(e.first)} ${esc(e.last)}</p>
                <span class="leave-card__sub">${esc(e.dept)} · ${esc(e.role)}</span>
              </div>
              ${typeChip(r.type)}
            </div>
            <div class="leave-card__body">
              <strong>${fmtDateShort(r.from)}</strong> — <strong>${fmtDateShort(r.to)}</strong>
              <span class="text-body-secondary">· ${dur} day${dur > 1 ? 's' : ''}</span>
              ${r.docs ? `<span class="ms-2 badge bg-info-subtle text-info"><i class="bi bi-paperclip"></i> ${r.docs}</span>` : ''}
              <p class="leave-card__reason">${esc(r.reason)}</p>
            </div>
            <div class="leave-card__foot">
              <small class="text-body-secondary">Coverage: <strong class="text-body">${esc(r.coverage)}</strong></small>
              <div class="d-inline-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-leave-action="view">View</button>
                ${r.status === 'pending' ? `
                  <button class="btn btn-sm btn-outline-danger" type="button" data-leave-action="reject">Reject</button>
                  <button class="btn btn-sm btn-primary" type="button" data-leave-action="approve">Approve</button>
                ` : ''}
              </div>
            </div>
          </div>
        `;
      }).join('');
    }

    // Mini team calendar
    (function miniCal() {
      const host = $('[data-leave-mini-cal]');
      if (!host) return;
      const today = new Date('2026-07-23');
      const start = new Date(today); start.setDate(today.getDate() - today.getDay());
      const outSet = new Set(['2026-07-22','2026-07-23','2026-07-24','2026-07-25']);
      let html = '<div class="leave-mini-cal">';
      ['S','M','T','W','T','F','S'].forEach(d => html += `<div class="leave-mini-cal__dow">${d}</div>`);
      for (let w = 0; w < 3; w++) {
        for (let i = 0; i < 7; i++) {
          const d = new Date(start); d.setDate(start.getDate() + w*7 + i);
          const dStr = iso(d);
          const cls = dStr === iso(today) ? 'leave-mini-cal__day--today' : outSet.has(dStr) ? 'leave-mini-cal__day--out' : '';
          html += `<div class="leave-mini-cal__day ${cls}" title="${dStr}">${d.getDate()}</div>`;
        }
      }
      html += '</div>';
      host.innerHTML = html;
    })();

    // Tabs
    $$('[data-leave-tab]').forEach(btn => {
      btn.addEventListener('click', () => {
        state.tab = btn.getAttribute('data-leave-tab');
        $$('[data-leave-tab]').forEach(b => b.classList.toggle('is-active', b === btn));
        render();
      });
    });

    // Filters
    $('[data-leave-search]').addEventListener('input', debounce(e => { state.search = e.target.value; render(); }, 220));
    $('[data-leave-type]').addEventListener('change', e => { state.type = e.target.value; render(); });
    $('[data-leave-dept]').addEventListener('change', e => { state.dept = e.target.value; render(); });
    $('[data-leave-reset]').addEventListener('click', () => {
      state.search = ''; state.type = ''; state.dept = '';
      $('[data-leave-search]').value = ''; $('[data-leave-type]').value = ''; $('[data-leave-dept]').value = '';
      render();
      orchidToast('Filters cleared', 'info');
    });

    // Actions
    document.addEventListener('click', e => {
      const act = e.target.closest('[data-leave-action]');
      if (!act) return;
      const card = act.closest('[data-leave-id]');
      const id = card.getAttribute('data-leave-id');
      const req = REQUESTS.find(r => r.id === id);
      const emp = empById(req.empId);
      const t = act.getAttribute('data-leave-action');

      if (t === 'view') {
        $('[data-leave-modal-name]').textContent = `${emp.first} ${emp.last} — Leave request`;
        $('[data-leave-modal-body]').innerHTML = `
          <div class="row g-3 mb-3">
            <div class="col-md-6"><small class="text-body-secondary d-block mb-1">Employee</small><div class="hr-name">${renderAvatar(emp)}<div class="hr-name__meta"><p class="hr-name__title">${esc(emp.first)} ${esc(emp.last)}</p><span class="hr-name__sub">${esc(emp.role)} · ${esc(emp.dept)}</span></div></div></div>
            <div class="col-md-3"><small class="text-body-secondary d-block mb-1">Type</small>${typeChip(req.type)}</div>
            <div class="col-md-3"><small class="text-body-secondary d-block mb-1">Duration</small><strong>${daysBetween(req.from, req.to)} days</strong></div>
            <div class="col-md-6"><small class="text-body-secondary d-block mb-1">From</small><strong>${fmtDate(req.from)}</strong></div>
            <div class="col-md-6"><small class="text-body-secondary d-block mb-1">To</small><strong>${fmtDate(req.to)}</strong></div>
          </div>
          <h6 class="mb-2">Reason</h6>
          <p class="text-body-secondary">${esc(req.reason)}</p>
          <h6 class="mb-2">Coverage plan</h6>
          <p><strong>${esc(req.coverage)}</strong> will cover during absence.</p>
          ${req.docs ? `<h6 class="mb-2">Attached documents</h6><ul class="list-unstyled small"><li><i class="bi bi-file-earmark-pdf text-danger me-1"></i>doctor-cert.pdf <span class="text-body-secondary">· 128 kb</span></li></ul>` : ''}
          <h6 class="mb-2 mt-3">Decision note</h6>
          <textarea class="form-control" rows="3" placeholder="Add a note before approving/rejecting…" data-leave-note></textarea>
        `;
        const m = new bootstrap.Modal($('#leaveViewModal'));
        m.show();
      }
      if (t === 'approve') { req.status = 'approved'; orchidToast('Approved ' + emp.first + "'s leave request", 'success'); render(); }
      if (t === 'reject')  { req.status = 'rejected'; orchidToast('Rejected ' + emp.first + "'s leave request", 'danger'); render(); }
    });

    $('[data-leave-modal-approve]').addEventListener('click', () => {
      const m = bootstrap.Modal.getInstance($('#leaveViewModal'));
      if (m) m.hide();
      orchidToast('Request approved with note', 'success');
    });
    $('[data-leave-modal-reject]').addEventListener('click', () => {
      const m = bootstrap.Modal.getInstance($('#leaveViewModal'));
      if (m) m.hide();
      orchidToast('Request rejected with note', 'danger');
    });

    setTimeout(render, 400);
    $('[data-leave-list]').innerHTML = skeletonCards(4);
  }

  /* =====================================================
     PAYROLL
     ===================================================== */
  if (PAGE === 'payroll') {
    const RUNS = [
      { id:'PAY-2026-07', period:'Jul 1 — Jul 31, 2026', employees:180, gross:1284000, net:986400, status:'processing', run:'2026-07-28' },
      { id:'PAY-2026-06', period:'Jun 1 — Jun 30, 2026', employees:178, gross:1268400, net:975200, status:'paid', run:'2026-06-28' },
      { id:'PAY-2026-05', period:'May 1 — May 31, 2026', employees:176, gross:1252800, net:963100, status:'paid', run:'2026-05-28' },
      { id:'PAY-2026-04', period:'Apr 1 — Apr 30, 2026', employees:174, gross:1238400, net:952800, status:'paid', run:'2026-04-28' },
      { id:'PAY-2026-03', period:'Mar 1 — Mar 31, 2026', employees:172, gross:1224000, net:942600, status:'paid', run:'2026-03-28' },
      { id:'PAY-2026-08', period:'Aug 1 — Aug 31, 2026', employees:180, gross:0,      net:0,     status:'draft', run:'—' }
    ];

    const SLIPS = EMPLOYEES.slice(0, 15).map((e, i) => {
      const gross = 4200 + (i * 320);
      const ded = Math.round(gross * 0.24);
      return { emp:e, period:'Jul 2026', gross, ded, net: gross - ded, status: i < 12 ? 'paid' : 'pending' };
    });

    // KPIs
    (function kpis() {
      const total = RUNS.filter(r => r.status !== 'draft').reduce((s,r) => s + r.gross, 0);
      $('[data-pay-kpi-total]').textContent = fmtMoneyK(total);
      $('[data-pay-kpi-employees]').textContent = 178;
      $('[data-pay-kpi-pending]').textContent = 3;
      // Countdown to next payroll
      const target = new Date('2026-07-28');
      const now = new Date('2026-07-23');
      const days = Math.max(0, Math.ceil((target - now) / 86400000));
      $('[data-pay-kpi-next]').textContent = days + ' days';
    })();

    function statusBadge(s) {
      return `<span class="pay-run-status pay-run-status--${s}">${s}</span>`;
    }

    function renderRuns() {
      const host = $('[data-pay-runs]');
      host.innerHTML = `
        <table class="hr-table">
          <thead><tr>
            <th>Payroll ID</th><th>Period</th><th>Employees</th><th>Gross</th><th>Net</th><th>Status</th><th>Run date</th><th class="text-end">Actions</th>
          </tr></thead>
          <tbody>
            ${RUNS.map(r => `
              <tr data-pay-run="${r.id}">
                <td><strong>${r.id}</strong></td>
                <td class="text-body-secondary">${r.period}</td>
                <td>${r.employees}</td>
                <td class="fw-semibold">${r.gross ? fmtMoneyK(r.gross) : '—'}</td>
                <td class="fw-semibold">${r.net ? fmtMoneyK(r.net) : '—'}</td>
                <td>${statusBadge(r.status)}</td>
                <td class="text-body-secondary">${r.run === '—' ? '—' : fmtDateShort(r.run)}</td>
                <td class="text-end"><div class="hr-actions">
                  <button class="hr-icon-btn" type="button" aria-label="View" data-pay-action="view"><i class="bi bi-eye"></i></button>
                  ${r.status === 'processing' ? '<button class="hr-icon-btn" type="button" aria-label="Approve" data-pay-action="approve"><i class="bi bi-check2-circle"></i></button>' : ''}
                  <button class="hr-icon-btn" type="button" aria-label="Export" data-pay-action="export"><i class="bi bi-download"></i></button>
                </div></td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
    }

    function renderSlips() {
      const host = $('[data-pay-slips]');
      host.innerHTML = `
        <table class="hr-table">
          <thead><tr><th>Employee</th><th>Period</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
            ${SLIPS.map(s => `
              <tr data-pay-slip="${s.emp.id}">
                <td><div class="hr-name">${renderAvatar(s.emp)}<div class="hr-name__meta"><p class="hr-name__title">${esc(s.emp.first)} ${esc(s.emp.last)}</p><span class="hr-name__sub">${esc(s.emp.dept)}</span></div></div></td>
                <td class="text-body-secondary">${s.period}</td>
                <td class="fw-semibold">${fmtMoney(s.gross)}</td>
                <td class="text-danger">- ${fmtMoney(s.ded)}</td>
                <td class="fw-semibold text-success">${fmtMoney(s.net)}</td>
                <td><span class="hr-status hr-status--${s.status === 'paid' ? 'success' : 'warning'}">${s.status}</span></td>
                <td class="text-end"><div class="hr-actions">
                  <button class="hr-icon-btn" type="button" aria-label="View slip" data-pay-slip-view="${s.emp.id}"><i class="bi bi-eye"></i></button>
                  <button class="hr-icon-btn" type="button" aria-label="Download" data-pay-slip-dl><i class="bi bi-file-earmark-pdf"></i></button>
                </div></td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
    }

    renderRuns();
    renderSlips();

    document.addEventListener('click', e => {
      const runAct = e.target.closest('[data-pay-action]');
      if (runAct) {
        const t = runAct.getAttribute('data-pay-action');
        const row = runAct.closest('[data-pay-run]');
        const id = row.getAttribute('data-pay-run');
        if (t === 'view')    orchidToast('Viewing run ' + id, 'info');
        if (t === 'approve') { const r = RUNS.find(x => x.id === id); if (r) { r.status = 'approved'; renderRuns(); orchidToast('Approved ' + id, 'success'); } }
        if (t === 'export')  orchidToast('Exporting payslips for ' + id, 'info');
      }
      const slipView = e.target.closest('[data-pay-slip-view]');
      if (slipView) {
        const id = slipView.getAttribute('data-pay-slip-view');
        const slip = SLIPS.find(s => s.emp.id === id);
        if (slip) openPayslipDrawer(slip);
      }
      if (e.target.closest('[data-pay-slip-dl]')) orchidToast('Downloading payslip PDF', 'success');
    });

    function openPayslipDrawer(slip) {
      $('[data-pay-drawer-title]').textContent = `${slip.emp.first} ${slip.emp.last} — ${slip.period}`;
      const tax = Math.round(slip.gross * 0.15);
      const pf  = Math.round(slip.gross * 0.06);
      const ins = slip.ded - tax - pf;
      $('[data-pay-drawer-body]').innerHTML = `
        <div class="d-flex align-items-center gap-2 mb-3">${renderAvatar(slip.emp, 'lg')}<div><p class="mb-0 fw-semibold">${esc(slip.emp.first)} ${esc(slip.emp.last)}</p><small class="text-body-secondary">${esc(slip.emp.role)} · ${esc(slip.emp.dept)}</small></div></div>
        <div class="pay-drawer-earnings">
          <h6 class="mb-2">Earnings</h6>
          <div class="pay-drawer-row"><span>Base salary</span><span>${fmtMoney(Math.round(slip.gross * 0.85))}</span></div>
          <div class="pay-drawer-row"><span>Bonus</span><span>${fmtMoney(Math.round(slip.gross * 0.08))}</span></div>
          <div class="pay-drawer-row"><span>Overtime</span><span>${fmtMoney(Math.round(slip.gross * 0.07))}</span></div>
          <div class="pay-drawer-row pay-drawer-row__total"><span>Gross</span><span>${fmtMoney(slip.gross)}</span></div>
        </div>
        <div class="pay-drawer-deductions">
          <h6 class="mb-2">Deductions</h6>
          <div class="pay-drawer-row"><span>Income tax</span><span class="text-danger">- ${fmtMoney(tax)}</span></div>
          <div class="pay-drawer-row"><span>Provident fund</span><span class="text-danger">- ${fmtMoney(pf)}</span></div>
          <div class="pay-drawer-row"><span>Health insurance</span><span class="text-danger">- ${fmtMoney(ins)}</span></div>
          <div class="pay-drawer-row pay-drawer-row__total"><span>Total</span><span class="text-danger">- ${fmtMoney(slip.ded)}</span></div>
        </div>
        <div class="pay-drawer-earnings">
          <h6 class="mb-2">Net pay</h6>
          <div class="pay-drawer-row pay-drawer-row__total"><span>Deposited</span><span class="text-success">${fmtMoney(slip.net)}</span></div>
        </div>
        <div class="pay-drawer-earnings">
          <h6 class="mb-2">Year to date</h6>
          <div class="pay-drawer-row"><span>Gross YTD</span><span>${fmtMoney(slip.gross * 7)}</span></div>
          <div class="pay-drawer-row"><span>Tax YTD</span><span>${fmtMoney(tax * 7)}</span></div>
          <div class="pay-drawer-row"><span>Net YTD</span><span>${fmtMoney(slip.net * 7)}</span></div>
        </div>
      `;
      const oc = new bootstrap.Offcanvas($('#payDrawer'));
      oc.show();
    }

    // Multi-step new run modal
    let stepIdx = 0;
    const steps = $$('[data-pay-step]');
    function goStep(i) {
      stepIdx = i;
      steps.forEach((s, idx) => {
        s.classList.toggle('is-active', idx === i);
        s.classList.toggle('is-done', idx < i);
      });
      $$('[data-pay-panel]').forEach((p, idx) => p.hidden = idx !== i);
      $('[data-pay-prev]').disabled = i === 0;
      $('[data-pay-next]').textContent = i === steps.length - 1 ? 'Run payroll' : 'Continue';
    }
    $('[data-pay-next]').addEventListener('click', () => {
      if (stepIdx < steps.length - 1) goStep(stepIdx + 1);
      else {
        const m = bootstrap.Modal.getInstance($('#payNewRunModal'));
        if (m) m.hide();
        orchidToast('New payroll run started for 180 employees', 'success');
        goStep(0);
      }
    });
    $('[data-pay-prev]').addEventListener('click', () => { if (stepIdx > 0) goStep(stepIdx - 1); });
    $('#payNewRunModal').addEventListener('show.bs.modal', () => goStep(0));

    $('[data-pay-export-ledger]').addEventListener('click', () => orchidToast('Ledger exported', 'success'));
  }

  /* =====================================================
     RECRUITMENT
     ===================================================== */
  if (PAGE === 'recruitment') {
    const CANDIDATES = [
      { id:'C01', first:'Priya',    last:'Menon',    role:'Backend Engineer II',  source:'linkedin', rating:4, match:88, stage:'applied',    next:'Screening call · Jul 24' },
      { id:'C02', first:'Marcus',   last:'Chen',     role:'Staff Engineer',       source:'referral', rating:5, match:92, stage:'interview',  next:'Onsite panel · Jul 26' },
      { id:'C03', first:'Sofia',    last:'García',   role:'Senior UX Designer',   source:'website',  rating:4, match:79, stage:'screening',  next:'Design brief · Jul 25' },
      { id:'C04', first:'Yuki',     last:'Tanaka',   role:'Product Manager',      source:'linkedin', rating:5, match:95, stage:'offer',      next:'Offer sent · Jul 22' },
      { id:'C05', first:'Ananya',   last:'Rao',      role:'Talent Partner',       source:'event',    rating:3, match:68, stage:'applied',    next:'Awaiting review' },
      { id:'C06', first:'David',    last:'Okafor',   role:'Account Executive',    source:'referral', rating:4, match:82, stage:'assessment', next:'Sales roleplay · Jul 25' },
      { id:'C07', first:'Isabella', last:'Rossi',    role:'Product Marketing',    source:'linkedin', rating:4, match:76, stage:'screening',  next:'HR chat · Jul 24' },
      { id:'C08', first:'Ken',      last:'Yamamoto', role:'Head of Data',         source:'referral', rating:5, match:94, stage:'hired',      next:'Onboarding · Aug 1' },
      { id:'C09', first:'Fatima',   last:'Al-Rashid',role:'Group PM',             source:'linkedin', rating:5, match:90, stage:'interview',  next:'Peer round · Jul 27' },
      { id:'C10', first:'Liam',     last:"O'Brien",  role:'Frontend Engineer',    source:'website',  rating:3, match:71, stage:'assessment', next:'Take-home · Jul 25' },
      { id:'C11', first:'Zara',     last:'Hussain',  role:'Customer Success',     source:'referral', rating:4, match:80, stage:'interview',  next:'Manager · Jul 26' },
      { id:'C12', first:'Noah',     last:'Park',     role:'SDR',                  source:'website',  rating:3, match:64, stage:'applied',    next:'Awaiting review' },
      { id:'C13', first:'Diana',    last:'Petrova',  role:'Support Engineer',     source:'event',    rating:3, match:67, stage:'screening',  next:'HR chat · Jul 24' },
      { id:'C14', first:'Mateo',    last:'Silva',    role:'Content Strategist',   source:'linkedin', rating:4, match:78, stage:'assessment', next:'Writing sample · Jul 25' },
      { id:'C15', first:'Emma',     last:'Watson',   role:'Design Lead',          source:'referral', rating:5, match:93, stage:'offer',      next:'Verbal offer · Jul 23' },
      { id:'C16', first:'James',    last:'Doe',      role:'Engineering Manager',  source:'linkedin', rating:5, match:89, stage:'hired',      next:'Onboarding · Aug 1' },
      { id:'C17', first:'Sarah',    last:'Miller',   role:'Product Designer',     source:'website',  rating:4, match:81, stage:'interview',  next:'Portfolio · Jul 26' },
      { id:'C18', first:'Ryan',     last:'Green',    role:'Growth Marketer',      source:'referral', rating:3, match:70, stage:'applied',    next:'Awaiting review' },
      { id:'C19', first:'Ava',      last:'Lee',      role:'Enterprise AE',        source:'linkedin', rating:5, match:96, stage:'offer',      next:'Contract review' },
      { id:'C20', first:'Chloe',    last:'Baker',    role:'HR Business Partner',  source:'referral', rating:4, match:83, stage:'screening',  next:'HR chat · Jul 24' }
    ];

    const STAGES = ['applied','screening','interview','assessment','offer','hired'];
    const STAGE_NAMES = { applied:'Applied', screening:'Screening', interview:'Interview', assessment:'Assessment', offer:'Offer', hired:'Hired' };
    const state = { search:'', role:'', source:'', minRating:0 };

    // KPIs
    (function kpis() {
      $('[data-rec-kpi-open]').textContent = 12;
      $('[data-rec-kpi-total]').textContent = CANDIDATES.length;
      $('[data-rec-kpi-interviews]').textContent = CANDIDATES.filter(c => c.stage === 'interview').length;
      $('[data-rec-kpi-offers]').textContent = CANDIDATES.filter(c => c.stage === 'offer').length;
    })();

    function filtered() {
      let list = CANDIDATES.slice();
      const q = state.search.toLowerCase();
      if (q) list = list.filter(c => (c.first + ' ' + c.last + ' ' + c.role).toLowerCase().includes(q));
      if (state.role) list = list.filter(c => c.role === state.role);
      if (state.source) list = list.filter(c => c.source === state.source);
      if (state.minRating) list = list.filter(c => c.rating >= state.minRating);
      return list;
    }

    function candCard(c) {
      return `
        <div class="rec-card" draggable="true" data-rec-id="${c.id}">
          <div class="rec-card__head">
            <span class="hr-avatar hr-avatar--sm hr-avatar--grad">${initials(c.first, c.last)}</span>
            <div>
              <p class="rec-card__name">${esc(c.first)} ${esc(c.last)}</p>
              <span class="rec-card__role">${esc(c.role)}</span>
            </div>
          </div>
          <div class="rec-card__meta">
            <span class="rec-source rec-source--${c.source}">${c.source}</span>
            ${renderStars(c.rating)}
            <span class="rec-match">${c.match}%</span>
          </div>
          <div class="rec-card__next"><i class="bi bi-arrow-right-circle"></i>${esc(c.next)}</div>
        </div>
      `;
    }

    function render() {
      const host = $('[data-rec-kanban]');
      const list = filtered();
      host.innerHTML = STAGES.map(s => {
        const items = list.filter(c => c.stage === s);
        return `
          <div class="rec-col rec-col--${s}" data-rec-col="${s}">
            <div class="rec-col__head">
              <span class="rec-col__name">${STAGE_NAMES[s]}</span>
              <span class="rec-col__count">${items.length}</span>
            </div>
            <div data-rec-col-body="${s}">
              ${items.map(candCard).join('') || '<p class="text-body-secondary small text-center py-3">Drop here</p>'}
            </div>
          </div>
        `;
      }).join('');
      wireDnD();
    }

    // Drag & Drop
    let draggedId = null;
    function wireDnD() {
      $$('.rec-card').forEach(card => {
        card.addEventListener('dragstart', e => {
          draggedId = card.getAttribute('data-rec-id');
          card.classList.add('is-dragging');
          e.dataTransfer.effectAllowed = 'move';
        });
        card.addEventListener('dragend', () => {
          card.classList.remove('is-dragging');
          draggedId = null;
        });
      });
      $$('.rec-col').forEach(col => {
        col.addEventListener('dragover', e => {
          e.preventDefault();
          col.classList.add('is-drag-over');
        });
        col.addEventListener('dragleave', () => col.classList.remove('is-drag-over'));
        col.addEventListener('drop', e => {
          e.preventDefault();
          col.classList.remove('is-drag-over');
          if (!draggedId) return;
          const stage = col.getAttribute('data-rec-col');
          const cand = CANDIDATES.find(c => c.id === draggedId);
          if (!cand) return;
          if (stage === 'rejected') {
            openRejectModal(cand);
          } else {
            cand.stage = stage;
            render();
            orchidToast(`${cand.first} moved to ${STAGE_NAMES[stage]}`, 'success');
          }
        });
      });
    }

    function openRejectModal(cand) {
      $('[data-rec-reject-name]').textContent = `${cand.first} ${cand.last}`;
      const m = new bootstrap.Modal($('#recRejectModal'));
      m.show();
    }

    // Filters
    $('[data-rec-search]').addEventListener('input', debounce(e => { state.search = e.target.value; render(); }, 220));
    $('[data-rec-role]').addEventListener('change', e => { state.role = e.target.value; render(); });
    $('[data-rec-source]').addEventListener('change', e => { state.source = e.target.value; render(); });
    $('[data-rec-rating]').addEventListener('change', e => { state.minRating = Number(e.target.value) || 0; render(); });
    $('[data-rec-reset]').addEventListener('click', () => {
      state.search = ''; state.role = ''; state.source = ''; state.minRating = 0;
      $('[data-rec-search]').value = ''; $('[data-rec-role]').value = ''; $('[data-rec-source]').value = ''; $('[data-rec-rating]').value = '0';
      render();
      orchidToast('Filters cleared', 'info');
    });

    // Add candidate
    $('[data-rec-add-form]').addEventListener('submit', e => {
      e.preventDefault();
      orchidToast('Candidate added to Applied', 'success');
      bootstrap.Offcanvas.getInstance($('#recAddOffcanvas')).hide();
      e.target.reset();
    });

    $('[data-rec-reject-confirm]').addEventListener('click', () => {
      const m = bootstrap.Modal.getInstance($('#recRejectModal'));
      if (m) m.hide();
      orchidToast('Candidate rejected with feedback', 'danger');
    });

    setTimeout(render, 400);
    $('[data-rec-kanban]').innerHTML = skeletonKanban();
  }

  /* =====================================================
     PERFORMANCE
     ===================================================== */
  if (PAGE === 'performance') {
    const REVIEWS = EMPLOYEES.slice(0, 15).map((e, i) => {
      const statuses = ['pending','self-eval','manager-eval','calibration','complete'];
      const status = statuses[i % 5];
      const rating = (3.4 + ((i * 17) % 16) / 10);
      return {
        id:'R' + String(i+1).padStart(2, '0'), emp:e, cycle: i % 2 ? 'H1 2026' : 'H2 2026',
        reviewer: ['Alex Kim','Marcus Chen','James Doe','Fatima Al-Rashid'][i % 4],
        status, rating: Number(rating.toFixed(1)), due:'2026-08-' + String(10 + i).padStart(2,'0'),
        competencies:[
          { name:'Delivery',   score: 3.5 + (i%3)*0.5 },
          { name:'Collaboration', score: 3.8 + ((i+1)%3)*0.4 },
          { name:'Craft',      score: 3.6 + ((i+2)%3)*0.5 },
          { name:'Ownership',  score: 3.9 + (i%2)*0.3 },
          { name:'Impact',     score: 3.4 + ((i*2)%3)*0.4 }
        ],
        goals:[
          { name:'Ship v2 API', progress: 78 },
          { name:'Mentor 2 juniors', progress: 55 },
          { name:'Reduce p95 latency', progress: 92 }
        ],
        promote: i % 4 === 0
      };
    });

    // KPIs
    (function kpis() {
      $('[data-perf-kpi-reviews]').textContent = REVIEWS.length;
      const avg = (REVIEWS.reduce((s,r) => s + r.rating, 0) / REVIEWS.length).toFixed(1);
      $('[data-perf-kpi-avg]').textContent = avg;
      $('[data-perf-kpi-top]').textContent = REVIEWS.filter(r => r.rating >= 4.5).length;
      $('[data-perf-kpi-overdue]').textContent = 2;
    })();

    const state = { search:'', cycle:'', dept:'', status:'', minRating:0, selectedId:'R01' };

    function statusPill(s) {
      const map = { pending:['muted','Pending'], 'self-eval':['info','Self-eval'], 'manager-eval':['indigo','Manager-eval'], calibration:['warning','Calibration'], complete:['success','Complete'] };
      const [k, txt] = map[s] || ['muted', s];
      return `<span class="hr-status hr-status--${k}">${txt}</span>`;
    }

    function filtered() {
      let list = REVIEWS.slice();
      const q = state.search.toLowerCase();
      if (q) list = list.filter(r => (r.emp.first + ' ' + r.emp.last).toLowerCase().includes(q));
      if (state.cycle) list = list.filter(r => r.cycle === state.cycle);
      if (state.dept) list = list.filter(r => r.emp.dept === state.dept);
      if (state.status) list = list.filter(r => r.status === state.status);
      if (state.minRating) list = list.filter(r => r.rating >= state.minRating);
      return list;
    }

    function renderList() {
      const host = $('[data-perf-list]');
      const list = filtered();
      if (!list.length) { host.innerHTML = emptyState('No reviews'); return; }
      host.innerHTML = list.map(r => `
        <div class="perf-list-card ${r.id === state.selectedId ? 'is-selected' : ''}" data-perf-id="${r.id}" tabindex="0" role="button">
          <div class="perf-list-card__head">
            ${renderAvatar(r.emp)}
            <div class="perf-list-card__meta">
              <p class="perf-list-card__name">${esc(r.emp.first)} ${esc(r.emp.last)}</p>
              <span class="perf-list-card__sub">${esc(r.emp.dept)} · ${r.cycle} · Reviewer: ${esc(r.reviewer)}</span>
            </div>
            ${statusPill(r.status)}
          </div>
          <div class="perf-list-card__body">
            <span class="perf-list-card__rating">${renderStars(r.rating)}<span>${r.rating}</span></span>
            <small class="text-body-secondary">Due ${fmtDateShort(r.due)}</small>
            <button class="btn btn-sm btn-outline-primary" type="button" data-perf-open>Open</button>
          </div>
        </div>
      `).join('');
    }

    function renderDetail() {
      const r = REVIEWS.find(x => x.id === state.selectedId) || REVIEWS[0];
      const host = $('[data-perf-detail]');
      host.innerHTML = `
        <div class="d-flex align-items-center gap-3 mb-3">
          ${renderAvatar(r.emp, 'lg')}
          <div class="flex-grow-1">
            <h5 class="mb-0">${esc(r.emp.first)} ${esc(r.emp.last)}</h5>
            <small class="text-body-secondary">${esc(r.emp.role)} · ${esc(r.emp.dept)} · ${r.cycle}</small>
          </div>
          <div class="text-end">
            <div class="fw-bold h4 mb-0">${r.rating}</div>
            ${renderStars(r.rating)}
          </div>
        </div>

        <h6 class="mb-3">Competency scores</h6>
        ${r.competencies.map(c => `
          <div class="perf-comp">
            <div class="perf-comp__head"><span class="perf-comp__name">${c.name}</span><span class="perf-comp__score">${c.score.toFixed(1)}/5</span></div>
            <div class="hr-progress"><div class="hr-progress__bar ${c.score >= 4.5 ? 'hr-progress__bar--success' : c.score < 3.5 ? 'hr-progress__bar--warning' : ''}" data-w="${(c.score/5)*100}"></div></div>
          </div>
        `).join('')}

        <h6 class="mb-2 mt-3">360° feedback</h6>
        <div class="perf-360">
          <div class="perf-360__q"><strong>Strengths</strong><br>Strong technical judgment and mentorship of juniors.</div>
          <div class="perf-360__q"><strong>Growth area</strong><br>Could delegate more and coach earlier.</div>
          <div class="perf-360__q"><strong>Impact</strong><br>Owned API v2 rollout, reduced latency by 42%.</div>
        </div>

        <h6 class="mb-2">Goals</h6>
        ${r.goals.map(g => `
          <div class="perf-goal">
            <div class="perf-goal__head"><span class="perf-goal__name">${g.name}</span><span class="text-body-secondary small">${g.progress}%</span></div>
            <div class="hr-progress"><div class="hr-progress__bar" data-w="${g.progress}"></div></div>
          </div>
        `).join('')}

        <div class="d-flex align-items-center justify-content-between mt-3 p-3 hr-panel-muted">
          <div>
            <p class="mb-0 fw-semibold">Promotion recommendation</p>
            <small class="text-body-secondary">Nominate for next promotion cycle</small>
          </div>
          <div class="form-check form-switch m-0">
            <input class="form-check-input" type="checkbox" role="switch" id="perfPromo${r.id}" ${r.promote ? 'checked' : ''}>
          </div>
        </div>

        <h6 class="mb-2 mt-3">Comments</h6>
        <div class="mb-2 p-3 border rounded">
          <div class="d-flex align-items-center gap-2 mb-1"><span class="hr-avatar hr-avatar--sm">${initials(r.reviewer.split(' ')[0], r.reviewer.split(' ')[1])}</span><strong class="small">${esc(r.reviewer)}</strong><small class="text-body-secondary">2 days ago</small></div>
          <p class="mb-0 small text-body-secondary">Strong quarter. Ready for stretch assignment in H2.</p>
        </div>
        <textarea class="form-control" rows="2" placeholder="Add a comment…" data-perf-comment></textarea>
        <div class="d-flex gap-2 mt-2">
          <button class="btn btn-primary btn-sm" type="button" data-perf-save>Save review</button>
          <button class="btn btn-outline-secondary btn-sm" type="button" data-perf-complete>Mark complete</button>
        </div>
      `;
    }

    function render() { renderList(); renderDetail(); }

    // Delegated selection
    document.addEventListener('click', e => {
      const card = e.target.closest('[data-perf-id]');
      if (card) {
        state.selectedId = card.getAttribute('data-perf-id');
        render();
        return;
      }
      if (e.target.closest('[data-perf-save]')) orchidToast('Review saved', 'success');
      if (e.target.closest('[data-perf-complete]')) {
        const r = REVIEWS.find(x => x.id === state.selectedId);
        if (r) { r.status = 'complete'; render(); orchidToast('Review completed', 'success'); }
      }
    });

    $('[data-perf-search]').addEventListener('input', debounce(e => { state.search = e.target.value; renderList(); }, 220));
    $('[data-perf-cycle]').addEventListener('change', e => { state.cycle = e.target.value; renderList(); });
    $('[data-perf-dept]').addEventListener('change', e => { state.dept = e.target.value; renderList(); });
    $('[data-perf-status]').addEventListener('change', e => { state.status = e.target.value; renderList(); });
    $('[data-perf-rating]').addEventListener('change', e => { state.minRating = Number(e.target.value) || 0; renderList(); });
    $('[data-perf-reset]').addEventListener('click', () => {
      state.search = ''; state.cycle = ''; state.dept = ''; state.status = ''; state.minRating = 0;
      $('[data-perf-search]').value = ''; $('[data-perf-cycle]').value = ''; $('[data-perf-dept]').value = ''; $('[data-perf-status]').value = ''; $('[data-perf-rating]').value = '0';
      renderList();
      orchidToast('Filters cleared', 'info');
    });

    $('[data-perf-start-cycle]').addEventListener('click', () => {
      const m = new bootstrap.Modal($('#perfStartModal'));
      m.show();
    });
    $('[data-perf-start-confirm]').addEventListener('click', () => {
      const m = bootstrap.Modal.getInstance($('#perfStartModal'));
      if (m) m.hide();
      orchidToast('Started H2 2026 review cycle', 'success');
    });

    // Rating trend chart via Chart.js if available
    if (window.Chart) {
      const el = $('[data-perf-chart-trend]');
      if (el) {
        const ctx = el.getContext('2d');
        new Chart(ctx, {
          type:'line',
          data:{
            labels:['H1 22','H2 22','H1 23','H2 23','H1 24','H2 24','H1 25','H2 25','H1 26'],
            datasets:[
              { label:'Avg rating', data:[3.7,3.8,3.9,4.0,4.1,4.0,4.2,4.3,4.2], borderColor:'#4f46e5', backgroundColor:'rgba(79,70,229,.14)', tension:.35, fill:true },
              { label:'Top performer %', data:[12,14,15,16,18,20,22,24,27], borderColor:'#22d3ee', backgroundColor:'transparent', tension:.35, yAxisID:'y1' }
            ]
          },
          options:{
            responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ position:'bottom', labels:{ boxWidth:8, boxHeight:8, usePointStyle:true } } },
            scales:{ y:{ beginAtZero:false, min:3, max:5 }, y1:{ position:'right', beginAtZero:true, grid:{ drawOnChartArea:false } } }
          }
        });
      }
    }

    setTimeout(render, 400);
    $('[data-perf-list]').innerHTML = skeletonCards(4);
    $('[data-perf-detail]').innerHTML = '<div class="hr-empty"><i class="bi bi-clipboard-check"></i><h6>Select a review</h6><p class="mb-0">Choose a review from the list to view details.</p></div>';
  }

  /* Apply widths on progress bars driven via data-w */
  const applyWidths = () => {
    $$('[data-w]').forEach(el => { el.style.width = Number(el.getAttribute('data-w')) + '%'; el.removeAttribute('data-w'); });
  };
  const mo = new MutationObserver(() => applyWidths());
  mo.observe(document.body, { childList:true, subtree:true });

  /* ---------- Skeleton helpers ---------- */
  function skeletonGrid(n) {
    return Array.from({length:n}).map(() => `
      <div class="emp-card">
        <span class="emp-card__hero"></span>
        <div class="mt-3 d-flex justify-content-center"><span class="hr-skeleton hr-skeleton--circle"></span></div>
        <span class="hr-skeleton hr-skeleton--line hr-skeleton--w60 mx-auto mt-3 d-block"></span>
        <span class="hr-skeleton hr-skeleton--line hr-skeleton--w80 mx-auto d-block"></span>
      </div>
    `).join('');
  }
  function skeletonTable(rows, cols) {
    return `<table class="hr-table"><tbody>${
      Array.from({length:rows}).map(() =>
        `<tr>${Array.from({length:cols}).map(() => `<td><span class="hr-skeleton hr-skeleton--line hr-skeleton--w80"></span></td>`).join('')}</tr>`
      ).join('')
    }</tbody></table>`;
  }
  function skeletonCards(n) {
    return Array.from({length:n}).map(() => `
      <div class="leave-card">
        <div class="d-flex align-items-center gap-2 mb-2"><span class="hr-skeleton hr-skeleton--circle"></span><span class="hr-skeleton hr-skeleton--line hr-skeleton--w40"></span></div>
        <span class="hr-skeleton hr-skeleton--line hr-skeleton--w80"></span>
        <span class="hr-skeleton hr-skeleton--line hr-skeleton--w60"></span>
      </div>
    `).join('');
  }
  function skeletonKanban() {
    return Array.from({length:6}).map(() => `
      <div class="rec-col">
        <div class="rec-col__head"><span class="hr-skeleton hr-skeleton--line hr-skeleton--w60"></span></div>
        ${skeletonCards(2)}
      </div>
    `).join('');
  }
  function emptyState(label) {
    return `<div class="hr-empty"><i class="bi bi-inbox"></i><h6>${label}</h6><p class="mb-0">Try adjusting your filters.</p></div>`;
  }
})();
