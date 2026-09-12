/* =====================================================
   Orchid - Sales Dashboard Page Charts
   Uses Chart.js v4 (global `Chart`)
   ===================================================== */
(function () {
  'use strict';

  if (typeof window.Chart === 'undefined') return;

  var instances = new Map();

  /* ---------- Theme helpers ---------- */
  function palette() {
    var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var styles = getComputedStyle(document.documentElement);
    var textMuted = styles.getPropertyValue('--orchid-text-muted').trim() || (isDark ? '#8891a8' : '#6b7385');
    var border = styles.getPropertyValue('--orchid-border').trim() || (isDark ? '#232842' : '#e9ecf3');
    return {
      isDark: isDark,
      text: isDark ? '#e6e8f2' : '#1e2436',
      muted: textMuted,
      grid: isDark ? 'rgba(255,255,255,.06)' : 'rgba(15,18,32,.06)',
      border: border,
      teal: '#14b8a6',
      cyan: '#06b6d4',
      emerald: '#10b981',
      indigo: '#6366f1',
      violet: '#8b5cf6',
      amber: '#f59e0b',
      rose: '#f43f5e',
      orange: '#f97316'
    };
  }

  function gradient(ctx, area, colorStart, colorEnd) {
    if (!area) return colorStart;
    var g = ctx.createLinearGradient(0, area.top, 0, area.bottom);
    g.addColorStop(0, colorStart);
    g.addColorStop(1, colorEnd);
    return g;
  }

  function hexToRgba(hex, alpha) {
    var h = hex.replace('#', '');
    if (h.length === 3) h = h.split('').map(function (c) { return c + c; }).join('');
    var r = parseInt(h.substr(0, 2), 16);
    var g = parseInt(h.substr(2, 2), 16);
    var b = parseInt(h.substr(4, 2), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
  }

  /* ---------- Common tooltip ---------- */
  function tooltipCfg(p) {
    return {
      backgroundColor: p.isDark ? 'rgba(23,26,43,.96)' : 'rgba(30,36,54,.96)',
      titleColor: '#fff',
      bodyColor: '#fff',
      padding: 10,
      cornerRadius: 8,
      displayColors: true,
      boxWidth: 8,
      boxHeight: 8,
      boxPadding: 4,
      titleFont: { weight: '600', size: 12 },
      bodyFont: { size: 12 }
    };
  }

  /* ---------- Sparkline factory ---------- */
  function makeSparkline(canvas, color, data) {
    var p = palette();
    var ctx = canvas.getContext('2d');
    return new Chart(ctx, {
      type: 'line',
      data: {
        labels: data.map(function (_, i) { return i; }),
        datasets: [{
          data: data,
          borderColor: color,
          borderWidth: 2,
          tension: .4,
          pointRadius: 0,
          fill: true,
          backgroundColor: function (c) {
            var chart = c.chart;
            var area = chart.chartArea;
            if (!area) return hexToRgba(color, .15);
            return gradient(chart.ctx, area, hexToRgba(color, .28), hexToRgba(color, 0));
          }
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 700 },
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: {
          x: { display: false },
          y: { display: false, beginAtZero: false }
        },
        elements: { line: { borderJoinStyle: 'round' } }
      }
    });
  }

  function makeBarSpark(canvas, color, data) {
    var ctx = canvas.getContext('2d');
    return new Chart(ctx, {
      type: 'bar',
      data: {
        labels: data.map(function (_, i) { return i; }),
        datasets: [{
          data: data,
          backgroundColor: hexToRgba(color, .55),
          borderColor: color,
          borderWidth: 0,
          borderRadius: 3,
          borderSkipped: false,
          barPercentage: .7,
          categoryPercentage: .8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 700 },
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: { x: { display: false }, y: { display: false, beginAtZero: true } }
      }
    });
  }

  /* ---------- Gauge center-text plugin ---------- */
  var gaugeCenterPlugin = {
    id: 'salesGaugeCenter',
    afterDatasetDraw: function () { /* content rendered via HTML overlay */ }
  };

  /* ---------- Chart builders ---------- */
  var builders = {

    // KPI sparklines
    kpiToday: function (canvas) {
      return makeSparkline(canvas, '#14b8a6', [12, 18, 14, 22, 19, 26, 24, 30, 28, 34, 32, 38]);
    },
    kpiWeek: function (canvas) {
      return makeBarSpark(canvas, '#06b6d4', [42, 55, 38, 65, 58, 72, 68]);
    },
    kpiMonth: function (canvas) {
      return makeSparkline(canvas, '#6366f1', [220, 245, 232, 268, 275, 260, 290, 305, 298, 320, 315, 340]);
    },
    kpiYear: function (canvas) {
      return makeBarSpark(canvas, '#f59e0b', [180, 210, 245, 220, 280, 310, 295, 340, 360, 355, 390, 420]);
    },

    // Sales target gauge (semi-circle doughnut)
    targetGauge: function (canvas) {
      var p = palette();
      var achieved = 71;
      var ctx = canvas.getContext('2d');
      return new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Achieved', 'Remaining'],
          datasets: [{
            data: [achieved, 100 - achieved],
            backgroundColor: [
              (function () {
                // gradient will be replaced after first render
                return '#14b8a6';
              })(),
              'rgba(255,255,255,.14)'
            ],
            borderWidth: 0,
            borderRadius: [10, 0],
            circumference: 180,
            rotation: 270,
            cutout: '76%'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: { animateRotate: true, duration: 1100 },
          plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
          }
        },
        plugins: [{
          id: 'gaugeGrad',
          beforeDatasetDraw: function (chart) {
            var area = chart.chartArea;
            if (!area) return;
            var g = chart.ctx.createLinearGradient(area.left, 0, area.right, 0);
            g.addColorStop(0, '#5eead4');
            g.addColorStop(.5, '#06b6d4');
            g.addColorStop(1, '#818cf8');
            chart.data.datasets[0].backgroundColor[0] = g;
          }
        }]
      });
    },

    // Revenue trend (area, YoY)
    revenueTrend: function (canvas) {
      var p = palette();
      var ctx = canvas.getContext('2d');
      var labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      var current = [42, 55, 51, 68, 72, 85, 92, 88, 105, 118, 112, 132];
      var previous = [38, 42, 48, 52, 58, 65, 70, 75, 78, 85, 90, 95];
      return new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            {
              label: '2026',
              data: current,
              borderColor: p.teal,
              borderWidth: 2.5,
              tension: .4,
              fill: true,
              pointRadius: 0,
              pointHoverRadius: 5,
              pointHoverBackgroundColor: p.teal,
              pointHoverBorderColor: '#fff',
              pointHoverBorderWidth: 2,
              backgroundColor: function (c) {
                var chart = c.chart;
                var area = chart.chartArea;
                if (!area) return hexToRgba(p.teal, .2);
                return gradient(chart.ctx, area, hexToRgba(p.teal, .30), hexToRgba(p.teal, 0));
              }
            },
            {
              label: '2025',
              data: previous,
              borderColor: p.indigo,
              borderWidth: 2,
              borderDash: [5, 4],
              tension: .4,
              fill: false,
              pointRadius: 0,
              pointHoverRadius: 5,
              pointHoverBackgroundColor: p.indigo,
              pointHoverBorderColor: '#fff',
              pointHoverBorderWidth: 2
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          animation: { duration: 900 },
          plugins: {
            legend: { display: false },
            tooltip: Object.assign({}, tooltipCfg(p), {
              callbacks: {
                label: function (ctx) { return ctx.dataset.label + ': $' + ctx.parsed.y + 'k'; }
              }
            })
          },
          scales: {
            x: {
              grid: { display: false, drawBorder: false },
              ticks: { color: p.muted, font: { size: 11 } }
            },
            y: {
              beginAtZero: true,
              grid: { color: p.grid, drawBorder: false },
              ticks: {
                color: p.muted,
                font: { size: 11 },
                callback: function (v) { return '$' + v + 'k'; }
              }
            }
          }
        }
      });
    },

    // Category sales (horizontal bar)
    categorySales: function (canvas) {
      var p = palette();
      var ctx = canvas.getContext('2d');
      var labels = ['Electronics', 'Home & Living', 'Wearables', 'Accessories', 'Software', 'Books'];
      var data = [148, 112, 96, 78, 64, 42];
      return new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Sales',
            data: data,
            backgroundColor: function (c) {
              var chart = c.chart;
              var area = chart.chartArea;
              if (!area) return p.teal;
              var g = chart.ctx.createLinearGradient(area.left, 0, area.right, 0);
              g.addColorStop(0, p.teal);
              g.addColorStop(1, p.cyan);
              return g;
            },
            borderRadius: 6,
            borderSkipped: false,
            barThickness: 20
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          animation: { duration: 900 },
          plugins: {
            legend: { display: false },
            tooltip: Object.assign({}, tooltipCfg(p), {
              callbacks: { label: function (ctx) { return '$' + ctx.parsed.x + 'k'; } }
            })
          },
          scales: {
            x: {
              beginAtZero: true,
              grid: { color: p.grid, drawBorder: false },
              ticks: {
                color: p.muted,
                font: { size: 11 },
                callback: function (v) { return '$' + v + 'k'; }
              }
            },
            y: {
              grid: { display: false, drawBorder: false },
              ticks: { color: p.text, font: { size: 12, weight: '500' } }
            }
          }
        }
      });
    },

    // Region sparklines
    regionNa: function (canvas) { return makeSparkline(canvas, '#14b8a6', [30, 38, 34, 42, 48, 45, 52, 58, 56, 64]); },
    regionEmea: function (canvas) { return makeSparkline(canvas, '#6366f1', [25, 28, 32, 30, 36, 38, 42, 40, 46, 48]); },
    regionApac: function (canvas) { return makeSparkline(canvas, '#f59e0b', [18, 22, 20, 26, 24, 30, 32, 35, 38, 42]); },
    regionLatam: function (canvas) { return makeSparkline(canvas, '#f43f5e', [15, 14, 17, 16, 19, 18, 21, 20, 23, 22]); },

    // Product row sparklines
    prod1: function (canvas) { return makeSparkline(canvas, '#14b8a6', [12, 18, 16, 22, 25, 28, 32, 36]); },
    prod2: function (canvas) { return makeSparkline(canvas, '#6366f1', [10, 14, 12, 18, 20, 22, 25, 28]); },
    prod3: function (canvas) { return makeSparkline(canvas, '#f59e0b', [16, 14, 18, 15, 20, 22, 19, 24]); },
    prod4: function (canvas) { return makeSparkline(canvas, '#f43f5e', [18, 20, 16, 22, 19, 17, 15, 14]); },
    prod5: function (canvas) { return makeSparkline(canvas, '#10b981', [8, 12, 10, 14, 16, 18, 20, 22]); }
  };

  /* ---------- Build / Rebuild ---------- */
  function buildAll() {
    var canvases = document.querySelectorAll('[data-sales-chart]');
    canvases.forEach(function (canvas) {
      var key = canvas.getAttribute('data-sales-chart');
      var builder = builders[key];
      if (!builder) return;
      var existing = instances.get(key);
      if (existing) {
        try { existing.destroy(); } catch (e) { /* noop */ }
      }
      try {
        var inst = builder(canvas);
        if (inst) instances.set(key, inst);
      } catch (e) {
        /* noop */
      }
    });
  }

  function destroyAll() {
    instances.forEach(function (c) { try { c.destroy(); } catch (e) {} });
    instances.clear();
  }

  /* ---------- Range toggle ---------- */
  function bindRange() {
    var group = document.querySelector('[data-sales-range]');
    if (!group) return;
    group.addEventListener('click', function (e) {
      var btn = e.target.closest('.sales-range__btn');
      if (!btn) return;
      group.querySelectorAll('.sales-range__btn').forEach(function (b) { b.classList.remove('is-active'); });
      btn.classList.add('is-active');
      // Simulate range change by rebuilding trend chart with variation
      var chart = instances.get('revenueTrend');
      if (!chart) return;
      var range = btn.getAttribute('data-range');
      var mult = range === 'week' ? .25 : (range === 'quarter' ? .75 : 1);
      chart.data.datasets.forEach(function (ds, i) {
        ds.data = ds.data.map(function (v) { return Math.round(v * (mult + (i ? -.05 : .05) + Math.random() * .1)); });
      });
      chart.update();
    });
  }

  /* ---------- Init ---------- */
  function init() {
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = palette().muted;
    buildAll();
    bindRange();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  /* ---------- Theme change ---------- */
  window.addEventListener('orchid:themechange', function () {
    Chart.defaults.color = palette().muted;
    destroyAll();
    buildAll();
  });

})();
