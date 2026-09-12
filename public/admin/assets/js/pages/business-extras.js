/* =====================================================
   Orchid — Business Extras shared toolkit
   Exposes: window.BizExtras = { toast, fmtMoney, fmtDate, renderStars,
                                 renderAvatar, customers, products,
                                 warehouses, dnd }
   ===================================================== */
(function () {
  'use strict';

  /* ---------- Toast host ---------- */
  var toastHost;
  function ensureHost() {
    if (toastHost) return toastHost;
    toastHost = document.querySelector('.biz-toast-host');
    if (!toastHost) {
      toastHost = document.createElement('div');
      toastHost.className = 'biz-toast-host';
      document.body.appendChild(toastHost);
    }
    return toastHost;
  }
  function orchidToast(msg, kind) {
    kind = kind || 'info';
    var iconMap = {
      success: 'bi-check-circle-fill',
      info: 'bi-info-circle-fill',
      warning: 'bi-exclamation-triangle-fill',
      danger: 'bi-x-circle-fill'
    };
    var host = ensureHost();
    var el = document.createElement('div');
    el.className = 'biz-toast biz-toast--' + kind;
    el.innerHTML = '<i class="bi ' + (iconMap[kind] || iconMap.info) + '"></i><div>' + msg + '</div>';
    host.appendChild(el);
    setTimeout(function () {
      el.style.transition = 'opacity 200ms ease, transform 200ms ease';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-6px)';
      setTimeout(function () { el.remove(); }, 220);
    }, 3200);
  }

  /* ---------- Formatters ---------- */
  function fmtMoney(v, currency) {
    currency = currency || 'USD';
    if (v == null || isNaN(v)) return '—';
    try {
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: currency, maximumFractionDigits: v >= 1000 ? 0 : 2 }).format(v);
    } catch (e) { return '$' + v; }
  }
  function fmtDate(v, opts) {
    if (!v) return '—';
    var d = (v instanceof Date) ? v : new Date(v);
    if (isNaN(d.getTime())) return '—';
    var defaults = { month: 'short', day: '2-digit', year: 'numeric' };
    return d.toLocaleDateString('en-US', Object.assign(defaults, opts || {}));
  }
  function fmtRelative(v) {
    if (!v) return '—';
    var d = (v instanceof Date) ? v : new Date(v);
    var diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return fmtDate(v);
  }

  /* ---------- Renderers ---------- */
  function renderStars(rating, max) {
    max = max || 5;
    var out = '<span class="biz-stars" aria-label="' + rating + ' out of ' + max + '">';
    for (var i = 1; i <= max; i++) {
      out += '<i class="bi bi-star-fill' + (i <= rating ? '' : ' off') + '"></i>';
    }
    out += '</span>';
    return out;
  }
  function renderAvatar(name, size, imgUrl) {
    size = size || 'md';
    var initials = (name || '?')
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map(function (w) { return w.charAt(0).toUpperCase(); })
      .join('') || '?';
    var palette = ['#4f46e5', '#0ea5e9', '#f97316', '#22c55e', '#e11d48', '#8b5cf6', '#0d9488', '#eab308'];
    var idx = 0;
    for (var i = 0; i < (name || '').length; i++) { idx = (idx + name.charCodeAt(i)) % palette.length; }
    var bg = palette[idx];
    if (imgUrl) {
      return '<span class="biz-avatar-circle ' + size + '"><img src="' + imgUrl + '" alt="' + name + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%"></span>';
    }
    return '<span class="biz-avatar-circle ' + size + '" data-bg="' + bg + '">' + initials + '</span>';
  }
  function bindAvatarColors(root) {
    (root || document).querySelectorAll('.biz-avatar-circle[data-bg]').forEach(function (el) {
      if (!el.dataset.painted) {
        el.style.backgroundColor = el.getAttribute('data-bg');
        el.dataset.painted = '1';
      }
    });
  }

  /* ---------- Datasets ---------- */
  var owners = ['Alex Kim', 'Sarah Miller', 'James Doe', 'Ava Lee', 'Ryan Green', 'Emma Watson'];
  var industries = ['Retail', 'SaaS', 'Manufacturing', 'Healthcare', 'Fintech', 'Media', 'Education', 'Logistics'];

  var customers = [
    { id: 'CUS-1001', name: 'Emma Watson',    company: 'Nova Labs',        email: 'emma@nova.io',        phone: '+44 20 7946 0111', country: 'UK',  city: 'London',      tier: 'VIP',        health: 92, ltv: 128450, orders: 42, lastOrder: '2026-07-18', owner: 'Alex Kim',     status: 'Active',   industry: 'SaaS',         joined: '2022-03-11', nps: 9 },
    { id: 'CUS-1002', name: 'James Doe',      company: 'Acme Corp',        email: 'james@acme.com',      phone: '+1 415 555 0122',  country: 'US',  city: 'San Francisco', tier: 'Enterprise', health: 88, ltv: 245100, orders: 96, lastOrder: '2026-07-19', owner: 'Sarah Miller', status: 'Active',   industry: 'Manufacturing', joined: '2021-08-04', nps: 10 },
    { id: 'CUS-1003', name: 'Sarah Miller',   company: 'ZenTech',          email: 'sarah@zen.co',        phone: '+61 2 8123 4567',  country: 'AU',  city: 'Sydney',      tier: 'VIP',        health: 76, ltv:  74200, orders: 28, lastOrder: '2026-07-10', owner: 'Ava Lee',      status: 'Active',   industry: 'SaaS',         joined: '2023-01-19', nps: 8 },
    { id: 'CUS-1004', name: 'Ryan Green',     company: 'Peak IO',          email: 'ryan@peak.io',        phone: '+1 416 555 0134',  country: 'CA',  city: 'Toronto',     tier: 'Regular',    health: 42, ltv:  12800, orders:  6, lastOrder: '2026-05-04', owner: 'James Doe',    status: 'At Risk',  industry: 'Fintech',      joined: '2024-06-12', nps: 5 },
    { id: 'CUS-1005', name: 'Ava Lee',        company: 'Bold Studio',      email: 'ava@bold.co',         phone: '+49 30 1234 5678', country: 'DE',  city: 'Berlin',      tier: 'Regular',    health: 68, ltv:  32400, orders: 18, lastOrder: '2026-07-01', owner: 'Emma Watson',  status: 'Active',   industry: 'Media',        joined: '2023-09-27', nps: 7 },
    { id: 'CUS-1006', name: 'Michael Chen',   company: 'Vertex Health',    email: 'mchen@vertex.health', phone: '+1 617 555 0188',  country: 'US',  city: 'Boston',      tier: 'Enterprise', health: 95, ltv: 312800, orders: 128, lastOrder: '2026-07-22', owner: 'Alex Kim',    status: 'Active',   industry: 'Healthcare',   joined: '2020-05-14', nps: 10 },
    { id: 'CUS-1007', name: 'Olivia Brown',   company: 'Aurora Retail',    email: 'olivia@aurora.shop',  phone: '+1 212 555 0121',  country: 'US',  city: 'New York',    tier: 'VIP',        health: 84, ltv:  98200, orders: 54, lastOrder: '2026-07-15', owner: 'Sarah Miller', status: 'Active',   industry: 'Retail',       joined: '2022-11-30', nps: 9 },
    { id: 'CUS-1008', name: 'Noah Wilson',    company: 'Streamline Logistics', email: 'noah@streamline.co', phone: '+1 312 555 0199', country: 'US', city: 'Chicago',   tier: 'Regular',    health: 71, ltv:  41200, orders: 22, lastOrder: '2026-07-08', owner: 'Ryan Green',   status: 'Active',   industry: 'Logistics',    joined: '2023-04-18', nps: 8 },
    { id: 'CUS-1009', name: 'Isabella Martinez', company: 'Casa Design',   email: 'isabella@casa.mx',    phone: '+52 55 5555 0177', country: 'MX',  city: 'Mexico City', tier: 'New',        health: 55, ltv:   4800, orders:  3, lastOrder: '2026-07-20', owner: 'Ava Lee',      status: 'Active',   industry: 'Retail',       joined: '2026-06-11', nps: 6 },
    { id: 'CUS-1010', name: 'Lucas Anderson', company: 'FinPath',          email: 'lucas@finpath.io',    phone: '+1 646 555 0100',  country: 'US',  city: 'New York',    tier: 'Enterprise', health: 90, ltv: 187600, orders: 82, lastOrder: '2026-07-17', owner: 'James Doe',    status: 'Active',   industry: 'Fintech',      joined: '2021-02-08', nps: 9 },
    { id: 'CUS-1011', name: 'Sophia Taylor',  company: 'BrightLearn',      email: 'sophia@brightlearn.edu', phone: '+44 161 555 0155', country: 'UK', city: 'Manchester', tier: 'Regular', health: 63, ltv:  22900, orders: 14, lastOrder: '2026-06-28', owner: 'Emma Watson',  status: 'Active',   industry: 'Education',    joined: '2023-11-04', nps: 7 },
    { id: 'CUS-1012', name: 'Mason Rodriguez', company: 'Forge Manufacturing', email: 'mason@forge.co',  phone: '+1 214 555 0132',  country: 'US',  city: 'Dallas',      tier: 'VIP',        health: 79, ltv:  84500, orders: 44, lastOrder: '2026-07-14', owner: 'Alex Kim',     status: 'Active',   industry: 'Manufacturing', joined: '2022-07-22', nps: 8 },
    { id: 'CUS-1013', name: 'Amelia Wright',  company: 'PixelPress',       email: 'amelia@pixelpress.com', phone: '+1 503 555 0143', country: 'US', city: 'Portland',    tier: 'Regular',    health: 58, ltv:  17400, orders: 11, lastOrder: '2026-05-29', owner: 'Sarah Miller', status: 'At Risk',  industry: 'Media',        joined: '2024-01-10', nps: 6 },
    { id: 'CUS-1014', name: 'Ethan Thompson', company: 'Trailhead Outdoor', email: 'ethan@trailhead.co', phone: '+1 720 555 0166',  country: 'US',  city: 'Denver',      tier: 'VIP',        health: 87, ltv:  71800, orders: 36, lastOrder: '2026-07-19', owner: 'Ryan Green',   status: 'Active',   industry: 'Retail',       joined: '2022-09-14', nps: 9 },
    { id: 'CUS-1015', name: 'Mia Garcia',     company: 'Solstice Health',  email: 'mia@solstice.health', phone: '+1 305 555 0117',  country: 'US',  city: 'Miami',       tier: 'Enterprise', health: 93, ltv: 268400, orders: 112, lastOrder: '2026-07-21', owner: 'Alex Kim',    status: 'Active',   industry: 'Healthcare',   joined: '2020-11-02', nps: 10 },
    { id: 'CUS-1016', name: 'Alexander King', company: 'Meridian Bank',    email: 'aking@meridian.bank', phone: '+1 415 555 0189',  country: 'US',  city: 'San Francisco', tier: 'Enterprise', health: 89, ltv: 224100, orders: 98, lastOrder: '2026-07-16', owner: 'James Doe',  status: 'Active',   industry: 'Fintech',      joined: '2021-04-20', nps: 9 },
    { id: 'CUS-1017', name: 'Charlotte Scott', company: 'Bloom Botanicals', email: 'charlotte@bloom.co', phone: '+1 415 555 0176',  country: 'US',  city: 'Seattle',     tier: 'New',        health: 48, ltv:   2100, orders:  2, lastOrder: '2026-07-05', owner: 'Ava Lee',      status: 'Trial',    industry: 'Retail',       joined: '2026-06-27', nps: 7 },
    { id: 'CUS-1018', name: 'Henry Adams',    company: 'Cascade Media',    email: 'henry@cascade.tv',    phone: '+1 503 555 0140',  country: 'US',  city: 'Portland',    tier: 'Regular',    health: 72, ltv:  35200, orders: 20, lastOrder: '2026-07-06', owner: 'Emma Watson',  status: 'Active',   industry: 'Media',        joined: '2023-06-08', nps: 8 },
    { id: 'CUS-1019', name: 'Evelyn Baker',   company: 'Northwind Traders', email: 'evelyn@northwind.co', phone: '+44 20 7946 0199', country: 'UK', city: 'London',      tier: 'VIP',        health: 81, ltv:  92600, orders: 48, lastOrder: '2026-07-13', owner: 'Sarah Miller', status: 'Active',   industry: 'Logistics',    joined: '2022-05-30', nps: 9 },
    { id: 'CUS-1020', name: 'Sebastian Nelson', company: 'Zephyr Cloud',   email: 'seb@zephyr.dev',      phone: '+353 1 555 0111',  country: 'IE',  city: 'Dublin',      tier: 'Enterprise', health: 91, ltv: 198700, orders: 76, lastOrder: '2026-07-20', owner: 'Alex Kim',     status: 'Active',   industry: 'SaaS',         joined: '2021-01-15', nps: 10 },
    { id: 'CUS-1021', name: 'Harper Mitchell', company: 'Ember Coffee Co.', email: 'harper@ember.coffee', phone: '+1 512 555 0128', country: 'US',  city: 'Austin',      tier: 'Regular',    health: 65, ltv:  28900, orders: 17, lastOrder: '2026-06-22', owner: 'Ryan Green',   status: 'Active',   industry: 'Retail',       joined: '2023-08-12', nps: 7 },
    { id: 'CUS-1022', name: 'Jack Perez',     company: 'Quantum Labs',     email: 'jack@quantum-labs.io', phone: '+1 617 555 0155', country: 'US', city: 'Cambridge',   tier: 'VIP',        health: 78, ltv:  67300, orders: 34, lastOrder: '2026-07-11', owner: 'James Doe',    status: 'Active',   industry: 'SaaS',         joined: '2022-12-04', nps: 8 },
    { id: 'CUS-1023', name: 'Lily Roberts',   company: 'Harbor Insurance', email: 'lily@harbor.ins',     phone: '+1 617 555 0170',  country: 'US',  city: 'Boston',      tier: 'Regular',    health: 38, ltv:   9400, orders:  5, lastOrder: '2026-04-18', owner: 'Ava Lee',      status: 'At Risk',  industry: 'Fintech',      joined: '2024-03-22', nps: 4 },
    { id: 'CUS-1024', name: 'Owen Phillips',  company: 'Ridgeway Robotics', email: 'owen@ridgeway.ai',   phone: '+1 408 555 0166',  country: 'US',  city: 'San Jose',    tier: 'Enterprise', health: 94, ltv: 342100, orders: 154, lastOrder: '2026-07-22', owner: 'Alex Kim',    status: 'Active',   industry: 'Manufacturing', joined: '2020-02-11', nps: 10 },
    { id: 'CUS-1025', name: 'Grace Campbell', company: 'Willow Home',      email: 'grace@willow.home',   phone: '+1 646 555 0165',  country: 'US',  city: 'Brooklyn',    tier: 'New',        health: 52, ltv:   3600, orders:  2, lastOrder: '2026-07-19', owner: 'Emma Watson',  status: 'Trial',    industry: 'Retail',       joined: '2026-07-02', nps: 6 }
  ];

  var products = [
    { sku: 'CAM-A100', name: 'Aperture A100 Mirrorless Camera',   category: 'Cameras',    brand: 'Aperture',   price: 1899, cost: 1200, stock: 42 },
    { sku: 'CAM-A100-KIT', name: 'Aperture A100 + 24-70mm Kit',    category: 'Cameras',    brand: 'Aperture',   price: 2499, cost: 1620, stock: 24 },
    { sku: 'SD-EX128',  name: 'ExpressCard SD 128GB V90',          category: 'Storage',    brand: 'ExpressCard', price:   89, cost:   45, stock: 340 },
    { sku: 'HDP-X7',    name: 'HeadphonePro X7 Wireless',          category: 'Audio',      brand: 'HeadphonePro', price:  349, cost:  180, stock: 128 },
    { sku: 'MON-27U',   name: 'MonoDisplay 27" 4K USB-C',           category: 'Displays',   brand: 'MonoDisplay', price:  649, cost:  380, stock:  56 },
    { sku: 'KBD-M1',    name: 'KeyMech M1 Wireless Keyboard',      category: 'Peripherals', brand: 'KeyMech',   price:  189, cost:  105, stock: 214 },
    { sku: 'MOU-P2',    name: 'PointerPro P2 Ergonomic Mouse',     category: 'Peripherals', brand: 'PointerPro', price:   79, cost:   38, stock: 302 },
    { sku: 'JKT-EX',    name: 'Explorer Shell Jacket',              category: 'Apparel',    brand: 'Trailhead', price:  219, cost:  110, stock: 168 },
    { sku: 'BAG-40L',   name: 'Summit 40L Adventure Pack',          category: 'Apparel',    brand: 'Trailhead', price:  159, cost:   72, stock:  94 },
    { sku: 'CFB-250',   name: 'Roaster\'s Reserve 250g Beans',      category: 'Consumables', brand: 'Ember',     price:   24, cost:   10, stock: 512 },
    { sku: 'DRN-M2',    name: 'MicroDrone M2 4K',                  category: 'Cameras',    brand: 'Aperture',   price: 1299, cost:  820, stock:  38 },
    { sku: 'LNS-2470',  name: 'Aperture 24-70mm f/2.8',            category: 'Cameras',    brand: 'Aperture',   price:  899, cost:  520, stock:  62 }
  ];

  var warehouses = [
    { code: 'US-EAST',   name: 'US East',    city: 'Ashburn, VA',   color: '#4f46e5' },
    { code: 'US-WEST',   name: 'US West',    city: 'Reno, NV',      color: '#0ea5e9' },
    { code: 'EU-CENTRAL', name: 'EU Central', city: 'Frankfurt, DE', color: '#22c55e' },
    { code: 'APAC',       name: 'APAC',       city: 'Singapore',     color: '#f97316' }
  ];

  /* ---------- Drag-drop helper for lists ---------- */
  function attachDnD(container, itemSelector, onReorder) {
    if (!container) return;
    var dragEl = null;
    container.addEventListener('dragstart', function (e) {
      var item = e.target.closest(itemSelector);
      if (!item) return;
      dragEl = item;
      item.classList.add('is-dragging');
      try { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', item.dataset.id || ''); } catch (_) {}
    });
    container.addEventListener('dragend', function () {
      if (dragEl) dragEl.classList.remove('is-dragging');
      container.querySelectorAll('.is-over').forEach(function (n) { n.classList.remove('is-over'); });
      dragEl = null;
    });
    container.addEventListener('dragover', function (e) {
      var item = e.target.closest(itemSelector);
      if (!item || item === dragEl) return;
      e.preventDefault();
      try { e.dataTransfer.dropEffect = 'move'; } catch (_) {}
      container.querySelectorAll('.is-over').forEach(function (n) { n.classList.remove('is-over'); });
      item.classList.add('is-over');
    });
    container.addEventListener('drop', function (e) {
      var item = e.target.closest(itemSelector);
      if (!item || !dragEl || item === dragEl) return;
      e.preventDefault();
      var rect = item.getBoundingClientRect();
      var after = (e.clientY - rect.top) > rect.height / 2;
      item.parentNode.insertBefore(dragEl, after ? item.nextSibling : item);
      item.classList.remove('is-over');
      if (typeof onReorder === 'function') {
        var order = Array.prototype.map.call(container.querySelectorAll(itemSelector), function (n) { return n.dataset.id; });
        onReorder(order);
      }
    });
    // Enable draggable attribute
    container.querySelectorAll(itemSelector).forEach(function (n) { n.setAttribute('draggable', 'true'); });
  }

  /* ---------- Sortable table headers ---------- */
  function attachSortable(table, onSort) {
    if (!table) return;
    table.querySelectorAll('th.biz-sortable').forEach(function (th) {
      th.addEventListener('click', function () {
        var key = th.getAttribute('data-sort');
        var dir = th.classList.contains('is-asc') ? 'desc' : 'asc';
        table.querySelectorAll('th.biz-sortable').forEach(function (n) { n.classList.remove('is-asc', 'is-desc'); });
        th.classList.add(dir === 'asc' ? 'is-asc' : 'is-desc');
        if (typeof onSort === 'function') onSort(key, dir);
      });
    });
  }

  /* ---------- Debounce ---------- */
  function debounce(fn, ms) {
    var t;
    return function () {
      var self = this, args = arguments;
      clearTimeout(t);
      t = setTimeout(function () { fn.apply(self, args); }, ms);
    };
  }

  /* ---------- Escape HTML ---------- */
  function escapeHtml(s) {
    if (s == null) return '';
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
    });
  }

  /* ---------- Public API ---------- */
  window.BizExtras = {
    toast: orchidToast,
    fmtMoney: fmtMoney,
    fmtDate: fmtDate,
    fmtRelative: fmtRelative,
    renderStars: renderStars,
    renderAvatar: renderAvatar,
    bindAvatarColors: bindAvatarColors,
    escapeHtml: escapeHtml,
    debounce: debounce,
    attachDnD: attachDnD,
    attachSortable: attachSortable,
    customers: customers,
    products: products,
    warehouses: warehouses,
    owners: owners,
    industries: industries
  };

  document.addEventListener('DOMContentLoaded', function () {
    ensureHost();
    bindAvatarColors(document);
  });
})();
