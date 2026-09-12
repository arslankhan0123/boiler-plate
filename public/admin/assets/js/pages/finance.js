/* =====================================================
   Orchid - Finance Dashboard page scripts
   Charts + theme-aware recolor
   ===================================================== */
(function () {
  'use strict';

  if (typeof window.Chart === 'undefined') return;

  var instances = new Map();

  // ---------- Theme helpers ----------
  function currentTheme() {
    return document.documentElement.getAttribute('data-bs-theme') || 'light';
  }
  function palette() {
    var isDark = currentTheme() === 'dark';
    return {
      emerald: '#10b981',
      emeraldSoft: isDark ? 'rgba(16,185,129,.28)' : 'rgba(16,185,129,.18)',
      indigo: isDark ? '#818cf8' : '#4f46e5',
      indigoSoft: isDark ? 'rgba(129,140,248,.30)' : 'rgba(79,70,229,.18)',
      amber: '#f59e0b',
      amberSoft: isDark ? 'rgba(245,158,11,.30)' : 'rgba(245,158,11,.20)',
      rose: '#e11d48',
      slate: isDark ? '#94a3b8' : '#64748b',
      grid: isDark ? 'rgba(255,255,255,.06)' : 'rgba(15,18,32,.06)',
      text: isDark ? '#8891a8' : '#6b7385',
      surface: isDark ? '#171a2b' : '#ffffff',
      donut: ['#4f46e5', '#10b981', '#f59e0b', '#0ea5e9', '#ec4899', '#14b8a6']
    };
  }

  // ---------- Number helper ----------
  function fmt(n) {
    return '$' + Math.round(n).toLocaleString('en-US');
  }

  // ---------- Chart builders ----------
  function buildSpark(ctx, values, color) {
    return new Chart(ctx, {
      type: 'bar',
      data: {
        labels: values.map(function (_, i) { return 'W' + (i + 1); }),
        datasets: [{
          data: values,
          backgroundColor: color,
          borderRadius: 3,
          barPercentage: 0.65,
          categoryPercentage: 0.75
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: {
          x: { display: false },
          y: { display: false, beginAtZero: true }
        }
      }
    });
  }

  function buildIncomeExpense(ctx) {
    var p = palette();
    var labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var income  = [72, 78, 84, 79, 91, 96, 102, 110, 115, 108, 122, 128];
    var expense = [48, 52, 55, 58, 60, 62, 65, 68, 71, 74, 76, 78];
    return new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Income',
            data: income,
            borderColor: p.emerald,
            backgroundColor: p.emeraldSoft,
            fill: true,
            tension: 0.4,
            pointRadius: 0,
            pointHoverRadius: 4,
            borderWidth: 2.2
          },
          {
            label: 'Expense',
            data: expense,
            borderColor: p.amber,
            backgroundColor: 'transparent',
            fill: false,
            tension: 0.4,
            pointRadius: 0,
            pointHoverRadius: 4,
            borderWidth: 2.2,
            borderDash: [4, 3]
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
            backgroundColor: p.surface,
            titleColor: p.slate,
            bodyColor: p.slate,
            borderColor: p.grid,
            borderWidth: 1,
            padding: 10,
            callbacks: {
              label: function (ctx) {
                return ctx.dataset.label + ': $' + ctx.parsed.y + 'k';
              }
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: p.text, font: { size: 11 } }
          },
          y: {
            grid: { color: p.grid, drawBorder: false },
            ticks: {
              color: p.text,
              font: { size: 11 },
              callback: function (v) { return '$' + v + 'k'; }
            }
          }
        }
      }
    });
  }

  function buildExpenseDonut(ctx) {
    var p = palette();
    return new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Payroll','SaaS','Office Rent','Marketing','Travel','Utilities'],
        datasets: [{
          data: [42800, 12400, 9600, 7200, 4800, 3200],
          backgroundColor: p.donut,
          borderColor: p.surface,
          borderWidth: 3,
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: p.surface,
            titleColor: p.slate,
            bodyColor: p.slate,
            borderColor: p.grid,
            borderWidth: 1,
            padding: 10,
            callbacks: {
              label: function (ctx) {
                return ctx.label + ': ' + fmt(ctx.parsed);
              }
            }
          }
        }
      }
    });
  }

  // ---------- Data attribute registry ----------
  var builders = {
    incomeExpense: buildIncomeExpense,
    expenseDonut: buildExpenseDonut,
    sparkCash:    function (ctx) { return buildSpark(ctx, [14,18,22,19,26,24,30], palette().emerald); },
    sparkIncome:  function (ctx) { return buildSpark(ctx, [8,12,10,16,14,20,22],  palette().emerald); },
    sparkExpense: function (ctx) { return buildSpark(ctx, [7,9,8,12,10,14,16],    palette().amber); },
    sparkProfit:  function (ctx) { return buildSpark(ctx, [4,6,5,9,10,12,15],     palette().indigo); }
  };

  // ---------- Init ----------
  function init() {
    var canvases = document.querySelectorAll('[data-finance-chart]');
    canvases.forEach(function (canvas) {
      var key = canvas.getAttribute('data-finance-chart');
      var builder = builders[key];
      if (!builder) return;
      var ctx = canvas.getContext('2d');
      var chart = builder(ctx);
      if (chart) instances.set(canvas, { key: key, chart: chart });
    });

    // Animate budget bars in
    requestAnimationFrame(function () {
      document.querySelectorAll('.finance-budget__fill[data-fill]').forEach(function (el) {
        el.style.width = el.getAttribute('data-fill') + '%';
      });
    });
  }

  // ---------- Rebuild on theme change ----------
  function rebuildAll() {
    instances.forEach(function (entry, canvas) {
      entry.chart.destroy();
      var builder = builders[entry.key];
      if (!builder) return;
      var chart = builder(canvas.getContext('2d'));
      instances.set(canvas, { key: entry.key, chart: chart });
    });
  }

  // ---------- Invoice filter ----------
  function initInvoiceFilter() {
    var buttons = document.querySelectorAll('[data-finance-filter]');
    var items = document.querySelectorAll('[data-finance-status]');
    var label = document.querySelector('[data-finance-filter-label]');
    buttons.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var status = btn.getAttribute('data-finance-filter');
        if (label) label.textContent = btn.textContent.trim();
        items.forEach(function (li) {
          var itemStatus = li.getAttribute('data-finance-status');
          li.style.display = (status === 'all' || status === itemStatus) ? '' : 'none';
        });
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { init(); initInvoiceFilter(); });
  } else {
    init();
    initInvoiceFilter();
  }

  document.addEventListener('orchid:themechange', rebuildAll);
})();
