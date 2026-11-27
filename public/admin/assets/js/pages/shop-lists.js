/* =====================================================
   Orchid — Shop Lists (E-Commerce list pages) toolkit
   Namespaces: data-shop-* + per-page hooks
   ===================================================== */
(function () {
  'use strict';

  /* ---------------- Product photo helper ---------------- */
  // Curated Unsplash IDs per category. Same SKU always resolves to the same photo.
  var IMG_UNSPLASH = {
    Headphones:   ['1590658268037-6bf12165a8df','1583394838336-acd977736f90','1608156639585-b3ac9425bb0f','1546435770-a3e426bf472b'],
    Cameras:      ['1502920917128-1aa500764cbd','1526170375885-4d8ecf77b99f','1516035069371-29a1b244cc32','1495707902641-75cac588d2e9'],
    Accessories:  ['1553062407-98eeb64c6a62','1524805444758-089113d48a6d','1548036328-c9fa89d128fa','1523275335684-37898b6baf30'],
    'Smart Home': ['1552242718-c5360894aecd','1507473885765-e6ed057f782c','1585500402180-1a75c1b7fcac','1550547660-d9450f859349'],
    Kitchen:      ['1556909114-f6e7ad7d3136','1590794056226-79ef3a8147e1','1608500218890-c4b93196e947','1585515320310-259814833e62'],
    Fitness:      ['1571019613454-1cb2f99b2d8b','1518611012118-696072aa579a','1534438327276-14e5300c3a48','1541534741688-6078c6bfb5c5']
  };
  var IMG_FALLBACK = ['1560343090-f0409e92791a','1524678606370-a47ad25cb82a','1553062407-98eeb64c6a62'];

  function productImage(p, w, h) {
    var cat = p.cat || p.category;
    var pool = IMG_UNSPLASH[cat] || IMG_FALLBACK;
    var seed = String(p.sku || p.id || p.name || 'x');
    var hash = 0;
    for (var i = 0; i < seed.length; i++) hash = (hash * 31 + seed.charCodeAt(i)) >>> 0;
    var photoId = pool[hash % pool.length];
    return 'https://images.unsplash.com/photo-' + photoId + '?auto=format&fit=crop&w=' + w + '&h=' + h + '&q=70';
  }

  // Defensive: swap any 404'd product image to a picsum fallback.
  document.addEventListener('error', function (e) {
    var el = e.target;
    if (!el || el.tagName !== 'IMG') return;
    if (!el.matches('.sprod-card__img, .shop-thumb-sm__img')) return;
    if (el.dataset.shopFallbackApplied === '1') return;
    el.dataset.shopFallbackApplied = '1';
    var w = el.getAttribute('width') || 480;
    var h = el.getAttribute('height') || 360;
    el.src = 'https://picsum.photos/seed/shop-fallback-' + (el.alt || 'x').replace(/\s+/g, '-') + '/' + w + '/' + h;
  }, true);

  /* ---------------- Toast helper ---------------- */
  function ensureToastRegion() {
    var r = document.getElementById('shopToastRegion');
    if (r) return r;
    r = document.createElement('div');
    r.id = 'shopToastRegion';
    r.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    r.style.zIndex = 1090;
    document.body.appendChild(r);
    return r;
  }
  function orchidToast(msg, variant) {
    variant = variant || 'primary';
    var r = ensureToastRegion();
    var wrap = document.createElement('div');
    wrap.className = 'toast align-items-center text-bg-' + variant + ' border-0';
    wrap.setAttribute('role', 'status');
    wrap.setAttribute('aria-live', 'polite');
    wrap.setAttribute('aria-atomic', 'true');
    wrap.innerHTML =
      '<div class="d-flex">' +
      '<div class="toast-body">' + msg + '</div>' +
      '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
      '</div>';
    r.appendChild(wrap);
    if (window.bootstrap && bootstrap.Toast) {
      var t = new bootstrap.Toast(wrap, { delay: 2400 });
      t.show();
      wrap.addEventListener('hidden.bs.toast', function () { wrap.remove(); });
    } else {
      setTimeout(function () { wrap.remove(); }, 2600);
    }
  }

  /* ---------------- Formatters ---------------- */
  function fmtMoney(n, code) {
    code = code || 'USD';
    if (n == null || isNaN(n)) return '—';
    try {
      return new Intl.NumberFormat('en-US', {
        style: 'currency', currency: code, maximumFractionDigits: 2
      }).format(n);
    } catch (e) { return '$' + Number(n).toFixed(2); }
  }
  function fmtDate(iso, mode) {
    if (!iso) return '—';
    var d = new Date(iso);
    if (isNaN(d)) return '—';
    if (mode === 'short') {
      return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
    if (mode === 'long') {
      return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
  }
  function renderStars(rating, size) {
    var full = Math.floor(rating);
    var half = (rating - full) >= 0.5;
    var out = '<span class="shop-stars' + (size === 'lg' ? ' shop-stars--lg' : '') + '" aria-label="Rating ' + rating + ' out of 5">';
    for (var i = 0; i < 5; i++) {
      var cls;
      if (i < full) cls = 'bi-star-fill';
      else if (i === full && half) cls = 'bi-star-half';
      else cls = 'bi-star dim';
      out += '<i class="bi ' + cls + '" aria-hidden="true"></i>';
    }
    out += '</span>';
    return out;
  }
  var FLAGS = {
    US: '🇺🇸', CA: '🇨🇦', GB: '🇬🇧', UK: '🇬🇧', DE: '🇩🇪', FR: '🇫🇷',
    IT: '🇮🇹', ES: '🇪🇸', NL: '🇳🇱', BE: '🇧🇪', SE: '🇸🇪', NO: '🇳🇴',
    IE: '🇮🇪', AT: '🇦🇹', PL: '🇵🇱', PT: '🇵🇹', FI: '🇫🇮', DK: '🇩🇰',
    AU: '🇦🇺', NZ: '🇳🇿', JP: '🇯🇵', KR: '🇰🇷', CN: '🇨🇳', IN: '🇮🇳',
    SG: '🇸🇬', HK: '🇭🇰', MY: '🇲🇾', TH: '🇹🇭', ID: '🇮🇩', PH: '🇵🇭',
    BR: '🇧🇷', MX: '🇲🇽', AR: '🇦🇷', CL: '🇨🇱', CO: '🇨🇴', PE: '🇵🇪',
    ZA: '🇿🇦', AE: '🇦🇪', SA: '🇸🇦', IL: '🇮🇱', TR: '🇹🇷', RU: '🇷🇺',
    CH: '🇨🇭', EG: '🇪🇬', NG: '🇳🇬', WORLD: '🌐'
  };
  function renderFlag(iso) {
    if (!iso) return '';
    var k = String(iso).toUpperCase();
    return FLAGS[k] || FLAGS.WORLD;
  }

  /* ---------------- Shared datasets ---------------- */
  var PRODUCTS = [
    { sku: 'HAL-EB-204', name: 'Halo Wireless Earbuds', brand: 'Halo', cat: 'Headphones', price: 129.00, was: 179.00, rating: 4.8, reviews: 842, stock: 132, thumb: 1, icon: 'bi-earbuds', ribbon: 'best', tags: ['audio','new'] },
    { sku: 'NOM-WB-118', name: 'Nomad Weekender Bag', brand: 'Nomad', cat: 'Accessories', price: 89.00, was: null, rating: 4.6, reviews: 318, stock: 54, thumb: 2, icon: 'bi-bag', ribbon: null, tags: ['outdoor'] },
    { sku: 'AUR-SB-04', name: 'Aurora Smart Bulb 4-Pack', brand: 'Aurora', cat: 'Smart Home', price: 39.00, was: 59.00, rating: 4.4, reviews: 612, stock: 208, thumb: 3, icon: 'bi-lightbulb', ribbon: 'sale', tags: ['smart','sale'] },
    { sku: 'MRD-CK-08', name: "Meridian Chef's Knife", brand: 'Meridian', cat: 'Kitchen', price: 89.00, was: null, rating: 4.9, reviews: 204, stock: 42, thumb: 4, icon: 'bi-scissors', ribbon: 'best', tags: ['kitchen','pro'] },
    { sku: 'EMB-TM-12', name: 'Ember Travel Mug 12oz', brand: 'Ember', cat: 'Kitchen', price: 39.00, was: null, rating: 4.5, reviews: 488, stock: 176, thumb: 5, icon: 'bi-cup-hot', ribbon: null, tags: ['kitchen'] },
    { sku: 'TER-YM-03', name: 'Terra Yoga Mat', brand: 'Terra', cat: 'Fitness', price: 49.00, was: 65.00, rating: 4.3, reviews: 291, stock: 88, thumb: 6, icon: 'bi-heart-pulse', ribbon: 'sale', tags: ['fitness','sale'] },
    { sku: 'VER-BP-15', name: 'Vertex Trail Backpack 30L', brand: 'Vertex', cat: 'Accessories', price: 119.00, was: null, rating: 4.7, reviews: 156, stock: 34, thumb: 7, icon: 'bi-backpack', ribbon: null, tags: ['outdoor','pro'] },
    { sku: 'NOV-CAM-42', name: 'Nova 4K Action Camera', brand: 'Nova', cat: 'Cameras', price: 249.00, was: 299.00, rating: 4.6, reviews: 402, stock: 18, thumb: 8, icon: 'bi-camera-video', ribbon: 'sale', tags: ['sale','pro'] },
    { sku: 'HAL-HP-101', name: 'Halo Studio Headphones', brand: 'Halo', cat: 'Headphones', price: 199.00, was: null, rating: 4.8, reviews: 621, stock: 96, thumb: 1, icon: 'bi-headphones', ribbon: 'best', tags: ['audio','premium'] },
    { sku: 'MRD-CB-11', name: 'Meridian Cutting Board Set', brand: 'Meridian', cat: 'Kitchen', price: 59.00, was: null, rating: 4.4, reviews: 118, stock: 66, thumb: 4, icon: 'bi-square', ribbon: null, tags: ['kitchen'] },
    { sku: 'AUR-TH-22', name: 'Aurora Smart Thermostat', brand: 'Aurora', cat: 'Smart Home', price: 189.00, was: null, rating: 4.5, reviews: 245, stock: 44, thumb: 3, icon: 'bi-thermometer-half', ribbon: 'new', tags: ['smart','new'] },
    { sku: 'NOM-DFB-07', name: 'Nomad Duffel Bag 40L', brand: 'Nomad', cat: 'Accessories', price: 109.00, was: null, rating: 4.6, reviews: 173, stock: 52, thumb: 2, icon: 'bi-bag-fill', ribbon: null, tags: ['outdoor'] },
    { sku: 'EMB-KT-18', name: 'Ember Smart Kettle', brand: 'Ember', cat: 'Kitchen', price: 149.00, was: 179.00, rating: 4.3, reviews: 202, stock: 27, thumb: 5, icon: 'bi-cup-straw', ribbon: 'sale', tags: ['kitchen','sale','smart'] },
    { sku: 'TER-BR-05', name: 'Terra Foam Roller', brand: 'Terra', cat: 'Fitness', price: 29.00, was: null, rating: 4.2, reviews: 148, stock: 210, thumb: 6, icon: 'bi-record-circle', ribbon: null, tags: ['fitness'] },
    { sku: 'NOV-LN-08', name: 'Nova 50mm Prime Lens', brand: 'Nova', cat: 'Cameras', price: 449.00, was: null, rating: 4.9, reviews: 88, stock: 12, thumb: 8, icon: 'bi-aperture', ribbon: 'best', tags: ['pro','premium'] },
    { sku: 'VER-TN-09', name: 'Vertex Trekking Poles', brand: 'Vertex', cat: 'Sports', price: 79.00, was: null, rating: 4.5, reviews: 96, stock: 41, thumb: 7, icon: 'bi-flag', ribbon: null, tags: ['outdoor'] },
    { sku: 'HAL-SP-15', name: 'Halo Portable Speaker', brand: 'Halo', cat: 'Audio', price: 89.00, was: 119.00, rating: 4.4, reviews: 356, stock: 74, thumb: 1, icon: 'bi-boombox', ribbon: 'sale', tags: ['audio','sale'] },
    { sku: 'AUR-CAM-20', name: 'Aurora Security Cam', brand: 'Aurora', cat: 'Smart Home', price: 129.00, was: null, rating: 4.3, reviews: 187, stock: 63, thumb: 3, icon: 'bi-camera', ribbon: null, tags: ['smart'] },
    { sku: 'MRD-PN-30', name: 'Meridian Cast Iron Pan', brand: 'Meridian', cat: 'Kitchen', price: 69.00, was: null, rating: 4.7, reviews: 244, stock: 58, thumb: 4, icon: 'bi-circle', ribbon: null, tags: ['kitchen','pro'] },
    { sku: 'EMB-CF-42', name: 'Ember Coffee Grinder', brand: 'Ember', cat: 'Kitchen', price: 119.00, was: null, rating: 4.6, reviews: 129, stock: 39, thumb: 5, icon: 'bi-cup', ribbon: 'new', tags: ['kitchen','new'] },
    { sku: 'NOM-WLT-04', name: 'Nomad Slim Wallet', brand: 'Nomad', cat: 'Accessories', price: 49.00, was: null, rating: 4.5, reviews: 88, stock: 152, thumb: 2, icon: 'bi-wallet', ribbon: null, tags: [] },
    { sku: 'VER-HB-33', name: 'Vertex Hydration Bottle', brand: 'Vertex', cat: 'Fitness', price: 29.00, was: null, rating: 4.4, reviews: 402, stock: 288, thumb: 7, icon: 'bi-droplet', ribbon: null, tags: ['fitness','outdoor'] }
  ];

  var CUSTOMERS = [
    { id: 'C-1001', name: 'Emma Watson',  email: 'emma@nova.io',      country: 'GB', ltv: 4820,  orders: 24, joined: '2024-05-12', last: '2026-07-19', tier: 'gold',     avatar: 1 },
    { id: 'C-1002', name: 'James Doe',    email: 'james@acme.com',    country: 'US', ltv: 12410, orders: 47, joined: '2023-11-04', last: '2026-07-21', tier: 'platinum', avatar: 2 },
    { id: 'C-1003', name: 'Sarah Miller', email: 'sarah@zen.co',      country: 'AU', ltv: 7920,  orders: 32, joined: '2024-02-18', last: '2026-07-20', tier: 'gold',     avatar: 3 },
    { id: 'C-1004', name: 'Ryan Green',   email: 'ryan@peak.io',      country: 'CA', ltv: 1150,  orders: 4,  joined: '2026-01-08', last: '2026-06-01', tier: 'bronze',   avatar: 4 },
    { id: 'C-1005', name: 'Ava Lee',      email: 'ava@bold.co',       country: 'DE', ltv: 3640,  orders: 18, joined: '2025-06-30', last: '2026-07-15', tier: 'silver',   avatar: 5 },
    { id: 'C-1006', name: 'Noah Park',    email: 'noah@peak.io',      country: 'KR', ltv: 9240,  orders: 38, joined: '2024-03-14', last: '2026-07-22', tier: 'platinum', avatar: 6 },
    { id: 'C-1007', name: 'Mia Chen',     email: 'mia@sparklab.io',   country: 'SG', ltv: 5620,  orders: 26, joined: '2024-09-01', last: '2026-07-18', tier: 'gold',     avatar: 1 },
    { id: 'C-1008', name: 'Lucas Rossi',  email: 'lucas@nordic.io',   country: 'IT', ltv: 2180,  orders: 11, joined: '2025-11-22', last: '2026-07-10', tier: 'silver',   avatar: 2 },
    { id: 'C-1009', name: 'Sofia Rivas',  email: 'sofia@estudio.mx',  country: 'MX', ltv: 830,   orders: 3,  joined: '2026-04-11', last: '2026-06-25', tier: 'bronze',   avatar: 3 },
    { id: 'C-1010', name: 'Ethan Brooks', email: 'ethan@midlab.co',   country: 'US', ltv: 15870, orders: 62, joined: '2023-08-08', last: '2026-07-21', tier: 'platinum', avatar: 4 },
    { id: 'C-1011', name: 'Olivia Grant', email: 'olivia@urban.io',   country: 'CA', ltv: 4210,  orders: 21, joined: '2024-10-19', last: '2026-07-16', tier: 'gold',     avatar: 5 },
    { id: 'C-1012', name: 'Kenji Tanaka', email: 'kenji@sakura.jp',   country: 'JP', ltv: 6880,  orders: 29, joined: '2024-06-05', last: '2026-07-20', tier: 'gold',     avatar: 6 }
  ];

  window.OrchidShop = {
    toast: orchidToast,
    fmtMoney: fmtMoney,
    fmtDate: fmtDate,
    renderStars: renderStars,
    renderFlag: renderFlag,
    PRODUCTS: PRODUCTS,
    CUSTOMERS: CUSTOMERS
  };

  /* ---------------- Auto init on DOM ready ---------------- */
  function onReady(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  onReady(function () {
    var page = document.body.getAttribute('data-shop-page');
    switch (page) {
      case 'products':  initProducts(); break;
      case 'orders':    initOrders(); break;
      case 'customers': initCustomers(); break;
      case 'coupons':   initCoupons(); break;
      case 'reviews':   initReviews(); break;
      case 'shipping':  initShipping(); break;
      case 'taxes':     initTaxes(); break;
    }
  });

  /* =====================================================
     PRODUCTS
     ===================================================== */
  function initProducts() {
    var grid = document.getElementById('sprodGrid');
    if (!grid) return;
    var searchInput = document.querySelector('[data-sprod-search]');
    var sortSel = document.querySelector('[data-sprod-sort]');
    var chips = document.querySelectorAll('[data-sprod-cat]');
    var priceMin = document.querySelector('[data-sprod-price-min]');
    var priceMax = document.querySelector('[data-sprod-price-max]');
    var brandInputs = document.querySelectorAll('[data-sprod-filter-brand]');
    var ratingInputs = document.querySelectorAll('[data-sprod-filter-rating]');
    var stockOnly = document.querySelector('[data-sprod-stock-only]');
    var countEl = document.querySelector('[data-sprod-count]');

    var state = {
      q: '',
      cat: 'All',
      sort: 'featured',
      min: 0, max: 500,
      brands: [],
      rating: 0,
      inStockOnly: false
    };

    function apply() {
      var list = PRODUCTS.slice();
      if (state.q) {
        var q = state.q.toLowerCase();
        list = list.filter(function (p) { return p.name.toLowerCase().indexOf(q) > -1 || p.brand.toLowerCase().indexOf(q) > -1 || p.sku.toLowerCase().indexOf(q) > -1; });
      }
      if (state.cat && state.cat !== 'All') {
        list = list.filter(function (p) { return p.cat === state.cat; });
      }
      list = list.filter(function (p) { return p.price >= state.min && p.price <= state.max; });
      if (state.brands.length) {
        list = list.filter(function (p) { return state.brands.indexOf(p.brand) > -1; });
      }
      if (state.rating > 0) {
        list = list.filter(function (p) { return p.rating >= state.rating; });
      }
      if (state.inStockOnly) {
        list = list.filter(function (p) { return p.stock > 0; });
      }
      switch (state.sort) {
        case 'price-asc':  list.sort(function (a, b) { return a.price - b.price; }); break;
        case 'price-desc': list.sort(function (a, b) { return b.price - a.price; }); break;
        case 'newest':     list.sort(function (a, b) { return (b.tags.indexOf('new') > -1) - (a.tags.indexOf('new') > -1); }); break;
        case 'best':       list.sort(function (a, b) { return b.reviews - a.reviews; }); break;
      }
      render(list);
    }

    function render(list) {
      if (countEl) countEl.textContent = list.length + ' product' + (list.length === 1 ? '' : 's');
      if (!list.length) {
        grid.innerHTML = '<div class="col-12"><div class="shop-empty"><div class="shop-empty__icon"><i class="bi bi-search"></i></div><h5 class="mb-1">No products match your filters</h5><p class="mb-0 small">Try clearing filters or broadening your search.</p></div></div>';
        return;
      }
      grid.innerHTML = list.map(function (p) {
        var ribbon = '';
        if (p.ribbon === 'best') ribbon = '<span class="sprod-ribbon">Best Seller</span>';
        else if (p.ribbon === 'sale') ribbon = '<span class="sprod-ribbon sprod-ribbon--sale">Sale</span>';
        else if (p.ribbon === 'new') ribbon = '<span class="sprod-ribbon sprod-ribbon--new">New</span>';
        var stockChip = '';
        if (p.stock === 0) stockChip = '<span class="shop-chip shop-chip--red sprod-card__stock">Out of stock</span>';
        else if (p.stock < 20) stockChip = '<span class="shop-chip shop-chip--amber sprod-card__stock">Only ' + p.stock + ' left</span>';
        else stockChip = '<span class="shop-chip shop-chip--emerald sprod-card__stock">In stock</span>';
        var price = '<span class="sprod-card__price-current">' + fmtMoney(p.price) + '</span>' + (p.was ? '<span class="sprod-card__price-was">' + fmtMoney(p.was) + '</span>' : '');
        var imgUrl = productImage(p, 480, 360);
        return '<div class="col-12 col-sm-6 col-lg-4 col-xxl-3">' +
          '<article class="sprod-card">' +
            '<div class="sprod-card__thumb sprod-card__thumb--' + p.thumb + '">' + ribbon +
              '<img class="sprod-card__img" src="' + imgUrl + '" alt="' + p.name + '" loading="lazy" width="480" height="360">' +
              stockChip +
            '</div>' +
            '<div class="sprod-card__body">' +
              '<p class="sprod-card__cat">' + p.cat + ' · ' + p.brand + '</p>' +
              '<h6 class="sprod-card__name">' + p.name + '</h6>' +
              '<div class="sprod-card__rating">' + renderStars(p.rating) + '<span>' + p.rating.toFixed(1) + ' (' + p.reviews + ')</span></div>' +
              '<div class="sprod-card__price">' + price + '</div>' +
              '<div class="sprod-card__actions">' +
                '<button type="button" class="btn btn-outline-secondary btn-sm" data-sprod-quick="' + p.sku + '"><i class="bi bi-eye me-1"></i>Quick view</button>' +
                '<button type="button" class="btn btn-primary btn-sm" data-sprod-add="' + p.sku + '"><i class="bi bi-cart-plus me-1"></i>Add to cart</button>' +
              '</div>' +
            '</div>' +
          '</article>' +
        '</div>';
      }).join('');
    }

    if (searchInput) searchInput.addEventListener('input', function (e) { state.q = e.target.value; apply(); });
    if (sortSel) sortSel.addEventListener('change', function (e) { state.sort = e.target.value; apply(); });
    chips.forEach(function (c) {
      c.addEventListener('click', function () {
        chips.forEach(function (o) { o.classList.remove('is-active'); });
        c.classList.add('is-active');
        state.cat = c.getAttribute('data-sprod-cat');
        apply();
      });
    });
    if (priceMin) priceMin.addEventListener('input', function (e) { state.min = +e.target.value || 0; apply(); });
    if (priceMax) priceMax.addEventListener('input', function (e) { state.max = +e.target.value || 5000; apply(); });
    brandInputs.forEach(function (i) {
      i.addEventListener('change', function () {
        state.brands = [].filter.call(brandInputs, function (x) { return x.checked; }).map(function (x) { return x.value; });
        apply();
      });
    });
    ratingInputs.forEach(function (i) {
      i.addEventListener('change', function () { state.rating = +i.value; apply(); });
    });
    if (stockOnly) stockOnly.addEventListener('change', function (e) { state.inStockOnly = e.target.checked; apply(); });

    var clearBtn = document.querySelector('[data-sprod-clear]');
    if (clearBtn) clearBtn.addEventListener('click', function () {
      state = { q: '', cat: 'All', sort: 'featured', min: 0, max: 500, brands: [], rating: 0, inStockOnly: false };
      if (searchInput) searchInput.value = '';
      if (priceMin) priceMin.value = 0;
      if (priceMax) priceMax.value = 500;
      brandInputs.forEach(function (b) { b.checked = false; });
      ratingInputs.forEach(function (r, idx) { r.checked = idx === 0; });
      if (stockOnly) stockOnly.checked = false;
      chips.forEach(function (o) { o.classList.remove('is-active'); });
      if (chips[0]) chips[0].classList.add('is-active');
      apply();
      orchidToast('Filters cleared', 'secondary');
    });

    grid.addEventListener('click', function (e) {
      var add = e.target.closest('[data-sprod-add]');
      if (add) {
        var sku = add.getAttribute('data-sprod-add');
        var p = PRODUCTS.find(function (x) { return x.sku === sku; });
        orchidToast('Added ' + (p ? p.name : sku) + ' to cart', 'success');
        return;
      }
      var qv = e.target.closest('[data-sprod-quick]');
      if (qv) {
        var sku2 = qv.getAttribute('data-sprod-quick');
        var p2 = PRODUCTS.find(function (x) { return x.sku === sku2; });
        var body = document.getElementById('sprodQuickBody');
        if (p2 && body) {
          body.innerHTML =
            '<div class="row g-3">' +
              '<div class="col-md-5"><div class="sprod-card__thumb sprod-card__thumb--' + p2.thumb + ' sprod-quick-thumb"><i class="bi ' + p2.icon + '"></i></div></div>' +
              '<div class="col-md-7">' +
                '<p class="sprod-card__cat">' + p2.cat + ' · ' + p2.brand + '</p>' +
                '<h4>' + p2.name + '</h4>' +
                '<div class="mb-2">' + renderStars(p2.rating, 'lg') + ' <span class="text-body-secondary small">' + p2.rating + ' (' + p2.reviews + ' reviews)</span></div>' +
                '<div class="sprod-card__price mb-3"><span class="sprod-card__price-current sprod-quick-price">' + fmtMoney(p2.price) + '</span>' + (p2.was ? '<span class="sprod-card__price-was">' + fmtMoney(p2.was) + '</span>' : '') + '</div>' +
                '<p class="text-body-secondary small">SKU: ' + p2.sku + ' · Stock: ' + p2.stock + '</p>' +
                '<button type="button" class="btn btn-primary" data-sprod-add="' + p2.sku + '"><i class="bi bi-cart-plus me-1"></i>Add to cart</button>' +
              '</div>' +
            '</div>';
        }
        if (window.bootstrap) { var m = new bootstrap.Modal(document.getElementById('sprodQuickModal')); m.show(); }
      }
    });

    if (chips[0]) chips[0].classList.add('is-active');
    apply();
  }

  /* =====================================================
     ORDERS
     ===================================================== */
  function initOrders() {
    var wrap = document.getElementById('sordList');
    if (!wrap) return;

    var ORDERS = [
      { num: 'SO-10842', cust: 'Emma Watson',  ai: 1, status: 'delivered', total: 249.00, date: '2026-07-22', items: [1,3,5], count: 3, pay: 'card' },
      { num: 'SO-10841', cust: 'James Doe',    ai: 2, status: 'shipped',   total: 519.00, date: '2026-07-22', items: [2,4,7,8], count: 4, pay: 'paypal' },
      { num: 'SO-10840', cust: 'Sarah Miller', ai: 3, status: 'paid',      total: 129.00, date: '2026-07-21', items: [1], count: 1, pay: 'card' },
      { num: 'SO-10839', cust: 'Ryan Green',   ai: 4, status: 'pending',   total: 89.00,  date: '2026-07-21', items: [6], count: 1, pay: 'card' },
      { num: 'SO-10838', cust: 'Ava Lee',      ai: 5, status: 'refunded',  total: 199.00, date: '2026-07-20', items: [3,5], count: 2, pay: 'card' },
      { num: 'SO-10837', cust: 'Noah Park',    ai: 6, status: 'delivered', total: 649.00, date: '2026-07-20', items: [4,7,8,1,2], count: 5, pay: 'apple' },
      { num: 'SO-10836', cust: 'Mia Chen',     ai: 1, status: 'shipped',   total: 89.00,  date: '2026-07-19', items: [2], count: 1, pay: 'card' },
      { num: 'SO-10835', cust: 'Lucas Rossi',  ai: 2, status: 'cancelled', total: 39.00,  date: '2026-07-19', items: [3], count: 1, pay: 'card' },
      { num: 'SO-10834', cust: 'Ethan Brooks', ai: 4, status: 'paid',      total: 349.00, date: '2026-07-18', items: [1,6,8], count: 3, pay: 'card' },
      { num: 'SO-10833', cust: 'Olivia Grant', ai: 5, status: 'delivered', total: 149.00, date: '2026-07-17', items: [5,7], count: 2, pay: 'paypal' },
      { num: 'SO-10832', cust: 'Kenji Tanaka', ai: 6, status: 'shipped',   total: 449.00, date: '2026-07-16', items: [8], count: 1, pay: 'card' },
      { num: 'SO-10831', cust: 'Sofia Rivas',  ai: 3, status: 'pending',   total: 29.00,  date: '2026-07-15', items: [6], count: 1, pay: 'card' }
    ];

    var STATUS = {
      pending:   { chip: 'shop-chip--amber',  ico: 'bi-clock', label: 'Pending' },
      paid:      { chip: 'shop-chip--emerald',ico: 'bi-currency-dollar', label: 'Paid' },
      shipped:   { chip: 'shop-chip--cyan',   ico: 'bi-truck', label: 'Shipped' },
      delivered: { chip: 'shop-chip--emerald',ico: 'bi-check2-circle', label: 'Delivered' },
      refunded:  { chip: 'shop-chip--red',    ico: 'bi-arrow-return-left', label: 'Refunded' },
      cancelled: { chip: 'shop-chip--slate',  ico: 'bi-x-circle', label: 'Cancelled' }
    };

    var searchInput = document.querySelector('[data-sord-search]');
    var statusSel = document.querySelector('[data-sord-status]');
    var paySel = document.querySelector('[data-sord-pay]');
    var dateFrom = document.querySelector('[data-sord-from]');
    var dateTo = document.querySelector('[data-sord-to]');

    var state = { q: '', status: 'all', pay: 'all', from: '', to: '' };

    function apply() {
      var list = ORDERS.filter(function (o) {
        if (state.q && (o.num + ' ' + o.cust).toLowerCase().indexOf(state.q.toLowerCase()) === -1) return false;
        if (state.status !== 'all' && o.status !== state.status) return false;
        if (state.pay !== 'all' && o.pay !== state.pay) return false;
        if (state.from && o.date < state.from) return false;
        if (state.to && o.date > state.to) return false;
        return true;
      });
      render(list);
      updateSummary(list);
    }

    function render(list) {
      if (!list.length) {
        wrap.innerHTML = '<div class="shop-empty"><div class="shop-empty__icon"><i class="bi bi-inbox"></i></div><h5 class="mb-1">No orders match</h5><p class="mb-0 small">Try adjusting your filters.</p></div>';
        return;
      }
      wrap.innerHTML = list.map(function (o) {
        var s = STATUS[o.status];
        var thumbs = o.items.slice(0, 4).map(function (t) {
          var ci = ((t - 1) % 8) + 1;
          return '<span class="sord-thumb shop-cbg-' + ci + '"><i class="bi bi-box"></i></span>';
        }).join('');
        if (o.count > 4) thumbs += '<span class="sord-thumb shop-cbg-slate">+' + (o.count - 4) + '</span>';
        var initials = o.cust.split(' ').map(function (w) { return w[0]; }).join('').slice(0,2);
        return '<article class="sord-card" data-sord-num="' + o.num + '">' +
          '<div class="sord-card__icon sord-card__icon--' + o.status + '"><i class="bi ' + s.ico + '"></i></div>' +
          '<div>' +
            '<div class="sord-card__head">' +
              '<span class="sord-card__num">' + o.num + '</span>' +
              '<span class="shop-chip ' + s.chip + '"><i class="bi ' + s.ico + '"></i>' + s.label + '</span>' +
            '</div>' +
            '<div class="sord-card__meta">' +
              '<span><span class="avatar avatar-xs bg-primary-subtle text-primary shop-avatar-xxs">' + initials + '</span> ' + o.cust + '</span>' +
              '<span>' + fmtDate(o.date, 'short') + '</span>' +
              '<span class="d-flex align-items-center gap-1"><i class="bi bi-box"></i>' + o.count + ' item' + (o.count === 1 ? '' : 's') + '</span>' +
              '<span class="sord-card__thumbs">' + thumbs + '</span>' +
            '</div>' +
          '</div>' +
          '<div class="sord-card__right">' +
            '<span class="sord-card__total">' + fmtMoney(o.total) + '</span>' +
            '<div class="btn-group btn-group-sm">' +
              '<button type="button" class="btn btn-outline-secondary" data-sord-view="' + o.num + '"><i class="bi bi-eye"></i></button>' +
              '<button type="button" class="btn btn-outline-secondary" data-sord-track="' + o.num + '"><i class="bi bi-geo-alt"></i></button>' +
              '<button type="button" class="btn btn-outline-secondary" data-sord-refund="' + o.num + '"><i class="bi bi-arrow-return-left"></i></button>' +
            '</div>' +
          '</div>' +
        '</article>';
      }).join('');
    }

    function updateSummary(list) {
      var totalRev = list.reduce(function (a, o) { return a + o.total; }, 0);
      var aov = list.length ? totalRev / list.length : 0;
      var elO = document.getElementById('sordSumOrders');
      var elR = document.getElementById('sordSumRev');
      var elA = document.getElementById('sordSumAov');
      if (elO) elO.textContent = list.length;
      if (elR) elR.textContent = fmtMoney(totalRev);
      if (elA) elA.textContent = fmtMoney(aov);
      // distribution
      var counts = { pending:0, paid:0, shipped:0, delivered:0, refunded:0, cancelled:0 };
      list.forEach(function (o) { counts[o.status]++; });
      Object.keys(counts).forEach(function (k) {
        var b = document.querySelector('[data-sord-dist="' + k + '"]');
        if (b) b.textContent = counts[k];
      });
    }

    if (searchInput) searchInput.addEventListener('input', function (e) { state.q = e.target.value; apply(); });
    if (statusSel) statusSel.addEventListener('change', function (e) { state.status = e.target.value; apply(); });
    if (paySel) paySel.addEventListener('change', function (e) { state.pay = e.target.value; apply(); });
    if (dateFrom) dateFrom.addEventListener('change', function (e) { state.from = e.target.value; apply(); });
    if (dateTo) dateTo.addEventListener('change', function (e) { state.to = e.target.value; apply(); });

    wrap.addEventListener('click', function (e) {
      var view = e.target.closest('[data-sord-view]');
      if (view) {
        var num = view.getAttribute('data-sord-view');
        var order = ORDERS.find(function (x) { return x.num === num; });
        var body = document.getElementById('sordDrawerBody');
        var title = document.getElementById('sordDrawerTitle');
        if (order && body && title) {
          title.textContent = 'Order ' + order.num;
          body.innerHTML =
            '<div class="mb-3"><span class="shop-chip ' + STATUS[order.status].chip + '"><i class="bi ' + STATUS[order.status].ico + '"></i>' + STATUS[order.status].label + '</span></div>' +
            '<h6>Customer</h6><p>' + order.cust + '</p>' +
            '<h6 class="mt-3">Items</h6>' +
            '<ul class="list-unstyled">' +
              order.items.map(function (i) {
                var p = PRODUCTS[i - 1] || PRODUCTS[0];
                return '<li class="d-flex align-items-center gap-2 py-2 border-bottom"><span class="sord-thumb sord-thumb--inline shop-cbg-1"><i class="bi bi-box"></i></span>' + p.name + '</li>';
              }).join('') +
            '</ul>' +
            '<h6 class="mt-3">Timeline</h6>' +
            '<ul class="orchid-timeline list-unstyled mb-0">' +
              '<li class="orchid-timeline__item"><span class="orchid-timeline__dot bg-primary"></span><div><p class="mb-0"><strong>Order placed</strong></p><small class="text-body-secondary">' + fmtDate(order.date) + '</small></div></li>' +
              '<li class="orchid-timeline__item"><span class="orchid-timeline__dot bg-success"></span><div><p class="mb-0"><strong>Payment confirmed</strong></p><small class="text-body-secondary">2h later</small></div></li>' +
              (['shipped','delivered'].indexOf(order.status) > -1 ? '<li class="orchid-timeline__item"><span class="orchid-timeline__dot bg-info"></span><div><p class="mb-0"><strong>Shipped</strong></p><small class="text-body-secondary">Next day</small></div></li>' : '') +
              (order.status === 'delivered' ? '<li class="orchid-timeline__item"><span class="orchid-timeline__dot bg-success"></span><div><p class="mb-0"><strong>Delivered</strong></p><small class="text-body-secondary">3 days later</small></div></li>' : '') +
            '</ul>' +
            '<h6 class="mt-3">Shipping</h6><p class="text-body-secondary small mb-0">1234 Market St, San Francisco, CA 94103, US</p>' +
            '<h6 class="mt-3">Total</h6><p class="fs-4 fw-bold mb-0">' + fmtMoney(order.total) + '</p>';
        }
        if (window.bootstrap) { var oc = new bootstrap.Offcanvas(document.getElementById('sordDrawer')); oc.show(); }
        return;
      }
      var tr = e.target.closest('[data-sord-track]');
      if (tr) { orchidToast('Tracking info sent to customer email', 'primary'); return; }
      var rf = e.target.closest('[data-sord-refund]');
      if (rf) { orchidToast('Refund initiated for ' + rf.getAttribute('data-sord-refund'), 'warning'); return; }
    });

    apply();

    // Activity feed
    var feed = document.getElementById('sordFeed');
    if (feed) {
      var acts = [
        { i: 'bi-check2-circle', c: 'bg-success', t: 'Order SO-10842 delivered', s: '5 min ago' },
        { i: 'bi-truck', c: 'bg-info', t: 'SO-10841 shipped via FedEx', s: '22 min ago' },
        { i: 'bi-currency-dollar', c: 'bg-primary', t: 'Payment received on SO-10840', s: '1 hour ago' },
        { i: 'bi-arrow-return-left', c: 'bg-danger', t: 'Refund issued for SO-10838', s: '3 hours ago' },
        { i: 'bi-bag-plus', c: 'bg-warning', t: 'New order SO-10839 placed', s: 'Yesterday' }
      ];
      feed.innerHTML = acts.map(function (a) {
        return '<li class="orchid-timeline__item"><span class="orchid-timeline__dot ' + a.c + '"></span><div><p class="mb-0">' + a.t + '</p><small class="text-body-secondary">' + a.s + '</small></div></li>';
      }).join('');
    }

    // Donut chart
    var canvas = document.getElementById('sordDonut');
    if (canvas && window.Chart) {
      var counts = { pending:0, paid:0, shipped:0, delivered:0, refunded:0, cancelled:0 };
      ORDERS.forEach(function (o) { counts[o.status]++; });
      new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
          labels: Object.keys(counts),
          datasets: [{ data: Object.values(counts),
            backgroundColor: ['#f59e0b','#10b981','#06b6d4','#22c55e','#ef4444','#64748b'],
            borderWidth: 0 }]
        },
        options: { plugins: { legend: { display: false } }, cutout: '70%', responsive: true, maintainAspectRatio: false }
      });
    }
  }

  /* =====================================================
     CUSTOMERS
     ===================================================== */
  function initCustomers() {
    var grid = document.getElementById('scustGrid');
    if (!grid) return;
    var searchInput = document.querySelector('[data-scust-search]');
    var tierSel = document.querySelector('[data-scust-tier]');
    var sortSel = document.querySelector('[data-scust-sort]');
    var minOrders = document.querySelector('[data-scust-orders-min]');

    var state = { q: '', tier: 'all', sort: 'ltv-desc', minOrders: 0 };

    function apply() {
      var list = CUSTOMERS.slice();
      if (state.q) list = list.filter(function (c) { return (c.name + ' ' + c.email).toLowerCase().indexOf(state.q.toLowerCase()) > -1; });
      if (state.tier !== 'all') list = list.filter(function (c) { return c.tier === state.tier; });
      if (state.minOrders > 0) list = list.filter(function (c) { return c.orders >= state.minOrders; });
      switch (state.sort) {
        case 'ltv-desc': list.sort(function (a, b) { return b.ltv - a.ltv; }); break;
        case 'ltv-asc':  list.sort(function (a, b) { return a.ltv - b.ltv; }); break;
        case 'orders':   list.sort(function (a, b) { return b.orders - a.orders; }); break;
        case 'recent':   list.sort(function (a, b) { return (b.last > a.last ? 1 : -1); }); break;
      }
      render(list);
    }

    function render(list) {
      var count = document.getElementById('scustCount');
      if (count) count.textContent = list.length + ' customer' + (list.length === 1 ? '' : 's');
      if (!list.length) {
        grid.innerHTML = '<div class="col-12"><div class="shop-empty"><div class="shop-empty__icon"><i class="bi bi-people"></i></div><h5 class="mb-1">No customers match</h5></div></div>';
        return;
      }
      grid.innerHTML = list.map(function (c) {
        var initials = c.name.split(' ').map(function (w) { return w[0]; }).join('').slice(0,2);
        return '<div class="col-12 col-sm-6 col-lg-4">' +
          '<article class="scust-card">' +
            '<span class="scust-tier scust-tier--' + c.tier + '">' + c.tier + '</span>' +
            '<div class="scust-card__avatar scust-card__avatar--' + c.avatar + '">' + initials + '</div>' +
            '<h6 class="scust-card__name">' + c.name + ' <span class="ms-1">' + renderFlag(c.country) + '</span></h6>' +
            '<p class="scust-card__email">' + c.email + '</p>' +
            '<p class="scust-card__ltv">' + fmtMoney(c.ltv) + '</p>' +
            '<p class="scust-card__ltv-label">Lifetime value</p>' +
            '<div class="scust-card__stats">' +
              '<div><div class="scust-card__stat-value">' + c.orders + '</div><div class="scust-card__stat-label">Orders</div></div>' +
              '<div><div class="scust-card__stat-value">' + fmtDate(c.last, 'short') + '</div><div class="scust-card__stat-label">Last order</div></div>' +
            '</div>' +
            '<div class="scust-card__actions">' +
              '<button type="button" class="btn btn-outline-secondary btn-sm" data-scust-orders="' + c.id + '"><i class="bi bi-bag"></i></button>' +
              '<button type="button" class="btn btn-outline-secondary btn-sm" data-scust-email="' + c.id + '"><i class="bi bi-envelope"></i></button>' +
              '<button type="button" class="btn btn-outline-secondary btn-sm" data-scust-discount="' + c.id + '"><i class="bi bi-ticket-perforated"></i></button>' +
            '</div>' +
          '</article>' +
        '</div>';
      }).join('');
    }

    if (searchInput) searchInput.addEventListener('input', function (e) { state.q = e.target.value; apply(); });
    if (tierSel) tierSel.addEventListener('change', function (e) { state.tier = e.target.value; apply(); });
    if (sortSel) sortSel.addEventListener('change', function (e) { state.sort = e.target.value; apply(); });
    if (minOrders) minOrders.addEventListener('input', function (e) { state.minOrders = +e.target.value || 0; apply(); });

    grid.addEventListener('click', function (e) {
      var b;
      if ((b = e.target.closest('[data-scust-email]'))) { orchidToast('Email composer opened', 'primary'); }
      else if ((b = e.target.closest('[data-scust-orders]'))) { orchidToast('Opening customer orders', 'primary'); }
      else if ((b = e.target.closest('[data-scust-discount]'))) { orchidToast('Discount created and sent', 'success'); }
    });

    apply();
  }

  /* =====================================================
     COUPONS
     ===================================================== */
  function initCoupons() {
    var tbody = document.getElementById('coupTbody');
    if (!tbody) return;

    var COUPONS = [
      { code: 'SUMMER25', desc: '25% off summer collection', type: 'percent', chip: 'shop-chip--pink', value: '25%', used: 480, limit: 1000, status: 'active',    exp: '2026-08-31' },
      { code: 'FIRSTBUY', desc: 'First purchase discount',   type: 'percent', chip: 'shop-chip--emerald', value: '15%', used: 2340, limit: 5000, status: 'active',   exp: '2027-01-01' },
      { code: 'VIP20',    desc: 'VIP customers only',        type: 'percent', chip: 'shop-chip--violet', value: '20%',  used: 128, limit: 200,  status: 'active',   exp: '2026-12-31' },
      { code: 'FREESHIP', desc: 'Free shipping over $50',    type: 'ship',    chip: 'shop-chip--cyan',   value: 'Ship', used: 890, limit: 2000, status: 'active',   exp: '2026-10-15' },
      { code: 'FLASH50',  desc: '$50 off flash sale',        type: 'fixed',   chip: 'shop-chip--amber',  value: '$50',  used: 62,  limit: 100,  status: 'active',   exp: '2026-07-31' },
      { code: 'BOGO24',   desc: 'Buy one get one free',      type: 'bogo',    chip: 'shop-chip--red',    value: 'BOGO', used: 178, limit: 500,  status: 'active',   exp: '2026-09-30' },
      { code: 'HOLIDAY', desc: 'Holiday special 30% off',    type: 'percent', chip: 'shop-chip--pink',   value: '30%',  used: 0,   limit: 3000, status: 'scheduled','exp': '2026-12-24' },
      { code: 'BLACKFR30',desc: 'Black Friday 30% off all',  type: 'percent', chip: 'shop-chip--pink',   value: '30%',  used: 0,   limit: 5000, status: 'scheduled', exp: '2026-11-28' },
      { code: 'STUDENT',  desc: 'Student discount 10%',      type: 'percent', chip: 'shop-chip--emerald',value: '10%',  used: 1240,limit: null, status: 'active',   exp: '2027-06-30' },
      { code: 'SPRING10', desc: 'Spring sale 10% off',       type: 'percent', chip: 'shop-chip--pink',   value: '10%',  used: 850, limit: 850,  status: 'expired',  exp: '2026-05-31' },
      { code: 'THANKYOU', desc: '$10 off next order',        type: 'fixed',   chip: 'shop-chip--amber',  value: '$10',  used: 342, limit: 1000, status: 'active',   exp: '2026-12-15' },
      { code: 'LOYAL15',  desc: 'Loyalty program members',   type: 'percent', chip: 'shop-chip--violet', value: '15%',  used: 220, limit: 400,  status: 'active',   exp: '2026-10-31' },
      { code: 'BUNDLE',   desc: 'Bundle deal free item',     type: 'bogo',    chip: 'shop-chip--red',    value: 'BOGO', used: 45,  limit: 100,  status: 'active',   exp: '2026-08-15' },
      { code: 'WELCOME5', desc: 'Welcome discount $5',       type: 'fixed',   chip: 'shop-chip--amber',  value: '$5',   used: 4820,limit: null, status: 'active',   exp: '2027-12-31' },
      { code: 'REFER50',  desc: 'Referral credit $50',       type: 'fixed',   chip: 'shop-chip--amber',  value: '$50',  used: 78,  limit: 500,  status: 'active',   exp: '2026-12-31' }
    ];

    function typeLabel(t) { return t === 'percent' ? '% off' : t === 'fixed' ? '$ off' : t === 'ship' ? 'Free ship' : 'BOGO'; }
    function statusChip(s) {
      var m = { active: 'shop-chip--emerald', scheduled: 'shop-chip--cyan', expired: 'shop-chip--slate' };
      return '<span class="shop-chip ' + m[s] + '">' + s.charAt(0).toUpperCase() + s.slice(1) + '</span>';
    }

    function render() {
      tbody.innerHTML = COUPONS.map(function (c, i) {
        var pct = c.limit ? Math.round(c.used / c.limit * 100) : 100;
        return '<tr class="coup-row" data-coup-idx="' + i + '">' +
          '<td><span class="shop-chip shop-chip--mono">' + c.code + '</span></td>' +
          '<td class="d-none d-md-table-cell text-body-secondary small">' + c.desc + '</td>' +
          '<td><span class="shop-chip ' + c.chip + '">' + typeLabel(c.type) + '</span></td>' +
          '<td class="fw-semibold">' + c.value + '</td>' +
          '<td><div class="coup-usage"><div class="coup-usage__bar"><div class="coup-usage__fill" data-fill="' + pct + '"></div></div><span class="coup-usage__text">' + c.used + '/' + (c.limit || '∞') + '</span></div></td>' +
          '<td>' + statusChip(c.status) + '</td>' +
          '<td class="text-body-secondary small">' + fmtDate(c.exp, 'short') + '</td>' +
          '<td class="text-end"><button type="button" class="btn btn-sm btn-icon" data-coup-edit><i class="bi bi-pencil"></i></button></td>' +
        '</tr>';
      }).join('');
      tbody.querySelectorAll('[data-fill]').forEach(function (f) {
        f.style.width = f.getAttribute('data-fill') + '%';
      });
    }

    function selectRow(idx) {
      tbody.querySelectorAll('.coup-row').forEach(function (r) { r.classList.remove('is-selected'); });
      var row = tbody.querySelector('[data-coup-idx="' + idx + '"]');
      if (row) row.classList.add('is-selected');
      var c = COUPONS[idx];
      var pane = document.getElementById('coupDetail');
      if (!c || !pane) return;
      var pct = c.limit ? Math.round(c.used / c.limit * 100) : 100;
      pane.innerHTML =
        '<div class="coup-detail">' +
          '<div class="coup-detail__code">' + c.code + '</div>' +
          '<p class="text-body-secondary">' + c.desc + '</p>' +
          '<div class="row g-3 mb-3">' +
            '<div class="col-6"><small class="text-body-secondary">Type</small><div><span class="shop-chip ' + c.chip + '">' + typeLabel(c.type) + '</span></div></div>' +
            '<div class="col-6"><small class="text-body-secondary">Value</small><div class="fw-bold fs-5">' + c.value + '</div></div>' +
            '<div class="col-6"><small class="text-body-secondary">Status</small><div>' + statusChip(c.status) + '</div></div>' +
            '<div class="col-6"><small class="text-body-secondary">Expires</small><div>' + fmtDate(c.exp) + '</div></div>' +
          '</div>' +
          '<div class="mb-3">' +
            '<div class="d-flex justify-content-between small mb-1"><span>Redemptions</span><span>' + c.used + ' / ' + (c.limit || '∞') + '</span></div>' +
            '<div class="coup-usage__bar"><div class="coup-usage__fill" data-fill="' + pct + '"></div></div>' +
          '</div>' +
          '<h6 class="mt-4">Redemptions over time</h6>' +
          '<div class="coup-chart-wrap"><canvas id="coupChart"></canvas></div>' +
          '<h6 class="mt-4">Top users</h6>' +
          '<ul class="list-unstyled">' +
            CUSTOMERS.slice(0, 4).map(function (u) {
              return '<li class="d-flex align-items-center gap-2 py-2 border-bottom"><span class="scust-card__avatar scust-card__avatar--' + u.avatar + ' scust-mini-avatar">' + u.name.split(' ').map(function (w) { return w[0]; }).join('') + '</span><span class="flex-grow-1 small">' + u.name + '</span><span class="text-body-secondary small">' + Math.floor(Math.random() * 5 + 1) + 'x</span></li>';
            }).join('') +
          '</ul>' +
          '<button type="button" class="btn btn-outline-secondary btn-sm mt-3 w-100"><i class="bi bi-pencil me-1"></i>Edit coupon</button>' +
        '</div>';
      pane.querySelectorAll('[data-fill]').forEach(function (f) {
        f.style.width = f.getAttribute('data-fill') + '%';
      });
      var canvas = document.getElementById('coupChart');
      if (canvas && window.Chart) {
        var seed = c.code.charCodeAt(0);
        var data = [];
        for (var i = 0; i < 12; i++) data.push(Math.round((Math.sin(i * seed) + 1.5) * (c.used / 24)));
        new Chart(canvas.getContext('2d'), {
          type: 'line',
          data: { labels: ['W1','W2','W3','W4','W5','W6','W7','W8','W9','W10','W11','W12'], datasets: [{ data: data, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,.15)', fill: true, tension: .4, pointRadius: 0 }] },
          options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } }, responsive: true, maintainAspectRatio: false }
        });
      }
    }

    render();
    tbody.addEventListener('click', function (e) {
      var row = e.target.closest('.coup-row');
      if (row) selectRow(+row.getAttribute('data-coup-idx'));
    });
    selectRow(0);

    // Multi-step create modal
    var steps = ['type','rules','restrictions','review'];
    var currentStep = 0;
    function updateStepper() {
      var wrap = document.getElementById('coupSteps');
      if (!wrap) return;
      wrap.querySelectorAll('.coup-stepper__step').forEach(function (s, i) {
        s.classList.remove('is-current','is-done');
        if (i < currentStep) s.classList.add('is-done');
        else if (i === currentStep) s.classList.add('is-current');
      });
      document.querySelectorAll('.coup-step-panel').forEach(function (p, i) {
        p.classList.toggle('is-current', i === currentStep);
      });
      var prev = document.getElementById('coupPrev');
      var next = document.getElementById('coupNext');
      var save = document.getElementById('coupSave');
      if (prev) prev.disabled = currentStep === 0;
      if (next) next.classList.toggle('d-none', currentStep === steps.length - 1);
      if (save) save.classList.toggle('d-none', currentStep !== steps.length - 1);
    }
    var next = document.getElementById('coupNext');
    var prev = document.getElementById('coupPrev');
    var save = document.getElementById('coupSave');
    if (next) next.addEventListener('click', function () { if (currentStep < steps.length - 1) currentStep++; updateStepper(); });
    if (prev) prev.addEventListener('click', function () { if (currentStep > 0) currentStep--; updateStepper(); });
    if (save) save.addEventListener('click', function () {
      orchidToast('Coupon created successfully', 'success');
      var m = bootstrap.Modal.getInstance(document.getElementById('coupCreateModal'));
      if (m) m.hide();
      currentStep = 0; updateStepper();
    });
    var createBtn = document.getElementById('coupCreateOpen');
    if (createBtn) createBtn.addEventListener('click', function () { currentStep = 0; updateStepper(); });
    updateStepper();
  }

  /* =====================================================
     REVIEWS
     ===================================================== */
  function initReviews() {
    var wrap = document.getElementById('revList');
    if (!wrap) return;

    var REVIEWS = [
      { id: 1, product: 'Halo Wireless Earbuds', thumb: 1, icon: 'bi-earbuds', reviewer: 'Emma W.', ai: 1, verified: true,  rating: 5, title: 'Best earbuds I have ever owned', body: 'Sound quality is incredible, ANC works great in the office, battery lasts all day. The case is tiny and fits in any pocket. Setup with iPhone was instant.', photos: 2, helpful: 42, status: 'approved', date: '2026-07-22' },
      { id: 2, product: 'Nomad Weekender Bag',    thumb: 2, icon: 'bi-bag',     reviewer: 'James D.', ai: 2, verified: true,  rating: 4, title: 'Sturdy build, small nitpicks',    body: 'Great bag for long weekends. Straps are comfortable and it fits a lot. The only downside is the water bottle pocket is a bit tight.', photos: 1, helpful: 18, status: 'approved', date: '2026-07-21' },
      { id: 3, product: 'Aurora Smart Bulb 4-Pack',thumb: 3,icon: 'bi-lightbulb',reviewer: 'Sarah M.',ai: 3, verified: false, rating: 3, title: 'Works but pairing is buggy',      body: 'Bulbs are bright and the color range is nice. Had to reset one bulb three times before it connected.', photos: 0, helpful: 8, status: 'pending', date: '2026-07-21' },
      { id: 4, product: 'Meridian Chef\'s Knife', thumb: 4, icon: 'bi-scissors', reviewer: 'Ryan G.', ai: 4, verified: true,  rating: 5, title: 'Razor sharp out of the box',      body: 'Balance is perfect, edge is beautifully sharp. I have been using it daily for two months and it still slices tomatoes cleanly.', photos: 3, helpful: 67, status: 'approved', date: '2026-07-20' },
      { id: 5, product: 'Ember Travel Mug 12oz',  thumb: 5, icon: 'bi-cup-hot',  reviewer: 'Ava L.',  ai: 5, verified: true,  rating: 2, title: 'Leaked after two weeks',           body: 'Started leaking around the lid after light daily use. Contacted support and got a replacement.', photos: 1, helpful: 22, status: 'flagged', date: '2026-07-19' },
      { id: 6, product: 'Terra Yoga Mat',         thumb: 6, icon: 'bi-heart-pulse',reviewer: 'Noah P.',ai: 6, verified: true, rating: 5, title: 'Grippy and thick',                body: 'Rolls out flat immediately, no funny smell. Great support for downward dog.', photos: 0, helpful: 14, status: 'approved', date: '2026-07-18' },
      { id: 7, product: 'Nova 4K Action Camera',  thumb: 8, icon: 'bi-camera-video',reviewer: 'Mia C.',ai: 1, verified: true,rating: 4, title: 'Great video, weak battery',        body: '4K footage is crisp with amazing stabilization. Battery only lasts about 45 mins recording continuously.', photos: 4, helpful: 31, status: 'approved', date: '2026-07-17' },
      { id: 8, product: 'Vertex Trail Backpack 30L',thumb:7,icon:'bi-backpack',   reviewer: 'Lucas R.',ai: 2, verified: false,rating: 1, title: 'Zipper broke on first hike',       body: 'Really disappointed. Main compartment zipper snagged and pulled apart on my first day out.', photos: 2, helpful: 5, status: 'pending', date: '2026-07-16' },
      { id: 9, product: 'Halo Studio Headphones', thumb: 1, icon: 'bi-headphones',reviewer: 'Ethan B.',ai: 4, verified: true, rating: 5, title: 'Studio quality at a fair price',   body: 'Detailed sound stage, comfortable for hour-long mixing sessions. My new go-to over-ears.', photos: 1, helpful: 88, status: 'approved', date: '2026-07-15' }
    ];

    var state = { tab: 'all', q: '', minRating: 0, verifiedOnly: false };

    function apply() {
      var list = REVIEWS.filter(function (r) {
        if (state.tab !== 'all' && r.status !== state.tab) return false;
        if (state.q && (r.product + ' ' + r.title + ' ' + r.body).toLowerCase().indexOf(state.q.toLowerCase()) === -1) return false;
        if (state.minRating > 0 && r.rating < state.minRating) return false;
        if (state.verifiedOnly && !r.verified) return false;
        return true;
      });
      render(list);
    }

    function render(list) {
      if (!list.length) {
        wrap.innerHTML = '<div class="shop-empty"><div class="shop-empty__icon"><i class="bi bi-chat-square-quote"></i></div><h5 class="mb-1">No reviews match</h5></div>';
        return;
      }
      wrap.innerHTML = list.map(function (r) {
        var photos = '';
        if (r.photos > 0) {
          photos = '<div class="rev-card__photos">';
          for (var i = 0; i < r.photos; i++) photos += '<span class="rev-photo shop-cbg-' + ((i % 4) + 1) + '"><i class="bi bi-image"></i></span>';
          photos += '</div>';
        }
        return '<article class="rev-card" data-rev-id="' + r.id + '">' +
          '<div class="rev-card__head">' +
            '<span class="rev-card__pthumb shop-cbg-' + r.thumb + '"><i class="bi ' + r.icon + '"></i></span>' +
            '<div class="flex-grow-1">' +
              '<p class="rev-card__pname">' + r.product + ' · ' + fmtDate(r.date, 'short') + '</p>' +
              '<div class="d-flex align-items-center gap-2 flex-wrap">' +
                '<span class="rev-card__reviewer"><span class="avatar bg-primary-subtle text-primary">' + r.reviewer.split(' ').map(function (w) { return w[0]; }).join('') + '</span>' + r.reviewer + '</span>' +
                (r.verified ? '<span class="rev-verified"><i class="bi bi-patch-check-fill"></i>Verified purchase</span>' : '') +
                renderStars(r.rating) +
              '</div>' +
            '</div>' +
          '</div>' +
          '<h6 class="rev-card__title">' + r.title + '</h6>' +
          '<p class="rev-card__body">' + r.body + '</p>' +
          photos +
          '<div class="rev-card__foot">' +
            '<span class="rev-card__helpful"><i class="bi bi-hand-thumbs-up me-1"></i>' + r.helpful + ' found helpful</span>' +
            '<div class="ms-auto d-flex gap-1">' +
              '<button type="button" class="btn btn-sm btn-outline-success" data-rev-act="approve" data-rev-id="' + r.id + '"><i class="bi bi-check2"></i></button>' +
              '<button type="button" class="btn btn-sm btn-outline-danger"  data-rev-act="reject"  data-rev-id="' + r.id + '"><i class="bi bi-x"></i></button>' +
              '<button type="button" class="btn btn-sm btn-outline-primary" data-rev-act="reply"   data-rev-id="' + r.id + '"><i class="bi bi-reply"></i></button>' +
              '<button type="button" class="btn btn-sm btn-outline-secondary" data-rev-act="hide"  data-rev-id="' + r.id + '"><i class="bi bi-eye-slash"></i></button>' +
              '<button type="button" class="btn btn-sm btn-outline-warning" data-rev-act="flag"    data-rev-id="' + r.id + '"><i class="bi bi-flag"></i></button>' +
            '</div>' +
          '</div>' +
        '</article>';
      }).join('');
    }

    // Tabs
    document.querySelectorAll('[data-rev-tab]').forEach(function (t) {
      t.addEventListener('click', function () {
        document.querySelectorAll('[data-rev-tab]').forEach(function (o) { o.classList.remove('active'); });
        t.classList.add('active');
        state.tab = t.getAttribute('data-rev-tab');
        apply();
      });
    });
    var s = document.querySelector('[data-rev-search]');
    if (s) s.addEventListener('input', function (e) { state.q = e.target.value; apply(); });
    var rt = document.querySelector('[data-rev-rating]');
    if (rt) rt.addEventListener('change', function (e) { state.minRating = +e.target.value; apply(); });
    var vo = document.querySelector('[data-rev-verified]');
    if (vo) vo.addEventListener('change', function (e) { state.verifiedOnly = e.target.checked; apply(); });

    wrap.addEventListener('click', function (e) {
      var b = e.target.closest('[data-rev-act]');
      if (!b) return;
      var act = b.getAttribute('data-rev-act');
      var id = b.getAttribute('data-rev-id');
      if (act === 'reply') {
        if (window.bootstrap) { var m = new bootstrap.Modal(document.getElementById('revReplyModal')); m.show(); }
      } else {
        orchidToast('Review #' + id + ' — ' + act + 'ed', 'primary');
      }
    });

    apply();

    // Summary chart bars
    var counts = [0,0,0,0,0];
    REVIEWS.forEach(function (r) { counts[r.rating - 1]++; });
    var maxC = Math.max.apply(null, counts) || 1;
    for (var i = 5; i >= 1; i--) {
      var bar = document.querySelector('[data-rev-dist="' + i + '"]');
      if (bar) bar.style.width = (counts[i-1] / maxC * 100) + '%';
      var cn = document.querySelector('[data-rev-dist-count="' + i + '"]');
      if (cn) cn.textContent = counts[i-1];
    }
  }

  /* =====================================================
     SHIPPING
     ===================================================== */
  function initShipping() {
    var zList = document.getElementById('shipZones');
    if (!zList) return;

    var ZONES = [
      { id: 'us', name: 'United States', flags: ['US'], carriers: ['USPS','FedEx','UPS'], methodCount: 4 },
      { id: 'ca', name: 'Canada',        flags: ['CA'], carriers: ['Canada Post','UPS'], methodCount: 2 },
      { id: 'eu', name: 'European Union',flags: ['DE','FR','IT','ES','NL','BE'], carriers: ['DHL','UPS','FedEx'], methodCount: 4 },
      { id: 'uk', name: 'United Kingdom',flags: ['GB'], carriers: ['Royal Mail','DHL'], methodCount: 3 },
      { id: 'apac',name: 'Asia Pacific', flags: ['JP','KR','SG','AU','HK'], carriers: ['DHL','FedEx'], methodCount: 3 },
      { id: 'row',name: 'Rest of World', flags: ['BR','MX','ZA','AE','IN'], carriers: ['DHL'], methodCount: 2 }
    ];

    var METHODS = {
      us: [
        { name: 'USPS Ground Advantage', carrier: 'USPS',  days: '3-5 days', free: 50, base: 5.99, kg: 1.20, status: 'active' },
        { name: 'FedEx Home Delivery',   carrier: 'FedEx', days: '2-4 days', free: 75, base: 8.99, kg: 1.50, status: 'active' },
        { name: 'UPS Ground',            carrier: 'UPS',   days: '3-5 days', free: 60, base: 7.99, kg: 1.35, status: 'active' },
        { name: 'FedEx Overnight',       carrier: 'FedEx', days: '1 day',    free: null,base: 29.99,kg: 4.50, status: 'active' }
      ],
      ca: [
        { name: 'Canada Post Regular', carrier: 'Canada Post', days: '5-8 days',  free: 75,  base: 9.99, kg: 2.00, status: 'active' },
        { name: 'UPS Standard',        carrier: 'UPS',         days: '3-6 days',  free: 100, base: 12.99,kg: 2.50, status: 'active' }
      ],
      eu: [
        { name: 'DHL Express',   carrier: 'DHL',   days: '2-4 days', free: 100, base: 14.99, kg: 3.00, status: 'active' },
        { name: 'UPS Standard',  carrier: 'UPS',   days: '3-6 days', free: 120, base: 12.99, kg: 2.50, status: 'active' },
        { name: 'FedEx Economy', carrier: 'FedEx', days: '4-7 days', free: 80,  base: 10.99, kg: 2.00, status: 'active' },
        { name: 'Local Courier', carrier: 'Local', days: '1-2 days', free: null,base: 19.99, kg: 3.50, status: 'draft' }
      ],
      uk: [
        { name: 'Royal Mail Tracked 48', carrier: 'Royal Mail', days: '2-3 days', free: 40, base: 4.99, kg: 1.00, status: 'active' },
        { name: 'Royal Mail Special',    carrier: 'Royal Mail', days: '1 day',    free: null,base: 12.99,kg: 2.50, status: 'active' },
        { name: 'DHL Domestic',          carrier: 'DHL',        days: '1-2 days', free: 60, base: 9.99, kg: 2.00, status: 'active' }
      ],
      apac: [
        { name: 'DHL Express Worldwide', carrier: 'DHL',   days: '3-5 days', free: 150, base: 24.99, kg: 4.50, status: 'active' },
        { name: 'FedEx International',   carrier: 'FedEx', days: '3-6 days', free: 150, base: 22.99, kg: 4.00, status: 'active' },
        { name: 'Standard Air',          carrier: 'Local', days: '7-14 days',free: 80,  base: 12.99, kg: 2.75, status: 'active' }
      ],
      row: [
        { name: 'DHL Worldwide', carrier: 'DHL', days: '5-10 days', free: 200, base: 29.99, kg: 5.50, status: 'active' },
        { name: 'Economy Air',   carrier: 'Local', days: '14-21 days', free: 150, base: 19.99, kg: 4.00, status: 'active' }
      ]
    };

    function renderZones(activeId) {
      zList.innerHTML = ZONES.map(function (z) {
        var flags = z.flags.slice(0, 5).map(function (f) { return '<span>' + renderFlag(f) + '</span>'; }).join('');
        if (z.flags.length > 5) flags += '<span class="more">+' + (z.flags.length - 5) + '</span>';
        var carriers = z.carriers.map(function (c) { return '<span class="shop-chip shop-chip--cyan">' + c + '</span>'; }).join(' ');
        return '<article class="ship-zone-card' + (z.id === activeId ? ' is-selected' : '') + '" data-ship-zone="' + z.id + '">' +
          '<div class="ship-zone-card__head">' +
            '<h6 class="ship-zone-card__name">' + z.name + '</h6>' +
            '<button type="button" class="btn btn-sm btn-icon" aria-label="Edit zone"><i class="bi bi-pencil"></i></button>' +
          '</div>' +
          '<div class="ship-zone-card__flags">' + flags + '</div>' +
          '<div class="ship-zone-card__foot">' +
            '<span><i class="bi bi-truck me-1"></i>' + z.methodCount + ' methods</span>' +
            '<span class="d-flex flex-wrap gap-1">' + carriers + '</span>' +
          '</div>' +
        '</article>';
      }).join('');
    }

    function renderMethods(zoneId) {
      var wrap = document.getElementById('shipMethodsBody');
      var title = document.getElementById('shipMethodsTitle');
      var zone = ZONES.find(function (z) { return z.id === zoneId; });
      if (title && zone) title.textContent = 'Shipping methods — ' + zone.name;
      var methods = METHODS[zoneId] || [];
      if (!wrap) return;
      wrap.innerHTML = methods.map(function (m) {
        var sc = m.status === 'active' ? 'shop-chip--emerald' : 'shop-chip--slate';
        return '<tr>' +
          '<td class="fw-semibold">' + m.name + '</td>' +
          '<td>' + m.carrier + '</td>' +
          '<td>' + m.days + '</td>' +
          '<td>' + (m.free ? '$' + m.free : '—') + '</td>' +
          '<td>' + fmtMoney(m.base) + '</td>' +
          '<td>' + fmtMoney(m.kg) + '/kg</td>' +
          '<td><span class="shop-chip ' + sc + '">' + m.status + '</span></td>' +
          '<td class="text-end"><button type="button" class="btn btn-sm btn-icon"><i class="bi bi-pencil"></i></button></td>' +
        '</tr>';
      }).join('');
    }

    renderZones('us');
    renderMethods('us');
    zList.addEventListener('click', function (e) {
      var card = e.target.closest('[data-ship-zone]');
      if (!card) return;
      var id = card.getAttribute('data-ship-zone');
      renderZones(id);
      renderMethods(id);
    });

    var addM = document.getElementById('shipAddMethod');
    if (addM) addM.addEventListener('click', function () { orchidToast('New shipping method dialog', 'primary'); });
    var addC = document.getElementById('shipAddCarrier');
    if (addC) addC.addEventListener('click', function () { orchidToast('Carrier setup opened', 'primary'); });
  }

  /* =====================================================
     TAXES
     ===================================================== */
  function initTaxes() {
    var tbody = document.getElementById('taxTbody');
    if (!tbody) return;

    var RATES = [
      { region: 'California, US',  country: 'US', rate: 7.25, type: 'Sales Tax', applies: 'both',     compound: false, from: '2026-01-01', status: 'active' },
      { region: 'New York, US',    country: 'US', rate: 8.875,type: 'Sales Tax', applies: 'both',     compound: false, from: '2026-01-01', status: 'active' },
      { region: 'Texas, US',       country: 'US', rate: 6.25, type: 'Sales Tax', applies: 'physical', compound: false, from: '2026-01-01', status: 'active' },
      { region: 'Florida, US',     country: 'US', rate: 6.00, type: 'Sales Tax', applies: 'both',     compound: false, from: '2026-01-01', status: 'active' },
      { region: 'Illinois, US',    country: 'US', rate: 6.25, type: 'Sales Tax', applies: 'physical', compound: false, from: '2026-01-01', status: 'active' },
      { region: 'Ontario, CA',     country: 'CA', rate: 13.0, type: 'HST',       applies: 'both',     compound: false, from: '2026-01-01', status: 'active' },
      { region: 'Quebec, CA',      country: 'CA', rate: 14.975,type: 'GST + QST',applies: 'both',     compound: true,  from: '2026-01-01', status: 'active' },
      { region: 'British Columbia, CA', country: 'CA', rate: 12.0, type: 'GST + PST', applies: 'both',compound: true,  from: '2026-01-01', status: 'active' },
      { region: 'Germany',         country: 'DE', rate: 19.0, type: 'VAT',       applies: 'both',     compound: false, from: '2026-01-01', status: 'active' },
      { region: 'France',          country: 'FR', rate: 20.0, type: 'VAT',       applies: 'both',     compound: false, from: '2026-01-01', status: 'active' },
      { region: 'Italy',           country: 'IT', rate: 22.0, type: 'VAT',       applies: 'both',     compound: false, from: '2026-01-01', status: 'active' },
      { region: 'Spain',           country: 'ES', rate: 21.0, type: 'VAT',       applies: 'both',     compound: false, from: '2026-01-01', status: 'active' },
      { region: 'Netherlands',     country: 'NL', rate: 21.0, type: 'VAT',       applies: 'both',     compound: false, from: '2026-01-01', status: 'active' },
      { region: 'United Kingdom',  country: 'GB', rate: 20.0, type: 'VAT',       applies: 'both',     compound: false, from: '2026-01-01', status: 'active' },
      { region: 'Ireland',         country: 'IE', rate: 23.0, type: 'VAT',       applies: 'both',     compound: false, from: '2026-01-01', status: 'active' },
      { region: 'Australia',       country: 'AU', rate: 10.0, type: 'GST',       applies: 'both',     compound: false, from: '2026-01-01', status: 'active' },
      { region: 'New Zealand',     country: 'NZ', rate: 15.0, type: 'GST',       applies: 'both',     compound: false, from: '2026-01-01', status: 'active' },
      { region: 'Japan',           country: 'JP', rate: 10.0, type: 'Consumption', applies: 'both',   compound: false, from: '2026-01-01', status: 'active' },
      { region: 'Singapore',       country: 'SG', rate: 9.0,  type: 'GST',       applies: 'both',     compound: false, from: '2026-01-01', status: 'active' },
      { region: 'India',           country: 'IN', rate: 18.0, type: 'GST',       applies: 'both',     compound: false, from: '2026-01-01', status: 'draft' },
      { region: 'Brazil',          country: 'BR', rate: 17.0, type: 'ICMS',      applies: 'physical', compound: true,  from: '2026-01-01', status: 'active' },
      { region: 'Mexico',          country: 'MX', rate: 16.0, type: 'VAT',       applies: 'both',     compound: false, from: '2026-01-01', status: 'active' }
    ];

    var state = { q: '', type: 'all', status: 'all', selected: [] };

    function apply() {
      var list = RATES.filter(function (r) {
        if (state.q && (r.region + ' ' + r.type).toLowerCase().indexOf(state.q.toLowerCase()) === -1) return false;
        if (state.type !== 'all' && r.type !== state.type) return false;
        if (state.status !== 'all' && r.status !== state.status) return false;
        return true;
      });
      render(list);
    }

    function render(list) {
      if (!list.length) {
        tbody.innerHTML = '<tr><td colspan="9"><div class="shop-empty"><div class="shop-empty__icon"><i class="bi bi-percent"></i></div><h5 class="mb-1">No tax rates match</h5></div></td></tr>';
        return;
      }
      tbody.innerHTML = list.map(function (r, i) {
        var appliesLabel = r.applies === 'both' ? 'Physical + Digital' : r.applies.charAt(0).toUpperCase() + r.applies.slice(1);
        var typeChip = ({ 'Sales Tax': 'shop-chip--pink', 'VAT': 'shop-chip--violet', 'GST': 'shop-chip--emerald', 'HST': 'shop-chip--cyan', 'GST + QST': 'shop-chip--cyan', 'GST + PST': 'shop-chip--cyan', 'Consumption': 'shop-chip--amber', 'ICMS': 'shop-chip--red' })[r.type] || 'shop-chip--slate';
        var stChip = r.status === 'active' ? 'shop-chip--emerald' : 'shop-chip--slate';
        return '<tr>' +
          '<td><input type="checkbox" class="form-check-input" data-tax-sel value="' + i + '"></td>' +
          '<td><span class="tax-flag">' + renderFlag(r.country) + '</span>' + r.region + '</td>' +
          '<td class="fw-bold">' + r.rate.toFixed(2) + '%</td>' +
          '<td><span class="shop-chip ' + typeChip + '">' + r.type + '</span></td>' +
          '<td class="text-body-secondary small">' + appliesLabel + '</td>' +
          '<td>' + (r.compound ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-dash-circle text-body-secondary"></i>') + '</td>' +
          '<td class="text-body-secondary small">' + fmtDate(r.from, 'short') + '</td>' +
          '<td><span class="shop-chip ' + stChip + '">' + r.status + '</span></td>' +
          '<td class="text-end"><button type="button" class="btn btn-sm btn-icon"><i class="bi bi-pencil"></i></button></td>' +
        '</tr>';
      }).join('');
      updateBulkBar();
    }

    function updateBulkBar() {
      var checked = tbody.querySelectorAll('[data-tax-sel]:checked');
      var bar = document.getElementById('taxBulkBar');
      var count = document.getElementById('taxBulkCount');
      if (!bar) return;
      bar.classList.toggle('is-visible', checked.length > 0);
      if (count) count.textContent = checked.length + ' selected';
    }

    tbody.addEventListener('change', updateBulkBar);
    var selAll = document.getElementById('taxSelAll');
    if (selAll) selAll.addEventListener('change', function (e) {
      tbody.querySelectorAll('[data-tax-sel]').forEach(function (c) { c.checked = e.target.checked; });
      updateBulkBar();
    });
    var s = document.querySelector('[data-tax-search]');
    if (s) s.addEventListener('input', function (e) { state.q = e.target.value; apply(); });
    var t = document.querySelector('[data-tax-type]');
    if (t) t.addEventListener('change', function (e) { state.type = e.target.value; apply(); });
    var st = document.querySelector('[data-tax-status]');
    if (st) st.addEventListener('change', function (e) { state.status = e.target.value; apply(); });

    document.querySelectorAll('[data-tax-bulk]').forEach(function (b) {
      b.addEventListener('click', function () {
        orchidToast('Bulk ' + b.getAttribute('data-tax-bulk') + ' applied', 'primary');
      });
    });

    var addForm = document.getElementById('taxAddForm');
    if (addForm) addForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var m = bootstrap.Modal.getInstance(document.getElementById('taxAddModal'));
      if (m) m.hide();
      orchidToast('Tax rate added', 'success');
    });

    apply();
  }

})();
