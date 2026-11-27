/* =====================================================
   Orchid - Icons & Maps pages
   Shared behavior for icons-* and maps-* pages
   ===================================================== */
(function () {
  'use strict';

  /* ---------- Toast ---------- */
  function ensureToastStack() {
    var stack = document.querySelector('.orchid-toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'orchid-toast-stack';
      document.body.appendChild(stack);
    }
    return stack;
  }
  function orchidToast(message, variant) {
    var stack = ensureToastStack();
    var toast = document.createElement('div');
    toast.className = 'orchid-toast' + (variant ? ' orchid-toast--' + variant : '');
    var iconName = variant === 'error' ? 'exclamation-circle' : (variant === 'success' ? 'check-circle' : 'info-circle');
    toast.innerHTML = '<i class="bi bi-' + iconName + '"></i><span></span>';
    toast.querySelector('span').textContent = message;
    stack.appendChild(toast);
    requestAnimationFrame(function () { toast.classList.add('is-visible'); });
    setTimeout(function () {
      toast.classList.remove('is-visible');
      setTimeout(function () { toast.remove(); }, 250);
    }, 2200);
  }
  window.orchidToast = orchidToast;

  /* ---------- Copy ---------- */
  function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(text)
        .then(function () { orchidToast('Copied to clipboard', 'success'); })
        .catch(function () { fallbackCopy(text); });
    }
    return Promise.resolve(fallbackCopy(text));
  }
  function fallbackCopy(text) {
    try {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'absolute';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
      orchidToast('Copied to clipboard', 'success');
    } catch (e) {
      orchidToast('Copy failed', 'error');
    }
  }
  window.copyToClipboard = copyToClipboard;

  /* ---------- Icon renderer (per library) ---------- */
  function renderIconMarkup(lib, name, opts) {
    opts = opts || {};
    var style = opts.style || '';
    switch (lib) {
      case 'bootstrap':
        return '<i class="bi bi-' + name + '"></i>';
      case 'fontawesome':
        // style: solid | regular | brands
        var prefix = 'fa-solid';
        if (style === 'regular') prefix = 'fa-regular';
        else if (style === 'brands') prefix = 'fa-brands';
        return '<i class="' + prefix + ' fa-' + name + '"></i>';
      case 'lucide':
        return '<i data-lucide="' + name + '"></i>';
      case 'remix':
        var suffix = style === 'fill' ? '-fill' : '-line';
        return '<i class="ri-' + name + suffix + '"></i>';
    }
    return '';
  }

  /* ---------- Icon library helper (grid + filters + modal) ---------- */
  function initIconLibrary(config) {
    var root = document.querySelector('[data-icons-root]');
    if (!root) return;

    var searchInput = root.querySelector('[data-icons-search]');
    var chipContainer = root.querySelector('[data-icons-chips]');
    var grid = root.querySelector('[data-icons-grid]');
    var countEl = root.querySelector('[data-icons-count]');
    var sizeSlider = root.querySelector('[data-icons-size]');
    var viewButtons = root.querySelectorAll('[data-icons-view]');
    var styleButtons = root.querySelectorAll('[data-icons-style]');

    var state = {
      search: '',
      category: 'all',
      style: config.defaultStyle || '',
      size: 26,
      view: 'grid'
    };

    function iconMarkup(item) {
      return renderIconMarkup(config.library, item.name, { style: state.style });
    }

    function render() {
      var q = state.search.toLowerCase().trim();
      var list = config.icons.filter(function (item) {
        var okCat = state.category === 'all' || (item.categories && item.categories.indexOf(state.category) !== -1);
        var okSearch = !q || item.name.toLowerCase().indexOf(q) !== -1 ||
                       (item.label && item.label.toLowerCase().indexOf(q) !== -1);
        // For style filter (fontawesome brands only match if item.brands or in special brand list)
        var okStyle = true;
        if (config.library === 'fontawesome') {
          if (state.style === 'brands') okStyle = !!item.brand;
          else okStyle = !item.brand;
        }
        return okCat && okSearch && okStyle;
      });

      if (list.length === 0) {
        grid.innerHTML = '<div class="icons-empty col-12"><i class="bi bi-search"></i><p class="mb-0">No icons match your filter.</p></div>';
      } else {
        var html = '';
        for (var i = 0; i < list.length; i++) {
          var item = list[i];
          html += '<button type="button" class="icons-tile" data-icon-name="' + item.name +
                  '" data-icon-label="' + (item.label || item.name) + '">' +
                  '<span class="icons-tile__icon">' + iconMarkup(item) + '</span>' +
                  '<span class="icons-tile__name">' + item.name + '</span>' +
                  '</button>';
        }
        grid.innerHTML = html;
        // Update css var for size
        grid.style.setProperty('--icons-size', state.size + 'px');
        // Re-fix svg size for Lucide
        if (config.library === 'lucide' && window.lucide) {
          window.lucide.createIcons({ nameAttr: 'data-lucide' });
        }
      }
      if (countEl) countEl.textContent = list.length + ' icons';
    }

    if (searchInput) {
      searchInput.addEventListener('input', function (e) {
        state.search = e.target.value;
        render();
      });
    }

    if (chipContainer) {
      chipContainer.addEventListener('click', function (e) {
        var chip = e.target.closest('[data-icons-chip]');
        if (!chip) return;
        chipContainer.querySelectorAll('[data-icons-chip]').forEach(function (c) { c.classList.remove('is-active'); });
        chip.classList.add('is-active');
        state.category = chip.getAttribute('data-icons-chip');
        render();
      });
    }

    if (sizeSlider) {
      sizeSlider.addEventListener('input', function (e) {
        state.size = parseInt(e.target.value, 10) || 26;
        grid.style.setProperty('--icons-size', state.size + 'px');
      });
    }

    viewButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        viewButtons.forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');
        state.view = btn.getAttribute('data-icons-view');
        grid.classList.toggle('is-list', state.view === 'list');
      });
    });

    styleButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        styleButtons.forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');
        state.style = btn.getAttribute('data-icons-style');
        render();
      });
    });

    // Modal open on tile click
    grid.addEventListener('click', function (e) {
      var tile = e.target.closest('.icons-tile');
      if (!tile) return;
      var name = tile.getAttribute('data-icon-name');
      var label = tile.getAttribute('data-icon-label');
      openIconDetail(config.library, name, label, state.style, config.equivalents);
    });

    render();
  }
  window.initIconLibrary = initIconLibrary;

  /* ---------- Icon detail modal ---------- */
  function openIconDetail(lib, name, label, style, equivalents) {
    var modalEl = document.getElementById('iconDetailModal');
    if (!modalEl) return;
    var markup = renderIconMarkup(lib, name, { style: style });
    var snippet = markup;

    modalEl.querySelector('[data-icon-detail-title]').textContent = name;
    modalEl.querySelector('[data-icon-detail-lib]').textContent = libLabel(lib);
    modalEl.querySelector('[data-icon-detail-preview]').innerHTML = markup;
    modalEl.querySelector('[data-icon-detail-code]').textContent = snippet;

    // Sizes
    var sizesWrap = modalEl.querySelector('[data-icon-detail-sizes]');
    if (sizesWrap) {
      var sizes = [
        { s: 16, l: 'sm' }, { s: 22, l: 'md' }, { s: 32, l: 'lg' }, { s: 44, l: 'xl' }
      ];
      var sh = '';
      sizes.forEach(function (s) {
        sh += '<div class="icons-sizes__item" style="font-size:' + s.s + 'px">' + markup + '<small>' + s.l + '</small></div>';
      });
      sizesWrap.innerHTML = sh;
    }

    // Colors
    var colorsWrap = modalEl.querySelector('[data-icon-detail-colors]');
    if (colorsWrap) {
      var colors = ['primary', 'success', 'warning', 'danger', 'info'];
      var ch = '';
      colors.forEach(function (c) {
        ch += '<div class="icons-variants__item text-' + c + '" style="font-size:1.75rem">' + markup + '<small>' + c + '</small></div>';
      });
      colorsWrap.innerHTML = ch;
    }

    // Equivalents (best effort)
    var equivWrap = modalEl.querySelector('[data-icon-detail-equiv]');
    if (equivWrap && equivalents) {
      var eq = equivalents[name] || {};
      var libs = [
        { key: 'bi', label: 'Bootstrap', markup: eq.bi ? '<i class="bi bi-' + eq.bi + '"></i>' : '<span class="text-body-secondary small">n/a</span>', code: eq.bi ? '<i class="bi bi-' + eq.bi + '"></i>' : '' },
        { key: 'fa', label: 'Font Awesome', markup: eq.fa ? '<i class="fa-solid fa-' + eq.fa + '"></i>' : '<span class="text-body-secondary small">n/a</span>', code: eq.fa ? '<i class="fa-solid fa-' + eq.fa + '"></i>' : '' },
        { key: 'lu', label: 'Lucide', markup: eq.lu ? '<i data-lucide="' + eq.lu + '"></i>' : '<span class="text-body-secondary small">n/a</span>', code: eq.lu ? '<i data-lucide="' + eq.lu + '"></i>' : '' },
        { key: 'ri', label: 'Remix', markup: eq.ri ? '<i class="ri-' + eq.ri + '-line"></i>' : '<span class="text-body-secondary small">n/a</span>', code: eq.ri ? '<i class="ri-' + eq.ri + '-line"></i>' : '' }
      ];
      var eqh = '';
      libs.forEach(function (l) {
        eqh += '<div class="icons-equiv__item"><span class="icons-equiv__label">' + l.label + '</span>' +
               '<span style="font-size:1.15rem">' + l.markup + '</span></div>';
      });
      equivWrap.innerHTML = eqh;
      if (window.lucide) window.lucide.createIcons({ nameAttr: 'data-lucide' });
    }

    // Attach copy handler
    var copyBtn = modalEl.querySelector('[data-icon-copy]');
    if (copyBtn) {
      copyBtn.onclick = function () { copyToClipboard(snippet); };
    }

    // Show modal
    var Modal = window.bootstrap && window.bootstrap.Modal;
    if (Modal) {
      var inst = Modal.getOrCreateInstance(modalEl);
      inst.show();
    }
    // Re-run lucide after modal shows
    if (window.lucide) window.lucide.createIcons({ nameAttr: 'data-lucide' });
  }
  function libLabel(lib) {
    return ({
      bootstrap: 'Bootstrap Icons',
      fontawesome: 'Font Awesome',
      lucide: 'Lucide',
      remix: 'Remix Icons'
    })[lib] || lib;
  }
  window.openIconDetail = openIconDetail;

  /* ---------- Vector map helper ---------- */
  function initVectorMap() {
    var vectors = document.querySelectorAll('[data-maps-vector]');
    if (!vectors.length) return;
    vectors.forEach(function (svgWrap) {
      var tooltip = svgWrap.querySelector('.maps-tooltip');
      if (!tooltip) {
        tooltip = document.createElement('div');
        tooltip.className = 'maps-tooltip';
        svgWrap.appendChild(tooltip);
      }
      var regions = svgWrap.querySelectorAll('[data-region]');
      regions.forEach(function (r) {
        r.addEventListener('mouseenter', function (e) {
          tooltip.textContent = r.getAttribute('data-region-name') || r.getAttribute('data-region');
          if (r.hasAttribute('data-region-value')) {
            tooltip.textContent += ' · ' + r.getAttribute('data-region-value');
          }
          tooltip.classList.add('is-visible');
        });
        r.addEventListener('mousemove', function (e) {
          var rect = svgWrap.getBoundingClientRect();
          tooltip.style.left = (e.clientX - rect.left) + 'px';
          tooltip.style.top = (e.clientY - rect.top) + 'px';
        });
        r.addEventListener('mouseleave', function () {
          tooltip.classList.remove('is-visible');
        });
        r.addEventListener('click', function () {
          var name = r.getAttribute('data-region-name') || r.getAttribute('data-region');
          openRegionDetail(name, r);
        });
      });

      // Markers
      var markers = svgWrap.querySelectorAll('.maps-marker');
      markers.forEach(function (m) {
        m.addEventListener('mouseenter', function (e) {
          tooltip.textContent = m.getAttribute('data-marker-label') || 'Marker';
          tooltip.classList.add('is-visible');
        });
        m.addEventListener('mousemove', function (e) {
          var rect = svgWrap.getBoundingClientRect();
          tooltip.style.left = (e.clientX - rect.left) + 'px';
          tooltip.style.top = (e.clientY - rect.top) + 'px';
        });
        m.addEventListener('mouseleave', function () {
          tooltip.classList.remove('is-visible');
        });
        m.addEventListener('click', function () {
          orchidToast(m.getAttribute('data-marker-label') || 'Marker clicked');
        });
      });
    });
  }
  function openRegionDetail(name, el) {
    var modalEl = document.getElementById('regionDetailModal');
    if (!modalEl) {
      orchidToast('Region: ' + name);
      return;
    }
    modalEl.querySelector('[data-region-title]').textContent = name;
    // Faux stats
    var pop = el && el.getAttribute('data-region-pop') || Math.round(50 + Math.random() * 350) + 'M';
    var gdp = el && el.getAttribute('data-region-gdp') || ('$' + Math.round(200 + Math.random() * 2000) + 'B');
    var city = el && el.getAttribute('data-region-city') || 'Capital City';
    modalEl.querySelector('[data-region-pop]').textContent = pop;
    modalEl.querySelector('[data-region-gdp]').textContent = gdp;
    modalEl.querySelector('[data-region-city]').textContent = city;
    var Modal = window.bootstrap && window.bootstrap.Modal;
    if (Modal) Modal.getOrCreateInstance(modalEl).show();
  }

  /* ---------- Google Maps helper (iframe swap) ---------- */
  function initGoogleMaps() {
    var frame = document.querySelector('[data-maps-frame]');
    if (!frame) return;
    var iframe = frame.querySelector('iframe');
    var cities = document.querySelectorAll('[data-maps-city]');
    cities.forEach(function (city) {
      city.addEventListener('click', function () {
        cities.forEach(function (c) { c.classList.remove('is-active'); });
        city.classList.add('is-active');
        var lat = city.getAttribute('data-lat');
        var lng = city.getAttribute('data-lng');
        var name = city.getAttribute('data-name') || 'Location';
        var zoom = city.getAttribute('data-zoom') || '11';
        if (iframe && lat && lng) {
          // OpenStreetMap embed (works without API key)
          var d = 0.4;
          var bbox = (parseFloat(lng) - d) + ',' + (parseFloat(lat) - d/2) + ',' + (parseFloat(lng) + d) + ',' + (parseFloat(lat) + d/2);
          var url = 'https://www.openstreetmap.org/export/embed.html?bbox=' + bbox + '&layer=mapnik&marker=' + lat + ',' + lng;
          iframe.src = url;
        }
        orchidToast('Centered on ' + name, 'success');
      });
    });

    // Search input
    var searchForm = document.querySelector('[data-maps-search-form]');
    if (searchForm) {
      searchForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var val = searchForm.querySelector('input').value.trim();
        if (val) orchidToast('Would search: ' + val);
      });
    }

    // Zoom / fullscreen / style buttons — visual feedback only (OSM embed doesn't accept param changes easily)
    var zoomIn = frame.querySelector('[data-maps-zoom-in]');
    var zoomOut = frame.querySelector('[data-maps-zoom-out]');
    var fullscreen = frame.querySelector('[data-maps-fullscreen]');
    var styleToggle = frame.querySelector('[data-maps-style]');
    if (zoomIn) zoomIn.addEventListener('click', function () { orchidToast('Zoom in'); });
    if (zoomOut) zoomOut.addEventListener('click', function () { orchidToast('Zoom out'); });
    if (fullscreen) fullscreen.addEventListener('click', function () {
      if (frame.requestFullscreen) frame.requestFullscreen().catch(function () { orchidToast('Fullscreen blocked'); });
    });
    if (styleToggle) {
      var isDark = false;
      styleToggle.addEventListener('click', function () {
        isDark = !isDark;
        frame.classList.toggle('is-dark', isDark);
        orchidToast('Map style: ' + (isDark ? 'dark' : 'light'));
      });
    }
  }

  /* ---------- Region-comparison chart (vector maps page) ---------- */
  function initComparisonChart() {
    var canvas = document.getElementById('mapsCompareChart');
    if (!canvas || typeof window.Chart === 'undefined') return;
    new window.Chart(canvas.getContext('2d'), {
      type: 'bar',
      data: {
        labels: ['N. America', 'S. America', 'Europe', 'Africa', 'Asia', 'Oceania'],
        datasets: [
          {
            label: 'Users (K)',
            data: [420, 180, 380, 220, 640, 90],
            backgroundColor: '#4f46e5',
            borderRadius: 6,
            barPercentage: .6
          },
          {
            label: 'Revenue ($K)',
            data: [860, 340, 720, 190, 980, 140],
            backgroundColor: '#22d3ee',
            borderRadius: 6,
            barPercentage: .6
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } },
        scales: {
          x: { grid: { display: false } },
          y: { grid: { color: 'rgba(148,163,184,.15)' }, beginAtZero: true }
        }
      }
    });
  }

  /* ---------- Boot ---------- */
  document.addEventListener('DOMContentLoaded', function () {
    // Year
    var y = document.querySelector('[data-orchid-year]');
    if (y) y.textContent = new Date().getFullYear();
    // Lucide draw
    if (window.lucide) window.lucide.createIcons({ nameAttr: 'data-lucide' });
    initVectorMap();
    initGoogleMaps();
    initComparisonChart();
  });
})();
