/* =====================================================
   Orchid - Content Management shared toolkit
   Exposes window.OrchidCMS with utilities used across
   the 6 CMS pages (Pages, Blog, Media, Categories,
   Comments, Menus).
   ===================================================== */
(function () {
  'use strict';

  // ---------- Blog hero image helper ----------
  // Curated Unsplash IDs per post category. Same post id → same photo.
  var POST_IMG = {
    Product:     ['1522542550221-31fd19575a2d','1552664730-d307ca884978','1517245386807-bb43f82c33c4','1519389950473-47ba0277781c'],
    Design:      ['1561070791-2526d30994b8','1558655146-9f40138edfeb','1587440871875-191322ee64b0','1611162617213-7d7a39e9b1d7'],
    Leadership:  ['1552664730-d307ca884978','1521737604893-d14cc237f11d','1521737711867-e3b97375f902','1519389950473-47ba0277781c'],
    Engineering: ['1517694712202-14dd9538aa97','1461749280684-dccba630e2f6','1555949963-aa79dcee981c','1498050108023-c5249f4df085'],
    Company:     ['1522071820081-009f0129c71c','1521737604893-d14cc237f11d','1497366216548-37526070297c','1497366811353-6870744d04b2'],
    Marketing:   ['1460925895917-afdab827c52f','1611162616475-46b635cb6868','1553877522-43269d4ea984','1533750349088-cd871a92f312']
  };
  var POST_IMG_FALLBACK = ['1497366216548-37526070297c','1519389950473-47ba0277781c','1553877522-43269d4ea984'];
  window.OrchidCMS_postImage = function (p, w, h) {
    var pool = POST_IMG[p.category] || POST_IMG_FALLBACK;
    var seed = String(p.id || p.title || 'x');
    var hash = 0;
    for (var i = 0; i < seed.length; i++) hash = (hash * 31 + seed.charCodeAt(i)) >>> 0;
    var photoId = pool[hash % pool.length];
    return 'https://images.unsplash.com/photo-' + photoId + '?auto=format&fit=crop&w=' + w + '&h=' + h + '&q=70';
  };

  // Defensive: swap 404'd blog hero images to a picsum fallback.
  document.addEventListener('error', function (e) {
    var el = e.target;
    if (!el || el.tagName !== 'IMG') return;
    if (!el.matches('.cmsb-card__img, .cmsb-list-row__img')) return;
    if (el.dataset.cmsFallbackApplied === '1') return;
    el.dataset.cmsFallbackApplied = '1';
    var w = el.getAttribute('width') || 480;
    var h = el.getAttribute('height') || 260;
    el.src = 'https://picsum.photos/seed/cms-fallback-' + (el.alt || 'x').replace(/\s+/g, '-').slice(0, 40) + '/' + w + '/' + h;
  }, true);

  // ---------- Toast (bootstrap) ----------
  function ensureToastContainer() {
    var c = document.querySelector('[data-cms-toast-container]');
    if (c) return c;
    c = document.createElement('div');
    c.className = 'toast-container position-fixed top-0 end-0 p-3';
    c.style.zIndex = '1080';
    c.setAttribute('data-cms-toast-container', '');
    document.body.appendChild(c);
    return c;
  }
  function orchidToast(msg, opts) {
    opts = opts || {};
    var variant = opts.variant || 'primary';
    var icon = opts.icon || (variant === 'success' ? 'check-circle' :
                            variant === 'danger' ? 'exclamation-triangle' :
                            variant === 'warning' ? 'exclamation-circle' :
                            variant === 'info' ? 'info-circle' : 'bell');
    var c = ensureToastContainer();
    var el = document.createElement('div');
    el.className = 'toast align-items-center border-0 text-bg-' + variant + ' show';
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'assertive');
    el.setAttribute('aria-atomic', 'true');
    el.innerHTML =
      '<div class="d-flex">' +
        '<div class="toast-body"><i class="bi bi-' + icon + ' me-2"></i>' + escapeHtml(msg) + '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
      '</div>';
    c.appendChild(el);
    var t;
    try { t = new bootstrap.Toast(el, { delay: opts.delay || 3000 }); t.show(); }
    catch (e) { setTimeout(function(){ el.remove(); }, opts.delay || 3000); }
    el.addEventListener('hidden.bs.toast', function () { el.remove(); });
  }

  // ---------- Utility helpers ----------
  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function slugify(str) {
    return String(str || '')
      .toLowerCase()
      .trim()
      .replace(/['`"]/g, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .slice(0, 80);
  }

  function fmtDate(d) {
    if (!d) return '';
    var dt = (d instanceof Date) ? d : new Date(d);
    if (isNaN(dt.getTime())) return String(d);
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return months[dt.getMonth()] + ' ' + dt.getDate() + ', ' + dt.getFullYear();
  }

  function fmtDateTime(d) {
    if (!d) return '';
    var dt = (d instanceof Date) ? d : new Date(d);
    if (isNaN(dt.getTime())) return String(d);
    var hh = dt.getHours(); var mm = dt.getMinutes();
    var ampm = hh >= 12 ? 'PM' : 'AM';
    hh = hh % 12 || 12;
    if (mm < 10) mm = '0' + mm;
    return fmtDate(dt) + ' • ' + hh + ':' + mm + ' ' + ampm;
  }

  function relativeTime(d) {
    if (!d) return '';
    var dt = (d instanceof Date) ? d : new Date(d);
    if (isNaN(dt.getTime())) return '';
    var diff = (Date.now() - dt.getTime()) / 1000;
    if (diff < 45) return 'just now';
    if (diff < 90) return '1 min ago';
    if (diff < 3600) return Math.round(diff/60) + ' mins ago';
    if (diff < 5400) return '1 hour ago';
    if (diff < 86400) return Math.round(diff/3600) + ' hours ago';
    if (diff < 172800) return 'yesterday';
    if (diff < 2592000) return Math.round(diff/86400) + ' days ago';
    if (diff < 5184000) return '1 month ago';
    return Math.round(diff/2592000) + ' months ago';
  }

  function fmtSize(bytes) {
    if (bytes == null || isNaN(bytes)) return '';
    var units = ['B','KB','MB','GB'];
    var i = 0, n = bytes;
    while (n >= 1024 && i < units.length-1) { n /= 1024; i++; }
    return (n < 10 ? n.toFixed(1) : Math.round(n)) + ' ' + units[i];
  }

  function fmtNum(n) {
    if (n == null || isNaN(n)) return '0';
    return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  var AVATAR_COLORS = [
    '#4f46e5','#0ea5e9','#10b981','#f59e0b','#ef4444',
    '#a855f7','#ec4899','#14b8a6','#f97316','#6366f1'
  ];
  function avatarColor(seed) {
    var s = String(seed || '');
    var h = 0;
    for (var i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) >>> 0;
    return AVATAR_COLORS[h % AVATAR_COLORS.length];
  }
  function initials(name) {
    if (!name) return '?';
    var parts = String(name).trim().split(/\s+/);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length-1][0]).toUpperCase();
  }
  function renderAvatar(name, opts) {
    opts = opts || {};
    var size = opts.size === 'sm' ? 'cms-avatar cms-avatar--sm' :
               opts.size === 'lg' ? 'cms-avatar cms-avatar--lg' : 'cms-avatar';
    var color = opts.color || avatarColor(name);
    return '<span class="' + size + '" style="background:' + color + '">' + escapeHtml(initials(name)) + '</span>';
  }

  // ---------- Query all as array ----------
  function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  // ---------- Debounce ----------
  function debounce(fn, wait) {
    var t;
    return function () {
      var ctx = this, args = arguments;
      clearTimeout(t);
      t = setTimeout(function(){ fn.apply(ctx, args); }, wait || 200);
    };
  }

  // ---------- Auto slug binder ----------
  function bindAutoSlug(titleInput, slugInput) {
    if (!titleInput || !slugInput) return;
    var manual = false;
    slugInput.addEventListener('input', function () { manual = true; });
    titleInput.addEventListener('input', function () {
      if (manual && slugInput.value.trim() !== '') return;
      slugInput.value = slugify(titleInput.value);
    });
  }

  // ---------- Rich text editor (contentEditable) ----------
  function initRichEditor(root) {
    if (!root) return;
    var area = root.querySelector('[data-cms-editor-area]');
    if (!area) return;
    root.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-cms-cmd]');
      if (!btn) return;
      e.preventDefault();
      var cmd = btn.getAttribute('data-cms-cmd');
      var arg = btn.getAttribute('data-cms-arg') || null;
      area.focus();
      if (cmd === 'createLink') {
        var url = prompt('Enter URL', 'https://');
        if (url) document.execCommand('createLink', false, url);
      } else if (cmd === 'formatBlock') {
        document.execCommand('formatBlock', false, arg);
      } else if (cmd === 'clear') {
        area.innerHTML = '';
      } else {
        document.execCommand(cmd, false, arg);
      }
    });
  }

  // ---------- Simple set-based selection helper ----------
  function selection() {
    var set = new Set();
    return {
      add: function (id) { set.add(String(id)); },
      remove: function (id) { set.delete(String(id)); },
      toggle: function (id) { id = String(id); if (set.has(id)) set.delete(id); else set.add(id); },
      has: function (id) { return set.has(String(id)); },
      clear: function () { set.clear(); },
      values: function () { return Array.from(set); },
      size: function () { return set.size; }
    };
  }

  // ---------- HTML5 drag & drop tree helper ----------
  // Attach draggable events to items with data-cms-drag on `container`.
  // Callbacks receive (dragId, targetId, position: 'above'|'below'|'into').
  function initDragTree(container, opts) {
    if (!container) return;
    opts = opts || {};
    var itemSel = opts.itemSelector || '[data-cms-drag]';
    var draggedEl = null;
    container.addEventListener('dragstart', function (e) {
      var item = e.target.closest(itemSel);
      if (!item) return;
      draggedEl = item;
      item.classList.add('is-dragging');
      try { e.dataTransfer.setData('text/plain', item.getAttribute('data-cms-drag') || ''); } catch (err) {}
      e.dataTransfer.effectAllowed = 'move';
    });
    container.addEventListener('dragend', function () {
      if (draggedEl) draggedEl.classList.remove('is-dragging');
      qsa('.is-drop-above,.is-drop-below,.is-drop-into,.is-drop-target', container).forEach(function (n) {
        n.classList.remove('is-drop-above','is-drop-below','is-drop-into','is-drop-target');
      });
      draggedEl = null;
    });
    container.addEventListener('dragover', function (e) {
      var item = e.target.closest(itemSel);
      if (!item || item === draggedEl) return;
      e.preventDefault();
      var rect = item.getBoundingClientRect();
      var y = e.clientY - rect.top;
      var pos;
      if (opts.nesting) {
        if (y < rect.height * 0.25) pos = 'above';
        else if (y > rect.height * 0.75) pos = 'below';
        else pos = 'into';
      } else {
        pos = (y < rect.height / 2) ? 'above' : 'below';
      }
      qsa('.is-drop-above,.is-drop-below,.is-drop-into', container).forEach(function (n) {
        n.classList.remove('is-drop-above','is-drop-below','is-drop-into');
      });
      item.classList.add(pos === 'above' ? 'is-drop-above' : pos === 'below' ? 'is-drop-below' : 'is-drop-into');
      item.setAttribute('data-cms-drop-pos', pos);
    });
    container.addEventListener('drop', function (e) {
      var item = e.target.closest(itemSel);
      if (!item || !draggedEl || item === draggedEl) return;
      e.preventDefault();
      var pos = item.getAttribute('data-cms-drop-pos') || 'below';
      var dragId = draggedEl.getAttribute('data-cms-drag');
      var targetId = item.getAttribute('data-cms-drag');
      if (typeof opts.onDrop === 'function') opts.onDrop(dragId, targetId, pos);
    });
  }

  // ---------- Bulk bar helper ----------
  function initBulkBar(container, selectionApi, opts) {
    if (!container) return;
    opts = opts || {};
    var countEl = container.querySelector('[data-cms-bulk-count]');
    function refresh() {
      if (selectionApi.size() > 0) {
        container.classList.add('is-visible');
        if (countEl) countEl.textContent = selectionApi.size();
      } else {
        container.classList.remove('is-visible');
      }
    }
    return { refresh: refresh };
  }

  // ---------- File pick helper (for uploads) ----------
  function openFilePicker(opts) {
    opts = opts || {};
    var input = document.createElement('input');
    input.type = 'file';
    if (opts.multiple) input.multiple = true;
    if (opts.accept) input.accept = opts.accept;
    input.addEventListener('change', function () {
      if (typeof opts.onFiles === 'function') opts.onFiles(input.files);
    });
    input.click();
  }

  // ---------- Fake upload progress ----------
  function simulateUpload(onProgress, onDone) {
    var pct = 0;
    var iv = setInterval(function () {
      pct += Math.max(5, Math.round(Math.random() * 20));
      if (pct >= 100) { pct = 100; clearInterval(iv); if (onProgress) onProgress(100); if (onDone) onDone(); return; }
      if (onProgress) onProgress(pct);
    }, 260);
    return function cancel() { clearInterval(iv); };
  }

  // ---------- Sortable columns ----------
  function initSortableHeader(container, onSort) {
    if (!container) return;
    container.addEventListener('click', function (e) {
      var th = e.target.closest('[data-cms-sort]');
      if (!th) return;
      var key = th.getAttribute('data-cms-sort');
      var current = th.classList.contains('is-sort-asc') ? 'asc'
                    : th.classList.contains('is-sort-desc') ? 'desc' : null;
      var next = current === 'asc' ? 'desc' : 'asc';
      qsa('[data-cms-sort]', container).forEach(function (h) {
        h.classList.remove('is-sort-asc','is-sort-desc');
      });
      th.classList.add(next === 'asc' ? 'is-sort-asc' : 'is-sort-desc');
      if (typeof onSort === 'function') onSort(key, next);
    });
  }

  // ---------- Public API ----------
  window.OrchidCMS = {
    toast: orchidToast,
    escapeHtml: escapeHtml,
    slugify: slugify,
    fmtDate: fmtDate,
    fmtDateTime: fmtDateTime,
    relativeTime: relativeTime,
    fmtSize: fmtSize,
    fmtNum: fmtNum,
    initials: initials,
    avatarColor: avatarColor,
    renderAvatar: renderAvatar,
    qsa: qsa,
    debounce: debounce,
    bindAutoSlug: bindAutoSlug,
    initRichEditor: initRichEditor,
    selection: selection,
    initDragTree: initDragTree,
    initBulkBar: initBulkBar,
    openFilePicker: openFilePicker,
    simulateUpload: simulateUpload,
    initSortableHeader: initSortableHeader
  };

  // ---------- Auto-init tiny things ----------
  document.addEventListener('DOMContentLoaded', function () {
    // Rich editors declared with [data-cms-editor]
    qsa('[data-cms-editor]').forEach(function (root) {
      window.OrchidCMS.initRichEditor(root);
    });
    // Auto slug binders
    qsa('[data-cms-slug-source]').forEach(function (src) {
      var targetSel = src.getAttribute('data-cms-slug-source');
      var target = document.querySelector(targetSel);
      window.OrchidCMS.bindAutoSlug(src, target);
    });
    // Year token
    qsa('[data-orchid-year]').forEach(function (n) { n.textContent = new Date().getFullYear(); });
  });

  // ---------- Per-page routers ----------
  document.addEventListener('DOMContentLoaded', function () {
    if (window.OrchidCMSPages && typeof window.OrchidCMSPages.init === 'function') window.OrchidCMSPages.init();
    if (window.OrchidCMSBlog && typeof window.OrchidCMSBlog.init === 'function') window.OrchidCMSBlog.init();
    if (window.OrchidCMSMedia && typeof window.OrchidCMSMedia.init === 'function') window.OrchidCMSMedia.init();
    if (window.OrchidCMSCategories && typeof window.OrchidCMSCategories.init === 'function') window.OrchidCMSCategories.init();
    if (window.OrchidCMSComments && typeof window.OrchidCMSComments.init === 'function') window.OrchidCMSComments.init();
    if (window.OrchidCMSMenus && typeof window.OrchidCMSMenus.init === 'function') window.OrchidCMSMenus.init();
  });
})();


/* =====================================================
   Page: cms-pages.html — OrchidCMSPages
   ===================================================== */
(function () {
  'use strict';
  var PAGES = [
    { id:'p1', title:'Home', slug:'/', template:'Landing', status:'Published', author:'Alex Kim', views:38452, modified:'2026-07-18' },
    { id:'p2', title:'About Us', slug:'/about', template:'About', status:'Published', author:'Sarah Miller', views:12480, modified:'2026-07-10' },
    { id:'p3', title:'Contact', slug:'/contact', template:'Contact', status:'Published', author:'Alex Kim', views:8720, modified:'2026-06-28' },
    { id:'p4', title:'Pricing', slug:'/pricing', template:'Landing', status:'Published', author:'Priya Patel', views:15920, modified:'2026-07-12' },
    { id:'p5', title:'Careers', slug:'/careers', template:'Landing', status:'Published', author:'James Doe', views:5640, modified:'2026-07-05' },
    { id:'p6', title:'Privacy Policy', slug:'/privacy', template:'Legal', status:'Published', author:'Emma Watson', views:2210, modified:'2026-05-20' },
    { id:'p7', title:'Terms of Service', slug:'/terms', template:'Legal', status:'Published', author:'Emma Watson', views:1980, modified:'2026-05-20' },
    { id:'p8', title:'Cookie Policy', slug:'/cookies', template:'Legal', status:'Published', author:'Emma Watson', views:1240, modified:'2026-05-20' },
    { id:'p9', title:'Features', slug:'/features', template:'Landing', status:'Draft', author:'Ryan Green', views:0, modified:'2026-07-20' },
    { id:'p10', title:'FAQ', slug:'/faq', template:'Blank', status:'Published', author:'Sarah Miller', views:6210, modified:'2026-06-15' },
    { id:'p11', title:'Blog Landing', slug:'/blog', template:'Blank', status:'Published', author:'Ava Lee', views:22150, modified:'2026-07-08' },
    { id:'p12', title:'Press Kit', slug:'/press', template:'About', status:'Draft', author:'Priya Patel', views:0, modified:'2026-07-19' },
    { id:'p13', title:'Partners', slug:'/partners', template:'Landing', status:'Scheduled', author:'Alex Kim', views:0, modified:'2026-07-22' },
    { id:'p14', title:'Newsroom', slug:'/newsroom', template:'Blank', status:'Scheduled', author:'James Doe', views:0, modified:'2026-07-21' },
    { id:'p15', title:'Legacy Landing', slug:'/legacy', template:'Landing', status:'Archived', author:'Ryan Green', views:120, modified:'2025-11-04' }
  ];
  var TEMPLATES = ['Landing','About','Legal','Contact','Blank'];

  var state = {
    q: '', status: '', template: '', author: '', from: '', to: '',
    sortKey: 'modified', sortDir: 'desc'
  };
  var sel;

  function filtered() {
    var q = state.q.trim().toLowerCase();
    return PAGES.filter(function (p) {
      if (q && p.title.toLowerCase().indexOf(q) === -1 && p.slug.toLowerCase().indexOf(q) === -1) return false;
      if (state.status && p.status !== state.status) return false;
      if (state.template && p.template !== state.template) return false;
      if (state.author && p.author !== state.author) return false;
      if (state.from && p.modified < state.from) return false;
      if (state.to && p.modified > state.to) return false;
      return true;
    }).sort(function (a, b) {
      var k = state.sortKey;
      var av = a[k], bv = b[k];
      if (typeof av === 'string') { av = av.toLowerCase(); bv = bv.toLowerCase(); }
      if (av < bv) return state.sortDir === 'asc' ? -1 : 1;
      if (av > bv) return state.sortDir === 'asc' ? 1 : -1;
      return 0;
    });
  }

  function statusCls(s) {
    return 'cms-status cms-status--' + s.toLowerCase();
  }

  function render() {
    var tbody = document.querySelector('[data-cms-pages-body]');
    if (!tbody) return;
    var rows = filtered();
    if (rows.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8"><div class="cms-empty"><i class="bi bi-file-earmark-x"></i>No pages match your filters.</div></td></tr>';
    } else {
      tbody.innerHTML = rows.map(function (p) {
        var checked = sel.has(p.id) ? 'checked' : '';
        return '<tr data-cms-page-row="' + p.id + '">' +
          '<td><input type="checkbox" class="form-check-input" data-cms-page-check="' + p.id + '" ' + checked + '></td>' +
          '<td>' +
            '<a href="#" class="cmsp-title" data-cms-page-edit="' + p.id + '">' + OrchidCMS.escapeHtml(p.title) + '</a>' +
            '<div class="cmsp-slug">' + OrchidCMS.escapeHtml(p.slug) + '</div>' +
          '</td>' +
          '<td><span class="cms-chip cms-chip--primary">' + OrchidCMS.escapeHtml(p.template) + '</span></td>' +
          '<td><span class="' + statusCls(p.status) + '">' + p.status + '</span></td>' +
          '<td><div class="d-flex align-items-center gap-2">' + OrchidCMS.renderAvatar(p.author, { size: 'sm' }) +
            '<span class="small">' + OrchidCMS.escapeHtml(p.author) + '</span></div></td>' +
          '<td class="fw-semibold">' + OrchidCMS.fmtNum(p.views) + '</td>' +
          '<td class="text-body-secondary small">' + OrchidCMS.fmtDate(p.modified) + '</td>' +
          '<td class="text-end">' +
            '<div class="btn-group btn-group-sm">' +
              '<button class="btn btn-icon" data-cms-page-view="' + p.id + '" aria-label="View" title="View"><i class="bi bi-eye"></i></button>' +
              '<button class="btn btn-icon" data-cms-page-edit="' + p.id + '" aria-label="Edit" title="Edit"><i class="bi bi-pencil"></i></button>' +
              '<div class="dropdown">' +
                '<button class="btn btn-icon" type="button" data-bs-toggle="dropdown" aria-label="More"><i class="bi bi-three-dots-vertical"></i></button>' +
                '<ul class="dropdown-menu dropdown-menu-end">' +
                  '<li><a class="dropdown-item" href="#" data-cms-page-dup="' + p.id + '"><i class="bi bi-files me-2"></i>Duplicate</a></li>' +
                  '<li><a class="dropdown-item" href="#" data-cms-page-toggle="' + p.id + '"><i class="bi bi-arrow-repeat me-2"></i>' + (p.status === 'Published' ? 'Unpublish' : 'Publish') + '</a></li>' +
                  '<li><hr class="dropdown-divider"></li>' +
                  '<li><a class="dropdown-item text-danger" href="#" data-cms-page-del="' + p.id + '"><i class="bi bi-trash me-2"></i>Delete</a></li>' +
                '</ul>' +
              '</div>' +
            '</div>' +
          '</td>' +
        '</tr>';
      }).join('');
    }
    // KPIs
    setText('[data-cms-pages-kpi-total]', PAGES.length);
    setText('[data-cms-pages-kpi-pub]', PAGES.filter(function(p){return p.status==='Published';}).length);
    setText('[data-cms-pages-kpi-draft]', PAGES.filter(function(p){return p.status==='Draft';}).length);
    setText('[data-cms-pages-kpi-sched]', PAGES.filter(function(p){return p.status==='Scheduled';}).length);
    setText('[data-cms-pages-count]', rows.length);
    if (window._cmsPagesBulkBar) window._cmsPagesBulkBar.refresh();
  }
  function setText(sel, val) {
    var n = document.querySelector(sel);
    if (n) n.textContent = val;
  }

  function init() {
    if (!document.querySelector('[data-cms-pages-body]')) return;
    sel = OrchidCMS.selection();

    // Author options
    var authors = Array.from(new Set(PAGES.map(function(p){return p.author;})));
    var authorSel = document.querySelector('[data-cms-pages-filter-author]');
    if (authorSel) {
      authors.forEach(function (a) {
        var o = document.createElement('option'); o.value = a; o.textContent = a; authorSel.appendChild(o);
      });
    }

    // Filter bindings
    document.querySelector('[data-cms-pages-filter-q]').addEventListener('input', OrchidCMS.debounce(function (e) { state.q = e.target.value; render(); }, 180));
    document.querySelector('[data-cms-pages-filter-status]').addEventListener('change', function (e) { state.status = e.target.value; render(); });
    document.querySelector('[data-cms-pages-filter-template]').addEventListener('change', function (e) { state.template = e.target.value; render(); });
    authorSel.addEventListener('change', function (e) { state.author = e.target.value; render(); });
    document.querySelector('[data-cms-pages-filter-from]').addEventListener('change', function (e) { state.from = e.target.value; render(); });
    document.querySelector('[data-cms-pages-filter-to]').addEventListener('change', function (e) { state.to = e.target.value; render(); });
    document.querySelector('[data-cms-pages-filter-reset]').addEventListener('click', function () {
      state = { q:'', status:'', template:'', author:'', from:'', to:'', sortKey:'modified', sortDir:'desc' };
      document.querySelector('[data-cms-pages-filter-q]').value = '';
      document.querySelector('[data-cms-pages-filter-status]').value = '';
      document.querySelector('[data-cms-pages-filter-template]').value = '';
      authorSel.value = '';
      document.querySelector('[data-cms-pages-filter-from]').value = '';
      document.querySelector('[data-cms-pages-filter-to]').value = '';
      render();
      OrchidCMS.toast('Filters reset', { variant: 'info' });
    });

    // Sortable headers
    OrchidCMS.initSortableHeader(document.querySelector('[data-cms-pages-thead]'), function (key, dir) {
      state.sortKey = key; state.sortDir = dir; render();
    });

    // Select all
    document.querySelector('[data-cms-pages-check-all]').addEventListener('change', function (e) {
      if (e.target.checked) filtered().forEach(function (p) { sel.add(p.id); });
      else sel.clear();
      render();
    });

    // Row-level clicks (event delegation)
    document.querySelector('[data-cms-pages-body]').addEventListener('click', function (e) {
      var t = e.target;
      var check = t.closest('[data-cms-page-check]');
      if (check) { sel.toggle(check.getAttribute('data-cms-page-check')); render(); return; }
      var edit = t.closest('[data-cms-page-edit]');
      if (edit) { e.preventDefault(); OrchidCMS.toast('Would open editor for this page', { variant: 'info' }); return; }
      var view = t.closest('[data-cms-page-view]');
      if (view) { e.preventDefault(); OrchidCMS.toast('Opening public preview…', { variant: 'primary' }); return; }
      var dup = t.closest('[data-cms-page-dup]');
      if (dup) { e.preventDefault(); duplicate(dup.getAttribute('data-cms-page-dup')); return; }
      var tog = t.closest('[data-cms-page-toggle]');
      if (tog) { e.preventDefault(); togglePub(tog.getAttribute('data-cms-page-toggle')); return; }
      var del = t.closest('[data-cms-page-del]');
      if (del) { e.preventDefault(); removeOne(del.getAttribute('data-cms-page-del')); return; }
    });

    // Bulk bar
    window._cmsPagesBulkBar = OrchidCMS.initBulkBar(document.querySelector('[data-cms-pages-bulkbar]'), sel);
    document.querySelector('[data-cms-pages-bulkbar]').addEventListener('click', function (e) {
      var btn = e.target.closest('[data-cms-bulk]');
      if (!btn) return;
      var act = btn.getAttribute('data-cms-bulk');
      var ids = sel.values();
      if (act === 'publish') { ids.forEach(function(id){ var p=find(id); if(p) p.status='Published'; }); OrchidCMS.toast(ids.length + ' page(s) published', { variant: 'success' }); }
      else if (act === 'unpublish') { ids.forEach(function(id){ var p=find(id); if(p) p.status='Draft'; }); OrchidCMS.toast(ids.length + ' page(s) unpublished', { variant: 'warning' }); }
      else if (act === 'archive') { ids.forEach(function(id){ var p=find(id); if(p) p.status='Archived'; }); OrchidCMS.toast(ids.length + ' page(s) archived', { variant: 'info' }); }
      else if (act === 'delete') { ids.forEach(function(id){ var i=PAGES.findIndex(function(x){return x.id===id;}); if(i>=0) PAGES.splice(i,1); }); OrchidCMS.toast(ids.length + ' page(s) deleted', { variant: 'danger' }); }
      sel.clear(); render();
    });

    // Create-page modal
    var modalEl = document.querySelector('[data-cms-pages-create-modal]');
    var form = document.querySelector('[data-cms-pages-create-form]');
    if (form) {
      var titleInp = form.querySelector('[data-cms-pages-create-title]');
      var slugInp = form.querySelector('[data-cms-pages-create-slug]');
      OrchidCMS.bindAutoSlug(titleInp, slugInp);
      // Template preset picker
      form.querySelectorAll('[data-cms-tpl-preset]').forEach(function (b) {
        b.addEventListener('click', function () {
          form.querySelectorAll('[data-cms-tpl-preset]').forEach(function(x){ x.classList.remove('is-selected'); });
          b.classList.add('is-selected');
          var v = b.getAttribute('data-cms-tpl-preset');
          form.querySelector('[data-cms-pages-create-tpl]').value = v;
        });
      });
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var t = titleInp.value.trim();
        if (!t) { OrchidCMS.toast('Title is required', { variant: 'danger' }); return; }
        PAGES.unshift({
          id: 'p' + (PAGES.length + 100),
          title: t,
          slug: slugInp.value.trim() || ('/' + OrchidCMS.slugify(t)),
          template: form.querySelector('[data-cms-pages-create-tpl]').value || 'Blank',
          status: 'Draft',
          author: 'Alex Kim',
          views: 0,
          modified: new Date().toISOString().slice(0,10)
        });
        form.reset();
        try { bootstrap.Modal.getInstance(modalEl).hide(); } catch (err) {}
        OrchidCMS.toast('Page "' + t + '" created', { variant: 'success' });
        render();
      });
    }

    render();
  }
  function find(id) { return PAGES.find(function (p) { return p.id === id; }); }
  function duplicate(id) {
    var p = find(id); if (!p) return;
    var copy = Object.assign({}, p);
    copy.id = 'p-dup-' + Date.now();
    copy.title = p.title + ' (Copy)';
    copy.slug = p.slug + '-copy';
    copy.status = 'Draft';
    copy.modified = new Date().toISOString().slice(0,10);
    PAGES.unshift(copy);
    OrchidCMS.toast('Duplicated page', { variant: 'success' });
    render();
  }
  function togglePub(id) {
    var p = find(id); if (!p) return;
    p.status = p.status === 'Published' ? 'Draft' : 'Published';
    OrchidCMS.toast('Page ' + (p.status === 'Published' ? 'published' : 'unpublished'), { variant: 'primary' });
    render();
  }
  function removeOne(id) {
    var i = PAGES.findIndex(function (p) { return p.id === id; });
    if (i >= 0) { PAGES.splice(i, 1); OrchidCMS.toast('Page deleted', { variant: 'danger' }); render(); }
  }

  window.OrchidCMSPages = { init: init };
})();


/* =====================================================
   Page: cms-blog.html — OrchidCMSBlog
   ===================================================== */
(function () {
  'use strict';
  var HEROES = ['','cmsb-card__hero--sun','cmsb-card__hero--forest','cmsb-card__hero--night','cmsb-card__hero--rose','cmsb-card__hero--sea','cmsb-card__hero--gold'];
  var CATEGORIES = ['Product','Engineering','Design','Marketing','Company','Leadership'];
  var POSTS = [
    { id:'b1', title:'Introducing Orchid 3.0 — the future of admin dashboards', excerpt:'Our biggest release yet: rebuilt navigation, dark theme polish, and a brand new template studio.', category:'Product', tags:['release','product'], author:'Alex Kim', date:'2026-07-18', status:'Published', read:6, views:12480, hero:3 },
    { id:'b2', title:'The state of admin dashboards 2026', excerpt:'We surveyed 1,200 teams to understand how they build internal tools. The results might surprise you.', category:'Design', tags:['research','ux'], author:'Sarah Miller', date:'2026-07-15', status:'Published', read:9, views:8940, hero:1 },
    { id:'b3', title:'How we scaled our sales team from 5 to 50', excerpt:'Lessons learned building a global revenue org — from hiring loops to enablement rituals.', category:'Leadership', tags:['sales','hiring'], author:'Ryan Green', date:'2026-07-12', status:'Published', read:12, views:5210, hero:2 },
    { id:'b4', title:'Design tokens: our workflow with Figma variables', excerpt:'Bridging design and code with a single source of truth. Here is our full pipeline.', category:'Design', tags:['tokens','figma'], author:'Ava Lee', date:'2026-07-08', status:'Published', read:7, views:4200, hero:4 },
    { id:'b5', title:'From monolith to modules: a 12-month migration story', excerpt:'What we learned splitting our platform into 40 services without breaking production.', category:'Engineering', tags:['architecture','platform'], author:'James Doe', date:'2026-07-05', status:'Published', read:15, views:3820, hero:5 },
    { id:'b6', title:'Why we killed our roadmap', excerpt:'A candid look at why we replaced quarterly planning with continuous discovery.', category:'Product', tags:['strategy'], author:'Priya Patel', date:'2026-07-01', status:'Published', read:5, views:6480, hero:6 },
    { id:'b7', title:'Meet the new Orchid charts engine', excerpt:'Composable, tiny, and blazing fast. Here is what makes it different.', category:'Engineering', tags:['charts','frontend'], author:'Alex Kim', date:'2026-06-28', status:'Published', read:8, views:2980, hero:1 },
    { id:'b8', title:'Announcing our SOC 2 Type II certification', excerpt:'A milestone for security — and a signal to our enterprise customers.', category:'Company', tags:['security','compliance'], author:'Emma Watson', date:'2026-06-24', status:'Published', read:3, views:1810, hero:2 },
    { id:'b9', title:'Building a marketing site that actually converts', excerpt:'Every experiment we ran to grow signups by 68% quarter-over-quarter.', category:'Marketing', tags:['growth'], author:'Priya Patel', date:'2026-06-20', status:'Published', read:9, views:2140, hero:3 },
    { id:'b10', title:'Onboarding: the underrated growth lever', excerpt:'Small changes, huge results. How we redesigned the first ten minutes of Orchid.', category:'Design', tags:['onboarding','growth'], author:'Ava Lee', date:'2026-06-16', status:'Published', read:6, views:2620, hero:4 },
    { id:'b11', title:'What we shipped in Q2', excerpt:'A recap of the biggest features, bug fixes, and improvements we delivered.', category:'Product', tags:['release'], author:'Alex Kim', date:'2026-06-12', status:'Published', read:4, views:1520, hero:5 },
    { id:'b12', title:'A framework for interviewing senior engineers', excerpt:'How we structure our loop to hire people who ship — not just those who talk about shipping.', category:'Engineering', tags:['hiring'], author:'James Doe', date:'2026-06-08', status:'Draft', read:11, views:0, hero:6 },
    { id:'b13', title:'Rebrand notes: our new design language', excerpt:'From gradients to grids, here is the thinking behind Orchid 3.0 visuals.', category:'Design', tags:['brand'], author:'Ava Lee', date:'2026-06-04', status:'Draft', read:10, views:0, hero:1 },
    { id:'b14', title:'How we do async standups', excerpt:'The ritual, the tooling, and the mistakes we made along the way.', category:'Company', tags:['culture'], author:'Sarah Miller', date:'2026-05-30', status:'Published', read:4, views:1240, hero:2 },
    { id:'b15', title:'Notes on our latest pricing overhaul', excerpt:'Why we moved to per-seat, how customers reacted, and what we learned.', category:'Marketing', tags:['pricing'], author:'Priya Patel', date:'2026-05-25', status:'Published', read:5, views:2410, hero:3 },
    { id:'b16', title:'Sneak peek: the new Reports module', excerpt:'A first look at report builder — coming to all workspaces next quarter.', category:'Product', tags:['preview'], author:'Alex Kim', date:'2026-05-20', status:'Scheduled', read:6, views:0, hero:4 },
    { id:'b17', title:'How to run better retros', excerpt:'Five formats we rotate through and what each one is best for.', category:'Leadership', tags:['team'], author:'Ryan Green', date:'2026-05-16', status:'Published', read:5, views:1420, hero:5 },
    { id:'b18', title:'Inside our support playbook', excerpt:'From triage to escalation, this is how our support team keeps customers happy.', category:'Company', tags:['support'], author:'Emma Watson', date:'2026-05-12', status:'Published', read:7, views:930, hero:6 }
  ];
  var TAGS = ['release','design','engineering','growth','hiring','culture','frontend','platform','security','charts','onboarding','sales','tokens','figma','preview','brand','ux','support'];
  var RECENT_COMMENTS = [
    { author:'Marcus Bell', on:'Introducing Orchid 3.0', text:'Been waiting for this release for months. The new nav is chef\'s kiss.', ago:'2 hours ago' },
    { author:'Rita Chen', on:'The state of admin dashboards 2026', text:'Great data, but I wish you had a breakdown by team size.', ago:'5 hours ago' },
    { author:'Diego Alvarez', on:'Design tokens', text:'Fantastic writeup — bookmarking this.', ago:'1 day ago' },
    { author:'Ivy Park', on:'Onboarding: the underrated growth lever', text:'We use a similar approach and it works wonders.', ago:'2 days ago' }
  ];

  var state = { q:'', status:'', category:'', author:'', from:'', to:'', view:'grid' };
  var sel;

  function filtered() {
    var q = state.q.trim().toLowerCase();
    return POSTS.filter(function (p) {
      if (q && p.title.toLowerCase().indexOf(q) === -1 && p.excerpt.toLowerCase().indexOf(q) === -1) return false;
      if (state.status && p.status !== state.status) return false;
      if (state.category && p.category !== state.category) return false;
      if (state.author && p.author !== state.author) return false;
      if (state.from && p.date < state.from) return false;
      if (state.to && p.date > state.to) return false;
      return true;
    });
  }

  function render() {
    var grid = document.querySelector('[data-cms-blog-grid]');
    if (!grid) return;
    var posts = filtered();
    if (posts.length === 0) {
      grid.innerHTML = '<div class="col-12"><div class="cms-empty"><i class="bi bi-journal-x"></i>No posts match your filters.</div></div>';
    } else if (state.view === 'grid') {
      grid.innerHTML = posts.map(function (p) {
        var img = window.OrchidCMS_postImage(p, 640, 360);
        return '<div class="col-12 col-sm-6 col-xl-4">' +
          '<article class="cmsb-card" data-cms-blog-item="' + p.id + '">' +
            '<div class="cmsb-card__hero ' + HEROES[p.hero % HEROES.length] + '">' +
              '<img class="cmsb-card__img" src="' + img + '" alt="' + OrchidCMS.escapeHtml(p.title) + '" loading="lazy" width="640" height="360">' +
              '<span class="cmsb-card__cat">' + OrchidCMS.escapeHtml(p.category) + '</span>' +
              '<span class="cmsb-card__status cms-status cms-status--' + p.status.toLowerCase() + '">' + p.status + '</span>' +
            '</div>' +
            '<div class="cmsb-card__body">' +
              '<h3 class="cmsb-card__title">' + OrchidCMS.escapeHtml(p.title) + '</h3>' +
              '<p class="cmsb-card__excerpt">' + OrchidCMS.escapeHtml(p.excerpt) + '</p>' +
              '<div class="cmsb-card__meta">' +
                '<span class="cmsb-card__author">' + OrchidCMS.renderAvatar(p.author, { size: 'sm' }) +
                  '<span class="fw-semibold">' + OrchidCMS.escapeHtml(p.author) + '</span></span>' +
                '<span><i class="bi bi-clock me-1"></i>' + p.read + ' min • ' + OrchidCMS.fmtDate(p.date) + '</span>' +
              '</div>' +
              '<div class="d-flex justify-content-between align-items-center mt-2">' +
                '<div class="form-check"><input type="checkbox" class="form-check-input" data-cms-blog-check="' + p.id + '" ' + (sel.has(p.id) ? 'checked' : '') + '></div>' +
                '<div class="btn-group btn-group-sm">' +
                  '<button class="btn btn-icon" data-cms-blog-edit="' + p.id + '" aria-label="Edit"><i class="bi bi-pencil"></i></button>' +
                  '<button class="btn btn-icon" data-cms-blog-toggle="' + p.id + '" aria-label="Toggle"><i class="bi bi-arrow-repeat"></i></button>' +
                  '<button class="btn btn-icon text-danger" data-cms-blog-del="' + p.id + '" aria-label="Delete"><i class="bi bi-trash"></i></button>' +
                '</div>' +
              '</div>' +
            '</div>' +
          '</article>' +
        '</div>';
      }).join('');
    } else {
      grid.innerHTML = '<div class="col-12">' + posts.map(function (p) {
        var listImg = window.OrchidCMS_postImage(p, 240, 160);
        return '<div class="cmsb-list-row" data-cms-blog-item="' + p.id + '">' +
          '<div class="cmsb-list-row__thumb ' + HEROES[p.hero % HEROES.length] + '"><img class="cmsb-list-row__img" src="' + listImg + '" alt="' + OrchidCMS.escapeHtml(p.title) + '" loading="lazy" width="120" height="80"></div>' +
          '<div class="cmsb-list-row__body">' +
            '<div class="d-flex align-items-center gap-2 mb-1">' +
              '<span class="cms-chip cms-chip--primary">' + OrchidCMS.escapeHtml(p.category) + '</span>' +
              '<span class="cms-status cms-status--' + p.status.toLowerCase() + '">' + p.status + '</span>' +
            '</div>' +
            '<p class="cmsb-list-row__title">' + OrchidCMS.escapeHtml(p.title) + '</p>' +
            '<div class="cmsb-list-row__meta">' + OrchidCMS.escapeHtml(p.author) + ' • ' + OrchidCMS.fmtDate(p.date) + ' • ' + p.read + ' min read • ' + OrchidCMS.fmtNum(p.views) + ' views</div>' +
          '</div>' +
          '<div class="btn-group btn-group-sm">' +
            '<button class="btn btn-icon" data-cms-blog-edit="' + p.id + '"><i class="bi bi-pencil"></i></button>' +
            '<button class="btn btn-icon" data-cms-blog-toggle="' + p.id + '"><i class="bi bi-arrow-repeat"></i></button>' +
            '<button class="btn btn-icon text-danger" data-cms-blog-del="' + p.id + '"><i class="bi bi-trash"></i></button>' +
          '</div>' +
        '</div>';
      }).join('') + '</div>';
    }
    setText('[data-cms-blog-count]', posts.length);
    setText('[data-cms-blog-kpi-total]', POSTS.length);
    setText('[data-cms-blog-kpi-pub]', POSTS.filter(function(p){return p.status==='Published';}).length);
    setText('[data-cms-blog-kpi-draft]', POSTS.filter(function(p){return p.status==='Draft';}).length);
    setText('[data-cms-blog-kpi-views]', OrchidCMS.fmtNum(POSTS.reduce(function(a,b){return a+(b.views||0);},0)));
    if (window._cmsBlogBulkBar) window._cmsBlogBulkBar.refresh();
  }
  function setText(sel, val) { var n = document.querySelector(sel); if (n) n.textContent = val; }

  function renderRightPanel() {
    // Top categories
    var byCat = {};
    POSTS.forEach(function (p) { byCat[p.category] = (byCat[p.category] || 0) + 1; });
    var cats = Object.keys(byCat).sort(function (a, b) { return byCat[b] - byCat[a]; });
    var colors = ['#4f46e5','#0ea5e9','#10b981','#f59e0b','#ec4899','#a855f7'];
    var catBox = document.querySelector('[data-cms-blog-topcats]');
    if (catBox) {
      catBox.innerHTML = cats.map(function (c, i) {
        return '<a href="#" class="cmsb-topcat">' +
          '<span class="cmsb-topcat__dot" style="background:' + colors[i % colors.length] + '"></span>' +
          '<span class="cmsb-topcat__name">' + OrchidCMS.escapeHtml(c) + '</span>' +
          '<span class="cmsb-topcat__count">' + byCat[c] + ' posts</span>' +
        '</a>';
      }).join('');
    }
    var tagBox = document.querySelector('[data-cms-blog-tags]');
    if (tagBox) {
      tagBox.innerHTML = TAGS.map(function (t) {
        return '<span class="cms-chip cms-chip--muted">#' + OrchidCMS.escapeHtml(t) + '</span>';
      }).join('');
    }
    var recBox = document.querySelector('[data-cms-blog-recent-comments]');
    if (recBox) {
      recBox.innerHTML = RECENT_COMMENTS.map(function (c) {
        return '<div class="cmsb-mini-comment">' +
          OrchidCMS.renderAvatar(c.author, { size: 'sm' }) +
          '<div class="flex-grow-1 min-w-0">' +
            '<p><strong>' + OrchidCMS.escapeHtml(c.author) + '</strong> on <em>' + OrchidCMS.escapeHtml(c.on) + '</em></p>' +
            '<p>' + OrchidCMS.escapeHtml(c.text) + '</p>' +
            '<small>' + OrchidCMS.escapeHtml(c.ago) + '</small>' +
          '</div>' +
        '</div>';
      }).join('');
    }
  }

  function init() {
    if (!document.querySelector('[data-cms-blog-grid]')) return;
    sel = OrchidCMS.selection();

    // Populate filter selects
    var catSel = document.querySelector('[data-cms-blog-filter-category]');
    if (catSel) CATEGORIES.forEach(function (c) { var o=document.createElement('option'); o.value=c; o.textContent=c; catSel.appendChild(o); });
    var authSel = document.querySelector('[data-cms-blog-filter-author]');
    var authors = Array.from(new Set(POSTS.map(function(p){return p.author;})));
    if (authSel) authors.forEach(function (a) { var o=document.createElement('option'); o.value=a; o.textContent=a; authSel.appendChild(o); });

    document.querySelector('[data-cms-blog-filter-q]').addEventListener('input', OrchidCMS.debounce(function (e) { state.q = e.target.value; render(); }, 180));
    document.querySelector('[data-cms-blog-filter-status]').addEventListener('change', function (e) { state.status = e.target.value; render(); });
    catSel.addEventListener('change', function (e) { state.category = e.target.value; render(); });
    authSel.addEventListener('change', function (e) { state.author = e.target.value; render(); });

    // View toggle
    OrchidCMS.qsa('[data-cms-blog-view]').forEach(function (b) {
      b.addEventListener('click', function () {
        state.view = b.getAttribute('data-cms-blog-view');
        OrchidCMS.qsa('[data-cms-blog-view]').forEach(function(x){ x.classList.remove('active'); });
        b.classList.add('active');
        render();
      });
    });

    // Grid click delegation
    document.querySelector('[data-cms-blog-grid]').addEventListener('click', function (e) {
      var t = e.target;
      var ch = t.closest('[data-cms-blog-check]'); if (ch) { sel.toggle(ch.getAttribute('data-cms-blog-check')); render(); return; }
      var ed = t.closest('[data-cms-blog-edit]'); if (ed) { e.preventDefault(); openEditor(ed.getAttribute('data-cms-blog-edit')); return; }
      var tg = t.closest('[data-cms-blog-toggle]'); if (tg) { e.preventDefault(); toggleStatus(tg.getAttribute('data-cms-blog-toggle')); return; }
      var dl = t.closest('[data-cms-blog-del]'); if (dl) { e.preventDefault(); removeOne(dl.getAttribute('data-cms-blog-del')); return; }
    });

    // Bulk
    window._cmsBlogBulkBar = OrchidCMS.initBulkBar(document.querySelector('[data-cms-blog-bulkbar]'), sel);
    document.querySelector('[data-cms-blog-bulkbar]').addEventListener('click', function (e) {
      var btn = e.target.closest('[data-cms-bulk]'); if (!btn) return;
      var act = btn.getAttribute('data-cms-bulk'); var ids = sel.values();
      if (act === 'publish') { ids.forEach(function(id){ var p=find(id); if(p) p.status='Published'; }); OrchidCMS.toast(ids.length + ' post(s) published', { variant: 'success' }); }
      else if (act === 'unpublish') { ids.forEach(function(id){ var p=find(id); if(p) p.status='Draft'; }); OrchidCMS.toast(ids.length + ' post(s) moved to draft', { variant: 'warning' }); }
      else if (act === 'delete') { ids.forEach(function(id){ var i=POSTS.findIndex(function(x){return x.id===id;}); if(i>=0) POSTS.splice(i,1); }); OrchidCMS.toast(ids.length + ' post(s) deleted', { variant: 'danger' }); }
      else if (act === 'feature') { OrchidCMS.toast(ids.length + ' post(s) marked as featured', { variant: 'primary' }); }
      else if (act === 'tag') { OrchidCMS.toast('Tag added to ' + ids.length + ' post(s)', { variant: 'info' }); }
      sel.clear(); render();
    });

    // New Post modal / editor
    var editorForm = document.querySelector('[data-cms-blog-editor-form]');
    if (editorForm) {
      var titleInp = editorForm.querySelector('[data-cms-blog-editor-title]');
      var slugInp = editorForm.querySelector('[data-cms-blog-editor-slug]');
      OrchidCMS.bindAutoSlug(titleInp, slugInp);
      // Populate category
      var catInp = editorForm.querySelector('[data-cms-blog-editor-cat]');
      CATEGORIES.forEach(function (c) { var o=document.createElement('option'); o.value=c; o.textContent=c; catInp.appendChild(o); });
      // Cover uploader
      var cover = editorForm.querySelector('[data-cms-blog-editor-cover]');
      if (cover) cover.addEventListener('click', function () { OrchidCMS.openFilePicker({ onFiles: function () { OrchidCMS.toast('Cover image selected', { variant: 'info' }); cover.classList.add('is-hover'); cover.querySelector('.cmsm-drop-text').textContent = 'cover-image.jpg selected'; } }); });
      editorForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var t = titleInp.value.trim();
        if (!t) { OrchidCMS.toast('Title is required', { variant: 'danger' }); return; }
        var newPost = {
          id: 'b-new-' + Date.now(),
          title: t,
          excerpt: (editorForm.querySelector('[data-cms-blog-editor-area]').innerText || '').slice(0, 140),
          category: catInp.value || 'Product',
          tags: [],
          author: 'Alex Kim',
          date: new Date().toISOString().slice(0,10),
          status: e.submitter && e.submitter.getAttribute('data-cms-publish') === 'now' ? 'Published' : 'Draft',
          read: 5,
          views: 0,
          hero: Math.floor(Math.random() * HEROES.length)
        };
        POSTS.unshift(newPost);
        editorForm.reset();
        editorForm.querySelector('[data-cms-blog-editor-area]').innerHTML = '';
        try { bootstrap.Modal.getInstance(document.querySelector('[data-cms-blog-editor-modal]')).hide(); } catch (err) {}
        OrchidCMS.toast('Post "' + t + '" saved as ' + newPost.status, { variant: 'success' });
        render();
      });
    }

    render();
    renderRightPanel();
  }
  function find(id) { return POSTS.find(function (p) { return p.id === id; }); }
  function openEditor(id) {
    var p = find(id); if (!p) return;
    var form = document.querySelector('[data-cms-blog-editor-form]');
    if (!form) return;
    form.querySelector('[data-cms-blog-editor-title]').value = p.title;
    form.querySelector('[data-cms-blog-editor-slug]').value = OrchidCMS.slugify(p.title);
    form.querySelector('[data-cms-blog-editor-cat]').value = p.category;
    form.querySelector('[data-cms-blog-editor-area]').innerText = p.excerpt;
    try { new bootstrap.Modal(document.querySelector('[data-cms-blog-editor-modal]')).show(); } catch (err) {}
  }
  function toggleStatus(id) {
    var p = find(id); if (!p) return;
    p.status = p.status === 'Published' ? 'Draft' : 'Published';
    OrchidCMS.toast('Post ' + (p.status === 'Published' ? 'published' : 'unpublished'), { variant: 'primary' });
    render();
  }
  function removeOne(id) {
    var i = POSTS.findIndex(function (p) { return p.id === id; });
    if (i >= 0) { POSTS.splice(i, 1); OrchidCMS.toast('Post deleted', { variant: 'danger' }); render(); }
  }

  window.OrchidCMSBlog = { init: init };
})();


/* =====================================================
   Page: cms-media.html — OrchidCMSMedia
   ===================================================== */
(function () {
  'use strict';
  var TYPE_ICONS = {
    image: 'image', video: 'camera-video', doc: 'file-earmark-text', audio: 'music-note-beamed', other: 'file-earmark'
  };
  var FOLDERS = [
    { id:'all', name:'All Files', icon:'folder' },
    { id:'image', name:'Images', icon:'images' },
    { id:'video', name:'Videos', icon:'camera-video' },
    { id:'doc', name:'Documents', icon:'file-earmark-text' },
    { id:'audio', name:'Audio', icon:'music-note-beamed' },
    { id:'other', name:'Other', icon:'file-earmark' },
    { id:'brand', name:'Brand Assets', icon:'palette', custom:true },
    { id:'marketing', name:'Marketing', icon:'megaphone', custom:true },
    { id:'product', name:'Product Screens', icon:'display', custom:true }
  ];
  var MEDIA = [
    { id:'m1', name:'hero-banner-01.jpg', type:'image', folder:'marketing', size: 2_400_000, dim:'2400x1350', uploaded:'2026-07-20', by:'Ava Lee' },
    { id:'m2', name:'orchid-logo-primary.svg', type:'image', folder:'brand', size: 8_400, dim:'1024x1024', uploaded:'2026-07-18', by:'Ava Lee' },
    { id:'m3', name:'product-tour-2026.mp4', type:'video', folder:'product', size: 18_400_000, dim:'1920x1080', uploaded:'2026-07-15', by:'Alex Kim' },
    { id:'m4', name:'brand-guidelines.pdf', type:'doc', folder:'brand', size: 4_200_000, dim:'', uploaded:'2026-07-14', by:'Emma Watson' },
    { id:'m5', name:'about-team-photo.jpg', type:'image', folder:'marketing', size: 3_100_000, dim:'2000x1333', uploaded:'2026-07-12', by:'Ryan Green' },
    { id:'m6', name:'launch-podcast.mp3', type:'audio', folder:'marketing', size: 12_800_000, dim:'', uploaded:'2026-07-10', by:'Priya Patel' },
    { id:'m7', name:'q2-report.pdf', type:'doc', folder:'doc', size: 1_800_000, dim:'', uploaded:'2026-07-08', by:'James Doe' },
    { id:'m8', name:'signup-flow.png', type:'image', folder:'product', size: 620_000, dim:'1440x900', uploaded:'2026-07-06', by:'Ava Lee' },
    { id:'m9', name:'orchid-favicon.ico', type:'image', folder:'brand', size: 22_000, dim:'32x32', uploaded:'2026-07-05', by:'Ava Lee' },
    { id:'m10', name:'testimonial-emma.mp4', type:'video', folder:'marketing', size: 24_500_000, dim:'1920x1080', uploaded:'2026-07-04', by:'Ryan Green' },
    { id:'m11', name:'pricing-hero.webp', type:'image', folder:'marketing', size: 480_000, dim:'1600x900', uploaded:'2026-07-03', by:'Priya Patel' },
    { id:'m12', name:'onboarding-tour-01.mp4', type:'video', folder:'product', size: 8_200_000, dim:'1440x900', uploaded:'2026-07-02', by:'Alex Kim' },
    { id:'m13', name:'invoice-template.docx', type:'doc', folder:'doc', size: 220_000, dim:'', uploaded:'2026-06-30', by:'Emma Watson' },
    { id:'m14', name:'roadmap-Q3.pdf', type:'doc', folder:'doc', size: 1_100_000, dim:'', uploaded:'2026-06-28', by:'Alex Kim' },
    { id:'m15', name:'dashboard-preview.png', type:'image', folder:'product', size: 810_000, dim:'2560x1440', uploaded:'2026-06-26', by:'Ava Lee' },
    { id:'m16', name:'brand-logo-dark.svg', type:'image', folder:'brand', size: 6_200, dim:'1024x1024', uploaded:'2026-06-24', by:'Ava Lee' },
    { id:'m17', name:'app-icon.png', type:'image', folder:'brand', size: 74_000, dim:'512x512', uploaded:'2026-06-22', by:'Ava Lee' },
    { id:'m18', name:'audio-jingle.wav', type:'audio', folder:'brand', size: 3_400_000, dim:'', uploaded:'2026-06-20', by:'Priya Patel' },
    { id:'m19', name:'demo-recording.webm', type:'video', folder:'product', size: 15_200_000, dim:'1920x1080', uploaded:'2026-06-18', by:'James Doe' },
    { id:'m20', name:'stakeholder-deck.pdf', type:'doc', folder:'doc', size: 5_800_000, dim:'', uploaded:'2026-06-16', by:'Alex Kim' },
    { id:'m21', name:'feature-mock-01.png', type:'image', folder:'product', size: 1_300_000, dim:'2000x1250', uploaded:'2026-06-14', by:'Ava Lee' },
    { id:'m22', name:'campaign-banner.jpg', type:'image', folder:'marketing', size: 2_100_000, dim:'2560x1440', uploaded:'2026-06-12', by:'Priya Patel' },
    { id:'m23', name:'sample-audio.mp3', type:'audio', folder:'marketing', size: 5_600_000, dim:'', uploaded:'2026-06-10', by:'Ryan Green' },
    { id:'m24', name:'legal-notice.txt', type:'other', folder:'doc', size: 4_800, dim:'', uploaded:'2026-06-08', by:'Emma Watson' },
    { id:'m25', name:'settings-flow.png', type:'image', folder:'product', size: 720_000, dim:'1440x900', uploaded:'2026-06-06', by:'Ava Lee' },
    { id:'m26', name:'landing-page-video.mp4', type:'video', folder:'marketing', size: 22_800_000, dim:'1920x1080', uploaded:'2026-06-04', by:'Ryan Green' },
    { id:'m27', name:'brand-typography.pdf', type:'doc', folder:'brand', size: 2_400_000, dim:'', uploaded:'2026-06-02', by:'Ava Lee' },
    { id:'m28', name:'analytics-mock.png', type:'image', folder:'product', size: 940_000, dim:'1920x1080', uploaded:'2026-05-30', by:'Ava Lee' },
    { id:'m29', name:'welcome-jingle.wav', type:'audio', folder:'brand', size: 1_800_000, dim:'', uploaded:'2026-05-28', by:'Priya Patel' },
    { id:'m30', name:'notes.md', type:'other', folder:'doc', size: 2_100, dim:'', uploaded:'2026-05-26', by:'Sarah Miller' }
  ];

  var state = { folder:'all', types:['image','video','doc','audio','other'], q:'', sort:'newest', view:'grid' };
  var sel;

  function filtered() {
    var q = state.q.trim().toLowerCase();
    var out = MEDIA.filter(function (m) {
      if (state.folder !== 'all' && m.folder !== state.folder && m.type !== state.folder) return false;
      if (state.types.length && state.types.indexOf(m.type) === -1) return false;
      if (q && m.name.toLowerCase().indexOf(q) === -1) return false;
      return true;
    });
    out.sort(function (a, b) {
      if (state.sort === 'name') return a.name.localeCompare(b.name);
      if (state.sort === 'size') return b.size - a.size;
      if (state.sort === 'type') return a.type.localeCompare(b.type);
      return b.uploaded.localeCompare(a.uploaded); // newest
    });
    return out;
  }

  function renderTile(m) {
    var iconName = TYPE_ICONS[m.type] || 'file-earmark';
    var selected = sel.has(m.id) ? 'is-selected' : '';
    return '<div class="cmsm-tile ' + selected + '" data-cms-media-tile="' + m.id + '">' +
      '<div class="form-check cmsm-tile__check">' +
        '<input type="checkbox" class="form-check-input" data-cms-media-check="' + m.id + '" ' + (sel.has(m.id) ? 'checked' : '') + '>' +
      '</div>' +
      '<div class="cmsm-tile__thumb cmsm-tile__thumb--' + m.type + '">' +
        '<i class="bi bi-' + iconName + '" aria-hidden="true"></i>' +
      '</div>' +
      '<div class="cmsm-tile__actions">' +
        '<button class="cmsm-tile__action" data-cms-media-detail="' + m.id + '" aria-label="View" title="View"><i class="bi bi-eye"></i></button>' +
        '<button class="cmsm-tile__action" data-cms-media-copy="' + m.id + '" aria-label="Copy URL" title="Copy URL"><i class="bi bi-link-45deg"></i></button>' +
        '<button class="cmsm-tile__action" data-cms-media-download="' + m.id + '" aria-label="Download" title="Download"><i class="bi bi-download"></i></button>' +
        '<button class="cmsm-tile__action" data-cms-media-del="' + m.id + '" aria-label="Delete" title="Delete"><i class="bi bi-trash"></i></button>' +
      '</div>' +
      '<div class="cmsm-tile__body">' +
        '<p class="cmsm-tile__name" title="' + OrchidCMS.escapeHtml(m.name) + '">' + OrchidCMS.escapeHtml(m.name) + '</p>' +
        '<div class="cmsm-tile__meta">' +
          '<span class="cms-chip cms-chip--muted text-uppercase">' + m.type + '</span>' +
          '<span>' + OrchidCMS.fmtSize(m.size) + '</span>' +
        '</div>' +
      '</div>' +
    '</div>';
  }

  function render() {
    var grid = document.querySelector('[data-cms-media-grid]');
    if (!grid) return;
    var items = filtered();
    // Also inject a big icon via CSS style (use inline SVG for larger area)
    grid.className = state.view === 'list' ? 'd-flex flex-column gap-2' : 'cmsm-grid';
    if (items.length === 0) {
      grid.innerHTML = '<div class="cms-empty"><i class="bi bi-folder-x"></i>No media items match.</div>';
    } else {
      grid.innerHTML = items.map(renderTile).join('');
    }
    setText('[data-cms-media-count]', items.length);
    updateFolderCounts();
    if (window._cmsMediaBulkBar) window._cmsMediaBulkBar.refresh();
  }

  function setText(sel, val) { var n = document.querySelector(sel); if (n) n.textContent = val; }

  function updateFolderCounts() {
    OrchidCMS.qsa('[data-cms-media-folder]').forEach(function (el) {
      var f = el.getAttribute('data-cms-media-folder');
      var c;
      if (f === 'all') c = MEDIA.length;
      else if (['image','video','doc','audio','other'].indexOf(f) !== -1) c = MEDIA.filter(function(m){return m.type===f;}).length;
      else c = MEDIA.filter(function(m){return m.folder===f;}).length;
      var cnt = el.querySelector('.cmsm-folder__count');
      if (cnt) cnt.textContent = c;
    });
  }

  function init() {
    if (!document.querySelector('[data-cms-media-grid]')) return;
    sel = OrchidCMS.selection();

    // Folder tree
    var tree = document.querySelector('[data-cms-media-folders]');
    if (tree) {
      tree.innerHTML = FOLDERS.map(function (f) {
        return '<li><div class="cmsm-folder ' + (f.id === state.folder ? 'is-active' : '') + '" data-cms-media-folder="' + f.id + '">' +
          '<i class="bi bi-' + f.icon + '"></i><span>' + OrchidCMS.escapeHtml(f.name) + '</span>' +
          '<span class="cmsm-folder__count">0</span>' +
        '</div></li>';
      }).join('');
      tree.addEventListener('click', function (e) {
        var f = e.target.closest('[data-cms-media-folder]');
        if (!f) return;
        state.folder = f.getAttribute('data-cms-media-folder');
        OrchidCMS.qsa('[data-cms-media-folder]').forEach(function(x){ x.classList.remove('is-active'); });
        f.classList.add('is-active');
        render();
      });
    }

    // Type filter checkboxes
    OrchidCMS.qsa('[data-cms-media-type]').forEach(function (chk) {
      chk.addEventListener('change', function () {
        var v = chk.value;
        if (chk.checked) { if (state.types.indexOf(v) === -1) state.types.push(v); }
        else { state.types = state.types.filter(function(x){ return x !== v; }); }
        render();
      });
    });

    // Search + sort + view
    document.querySelector('[data-cms-media-q]').addEventListener('input', OrchidCMS.debounce(function (e) { state.q = e.target.value; render(); }, 180));
    document.querySelector('[data-cms-media-sort]').addEventListener('change', function (e) { state.sort = e.target.value; render(); });
    OrchidCMS.qsa('[data-cms-media-view]').forEach(function (b) {
      b.addEventListener('click', function () {
        state.view = b.getAttribute('data-cms-media-view');
        OrchidCMS.qsa('[data-cms-media-view]').forEach(function(x){ x.classList.remove('active'); });
        b.classList.add('active');
        render();
      });
    });
    document.querySelector('[data-cms-media-select-all]').addEventListener('click', function () {
      filtered().forEach(function (m) { sel.add(m.id); });
      OrchidCMS.toast('Selected all visible items', { variant: 'info' });
      render();
    });

    // Grid delegation
    document.querySelector('[data-cms-media-grid]').addEventListener('click', function (e) {
      var t = e.target;
      var ch = t.closest('[data-cms-media-check]'); if (ch) { sel.toggle(ch.getAttribute('data-cms-media-check')); render(); return; }
      var det = t.closest('[data-cms-media-detail]'); if (det) { e.preventDefault(); openDetail(det.getAttribute('data-cms-media-detail')); return; }
      var cp = t.closest('[data-cms-media-copy]'); if (cp) { e.preventDefault(); OrchidCMS.toast('URL copied to clipboard', { variant: 'success' }); return; }
      var dl = t.closest('[data-cms-media-download]'); if (dl) { e.preventDefault(); OrchidCMS.toast('Download started', { variant: 'info' }); return; }
      var del = t.closest('[data-cms-media-del]'); if (del) { e.preventDefault(); removeOne(del.getAttribute('data-cms-media-del')); return; }
      var tile = t.closest('[data-cms-media-tile]');
      if (tile && !t.closest('button') && !t.closest('input')) openDetail(tile.getAttribute('data-cms-media-tile'));
    });

    // Bulk
    window._cmsMediaBulkBar = OrchidCMS.initBulkBar(document.querySelector('[data-cms-media-bulkbar]'), sel);
    document.querySelector('[data-cms-media-bulkbar]').addEventListener('click', function (e) {
      var btn = e.target.closest('[data-cms-bulk]'); if (!btn) return;
      var act = btn.getAttribute('data-cms-bulk'); var ids = sel.values();
      if (act === 'move') OrchidCMS.toast(ids.length + ' item(s) moved to folder', { variant: 'primary' });
      else if (act === 'tag') OrchidCMS.toast('Tags applied to ' + ids.length + ' item(s)', { variant: 'info' });
      else if (act === 'zip') OrchidCMS.toast('Preparing ZIP of ' + ids.length + ' items…', { variant: 'info' });
      else if (act === 'delete') { ids.forEach(function(id){ var i=MEDIA.findIndex(function(x){return x.id===id;}); if(i>=0) MEDIA.splice(i,1); }); OrchidCMS.toast(ids.length + ' item(s) deleted', { variant: 'danger' }); }
      sel.clear(); render();
    });

    // Upload modal
    var upModal = document.querySelector('[data-cms-media-upload-modal]');
    var drop = document.querySelector('[data-cms-media-drop]');
    var list = document.querySelector('[data-cms-media-upload-list]');
    function processFiles(files) {
      if (!files || files.length === 0) return;
      Array.from(files).forEach(function (f) {
        var row = document.createElement('div');
        row.className = 'cmsm-upload-item';
        row.innerHTML =
          '<i class="bi bi-file-earmark-arrow-up text-primary"></i>' +
          '<div class="cmsm-upload-item__name">' + OrchidCMS.escapeHtml(f.name) + '</div>' +
          '<div class="progress" role="progressbar"><div class="progress-bar" data-cms-progress></div></div>' +
          '<span class="small text-body-secondary" data-cms-pct>0%</span>';
        list.appendChild(row);
        var bar = row.querySelector('[data-cms-progress]');
        var pct = row.querySelector('[data-cms-pct]');
        OrchidCMS.simulateUpload(function (p) { bar.style.width = p + '%'; pct.textContent = p + '%'; }, function () {
          pct.innerHTML = '<i class="bi bi-check-circle text-success"></i>';
          var ext = (f.name.split('.').pop() || '').toLowerCase();
          var type = /jpe?g|png|gif|webp|svg|ico/.test(ext) ? 'image'
                    : /mp4|webm|mov/.test(ext) ? 'video'
                    : /mp3|wav|ogg/.test(ext) ? 'audio'
                    : /pdf|docx?|xlsx?|pptx?|txt|md/.test(ext) ? 'doc' : 'other';
          MEDIA.unshift({ id:'up-'+Date.now(), name:f.name, type:type, folder: state.folder === 'all' ? 'marketing' : state.folder,
                          size:f.size, dim:'', uploaded:new Date().toISOString().slice(0,10), by:'Alex Kim' });
          render();
        });
      });
    }
    if (drop) {
      drop.addEventListener('click', function () { OrchidCMS.openFilePicker({ multiple:true, onFiles: processFiles }); });
      drop.addEventListener('dragover', function (e) { e.preventDefault(); drop.classList.add('is-hover'); });
      drop.addEventListener('dragleave', function () { drop.classList.remove('is-hover'); });
      drop.addEventListener('drop', function (e) { e.preventDefault(); drop.classList.remove('is-hover'); processFiles(e.dataTransfer.files); });
    }

    render();
  }
  function find(id) { return MEDIA.find(function (m) { return m.id === id; }); }
  function removeOne(id) {
    var i = MEDIA.findIndex(function (m) { return m.id === id; });
    if (i >= 0) { MEDIA.splice(i, 1); OrchidCMS.toast('Item deleted', { variant: 'danger' }); render(); }
  }
  function openDetail(id) {
    var m = find(id); if (!m) return;
    var drawer = document.querySelector('[data-cms-media-drawer]');
    if (!drawer) return;
    drawer.querySelector('[data-cms-media-drawer-name]').textContent = m.name;
    var prev = drawer.querySelector('[data-cms-media-drawer-preview]');
    prev.className = 'cmsm-drawer-preview cmsm-tile__thumb cmsm-tile__thumb--' + m.type;
    prev.innerHTML = '<i class="bi bi-' + (TYPE_ICONS[m.type] || 'file-earmark') + '"></i>';
    drawer.querySelector('[data-cms-media-drawer-dim]').textContent = m.dim || '—';
    drawer.querySelector('[data-cms-media-drawer-size]').textContent = OrchidCMS.fmtSize(m.size);
    drawer.querySelector('[data-cms-media-drawer-type]').textContent = m.type.toUpperCase();
    drawer.querySelector('[data-cms-media-drawer-uploaded]').textContent = OrchidCMS.fmtDate(m.uploaded);
    drawer.querySelector('[data-cms-media-drawer-by]').textContent = m.by;
    drawer.querySelector('[data-cms-media-drawer-url]').value = 'https://cdn.orchid.io/media/' + m.name;
    try { new bootstrap.Offcanvas(drawer).show(); } catch (err) {}
  }

  window.OrchidCMSMedia = { init: init };
})();


/* =====================================================
   Page: cms-categories.html — OrchidCMSCategories
   ===================================================== */
(function () {
  'use strict';
  var COLORS = ['#4f46e5','#0ea5e9','#10b981','#f59e0b','#ef4444','#a855f7','#ec4899','#14b8a6'];
  var TREE = [
    { id:'c1', name:'Product', color:'#4f46e5', desc:'Everything related to our platform.', count:24, order:1, types:['Pages','Posts'], children:[
      { id:'c1-1', name:'Releases', color:'#4f46e5', desc:'Release announcements.', count:12, order:1, types:['Posts'], children:[] },
      { id:'c1-2', name:'Roadmap', color:'#0ea5e9', desc:'What we\'re building next.', count:6, order:2, types:['Pages','Posts'], children:[] },
      { id:'c1-3', name:'Changelog', color:'#10b981', desc:'Detailed changes shipped.', count:6, order:3, types:['Posts'], children:[] }
    ] },
    { id:'c2', name:'Engineering', color:'#0ea5e9', desc:'Deep dives from the engineering team.', count:18, order:2, types:['Posts','Media'], children:[
      { id:'c2-1', name:'Frontend', color:'#0ea5e9', desc:'Web platform posts.', count:8, order:1, types:['Posts'], children:[] },
      { id:'c2-2', name:'Backend', color:'#a855f7', desc:'Distributed systems, APIs.', count:7, order:2, types:['Posts'], children:[] },
      { id:'c2-3', name:'DevOps', color:'#f59e0b', desc:'Infra & tooling.', count:3, order:3, types:['Posts'], children:[] }
    ] },
    { id:'c3', name:'Design', color:'#ec4899', desc:'From the design & brand teams.', count:14, order:3, types:['Posts','Media'], children:[
      { id:'c3-1', name:'Brand', color:'#ec4899', desc:'Brand system & identity.', count:5, order:1, types:['Media'], children:[] },
      { id:'c3-2', name:'UX Research', color:'#14b8a6', desc:'Insights from user studies.', count:9, order:2, types:['Posts'], children:[] }
    ] },
    { id:'c4', name:'Marketing', color:'#f59e0b', desc:'Growth, campaigns and content.', count:10, order:4, types:['Posts'], children:[] },
    { id:'c5', name:'Company', color:'#a855f7', desc:'Company news, milestones & culture.', count:8, order:5, types:['Posts','Pages'], children:[] },
    { id:'c6', name:'Leadership', color:'#ef4444', desc:'Long-form essays from our leadership.', count:6, order:6, types:['Posts'], children:[] },
    { id:'c7', name:'Support', color:'#10b981', desc:'Help center topics.', count:16, order:7, types:['Pages'], children:[
      { id:'c7-1', name:'Getting Started', color:'#10b981', desc:'New user guides.', count:8, order:1, types:['Pages'], children:[] },
      { id:'c7-2', name:'Troubleshooting', color:'#f59e0b', desc:'Common problems & fixes.', count:8, order:2, types:['Pages'], children:[] }
    ] }
  ];

  var selectedId = 'c1';
  var expanded = new Set(['c1','c2','c3','c7']);
  var searchQ = '';

  function walk(nodes, fn, parent) {
    nodes.forEach(function (n) { fn(n, parent); if (n.children && n.children.length) walk(n.children, fn, n); });
  }
  function find(id, nodes) {
    nodes = nodes || TREE;
    for (var i = 0; i < nodes.length; i++) {
      if (nodes[i].id === id) return nodes[i];
      var c = find(id, nodes[i].children || []);
      if (c) return c;
    }
    return null;
  }
  function findParent(id, nodes, parent) {
    nodes = nodes || TREE;
    for (var i = 0; i < nodes.length; i++) {
      if (nodes[i].id === id) return parent;
      var c = findParent(id, nodes[i].children || [], nodes[i]);
      if (c !== undefined) return c;
    }
    return undefined;
  }
  function removeNode(id) {
    var p = findParent(id);
    var arr = p ? p.children : TREE;
    var i = arr.findIndex(function(n){ return n.id === id; });
    if (i >= 0) arr.splice(i, 1);
  }

  function renderTree() {
    var host = document.querySelector('[data-cms-cats-tree]');
    if (!host) return;
    function nodeHtml(n) {
      var hasChild = n.children && n.children.length > 0;
      var isExp = expanded.has(n.id);
      var isSel = selectedId === n.id;
      var matches = !searchQ || n.name.toLowerCase().indexOf(searchQ) !== -1
                    || (n.children || []).some(function(c){ return c.name.toLowerCase().indexOf(searchQ) !== -1; });
      if (!matches) return '';
      return '<li>' +
        '<div class="cmsc-node ' + (isSel ? 'is-selected' : '') + '" data-cms-drag="' + n.id + '" data-cms-cat-node="' + n.id + '" draggable="true">' +
          '<i class="bi bi-grip-vertical cmsc-node__handle" data-cms-noselect></i>' +
          '<button class="cmsc-node__chev ' + (hasChild ? (isExp ? '' : 'is-collapsed') : 'cmsc-node__chev--empty') + '" data-cms-cat-chev="' + n.id + '" aria-label="Toggle"><i class="bi bi-chevron-down"></i></button>' +
          '<span class="cmsc-node__dot" style="background:' + n.color + '"></span>' +
          '<span class="cmsc-node__name" data-cms-cat-select="' + n.id + '">' + OrchidCMS.escapeHtml(n.name) + '</span>' +
          '<span class="cmsc-node__count">' + n.count + '</span>' +
          '<span class="cmsc-node__actions">' +
            '<button class="btn btn-icon btn-sm" data-cms-cat-add-sub="' + n.id + '" title="Add subcategory"><i class="bi bi-plus-lg"></i></button>' +
            '<button class="btn btn-icon btn-sm" data-cms-cat-edit="' + n.id + '" title="Edit"><i class="bi bi-pencil"></i></button>' +
            '<button class="btn btn-icon btn-sm text-danger" data-cms-cat-del="' + n.id + '" title="Delete"><i class="bi bi-trash"></i></button>' +
          '</span>' +
        '</div>' +
        (hasChild && isExp ? '<ul>' + n.children.map(nodeHtml).join('') + '</ul>' : '') +
      '</li>';
    }
    host.innerHTML = TREE.map(nodeHtml).join('') || '<div class="cms-empty"><i class="bi bi-diagram-3"></i>No categories yet.</div>';
    var count = 0; walk(TREE, function(){ count++; });
    var cnt = document.querySelector('[data-cms-cats-total]');
    if (cnt) cnt.textContent = count;
  }

  function renderEditor() {
    var host = document.querySelector('[data-cms-cats-editor]');
    if (!host) return;
    var n = find(selectedId);
    if (!n) { host.innerHTML = '<div class="cms-empty"><i class="bi bi-arrow-left-circle"></i>Select a category to edit.</div>'; return; }
    host.innerHTML =
      '<form data-cms-cats-editor-form>' +
        '<div class="d-flex align-items-center gap-2 mb-3">' +
          '<span class="cmsc-node__dot" style="background:' + n.color + ';width:16px;height:16px"></span>' +
          '<input type="text" class="form-control form-control-lg fw-bold border-0 px-0" value="' + OrchidCMS.escapeHtml(n.name) + '" data-cms-cats-name>' +
        '</div>' +
        '<div class="row g-3">' +
          '<div class="col-md-6">' +
            '<label class="form-label small text-body-secondary">Slug</label>' +
            '<input type="text" class="form-control" value="' + OrchidCMS.slugify(n.name) + '" data-cms-cats-slug>' +
          '</div>' +
          '<div class="col-md-6">' +
            '<label class="form-label small text-body-secondary">Parent</label>' +
            '<select class="form-select" data-cms-cats-parent>' +
              '<option value="">(Top level)</option>' +
              parentOptions(n.id) +
            '</select>' +
          '</div>' +
          '<div class="col-12">' +
            '<label class="form-label small text-body-secondary">Description</label>' +
            '<textarea class="form-control" rows="3" data-cms-cats-desc>' + OrchidCMS.escapeHtml(n.desc) + '</textarea>' +
          '</div>' +
          '<div class="col-md-6">' +
            '<label class="form-label small text-body-secondary">Cover color</label>' +
            '<div class="cmsc-editor__colors">' +
              COLORS.map(function (c) {
                return '<button type="button" class="cmsc-editor__color ' + (c.toLowerCase() === n.color.toLowerCase() ? 'is-selected' : '') + '" data-cms-cats-color="' + c + '" style="background:' + c + '" aria-label="Color ' + c + '"></button>';
              }).join('') +
            '</div>' +
          '</div>' +
          '<div class="col-md-6">' +
            '<label class="form-label small text-body-secondary">Sort order</label>' +
            '<input type="number" class="form-control" value="' + n.order + '" data-cms-cats-order>' +
          '</div>' +
          '<div class="col-12">' +
            '<label class="form-label small text-body-secondary">Applies to content types</label>' +
            '<div class="d-flex flex-wrap gap-3">' +
              ['Pages','Posts','Media'].map(function (t) {
                var chk = (n.types || []).indexOf(t) !== -1 ? 'checked' : '';
                return '<div class="form-check"><input class="form-check-input" type="checkbox" id="ct-' + t + '" value="' + t + '" data-cms-cats-type ' + chk + '><label class="form-check-label" for="ct-' + t + '">' + t + '</label></div>';
              }).join('') +
            '</div>' +
          '</div>' +
          '<div class="col-12"><hr></div>' +
          '<div class="col-md-6">' +
            '<label class="form-label small text-body-secondary">SEO title</label>' +
            '<input type="text" class="form-control" value="' + OrchidCMS.escapeHtml(n.name) + ' — Orchid" data-cms-cats-seo-title>' +
          '</div>' +
          '<div class="col-md-6">' +
            '<label class="form-label small text-body-secondary">SEO description</label>' +
            '<input type="text" class="form-control" value="' + OrchidCMS.escapeHtml(n.desc) + '" data-cms-cats-seo-desc>' +
          '</div>' +
        '</div>' +
        '<div class="d-flex gap-2 justify-content-end mt-4">' +
          '<button type="button" class="btn btn-outline-secondary" data-cms-cats-cancel><i class="bi bi-x-lg me-1"></i>Discard</button>' +
          '<button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Category</button>' +
        '</div>' +
      '</form>';

    // Bind editor events
    var form = host.querySelector('[data-cms-cats-editor-form]');
    form.querySelectorAll('[data-cms-cats-color]').forEach(function (b) {
      b.addEventListener('click', function () {
        form.querySelectorAll('[data-cms-cats-color]').forEach(function(x){ x.classList.remove('is-selected'); });
        b.classList.add('is-selected');
      });
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      n.name = form.querySelector('[data-cms-cats-name]').value.trim() || n.name;
      n.desc = form.querySelector('[data-cms-cats-desc]').value;
      n.order = parseInt(form.querySelector('[data-cms-cats-order]').value, 10) || 0;
      var col = form.querySelector('[data-cms-cats-color].is-selected');
      if (col) n.color = col.getAttribute('data-cms-cats-color');
      n.types = Array.from(form.querySelectorAll('[data-cms-cats-type]:checked')).map(function(x){return x.value;});
      OrchidCMS.toast('Category "' + n.name + '" saved', { variant: 'success' });
      renderTree(); renderEditor();
    });
  }

  function parentOptions(excludeId) {
    var opts = [];
    function walkO(nodes, depth) {
      nodes.forEach(function (n) {
        if (n.id === excludeId) return;
        opts.push('<option value="' + n.id + '">' + '  '.repeat(depth) + OrchidCMS.escapeHtml(n.name) + '</option>');
        if (n.children && n.children.length) walkO(n.children, depth + 1);
      });
    }
    walkO(TREE, 0);
    return opts.join('');
  }

  function addRoot(name) {
    TREE.push({
      id: 'c-new-' + Date.now(),
      name: name || 'New Category',
      color: COLORS[TREE.length % COLORS.length],
      desc: '',
      count: 0,
      order: TREE.length + 1,
      types: ['Posts'],
      children: []
    });
  }

  function init() {
    if (!document.querySelector('[data-cms-cats-tree]')) return;

    document.querySelector('[data-cms-cats-search]').addEventListener('input', OrchidCMS.debounce(function (e) {
      searchQ = e.target.value.trim().toLowerCase(); renderTree();
    }, 180));

    document.querySelector('[data-cms-cats-tree]').addEventListener('click', function (e) {
      var t = e.target;
      var sel = t.closest('[data-cms-cat-select]'); if (sel) { selectedId = sel.getAttribute('data-cms-cat-select'); renderTree(); renderEditor(); return; }
      var chev = t.closest('[data-cms-cat-chev]');
      if (chev) {
        var id = chev.getAttribute('data-cms-cat-chev');
        if (expanded.has(id)) expanded.delete(id); else expanded.add(id);
        renderTree(); return;
      }
      var addSub = t.closest('[data-cms-cat-add-sub]');
      if (addSub) {
        var pid = addSub.getAttribute('data-cms-cat-add-sub');
        var parent = find(pid);
        if (parent) {
          parent.children = parent.children || [];
          parent.children.push({
            id:'c-sub-'+Date.now(), name:'New Subcategory', color: COLORS[parent.children.length % COLORS.length],
            desc:'', count:0, order:parent.children.length+1, types:['Posts'], children:[]
          });
          expanded.add(pid);
          OrchidCMS.toast('Subcategory added', { variant: 'success' });
          renderTree();
        }
        return;
      }
      var ed = t.closest('[data-cms-cat-edit]');
      if (ed) { selectedId = ed.getAttribute('data-cms-cat-edit'); renderTree(); renderEditor(); return; }
      var dl = t.closest('[data-cms-cat-del]');
      if (dl) {
        var did = dl.getAttribute('data-cms-cat-del');
        removeNode(did);
        if (selectedId === did) selectedId = TREE[0] ? TREE[0].id : null;
        OrchidCMS.toast('Category deleted', { variant: 'danger' });
        renderTree(); renderEditor();
      }
    });

    // New root category
    document.querySelector('[data-cms-cats-new]').addEventListener('click', function () {
      addRoot('Untitled Category');
      selectedId = TREE[TREE.length-1].id;
      OrchidCMS.toast('New category added', { variant: 'success' });
      renderTree(); renderEditor();
    });

    // Bulk import
    var bulkModal = document.querySelector('[data-cms-cats-import-modal]');
    var bulkForm = document.querySelector('[data-cms-cats-import-form]');
    if (bulkForm) {
      bulkForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var txt = bulkForm.querySelector('[data-cms-cats-import-text]').value.trim();
        if (!txt) return;
        var lines = txt.split(/\r?\n/).filter(function(l){return l.trim().length > 0;});
        lines.forEach(function (line) { addRoot(line.split(',')[0].trim()); });
        OrchidCMS.toast(lines.length + ' categories imported', { variant: 'success' });
        try { bootstrap.Modal.getInstance(bulkModal).hide(); } catch (err) {}
        bulkForm.reset();
        renderTree();
      });
    }

    // Drag-drop
    OrchidCMS.initDragTree(document.querySelector('[data-cms-cats-tree]'), {
      nesting: true,
      onDrop: function (dragId, targetId, pos) {
        if (dragId === targetId) return;
        var dragged = find(dragId); if (!dragged) return;
        // Prevent dropping into own descendant
        if (isDescendant(dragged, targetId)) { OrchidCMS.toast('Cannot move into own subtree', { variant: 'warning' }); return; }
        removeNode(dragId);
        var target = find(targetId); if (!target) { TREE.push(dragged); renderTree(); return; }
        if (pos === 'into') {
          target.children = target.children || [];
          target.children.push(dragged);
          expanded.add(target.id);
        } else {
          var parent = findParent(targetId);
          var arr = parent ? parent.children : TREE;
          var i = arr.findIndex(function(n){ return n.id === targetId; });
          if (pos === 'above') arr.splice(i, 0, dragged);
          else arr.splice(i + 1, 0, dragged);
        }
        OrchidCMS.toast('Category reordered', { variant: 'primary' });
        renderTree();
      }
    });

    renderTree();
    renderEditor();
  }
  function isDescendant(node, id) {
    if (!node.children) return false;
    for (var i = 0; i < node.children.length; i++) {
      if (node.children[i].id === id) return true;
      if (isDescendant(node.children[i], id)) return true;
    }
    return false;
  }

  window.OrchidCMSCategories = { init: init };
})();


/* =====================================================
   Page: cms-comments.html — OrchidCMSComments
   ===================================================== */
(function () {
  'use strict';
  var POSTS_LIST = ['Introducing Orchid 3.0','The state of admin dashboards 2026','How we scaled our sales team','Design tokens: our workflow','From monolith to modules','Why we killed our roadmap','Meet the new charts engine','Announcing SOC 2','Q2 recap','Onboarding growth lever'];
  var SENTIMENTS = ['Positive','Neutral','Negative'];
  var STATUSES = ['Pending','Approved','Trash','Spam'];
  var COMMENTS = [
    { id:'k1', name:'Marcus Bell', email:'marcus@bell.dev', post:'Introducing Orchid 3.0', text:'Been waiting for this release for months. The new nav is chef\'s kiss.', ts:'2026-07-22T10:20:00', status:'Pending', sentiment:'Positive' },
    { id:'k2', name:'Rita Chen', email:'rita@studio.co', post:'The state of admin dashboards 2026', text:'Great data, but I wish you had a breakdown by team size.', ts:'2026-07-22T09:05:00', status:'Pending', sentiment:'Neutral' },
    { id:'k3', name:'Diego Alvarez', email:'diego@lab.io', post:'Design tokens: our workflow', text:'Fantastic writeup — bookmarking this.', ts:'2026-07-21T22:10:00', status:'Approved', sentiment:'Positive' },
    { id:'k4', name:'Ivy Park', email:'ivy@park.dev', post:'Onboarding growth lever', text:'We use a similar approach and it works wonders for our activation KPI.', ts:'2026-07-21T18:40:00', status:'Approved', sentiment:'Positive' },
    { id:'k5', name:'Anonymous', email:'noreply@spam.xx', post:'Introducing Orchid 3.0', text:'Buy cheap watches at buy-fake-lux.example, we are the best!!!', ts:'2026-07-21T15:03:00', status:'Spam', sentiment:'Negative' },
    { id:'k6', name:'Priya Patel', email:'priya@orchid.io', post:'Q2 recap', text:'So proud of what the team shipped this quarter.', ts:'2026-07-21T12:00:00', status:'Approved', sentiment:'Positive' },
    { id:'k7', name:'Kwame Osei', email:'kwame@osei.gh', post:'From monolith to modules', text:'Curious how you handled shared auth during the migration?', ts:'2026-07-20T20:15:00', status:'Pending', sentiment:'Neutral' },
    { id:'k8', name:'Lena Novak', email:'lena@novak.eu', post:'Meet the new charts engine', text:'Any plans to support Sankey diagrams natively?', ts:'2026-07-20T14:40:00', status:'Pending', sentiment:'Neutral' },
    { id:'k9', name:'Ryan Green', email:'ryan@orchid.io', post:'Why we killed our roadmap', text:'This resonates so much — great post.', ts:'2026-07-20T09:22:00', status:'Approved', sentiment:'Positive' },
    { id:'k10', name:'Angry Reader', email:'mad@web.example', post:'Announcing SOC 2', text:'Way overdue, honestly. Should have been done years ago.', ts:'2026-07-19T23:45:00', status:'Pending', sentiment:'Negative' },
    { id:'k11', name:'Sam Ortiz', email:'sam@ortiz.mx', post:'The state of admin dashboards 2026', text:'Really valuable benchmarks, thanks for sharing openly.', ts:'2026-07-19T18:30:00', status:'Approved', sentiment:'Positive' },
    { id:'k12', name:'Bot #42', email:'bot@bot.example', post:'How we scaled our sales team', text:'CLICK HERE FOR FREE CRYPTO GAINS 🚀🚀🚀', ts:'2026-07-19T16:11:00', status:'Spam', sentiment:'Negative' },
    { id:'k13', name:'Marta Rossi', email:'marta@rossi.it', post:'Design tokens: our workflow', text:'We ended up moving to a similar Figma variables setup last month.', ts:'2026-07-19T12:04:00', status:'Approved', sentiment:'Positive' },
    { id:'k14', name:'Anon User', email:'anon@user.example', post:'Introducing Orchid 3.0', text:'Was fine before, why did you change everything?', ts:'2026-07-19T08:22:00', status:'Trash', sentiment:'Negative' },
    { id:'k15', name:'Julien Blanc', email:'julien@blanc.fr', post:'Meet the new charts engine', text:'The animations feel much snappier now. Nice job!', ts:'2026-07-18T21:10:00', status:'Approved', sentiment:'Positive' },
    { id:'k16', name:'Nora West', email:'nora@west.dev', post:'From monolith to modules', text:'What did you use for the service mesh in the end?', ts:'2026-07-18T17:44:00', status:'Pending', sentiment:'Neutral' },
    { id:'k17', name:'Tomás Braga', email:'tomas@braga.pt', post:'Q2 recap', text:'The billing improvements have been a huge win for our team.', ts:'2026-07-18T14:20:00', status:'Approved', sentiment:'Positive' },
    { id:'k18', name:'Zoe Fisher', email:'zoe@fisher.dev', post:'Why we killed our roadmap', text:'Not fully convinced, but interesting perspective.', ts:'2026-07-18T09:05:00', status:'Pending', sentiment:'Neutral' },
    { id:'k19', name:'Milo Grant', email:'milo@grant.uk', post:'Announcing SOC 2', text:'Big deal for us as an enterprise customer, thank you.', ts:'2026-07-17T22:15:00', status:'Approved', sentiment:'Positive' },
    { id:'k20', name:'Aisha Rahman', email:'aisha@rahman.co', post:'Design tokens: our workflow', text:'This is exactly the framework we needed to adopt.', ts:'2026-07-17T19:00:00', status:'Approved', sentiment:'Positive' }
  ];

  var state = { q:'', posts: [], author:'', sentiment:'', tab:'All' };
  var sel;

  function filtered() {
    var q = state.q.trim().toLowerCase();
    return COMMENTS.filter(function (c) {
      if (state.tab !== 'All' && c.status !== state.tab) return false;
      if (q && c.text.toLowerCase().indexOf(q) === -1 && c.name.toLowerCase().indexOf(q) === -1) return false;
      if (state.posts.length && state.posts.indexOf(c.post) === -1) return false;
      if (state.author && c.name !== state.author) return false;
      if (state.sentiment && c.sentiment !== state.sentiment) return false;
      return true;
    }).sort(function (a, b) { return b.ts.localeCompare(a.ts); });
  }

  function render() {
    var host = document.querySelector('[data-cms-comments-list]');
    if (!host) return;
    var items = filtered();
    if (items.length === 0) {
      host.innerHTML = '<div class="cms-empty"><i class="bi bi-chat-square"></i>No comments match.</div>';
    } else {
      host.innerHTML = items.map(function (c) {
        return '<article class="cmscm-item ' + (sel.has(c.id) ? 'is-selected' : '') + '" data-cms-cm-item="' + c.id + '">' +
          '<div class="form-check"><input class="form-check-input" type="checkbox" data-cms-cm-check="' + c.id + '" ' + (sel.has(c.id) ? 'checked' : '') + '></div>' +
          OrchidCMS.renderAvatar(c.name) +
          '<div class="cmscm-item__body">' +
            '<div class="cmscm-item__head">' +
              '<span class="cmscm-item__name">' + OrchidCMS.escapeHtml(c.name) + '</span>' +
              '<span class="cmscm-item__email">&lt;' + OrchidCMS.escapeHtml(c.email) + '&gt;</span>' +
              '<span class="cms-status cms-status--' + c.status.toLowerCase() + '">' + c.status + '</span>' +
              '<span class="cmscm-sentiment cmscm-sentiment--' + c.sentiment.toLowerCase() + '">' +
                '<i class="bi bi-' + (c.sentiment === 'Positive' ? 'emoji-smile' : c.sentiment === 'Negative' ? 'emoji-frown' : 'emoji-neutral') + '"></i>' + c.sentiment +
              '</span>' +
            '</div>' +
            '<p class="cmscm-item__comment">' + OrchidCMS.escapeHtml(c.text) + '</p>' +
            '<div class="cmscm-item__meta">' +
              '<span><i class="bi bi-file-earmark-richtext me-1"></i>on <a href="#">' + OrchidCMS.escapeHtml(c.post) + '</a></span>' +
              '<span><i class="bi bi-clock me-1"></i>' + OrchidCMS.relativeTime(c.ts) + '</span>' +
            '</div>' +
            '<div class="cmscm-item__actions">' +
              '<button class="btn btn-sm btn-success" data-cms-cm-approve="' + c.id + '"><i class="bi bi-check-lg me-1"></i>Approve</button>' +
              '<button class="btn btn-sm btn-outline-secondary" data-cms-cm-reply="' + c.id + '"><i class="bi bi-reply me-1"></i>Reply</button>' +
              '<button class="btn btn-sm btn-outline-secondary" data-cms-cm-reject="' + c.id + '"><i class="bi bi-x-lg me-1"></i>Reject</button>' +
              '<button class="btn btn-sm btn-outline-warning" data-cms-cm-spam="' + c.id + '"><i class="bi bi-flag me-1"></i>Mark spam</button>' +
              '<button class="btn btn-sm btn-outline-danger" data-cms-cm-delete="' + c.id + '"><i class="bi bi-trash me-1"></i>Delete</button>' +
            '</div>' +
          '</div>' +
        '</article>';
      }).join('');
    }
    // KPIs & tab counts
    var weekAgo = new Date(Date.now() - 7 * 86400000).toISOString();
    setText('[data-cms-cm-kpi-total]', COMMENTS.length);
    setText('[data-cms-cm-kpi-pending]', COMMENTS.filter(function(c){return c.status==='Pending';}).length);
    setText('[data-cms-cm-kpi-spam]', COMMENTS.filter(function(c){return c.status==='Spam';}).length);
    setText('[data-cms-cm-kpi-approved-week]', COMMENTS.filter(function(c){return c.status==='Approved' && c.ts >= weekAgo;}).length);
    setText('[data-cms-cm-count]', items.length);
    updateTabCounts();
    if (window._cmsCmBulkBar) window._cmsCmBulkBar.refresh();
  }
  function setText(sel, val) { var n = document.querySelector(sel); if (n) n.textContent = val; }

  function updateTabCounts() {
    OrchidCMS.qsa('[data-cms-cm-tab]').forEach(function (btn) {
      var t = btn.getAttribute('data-cms-cm-tab');
      var c = t === 'All' ? COMMENTS.length : COMMENTS.filter(function(x){return x.status===t;}).length;
      var badge = btn.querySelector('.cms-tabs__badge');
      if (badge) badge.textContent = c;
    });
  }

  function init() {
    if (!document.querySelector('[data-cms-comments-list]')) return;
    sel = OrchidCMS.selection();

    // Filter tabs
    OrchidCMS.qsa('[data-cms-cm-tab]').forEach(function (b) {
      b.addEventListener('click', function () {
        state.tab = b.getAttribute('data-cms-cm-tab');
        OrchidCMS.qsa('[data-cms-cm-tab]').forEach(function(x){ x.classList.remove('is-active'); });
        b.classList.add('is-active');
        render();
      });
    });

    // Search + post multi-select + author + sentiment
    document.querySelector('[data-cms-cm-q]').addEventListener('input', OrchidCMS.debounce(function (e) { state.q = e.target.value; render(); }, 180));

    // Populate post multi-select
    var postSel = document.querySelector('[data-cms-cm-posts]');
    if (postSel) {
      POSTS_LIST.forEach(function (p) { var o=document.createElement('option'); o.value=p; o.textContent=p; postSel.appendChild(o); });
      postSel.addEventListener('change', function () {
        state.posts = Array.from(postSel.selectedOptions).map(function(o){ return o.value; });
        render();
      });
    }

    var authSel = document.querySelector('[data-cms-cm-author]');
    var authors = Array.from(new Set(COMMENTS.map(function(c){return c.name;})));
    if (authSel) {
      authors.forEach(function (a) { var o=document.createElement('option'); o.value=a; o.textContent=a; authSel.appendChild(o); });
      authSel.addEventListener('change', function (e) { state.author = e.target.value; render(); });
    }
    var sentSel = document.querySelector('[data-cms-cm-sentiment]');
    if (sentSel) {
      SENTIMENTS.forEach(function (s) { var o=document.createElement('option'); o.value=s; o.textContent=s; sentSel.appendChild(o); });
      sentSel.addEventListener('change', function (e) { state.sentiment = e.target.value; render(); });
    }

    document.querySelector('[data-cms-comments-list]').addEventListener('click', function (e) {
      var t = e.target;
      var ch = t.closest('[data-cms-cm-check]'); if (ch) { sel.toggle(ch.getAttribute('data-cms-cm-check')); render(); return; }
      var ap = t.closest('[data-cms-cm-approve]'); if (ap) { changeStatus(ap.getAttribute('data-cms-cm-approve'), 'Approved'); return; }
      var rj = t.closest('[data-cms-cm-reject]'); if (rj) { changeStatus(rj.getAttribute('data-cms-cm-reject'), 'Trash'); return; }
      var sp = t.closest('[data-cms-cm-spam]'); if (sp) { changeStatus(sp.getAttribute('data-cms-cm-spam'), 'Spam'); return; }
      var dl = t.closest('[data-cms-cm-delete]'); if (dl) { removeOne(dl.getAttribute('data-cms-cm-delete')); return; }
      var rp = t.closest('[data-cms-cm-reply]'); if (rp) { openReply(rp.getAttribute('data-cms-cm-reply')); return; }
    });

    // Bulk
    window._cmsCmBulkBar = OrchidCMS.initBulkBar(document.querySelector('[data-cms-cm-bulkbar]'), sel);
    document.querySelector('[data-cms-cm-bulkbar]').addEventListener('click', function (e) {
      var btn = e.target.closest('[data-cms-bulk]'); if (!btn) return;
      var act = btn.getAttribute('data-cms-bulk'); var ids = sel.values();
      if (act === 'approve') ids.forEach(function(id){ var c=find(id); if(c) c.status='Approved'; });
      else if (act === 'reject') ids.forEach(function(id){ var c=find(id); if(c) c.status='Trash'; });
      else if (act === 'spam') ids.forEach(function(id){ var c=find(id); if(c) c.status='Spam'; });
      else if (act === 'delete') ids.forEach(function(id){ var i=COMMENTS.findIndex(function(x){return x.id===id;}); if(i>=0) COMMENTS.splice(i,1); });
      OrchidCMS.toast(ids.length + ' comment(s) ' + act + (act === 'delete' ? 'd' : (act.endsWith('e') ? 'd' : 'ed')), { variant: 'success' });
      sel.clear(); render();
    });

    // Reply drawer send
    var drawerForm = document.querySelector('[data-cms-cm-reply-form]');
    if (drawerForm) {
      drawerForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = drawerForm.querySelector('[data-cms-cm-reply-text]').value.trim();
        if (!text) return;
        OrchidCMS.toast('Reply sent', { variant: 'success' });
        drawerForm.reset();
        try { bootstrap.Offcanvas.getInstance(document.querySelector('[data-cms-cm-reply-drawer]')).hide(); } catch (err) {}
      });
    }

    render();
  }
  function find(id) { return COMMENTS.find(function (c) { return c.id === id; }); }
  function changeStatus(id, status) {
    var c = find(id); if (!c) return;
    c.status = status;
    OrchidCMS.toast('Comment marked ' + status.toLowerCase(), { variant: 'primary' });
    render();
  }
  function removeOne(id) {
    var i = COMMENTS.findIndex(function (c) { return c.id === id; });
    if (i >= 0) { COMMENTS.splice(i, 1); OrchidCMS.toast('Comment deleted', { variant: 'danger' }); render(); }
  }
  function openReply(id) {
    var c = find(id); if (!c) return;
    var drawer = document.querySelector('[data-cms-cm-reply-drawer]');
    drawer.querySelector('[data-cms-cm-reply-quoted]').innerHTML =
      '<strong>' + OrchidCMS.escapeHtml(c.name) + '</strong> on <em>' + OrchidCMS.escapeHtml(c.post) + '</em><br>' + OrchidCMS.escapeHtml(c.text);
    try { new bootstrap.Offcanvas(drawer).show(); } catch (err) {}
  }

  window.OrchidCMSComments = { init: init };
})();


/* =====================================================
   Page: cms-menus.html — OrchidCMSMenus
   ===================================================== */
(function () {
  'use strict';
  var ICONS = ['house-door','info-circle','envelope','file-earmark-text','tag','person','currency-dollar','question-circle','box','star','bookmark','globe','shop','bag','life-preserver','book','journal-text','images','chat-dots','gear','folder','graph-up','lightning','shield-check'];
  var LOCATIONS = [
    { id:'header', name:'Header Menu', icon:'window', active:true },
    { id:'footer', name:'Footer Menu', icon:'window-dock', active:true },
    { id:'sidebar', name:'Sidebar Menu', icon:'layout-sidebar', active:true },
    { id:'mobile', name:'Mobile Menu', icon:'phone', active:false },
    { id:'custom', name:'Custom Location', icon:'grid-3x3-gap', active:false }
  ];
  var MENUS = {
    header: [
      { id:'h1', label:'Product', url:'/product', icon:'box', newTab:false, children:[
        { id:'h1-1', label:'Features', url:'/features', icon:'star', newTab:false, children:[] },
        { id:'h1-2', label:'Integrations', url:'/integrations', icon:'plug', newTab:false, children:[] },
        { id:'h1-3', label:'Templates', url:'/templates', icon:'grid', newTab:false, children:[] },
        { id:'h1-4', label:'Changelog', url:'/changelog', icon:'clock-history', newTab:false, children:[] }
      ] },
      { id:'h2', label:'Solutions', url:'/solutions', icon:'lightning', newTab:false, children:[
        { id:'h2-1', label:'For Startups', url:'/solutions/startups', icon:'rocket', newTab:false, children:[] },
        { id:'h2-2', label:'For Enterprise', url:'/solutions/enterprise', icon:'building', newTab:false, children:[] },
        { id:'h2-3', label:'For Agencies', url:'/solutions/agencies', icon:'people', newTab:false, children:[] }
      ] },
      { id:'h3', label:'Pricing', url:'/pricing', icon:'currency-dollar', newTab:false, children:[] },
      { id:'h4', label:'Resources', url:'/resources', icon:'book', newTab:false, children:[
        { id:'h4-1', label:'Documentation', url:'/docs', icon:'journal-text', newTab:false, children:[] },
        { id:'h4-2', label:'Blog', url:'/blog', icon:'chat-dots', newTab:false, children:[] },
        { id:'h4-3', label:'Help Center', url:'/help', icon:'life-preserver', newTab:false, children:[] }
      ] },
      { id:'h5', label:'Company', url:'/company', icon:'building', newTab:false, children:[] },
      { id:'h6', label:'Login', url:'/login', icon:'box-arrow-in-right', newTab:false, children:[] }
    ],
    footer: [
      { id:'f1', label:'About Us', url:'/about', icon:'info-circle', newTab:false, children:[] },
      { id:'f2', label:'Careers', url:'/careers', icon:'briefcase', newTab:false, children:[] },
      { id:'f3', label:'Privacy Policy', url:'/privacy', icon:'shield-check', newTab:false, children:[] },
      { id:'f4', label:'Terms', url:'/terms', icon:'file-earmark-text', newTab:false, children:[] }
    ],
    sidebar: [
      { id:'s1', label:'Dashboard', url:'/dashboard', icon:'house-door', newTab:false, children:[] },
      { id:'s2', label:'Reports', url:'/reports', icon:'graph-up', newTab:false, children:[] },
      { id:'s3', label:'Settings', url:'/settings', icon:'gear', newTab:false, children:[] }
    ],
    mobile: [
      { id:'m1', label:'Home', url:'/', icon:'house-door', newTab:false, children:[] },
      { id:'m2', label:'Menu', url:'/menu', icon:'list', newTab:false, children:[] }
    ],
    custom: []
  };

  var currentLoc = 'header';

  function find(id, arr) {
    arr = arr || MENUS[currentLoc];
    for (var i = 0; i < arr.length; i++) {
      if (arr[i].id === id) return arr[i];
      var c = find(id, arr[i].children || []);
      if (c) return c;
    }
    return null;
  }
  function findParent(id, arr, parent) {
    arr = arr || MENUS[currentLoc];
    for (var i = 0; i < arr.length; i++) {
      if (arr[i].id === id) return parent;
      var c = findParent(id, arr[i].children || [], arr[i]);
      if (c !== undefined) return c;
    }
    return undefined;
  }
  function removeItem(id) {
    var p = findParent(id);
    var arr = p ? p.children : MENUS[currentLoc];
    var i = arr.findIndex(function (n) { return n.id === id; });
    if (i >= 0) arr.splice(i, 1);
  }
  function countAll(arr) {
    var n = 0;
    (arr || MENUS[currentLoc]).forEach(function (x) { n++; n += countAll(x.children || []); });
    return n;
  }

  function renderLocations() {
    var host = document.querySelector('[data-cms-menus-locations]');
    if (!host) return;
    host.innerHTML = LOCATIONS.map(function (loc) {
      var isActive = loc.id === currentLoc;
      var count = countAll(MENUS[loc.id] || []);
      return '<div class="cmsmenu-loc-card ' + (isActive ? 'is-active' : '') + '" data-cms-menus-loc="' + loc.id + '">' +
        '<span class="cmsmenu-loc-card__icon"><i class="bi bi-' + loc.icon + '"></i></span>' +
        '<div class="cmsmenu-loc-card__body">' +
          '<p class="cmsmenu-loc-card__name">' + OrchidCMS.escapeHtml(loc.name) + '</p>' +
          '<span class="cmsmenu-loc-card__meta">' + count + ' items</span>' +
        '</div>' +
        '<div class="form-check form-switch">' +
          '<input class="form-check-input" type="checkbox" role="switch" ' + (loc.active ? 'checked' : '') + ' data-cms-menus-loc-active="' + loc.id + '" aria-label="Active">' +
        '</div>' +
      '</div>';
    }).join('');
  }

  function itemHtml(n, depth) {
    depth = depth || 0;
    var canNest = depth < 1; // 2-level deep supported
    var children = (n.children || []).map(function (c) { return itemHtml(c, depth + 1); }).join('');
    return '<li>' +
      '<div class="cmsmenu-item" data-cms-drag="' + n.id + '" data-cms-menu-item="' + n.id + '" draggable="true">' +
        '<div class="cmsmenu-item__head">' +
          '<span class="cmsmenu-item__handle" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></span>' +
          '<i class="bi bi-' + n.icon + ' text-primary"></i>' +
          '<span class="cmsmenu-item__label">' + OrchidCMS.escapeHtml(n.label) + '</span>' +
          '<span class="cmsmenu-item__url d-none d-md-inline">' + OrchidCMS.escapeHtml(n.url) + '</span>' +
          '<div class="cmsmenu-item__actions ms-auto">' +
            (canNest ? '<button class="btn btn-icon btn-sm" data-cms-menu-add-sub="' + n.id + '" title="Add sub-item"><i class="bi bi-plus-lg"></i></button>' : '') +
            '<button class="btn btn-icon btn-sm text-danger" data-cms-menu-del="' + n.id + '" title="Remove"><i class="bi bi-trash"></i></button>' +
            '<button class="cmsmenu-item__toggle" data-cms-menu-toggle="' + n.id + '" title="Toggle"><i class="bi bi-chevron-down"></i></button>' +
          '</div>' +
        '</div>' +
        '<div class="cmsmenu-item__body">' +
          '<div class="row g-2">' +
            '<div class="col-md-6"><label class="form-label small">Label</label><input class="form-control form-control-sm" value="' + OrchidCMS.escapeHtml(n.label) + '" data-cms-menu-field="label" data-cms-menu-id="' + n.id + '"></div>' +
            '<div class="col-md-6"><label class="form-label small">URL</label><input class="form-control form-control-sm" value="' + OrchidCMS.escapeHtml(n.url) + '" data-cms-menu-field="url" data-cms-menu-id="' + n.id + '"></div>' +
            '<div class="col-md-6"><label class="form-label small">Icon</label>' +
              '<div class="cmsmenu-icon-picker" data-cms-menu-iconpicker="' + n.id + '">' +
                ICONS.map(function (ic) { return '<button type="button" class="' + (ic === n.icon ? 'is-selected' : '') + '" data-cms-menu-icon="' + ic + '" title="' + ic + '"><i class="bi bi-' + ic + '"></i></button>'; }).join('') +
              '</div>' +
            '</div>' +
            '<div class="col-md-6 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="nt-' + n.id + '" ' + (n.newTab ? 'checked' : '') + ' data-cms-menu-newtab="' + n.id + '"><label class="form-check-label" for="nt-' + n.id + '">Open in new tab</label></div></div>' +
          '</div>' +
        '</div>' +
      '</div>' +
      (children ? '<ul class="cmsmenu-builder__children">' + children + '</ul>' : '') +
    '</li>';
  }

  function renderBuilder() {
    var host = document.querySelector('[data-cms-menus-builder]');
    if (!host) return;
    var items = MENUS[currentLoc];
    host.innerHTML = items.length === 0
      ? '<div class="cms-empty"><i class="bi bi-list"></i>This menu is empty. Click "Add Item" to get started.</div>'
      : items.map(function (n) { return itemHtml(n, 0); }).join('');
    var nameInp = document.querySelector('[data-cms-menus-name]');
    if (nameInp) {
      var loc = LOCATIONS.find(function(l){return l.id===currentLoc;});
      nameInp.value = loc ? loc.name : '';
    }
    renderLocations();
  }

  function init() {
    if (!document.querySelector('[data-cms-menus-builder]')) return;

    renderLocations();
    renderBuilder();

    // Location cards
    document.querySelector('[data-cms-menus-locations]').addEventListener('click', function (e) {
      if (e.target.closest('[data-cms-menus-loc-active]')) return;
      var card = e.target.closest('[data-cms-menus-loc]');
      if (!card) return;
      currentLoc = card.getAttribute('data-cms-menus-loc');
      renderBuilder();
    });
    document.querySelector('[data-cms-menus-locations]').addEventListener('change', function (e) {
      var chk = e.target.closest('[data-cms-menus-loc-active]');
      if (!chk) return;
      var loc = LOCATIONS.find(function (l) { return l.id === chk.getAttribute('data-cms-menus-loc-active'); });
      if (loc) { loc.active = chk.checked; OrchidCMS.toast(loc.name + ' ' + (loc.active ? 'activated' : 'deactivated'), { variant: chk.checked ? 'success' : 'warning' }); }
    });

    // Builder click delegation
    var builder = document.querySelector('[data-cms-menus-builder]');
    builder.addEventListener('click', function (e) {
      var t = e.target;
      var tog = t.closest('[data-cms-menu-toggle]');
      if (tog) {
        var id = tog.getAttribute('data-cms-menu-toggle');
        var el = builder.querySelector('[data-cms-menu-item="' + id + '"]');
        if (el) el.classList.toggle('is-open');
        return;
      }
      var addSub = t.closest('[data-cms-menu-add-sub]');
      if (addSub) {
        var pid = addSub.getAttribute('data-cms-menu-add-sub');
        var parent = find(pid);
        if (parent) {
          parent.children = parent.children || [];
          parent.children.push({ id:'mi-'+Date.now(), label:'New Item', url:'#', icon:'bookmark', newTab:false, children:[] });
          renderBuilder();
          OrchidCMS.toast('Sub-item added', { variant: 'success' });
        }
        return;
      }
      var del = t.closest('[data-cms-menu-del]');
      if (del) { removeItem(del.getAttribute('data-cms-menu-del')); OrchidCMS.toast('Item removed', { variant: 'danger' }); renderBuilder(); return; }
      var icon = t.closest('[data-cms-menu-icon]');
      if (icon) {
        var picker = icon.closest('[data-cms-menu-iconpicker]');
        var itemId = picker.getAttribute('data-cms-menu-iconpicker');
        var ic = icon.getAttribute('data-cms-menu-icon');
        var it = find(itemId); if (it) { it.icon = ic; renderBuilder(); }
        return;
      }
    });
    builder.addEventListener('input', function (e) {
      var f = e.target.closest('[data-cms-menu-field]');
      if (f) {
        var itemId = f.getAttribute('data-cms-menu-id');
        var field = f.getAttribute('data-cms-menu-field');
        var it = find(itemId); if (it) it[field] = f.value;
      }
    });
    builder.addEventListener('change', function (e) {
      var nt = e.target.closest('[data-cms-menu-newtab]');
      if (nt) { var it = find(nt.getAttribute('data-cms-menu-newtab')); if (it) it.newTab = nt.checked; }
    });

    // Drag-drop nesting
    OrchidCMS.initDragTree(builder, {
      nesting: true,
      onDrop: function (dragId, targetId, pos) {
        if (dragId === targetId) return;
        var dragged = find(dragId); if (!dragged) return;
        // Prevent nesting into own subtree
        if (isDescendant(dragged, targetId)) { OrchidCMS.toast('Cannot nest into own subtree', { variant: 'warning' }); return; }
        removeItem(dragId);
        var target = find(targetId); if (!target) { MENUS[currentLoc].push(dragged); renderBuilder(); return; }
        if (pos === 'into') {
          // Only if target has depth 0
          var depth = getDepth(target); if (depth >= 1) { OrchidCMS.toast('Menus support 2 levels of nesting', { variant: 'warning' }); MENUS[currentLoc].push(dragged); renderBuilder(); return; }
          target.children = target.children || []; target.children.push(dragged);
        } else {
          var parent = findParent(targetId);
          var arr = parent ? parent.children : MENUS[currentLoc];
          var i = arr.findIndex(function(n){ return n.id === targetId; });
          if (pos === 'above') arr.splice(i, 0, dragged);
          else arr.splice(i + 1, 0, dragged);
        }
        OrchidCMS.toast('Menu reordered', { variant: 'primary' });
        renderBuilder();
      }
    });

    // Add-item modal
    var addForm = document.querySelector('[data-cms-menus-add-form]');
    if (addForm) {
      addForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var type = addForm.querySelector('[data-cms-menus-add-type]').value;
        var label = addForm.querySelector('[data-cms-menus-add-label]').value.trim() || 'New Item';
        var url = addForm.querySelector('[data-cms-menus-add-url]').value.trim() || '#';
        if (type === 'divider') { label = '— Divider —'; url = '#'; }
        MENUS[currentLoc].push({ id:'mi-'+Date.now(), label:label, url:url, icon: type === 'divider' ? 'dash' : 'bookmark', newTab:false, children:[] });
        try { bootstrap.Modal.getInstance(document.querySelector('[data-cms-menus-add-modal]')).hide(); } catch (err) {}
        addForm.reset();
        renderBuilder();
        OrchidCMS.toast('Item added to menu', { variant: 'success' });
      });
    }

    // Save + preview
    var saveBtn = document.querySelector('[data-cms-menus-save]');
    if (saveBtn) saveBtn.addEventListener('click', function () { OrchidCMS.toast('Menu saved successfully', { variant: 'success' }); });
    var prevBtn = document.querySelector('[data-cms-menus-preview]');
    if (prevBtn) prevBtn.addEventListener('click', function () { OrchidCMS.toast('Opening menu preview…', { variant: 'primary' }); });
  }
  function getDepth(node, arr, depth) {
    arr = arr || MENUS[currentLoc]; depth = depth || 0;
    for (var i = 0; i < arr.length; i++) {
      if (arr[i].id === node.id) return depth;
      var d = getDepth(node, arr[i].children || [], depth + 1);
      if (d !== -1) return d;
    }
    return -1;
  }
  function isDescendant(node, id) {
    if (!node.children) return false;
    for (var i = 0; i < node.children.length; i++) {
      if (node.children[i].id === id) return true;
      if (isDescendant(node.children[i], id)) return true;
    }
    return false;
  }

  window.OrchidCMSMenus = { init: init };
})();
