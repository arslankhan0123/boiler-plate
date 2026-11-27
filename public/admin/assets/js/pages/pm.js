/* =====================================================
   Orchid — Project Management shared toolkit
   ===================================================== */
(function () {
  'use strict';

  const PM = (window.PM = window.PM || {});

  /* ---------- Toast helper ---------- */
  PM.orchidToast = function (message, variant) {
    variant = variant || 'primary';
    let host = document.getElementById('pmToastHost');
    if (!host) {
      host = document.createElement('div');
      host.id = 'pmToastHost';
      host.className = 'toast-container position-fixed bottom-0 end-0 p-3';
      host.style.zIndex = '1090';
      document.body.appendChild(host);
    }
    const el = document.createElement('div');
    el.className = 'toast align-items-center text-bg-' + variant + ' border-0';
    el.setAttribute('role', 'status');
    el.setAttribute('aria-live', 'polite');
    el.innerHTML =
      '<div class="d-flex">' +
        '<div class="toast-body">' + PM.escape(message) + '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
      '</div>';
    host.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 2600 });
    t.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  };

  PM.escape = function (str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  };

  /* ---------- Date helpers ---------- */
  const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  const MONTHS_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const DAYS_SHORT = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

  PM.MONTHS = MONTHS;
  PM.MONTHS_SHORT = MONTHS_SHORT;
  PM.DAYS_SHORT = DAYS_SHORT;

  PM.fmtDate = function (d) {
    if (!(d instanceof Date)) d = new Date(d);
    return MONTHS_SHORT[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
  };
  PM.fmtDateShort = function (d) {
    if (!(d instanceof Date)) d = new Date(d);
    return MONTHS_SHORT[d.getMonth()] + ' ' + d.getDate();
  };
  PM.fmtTime = function (d) {
    if (!(d instanceof Date)) d = new Date(d);
    let h = d.getHours();
    const m = String(d.getMinutes()).padStart(2, '0');
    const am = h < 12 ? 'AM' : 'PM';
    h = h % 12 || 12;
    return h + ':' + m + ' ' + am;
  };
  PM.relativeTime = function (d) {
    if (!(d instanceof Date)) d = new Date(d);
    const diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.round(diff/60) + 'm ago';
    if (diff < 86400) return Math.round(diff/3600) + 'h ago';
    if (diff < 86400 * 7) return Math.round(diff/86400) + 'd ago';
    return PM.fmtDate(d);
  };
  PM.daysInMonth = function (year, month) { return new Date(year, month + 1, 0).getDate(); };
  PM.startOfDay = function (d) { const x = new Date(d); x.setHours(0,0,0,0); return x; };
  PM.sameDay = function (a, b) {
    a = new Date(a); b = new Date(b);
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
  };
  PM.addDays = function (d, n) { const x = new Date(d); x.setDate(x.getDate() + n); return x; };
  PM.dateISO = function (d) {
    if (!(d instanceof Date)) d = new Date(d);
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + day;
  };

  /* Returns 42 (6-week) grid Dates starting Sun for given month */
  PM.getMonthGrid = function (year, month) {
    const first = new Date(year, month, 1);
    const startOffset = first.getDay(); // 0..6 Sun..Sat
    const start = PM.addDays(first, -startOffset);
    const cells = [];
    for (let i = 0; i < 42; i++) cells.push(PM.addDays(start, i));
    return cells;
  };

  /* ---------- Palettes ---------- */
  PM.PROJECT_COLORS = {
    'Aurora Mobile App v2': { bg: 'rgba(79,70,229,.12)', color: '#4f46e5' },
    'Website Redesign Q3':  { bg: 'rgba(34,211,238,.14)', color: '#0891b2' },
    'Payments API Migration': { bg: 'rgba(234,88,12,.14)', color: '#c2410c' },
    'Onboarding Revamp':    { bg: 'rgba(16,185,129,.14)', color: '#047857' },
    'Titan Media Rollout':  { bg: 'rgba(217,70,239,.14)', color: '#a21caf' }
  };

  PM.TEAM_GRADIENTS = {
    Engineering: 'linear-gradient(135deg,#6366f1,#22d3ee)',
    Design:      'linear-gradient(135deg,#ec4899,#f59e0b)',
    Product:     'linear-gradient(135deg,#10b981,#22d3ee)',
    Marketing:   'linear-gradient(135deg,#f43f5e,#f97316)',
    Sales:       'linear-gradient(135deg,#0ea5e9,#6366f1)',
    'People Ops':'linear-gradient(135deg,#8b5cf6,#ec4899)',
    Data:        'linear-gradient(135deg,#14b8a6,#0ea5e9)',
    Support:     'linear-gradient(135deg,#f59e0b,#ef4444)'
  };

  PM.CATEGORY_COLORS = {
    Meeting:    '#4f46e5',
    Deadline:   '#dc2626',
    'Focus Time': '#0891b2',
    Personal:   '#8b5cf6',
    Company:    '#f59e0b'
  };

  PM.avatarBg = ['#6366f1','#0891b2','#ec4899','#10b981','#f59e0b','#8b5cf6','#ef4444','#14b8a6','#0ea5e9','#f43f5e'];
  PM.colorForKey = function (key) {
    let h = 0; key = String(key || '');
    for (let i = 0; i < key.length; i++) h = (h * 31 + key.charCodeAt(i)) | 0;
    return PM.avatarBg[Math.abs(h) % PM.avatarBg.length];
  };
  PM.initials = function (name) {
    return String(name || '').trim().split(/\s+/).map(s => s[0] || '').slice(0, 2).join('').toUpperCase();
  };

  /* ---------- Shared datasets ---------- */
  PM.USERS = [
    { id: 'u1', name: 'Alex Kim',        role: 'Product Manager',      email: 'alex@orchid.io' },
    { id: 'u2', name: 'Sarah Miller',    role: 'Senior Designer',      email: 'sarah@orchid.io' },
    { id: 'u3', name: 'James Doe',       role: 'Staff Engineer',       email: 'james@orchid.io' },
    { id: 'u4', name: 'Ava Lee',         role: 'Frontend Engineer',    email: 'ava@orchid.io' },
    { id: 'u5', name: 'Ryan Green',      role: 'Backend Engineer',     email: 'ryan@orchid.io' },
    { id: 'u6', name: 'Emma Watson',     role: 'UX Researcher',        email: 'emma@orchid.io' },
    { id: 'u7', name: 'Noah Patel',      role: 'DevOps',               email: 'noah@orchid.io' },
    { id: 'u8', name: 'Mia Chen',        role: 'Data Analyst',         email: 'mia@orchid.io' },
    { id: 'u9', name: 'Liam O\'Brien',   role: 'iOS Engineer',         email: 'liam@orchid.io' },
    { id: 'u10',name: 'Sofia Rossi',     role: 'Content Lead',         email: 'sofia@orchid.io' },
    { id: 'u11',name: 'Ananya Rao',      role: 'Engineering Lead',     email: 'ananya@orchid.io' },
    { id: 'u12',name: 'Marcus Weber',    role: 'QA Engineer',          email: 'marcus@orchid.io' },
    { id: 'u13',name: 'Chloe Dubois',    role: 'Marketing Manager',    email: 'chloe@orchid.io' },
    { id: 'u14',name: 'Diego Alvarez',   role: 'Sales Executive',      email: 'diego@orchid.io' },
    { id: 'u15',name: 'Yuki Tanaka',     role: 'Data Scientist',       email: 'yuki@orchid.io' },
    { id: 'u16',name: 'Priya Sharma',    role: 'People Ops',           email: 'priya@orchid.io' }
  ];
  PM.USERS.forEach(u => { u.initials = PM.initials(u.name); u.color = PM.colorForKey(u.id); });

  PM.userById = function (id) { return PM.USERS.find(u => u.id === id); };

  PM.PROJECTS = [
    { id: 'p1', name: 'Aurora Mobile App v2' },
    { id: 'p2', name: 'Website Redesign Q3' },
    { id: 'p3', name: 'Payments API Migration' },
    { id: 'p4', name: 'Onboarding Revamp' },
    { id: 'p5', name: 'Titan Media Rollout' }
  ];

  /* ---------- Render helpers ---------- */
  PM.renderAvatar = function (user, size) {
    if (!user) return '';
    size = size || 26;
    const style = 'width:' + size + 'px;height:' + size + 'px;background:' + user.color + ';';
    return '<span class="pm-avatar" data-pm-avatar="' + user.id + '" title="' + PM.escape(user.name) +
      '" style="' + style + '">' + PM.escape(user.initials) + '</span>';
  };
  PM.renderAvatarStack = function (users, max) {
    max = max || 3;
    const shown = users.slice(0, max);
    let html = '<span class="pm-avatar-stack">';
    shown.forEach(u => html += PM.renderAvatar(u));
    if (users.length > max) {
      html += '<span class="pm-avatar" style="background:#94a3b8">+' + (users.length - max) + '</span>';
    }
    html += '</span>';
    return html;
  };

  /* Confirm modal (shared, injected on demand) */
  PM.confirm = function (opts) {
    return new Promise(resolve => {
      const id = 'pmConfirmModal';
      let modal = document.getElementById(id);
      if (!modal) {
        modal = document.createElement('div');
        modal.id = id;
        modal.className = 'modal fade';
        modal.tabIndex = -1;
        modal.innerHTML =
          '<div class="modal-dialog modal-dialog-centered">' +
            '<div class="modal-content">' +
              '<div class="modal-header"><h5 class="modal-title" data-pm-confirm-title></h5>' +
              '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>' +
              '<div class="modal-body"><p data-pm-confirm-msg class="mb-0"></p></div>' +
              '<div class="modal-footer">' +
                '<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" data-pm-confirm-cancel>Cancel</button>' +
                '<button type="button" class="btn btn-danger btn-sm" data-pm-confirm-ok>Confirm</button>' +
              '</div>' +
            '</div>' +
          '</div>';
        document.body.appendChild(modal);
      }
      modal.querySelector('[data-pm-confirm-title]').textContent = opts.title || 'Confirm action';
      modal.querySelector('[data-pm-confirm-msg]').textContent = opts.message || 'Are you sure?';
      const okBtn = modal.querySelector('[data-pm-confirm-ok]');
      okBtn.textContent = opts.okText || 'Confirm';
      okBtn.className = 'btn btn-sm btn-' + (opts.okVariant || 'danger');

      const bs = bootstrap.Modal.getOrCreateInstance(modal);
      let done = false;
      const onOk = () => { done = true; bs.hide(); resolve(true); };
      const onHide = () => {
        okBtn.removeEventListener('click', onOk);
        modal.removeEventListener('hidden.bs.modal', onHide);
        if (!done) resolve(false);
      };
      okBtn.addEventListener('click', onOk);
      modal.addEventListener('hidden.bs.modal', onHide);
      bs.show();
    });
  };

  /* Debounce */
  PM.debounce = function (fn, wait) {
    let t;
    return function () {
      const args = arguments, ctx = this;
      clearTimeout(t);
      t = setTimeout(() => fn.apply(ctx, args), wait);
    };
  };

  /* Common footer year */
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-orchid-year]').forEach(el => el.textContent = new Date().getFullYear());
  });

})();
