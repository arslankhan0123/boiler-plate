/* =====================================================
   Orchid — E-Commerce Dashboard page scripts
   - Uses global Chart (Chart.js v4)
   - Reads canvases from [data-shop-chart]
   - Keeps a Map of instances; destroys + rebuilds on
     'orchid:themechange' so colors stay in sync.
   ===================================================== */
(function () {
  'use strict';

  if (typeof window.Chart === 'undefined') return;

  var Chart = window.Chart;
  var instances = new Map();

  // ---------- Theme helpers ----------
  function readTheme() {
    var theme = document.documentElement.getAttribute('data-bs-theme') || 'light';
    var css = getComputedStyle(document.documentElement);
    var text  = (css.getPropertyValue('--orchid-text') || '#1e2436').trim();
    var muted = (css.getPropertyValue('--orchid-text-muted') || '#6b7385').trim();
    var border = (css.getPropertyValue('--orchid-border') || '#e9ecf3').trim();
    var surface = (css.getPropertyValue('--orchid-surface') || '#ffffff').trim();
    return {
      name: theme,
      text: text,
      muted: muted,
      border: border,
      surface: surface,
      grid: theme === 'dark' ? 'rgba(255,255,255,.06)' : 'rgba(15,18,32,.06)',
      cyan: '#06b6d4',
      cyanSoft: 'rgba(6,182,212,.15)',
      pink: '#ec4899',
      pinkSoft: 'rgba(236,72,153,.15)',
      violet: '#8b5cf6',
      amber: '#f59e0b',
      emerald: '#10b981',
      indigo: '#6366f1'
    };
  }

  // Chart defaults driven by theme
  function applyDefaults(t) {
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = t.muted;
    Chart.defaults.borderColor = t.border;
  }

  // ---------- Random helpers (deterministic seed for stability) ----------
  function seedRand(seed) {
    var s = seed || 1;
    return function () {
      s = (s * 9301 + 49297) % 233280;
      return s / 233280;
    };
  }

  // ---------- KPI mini bars ----------
  function renderKpiBars() {
    var containers = document.querySelectorAll('[data-shop-kpi-bars]');
    containers.forEach(function (el) {
      if (el.dataset.rendered === '1') return;
      var seed = parseInt(el.dataset.seed || '7', 10);
      var rand = seedRand(seed);
      var count = 14;
      var strongIndex = Math.floor(count * 0.72);
      var frag = document.createDocumentFragment();
      for (var i = 0; i < count; i++) {
        var span = document.createElement('span');
        span.className = 'shop-kpi__bar';
        var pct = 22 + Math.floor(rand() * 78);
        span.style.height = pct + '%';
        if (i >= strongIndex) span.classList.add('shop-kpi__bar--strong');
        frag.appendChild(span);
      }
      el.appendChild(frag);
      el.dataset.rendered = '1';
    });
  }

  // ---------- Distribution bars (satisfaction + category ratings) ----------
  function renderDistBars() {
    var bars = document.querySelectorAll('[data-shop-dist]');
    bars.forEach(function (bar) {
      var pct = Math.max(0, Math.min(100, parseFloat(bar.getAttribute('data-shop-dist')) || 0));
      // If the attr sits on a fill element itself, animate that; else find a child fill.
      var target = bar.matches('.shop-dist__fill, .shop-sat-cats__fill')
        ? bar
        : bar.querySelector('.shop-dist__fill, .shop-sat-cats__fill');
      if (target) {
        requestAnimationFrame(function () { target.style.width = pct + '%'; });
      }
    });
  }

  // ---------- Payment-methods stacked bar segments ----------
  function renderPayBar() {
    document.querySelectorAll('.shop-pay-bar__seg[data-shop-pay]').forEach(function (seg) {
      var pct = Math.max(0, parseFloat(seg.getAttribute('data-shop-pay')) || 0);
      seg.style.flex = pct + ' 1 0%';
    });
  }

  // ---------- Chart builders ----------
  var chartBuilders = {

    // Mixed bar (orders) + line (revenue) — last 30 days
    salesTrend: function (canvas, t) {
      var days = 30;
      var labels = [];
      var orders = [];
      var revenue = [];
      var rand = seedRand(42);
      for (var i = 0; i < days; i++) {
        labels.push('Day ' + (i + 1));
        var base = 60 + Math.sin(i / 2.4) * 22 + rand() * 18;
        var weekend = (i % 7 === 5 || i % 7 === 6) ? 20 : 0;
        var o = Math.round(base + weekend);
        orders.push(o);
        revenue.push(Math.round(o * (34 + rand() * 12)));
      }

      var ctx = canvas.getContext('2d');
      var gradBar = ctx.createLinearGradient(0, 0, 0, 300);
      gradBar.addColorStop(0, 'rgba(6,182,212,.9)');
      gradBar.addColorStop(1, 'rgba(6,182,212,.35)');

      return new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              type: 'bar',
              label: 'Orders',
              data: orders,
              backgroundColor: gradBar,
              borderRadius: 4,
              borderSkipped: false,
              barPercentage: 0.75,
              categoryPercentage: 0.85,
              yAxisID: 'y'
            },
            {
              type: 'line',
              label: 'Revenue',
              data: revenue,
              borderColor: t.pink,
              backgroundColor: 'rgba(236,72,153,.12)',
              borderWidth: 2.5,
              pointRadius: 0,
              pointHoverRadius: 5,
              pointBackgroundColor: t.pink,
              tension: 0.35,
              fill: false,
              yAxisID: 'y1'
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: t.surface,
              titleColor: t.text,
              bodyColor: t.text,
              borderColor: t.border,
              borderWidth: 1,
              padding: 10,
              callbacks: {
                label: function (ctx) {
                  if (ctx.dataset.label === 'Revenue') {
                    return 'Revenue: $' + ctx.parsed.y.toLocaleString();
                  }
                  return 'Orders: ' + ctx.parsed.y;
                }
              }
            }
          },
          scales: {
            x: {
              grid: { display: false, drawBorder: false },
              ticks: {
                color: t.muted, maxRotation: 0,
                autoSkip: true, maxTicksLimit: 8
              }
            },
            y: {
              beginAtZero: true,
              grid: { color: t.grid, drawBorder: false },
              ticks: { color: t.muted, precision: 0 },
              title: { display: false }
            },
            y1: {
              beginAtZero: true,
              position: 'right',
              grid: { drawOnChartArea: false },
              ticks: {
                color: t.muted,
                callback: function (v) { return '$' + (v / 1000).toFixed(1) + 'k'; }
              }
            }
          }
        }
      });
    },

    // Product sparklines
    productSpark: function (canvas, t) {
      var seed = parseInt(canvas.getAttribute('data-shop-seed') || '3', 10);
      var color = canvas.getAttribute('data-shop-color') || t.cyan;
      var rand = seedRand(seed);
      var pts = [];
      for (var i = 0; i < 12; i++) pts.push(20 + rand() * 80);
      var ctx = canvas.getContext('2d');
      return new Chart(ctx, {
        type: 'line',
        data: {
          labels: pts.map(function (_, i) { return i; }),
          datasets: [{
            data: pts,
            borderColor: color,
            backgroundColor: 'transparent',
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.4,
            fill: false
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false }, tooltip: { enabled: false } },
          scales: {
            x: { display: false },
            y: { display: false }
          },
          elements: { line: { capBezierPoints: true } }
        }
      });
    },

    // Payment methods donut (secondary visual)
    paymentDonut: function (canvas, t) {
      var ctx = canvas.getContext('2d');
      return new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Credit Card', 'PayPal', 'Apple Pay', 'Bank Transfer', 'BNPL'],
          datasets: [{
            data: [42, 22, 15, 12, 9],
            backgroundColor: [t.cyan, t.indigo, '#111827', t.emerald, t.pink],
            borderColor: t.surface,
            borderWidth: 3,
            hoverOffset: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '68%',
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: t.surface,
              titleColor: t.text,
              bodyColor: t.text,
              borderColor: t.border,
              borderWidth: 1,
              callbacks: {
                label: function (ctx) { return ' ' + ctx.label + ': ' + ctx.parsed + '%'; }
              }
            }
          }
        }
      });
    }
  };

  // ---------- Build / Destroy pipeline ----------
  function buildAll() {
    var t = readTheme();
    applyDefaults(t);

    document.querySelectorAll('[data-shop-chart]').forEach(function (canvas) {
      var key = canvas.getAttribute('data-shop-chart');
      var builder;
      if (key === 'salesTrend') builder = chartBuilders.salesTrend;
      else if (key === 'paymentDonut') builder = chartBuilders.paymentDonut;
      else if (key.indexOf('productSpark') === 0) builder = chartBuilders.productSpark;
      if (!builder) return;
      try {
        var instance = builder(canvas, t);
        if (instance) instances.set(canvas, instance);
      } catch (e) {
        // Fail-safe: log and continue
        if (window.console && console.warn) console.warn('[shop] chart failed:', key, e);
      }
    });
  }

  function destroyAll() {
    instances.forEach(function (chart) {
      try { chart.destroy(); } catch (e) { /* noop */ }
    });
    instances.clear();
  }

  // ---------- Sales Trend range control (visual demo) ----------
  function bindRangeControl() {
    var inputs = document.querySelectorAll('[data-shop-range] input[type="radio"]');
    inputs.forEach(function (input) {
      input.addEventListener('change', function () {
        // Rebuild just the trend chart with a subtle animation reseed
        var canvas = document.querySelector('[data-shop-chart="salesTrend"]');
        if (!canvas) return;
        var existing = instances.get(canvas);
        if (existing) { try { existing.destroy(); } catch (e) {} instances.delete(canvas); }
        var t = readTheme();
        var chart = chartBuilders.salesTrend(canvas, t);
        instances.set(canvas, chart);
      });
    });
  }

  // ---------- Restock button demo ----------
  function bindRestock() {
    document.querySelectorAll('[data-shop-restock]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        btn.disabled = true;
        var original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2"></i> Queued';
        setTimeout(function () {
          btn.disabled = false;
          btn.innerHTML = original;
        }, 1800);
      });
    });
  }

  // ---------- Init ----------
  function init() {
    renderKpiBars();
    renderDistBars();
    renderPayBar();
    buildAll();
    bindRangeControl();
    bindRestock();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Rebuild charts on theme change
  document.addEventListener('orchid:themechange', function () {
    destroyAll();
    // Small delay so CSS variables settle
    requestAnimationFrame(buildAll);
  });

})();
