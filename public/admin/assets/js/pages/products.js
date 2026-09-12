/* =====================================================
   Orchid — Products (shared toolkit + per-page controllers)
   ===================================================== */
(function () {
  'use strict';

  /* ---------------- SHARED HELPERS ---------------- */

  function fmtMoney(n) {
    if (n == null || isNaN(n)) return '$0';
    return '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }
  function fmtNumber(n) {
    if (n == null || isNaN(n)) return '0';
    return Number(n).toLocaleString('en-US');
  }
  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }
  function renderStars(rating) {
    var r = Math.max(0, Math.min(5, Number(rating) || 0));
    var full = Math.floor(r);
    var half = (r - full) >= 0.5;
    var empty = 5 - full - (half ? 1 : 0);
    var html = '<span class="products-stars" aria-label="Rating ' + r.toFixed(1) + ' of 5">';
    for (var i = 0; i < full;  i++) html += '<i class="bi bi-star-fill"></i>';
    if (half)                        html += '<i class="bi bi-star-half"></i>';
    for (var j = 0; j < empty; j++)  html += '<i class="bi bi-star"></i>';
    html += '<span class="products-stars__num">' + r.toFixed(1) + '</span>';
    html += '</span>';
    return html;
  }

  /* ---------------- TOAST ---------------- */
  function ensureToastContainer() {
    var c = document.querySelector('.products-toast-container');
    if (!c) {
      c = document.createElement('div');
      c.className = 'products-toast-container';
      c.setAttribute('aria-live', 'polite');
      c.setAttribute('aria-atomic', 'true');
      document.body.appendChild(c);
    }
    return c;
  }
  function orchidToast(title, message, variant) {
    variant = variant || 'success';
    var icons = { success: 'bi-check-circle-fill', warn: 'bi-exclamation-triangle-fill', error: 'bi-x-octagon-fill', info: 'bi-info-circle-fill' };
    var el = document.createElement('div');
    el.className = 'products-toast products-toast--' + variant;
    el.setAttribute('role', 'status');
    el.innerHTML =
      '<i class="products-toast__icon bi ' + (icons[variant] || icons.info) + '"></i>' +
      '<div class="products-toast__body">' +
        '<p class="products-toast__title">' + escapeHtml(title) + '</p>' +
        (message ? '<p class="products-toast__msg">' + escapeHtml(message) + '</p>' : '') +
      '</div>';
    ensureToastContainer().appendChild(el);
    setTimeout(function () {
      el.style.transition = 'opacity 220ms ease, transform 220ms ease';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-4px)';
      setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 240);
    }, 3200);
  }

  function filterList(list, predicate) {
    return list.filter(predicate);
  }
  function sortList(list, key, dir) {
    dir = dir === 'desc' ? -1 : 1;
    return list.slice().sort(function (a, b) {
      var av = a[key], bv = b[key];
      if (typeof av === 'string') return av.localeCompare(bv) * dir;
      return (av - bv) * dir;
    });
  }

  /* ---------------- SVG THUMB GENERATOR ---------------- */
  function makeThumb(seed, colorA, colorB) {
    var id = 'g' + Math.random().toString(36).slice(2, 8);
    var s = String(seed || 'x');
    var hash = 0;
    for (var i = 0; i < s.length; i++) hash = (hash * 31 + s.charCodeAt(i)) & 0xffffffff;
    var r = Math.abs(hash);
    var cx = 60 + (r % 80);
    var cy = 50 + ((r >> 3) % 60);
    var rx = 60 + ((r >> 5) % 40);
    var ry = 50 + ((r >> 7) % 30);
    return (
      '<svg viewBox="0 0 240 180" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Product image">' +
        '<defs>' +
          '<linearGradient id="' + id + '" x1="0" y1="0" x2="1" y2="1">' +
            '<stop offset="0%" stop-color="' + colorA + '"/>' +
            '<stop offset="100%" stop-color="' + colorB + '"/>' +
          '</linearGradient>' +
        '</defs>' +
        '<rect width="240" height="180" fill="url(#' + id + ')"/>' +
        '<ellipse cx="' + cx + '" cy="' + cy + '" rx="' + rx + '" ry="' + ry + '" fill="rgba(255,255,255,0.28)"/>' +
        '<circle cx="' + (200 - (r % 50)) + '" cy="' + (140 - ((r >> 2) % 40)) + '" r="' + (14 + ((r >> 4) % 20)) + '" fill="rgba(255,255,255,0.22)"/>' +
        '<circle cx="' + (40 + ((r >> 6) % 30)) + '" cy="' + (35 + ((r >> 8) % 30)) + '" r="' + (10 + ((r >> 10) % 18)) + '" fill="rgba(255,255,255,0.18)"/>' +
      '</svg>'
    );
  }

  /* ---------------- SHARED DATA ---------------- */
  var CATEGORIES_TREE = [
    { id: 'c1', name: 'Electronics', color: '#4f46e5', children: [
      { id: 'c1a', name: 'Laptops', color: '#6366f1' },
      { id: 'c1b', name: 'Headphones', color: '#8b5cf6' },
      { id: 'c1c', name: 'Cameras', color: '#7c3aed' },
      { id: 'c1d', name: 'Smart Home', color: '#a78bfa' }
    ]},
    { id: 'c2', name: 'Apparel', color: '#ec4899', children: [
      { id: 'c2a', name: "Men's", color: '#f472b6' },
      { id: 'c2b', name: "Women's", color: '#e11d48' },
      { id: 'c2c', name: 'Accessories', color: '#db2777' }
    ]},
    { id: 'c3', name: 'Home & Garden', color: '#16a34a', children: [
      { id: 'c3a', name: 'Furniture', color: '#22c55e' },
      { id: 'c3b', name: 'Kitchen', color: '#059669' },
      { id: 'c3c', name: 'Tools', color: '#84cc16' }
    ]},
    { id: 'c4', name: 'Sports & Outdoors', color: '#0ea5e9', children: [
      { id: 'c4a', name: 'Fitness', color: '#0284c7' },
      { id: 'c4b', name: 'Cycling', color: '#06b6d4' },
      { id: 'c4c', name: 'Camping', color: '#38bdf8' }
    ]},
    { id: 'c5', name: 'Books', color: '#d97706', children: [
      { id: 'c5a', name: 'Fiction', color: '#f59e0b' },
      { id: 'c5b', name: 'Non-fiction', color: '#eab308' },
      { id: 'c5c', name: 'Technical', color: '#ca8a04' }
    ]},
    { id: 'c6', name: 'Beauty', color: '#f43f5e', children: [
      { id: 'c6a', name: 'Skincare', color: '#fb7185' },
      { id: 'c6b', name: 'Fragrance', color: '#e11d48' }
    ]}
  ];

  var BRANDS = [
    { id: 'b1', name: 'Halo',     region: 'US' },
    { id: 'b2', name: 'Nomad',    region: 'US' },
    { id: 'b3', name: 'Aurora',   region: 'EU' },
    { id: 'b4', name: 'Meridian', region: 'US' },
    { id: 'b5', name: 'Nova',     region: 'EU' },
    { id: 'b6', name: 'Aegis',    region: 'APAC' },
    { id: 'b7', name: 'Terra',    region: 'EU' },
    { id: 'b8', name: 'Cirrus',   region: 'US' },
    { id: 'b9', name: 'Ember',    region: 'APAC' },
    { id: 'b10', name: 'Vertex',  region: 'US' },
    { id: 'b11', name: 'Pelagic', region: 'APAC' },
    { id: 'b12', name: 'Kestrel', region: 'EU' },
    { id: 'b13', name: 'Titan',   region: 'US' },
    { id: 'b14', name: 'Zenith',  region: 'APAC' },
    { id: 'b15', name: 'Orchid',   region: 'EU' }
  ];

  var GRAD_PAIRS = [
    ['#4f46e5', '#22d3ee'], ['#ec4899', '#f97316'], ['#16a34a', '#84cc16'],
    ['#0ea5e9', '#6366f1'], ['#f59e0b', '#ef4444'], ['#8b5cf6', '#ec4899'],
    ['#06b6d4', '#3b82f6'], ['#22c55e', '#10b981'], ['#f43f5e', '#a855f7'],
    ['#7c3aed', '#2563eb'], ['#f97316', '#facc15'], ['#0891b2', '#22c55e']
  ];
  function gradFor(seed) {
    var s = String(seed || 'x');
    var hash = 0;
    for (var i = 0; i < s.length; i++) hash = (hash * 31 + s.charCodeAt(i)) & 0xffffffff;
    return GRAD_PAIRS[Math.abs(hash) % GRAD_PAIRS.length];
  }

  var PRODUCTS = [
    { id: 'p1', name: 'Halo Wireless Earbuds Pro', sku: 'HL-EAR-PRO-BK', price: 199, category: 'Headphones', catId: 'c1b', parentId: 'c1', brand: 'Halo', rating: 4.7, stock: 128, status: 'in', tags: ['audio','wireless','new'], updated: '2026-07-20' },
    { id: 'p2', name: 'Nomad Weekender Bag', sku: 'NM-BAG-WK-BR', price: 148, category: "Men's", catId: 'c2a', parentId: 'c2', brand: 'Nomad', rating: 4.5, stock: 42, status: 'in', tags: ['travel'], updated: '2026-07-18' },
    { id: 'p3', name: 'Aurora Smart Bulb 4-Pack', sku: 'AR-BLB-4PK', price: 59, category: 'Smart Home', catId: 'c1d', parentId: 'c1', brand: 'Aurora', rating: 4.3, stock: 8, status: 'low', tags: ['smart','sale'], updated: '2026-07-15' },
    { id: 'p4', name: "Meridian Chef's Knife 8-inch", sku: 'MR-KNF-8', price: 120, category: 'Kitchen', catId: 'c3b', parentId: 'c3', brand: 'Meridian', rating: 4.8, stock: 65, status: 'in', tags: ['kitchen','pro'], updated: '2026-07-22' },
    { id: 'p5', name: 'Nova 4K Action Cam', sku: 'NV-CAM-4K', price: 349, category: 'Cameras', catId: 'c1c', parentId: 'c1', brand: 'Nova', rating: 4.6, stock: 0, status: 'oos', tags: ['camera'], updated: '2026-07-10' },
    { id: 'p6', name: 'Aegis Yoga Mat Pro', sku: 'AG-YOG-PR-GR', price: 68, category: 'Fitness', catId: 'c4a', parentId: 'c4', brand: 'Aegis', rating: 4.4, stock: 210, status: 'in', tags: ['yoga','fitness'], updated: '2026-07-19' },
    { id: 'p7', name: 'Terra Cast Iron Skillet 12"', sku: 'TR-SKL-12', price: 89, category: 'Kitchen', catId: 'c3b', parentId: 'c3', brand: 'Terra', rating: 4.7, stock: 33, status: 'in', tags: ['kitchen'], updated: '2026-07-14' },
    { id: 'p8', name: 'Cirrus Ultralight Tent 2P', sku: 'CR-TNT-2P', price: 279, category: 'Camping', catId: 'c4c', parentId: 'c4', brand: 'Cirrus', rating: 4.5, stock: 12, status: 'low', tags: ['camping','outdoor'], updated: '2026-07-12' },
    { id: 'p9', name: 'Ember Ceramic Mug', sku: 'EM-MUG-14', price: 129, category: 'Kitchen', catId: 'c3b', parentId: 'c3', brand: 'Ember', rating: 4.2, stock: 88, status: 'in', tags: ['drinkware'], updated: '2026-07-21' },
    { id: 'p10', name: 'Vertex 15" Laptop Sleeve', sku: 'VX-LSL-15', price: 45, category: 'Laptops', catId: 'c1a', parentId: 'c1', brand: 'Vertex', rating: 4.1, stock: 0, status: 'discontinued', tags: ['accessory'], updated: '2026-05-30' },
    { id: 'p11', name: 'Halo Studio Headphones', sku: 'HL-HDP-ST', price: 259, category: 'Headphones', catId: 'c1b', parentId: 'c1', brand: 'Halo', rating: 4.8, stock: 47, status: 'in', tags: ['audio','pro'], updated: '2026-07-17' },
    { id: 'p12', name: 'Pelagic Dive Watch', sku: 'PL-WCH-DV', price: 599, category: 'Accessories', catId: 'c2c', parentId: 'c2', brand: 'Pelagic', rating: 4.9, stock: 5, status: 'low', tags: ['premium','watch'], updated: '2026-07-11' },
    { id: 'p13', name: 'Kestrel Trail Running Shoes', sku: 'KS-SHO-TR', price: 139, category: 'Fitness', catId: 'c4a', parentId: 'c4', brand: 'Kestrel', rating: 4.3, stock: 76, status: 'in', tags: ['running'], updated: '2026-07-16' },
    { id: 'p14', name: 'Nomad Leather Wallet', sku: 'NM-WAL-LT', price: 78, category: 'Accessories', catId: 'c2c', parentId: 'c2', brand: 'Nomad', rating: 4.4, stock: 195, status: 'in', tags: ['leather'], updated: '2026-07-13' },
    { id: 'p15', name: 'Titan Adjustable Dumbbells', sku: 'TT-DMB-55', price: 429, category: 'Fitness', catId: 'c4a', parentId: 'c4', brand: 'Titan', rating: 4.7, stock: 22, status: 'in', tags: ['fitness','heavy'], updated: '2026-07-09' },
    { id: 'p16', name: 'Zenith Coffee Grinder', sku: 'ZN-GRD-CF', price: 165, category: 'Kitchen', catId: 'c3b', parentId: 'c3', brand: 'Zenith', rating: 4.6, stock: 54, status: 'in', tags: ['coffee'], updated: '2026-07-20' },
    { id: 'p17', name: 'Orchid Standing Desk', sku: 'OR-DSK-ST', price: 549, category: 'Furniture', catId: 'c3a', parentId: 'c3', brand: 'Orchid', rating: 4.5, stock: 15, status: 'low', tags: ['office'], updated: '2026-07-06' },
    { id: 'p18', name: 'Nova Mirrorless Camera', sku: 'NV-CAM-MR', price: 1299, category: 'Cameras', catId: 'c1c', parentId: 'c1', brand: 'Nova', rating: 4.9, stock: 8, status: 'low', tags: ['pro','camera'], updated: '2026-07-08' },
    { id: 'p19', name: 'Aurora Sunrise Lamp', sku: 'AR-LMP-SR', price: 89, category: 'Smart Home', catId: 'c1d', parentId: 'c1', brand: 'Aurora', rating: 4.2, stock: 140, status: 'in', tags: ['home','wellness'], updated: '2026-07-19' },
    { id: 'p20', name: 'Vertex Ultrabook 14"', sku: 'VX-LTP-14', price: 1499, category: 'Laptops', catId: 'c1a', parentId: 'c1', brand: 'Vertex', rating: 4.7, stock: 18, status: 'in', tags: ['pro','laptop'], updated: '2026-07-04' },
    { id: 'p21', name: "Ember Women's Puffer Jacket", sku: 'EM-JKT-PF', price: 189, category: "Women's", catId: 'c2b', parentId: 'c2', brand: 'Ember', rating: 4.4, stock: 61, status: 'in', tags: ['winter'], updated: '2026-07-01' },
    { id: 'p22', name: 'Terra Garden Tool Set', sku: 'TR-GRD-TS', price: 95, category: 'Tools', catId: 'c3c', parentId: 'c3', brand: 'Terra', rating: 4.1, stock: 40, status: 'in', tags: ['garden'], updated: '2026-06-28' },
    { id: 'p23', name: 'Cirrus Cycling Helmet', sku: 'CR-HLM-CY', price: 149, category: 'Cycling', catId: 'c4b', parentId: 'c4', brand: 'Cirrus', rating: 4.5, stock: 27, status: 'in', tags: ['safety'], updated: '2026-07-05' },
    { id: 'p24', name: 'Titan Kettlebell 20kg', sku: 'TT-KTB-20', price: 79, category: 'Fitness', catId: 'c4a', parentId: 'c4', brand: 'Titan', rating: 4.6, stock: 300, status: 'over', tags: ['fitness'], updated: '2026-07-20' }
  ];

  var STATUS_LABEL = { in: 'In Stock', low: 'Low', oos: 'Out of Stock', discontinued: 'Discontinued', over: 'Overstock' };
  var STATUS_CHIP  = { in: 'products-chip--instock', low: 'products-chip--low', oos: 'products-chip--oos', discontinued: 'products-chip--discont', over: 'products-chip--overstock' };

  /* ---------------- INVENTORY DATA ---------------- */
  var WAREHOUSES = [
    { id: 'use', code: 'US-East',    chip: 'inv-warehouse-chip--use' },
    { id: 'usw', code: 'US-West',    chip: 'inv-warehouse-chip--usw' },
    { id: 'eu',  code: 'EU-Central', chip: 'inv-warehouse-chip--eu' },
    { id: 'ap',  code: 'APAC',       chip: 'inv-warehouse-chip--ap' }
  ];

  var INVENTORY = [
    { sku: 'HL-EAR-PRO-BK', name: 'Halo Wireless Earbuds Pro', category: 'Headphones', wh: 'use', onHand: 128, reserved: 12, reorder: 40, status: 'in',  lastRecv: '2026-07-19' },
    { sku: 'NM-BAG-WK-BR',  name: 'Nomad Weekender Bag',        category: "Men's",      wh: 'usw', onHand: 42,  reserved: 8,  reorder: 25, status: 'in',  lastRecv: '2026-07-15' },
    { sku: 'AR-BLB-4PK',    name: 'Aurora Smart Bulb 4-Pack',   category: 'Smart Home', wh: 'eu',  onHand: 8,   reserved: 3,  reorder: 20, status: 'low', lastRecv: '2026-07-02' },
    { sku: 'MR-KNF-8',      name: "Meridian Chef's Knife 8\"", category: 'Kitchen',    wh: 'use', onHand: 65,  reserved: 5,  reorder: 30, status: 'in',  lastRecv: '2026-07-21' },
    { sku: 'NV-CAM-4K',     name: 'Nova 4K Action Cam',         category: 'Cameras',    wh: 'ap',  onHand: 0,   reserved: 0,  reorder: 15, status: 'oos', lastRecv: '2026-06-10' },
    { sku: 'AG-YOG-PR-GR',  name: 'Aegis Yoga Mat Pro',         category: 'Fitness',    wh: 'usw', onHand: 210, reserved: 14, reorder: 50, status: 'in',  lastRecv: '2026-07-18' },
    { sku: 'TR-SKL-12',     name: 'Terra Cast Iron Skillet 12"',category: 'Kitchen',    wh: 'eu',  onHand: 33,  reserved: 4,  reorder: 20, status: 'in',  lastRecv: '2026-07-08' },
    { sku: 'CR-TNT-2P',     name: 'Cirrus Ultralight Tent 2P',  category: 'Camping',    wh: 'usw', onHand: 12,  reserved: 2,  reorder: 15, status: 'low', lastRecv: '2026-07-04' },
    { sku: 'EM-MUG-14',     name: 'Ember Ceramic Mug',          category: 'Kitchen',    wh: 'ap',  onHand: 88,  reserved: 6,  reorder: 40, status: 'in',  lastRecv: '2026-07-20' },
    { sku: 'VX-LSL-15',     name: 'Vertex 15" Laptop Sleeve',   category: 'Laptops',    wh: 'use', onHand: 0,   reserved: 0,  reorder: 10, status: 'oos', lastRecv: '2026-04-30' },
    { sku: 'HL-HDP-ST',     name: 'Halo Studio Headphones',     category: 'Headphones', wh: 'usw', onHand: 47,  reserved: 3,  reorder: 25, status: 'in',  lastRecv: '2026-07-17' },
    { sku: 'PL-WCH-DV',     name: 'Pelagic Dive Watch',         category: 'Accessories',wh: 'eu',  onHand: 5,   reserved: 1,  reorder: 10, status: 'low', lastRecv: '2026-06-25' },
    { sku: 'KS-SHO-TR',     name: 'Kestrel Trail Running Shoes',category: 'Fitness',    wh: 'ap',  onHand: 76,  reserved: 8,  reorder: 35, status: 'in',  lastRecv: '2026-07-14' },
    { sku: 'NM-WAL-LT',     name: 'Nomad Leather Wallet',       category: 'Accessories',wh: 'use', onHand: 195, reserved: 12, reorder: 60, status: 'in',  lastRecv: '2026-07-11' },
    { sku: 'TT-DMB-55',     name: 'Titan Adjustable Dumbbells', category: 'Fitness',    wh: 'usw', onHand: 22,  reserved: 3,  reorder: 12, status: 'in',  lastRecv: '2026-07-06' },
    { sku: 'ZN-GRD-CF',     name: 'Zenith Coffee Grinder',      category: 'Kitchen',    wh: 'eu',  onHand: 54,  reserved: 4,  reorder: 25, status: 'in',  lastRecv: '2026-07-19' },
    { sku: 'OR-DSK-ST',     name: 'Orchid Standing Desk',        category: 'Furniture',  wh: 'ap',  onHand: 15,  reserved: 3,  reorder: 20, status: 'low', lastRecv: '2026-07-01' },
    { sku: 'NV-CAM-MR',     name: 'Nova Mirrorless Camera',     category: 'Cameras',    wh: 'use', onHand: 8,   reserved: 2,  reorder: 12, status: 'low', lastRecv: '2026-07-07' },
    { sku: 'AR-LMP-SR',     name: 'Aurora Sunrise Lamp',        category: 'Smart Home', wh: 'eu',  onHand: 140, reserved: 9,  reorder: 45, status: 'in',  lastRecv: '2026-07-18' },
    { sku: 'VX-LTP-14',     name: 'Vertex Ultrabook 14"',       category: 'Laptops',    wh: 'usw', onHand: 18,  reserved: 4,  reorder: 10, status: 'in',  lastRecv: '2026-07-03' },
    { sku: 'EM-JKT-PF',     name: "Ember Women's Puffer Jacket",category: "Women's",    wh: 'ap',  onHand: 61,  reserved: 6,  reorder: 30, status: 'in',  lastRecv: '2026-06-30' },
    { sku: 'TT-KTB-20',     name: 'Titan Kettlebell 20kg',      category: 'Fitness',    wh: 'use', onHand: 300, reserved: 10, reorder: 60, status: 'over', lastRecv: '2026-07-20' }
  ];

  /* Expose toolkit ------------------------------------ */
  window.OrchidProducts = {
    fmtMoney: fmtMoney,
    fmtNumber: fmtNumber,
    escapeHtml: escapeHtml,
    renderStars: renderStars,
    orchidToast: orchidToast,
    filterList: filterList,
    sortList: sortList,
    makeThumb: makeThumb,
    gradFor: gradFor,
    data: {
      products: PRODUCTS,
      brands: BRANDS,
      categoriesTree: CATEGORIES_TREE,
      inventory: INVENTORY,
      warehouses: WAREHOUSES
    },
    statusLabel: STATUS_LABEL,
    statusChip: STATUS_CHIP
  };

  /* ---------------- BANNER DISMISS ---------------- */
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-products-banner-close]');
    if (t) {
      var b = t.closest('.products-banner');
      if (b) b.remove();
    }
  });

  /* =====================================================
     CONTROLLER: PRODUCT LIST
     ===================================================== */
  var plRoot = document.querySelector('[data-page="product-list"]');
  if (plRoot) initProductList(plRoot);

  function initProductList(root) {
    var state = {
      view: 'grid',
      search: '',
      sort: 'popular',
      selected: new Set(),
      filters: {
        categories: [],
        brands: [],
        priceMin: 0,
        priceMax: 2000,
        rating: 0,
        statuses: [],
        tags: []
      }
    };

    var gridEl   = root.querySelector('[data-pl-grid]');
    var tableEl  = root.querySelector('[data-pl-table-body]');
    var emptyEl  = root.querySelector('[data-pl-empty]');
    var tableWrap= root.querySelector('[data-pl-table-wrap]');
    var searchEl = root.querySelector('[data-pl-search]');
    var sortEl   = root.querySelector('[data-pl-sort]');
    var toggleBtns = root.querySelectorAll('[data-pl-view]');
    var bulkbar = root.querySelector('[data-pl-bulk]');
    var bulkCount = root.querySelector('[data-pl-bulk-count]');

    toggleBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        state.view = btn.getAttribute('data-pl-view');
        toggleBtns.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
        gridEl.classList.toggle('pl-hidden', state.view !== 'grid');
        tableWrap.classList.toggle('pl-hidden', state.view !== 'table');
        render();
      });
    });

    if (searchEl) searchEl.addEventListener('input', function () {
      state.search = this.value.trim().toLowerCase();
      render();
    });
    if (sortEl) sortEl.addEventListener('change', function () {
      state.sort = this.value;
      render();
    });

    // Filter drawer wiring
    var applyBtn = root.querySelector('[data-pl-filter-apply]');
    var clearBtn = root.querySelector('[data-pl-filter-clear]');
    if (applyBtn) applyBtn.addEventListener('click', function () {
      collectFilters();
      var oc = bootstrap.Offcanvas.getInstance(document.getElementById('plFilters'));
      if (oc) oc.hide();
      render();
      orchidToast('Filters applied', 'Product list updated', 'info');
    });
    if (clearBtn) clearBtn.addEventListener('click', function () {
      var form = root.querySelector('[data-pl-filter-form]');
      if (form) form.reset();
      state.filters = { categories: [], brands: [], priceMin: 0, priceMax: 2000, rating: 0, statuses: [], tags: [] };
      var pmi = root.querySelector('[data-pl-price-min]'); if (pmi) pmi.value = 0;
      var pma = root.querySelector('[data-pl-price-max]'); if (pma) pma.value = 2000;
      render();
      orchidToast('Filters cleared', '', 'info');
    });

    function collectFilters() {
      state.filters.categories = Array.from(root.querySelectorAll('[data-pl-filter-cat]:checked')).map(function (i) { return i.value; });
      state.filters.brands     = Array.from(root.querySelectorAll('[data-pl-filter-brand]:checked')).map(function (i) { return i.value; });
      state.filters.statuses   = Array.from(root.querySelectorAll('[data-pl-filter-status]:checked')).map(function (i) { return i.value; });
      state.filters.tags       = Array.from(root.querySelectorAll('[data-pl-filter-tag]:checked')).map(function (i) { return i.value; });
      var rEl = root.querySelector('[data-pl-filter-rating]:checked');
      state.filters.rating     = rEl ? Number(rEl.value) : 0;
      var pmi = root.querySelector('[data-pl-price-min]');
      var pma = root.querySelector('[data-pl-price-max]');
      state.filters.priceMin   = pmi ? Number(pmi.value) || 0 : 0;
      state.filters.priceMax   = pma ? Number(pma.value) || 999999 : 999999;
    }

    // Add product modal
    var addForm = root.querySelector('[data-pl-add-form]');
    if (addForm) addForm.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!addForm.checkValidity()) { addForm.classList.add('was-validated'); return; }
      var data = new FormData(addForm);
      var newP = {
        id: 'p' + (PRODUCTS.length + 1),
        name: data.get('name'),
        sku: data.get('sku'),
        price: Number(data.get('price')) || 0,
        category: data.get('category') || 'Electronics',
        brand: data.get('brand') || 'Orchid',
        rating: 4.0,
        stock: Number(data.get('stock')) || 0,
        status: (Number(data.get('stock')) || 0) > 0 ? 'in' : 'oos',
        tags: [],
        updated: new Date().toISOString().slice(0,10)
      };
      PRODUCTS.unshift(newP);
      addForm.reset();
      addForm.classList.remove('was-validated');
      var mod = bootstrap.Modal.getInstance(document.getElementById('plAddModal'));
      if (mod) mod.hide();
      orchidToast('Product added', newP.name + ' created', 'success');
      render();
    });

    // Bulk actions
    root.querySelectorAll('[data-pl-bulk-action]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var act = btn.getAttribute('data-pl-bulk-action');
        if (state.selected.size === 0) return;
        if (act === 'delete') {
          var toDel = new Set(state.selected);
          for (var i = PRODUCTS.length - 1; i >= 0; i--) {
            if (toDel.has(PRODUCTS[i].id)) PRODUCTS.splice(i, 1);
          }
          orchidToast('Deleted', toDel.size + ' product(s) removed', 'warn');
        } else if (act === 'export') {
          orchidToast('Export started', state.selected.size + ' product(s) queued for CSV', 'info');
        } else if (act.indexOf('status:') === 0) {
          var st = act.split(':')[1];
          PRODUCTS.forEach(function (p) { if (state.selected.has(p.id)) p.status = st; });
          orchidToast('Status updated', 'Set to ' + STATUS_LABEL[st], 'success');
        } else if (act.indexOf('category:') === 0) {
          var cat = act.split(':')[1];
          PRODUCTS.forEach(function (p) { if (state.selected.has(p.id)) p.category = cat; });
          orchidToast('Category updated', 'Moved to ' + cat, 'success');
        } else if (act === 'clear') {
          // handled below
        }
        state.selected.clear();
        render();
      });
    });

    function filteredList() {
      var f = state.filters;
      var list = PRODUCTS.filter(function (p) {
        if (state.search && !(p.name.toLowerCase().indexOf(state.search) !== -1 || p.sku.toLowerCase().indexOf(state.search) !== -1)) return false;
        if (f.categories.length && f.categories.indexOf(p.category) === -1 && f.categories.indexOf(p.parentId) === -1) return false;
        if (f.brands.length && f.brands.indexOf(p.brand) === -1) return false;
        if (p.price < f.priceMin || p.price > f.priceMax) return false;
        if (f.rating && p.rating < f.rating) return false;
        if (f.statuses.length && f.statuses.indexOf(p.status) === -1) return false;
        if (f.tags.length && !p.tags.some(function (t) { return f.tags.indexOf(t) !== -1; })) return false;
        return true;
      });
      // sort
      if (state.sort === 'price-asc')  list.sort(function (a,b) { return a.price - b.price; });
      else if (state.sort === 'price-desc') list.sort(function (a,b) { return b.price - a.price; });
      else if (state.sort === 'rating') list.sort(function (a,b) { return b.rating - a.rating; });
      else if (state.sort === 'new')    list.sort(function (a,b) { return (b.updated||'').localeCompare(a.updated||''); });
      // 'popular' = default order
      return list;
    }

    function render() {
      var list = filteredList();
      // Skeletons hidden on first render
      root.querySelectorAll('[data-pl-skeleton]').forEach(function (s) { s.remove(); });

      // Empty state
      if (list.length === 0) {
        emptyEl.classList.add('is-visible');
        gridEl.innerHTML = '';
        tableEl.innerHTML = '';
      } else {
        emptyEl.classList.remove('is-visible');
      }

      // Grid
      if (state.view === 'grid') {
        gridEl.innerHTML = list.map(cardHTML).join('');
      } else {
        tableEl.innerHTML = list.map(rowHTML).join('');
      }

      // wire selection + fav
      root.querySelectorAll('[data-pl-select]').forEach(function (ck) {
        ck.checked = state.selected.has(ck.getAttribute('data-pl-select'));
        ck.addEventListener('change', function () {
          var id = ck.getAttribute('data-pl-select');
          if (ck.checked) state.selected.add(id); else state.selected.delete(id);
          updateBulk();
        });
      });
      root.querySelectorAll('[data-pl-fav]').forEach(function (b) {
        b.addEventListener('click', function () {
          b.classList.toggle('is-fav');
          orchidToast(b.classList.contains('is-fav') ? 'Added to favorites' : 'Removed from favorites', '', 'info');
        });
      });
      root.querySelectorAll('[data-pl-quick]').forEach(function (b) {
        b.addEventListener('click', function () {
          var act = b.getAttribute('data-pl-quick');
          orchidToast(act.charAt(0).toUpperCase()+act.slice(1), 'Action triggered', 'info');
        });
      });

      updateBulk();
    }

    function updateBulk() {
      var n = state.selected.size;
      if (bulkCount) bulkCount.textContent = n;
      if (bulkbar) bulkbar.classList.toggle('is-visible', n > 0);
      // update visuals
      root.querySelectorAll('.pl-card').forEach(function (c) {
        c.classList.toggle('is-selected', state.selected.has(c.getAttribute('data-pl-card')));
      });
      root.querySelectorAll('.pl-table tbody tr').forEach(function (r) {
        r.classList.toggle('is-selected', state.selected.has(r.getAttribute('data-pl-row')));
      });
    }

    function cardHTML(p) {
      var img = productImage(p, 480, 360);
      return (
        '<div class="pl-card" data-pl-card="' + p.id + '">' +
          '<div class="form-check pl-card__check">' +
            '<input class="form-check-input" type="checkbox" data-pl-select="' + p.id + '" aria-label="Select ' + escapeHtml(p.name) + '">' +
          '</div>' +
          '<button type="button" class="pl-card__fav" data-pl-fav aria-label="Add to favorites"><i class="bi bi-heart"></i></button>' +
          '<div class="pl-card__thumb">' +
            '<img class="pl-card__img" src="' + img + '" alt="' + escapeHtml(p.name) + '" loading="lazy" width="480" height="360">' +
          '</div>' +
          '<div class="pl-card__body">' +
            '<div class="pl-card__cat">' + escapeHtml(p.category) + '</div>' +
            '<h6 class="pl-card__name">' + escapeHtml(p.name) + '</h6>' +
            '<div class="pl-card__sku">' + escapeHtml(p.sku) + '</div>' +
            '<div class="pl-card__meta">' +
              '<span class="pl-card__price">' + fmtMoney(p.price) + '</span>' +
              '<span class="products-chip ' + STATUS_CHIP[p.status] + '">' + STATUS_LABEL[p.status] + '</span>' +
            '</div>' +
            renderStars(p.rating) +
            '<div class="pl-card__actions">' +
              '<button type="button" class="pl-card__action" data-pl-quick="view" aria-label="View"><i class="bi bi-eye"></i></button>' +
              '<button type="button" class="pl-card__action" data-pl-quick="edit" aria-label="Edit"><i class="bi bi-pencil"></i></button>' +
              '<button type="button" class="pl-card__action pl-card__action--primary" data-pl-quick="cart" aria-label="Add to cart"><i class="bi bi-cart-plus"></i></button>' +
              '<button type="button" class="pl-card__action" data-pl-quick="more" aria-label="More"><i class="bi bi-three-dots"></i></button>' +
            '</div>' +
          '</div>' +
        '</div>'
      );
    }

    function rowHTML(p) {
      var img = productImage(p, 96, 96);
      return (
        '<tr data-pl-row="' + p.id + '">' +
          '<td><div class="form-check m-0"><input type="checkbox" class="form-check-input" data-pl-select="' + p.id + '" aria-label="Select"></div></td>' +
          '<td><div class="pl-name-cell"><div class="pl-thumb-sm"><img class="pl-thumb-sm__img" src="' + img + '" alt="' + escapeHtml(p.name) + '" loading="lazy" width="48" height="48"></div>' +
            '<div><p class="pl-name-cell__name">' + escapeHtml(p.name) + '</p>' +
            '<span class="pl-name-cell__sku">' + escapeHtml(p.sku) + '</span></div></div></td>' +
          '<td><span class="pl-catchip"><i class="bi bi-tag"></i>' + escapeHtml(p.category) + '</span></td>' +
          '<td>' + escapeHtml(p.brand) + '</td>' +
          '<td class="fw-semibold">' + fmtMoney(p.price) + '</td>' +
          '<td>' + fmtNumber(p.stock) + ' <span class="products-chip ' + STATUS_CHIP[p.status] + '">' + STATUS_LABEL[p.status] + '</span></td>' +
          '<td>' + renderStars(p.rating) + '</td>' +
          '<td class="text-body-secondary">' + escapeHtml(p.updated) + '</td>' +
          '<td class="text-end">' +
            '<button class="btn btn-sm btn-icon" type="button" data-pl-quick="edit" aria-label="Edit"><i class="bi bi-pencil"></i></button> ' +
            '<button class="btn btn-sm btn-icon" type="button" data-pl-quick="more" aria-label="More"><i class="bi bi-three-dots-vertical"></i></button>' +
          '</td>' +
        '</tr>'
      );
    }

    // initial render after brief skeleton delay
    setTimeout(render, 350);
  }

  /* =====================================================
     CONTROLLER: CATEGORIES
     ===================================================== */
  var catRoot = document.querySelector('[data-page="product-categories"]');
  if (catRoot) initCategories(catRoot);

  function initCategories(root) {
    var tree = JSON.parse(JSON.stringify(CATEGORIES_TREE));
    var counts = {};
    PRODUCTS.forEach(function (p) {
      counts[p.catId] = (counts[p.catId] || 0) + 1;
      counts[p.parentId] = (counts[p.parentId] || 0) + 1;
    });

    var state = { selectedId: null };

    var treeEl = root.querySelector('[data-cat-tree]');
    var detailEl = root.querySelector('[data-cat-detail]');
    var searchEl = root.querySelector('[data-cat-search]');

    function findNode(id, list) {
      list = list || tree;
      for (var i = 0; i < list.length; i++) {
        if (list[i].id === id) return { node: list[i], parent: list, parentNode: null };
        if (list[i].children) {
          var found = findNode(id, list[i].children);
          if (found) {
            if (!found.parentNode) found.parentNode = list[i];
            return found;
          }
        }
      }
      return null;
    }

    function renderTree() {
      var q = (searchEl && searchEl.value || '').trim().toLowerCase();
      treeEl.innerHTML = buildBranch(tree, q, 0);
      wireTreeInteractions();
    }

    function buildBranch(list, q, depth) {
      return '<ul class="cat-tree' + (depth === 0 ? '' : ' cat-tree--sub') + '">' + list.map(function (n) {
        var hasKids = n.children && n.children.length;
        var visible = !q || n.name.toLowerCase().indexOf(q) !== -1 ||
                      (hasKids && n.children.some(function (c) { return c.name.toLowerCase().indexOf(q) !== -1; }));
        if (!visible) return '';
        var openCls = q ? ' is-open' : '';
        return '<li>' +
          '<div class="cat-node' + (state.selectedId === n.id ? ' is-selected' : '') + '" ' +
               'data-cat-node="' + n.id + '" draggable="true">' +
            '<span class="cat-node__chev' + (hasKids ? openCls : ' is-leaf') + '" data-cat-chev><i class="bi bi-chevron-right"></i></span>' +
            '<span class="cat-node__dot" data-dot-for="' + n.id + '"></span>' +
            '<span class="cat-node__name">' + escapeHtml(n.name) + '</span>' +
            '<span class="cat-node__count">' + (counts[n.id] || 0) + '</span>' +
            '<span class="cat-node__grip"><i class="bi bi-grip-vertical"></i></span>' +
            '<span class="cat-node__actions">' +
              '<button type="button" data-cat-act="add" aria-label="Add subcategory"><i class="bi bi-plus"></i></button>' +
              '<button type="button" data-cat-act="edit" aria-label="Edit"><i class="bi bi-pencil"></i></button>' +
              '<button type="button" data-cat-act="del" aria-label="Delete"><i class="bi bi-trash"></i></button>' +
            '</span>' +
          '</div>' +
          (hasKids ? '<div class="cat-node__children' + openCls + '">' + buildBranch(n.children, q, depth+1) + '</div>' : '') +
        '</li>';
      }).join('') + '</ul>';
    }

    function wireTreeInteractions() {
      // apply dot colors
      treeEl.querySelectorAll('[data-dot-for]').forEach(function (el) {
        var id = el.getAttribute('data-dot-for');
        var found = findNode(id);
        if (found) el.style.background = found.node.color;
      });

      treeEl.querySelectorAll('[data-cat-node]').forEach(function (node) {
        var id = node.getAttribute('data-cat-node');

        node.addEventListener('click', function (e) {
          if (e.target.closest('[data-cat-chev]') || e.target.closest('[data-cat-act]')) return;
          state.selectedId = id;
          renderTree();
          renderDetail();
        });

        var chev = node.querySelector('[data-cat-chev]');
        if (chev) chev.addEventListener('click', function (e) {
          e.stopPropagation();
          if (chev.classList.contains('is-leaf')) return;
          chev.classList.toggle('is-open');
          var kids = node.parentNode.querySelector('.cat-node__children');
          if (kids) kids.classList.toggle('is-open');
        });

        node.querySelectorAll('[data-cat-act]').forEach(function (btn) {
          btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var act = btn.getAttribute('data-cat-act');
            var f = findNode(id);
            if (!f) return;
            if (act === 'add') {
              var newId = 'c' + Date.now();
              f.node.children = f.node.children || [];
              f.node.children.push({ id: newId, name: 'New Subcategory', color: '#818cf8' });
              counts[newId] = 0;
              orchidToast('Subcategory added', 'Under ' + f.node.name, 'success');
              renderTree();
            } else if (act === 'edit') {
              state.selectedId = id;
              renderTree();
              renderDetail();
              var input = detailEl.querySelector('[data-cat-name]');
              if (input) input.focus();
            } else if (act === 'del') {
              var idx = f.parent.indexOf(f.node);
              if (idx >= 0) f.parent.splice(idx, 1);
              orchidToast('Deleted', f.node.name + ' removed', 'warn');
              if (state.selectedId === id) state.selectedId = null;
              renderTree();
              renderDetail();
            }
          });
        });

        // Drag & drop
        node.addEventListener('dragstart', function (e) {
          node.classList.add('is-dragging');
          e.dataTransfer.setData('text/cat', id);
          e.dataTransfer.effectAllowed = 'move';
        });
        node.addEventListener('dragend', function () { node.classList.remove('is-dragging'); });
        node.addEventListener('dragover', function (e) { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; node.classList.add('is-dragover'); });
        node.addEventListener('dragleave', function () { node.classList.remove('is-dragover'); });
        node.addEventListener('drop', function (e) {
          e.preventDefault();
          node.classList.remove('is-dragover');
          var dragId = e.dataTransfer.getData('text/cat');
          if (!dragId || dragId === id) return;
          var src = findNode(dragId);
          var dst = findNode(id);
          if (!src || !dst) return;
          // remove from source
          var idx = src.parent.indexOf(src.node);
          if (idx >= 0) src.parent.splice(idx, 1);
          // insert before target
          var dstIdx = dst.parent.indexOf(dst.node);
          dst.parent.splice(dstIdx, 0, src.node);
          orchidToast('Reordered', src.node.name + ' moved', 'info');
          renderTree();
        });
      });
    }

    function renderDetail() {
      if (!state.selectedId) {
        detailEl.innerHTML =
          '<div class="cat-detail__empty">' +
            '<i class="bi bi-diagram-3 mb-2"></i>' +
            '<h6 class="mt-2">Select a category</h6>' +
            '<p class="mb-0 small">Click any category on the left to view and edit its details.</p>' +
          '</div>';
        return;
      }
      var f = findNode(state.selectedId);
      if (!f) { detailEl.innerHTML = ''; return; }
      var n = f.node;
      var palette = ['#4f46e5','#22d3ee','#16a34a','#f59e0b','#ef4444','#ec4899','#8b5cf6','#0ea5e9'];
      detailEl.innerHTML =
        '<div class="cat-detail">' +
          '<div class="d-flex align-items-center justify-content-between mb-3">' +
            '<span class="badge bg-primary-subtle text-primary">Category</span>' +
            '<div class="d-flex gap-2">' +
              '<button type="button" class="btn btn-sm btn-outline-secondary" data-cat-view-products><i class="bi bi-box me-1"></i>' + (counts[n.id] || 0) + ' products</button>' +
              '<div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" id="catVisible" checked data-cat-visible><label class="form-check-label small" for="catVisible">Visible</label></div>' +
            '</div>' +
          '</div>' +
          '<input type="text" class="cat-detail__nameinput mb-3" value="' + escapeHtml(n.name) + '" data-cat-name aria-label="Category name">' +
          '<div class="mb-3">' +
            '<label class="form-label small text-uppercase fw-semibold">Description</label>' +
            '<textarea class="form-control" rows="3" placeholder="Describe this category…" data-cat-desc>' + escapeHtml(n.description || '') + '</textarea>' +
          '</div>' +
          '<div class="row g-3 mb-3">' +
            '<div class="col-md-6">' +
              '<label class="form-label small text-uppercase fw-semibold">Color</label>' +
              '<div class="cat-color-swatches" data-cat-swatches>' +
                palette.map(function (c) {
                  return '<button type="button" class="cat-color-swatch' + (n.color === c ? ' is-selected' : '') + '" data-cat-color="' + c + '" data-color-fill="' + c + '" aria-label="Color ' + c + '"></button>';
                }).join('') +
              '</div>' +
            '</div>' +
            '<div class="col-md-6">' +
              '<label class="form-label small text-uppercase fw-semibold">Image</label>' +
              '<label class="cat-upload d-block" for="catImgInput">' +
                '<img class="cat-upload__preview" alt="Preview" data-cat-preview>' +
                '<i class="bi bi-cloud-upload d-block mb-1" data-cat-upload-icon></i>' +
                '<span class="small">Click or drop image here</span>' +
                '<input type="file" id="catImgInput" accept="image/*" class="visually-hidden" data-cat-img>' +
              '</label>' +
            '</div>' +
          '</div>' +
          '<hr>' +
          '<h6 class="text-uppercase small fw-bold mb-3">SEO</h6>' +
          '<div class="row g-3">' +
            '<div class="col-12"><label class="form-label small">Meta title</label><input type="text" class="form-control" value="' + escapeHtml(n.name) + ' | Orchid Store"></div>' +
            '<div class="col-12"><label class="form-label small">Meta description</label><textarea class="form-control" rows="2">Shop the best in ' + escapeHtml(n.name) + '.</textarea></div>' +
            '<div class="col-12"><label class="form-label small">URL slug</label><div class="input-group input-group-sm"><span class="input-group-text">/category/</span><input type="text" class="form-control" value="' + escapeHtml(n.name.toLowerCase().replace(/\s+/g,'-')) + '"></div></div>' +
          '</div>' +
          '<div class="d-flex justify-content-end gap-2 mt-4">' +
            '<button type="button" class="btn btn-outline-secondary btn-sm" data-cat-cancel>Cancel</button>' +
            '<button type="button" class="btn btn-primary btn-sm" data-cat-save><i class="bi bi-check2 me-1"></i>Save changes</button>' +
          '</div>' +
        '</div>';

      // Apply color swatch background colors dynamically
      detailEl.querySelectorAll('[data-color-fill]').forEach(function (el) {
        el.style.background = el.getAttribute('data-color-fill');
      });

      // wire detail
      var nameEl = detailEl.querySelector('[data-cat-name]');
      nameEl.addEventListener('input', function () { n.name = nameEl.value; });
      nameEl.addEventListener('blur', function () { renderTree(); });

      detailEl.querySelectorAll('[data-cat-color]').forEach(function (b) {
        b.addEventListener('click', function () {
          n.color = b.getAttribute('data-cat-color');
          detailEl.querySelectorAll('[data-cat-color]').forEach(function (x) { x.classList.remove('is-selected'); });
          b.classList.add('is-selected');
          renderTree();
        });
      });

      var img = detailEl.querySelector('[data-cat-img]');
      var preview = detailEl.querySelector('[data-cat-preview]');
      var uploadIcon = detailEl.querySelector('[data-cat-upload-icon]');
      if (img) img.addEventListener('change', function () {
        if (img.files && img.files[0]) {
          var reader = new FileReader();
          reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.add('is-visible');
            if (uploadIcon) uploadIcon.classList.add('pl-hidden');
          };
          reader.readAsDataURL(img.files[0]);
        }
      });

      detailEl.querySelector('[data-cat-save]').addEventListener('click', function () {
        orchidToast('Category saved', n.name + ' updated', 'success');
        renderTree();
      });
      detailEl.querySelector('[data-cat-cancel]').addEventListener('click', function () {
        state.selectedId = null;
        renderTree();
        renderDetail();
      });
      detailEl.querySelector('[data-cat-view-products]').addEventListener('click', function () {
        orchidToast('Products list', (counts[n.id] || 0) + ' items in ' + n.name, 'info');
      });
    }

    if (searchEl) searchEl.addEventListener('input', renderTree);

    // New category toolbar
    var addBtn = root.querySelector('[data-cat-new]');
    if (addBtn) addBtn.addEventListener('click', function () {
      var newId = 'c' + Date.now();
      tree.push({ id: newId, name: 'New Category', color: '#4f46e5', children: [] });
      counts[newId] = 0;
      state.selectedId = newId;
      renderTree();
      renderDetail();
      orchidToast('Category added', 'New Category created', 'success');
    });

    var importBtn = root.querySelector('[data-cat-import]');
    if (importBtn) importBtn.addEventListener('click', function () {
      orchidToast('Import ready', 'Choose a CSV file to import', 'info');
    });

    renderTree();
    renderDetail();
  }

  /* =====================================================
     CONTROLLER: BRANDS
     ===================================================== */
  var brandRoot = document.querySelector('[data-page="product-brands"]');
  var TAGLINES = [
    'Pure sound engineered', 'Built for the road', 'Light up your life',
    'Where craft meets steel', 'Capture the moment', 'Wellness in motion',
    'Fire-tempered essentials', 'Explore untethered', 'Warm rituals daily',
    'Peak performance', 'Deep ocean precision', 'Freedom to run',
    'Strength refined', 'Coffee redefined', 'Work smarter'
  ];

  // Curated Unsplash photo IDs grouped by product category / concept.
  // Same product id/sku deterministically resolves to the same photo.
  var IMG_UNSPLASH = {
    Headphones:   ['1590658268037-6bf12165a8df','1583394838336-acd977736f90','1608156639585-b3ac9425bb0f','1546435770-a3e426bf472b'],
    Cameras:      ['1502920917128-1aa500764cbd','1526170375885-4d8ecf77b99f','1516035069371-29a1b244cc32','1495707902641-75cac588d2e9'],
    Laptops:      ['1517336714731-489689fd1ca8','1496181133206-80ce9b88a853','1541807084-5c52b6b3adef','1587614382346-4ec70e388b28'],
    'Smart Home': ['1552242718-c5360894aecd','1507473885765-e6ed057f782c','1585500402180-1a75c1b7fcac','1550547660-d9450f859349'],
    Kitchen:      ['1556909114-f6e7ad7d3136','1590794056226-79ef3a8147e1','1608500218890-c4b93196e947','1585515320310-259814833e62'],
    Fitness:      ['1571019613454-1cb2f99b2d8b','1518611012118-696072aa579a','1534438327276-14e5300c3a48','1541534741688-6078c6bfb5c5'],
    Camping:      ['1478131143081-80f7f84ca84d','1504280390367-361c6d9f38f4','1523987355523-c7b5b0dd90a7','1508873699372-7aeab60b44ab'],
    "Men's":      ['1553062407-98eeb64c6a62','1548036328-c9fa89d128fa','1590874103328-eac38a683ce7','1524498250077-390f9e378fc0'],
    "Women's":    ['1590874103328-eac38a683ce7','1594633312681-425c7b97ccd1','1584917865442-de89df76afd3','1596461404969-9ae70f2830c1'],
    Accessories:  ['1524805444758-089113d48a6d','1523275335684-37898b6baf30','1522312346375-d1a52e2b99b3','1548036328-c9fa89d128fa'],
    Furniture:    ['1587467512961-120760940315','1517705008128-361805f42e86','1555041469-a586c61ea9bc','1586023492125-27b2c045efd7']
  };
  var IMG_FALLBACK = ['1560343090-f0409e92791a','1524678606370-a47ad25cb82a','1553062407-98eeb64c6a62'];

  function productImage(p, w, h) {
    var pool = IMG_UNSPLASH[p.category] || IMG_FALLBACK;
    var seed = String(p.id || p.sku || p.name || 'x');
    var hash = 0;
    for (var i = 0; i < seed.length; i++) hash = (hash * 31 + seed.charCodeAt(i)) >>> 0;
    var photoId = pool[hash % pool.length];
    return 'https://images.unsplash.com/photo-' + photoId + '?auto=format&fit=crop&w=' + w + '&h=' + h + '&q=70';
  }

  // Defensive: if any Unsplash image 404s, swap to a picsum placeholder so the
  // card/row doesn't render as a broken image.
  document.addEventListener('error', function (e) {
    var el = e.target;
    if (!el || el.tagName !== 'IMG') return;
    if (!el.matches('.pl-card__img, .pl-thumb-sm__img, .inv-thumb-sm__img')) return;
    if (el.dataset.plFallbackApplied === '1') return;
    el.dataset.plFallbackApplied = '1';
    var w = el.getAttribute('width') || 480;
    var h = el.getAttribute('height') || 360;
    el.src = 'https://picsum.photos/seed/orchid-fallback-' + (el.alt || 'x').replace(/\s+/g, '-') + '/' + w + '/' + h;
  }, true); // useCapture=true — <img> error events don't bubble

  if (brandRoot) initBrands(brandRoot);

  function initBrands(root) {
    // compute per-brand product counts and mock stats
    var byBrand = {};
    PRODUCTS.forEach(function (p) {
      if (!byBrand[p.brand]) byBrand[p.brand] = { count: 0, inStock: 0, revenue: 0 };
      byBrand[p.brand].count++;
      if (p.status === 'in') byBrand[p.brand].inStock += p.stock;
      byBrand[p.brand].revenue += p.price * (p.stock || 1);
    });

    var brandDataFull = BRANDS.map(function (b, i) {
      var s = byBrand[b.name] || { count: 0, inStock: 0, revenue: 0 };
      var trend = (i % 3 === 0 ? -1 : 1) * (5 + (i * 7) % 22);
      return Object.assign({}, b, {
        products: s.count || Math.floor(Math.random() * 20) + 3,
        inStock: s.inStock || Math.floor(Math.random() * 800) + 50,
        revenue: s.revenue || Math.floor(Math.random() * 90000) + 8000,
        trend: trend,
        tagline: TAGLINES[i % TAGLINES.length]
      });
    });

    var state = {
      search: '',
      regions: [],
      sort: 'alpha',
      selected: new Set()
    };

    var gridEl = root.querySelector('[data-brand-grid]');
    var emptyEl = root.querySelector('[data-brand-empty]');
    var searchEl = root.querySelector('[data-brand-search]');
    var sortEl = root.querySelector('[data-brand-sort]');
    var bulkbar = root.querySelector('[data-brand-bulk]');
    var bulkCount = root.querySelector('[data-brand-bulk-count]');

    // stats hero
    var totalBrandsEl = root.querySelector('[data-brand-stat-brands]');
    var totalSkusEl   = root.querySelector('[data-brand-stat-skus]');
    var activeEl      = root.querySelector('[data-brand-stat-active]');
    if (totalBrandsEl) totalBrandsEl.textContent = fmtNumber(brandDataFull.length);
    if (totalSkusEl)   totalSkusEl.textContent = fmtNumber(brandDataFull.reduce(function (a, b) { return a + b.products; }, 0));
    if (activeEl)      activeEl.textContent = fmtNumber(brandDataFull.filter(function (b) { return b.products > 0; }).length);

    if (searchEl) searchEl.addEventListener('input', function () { state.search = this.value.trim().toLowerCase(); render(); });
    if (sortEl)   sortEl.addEventListener('change', function () { state.sort = this.value; render(); });
    root.querySelectorAll('[data-brand-region]').forEach(function (ck) {
      ck.addEventListener('change', function () {
        state.regions = Array.from(root.querySelectorAll('[data-brand-region]:checked')).map(function (i) { return i.value; });
        render();
      });
    });

    // bulk actions
    root.querySelectorAll('[data-brand-bulk-action]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var act = btn.getAttribute('data-brand-bulk-action');
        if (state.selected.size === 0) return;
        if (act === 'delete') {
          for (var i = brandDataFull.length - 1; i >= 0; i--) {
            if (state.selected.has(brandDataFull[i].id)) brandDataFull.splice(i, 1);
          }
          orchidToast('Deleted', state.selected.size + ' brand(s) removed', 'warn');
        } else if (act === 'merge') {
          orchidToast('Merged', state.selected.size + ' brand(s) merged', 'success');
        } else if (act === 'export') {
          orchidToast('Export queued', state.selected.size + ' brand(s) queued', 'info');
        }
        state.selected.clear();
        render();
      });
    });

    // add brand form
    var addForm = root.querySelector('[data-brand-add-form]');
    var logoInput = root.querySelector('[data-brand-logo-input]');
    var logoPrev = root.querySelector('[data-brand-logo-preview]');
    if (logoInput) logoInput.addEventListener('change', function () {
      if (logoInput.files && logoInput.files[0]) {
        var r = new FileReader();
        r.onload = function (e) { logoPrev.innerHTML = '<img alt="Logo preview" src="' + e.target.result + '">'; };
        r.readAsDataURL(logoInput.files[0]);
      }
    });
    if (addForm) addForm.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!addForm.checkValidity()) { addForm.classList.add('was-validated'); return; }
      var fd = new FormData(addForm);
      var newB = {
        id: 'b' + Date.now(),
        name: fd.get('name'),
        region: fd.get('region') || 'US',
        tagline: fd.get('tagline') || '',
        products: 0,
        inStock: 0,
        revenue: 0,
        trend: 0
      };
      brandDataFull.unshift(newB);
      addForm.reset();
      addForm.classList.remove('was-validated');
      logoPrev.innerHTML = '<i class="bi bi-image"></i>';
      var m = bootstrap.Modal.getInstance(document.getElementById('brandAddModal'));
      if (m) m.hide();
      orchidToast('Brand added', newB.name + ' created', 'success');
      render();
    });

    function filtered() {
      var list = brandDataFull.filter(function (b) {
        if (state.search && b.name.toLowerCase().indexOf(state.search) === -1) return false;
        if (state.regions.length && state.regions.indexOf(b.region) === -1) return false;
        return true;
      });
      if (state.sort === 'alpha')      list.sort(function (a, b) { return a.name.localeCompare(b.name); });
      else if (state.sort === 'products') list.sort(function (a, b) { return b.products - a.products; });
      else if (state.sort === 'revenue')  list.sort(function (a, b) { return b.revenue - a.revenue; });
      return list;
    }

    function render() {
      root.querySelectorAll('[data-brand-skeleton]').forEach(function (s) { s.remove(); });
      var list = filtered();
      if (!list.length) {
        gridEl.innerHTML = '';
        emptyEl.classList.add('is-visible');
      } else {
        emptyEl.classList.remove('is-visible');
        gridEl.innerHTML = list.map(cardHTML).join('');
      }

      root.querySelectorAll('[data-brand-card]').forEach(function (card) {
        var id = card.getAttribute('data-brand-card');
        var tile = card.querySelector('[data-brand-tile]');
        if (tile) {
          var g = gradFor(id + '-tile');
          tile.style.background = 'linear-gradient(135deg,' + g[0] + ',' + g[1] + ')';
        }
        card.addEventListener('click', function (e) {
          if (e.target.closest('.brand-card__actions') || e.target.closest('input')) return;
          var ck = card.querySelector('[data-brand-select]');
          if (ck) { ck.checked = !ck.checked; ck.dispatchEvent(new Event('change')); }
        });
      });

      root.querySelectorAll('[data-brand-select]').forEach(function (ck) {
        ck.checked = state.selected.has(ck.getAttribute('data-brand-select'));
        ck.addEventListener('change', function () {
          var id = ck.getAttribute('data-brand-select');
          if (ck.checked) state.selected.add(id); else state.selected.delete(id);
          if (bulkCount) bulkCount.textContent = state.selected.size;
          if (bulkbar) bulkbar.classList.toggle('is-visible', state.selected.size > 0);
          var card = root.querySelector('[data-brand-card="' + id + '"]');
          if (card) card.classList.toggle('is-selected', ck.checked);
        });
      });

      root.querySelectorAll('[data-brand-quick]').forEach(function (b) {
        b.addEventListener('click', function (e) {
          e.stopPropagation();
          var act = b.getAttribute('data-brand-quick');
          orchidToast(act.charAt(0).toUpperCase()+act.slice(1), 'Brand action triggered', 'info');
        });
      });
    }

    function cardHTML(b) {
      var initial = b.name.charAt(0).toUpperCase();
      var trendUp = b.trend >= 0;
      return (
        '<div class="brand-card" data-brand-card="' + b.id + '">' +
          '<div class="form-check brand-card__check">' +
            '<input class="form-check-input" type="checkbox" data-brand-select="' + b.id + '" aria-label="Select ' + escapeHtml(b.name) + '">' +
          '</div>' +
          '<div class="brand-card__header">' +
            '<div class="brand-card__tile" data-brand-tile>' + initial + '</div>' +
            '<div>' +
              '<h6 class="brand-card__title">' + escapeHtml(b.name) + '</h6>' +
              '<p class="brand-card__tagline">' + escapeHtml(b.tagline || 'Region: ' + b.region) + '</p>' +
            '</div>' +
          '</div>' +
          '<div class="brand-card__stats">' +
            '<div class="brand-card__stat"><p>Products</p><strong>' + fmtNumber(b.products) + '</strong></div>' +
            '<div class="brand-card__stat"><p>In stock</p><strong>' + fmtNumber(b.inStock) + '</strong></div>' +
            '<div class="brand-card__stat"><p>Revenue</p><strong>' + fmtMoney(b.revenue) + '</strong><small class="' + (trendUp ? 'up' : 'down') + '"><i class="bi bi-arrow-' + (trendUp ? 'up' : 'down') + '-short"></i>' + Math.abs(b.trend) + '%</small></div>' +
          '</div>' +
          '<div class="brand-card__actions">' +
            '<button type="button" class="brand-card__action brand-card__action--primary" data-brand-quick="view"><i class="bi bi-box me-1"></i>Products</button>' +
            '<button type="button" class="brand-card__action" data-brand-quick="edit"><i class="bi bi-pencil me-1"></i>Edit</button>' +
            '<button type="button" class="brand-card__action" data-brand-quick="analytics"><i class="bi bi-graph-up me-1"></i>Analytics</button>' +
          '</div>' +
        '</div>'
      );
    }

    setTimeout(render, 300);
  }

  /* =====================================================
     CONTROLLER: INVENTORY
     ===================================================== */
  var invRoot = document.querySelector('[data-page="inventory"]');
  if (invRoot) initInventory(invRoot);

  function initInventory(root) {
    var data = INVENTORY.slice();
    var state = {
      search: '',
      warehouses: [],
      category: 'all',
      status: 'all',
      selected: new Set()
    };

    var tbody = root.querySelector('[data-inv-tbody]');
    var empty = root.querySelector('[data-inv-empty]');
    var searchEl = root.querySelector('[data-inv-search]');
    var catEl = root.querySelector('[data-inv-filter-cat]');
    var stEl  = root.querySelector('[data-inv-filter-status]');
    var bulkbar = root.querySelector('[data-inv-bulk]');
    var bulkCount = root.querySelector('[data-inv-bulk-count]');
    var selectAllEl = root.querySelector('[data-inv-selectall]');

    // Populate category filter
    if (catEl) {
      var cats = Array.from(new Set(data.map(function (r) { return r.category; }))).sort();
      catEl.innerHTML = '<option value="all">All categories</option>' + cats.map(function (c) { return '<option value="' + c + '">' + escapeHtml(c) + '</option>'; }).join('');
    }

    // KPI rings
    var totalSkus = data.length;
    var lowN  = data.filter(function (r) { return r.status === 'low'; }).length;
    var oosN  = data.filter(function (r) { return r.status === 'oos'; }).length;
    var overN = data.filter(function (r) { return r.status === 'over'; }).length;

    setRing(root.querySelector('[data-inv-kpi="total"]'), totalSkus, totalSkus);
    setRing(root.querySelector('[data-inv-kpi="low"]'),   lowN,  totalSkus);
    setRing(root.querySelector('[data-inv-kpi="oos"]'),   oosN,  totalSkus);
    setRing(root.querySelector('[data-inv-kpi="over"]'),  overN, totalSkus);

    var totalValEl = root.querySelector('[data-inv-kpi-val="total"]'); if (totalValEl) totalValEl.textContent = fmtNumber(totalSkus);
    var lowValEl   = root.querySelector('[data-inv-kpi-val="low"]');   if (lowValEl)   lowValEl.textContent = fmtNumber(lowN);
    var oosValEl   = root.querySelector('[data-inv-kpi-val="oos"]');   if (oosValEl)   oosValEl.textContent = fmtNumber(oosN);
    var overValEl  = root.querySelector('[data-inv-kpi-val="over"]');  if (overValEl)  overValEl.textContent = fmtNumber(overN);

    function setRing(el, part, total) {
      if (!el) return;
      var circle = el.querySelector('circle.fg');
      if (!circle) return;
      var r = 24;
      var c = 2 * Math.PI * r;
      var pct = total > 0 ? Math.min(100, (part / total) * 100) : 0;
      circle.setAttribute('stroke-dasharray', c.toFixed(2));
      circle.setAttribute('stroke-dashoffset', (c * (1 - pct/100)).toFixed(2));
    }

    // Warehouse filter checkboxes
    root.querySelectorAll('[data-inv-wh]').forEach(function (ck) {
      ck.addEventListener('change', function () {
        state.warehouses = Array.from(root.querySelectorAll('[data-inv-wh]:checked')).map(function (i) { return i.value; });
        render();
      });
    });
    if (searchEl) searchEl.addEventListener('input', function () { state.search = this.value.trim().toLowerCase(); render(); });
    if (catEl)    catEl.addEventListener('change', function () { state.category = this.value; render(); });
    if (stEl)     stEl.addEventListener('change', function () { state.status = this.value; render(); });

    // Bulk actions
    root.querySelectorAll('[data-inv-bulk-action]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var act = btn.getAttribute('data-inv-bulk-action');
        if (state.selected.size === 0) return;
        if (act === 'restock')  orchidToast('Restock queued', state.selected.size + ' SKU(s)', 'info');
        if (act === 'transfer') orchidToast('Transfer created', state.selected.size + ' SKU(s)', 'info');
        if (act === 'adjust')   orchidToast('Adjustment saved', state.selected.size + ' SKU(s)', 'success');
        if (act === 'export')   orchidToast('Report exported', 'Ready for download', 'success');
        state.selected.clear();
        render();
      });
    });

    if (selectAllEl) selectAllEl.addEventListener('change', function () {
      var list = filtered();
      if (selectAllEl.checked) list.forEach(function (r) { state.selected.add(r.sku); });
      else state.selected.clear();
      render();
    });

    // Restock modal
    var restockForm = root.querySelector('[data-inv-restock-form]');
    var restockSku  = root.querySelector('[data-inv-restock-sku]');
    if (restockForm) restockForm.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!restockForm.checkValidity()) { restockForm.classList.add('was-validated'); return; }
      var sku = restockSku.value;
      var qty = Number(restockForm.querySelector('[name=qty]').value) || 0;
      var row = data.find(function (r) { return r.sku === sku; });
      if (row) {
        row.onHand += qty;
        row.lastRecv = new Date().toISOString().slice(0,10);
        if (row.status === 'oos' && row.onHand > 0) row.status = row.onHand < row.reorder ? 'low' : 'in';
        else if (row.status === 'low' && row.onHand >= row.reorder) row.status = 'in';
      }
      restockForm.reset();
      restockForm.classList.remove('was-validated');
      var m = bootstrap.Modal.getInstance(document.getElementById('invRestockModal'));
      if (m) m.hide();
      orchidToast('Restocked', sku + ' + ' + qty + ' units', 'success');
      render();
    });

    function filtered() {
      return data.filter(function (r) {
        if (state.search && r.name.toLowerCase().indexOf(state.search) === -1 && r.sku.toLowerCase().indexOf(state.search) === -1) return false;
        if (state.warehouses.length && state.warehouses.indexOf(r.wh) === -1) return false;
        if (state.category !== 'all' && r.category !== state.category) return false;
        if (state.status !== 'all' && r.status !== state.status) return false;
        return true;
      });
    }

    function render() {
      root.querySelectorAll('[data-inv-skeleton]').forEach(function (s) { s.remove(); });
      var list = filtered();
      if (!list.length) {
        tbody.innerHTML = '';
        empty.classList.add('is-visible');
      } else {
        empty.classList.remove('is-visible');
        tbody.innerHTML = list.map(rowHTML).join('');
      }
      root.querySelectorAll('[data-inv-select]').forEach(function (ck) {
        ck.checked = state.selected.has(ck.getAttribute('data-inv-select'));
        ck.addEventListener('change', function () {
          var sku = ck.getAttribute('data-inv-select');
          if (ck.checked) state.selected.add(sku); else state.selected.delete(sku);
          bulkCount.textContent = state.selected.size;
          bulkbar.classList.toggle('is-visible', state.selected.size > 0);
          var tr = ck.closest('tr'); if (tr) tr.classList.toggle('is-selected', ck.checked);
        });
      });
      root.querySelectorAll('[data-inv-restock-btn]').forEach(function (b) {
        b.addEventListener('click', function () {
          var sku = b.getAttribute('data-inv-restock-btn');
          var row = data.find(function (r) { return r.sku === sku; });
          if (!row) return;
          restockSku.value = sku;
          var lbl = root.querySelector('[data-inv-restock-name]');
          if (lbl) lbl.textContent = row.name + ' (' + sku + ')';
          var m = new bootstrap.Modal(document.getElementById('invRestockModal'));
          m.show();
        });
      });

      bulkCount.textContent = state.selected.size;
      bulkbar.classList.toggle('is-visible', state.selected.size > 0);
    }

    function rowHTML(r) {
      var wh = WAREHOUSES.find(function (w) { return w.id === r.wh; }) || WAREHOUSES[0];
      var avail = r.onHand - r.reserved;
      var availCls = avail <= 0 ? 'inv-num-danger' : (avail < r.reorder ? 'inv-num-warn' : 'inv-num-ok');
      var img = productImage(r, 96, 96);
      return (
        '<tr>' +
          '<td><div class="form-check m-0"><input type="checkbox" class="form-check-input" data-inv-select="' + r.sku + '" aria-label="Select"></div></td>' +
          '<td class="fw-semibold">' + escapeHtml(r.sku) + '</td>' +
          '<td><div class="d-flex align-items-center gap-2"><div class="inv-thumb-sm"><img class="inv-thumb-sm__img" src="' + img + '" alt="' + escapeHtml(r.name) + '" loading="lazy" width="40" height="40"></div>' + escapeHtml(r.name) + '</div></td>' +
          '<td class="text-body-secondary">' + escapeHtml(r.category) + '</td>' +
          '<td><span class="inv-warehouse-chip ' + wh.chip + '"><i class="bi bi-building"></i>' + escapeHtml(wh.code) + '</span></td>' +
          '<td class="text-end">' + fmtNumber(r.onHand) + '</td>' +
          '<td class="text-end text-body-secondary">' + fmtNumber(r.reserved) + '</td>' +
          '<td class="text-end ' + availCls + '">' + fmtNumber(avail) + '</td>' +
          '<td class="text-end">' + fmtNumber(r.reorder) + '</td>' +
          '<td><span class="products-chip ' + STATUS_CHIP[r.status] + '">' + STATUS_LABEL[r.status] + '</span></td>' +
          '<td class="text-body-secondary">' + escapeHtml(r.lastRecv) + '</td>' +
          '<td class="text-end"><button class="btn btn-sm btn-outline-primary" type="button" data-inv-restock-btn="' + r.sku + '"><i class="bi bi-plus-lg me-1"></i>Restock</button></td>' +
        '</tr>'
      );
    }

    // Chart
    var canvas = root.querySelector('[data-inv-chart]');
    if (canvas && window.Chart) {
      var days = 30;
      var labels = [];
      var recv = [], ship = [];
      for (var i = days - 1; i >= 0; i--) {
        var d = new Date(); d.setDate(d.getDate() - i);
        labels.push(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        recv.push(Math.floor(40 + Math.random() * 120));
        ship.push(Math.floor(60 + Math.random() * 140));
      }
      new Chart(canvas, {
        data: {
          labels: labels,
          datasets: [
            { type: 'bar', label: 'Received', data: recv, backgroundColor: 'rgba(79,70,229,.4)', borderColor: '#4f46e5', borderWidth: 1, borderRadius: 4 },
            { type: 'line', label: 'Shipped', data: ship, borderColor: '#22d3ee', backgroundColor: 'rgba(34,211,238,.15)', tension: .35, fill: true, pointRadius: 2 }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } },
          scales: {
            x: { grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 8 } },
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' } }
          }
        }
      });
    }

    setTimeout(render, 300);
  }
})();
