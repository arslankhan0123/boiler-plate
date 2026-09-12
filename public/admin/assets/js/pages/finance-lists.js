/* =====================================================
   Orchid — Finance Lists shared toolkit + per-page controllers.
   Namespaces: window.FinanceLists (shared); page bootstraps per-file.
   ===================================================== */
(function () {
  'use strict';

  /* -------- helpers -------- */
  function escapeHtml(v) {
    if (v == null) return '';
    return String(v)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }
  function fmtMoney(n, ccy) {
    const c = ccy || 'USD';
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
  function fmtNumber(n) {
    return new Intl.NumberFormat('en-US').format(n);
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
  function debounce(fn, wait) {
    let t;
    return function () {
      const ctx = this, args = arguments;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(ctx, args); }, wait);
    };
  }
  function initials(name) {
    if (!name) return '?';
    return name.trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase();
  }
  const AVATAR_COLORS = [
    ['bg-primary-subtle', 'text-primary'],
    ['bg-info-subtle',    'text-info'],
    ['bg-success-subtle', 'text-success'],
    ['bg-warning-subtle', 'text-warning'],
    ['bg-danger-subtle',  'text-danger'],
    ['bg-purple-subtle',  'text-purple']
  ];
  function avatarClass(name) {
    let hash = 0;
    for (let i = 0; i < (name || '').length; i++) hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
    const c = AVATAR_COLORS[hash % AVATAR_COLORS.length];
    return c.join(' ');
  }

  /* -------- toast -------- */
  function ensureToastWrap() {
    let wrap = document.querySelector('[data-finance-toast-wrap]');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.className = 'finance-toast-wrap';
      wrap.setAttribute('data-finance-toast-wrap', '');
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
    el.className = 'finance-toast finance-toast--' + kind;
    el.innerHTML =
      '<div class="finance-toast__icon"><i class="bi bi-' + iconMap[kind] + '"></i></div>' +
      '<div class="finance-toast__body">' +
        (options.title ? '<strong>' + escapeHtml(options.title) + '</strong>' : '') +
        escapeHtml(msg) +
      '</div>' +
      '<button class="finance-toast__close" type="button" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>';
    wrap.appendChild(el);
    const close = () => { el.style.opacity = '0'; el.style.transform = 'translateY(-4px)'; setTimeout(() => el.remove(), 200); };
    el.querySelector('.finance-toast__close').addEventListener('click', close);
    setTimeout(close, options.duration || 4000);
  }

  /* -------- category palettes -------- */
  const EXPENSE_CATEGORIES = {
    travel:        { label: 'Travel',        color: '#0ea5e9' },
    meals:         { label: 'Meals',         color: '#f59e0b' },
    software:      { label: 'Software',      color: '#4f46e5' },
    office:        { label: 'Office',        color: '#8b5cf6' },
    marketing:     { label: 'Marketing',     color: '#ec4899' },
    training:      { label: 'Training',      color: '#14b8a6' },
    miscellaneous: { label: 'Miscellaneous', color: '#6b7385' }
  };
  const INCOME_SOURCES = {
    subscriptions: { label: 'Subscriptions', color: '#4f46e5' },
    services:      { label: 'Services',      color: '#0ea5e9' },
    products:      { label: 'Products',      color: '#16a34a' },
    consulting:    { label: 'Consulting',    color: '#f59e0b' },
    refunds:       { label: 'Refunds',       color: '#dc2626' }
  };
  const BUDGET_CATEGORIES = [
    { key: 'saas',       label: 'SaaS Subscriptions', icon: 'cloud',           color: '#4f46e5' },
    { key: 'payroll',    label: 'Payroll',            icon: 'cash-stack',      color: '#16a34a' },
    { key: 'marketing',  label: 'Marketing',          icon: 'megaphone',       color: '#ec4899' },
    { key: 'travel',     label: 'Travel',             icon: 'airplane',        color: '#0ea5e9' },
    { key: 'office',     label: 'Office',             icon: 'building',        color: '#8b5cf6' },
    { key: 'cloud',      label: 'Cloud Infra',        icon: 'server',          color: '#0ea5e9' },
    { key: 'legal',      label: 'Legal',              icon: 'briefcase',       color: '#8b5cf6' },
    { key: 'training',   label: 'Training',           icon: 'mortarboard',     color: '#14b8a6' },
    { key: 'insurance',  label: 'Insurance',          icon: 'shield-check',    color: '#f59e0b' },
    { key: 'contractors',label: 'Contractors',        icon: 'person-workspace',color: '#6b7385' }
  ];

  /* -------- chart-of-accounts dataset -------- */
  const COA = [
    { id: 'a1000', code: '1000', name: 'Cash & Equivalents',  group: 'Assets',      sub: 'Current Assets',  balance: 148250.00, type: 'debit' },
    { id: 'a1010', code: '1010', name: 'Bank Account',        group: 'Assets',      sub: 'Current Assets',  balance: 82430.15,  type: 'debit' },
    { id: 'a1020', code: '1020', name: 'Petty Cash',          group: 'Assets',      sub: 'Current Assets',  balance: 1250.00,   type: 'debit' },
    { id: 'a1100', code: '1100', name: 'Accounts Receivable', group: 'Assets',      sub: 'Current Assets',  balance: 62890.50,  type: 'debit' },
    { id: 'a1200', code: '1200', name: 'Inventory',           group: 'Assets',      sub: 'Current Assets',  balance: 24560.20,  type: 'debit' },
    { id: 'a1500', code: '1500', name: 'Equipment',           group: 'Assets',      sub: 'Fixed Assets',    balance: 89200.00,  type: 'debit' },
    { id: 'a1510', code: '1510', name: 'Accumulated Depreciation', group: 'Assets', sub: 'Fixed Assets',    balance: -18420.00, type: 'debit' },
    { id: 'a2000', code: '2000', name: 'Accounts Payable',    group: 'Liabilities', sub: 'Current Liabilities', balance: -32410.00, type: 'credit' },
    { id: 'a2100', code: '2100', name: 'Credit Card Payable', group: 'Liabilities', sub: 'Current Liabilities', balance: -8560.42,  type: 'credit' },
    { id: 'a2200', code: '2200', name: 'Sales Tax Payable',   group: 'Liabilities', sub: 'Current Liabilities', balance: -4820.00,  type: 'credit' },
    { id: 'a2500', code: '2500', name: 'Bank Loan',           group: 'Liabilities', sub: 'Long-term Liabilities', balance: -125000.00, type: 'credit' },
    { id: 'a3000', code: '3000', name: 'Common Stock',        group: 'Equity',      sub: 'Equity',          balance: -50000.00, type: 'credit' },
    { id: 'a3100', code: '3100', name: 'Retained Earnings',   group: 'Equity',      sub: 'Equity',          balance: -128450.00,type: 'credit' },
    { id: 'a4000', code: '4000', name: 'Subscription Revenue',group: 'Revenue',     sub: 'Operating Revenue', balance: -284500.00, type: 'credit' },
    { id: 'a4010', code: '4010', name: 'Services Revenue',    group: 'Revenue',     sub: 'Operating Revenue', balance: -96420.00,  type: 'credit' },
    { id: 'a4020', code: '4020', name: 'Consulting Revenue',  group: 'Revenue',     sub: 'Operating Revenue', balance: -42800.00,  type: 'credit' },
    { id: 'a5000', code: '5000', name: 'Cost of Goods Sold',  group: 'Expenses',    sub: 'COGS',            balance: 68420.00,  type: 'debit' },
    { id: 'a6000', code: '6000', name: 'Salaries & Wages',    group: 'Expenses',    sub: 'Operating Expenses', balance: 148200.00, type: 'debit' },
    { id: 'a6010', code: '6010', name: 'Rent',                group: 'Expenses',    sub: 'Operating Expenses', balance: 24600.00,  type: 'debit' },
    { id: 'a6020', code: '6020', name: 'Utilities',           group: 'Expenses',    sub: 'Operating Expenses', balance: 6280.00,   type: 'debit' },
    { id: 'a6030', code: '6030', name: 'Software & SaaS',     group: 'Expenses',    sub: 'Operating Expenses', balance: 14820.00,  type: 'debit' },
    { id: 'a6040', code: '6040', name: 'Marketing',           group: 'Expenses',    sub: 'Operating Expenses', balance: 22450.00,  type: 'debit' },
    { id: 'a6050', code: '6050', name: 'Travel & Entertainment', group: 'Expenses', sub: 'Operating Expenses', balance: 8940.00,   type: 'debit' }
  ];

  /* -------- transactions dataset -------- */
  const CLIENTS = ['Vertex Robotics', 'Meridian Health', 'Aurora Labs', 'Northwind Analytics', 'Helios Media', 'Kepler Systems', 'Orion Freight', 'Sable & Co.', 'Zenith Studios', 'Cobalt Retail'];
  const ACCOUNTS = ['Chase ***4821', 'Stripe payout', 'PayPal biz', 'Wells Fargo ***1054', 'Amex Corporate'];
  const TXN_CATEGORIES = ['Revenue', 'Payroll', 'SaaS', 'Marketing', 'Travel', 'Office', 'Cloud', 'Legal', 'Refund', 'Interest'];

  function makeTransactions() {
    const seed = [
      { date:'2026-07-22T09:14', desc:'Stripe payout — subscriptions', account:'Stripe payout',     category:'Revenue',   type:'in',  amount: 12480.00, reconciled: true  },
      { date:'2026-07-22T10:02', desc:'AWS monthly billing',           account:'Amex Corporate',    category:'Cloud',     type:'out', amount: 3420.75,  reconciled: true  },
      { date:'2026-07-21T16:47', desc:'Vertex Robotics — INV-2841',    account:'Chase ***4821',     category:'Revenue',   type:'in',  amount: 8420.00,  reconciled: true  },
      { date:'2026-07-21T11:30', desc:'Google Workspace',              account:'Amex Corporate',    category:'SaaS',      type:'out', amount: 216.00,   reconciled: true  },
      { date:'2026-07-20T14:22', desc:'Payroll batch #189',            account:'Chase ***4821',     category:'Payroll',   type:'out', amount: 38240.00, reconciled: true  },
      { date:'2026-07-20T10:11', desc:'Meridian Health — INV-2839',    account:'Chase ***4821',     category:'Revenue',   type:'in',  amount: 5600.00,  reconciled: true  },
      { date:'2026-07-19T13:05', desc:'Facebook Ads — July',           account:'Amex Corporate',    category:'Marketing', type:'out', amount: 4200.00,  reconciled: false },
      { date:'2026-07-19T09:44', desc:'Aurora Labs — INV-2837',        account:'PayPal biz',        category:'Revenue',   type:'in',  amount: 3250.00,  reconciled: false },
      { date:'2026-07-18T17:20', desc:'Office lease — July',           account:'Chase ***4821',     category:'Office',    type:'out', amount: 4100.00,  reconciled: true  },
      { date:'2026-07-18T12:00', desc:'Northwind Analytics — retainer',account:'Chase ***4821',     category:'Revenue',   type:'in',  amount: 6800.00,  reconciled: true  },
      { date:'2026-07-17T15:12', desc:'Figma team subscription',       account:'Amex Corporate',    category:'SaaS',      type:'out', amount: 540.00,   reconciled: true  },
      { date:'2026-07-17T09:00', desc:'Helios Media — INV-2833',       account:'Stripe payout',     category:'Revenue',   type:'in',  amount: 4820.00,  reconciled: false },
      { date:'2026-07-16T18:30', desc:'Delta Airlines — SF client trip',account:'Amex Corporate',    category:'Travel',    type:'out', amount: 1240.60,  reconciled: false },
      { date:'2026-07-16T10:44', desc:'Kepler Systems — INV-2830',     account:'Chase ***4821',     category:'Revenue',   type:'in',  amount: 9200.00,  reconciled: true  },
      { date:'2026-07-15T14:00', desc:'Slack Enterprise',              account:'Amex Corporate',    category:'SaaS',      type:'out', amount: 828.00,   reconciled: true  },
      { date:'2026-07-15T11:22', desc:'Orion Freight — INV-2828',      account:'Chase ***4821',     category:'Revenue',   type:'in',  amount: 3450.00,  reconciled: true  },
      { date:'2026-07-14T16:00', desc:'LinkedIn Ads',                  account:'Amex Corporate',    category:'Marketing', type:'out', amount: 1820.00,  reconciled: false },
      { date:'2026-07-14T09:18', desc:'Sable & Co. — retainer',        account:'Chase ***4821',     category:'Revenue',   type:'in',  amount: 4200.00,  reconciled: true  },
      { date:'2026-07-13T12:44', desc:'Legal consultation — Baker LLP',account:'Chase ***4821',     category:'Legal',     type:'out', amount: 2600.00,  reconciled: true  },
      { date:'2026-07-13T09:32', desc:'Zenith Studios — INV-2820',     account:'Stripe payout',     category:'Revenue',   type:'in',  amount: 7420.00,  reconciled: true  },
      { date:'2026-07-12T15:11', desc:'Notion Team',                   account:'Amex Corporate',    category:'SaaS',      type:'out', amount: 96.00,    reconciled: true  },
      { date:'2026-07-12T10:00', desc:'Cobalt Retail — INV-2818',      account:'Chase ***4821',     category:'Revenue',   type:'in',  amount: 12800.00, reconciled: true  },
      { date:'2026-07-11T16:20', desc:'Uber for business',             account:'Amex Corporate',    category:'Travel',    type:'out', amount: 342.85,   reconciled: false },
      { date:'2026-07-11T09:14', desc:'Vertex Robotics — INV-2815',    account:'Chase ***4821',     category:'Revenue',   type:'in',  amount: 5200.00,  reconciled: true  },
      { date:'2026-07-10T13:44', desc:'Refund — INV-2790',             account:'Stripe payout',     category:'Refund',    type:'out', amount: 480.00,   reconciled: true  },
      { date:'2026-07-10T09:00', desc:'Payroll batch #188',            account:'Chase ***4821',     category:'Payroll',   type:'out', amount: 38240.00, reconciled: true  },
      { date:'2026-07-09T14:20', desc:'GitHub Enterprise',             account:'Amex Corporate',    category:'SaaS',      type:'out', amount: 420.00,   reconciled: true  },
      { date:'2026-07-09T11:00', desc:'Aurora Labs — INV-2811',        account:'Chase ***4821',     category:'Revenue',   type:'in',  amount: 6420.00,  reconciled: true  },
      { date:'2026-07-08T15:50', desc:'HubSpot Marketing',             account:'Amex Corporate',    category:'Marketing', type:'out', amount: 890.00,   reconciled: false },
      { date:'2026-07-08T10:32', desc:'Bank interest',                 account:'Chase ***4821',     category:'Interest',  type:'in',  amount: 128.40,   reconciled: true  },
      { date:'2026-07-07T17:00', desc:'Office cleaning contract',      account:'Chase ***4821',     category:'Office',    type:'out', amount: 620.00,   reconciled: true  },
      { date:'2026-07-07T12:12', desc:'Meridian Health — INV-2805',    account:'Chase ***4821',     category:'Revenue',   type:'in',  amount: 4800.00,  reconciled: true  }
    ];
    // running balance
    let bal = 156420.00;
    return seed.slice().reverse().map(t => {
      bal += (t.type === 'in' ? t.amount : -t.amount);
      return Object.assign({}, t, { balance: bal });
    }).reverse();
  }

  /* -------- expose -------- */
  window.FinanceLists = {
    escapeHtml, fmtMoney, fmtCompactMoney, fmtNumber, fmtDate, fmtDateTime,
    debounce, initials, avatarClass, orchidToast,
    EXPENSE_CATEGORIES, INCOME_SOURCES, BUDGET_CATEGORIES,
    COA, makeTransactions
  };

  /* -------- year token + declarative fill bars -------- */
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-orchid-year]').forEach(el => el.textContent = new Date().getFullYear());
    document.querySelectorAll('[data-finance-fill]').forEach(function (el) {
      const pct = Math.max(0, Math.min(100, parseFloat(el.getAttribute('data-finance-fill')) || 0));
      el.style.width = pct + '%';
    });
  });

  /* ========================================================
     ACCOUNTING PAGE
     ======================================================== */
  document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-finance-page="accounting"]');
    if (!root) return;

    const tree = root.querySelector('[data-acct-tree]');
    const headName = root.querySelector('[data-acct-head-name]');
    const headCode = root.querySelector('[data-acct-head-code]');
    const headBal  = root.querySelector('[data-acct-head-balance]');
    const debitVal = root.querySelector('[data-acct-debit-value]');
    const creditVal= root.querySelector('[data-acct-credit-value]');
    const debitFill= root.querySelector('[data-acct-debit-fill]');
    const creditFill=root.querySelector('[data-acct-credit-fill]');
    const jbody    = root.querySelector('[data-acct-journal-body]');
    const searchEl = root.querySelector('[data-acct-search]');
    const jSearch  = root.querySelector('[data-acct-journal-search]');

    // journal entries (per-account samples)
    const JE_TEMPLATES = [
      { date:'2026-07-22', desc:'Client payment — Vertex Robotics', ref:'INV-2841', debit:8420.00, credit:0    },
      { date:'2026-07-22', desc:'AWS monthly billing',              ref:'BILL-1122', debit:0,      credit:3420.75 },
      { date:'2026-07-21', desc:'Payroll batch #189',               ref:'PAY-189',  debit:0,      credit:38240.00 },
      { date:'2026-07-20', desc:'Bank interest earned',             ref:'BNK-0087', debit:128.40, credit:0    },
      { date:'2026-07-19', desc:'Office lease — July',              ref:'RENT-07',  debit:0,      credit:4100.00  },
      { date:'2026-07-18', desc:'Stripe payout — subscriptions',    ref:'STR-8422', debit:12480.00,credit:0   },
      { date:'2026-07-17', desc:'Facebook Ads — July',              ref:'FB-1044',  debit:0,      credit:4200.00  },
      { date:'2026-07-16', desc:'Meridian Health — INV-2839',       ref:'INV-2839', debit:5600.00,credit:0    },
      { date:'2026-07-15', desc:'Google Workspace',                 ref:'GW-0721',  debit:0,      credit:216.00   },
      { date:'2026-07-14', desc:'Aurora Labs — INV-2837',           ref:'INV-2837', debit:3250.00,credit:0    },
      { date:'2026-07-13', desc:'Legal — Baker LLP',                ref:'LGL-2201', debit:0,      credit:2600.00  },
      { date:'2026-07-12', desc:'Northwind — retainer',             ref:'RET-9911', debit:6800.00,credit:0    },
      { date:'2026-07-11', desc:'Sable & Co. — retainer',           ref:'RET-9910', debit:4200.00,credit:0    },
      { date:'2026-07-10', desc:'Uber for business',                ref:'UBR-4491', debit:0,      credit:342.85   },
      { date:'2026-07-09', desc:'Zenith Studios — INV-2820',        ref:'INV-2820', debit:7420.00,credit:0    },
      { date:'2026-07-08', desc:'GitHub Enterprise',                ref:'GHE-0721', debit:0,      credit:420.00   },
      { date:'2026-07-07', desc:'Cobalt Retail — INV-2818',         ref:'INV-2818', debit:12800.00,credit:0   },
      { date:'2026-07-06', desc:'HubSpot Marketing',                ref:'HUB-0611', debit:0,      credit:890.00   },
      { date:'2026-07-05', desc:'Helios Media — INV-2833',          ref:'INV-2833', debit:4820.00,credit:0    },
      { date:'2026-07-04', desc:'Slack Enterprise',                 ref:'SLK-0721', debit:0,      credit:828.00   },
      { date:'2026-07-03', desc:'Kepler Systems — INV-2830',        ref:'INV-2830', debit:9200.00,credit:0    },
      { date:'2026-07-02', desc:'Delta Airlines — SF trip',         ref:'DL-8891',  debit:0,      credit:1240.60  },
      { date:'2026-07-01', desc:'Vertex Robotics — INV-2815',       ref:'INV-2815', debit:5200.00,credit:0    },
      { date:'2026-06-30', desc:'Office cleaning',                  ref:'CLN-0630', debit:0,      credit:620.00   },
      { date:'2026-06-29', desc:'Meridian Health — INV-2805',       ref:'INV-2805', debit:4800.00,credit:0    },
      { date:'2026-06-28', desc:'Payroll batch #188',               ref:'PAY-188',  debit:0,      credit:38240.00 },
      { date:'2026-06-27', desc:'Aurora Labs — INV-2811',           ref:'INV-2811', debit:6420.00,credit:0    },
      { date:'2026-06-26', desc:'Notion Team',                      ref:'NOT-0626', debit:0,      credit:96.00    },
      { date:'2026-06-25', desc:'Orion Freight — INV-2828',         ref:'INV-2828', debit:3450.00,credit:0    },
      { date:'2026-06-24', desc:'LinkedIn Ads',                     ref:'LNK-0624', debit:0,      credit:1820.00  }
    ];

    let currentAccountId = 'a1010';
    let jFilter = '';

    function groupCoa() {
      const groups = {};
      COA.forEach(a => {
        if (!groups[a.group]) groups[a.group] = [];
        groups[a.group].push(a);
      });
      return groups;
    }
    function groupTotal(list) { return list.reduce((s, a) => s + a.balance, 0); }
    function balanceChipCls(bal) {
      if (bal > 0) return 'acct-balance-chip acct-balance-chip--positive';
      if (bal < 0) return 'acct-balance-chip acct-balance-chip--negative';
      return 'acct-balance-chip';
    }

    function renderTree() {
      const groups = groupCoa();
      const q = (searchEl?.value || '').trim().toLowerCase();
      const html = Object.keys(groups).map(g => {
        const items = groups[g].filter(a =>
          !q ||
          a.name.toLowerCase().includes(q) ||
          a.code.includes(q) ||
          a.sub.toLowerCase().includes(q)
        );
        if (!items.length) return '';
        const total = groupTotal(items);
        return (
          '<div class="acct-tree__group" data-acct-group="' + g + '">' +
            '<div class="acct-tree__group-head" data-acct-toggle>' +
              '<div class="d-flex align-items-center gap-2">' +
                '<i class="bi bi-chevron-down acct-caret"></i>' +
                '<span>' + escapeHtml(g) + '</span>' +
                '<span class="badge bg-secondary-subtle text-secondary">' + items.length + '</span>' +
              '</div>' +
              '<span class="' + balanceChipCls(total) + ' finance-mono">' + fmtMoney(Math.abs(total)) + '</span>' +
            '</div>' +
            '<ul class="acct-tree__list">' +
              items.map(a =>
                '<li class="acct-tree__item ' + (a.id === currentAccountId ? 'is-active' : '') + '" data-acct-select="' + a.id + '" draggable="true">' +
                  '<div class="d-flex align-items-center gap-1 flex-grow-1 overflow-hidden">' +
                    '<i class="bi bi-grip-vertical acct-tree__handle" aria-label="Drag"></i>' +
                    '<span class="acct-tree__code">' + a.code + '</span>' +
                    '<span class="acct-tree__name text-truncate">' + escapeHtml(a.name) + '</span>' +
                  '</div>' +
                  '<span class="' + balanceChipCls(a.balance) + ' finance-mono">' + fmtMoney(Math.abs(a.balance)) + '</span>' +
                '</li>'
              ).join('') +
            '</ul>' +
          '</div>'
        );
      }).join('');
      tree.innerHTML = html || '<div class="finance-empty"><div class="finance-empty__icon"><i class="bi bi-search"></i></div><h6>No accounts found</h6><p>Try a different search term.</p></div>';
    }

    function renderAccountDetail() {
      const acct = COA.find(a => a.id === currentAccountId);
      if (!acct) return;
      headName.textContent = acct.name;
      headCode.textContent = 'Account · ' + acct.code + ' · ' + acct.sub;
      headBal.textContent  = fmtMoney(Math.abs(acct.balance));
      headBal.className    = 'acct-account-head__balance finance-mono ' + (acct.balance >= 0 ? 'text-success' : 'text-danger');
      // debit/credit totals (deterministic seed by acct id)
      const hashSeed = acct.id.charCodeAt(1) + acct.id.charCodeAt(2);
      const je = JE_TEMPLATES.map((j, i) => (i + hashSeed) % 2 === 0 ? Object.assign({}, j, { debit: j.debit, credit: j.credit }) : Object.assign({}, j, { debit: j.credit, credit: j.debit }));
      const totalDebit  = je.reduce((s, e) => s + e.debit, 0);
      const totalCredit = je.reduce((s, e) => s + e.credit, 0);
      const max = Math.max(totalDebit, totalCredit) || 1;
      debitVal.textContent  = fmtMoney(totalDebit);
      creditVal.textContent = fmtMoney(totalCredit);
      debitFill.style.width  = (totalDebit / max * 100).toFixed(1) + '%';
      creditFill.style.width = (totalCredit / max * 100).toFixed(1) + '%';

      const q = (jSearch?.value || '').trim().toLowerCase();
      const visible = je.filter(e => !q || e.desc.toLowerCase().includes(q) || e.ref.toLowerCase().includes(q));
      let running = acct.balance;
      const rows = visible.map(e => {
        const net = (e.debit - e.credit) * (acct.type === 'debit' ? 1 : -1);
        running -= net;
        return '<tr>' +
          '<td class="finance-mono">' + escapeHtml(e.date) + '</td>' +
          '<td>' + escapeHtml(e.desc) + '</td>' +
          '<td><code class="small">' + escapeHtml(e.ref) + '</code></td>' +
          '<td class="finance-mono text-end text-danger">' + (e.debit ? fmtMoney(e.debit) : '—') + '</td>' +
          '<td class="finance-mono text-end text-success">' + (e.credit ? fmtMoney(e.credit) : '—') + '</td>' +
          '<td class="finance-mono text-end">' + fmtMoney(Math.abs(running + net)) + '</td>' +
        '</tr>';
      }).join('');
      jbody.innerHTML = rows || '<tr><td colspan="6"><div class="finance-empty"><div class="finance-empty__icon"><i class="bi bi-journals"></i></div><h6>No journal entries</h6><p>Adjust your search or create a new entry.</p></div></td></tr>';
    }

    function renderSkeleton() {
      tree.innerHTML = Array.from({length: 8}).map(() =>
        '<div class="p-2"><span class="finance-skel" style="width:80%"></span><span class="finance-skel finance-skel--sm mt-2" style="width:50%"></span></div>'
      ).join('');
    }

    // events
    tree.addEventListener('click', function (e) {
      const item = e.target.closest('[data-acct-select]');
      if (item) {
        currentAccountId = item.getAttribute('data-acct-select');
        renderTree(); renderAccountDetail();
        return;
      }
      const toggle = e.target.closest('[data-acct-toggle]');
      if (toggle) toggle.parentElement.classList.toggle('is-collapsed');
    });
    if (searchEl) searchEl.addEventListener('input', debounce(renderTree, 200));
    if (jSearch)  jSearch.addEventListener('input',  debounce(renderAccountDetail, 200));

    // drag/drop reorder (visual only)
    tree.addEventListener('dragstart', function (e) {
      const item = e.target.closest('[data-acct-select]');
      if (!item) return;
      item.classList.add('is-dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', item.getAttribute('data-acct-select'));
    });
    tree.addEventListener('dragend', function (e) {
      const item = e.target.closest('[data-acct-select]');
      if (item) item.classList.remove('is-dragging');
    });
    tree.addEventListener('dragover', function (e) { e.preventDefault(); });
    tree.addEventListener('drop', function (e) {
      e.preventDefault();
      orchidToast('Account order updated', { kind: 'success' });
    });

    // journal entry modal validation
    const jeModal = document.getElementById('acctJeModal');
    if (jeModal) {
      const debitInput = jeModal.querySelector('[data-je-debit]');
      const creditInput= jeModal.querySelector('[data-je-credit]');
      const amountInput= jeModal.querySelector('[data-je-amount]');
      const warn       = jeModal.querySelector('[data-je-balance-warn]');
      const saveBtn    = jeModal.querySelector('[data-je-save]');

      function validate() {
        const amount = parseFloat(amountInput.value) || 0;
        const ok = amount > 0 && debitInput.value && creditInput.value && debitInput.value !== creditInput.value;
        if (!ok) {
          warn.classList.add('is-active');
          warn.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>Debit and credit accounts must differ and amount &gt; 0 for a balanced entry.';
        } else {
          warn.classList.remove('is-active');
        }
        return ok;
      }
      [debitInput, creditInput, amountInput].forEach(el => el && el.addEventListener('input', validate));
      saveBtn && saveBtn.addEventListener('click', function () {
        if (!validate()) { orchidToast('Entry is unbalanced', { kind: 'danger' }); return; }
        orchidToast('Journal entry posted successfully', { kind: 'success' });
        bootstrap.Modal.getInstance(jeModal)?.hide();
      });
    }

    // populate account pickers in JE modal
    if (jeModal) {
      const options = COA.map(a => '<option value="' + a.id + '">' + a.code + ' — ' + escapeHtml(a.name) + '</option>').join('');
      jeModal.querySelectorAll('[data-je-account]').forEach(sel => sel.innerHTML = '<option value="">Choose account…</option>' + options);
    }

    // new account button
    root.querySelectorAll('[data-acct-new]').forEach(btn => btn.addEventListener('click', function () {
      orchidToast('New account created', { kind: 'success' });
    }));

    // initial render with 400ms skeleton
    renderSkeleton();
    setTimeout(function () { renderTree(); renderAccountDetail(); }, 400);
  });

  /* ========================================================
     EXPENSES PAGE
     ======================================================== */
  document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-finance-page="expenses"]');
    if (!root) return;

    const submitters = ['Emma Watson','James Doe','Sarah Miller','Ryan Green','Ava Lee','Alex Kim','Noah Park','Olivia Chen'];
    const approvers  = ['Alex Kim','Sarah Miller','James Doe'];
    const statuses   = ['draft','submitted','approved','rejected','reimbursed'];
    const descs = [
      'Client dinner — Vertex Robotics','SF conference flight','Hotel — Meridian offsite','Office supplies restock',
      'GitHub Enterprise annual','Notion Team seats','Adobe Creative Cloud','Uber to airport',
      'Facebook Ads campaign','LinkedIn Ads sprint','Team lunch — offsite','Amazon Business — chairs',
      'UX training course','Airbnb — Aurora Labs visit','Delta Airlines — JFK','Slack Enterprise upgrade',
      'Zoom Business seats','Amex membership fee','Ergonomic keyboards','Team building dinner'
    ];
    const cats = ['travel','meals','software','office','marketing','training','miscellaneous'];

    function makeExpenses() {
      const arr = [];
      for (let i = 0; i < 20; i++) {
        const d = new Date(2026, 6, 22 - i);
        arr.push({
          id: 'EXP-' + (2400 + i),
          date: d.toISOString().slice(0,10),
          desc: descs[i],
          category: cats[i % cats.length],
          amount: [86.50, 720.15, 480.00, 128.90, 3420.00, 96.00, 260.00, 42.75, 4200.00, 1820.00, 320.00, 1140.20, 990.00, 342.85, 620.00, 240.00, 180.00, 550.00, 480.00, 720.00][i],
          submitter: submitters[i % submitters.length],
          approver: approvers[i % approvers.length],
          status: statuses[i % statuses.length],
          hasReceipt: i % 3 !== 0
        });
      }
      return arr;
    }

    let data = makeExpenses();
    let activeCategory = 'all';
    let sortKey = 'date';
    let sortDir = 'desc';

    const chipsWrap = root.querySelector('[data-exp-chips]');
    const tbody     = root.querySelector('[data-exp-body]');
    const searchEl  = root.querySelector('[data-exp-search]');
    const statusEl  = root.querySelector('[data-exp-status]');
    const bulk      = root.querySelector('[data-finance-bulk]');
    const bulkCount = root.querySelector('[data-finance-bulk-count]');
    const emptyEl   = root.querySelector('[data-exp-empty]');
    const countEl   = root.querySelector('[data-exp-count]');
    const selectAll = root.querySelector('[data-finance-select-all]');
    const kpiPending= root.querySelector('[data-kpi-pending]');
    const kpiTotal  = root.querySelector('[data-kpi-total]');
    const kpiReimb  = root.querySelector('[data-kpi-reimb]');

    function renderChips() {
      const all = { key: 'all', label: 'All', color: '#6b7385' };
      const list = [all].concat(Object.keys(EXPENSE_CATEGORIES).map(k => Object.assign({ key: k }, EXPENSE_CATEGORIES[k])));
      chipsWrap.innerHTML = list.map(c =>
        '<button class="exp-cat-chip ' + (activeCategory === c.key ? 'is-active' : '') + '" type="button" data-exp-chip="' + c.key + '">' +
          '<span class="exp-cat-chip__dot" style="background:' + c.color + '"></span>' +
          escapeHtml(c.label) +
        '</button>'
      ).join('');
    }

    function filtered() {
      const q = (searchEl?.value || '').trim().toLowerCase();
      const st = statusEl?.value || 'all';
      return data.filter(d =>
        (activeCategory === 'all' || d.category === activeCategory) &&
        (st === 'all' || d.status === st) &&
        (!q || d.desc.toLowerCase().includes(q) || d.submitter.toLowerCase().includes(q) || d.id.toLowerCase().includes(q))
      ).sort((a, b) => {
        const av = a[sortKey], bv = b[sortKey];
        if (av === bv) return 0;
        return (av > bv ? 1 : -1) * (sortDir === 'asc' ? 1 : -1);
      });
    }

    function renderKpis() {
      const totalMtd = data.reduce((s, d) => s + d.amount, 0);
      const pending  = data.filter(d => d.status === 'submitted').length;
      const reimb    = data.filter(d => d.status === 'reimbursed').reduce((s, d) => s + d.amount, 0);
      kpiTotal.textContent  = fmtMoney(totalMtd);
      kpiPending.textContent= pending;
      kpiReimb.textContent  = fmtMoney(reimb);
    }

    function renderTable() {
      const rows = filtered();
      countEl.textContent = rows.length + ' expense' + (rows.length === 1 ? '' : 's');
      if (!rows.length) {
        tbody.innerHTML = '';
        emptyEl.classList.remove('d-none');
        return;
      }
      emptyEl.classList.add('d-none');
      tbody.innerHTML = rows.map(e => {
        const cat = EXPENSE_CATEGORIES[e.category];
        return '<tr data-id="' + e.id + '">' +
          '<td><input class="form-check-input" type="checkbox" data-finance-select aria-label="Select ' + e.id + '"></td>' +
          '<td class="finance-mono">' + fmtDate(e.date) + '</td>' +
          '<td>' +
            '<div class="d-flex align-items-center gap-2">' +
              (e.hasReceipt ? '<i class="bi bi-paperclip text-body-secondary" title="Receipt attached"></i>' : '<i class="bi bi-file-x text-body-secondary opacity-25" title="No receipt"></i>') +
              '<div><strong class="d-block">' + escapeHtml(e.desc) + '</strong>' +
              '<small class="text-body-secondary">' + escapeHtml(e.id) + '</small></div>' +
            '</div>' +
          '</td>' +
          '<td><span class="exp-cat-badge"><span class="exp-cat-badge__dot" style="background:' + cat.color + '"></span>' + cat.label + '</span></td>' +
          '<td class="finance-mono">' + fmtMoney(e.amount) + '</td>' +
          '<td>' +
            '<div class="d-flex align-items-center gap-2"><span class="avatar avatar-sm ' + avatarClass(e.submitter) + ' fw-semibold">' + initials(e.submitter) + '</span>' +
            '<span class="small">' + escapeHtml(e.submitter) + '</span></div>' +
          '</td>' +
          '<td class="small text-body-secondary">' + escapeHtml(e.approver) + '</td>' +
          '<td><span class="exp-status exp-status--' + e.status + '">' + e.status.charAt(0).toUpperCase() + e.status.slice(1) + '</span></td>' +
          '<td class="text-end">' +
            '<button class="btn btn-sm btn-icon" type="button" data-exp-view="' + e.id + '" aria-label="View"><i class="bi bi-eye"></i></button>' +
            '<button class="btn btn-sm btn-icon" type="button" aria-label="Edit"><i class="bi bi-pencil"></i></button>' +
          '</td>' +
        '</tr>';
      }).join('');
    }

    function renderSkeleton() {
      tbody.innerHTML = Array.from({length: 6}).map(() =>
        '<tr class="finance-skel-row"><td colspan="9"><span class="finance-skel"></span></td></tr>'
      ).join('');
    }

    // events
    chipsWrap.addEventListener('click', function (e) {
      const chip = e.target.closest('[data-exp-chip]');
      if (!chip) return;
      activeCategory = chip.getAttribute('data-exp-chip');
      renderChips(); renderTable();
    });
    searchEl && searchEl.addEventListener('input', debounce(renderTable, 200));
    statusEl && statusEl.addEventListener('change', renderTable);

    // sortable
    root.querySelectorAll('[data-sort-key]').forEach(th => th.addEventListener('click', function () {
      const key = th.getAttribute('data-sort-key');
      if (sortKey === key) sortDir = sortDir === 'asc' ? 'desc' : 'asc'; else { sortKey = key; sortDir = 'asc'; }
      root.querySelectorAll('[data-sort-key]').forEach(t => { t.classList.remove('is-asc','is-desc'); });
      th.classList.add(sortDir === 'asc' ? 'is-asc' : 'is-desc');
      renderTable();
    }));

    // bulk
    function updateBulk() {
      const checked = tbody.querySelectorAll('[data-finance-select]:checked').length;
      bulk.classList.toggle('is-active', checked > 0);
      bulkCount.textContent = checked;
    }
    tbody.addEventListener('change', function (e) {
      if (e.target.matches('[data-finance-select]')) {
        const tr = e.target.closest('tr');
        tr.classList.toggle('is-selected', e.target.checked);
        updateBulk();
      }
    });
    selectAll && selectAll.addEventListener('change', function () {
      tbody.querySelectorAll('[data-finance-select]').forEach(c => {
        c.checked = selectAll.checked;
        c.closest('tr').classList.toggle('is-selected', selectAll.checked);
      });
      updateBulk();
    });

    root.querySelectorAll('[data-exp-bulk]').forEach(b => b.addEventListener('click', function () {
      const action = b.getAttribute('data-exp-bulk');
      const labels = { approve: 'approved', reject: 'rejected', export: 'exported', reimburse: 'submitted for reimbursement' };
      const n = tbody.querySelectorAll('[data-finance-select]:checked').length;
      orchidToast(n + ' expense' + (n === 1 ? '' : 's') + ' ' + labels[action], { kind: 'success' });
    }));

    // view drawer
    const drawer = document.getElementById('expViewDrawer');
    tbody.addEventListener('click', function (e) {
      const btn = e.target.closest('[data-exp-view]');
      if (!btn) return;
      const id = btn.getAttribute('data-exp-view');
      const rec = data.find(x => x.id === id);
      const body = drawer.querySelector('[data-exp-view-body]');
      const cat = EXPENSE_CATEGORIES[rec.category];
      body.innerHTML =
        '<div class="mb-3"><h5>' + escapeHtml(rec.desc) + '</h5><small class="text-body-secondary">' + rec.id + '</small></div>' +
        '<dl class="row small mb-3">' +
          '<dt class="col-5">Category</dt><dd class="col-7"><span class="exp-cat-badge"><span class="exp-cat-badge__dot" style="background:' + cat.color + '"></span>' + cat.label + '</span></dd>' +
          '<dt class="col-5">Date</dt><dd class="col-7">' + fmtDate(rec.date) + '</dd>' +
          '<dt class="col-5">Amount</dt><dd class="col-7 finance-mono fw-semibold">' + fmtMoney(rec.amount) + '</dd>' +
          '<dt class="col-5">Submitted by</dt><dd class="col-7">' + escapeHtml(rec.submitter) + '</dd>' +
          '<dt class="col-5">Approver</dt><dd class="col-7">' + escapeHtml(rec.approver) + '</dd>' +
          '<dt class="col-5">Status</dt><dd class="col-7"><span class="exp-status exp-status--' + rec.status + '">' + rec.status + '</span></dd>' +
        '</dl>' +
        '<h6 class="mt-4 mb-2">Receipt</h6>' +
        '<div class="exp-drawer-preview mb-3"><i class="bi bi-receipt"></i></div>' +
        '<h6 class="mb-2">Approval trail</h6>' +
        '<ul class="exp-approval-trail">' +
          '<li><span class="exp-approval-trail__dot bg-info"><i class="bi bi-file-earmark"></i></span><div><strong>Submitted</strong> by ' + escapeHtml(rec.submitter) + '<br><small class="text-body-secondary">' + fmtDate(rec.date) + '</small></div></li>' +
          '<li><span class="exp-approval-trail__dot bg-warning"><i class="bi bi-hourglass-split"></i></span><div><strong>In review</strong> by ' + escapeHtml(rec.approver) + '<br><small class="text-body-secondary">' + fmtDate(rec.date) + '</small></div></li>' +
          (rec.status === 'approved' || rec.status === 'reimbursed' ? '<li><span class="exp-approval-trail__dot bg-success"><i class="bi bi-check-lg"></i></span><div><strong>Approved</strong> by ' + escapeHtml(rec.approver) + '<br><small class="text-body-secondary">' + fmtDate(rec.date) + '</small></div></li>' : '') +
        '</ul>';
      bootstrap.Offcanvas.getOrCreateInstance(drawer).show();
    });

    // new expense offcanvas save
    root.querySelectorAll('[data-exp-save]').forEach(b => b.addEventListener('click', function () {
      orchidToast('Expense claim created', { kind: 'success' });
      const oc = document.getElementById('expNewCanvas');
      bootstrap.Offcanvas.getInstance(oc)?.hide();
    }));

    // receipt drop
    const drop = root.querySelector('[data-exp-drop]');
    if (drop) {
      ['dragenter','dragover'].forEach(ev => drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-dragging'); }));
      ['dragleave','drop'].forEach(ev => drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-dragging'); }));
      drop.addEventListener('drop', function () { orchidToast('Receipt uploaded', { kind: 'success' }); });
    }

    renderChips(); renderKpis(); renderSkeleton();
    setTimeout(renderTable, 400);
  });

  /* ========================================================
     INCOME PAGE
     ======================================================== */
  document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-finance-page="income"]');
    if (!root) return;

    const sources = Object.keys(INCOME_SOURCES);
    const clients = ['Vertex Robotics','Meridian Health','Aurora Labs','Northwind Analytics','Helios Media','Kepler Systems','Orion Freight','Sable & Co.','Zenith Studios','Cobalt Retail'];

    function makeIncome() {
      const seed = [
        { date:'2026-07-22', source:'subscriptions', client:'Vertex Robotics', ref:'INV-2841', amount:12480,  status:'received' },
        { date:'2026-07-21', source:'services',      client:'Meridian Health', ref:'INV-2839', amount:5600,   status:'received' },
        { date:'2026-07-20', source:'consulting',    client:'Northwind Analytics', ref:'INV-2838', amount:6800, status:'received' },
        { date:'2026-07-19', source:'subscriptions', client:'Aurora Labs',     ref:'INV-2837', amount:3250,   status:'pending'  },
        { date:'2026-07-18', source:'products',      client:'Cobalt Retail',   ref:'INV-2818', amount:12800,  status:'received' },
        { date:'2026-07-17', source:'services',      client:'Helios Media',    ref:'INV-2833', amount:4820,   status:'received' },
        { date:'2026-07-16', source:'consulting',    client:'Kepler Systems',  ref:'INV-2830', amount:9200,   status:'received' },
        { date:'2026-07-15', source:'subscriptions', client:'Orion Freight',   ref:'INV-2828', amount:3450,   status:'received' },
        { date:'2026-07-14', source:'services',      client:'Sable & Co.',     ref:'INV-2825', amount:4200,   status:'pending'  },
        { date:'2026-07-13', source:'products',      client:'Zenith Studios',  ref:'INV-2820', amount:7420,   status:'received' },
        { date:'2026-07-12', source:'subscriptions', client:'Vertex Robotics', ref:'INV-2815', amount:5200,   status:'received' },
        { date:'2026-07-11', source:'consulting',    client:'Aurora Labs',     ref:'INV-2811', amount:6420,   status:'received' },
        { date:'2026-07-10', source:'refunds',       client:'Meridian Health', ref:'REF-0812', amount:-480,   status:'received' },
        { date:'2026-07-09', source:'services',      client:'Meridian Health', ref:'INV-2805', amount:4800,   status:'received' },
        { date:'2026-07-08', source:'subscriptions', client:'Northwind Analytics', ref:'INV-2801', amount:9800, status:'overdue' },
        { date:'2026-07-07', source:'products',      client:'Helios Media',    ref:'INV-2798', amount:2450,   status:'received' },
        { date:'2026-07-06', source:'consulting',    client:'Cobalt Retail',   ref:'INV-2795', amount:5200,   status:'pending'  },
        { date:'2026-07-05', source:'services',      client:'Kepler Systems',  ref:'INV-2792', amount:3800,   status:'received' },
        { date:'2026-07-04', source:'subscriptions', client:'Sable & Co.',     ref:'INV-2790', amount:2400,   status:'overdue'  },
        { date:'2026-07-03', source:'services',      client:'Aurora Labs',     ref:'INV-2788', amount:4600,   status:'received' },
        { date:'2026-07-02', source:'products',      client:'Zenith Studios',  ref:'INV-2785', amount:3400,   status:'received' },
        { date:'2026-07-01', source:'consulting',    client:'Vertex Robotics', ref:'INV-2782', amount:7800,   status:'received' }
      ];
      return seed.map((s, i) => Object.assign({ id: 'INC-' + (900 + i) }, s));
    }

    let data = makeIncome();
    let sortKey = 'date', sortDir = 'desc';

    const tbody   = root.querySelector('[data-inc-body]');
    const search  = root.querySelector('[data-inc-search]');
    const statusEl= root.querySelector('[data-inc-status]');
    const sourceEl= root.querySelector('[data-inc-source]');
    const emptyEl = root.querySelector('[data-inc-empty]');
    const countEl = root.querySelector('[data-inc-count]');
    const legend  = root.querySelector('[data-inc-legend]');
    const donut   = root.querySelector('[data-inc-donut]');
    const centerV = root.querySelector('[data-inc-donut-value]');

    let chart;

    function filtered() {
      const q  = (search?.value || '').trim().toLowerCase();
      const st = statusEl?.value || 'all';
      const sc = sourceEl?.value || 'all';
      return data.filter(r =>
        (st === 'all' || r.status === st) &&
        (sc === 'all' || r.source === sc) &&
        (!q || r.client.toLowerCase().includes(q) || r.ref.toLowerCase().includes(q))
      ).sort((a,b) => {
        const av = a[sortKey], bv = b[sortKey];
        if (av === bv) return 0;
        return (av > bv ? 1 : -1) * (sortDir === 'asc' ? 1 : -1);
      });
    }

    function renderKpis() {
      const total = data.reduce((s, r) => s + r.amount, 0);
      const recurring = data.filter(r => r.source === 'subscriptions').reduce((s, r) => s + r.amount, 0);
      const onetime   = data.filter(r => r.source !== 'subscriptions' && r.source !== 'refunds').reduce((s, r) => s + r.amount, 0);
      root.querySelector('[data-kpi-total]').textContent = fmtMoney(total);
      root.querySelector('[data-kpi-recurring]').textContent = fmtMoney(recurring);
      root.querySelector('[data-kpi-onetime]').textContent   = fmtMoney(onetime);
      root.querySelector('[data-kpi-growth]').textContent    = '+18.4%';
    }

    function renderTable() {
      const rows = filtered();
      countEl.textContent = rows.length + ' entries';
      if (!rows.length) {
        tbody.innerHTML = '';
        emptyEl.classList.remove('d-none');
        return;
      }
      emptyEl.classList.add('d-none');
      tbody.innerHTML = rows.map(r => {
        const src = INCOME_SOURCES[r.source];
        return '<tr data-id="' + r.id + '">' +
          '<td><input class="form-check-input" type="checkbox" data-finance-select aria-label="Select"></td>' +
          '<td class="finance-mono">' + fmtDate(r.date) + '</td>' +
          '<td><span class="inc-source-chip"><span class="inc-source-chip__dot" style="background:' + src.color + '"></span>' + src.label + '</span></td>' +
          '<td>' + escapeHtml(r.client) + '</td>' +
          '<td><a href="invoice.html" class="text-decoration-none">' + escapeHtml(r.ref) + '</a></td>' +
          '<td class="finance-mono fw-semibold ' + (r.amount < 0 ? 'text-danger' : '') + '">' + fmtMoney(r.amount) + '</td>' +
          '<td class="small text-body-secondary">' + escapeHtml(src.label) + '</td>' +
          '<td><span class="inc-status inc-status--' + r.status + '">' + r.status.charAt(0).toUpperCase() + r.status.slice(1) + '</span></td>' +
          '<td class="text-end"><button class="btn btn-sm btn-icon" type="button" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button></td>' +
        '</tr>';
      }).join('');
    }

    function renderChart() {
      const bySource = {};
      data.forEach(r => { if (r.amount > 0) bySource[r.source] = (bySource[r.source] || 0) + r.amount; });
      const labels = Object.keys(bySource).map(k => INCOME_SOURCES[k].label);
      const colors = Object.keys(bySource).map(k => INCOME_SOURCES[k].color);
      const values = Object.keys(bySource).map(k => bySource[k]);
      const total = values.reduce((s, v) => s + v, 0);
      centerV.textContent = fmtCompactMoney(total);

      if (chart) chart.destroy();
      chart = new Chart(donut, {
        type: 'doughnut',
        data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 0 }] },
        options: {
          responsive: true, maintainAspectRatio: false,
          cutout: '68%',
          plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => c.label + ': ' + fmtMoney(c.parsed) } } }
        }
      });

      legend.innerHTML = labels.map((lbl, i) =>
        '<li><span class="inc-legend__dot" style="background:' + colors[i] + '"></span>' +
        '<span class="inc-legend__label">' + escapeHtml(lbl) + '</span>' +
        '<span class="inc-legend__value finance-mono">' + fmtMoney(values[i]) + '</span></li>'
      ).join('');
    }

    // events
    [search, statusEl, sourceEl].forEach(el => el && el.addEventListener('input', debounce(renderTable, 200)));
    root.querySelectorAll('[data-sort-key]').forEach(th => th.addEventListener('click', function () {
      const key = th.getAttribute('data-sort-key');
      if (sortKey === key) sortDir = sortDir === 'asc' ? 'desc' : 'asc'; else { sortKey = key; sortDir = 'asc'; }
      root.querySelectorAll('[data-sort-key]').forEach(t => t.classList.remove('is-asc','is-desc'));
      th.classList.add(sortDir === 'asc' ? 'is-asc' : 'is-desc');
      renderTable();
    }));
    root.querySelectorAll('[data-inc-export]').forEach(b => b.addEventListener('click', function () {
      orchidToast('Income entries exported to CSV', { kind: 'success' });
    }));
    root.querySelectorAll('[data-inc-save]').forEach(b => b.addEventListener('click', function () {
      orchidToast('Income entry recorded', { kind: 'success' });
      const m = document.getElementById('incNewModal');
      bootstrap.Modal.getInstance(m)?.hide();
    }));

    renderKpis();
    tbody.innerHTML = Array.from({length: 6}).map(() => '<tr class="finance-skel-row"><td colspan="9"><span class="finance-skel"></span></td></tr>').join('');
    setTimeout(function () { renderTable(); renderChart(); }, 400);
  });

  /* ========================================================
     TRANSACTIONS PAGE
     ======================================================== */
  document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-finance-page="transactions"]');
    if (!root) return;

    let data = window.FinanceLists.makeTransactions();
    let sortKey = 'date', sortDir = 'desc';

    const tbody    = root.querySelector('[data-txn-body]');
    const search   = root.querySelector('[data-txn-search]');
    const typeEl   = root.querySelector('[data-txn-type]');
    const catEl    = root.querySelector('[data-txn-category]');
    const acctEl   = root.querySelector('[data-txn-account]');
    const amtMin   = root.querySelector('[data-txn-amt-min]');
    const amtMax   = root.querySelector('[data-txn-amt-max]');
    const dateFrom = root.querySelector('[data-txn-date-from]');
    const dateTo   = root.querySelector('[data-txn-date-to]');
    const bulk     = root.querySelector('[data-finance-bulk]');
    const bulkCount= root.querySelector('[data-finance-bulk-count]');
    const emptyEl  = root.querySelector('[data-txn-empty]');
    const countEl  = root.querySelector('[data-txn-count]');
    const selectAll= root.querySelector('[data-finance-select-all]');
    const reconLabel= root.querySelector('[data-txn-recon-label]');
    const reconFill = root.querySelector('[data-txn-recon-fill]');

    function filtered() {
      const q  = (search?.value || '').trim().toLowerCase();
      const t  = typeEl?.value || 'all';
      const c  = catEl?.value  || 'all';
      const a  = acctEl?.value || 'all';
      const df = dateFrom?.value ? new Date(dateFrom.value) : null;
      const dt = dateTo?.value   ? new Date(dateTo.value)   : null;
      const mn = parseFloat(amtMin?.value) || 0;
      const mx = parseFloat(amtMax?.value) || Infinity;
      return data.filter(r =>
        (t === 'all' || r.type === t) &&
        (c === 'all' || r.category === c) &&
        (a === 'all' || r.account  === a) &&
        (!df || new Date(r.date) >= df) &&
        (!dt || new Date(r.date) <= dt) &&
        r.amount >= mn && r.amount <= mx &&
        (!q || r.desc.toLowerCase().includes(q) || r.account.toLowerCase().includes(q))
      ).sort((a,b) => {
        const av = a[sortKey], bv = b[sortKey];
        if (av === bv) return 0;
        return (av > bv ? 1 : -1) * (sortDir === 'asc' ? 1 : -1);
      });
    }

    function renderMetrics() {
      const moneyIn  = data.filter(r => r.type === 'in').reduce((s, r) => s + r.amount, 0);
      const moneyOut = data.filter(r => r.type === 'out').reduce((s, r) => s + r.amount, 0);
      root.querySelector('[data-txn-metric-in]').textContent  = fmtMoney(moneyIn);
      root.querySelector('[data-txn-metric-out]').textContent = fmtMoney(moneyOut);
      root.querySelector('[data-txn-metric-net]').textContent = fmtMoney(moneyIn - moneyOut);

      const total = data.length;
      const recon = data.filter(r => r.reconciled).length;
      reconLabel.innerHTML = '<strong>' + recon + '</strong> of ' + total + ' reconciled · ' + Math.round(recon/total*100) + '%';
      reconFill.style.width = (recon/total*100).toFixed(1) + '%';
    }

    function renderCatFilter() {
      const set = Array.from(new Set(data.map(r => r.category))).sort();
      catEl.innerHTML = '<option value="all">All categories</option>' + set.map(c => '<option value="' + c + '">' + c + '</option>').join('');
      const setA = Array.from(new Set(data.map(r => r.account))).sort();
      acctEl.innerHTML = '<option value="all">All accounts</option>' + setA.map(c => '<option value="' + c + '">' + escapeHtml(c) + '</option>').join('');
    }

    function renderTable() {
      const rows = filtered();
      countEl.textContent = rows.length + ' transactions';
      if (!rows.length) { tbody.innerHTML = ''; emptyEl.classList.remove('d-none'); return; }
      emptyEl.classList.add('d-none');
      tbody.innerHTML = rows.map(r =>
        '<tr data-id="' + escapeHtml(r.date + r.desc) + '">' +
          '<td><input class="form-check-input" type="checkbox" data-finance-select aria-label="Select"></td>' +
          '<td class="finance-mono small">' + fmtDateTime(r.date) + '</td>' +
          '<td><strong>' + escapeHtml(r.desc) + '</strong></td>' +
          '<td><span class="txn-acct-chip">' + escapeHtml(r.account) + '</span></td>' +
          '<td class="small">' + escapeHtml(r.category) + '</td>' +
          '<td><span class="txn-type-icon txn-type-icon--' + r.type + '"><i class="bi bi-arrow-' + (r.type === 'in' ? 'down' : 'up') + '"></i></span></td>' +
          '<td class="finance-mono ' + (r.type === 'in' ? 'finance-money-in' : 'finance-money-out') + '">' + (r.type === 'in' ? '+' : '-') + fmtMoney(r.amount) + '</td>' +
          '<td class="finance-mono text-body-secondary">' + fmtMoney(r.balance) + '</td>' +
          '<td><input class="form-check-input txn-recon-check" type="checkbox" data-txn-recon="' + escapeHtml(r.date + r.desc) + '" ' + (r.reconciled ? 'checked' : '') + ' aria-label="Reconciled"></td>' +
          '<td class="text-end">' +
            '<button class="btn btn-sm btn-icon" type="button" data-txn-split aria-label="Split transaction"><i class="bi bi-arrows-collapse"></i></button>' +
            '<button class="btn btn-sm btn-icon" type="button" aria-label="More"><i class="bi bi-three-dots-vertical"></i></button>' +
          '</td>' +
        '</tr>'
      ).join('');
    }

    // events
    [search, typeEl, catEl, acctEl, dateFrom, dateTo, amtMin, amtMax].forEach(el => el && el.addEventListener('input', debounce(renderTable, 200)));
    root.querySelectorAll('[data-sort-key]').forEach(th => th.addEventListener('click', function () {
      const key = th.getAttribute('data-sort-key');
      if (sortKey === key) sortDir = sortDir === 'asc' ? 'desc' : 'asc'; else { sortKey = key; sortDir = 'asc'; }
      root.querySelectorAll('[data-sort-key]').forEach(t => t.classList.remove('is-asc','is-desc'));
      th.classList.add(sortDir === 'asc' ? 'is-asc' : 'is-desc');
      renderTable();
    }));

    // reconciliation toggle
    tbody.addEventListener('change', function (e) {
      if (e.target.matches('[data-txn-recon]')) {
        const key = e.target.getAttribute('data-txn-recon');
        const r = data.find(x => (x.date + x.desc) === key);
        if (r) { r.reconciled = e.target.checked; renderMetrics(); }
        orchidToast('Transaction ' + (e.target.checked ? 'reconciled' : 'unreconciled'), { kind: 'info' });
      }
      if (e.target.matches('[data-finance-select]')) {
        const tr = e.target.closest('tr');
        tr.classList.toggle('is-selected', e.target.checked);
        const n = tbody.querySelectorAll('[data-finance-select]:checked').length;
        bulk.classList.toggle('is-active', n > 0);
        bulkCount.textContent = n;
      }
    });
    selectAll && selectAll.addEventListener('change', function () {
      tbody.querySelectorAll('[data-finance-select]').forEach(c => {
        c.checked = selectAll.checked;
        c.closest('tr').classList.toggle('is-selected', selectAll.checked);
      });
      const n = tbody.querySelectorAll('[data-finance-select]:checked').length;
      bulk.classList.toggle('is-active', n > 0);
      bulkCount.textContent = n;
    });
    root.querySelectorAll('[data-txn-bulk]').forEach(b => b.addEventListener('click', function () {
      const action = b.getAttribute('data-txn-bulk');
      const labels = { reconcile: 'reconciled', tag: 'tagged', export: 'exported to CSV' };
      const n = tbody.querySelectorAll('[data-finance-select]:checked').length;
      if (action === 'reconcile') {
        tbody.querySelectorAll('[data-finance-select]:checked').forEach(c => {
          const key = c.closest('tr').querySelector('[data-txn-recon]').getAttribute('data-txn-recon');
          const r = data.find(x => (x.date + x.desc) === key);
          if (r) r.reconciled = true;
        });
        renderMetrics(); renderTable();
      }
      orchidToast(n + ' transactions ' + labels[action], { kind: 'success' });
    }));

    // split modal
    tbody.addEventListener('click', function (e) {
      if (e.target.closest('[data-txn-split]')) {
        const modal = document.getElementById('txnSplitModal');
        bootstrap.Modal.getOrCreateInstance(modal).show();
      }
    });
    const splitAddBtn = document.querySelector('[data-txn-split-add]');
    if (splitAddBtn) splitAddBtn.addEventListener('click', function () {
      const lines = document.querySelector('[data-txn-split-lines]');
      const idx = lines.querySelectorAll('.txn-split-line').length + 1;
      const div = document.createElement('div');
      div.className = 'txn-split-line';
      div.innerHTML =
        '<div><label class="form-label small mb-1">Account</label><select class="form-select form-select-sm"><option>Choose…</option></select></div>' +
        '<div><label class="form-label small mb-1">Category</label><input class="form-control form-control-sm" placeholder="Category ' + idx + '"></div>' +
        '<div><label class="form-label small mb-1">Amount</label><input type="number" class="form-control form-control-sm" step="0.01"></div>' +
        '<button class="btn btn-sm btn-icon text-danger" type="button" data-txn-split-remove><i class="bi bi-trash"></i></button>';
      lines.appendChild(div);
    });
    document.addEventListener('click', function (e) {
      if (e.target.closest('[data-txn-split-remove]')) e.target.closest('.txn-split-line').remove();
    });
    const splitSave = document.querySelector('[data-txn-split-save]');
    if (splitSave) splitSave.addEventListener('click', function () {
      orchidToast('Transaction split saved', { kind: 'success' });
      bootstrap.Modal.getInstance(document.getElementById('txnSplitModal'))?.hide();
    });

    renderMetrics(); renderCatFilter();
    tbody.innerHTML = Array.from({length: 8}).map(() => '<tr class="finance-skel-row"><td colspan="10"><span class="finance-skel"></span></td></tr>').join('');
    setTimeout(renderTable, 400);
  });

  /* ========================================================
     BUDGETS PAGE
     ======================================================== */
  document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-finance-page="budgets"]');
    if (!root) return;

    function makeBudgets() {
      const seed = [
        { key:'saas',       budget: 5000,  spent: 3820,  spark: [420, 380, 450, 520, 480, 610, 590] },
        { key:'payroll',    budget: 80000, spent: 76480, spark: [11200, 10800, 11400, 10900, 11200, 10800, 10180] },
        { key:'marketing',  budget: 12000, spent: 13800, spark: [1400, 1600, 1800, 2200, 1900, 2400, 2500] },
        { key:'travel',     budget: 4000,  spent: 1240,  spark: [180, 240, 120, 180, 220, 140, 160] },
        { key:'office',     budget: 6000,  spent: 4720,  spark: [620, 580, 720, 640, 680, 720, 760] },
        { key:'cloud',      budget: 8000,  spent: 6420,  spark: [820, 780, 920, 880, 920, 1020, 1080] },
        { key:'legal',      budget: 5000,  spent: 2600,  spark: [400, 300, 200, 500, 300, 400, 500] },
        { key:'training',   budget: 3000,  spent: 990,   spark: [120, 140, 180, 100, 150, 150, 150] },
        { key:'insurance',  budget: 4000,  spent: 3800,  spark: [540, 540, 540, 540, 540, 540, 560] },
        { key:'contractors',budget: 10000, spent: 8420,  spark: [1200, 1100, 1250, 1180, 1250, 1220, 1220] }
      ];
      return seed.map(s => {
        const meta = BUDGET_CATEGORIES.find(c => c.key === s.key);
        return Object.assign({}, meta, s);
      });
    }

    let budgets = makeBudgets();
    let filter = { q: '', sort: 'alpha' };
    const gridEl = root.querySelector('[data-bud-grid]');
    const searchEl= root.querySelector('[data-bud-search]');
    const sortEl  = root.querySelector('[data-bud-sort]');

    function renderKpis() {
      const totalBud   = budgets.reduce((s, b) => s + b.budget, 0);
      const totalSpent = budgets.reduce((s, b) => s + b.spent, 0);
      const remain = totalBud - totalSpent;
      const pct = Math.min(100, (totalSpent / totalBud * 100));
      root.querySelector('[data-kpi-budget]').textContent = fmtMoney(totalBud);
      root.querySelector('[data-kpi-spent]').textContent  = fmtMoney(totalSpent);
      root.querySelector('[data-kpi-remain]').textContent = fmtMoney(remain);
      root.querySelectorAll('[data-kpi-ring]').forEach((ring, i) => {
        const p = [pct, pct, 100 - pct][i];
        ring.style.setProperty('--pct', p.toFixed(1));
        ring.querySelector('[data-kpi-ring-label]').textContent = Math.round(p) + '%';
      });
    }

    function renderGrid() {
      let arr = budgets.slice().filter(b => !filter.q || b.label.toLowerCase().includes(filter.q));
      if (filter.sort === 'alpha') arr.sort((a,b) => a.label.localeCompare(b.label));
      if (filter.sort === 'pct')   arr.sort((a,b) => (b.spent/b.budget) - (a.spent/a.budget));
      if (filter.sort === 'remain')arr.sort((a,b) => (a.budget-a.spent) - (b.budget-b.spent));

      gridEl.innerHTML = arr.map(b => {
        const pct = (b.spent / b.budget * 100);
        const fillCls = pct > 100 ? 'bud-card__fill--over' : pct >= 80 ? 'bud-card__fill--warn' : '';
        const remain = b.budget - b.spent;
        return '<div class="col-12 col-md-6 col-xl-4">' +
          '<div class="bud-card" data-bud-detail="' + b.key + '">' +
            '<div class="bud-card__head">' +
              '<span class="bud-card__icon" style="background:' + hexToRgba(b.color, .12) + ';color:' + b.color + '"><i class="bi bi-' + b.icon + '"></i></span>' +
              '<div class="flex-grow-1">' +
                '<h6 class="bud-card__name">' + escapeHtml(b.label) + '</h6>' +
                '<p class="bud-card__meta">' + fmtMoney(b.budget) + ' monthly budget</p>' +
              '</div>' +
              (pct > 100 ? '<span class="bud-warn-badge"><i class="bi bi-exclamation-triangle-fill"></i>Over</span>' : '') +
            '</div>' +
            '<div class="bud-card__amount-row">' +
              '<span class="bud-card__spent">' + fmtMoney(b.spent) + '</span>' +
              '<span class="bud-card__budget">of ' + fmtMoney(b.budget) + '</span>' +
            '</div>' +
            '<div class="bud-card__bar"><div class="bud-card__fill ' + fillCls + '" data-bud-fill="' + Math.min(100, pct).toFixed(1) + '"></div></div>' +
            '<div class="bud-card__spark"><canvas data-bud-spark="' + b.key + '"></canvas></div>' +
            '<div class="bud-card__footer">' +
              '<span class="' + (remain < 0 ? 'bud-card__remaining--over' : 'text-body-secondary') + '">' + (remain < 0 ? 'Over by ' + fmtMoney(-remain) : fmtMoney(remain) + ' remaining') + '</span>' +
              '<a href="#" class="link-primary text-decoration-none small">View details <i class="bi bi-arrow-right"></i></a>' +
            '</div>' +
          '</div>' +
        '</div>';
      }).join('');

      // animate fills + sparklines
      requestAnimationFrame(() => {
        gridEl.querySelectorAll('[data-bud-fill]').forEach(f => f.style.width = f.getAttribute('data-bud-fill') + '%');
        arr.forEach(b => {
          const c = gridEl.querySelector('[data-bud-spark="' + b.key + '"]');
          if (!c) return;
          new Chart(c, {
            type: 'line',
            data: { labels: b.spark.map((_,i)=>i+1), datasets: [{ data: b.spark, borderColor: b.color, backgroundColor: hexToRgba(b.color, .15), fill: true, tension: .35, borderWidth: 1.5, pointRadius: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { enabled: false } }, scales: { x: { display: false }, y: { display: false } } }
          });
        });
      });
    }

    function hexToRgba(hex, a) {
      const h = hex.replace('#','');
      const bigint = parseInt(h, 16);
      const r = (bigint >> 16) & 255, g = (bigint >> 8) & 255, b = bigint & 255;
      return 'rgba(' + r + ',' + g + ',' + b + ',' + a + ')';
    }

    searchEl && searchEl.addEventListener('input', debounce(function () { filter.q = searchEl.value.toLowerCase(); renderGrid(); }, 200));
    sortEl && sortEl.addEventListener('change', function () { filter.sort = sortEl.value; renderGrid(); });

    // detail drawer
    gridEl.addEventListener('click', function (e) {
      const card = e.target.closest('[data-bud-detail]');
      if (!card) return;
      const key = card.getAttribute('data-bud-detail');
      const b = budgets.find(x => x.key === key);
      const drawer = document.getElementById('budDetailDrawer');
      const body = drawer.querySelector('[data-bud-detail-body]');
      const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul'];
      body.innerHTML =
        '<h5 class="mb-1">' + escapeHtml(b.label) + '</h5>' +
        '<p class="text-body-secondary small mb-3">Monthly budget: ' + fmtMoney(b.budget) + '</p>' +
        '<div class="table-responsive"><table class="table table-sm mb-0">' +
          '<thead><tr><th>Month</th><th class="text-end">Spent</th><th class="text-end">Budget</th><th>Variance</th></tr></thead>' +
          '<tbody>' +
            months.map((m, i) => {
              const spent = b.spark[i];
              const variance = b.budget - spent;
              const pct = Math.min(100, Math.max(0, (spent/b.budget)*100));
              return '<tr><td>' + m + '</td>' +
                '<td class="finance-mono text-end">' + fmtMoney(spent) + '</td>' +
                '<td class="finance-mono text-end text-body-secondary">' + fmtMoney(b.budget) + '</td>' +
                '<td><div class="bud-detail-var"><div class="bud-detail-var__bar"><div style="height:100%;width:' + pct + '%;background:' + (variance < 0 ? '#dc2626' : b.color) + '"></div></div>' +
                '<small class="' + (variance < 0 ? 'text-danger' : 'text-success') + '">' + (variance < 0 ? '-' : '+') + fmtMoney(Math.abs(variance)) + '</small></div></td></tr>';
            }).join('') +
          '</tbody></table></div>';
      bootstrap.Offcanvas.getOrCreateInstance(drawer).show();
    });

    // new budget
    root.querySelectorAll('[data-bud-save]').forEach(b => b.addEventListener('click', function () {
      orchidToast('Budget created', { kind: 'success' });
      bootstrap.Modal.getInstance(document.getElementById('budNewModal'))?.hide();
    }));

    renderKpis();
    gridEl.innerHTML = Array.from({length: 6}).map(() =>
      '<div class="col-12 col-md-6 col-xl-4"><div class="bud-card"><span class="finance-skel"></span><span class="finance-skel finance-skel--lg mt-2"></span><span class="finance-skel mt-2"></span></div></div>'
    ).join('');
    setTimeout(renderGrid, 400);
  });

  /* ========================================================
     FINANCIAL REPORTS PAGE
     ======================================================== */
  document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-finance-page="financial-reports"]');
    if (!root) return;

    const REPORTS = {
      pnl: {
        title: 'Profit & Loss Statement',
        rows: [
          { type:'heading', label:'Revenue' },
          { label:'Subscription revenue', current: 284500, prior: 240100 },
          { label:'Services revenue',     current: 96420,  prior: 82000 },
          { label:'Consulting revenue',   current: 42800,  prior: 38400 },
          { type:'subtotal', label:'Total Revenue', current: 423720, prior: 360500 },
          { type:'heading', label:'Cost of Goods Sold' },
          { label:'Hosting & infrastructure', current: 42400, prior: 38200 },
          { label:'Software licenses',        current: 14820, prior: 12600 },
          { label:'Contractor delivery',      current: 11200, prior: 9800 },
          { type:'subtotal', label:'Total COGS', current: 68420, prior: 60600 },
          { type:'subtotal', label:'Gross Profit', current: 355300, prior: 299900 },
          { type:'heading', label:'Operating Expenses' },
          { label:'Salaries & wages', current: 148200, prior: 132400 },
          { label:'Rent',             current: 24600,  prior: 24600 },
          { label:'Utilities',        current: 6280,   prior: 5820 },
          { label:'Marketing',        current: 22450,  prior: 18400 },
          { label:'Travel',           current: 8940,   prior: 6200 },
          { label:'Legal & professional', current: 12400, prior: 9800 },
          { type:'subtotal', label:'Total OpEx', current: 222870, prior: 197220 },
          { type:'subtotal', label:'EBITDA', current: 132430, prior: 102680 },
          { label:'Depreciation & amortization', current: 8420, prior: 7900 },
          { label:'Interest expense',            current: 4200, prior: 3800 },
          { type:'total', label:'Net Income', current: 119810, prior: 90980 }
        ]
      },
      balance: {
        title: 'Balance Sheet',
        rows: [
          { type:'heading', label:'Assets' },
          { label:'Cash & equivalents', current: 148250, prior: 121400 },
          { label:'Accounts receivable',current: 62890,  prior: 58200 },
          { label:'Inventory',          current: 24560,  prior: 22100 },
          { label:'Equipment',          current: 89200,  prior: 82400 },
          { type:'subtotal', label:'Total Assets', current: 324900, prior: 284100 },
          { type:'heading', label:'Liabilities' },
          { label:'Accounts payable',   current: 32410, prior: 28900 },
          { label:'Credit card payable',current: 8560,  prior: 7420 },
          { label:'Bank loan',          current: 125000,prior: 130000 },
          { type:'subtotal', label:'Total Liabilities', current: 165970, prior: 166320 },
          { type:'heading', label:'Equity' },
          { label:'Common stock',       current: 50000, prior: 50000 },
          { label:'Retained earnings',  current: 108930, prior: 67780 },
          { type:'total', label:'Total Equity', current: 158930, prior: 117780 }
        ]
      },
      cashflow: {
        title: 'Cash Flow Statement',
        rows: [
          { type:'heading', label:'Operating Activities' },
          { label:'Net income',                    current: 119810, prior: 90980 },
          { label:'Depreciation & amortization',   current: 8420,   prior: 7900 },
          { label:'Change in AR',                  current: -4690,  prior: -3200 },
          { label:'Change in AP',                  current: 3510,   prior: 2800 },
          { type:'subtotal', label:'Cash from operations', current: 127050, prior: 98480 },
          { type:'heading', label:'Investing Activities' },
          { label:'Equipment purchases',           current: -6800,  prior: -12400 },
          { type:'subtotal', label:'Cash from investing', current: -6800, prior: -12400 },
          { type:'heading', label:'Financing Activities' },
          { label:'Loan repayments',               current: -5000,  prior: -5000 },
          { label:'Dividends paid',                current: 0,      prior: 0 },
          { type:'subtotal', label:'Cash from financing', current: -5000, prior: -5000 },
          { type:'total', label:'Net change in cash', current: 115250, prior: 81080 }
        ]
      },
      trial: {
        title: 'Trial Balance',
        rows: [
          { type:'heading', label:'Accounts' },
          { label:'Cash & equivalents', current: 148250, prior: 0 },
          { label:'Bank account',       current: 82430,  prior: 0 },
          { label:'Accounts receivable',current: 62890,  prior: 0 },
          { label:'Equipment',          current: 89200,  prior: 0 },
          { label:'Accounts payable',   current: 0, prior: 32410 },
          { label:'Bank loan',          current: 0, prior: 125000 },
          { label:'Common stock',       current: 0, prior: 50000 },
          { label:'Retained earnings',  current: 0, prior: 128450 },
          { label:'Revenue',            current: 0, prior: 423720 },
          { label:'Expenses',           current: 291290, prior: 0 },
          { type:'total', label:'Totals', current: 674060, prior: 674060 }
        ]
      },
      budget: {
        title: 'Budget vs Actual',
        rows: [
          { type:'heading', label:'Category' },
          { label:'SaaS Subscriptions', current: 3820, prior: 5000 },
          { label:'Payroll',            current: 76480, prior: 80000 },
          { label:'Marketing',          current: 13800, prior: 12000 },
          { label:'Travel',             current: 1240, prior: 4000 },
          { label:'Office',             current: 4720, prior: 6000 },
          { label:'Cloud',              current: 6420, prior: 8000 },
          { type:'total', label:'Total', current: 106480, prior: 115000 }
        ]
      },
      ar: {
        title: 'AR Aging Report',
        rows: [
          { type:'heading', label:'Client' },
          { label:'Vertex Robotics', current: 12480, prior: 0 },
          { label:'Meridian Health', current: 5600, prior: 0 },
          { label:'Aurora Labs',     current: 3250, prior: 0 },
          { label:'Northwind Analytics', current: 9800, prior: 0 },
          { label:'Sable & Co.',     current: 2400, prior: 0 },
          { type:'total', label:'Total AR', current: 33530, prior: 0 }
        ]
      },
      ap: {
        title: 'AP Aging Report',
        rows: [
          { type:'heading', label:'Vendor' },
          { label:'AWS',           current: 3420, prior: 0 },
          { label:'Baker LLP',     current: 2600, prior: 0 },
          { label:'Delta Airlines',current: 1240, prior: 0 },
          { label:'Facebook',      current: 4200, prior: 0 },
          { label:'LinkedIn',      current: 1820, prior: 0 },
          { type:'total', label:'Total AP', current: 13280, prior: 0 }
        ]
      }
    };

    let currentId = 'pnl';
    let mode = 'table';
    let compare = false;
    let chart;

    const picker  = root.querySelector('[data-rep-picker]');
    const viewer  = root.querySelector('[data-rep-viewer]');
    const title   = root.querySelector('[data-rep-title]');
    const meta    = root.querySelector('[data-rep-meta]');
    const modeGroup = root.querySelector('[data-rep-mode]');
    const compareBtn = root.querySelector('[data-rep-compare]');

    function renderPicker() {
      picker.addEventListener('click', function (e) {
        const item = e.target.closest('[data-rep-select]');
        if (!item) return;
        currentId = item.getAttribute('data-rep-select');
        picker.querySelectorAll('[data-rep-select]').forEach(i => i.classList.toggle('is-active', i === item));
        renderViewer();
      }, { once: false });
    }

    function renderViewer() {
      const r = REPORTS[currentId];
      title.textContent = r.title;
      meta.textContent  = 'Period: July 2026' + (compare ? ' vs June 2026' : '') + ' · Currency: USD';

      if (mode === 'table') {
        viewer.innerHTML =
          '<div class="table-responsive"><table class="table rep-table mb-0"><thead><tr>' +
            '<th>' + escapeHtml(r.rows.find(x => x.type === 'heading')?.label || 'Item') + '</th>' +
            '<th class="text-end">Current</th>' +
            (compare ? '<th class="text-end">Prior</th><th class="text-end">Change</th>' : '') +
          '</tr></thead><tbody>' +
          r.rows.filter(x => x.type !== 'heading' || false).map(row => {
            if (row.type === 'heading') return '<tr class="rep-heading"><td colspan="' + (compare ? 4 : 2) + '">' + escapeHtml(row.label) + '</td></tr>';
            const cls = row.type === 'total' ? 'rep-total' : row.type === 'subtotal' ? 'rep-subtotal' : '';
            const indent = !row.type ? 'rep-indent' : '';
            const change = row.current - row.prior;
            const changePct = row.prior ? (change / Math.abs(row.prior) * 100).toFixed(1) : '—';
            return '<tr class="' + cls + '"><td class="' + indent + '">' + escapeHtml(row.label) + '</td>' +
              '<td class="rep-num">' + fmtMoney(row.current) + '</td>' +
              (compare ? '<td class="rep-num text-body-secondary">' + fmtMoney(row.prior) + '</td>' +
                '<td class="rep-num ' + (change >= 0 ? 'text-success' : 'text-danger') + '">' + (change >= 0 ? '+' : '') + fmtMoney(change) + (changePct !== '—' ? ' (' + changePct + '%)' : '') + '</td>' : '') +
            '</tr>';
          }).join('') +
          '</tbody></table></div>';
      } else {
        viewer.innerHTML = '<div class="rep-chart-wrap"><canvas data-rep-canvas></canvas></div>';
        const labels = r.rows.filter(x => x.type !== 'heading').map(x => x.label);
        const cur = r.rows.filter(x => x.type !== 'heading').map(x => x.current);
        const pri = r.rows.filter(x => x.type !== 'heading').map(x => x.prior);
        const ctx = viewer.querySelector('[data-rep-canvas]');
        if (chart) chart.destroy();
        chart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: labels,
            datasets: [
              { label: 'Current', data: cur, backgroundColor: '#4f46e5', borderRadius: 6 },
              compare ? { label: 'Prior', data: pri, backgroundColor: 'rgba(79,70,229,.35)', borderRadius: 6 } : null
            ].filter(Boolean)
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: c => c.dataset.label + ': ' + fmtMoney(c.parsed.y) } } },
            scales: { x: { ticks: { autoSkip: true, maxRotation: 45, minRotation: 45 } }, y: { ticks: { callback: v => fmtCompactMoney(v) } } }
          }
        });
      }
    }

    modeGroup && modeGroup.addEventListener('click', function (e) {
      const btn = e.target.closest('[data-rep-mode-btn]');
      if (!btn) return;
      mode = btn.getAttribute('data-rep-mode-btn');
      modeGroup.querySelectorAll('[data-rep-mode-btn]').forEach(b => b.classList.toggle('active', b === btn));
      renderViewer();
    });
    compareBtn && compareBtn.addEventListener('change', function () { compare = compareBtn.checked; renderViewer(); });

    // exports
    root.querySelectorAll('[data-rep-export]').forEach(b => b.addEventListener('click', function () {
      orchidToast('Report exported as ' + b.getAttribute('data-rep-export').toUpperCase(), { kind: 'success' });
    }));
    // builder save
    root.querySelectorAll('[data-rep-builder-save]').forEach(b => b.addEventListener('click', function () {
      orchidToast('Custom report saved', { kind: 'success' });
      bootstrap.Modal.getInstance(document.getElementById('repBuilderModal'))?.hide();
    }));

    renderPicker();
    viewer.innerHTML = '<div class="p-4"><span class="finance-skel finance-skel--lg" style="width:40%"></span><br><br><span class="finance-skel"></span><br><span class="finance-skel"></span><br><span class="finance-skel"></span></div>';
    setTimeout(renderViewer, 400);
  });

})();
