/* =====================================================
   Orchid — Tables Showcase (shared JS toolkit)
   Loaded on all 5 tables-*.html pages.
   Each page uses only the subsystems it needs.
   ===================================================== */
(function () {
  'use strict';

  /* -------- Toast -------- */
  function ensureToastContainer() {
    var c = document.querySelector('.tables-toast-container');
    if (!c) {
      c = document.createElement('div');
      c.className = 'tables-toast-container';
      c.setAttribute('aria-live', 'polite');
      c.setAttribute('aria-atomic', 'true');
      document.body.appendChild(c);
    }
    return c;
  }
  function orchidToast(message, type) {
    type = type || 'info';
    var icon = {
      success: 'bi-check-circle-fill',
      danger:  'bi-x-circle-fill',
      warning: 'bi-exclamation-triangle-fill',
      info:    'bi-info-circle-fill'
    }[type] || 'bi-info-circle-fill';
    var c = ensureToastContainer();
    var t = document.createElement('div');
    t.className = 'tables-toast tables-toast--' + type;
    t.setAttribute('role', 'status');
    t.innerHTML = '<i class="bi ' + icon + '"></i><span></span>';
    t.querySelector('span').textContent = message;
    c.appendChild(t);
    requestAnimationFrame(function () { t.classList.add('is-visible'); });
    setTimeout(function () {
      t.classList.remove('is-visible');
      setTimeout(function () { if (t.parentNode) t.parentNode.removeChild(t); }, 260);
    }, 2400);
  }
  window.orchidToast = orchidToast;

  /* -------- Copy to Clipboard -------- */
  function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    return new Promise(function (resolve, reject) {
      try {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        resolve();
      } catch (e) { reject(e); }
    });
  }
  window.copyToClipboard = copyToClipboard;

  /* -------- Code preview toggle + copy -------- */
  document.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-tables-code-toggle]');
    if (toggle) {
      var sel = toggle.getAttribute('data-tables-code-toggle');
      var block = document.querySelector(sel);
      if (block) {
        block.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', block.classList.contains('is-open') ? 'true' : 'false');
        var lbl = toggle.querySelector('[data-label]');
        if (lbl) lbl.textContent = block.classList.contains('is-open') ? 'Hide code' : 'View code';
      }
      return;
    }
    var copyBtn = e.target.closest('[data-tables-copy]');
    if (copyBtn) {
      var codeSel = copyBtn.getAttribute('data-tables-copy');
      var pre = document.querySelector(codeSel);
      if (pre) {
        copyToClipboard(pre.textContent).then(function () {
          orchidToast('Snippet copied to clipboard', 'success');
        }).catch(function () {
          orchidToast('Copy failed', 'danger');
        });
      }
      return;
    }
  });

  /* -------- TOC scroll spy -------- */
  var toc = document.querySelector('[data-tables-toc]');
  if (toc) {
    var links = Array.prototype.slice.call(toc.querySelectorAll('a[href^="#"]'));
    var sections = links.map(function (a) {
      return document.querySelector(a.getAttribute('href'));
    }).filter(Boolean);
    var setActive = function (id) {
      links.forEach(function (a) {
        a.classList.toggle('is-active', a.getAttribute('href') === '#' + id);
      });
    };
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (en.isIntersecting) setActive(en.target.id);
        });
      }, { rootMargin: '-40% 0px -55% 0px', threshold: 0 });
      sections.forEach(function (s) { io.observe(s); });
    }
    // smooth scroll
    links.forEach(function (a) {
      a.addEventListener('click', function (e) {
        var target = document.querySelector(a.getAttribute('href'));
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          setActive(target.id);
          history.replaceState(null, '', a.getAttribute('href'));
        }
      });
    });
  }

  /* -------- CSV export helper -------- */
  function csvEscape(v) {
    if (v === null || v === undefined) return '';
    var s = String(v);
    if (/[",\r\n]/.test(s)) return '"' + s.replace(/"/g, '""') + '"';
    return s;
  }
  function exportCsv(rows, headers, filename) {
    var lines = [];
    lines.push(headers.map(csvEscape).join(','));
    rows.forEach(function (r) {
      lines.push(headers.map(function (h) { return csvEscape(r[h]); }).join(','));
    });
    var blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    document.body.appendChild(a);
    a.click();
    setTimeout(function () {
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    }, 100);
  }
  window.exportCsv = exportCsv;

  /* -------- Sample dataset factory (deterministic pseudo random) -------- */
  function mulberry32(seed) {
    return function () {
      seed |= 0; seed = seed + 0x6D2B79F5 | 0;
      var t = seed;
      t = Math.imul(t ^ t >>> 15, t | 1);
      t ^= t + Math.imul(t ^ t >>> 7, t | 61);
      return ((t ^ t >>> 14) >>> 0) / 4294967296;
    };
  }
  window.tablesRng = mulberry32;

  var FIRST = ['Emma','Liam','Olivia','Noah','Ava','Ethan','Sophia','Mason','Isabella','Lucas','Mia','Aiden','Amelia','Jack','Harper','Owen','Ella','Levi','Scarlett','Wyatt','Grace','Leo','Chloe','Julian','Zoe','Aria','Lily','Nora','Riley','Nova'];
  var LAST = ['Kim','Chen','Watson','Doe','Miller','Smith','Brown','Jones','Garcia','Rodriguez','Lee','Green','Hall','Young','King','Wright','Scott','Torres','Nguyen','Hill','Baker','Adams','Nelson','Carter','Mitchell'];
  var COMPANIES = ['Nova','Acme','Zenith','Peak','Bold','Orchid','Vertex','Prism','Loom','Nexus','Halcyon','Kite','Ridge','Auric','Delta'];
  var REGIONS = ['NA','EMEA','APAC','LATAM'];
  var CATS = ['Software','Hardware','Services','Consulting','Support'];
  var STATUSES = ['Active','Pending','Inactive'];
  var DEAL_STAGES = ['Prospect','Qualified','Proposal','Negotiation','Won','Lost'];

  function pick(rng, arr) { return arr[Math.floor(rng() * arr.length)]; }
  function pad2(n) { return n < 10 ? '0' + n : '' + n; }
  function daysAgo(rng, max) {
    var d = new Date();
    d.setDate(d.getDate() - Math.floor(rng() * max));
    return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
  }

  function generateSample(count, seed) {
    var rng = mulberry32(seed || 42);
    var out = [];
    for (var i = 1; i <= count; i++) {
      var first = pick(rng, FIRST);
      var last = pick(rng, LAST);
      var co = pick(rng, COMPANIES);
      out.push({
        id: i,
        name: first + ' ' + last,
        email: first.toLowerCase() + '.' + last.toLowerCase() + '@' + co.toLowerCase() + '.io',
        company: co,
        region: pick(rng, REGIONS),
        category: pick(rng, CATS),
        status: pick(rng, STATUSES),
        stage: pick(rng, DEAL_STAGES),
        amount: Math.round((500 + rng() * 24500) * 100) / 100,
        deals: Math.floor(rng() * 42) + 1,
        joined: daysAgo(rng, 900),
        lastActive: daysAgo(rng, 60)
      });
    }
    return out;
  }
  window.tablesSample = generateSample;

  /* -------- Escape HTML helper -------- */
  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }
  window.tablesEsc = esc;

  /* -------- Status badge helper -------- */
  function statusBadge(s) {
    var map = {
      Active: 'success', Pending: 'warning', Inactive: 'danger',
      Won: 'success', Lost: 'danger', Prospect: 'info',
      Qualified: 'primary', Proposal: 'warning', Negotiation: 'secondary'
    };
    var c = map[s] || 'secondary';
    return '<span class="badge bg-' + c + '-subtle text-' + c + '">' + esc(s) + '</span>';
  }
  window.tablesStatusBadge = statusBadge;

  /* -------- Money helper -------- */
  function money(n) {
    return '$' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  window.tablesMoney = money;

  /* -------- Init year in footer -------- */
  document.querySelectorAll('[data-orchid-year]').forEach(function (el) {
    el.textContent = new Date().getFullYear();
  });
})();
