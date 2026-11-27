/* =====================================================
   Orchid — Analytics Console
   Chart.js configs + interactive hooks.
   ===================================================== */
(function () {
  'use strict';

  var instances = new Map();

  var COLORS = {
    violet:  '#7c3aed',
    violet2: '#a855f7',
    cyan:    '#22d3ee',
    lime:    '#84cc16',
    coral:   '#f43f5e',
    amber:   '#f59e0b'
  };

  function isDark() { return document.documentElement.getAttribute('data-bs-theme') === 'dark'; }
  function grid() { return isDark() ? 'rgba(255,255,255,.06)' : 'rgba(15,18,32,.06)'; }
  function tick() { return isDark() ? '#8891a8' : '#6b7385'; }
  var font = { family: 'Inter, sans-serif', size: 11, weight: '500' };

  function baseOpts() {
    return {
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: 0 },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: isDark() ? '#171a2b' : '#ffffff',
          titleColor: isDark() ? '#e6e8f2' : '#0a1230',
          bodyColor:  isDark() ? '#e6e8f2' : '#0a1230',
          borderColor: isDark() ? '#232842' : '#e9ecf3',
          borderWidth: 1, padding: 10, cornerRadius: 8,
          titleFont: { family: 'Inter', size: 12, weight: '700' },
          bodyFont:  { family: 'Inter', size: 11, weight: '500' },
          displayColors: true, boxPadding: 4
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: tick(), font: font } },
        y: { grid: { color: grid(), drawBorder: false }, ticks: { color: tick(), font: font } }
      }
    };
  }

  function hexToRgba(hex, a) {
    var h = hex.replace('#', '');
    var r = parseInt(h.slice(0,2),16), g = parseInt(h.slice(2,4),16), b = parseInt(h.slice(4,6),16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + a + ')';
  }
  function makeGradient(ctx, area, hex, topA, bottomA) {
    var g = ctx.createLinearGradient(0, area.top, 0, area.bottom);
    g.addColorStop(0, hexToRgba(hex, topA));
    g.addColorStop(1, hexToRgba(hex, bottomA));
    return g;
  }

  /* -------- Chart builders -------- */

  /* Compact minimal chart used for the 4 KPI mini charts in the hero */
  function makeMiniLine(canvas, data, hex) {
    return new Chart(canvas, {
      type: 'line',
      data: {
        labels: data.map(function (_, i) { return String(i); }),
        datasets: [{
          data: data,
          borderColor: hex,
          backgroundColor: function (ctx) {
            var area = ctx.chart.chartArea;
            if (!area) return hexToRgba(hex, .22);
            return makeGradient(ctx.chart.ctx, area, hex, .42, 0);
          },
          borderWidth: 2,
          tension: .42,
          fill: true,
          pointRadius: 0,
          pointHoverRadius: 3,
          pointBackgroundColor: hex
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false, layout: { padding: 0 },
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: { x: { display: false }, y: { display: false } }
      }
    });
  }
  function makeMiniBar(canvas, data, hex) {
    return new Chart(canvas, {
      type: 'bar',
      data: {
        labels: data.map(function (_, i) { return String(i); }),
        datasets: [{
          data: data,
          backgroundColor: function (ctx) {
            var area = ctx.chart.chartArea;
            if (!area) return hexToRgba(hex, .7);
            var g = ctx.chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
            g.addColorStop(0, hexToRgba(hex, .95));
            g.addColorStop(1, hexToRgba(hex, .35));
            return g;
          },
          borderRadius: 3,
          barPercentage: .7,
          categoryPercentage: .85
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false, layout: { padding: 0 },
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: { x: { display: false }, y: { display: false } }
      }
    });
  }

  function buildKpiRevenue(canvas) {
    // Green/lime line — upward
    return makeMiniLine(canvas,
      [18,22,20,26,24,30,28,34,32,38,42,40,46,52,58],
      '#a3e635');
  }
  function buildKpiUsers(canvas) {
    // Cyan bar — upward
    return makeMiniBar(canvas,
      [24,28,22,30,26,34,32,38,36,42,40,46,44,50,54],
      '#22d3ee');
  }
  function buildKpiSessions(canvas) {
    // Violet line — upward, steadier
    return makeMiniLine(canvas,
      [40,42,38,44,46,44,50,52,50,56,58,56,62,64,68],
      '#a855f7');
  }
  function buildKpiConversion(canvas) {
    // Coral line — downward (matches -0.6% delta)
    return makeMiniLine(canvas,
      [58,56,54,52,50,52,48,46,48,44,42,40,38,40,36],
      '#f43f5e');
  }

  function buildTraffic(canvas) {
    var labels = Array.from({ length: 30 }, function (_, i) {
      var d = new Date(); d.setDate(d.getDate() - (29 - i));
      return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    });
    var sess = [8420,8180,9210,10240,9890,11280,12100,11720,12580,13440,12980,13820,14260,15020,14680,15420,16180,15840,16820,17420,16980,17640,18280,17920,18720,19420,19080,19940,20380,21120];
    var users = [5240,5060,5720,6360,6120,7020,7500,7280,7780,8340,8060,8560,8840,9320,9080,9560,10040,9820,10420,10820,10540,10940,11340,11120,11620,12040,11820,12360,12640,13100];

    return new Chart(canvas, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Sessions',
            data: sess,
            borderColor: COLORS.violet,
            backgroundColor: function (ctx) {
              var area = ctx.chart.chartArea;
              if (!area) return hexToRgba(COLORS.violet, .18);
              return makeGradient(ctx.chart.ctx, area, COLORS.violet, .32, 0);
            },
            borderWidth: 2.5, tension: .42, fill: true,
            pointRadius: 0, pointHoverRadius: 4,
            pointBackgroundColor: COLORS.violet
          },
          {
            label: 'Users',
            data: users,
            borderColor: COLORS.cyan,
            backgroundColor: function (ctx) {
              var area = ctx.chart.chartArea;
              if (!area) return hexToRgba(COLORS.cyan, .12);
              return makeGradient(ctx.chart.ctx, area, COLORS.cyan, .20, 0);
            },
            borderWidth: 2, tension: .42, fill: true,
            pointRadius: 0, pointHoverRadius: 4,
            pointBackgroundColor: COLORS.cyan
          }
        ]
      },
      options: Object.assign(baseOpts(), {
        interaction: { mode: 'index', intersect: false },
        scales: {
          x: { grid: { display: false }, ticks: { color: tick(), font: font, maxTicksLimit: 8 } },
          y: {
            grid: { color: grid(), drawBorder: false },
            ticks: { color: tick(), font: font, callback: function (v) { return (v / 1000).toFixed(0) + 'k'; } }
          }
        }
      })
    });
  }

  function buildDevices(canvas) {
    return new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: ['Mobile', 'Desktop', 'Tablet'],
        datasets: [{
          data: [72, 22, 6],
          backgroundColor: [COLORS.violet, COLORS.cyan, COLORS.lime],
          borderColor: isDark() ? '#171a2b' : '#ffffff',
          borderWidth: 3, hoverOffset: 6
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: isDark() ? '#171a2b' : '#ffffff',
            titleColor: isDark() ? '#e6e8f2' : '#0a1230',
            bodyColor:  isDark() ? '#e6e8f2' : '#0a1230',
            borderColor: isDark() ? '#232842' : '#e9ecf3',
            borderWidth: 1, padding: 8, cornerRadius: 8,
            callbacks: { label: function (c) { return c.label + ': ' + c.parsed + '%'; } }
          }
        }
      }
    });
  }

  var builders = {
    kpiRevenue:    buildKpiRevenue,
    kpiUsers:      buildKpiUsers,
    kpiSessions:   buildKpiSessions,
    kpiConversion: buildKpiConversion,
    traffic:       buildTraffic,
    devices:       buildDevices
  };

  function initCharts() {
    if (typeof Chart === 'undefined') return;
    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.color = tick();
    document.querySelectorAll('[data-analytics-chart]').forEach(function (canvas) {
      var key = canvas.getAttribute('data-analytics-chart');
      var builder = builders[key];
      if (builder) instances.set(canvas, builder(canvas));
    });
  }
  function destroyCharts() {
    instances.forEach(function (c) { try { c.destroy(); } catch (_) {} });
    instances.clear();
  }
  document.addEventListener('orchid:themechange', function () {
    destroyCharts();
    initCharts();
  });

  /* -------- Attribute hydrations -------- */
  function hydrate() {
    // Country bar fills — expand from 0% to their target width for the launch animation
    document.querySelectorAll('[data-analytics-width]').forEach(function (el) {
      var w = Math.max(0, Math.min(100, parseFloat(el.getAttribute('data-analytics-width')) || 0));
      requestAnimationFrame(function () { el.style.width = w + '%'; });
    });
    // Source segments — set flex ratio inside the stacked bar
    document.querySelectorAll('[data-analytics-flex]').forEach(function (el) {
      var f = Math.max(0, parseFloat(el.getAttribute('data-analytics-flex')) || 0);
      el.style.flex = f + ' 1 0%';
    });
    // Landing-page row backgrounds — insert a positioned overlay and expand its width
    document.querySelectorAll('[data-analytics-bar]').forEach(function (row) {
      if (row.querySelector('.analytics-page-row__bg')) return;
      var pct = Math.max(0, Math.min(100, parseFloat(row.getAttribute('data-analytics-bar')) || 0));
      var bg = document.createElement('span');
      bg.className = 'analytics-page-row__bg';
      bg.setAttribute('aria-hidden', 'true');
      bg.style.position = 'absolute';
      bg.style.inset = '0';
      bg.style.width = '0%';
      bg.style.background = 'linear-gradient(90deg, rgba(124,58,237,.10) 0%, rgba(34,211,238,.06) 100%)';
      bg.style.borderRadius = '12px';
      bg.style.zIndex = '0';
      bg.style.pointerEvents = 'none';
      bg.style.transition = 'width .9s cubic-bezier(.4,0,.2,1)';
      row.insertBefore(bg, row.firstChild);
      // ensure real content sits above the background
      Array.prototype.forEach.call(row.children, function (kid) {
        if (kid !== bg && !kid.style.position) kid.style.position = 'relative';
      });
      requestAnimationFrame(function () { bg.style.width = pct + '%'; });
    });
  }

  /* -------- Landing-pages filter -------- */
  function initFilter() {
    var input = document.querySelector('[data-analytics-page-filter]');
    if (!input) return;
    var rows = Array.from(document.querySelectorAll('.analytics-pages .analytics-page-row'));
    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      rows.forEach(function (r) {
        var hay = (r.textContent || '').toLowerCase();
        r.style.display = (!q || hay.indexOf(q) >= 0) ? '' : 'none';
      });
    });
  }

  /* -------- Range toggle -------- */
  function initRange() {
    var btns = document.querySelectorAll('[data-analytics-range]');
    btns.forEach(function (b) {
      b.addEventListener('click', function () {
        btns.forEach(function (x) { x.classList.remove('is-active'); x.setAttribute('aria-pressed', 'false'); });
        b.classList.add('is-active');
        b.setAttribute('aria-pressed', 'true');
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initCharts();
    hydrate();
    initFilter();
    initRange();
  });
})();
