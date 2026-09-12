/* =====================================================
   Orchid — HR Dashboard Page Scripts
   Charts: Chart.js v4 (global `Chart`)
   Rebuilds all instances on `orchid:themechange`
   ===================================================== */
(function () {
  'use strict';

  if (typeof window === 'undefined') return;

  var HR_PALETTE = {
    rose:  '#f43f5e',
    coral: '#fb7185',
    peach: '#fdba74',
    amber: '#f59e0b',
    mint:  '#34d399',
    green: '#10b981',
    sky:   '#38bdf8',
    plum:  '#a21caf',
    fuchsia: '#d946ef',
    slate: '#64748b'
  };

  var DEPT_COLORS = {
    engineering: '#f43f5e',
    design:      '#fb7185',
    product:     '#fdba74',
    sales:       '#a21caf',
    marketing:   '#38bdf8',
    ops:         '#34d399'
  };

  var BIRTHDAY_AVATAR_BG = {
    rose:  '#f43f5e',
    amber: '#f59e0b',
    mint:  '#10b981',
    sky:   '#0ea5e9'
  };

  // Registry of live Chart instances so we can destroy + rebuild on theme change
  var instances = new Map();

  // ---- Utilities ----
  function themeColors() {
    var styles = getComputedStyle(document.documentElement);
    var text = styles.getPropertyValue('--orchid-text').trim() || '#1e2436';
    var muted = styles.getPropertyValue('--orchid-text-muted').trim() || '#6b7385';
    var border = styles.getPropertyValue('--orchid-border').trim() || '#e9ecf3';
    var surface = styles.getPropertyValue('--orchid-surface').trim() || '#ffffff';
    return { text: text, muted: muted, border: border, surface: surface };
  }

  function withAlpha(hex, alpha) {
    var h = hex.replace('#', '');
    if (h.length === 3) h = h.split('').map(function (c) { return c + c; }).join('');
    var r = parseInt(h.substr(0, 2), 16);
    var g = parseInt(h.substr(2, 2), 16);
    var b = parseInt(h.substr(4, 2), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
  }

  function baseTooltip(colors) {
    return {
      backgroundColor: colors.surface,
      titleColor: colors.text,
      bodyColor: colors.muted,
      borderColor: colors.border,
      borderWidth: 1,
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

  // ---- Chart builders ----
  function buildSparkline(ctx, data, color) {
    var canvas = ctx.canvas;
    var gradient = ctx.createLinearGradient(0, 0, 0, canvas.height || 40);
    gradient.addColorStop(0, withAlpha(color, 0.35));
    gradient.addColorStop(1, withAlpha(color, 0));

    return new Chart(ctx, {
      type: 'line',
      data: {
        labels: data.map(function (_, i) { return i; }),
        datasets: [{
          data: data,
          borderColor: color,
          backgroundColor: gradient,
          borderWidth: 2,
          tension: 0.4,
          fill: true,
          pointRadius: 0,
          pointHoverRadius: 3,
          pointHoverBackgroundColor: color
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
        elements: { line: { borderJoinStyle: 'round' } }
      }
    });
  }

  function buildAttendance(ctx) {
    var colors = themeColors();
    var labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    return new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Present',
            data: [168, 172, 165, 170, 174, 96, 42],
            backgroundColor: HR_PALETTE.rose,
            borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
            borderSkipped: false,
            stack: 'a',
            barPercentage: 0.55,
            categoryPercentage: 0.7
          },
          {
            label: 'Late',
            data: [8, 6, 10, 7, 5, 3, 1],
            backgroundColor: HR_PALETTE.amber,
            stack: 'a',
            barPercentage: 0.55,
            categoryPercentage: 0.7
          },
          {
            label: 'Absent',
            data: [4, 2, 5, 3, 1, 2, 0],
            backgroundColor: HR_PALETTE.slate,
            borderRadius: { topLeft: 0, topRight: 0, bottomLeft: 6, bottomRight: 6 },
            borderSkipped: false,
            stack: 'a',
            barPercentage: 0.55,
            categoryPercentage: 0.7
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: Object.assign(baseTooltip(colors), {
            callbacks: {
              label: function (item) {
                return item.dataset.label + ': ' + item.formattedValue;
              }
            }
          })
        },
        scales: {
          x: {
            stacked: true,
            grid: { display: false },
            border: { display: false },
            ticks: { color: colors.muted, font: { size: 11, weight: '600' } }
          },
          y: {
            stacked: true,
            grid: { color: withAlpha(colors.border, 0.6), drawBorder: false },
            border: { display: false },
            ticks: { color: colors.muted, font: { size: 11 }, stepSize: 50 }
          }
        }
      }
    });
  }

  function buildDepartments(ctx) {
    var colors = themeColors();
    return new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Engineering', 'Design', 'Product', 'Sales', 'Marketing', 'People Ops'],
        datasets: [{
          data: [62, 22, 18, 34, 24, 20],
          backgroundColor: [
            DEPT_COLORS.engineering,
            DEPT_COLORS.design,
            DEPT_COLORS.product,
            DEPT_COLORS.sales,
            DEPT_COLORS.marketing,
            DEPT_COLORS.ops
          ],
          borderColor: colors.surface,
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
          tooltip: Object.assign(baseTooltip(colors), {
            callbacks: {
              label: function (item) {
                return item.label + ': ' + item.formattedValue + ' people';
              }
            }
          })
        }
      }
    });
  }

  // ---- Chart lifecycle ----
  function destroyAll() {
    instances.forEach(function (chart) {
      try { chart.destroy(); } catch (e) { /* ignore */ }
    });
    instances.clear();
  }

  function buildAll() {
    var canvases = document.querySelectorAll('[data-hr-chart]');
    canvases.forEach(function (canvas) {
      var key = canvas.getAttribute('data-hr-chart');
      var ctx = canvas.getContext('2d');
      var chart = null;
      switch (key) {
        case 'sparkEmployees':
          chart = buildSparkline(ctx, [150, 155, 158, 162, 166, 172, 176, 180], HR_PALETTE.rose);
          break;
        case 'sparkAttendance':
          chart = buildSparkline(ctx, [88, 91, 93, 92, 94, 95, 94.6], HR_PALETTE.mint);
          break;
        case 'sparkLeave':
          chart = buildSparkline(ctx, [18, 16, 14, 15, 13, 12, 12], HR_PALETTE.amber);
          break;
        case 'sparkRecruitment':
          chart = buildSparkline(ctx, [10, 11, 13, 14, 16, 17, 18], HR_PALETTE.plum);
          break;
        case 'attendance':
          chart = buildAttendance(ctx);
          break;
        case 'departments':
          chart = buildDepartments(ctx);
          break;
        default:
          break;
      }
      if (chart) instances.set(key, chart);
    });
  }

  // ---- Non-chart visual polish ----
  function paintLegendDots() {
    var dotColors = {
      present: HR_PALETTE.rose,
      late:    HR_PALETTE.amber,
      absent:  HR_PALETTE.slate
    };
    document.querySelectorAll('.hr-legend__dot[data-legend]').forEach(function (el) {
      var key = el.getAttribute('data-legend');
      if (dotColors[key]) el.style.backgroundColor = dotColors[key];
    });
  }

  function paintDeptDots() {
    document.querySelectorAll('.hr-dept-dot[data-dept]').forEach(function (el) {
      var key = el.getAttribute('data-dept');
      if (DEPT_COLORS[key]) el.style.backgroundColor = DEPT_COLORS[key];
    });
  }

  function paintBirthdayAvatars() {
    document.querySelectorAll('[data-hr-avatar]').forEach(function (el) {
      var key = el.getAttribute('data-hr-avatar');
      if (BIRTHDAY_AVATAR_BG[key]) el.style.backgroundColor = BIRTHDAY_AVATAR_BG[key];
    });
  }

  function paintPayrollBars() {
    var palette = {
      engineering: 'linear-gradient(90deg, #f43f5e, #fb7185)',
      sales:       'linear-gradient(90deg, #a21caf, #d946ef)',
      design:      'linear-gradient(90deg, #fb7185, #fdba74)',
      product:     'linear-gradient(90deg, #f59e0b, #fbbf24)',
      marketing:   'linear-gradient(90deg, #0ea5e9, #38bdf8)',
      ops:         'linear-gradient(90deg, #10b981, #34d399)'
    };
    document.querySelectorAll('[data-hr-payroll]').forEach(function (el) {
      var key = el.getAttribute('data-hr-payroll');
      var w = el.getAttribute('data-hr-width') || '0';
      el.style.background = palette[key] || palette.engineering;
      // small stagger for a bit of life
      el.style.width = '0%';
      requestAnimationFrame(function () {
        el.style.transition = 'width 900ms cubic-bezier(.4,0,.2,1)';
        el.style.width = w + '%';
      });
    });
  }

  function paintPipelineBars() {
    document.querySelectorAll('[data-hr-progress]').forEach(function (el) {
      var w = el.getAttribute('data-hr-progress') || '0';
      el.style.width = '0%';
      requestAnimationFrame(function () {
        el.style.transition = 'width 900ms cubic-bezier(.4,0,.2,1)';
        el.style.width = w + '%';
      });
    });
  }

  // ---- People filter (search + dept) ----
  function wireFilters() {
    var search = document.getElementById('hrPeopleSearch');
    var dept = document.getElementById('hrDeptFilter');
    var birthdays = document.querySelectorAll('.hr-birthday');
    var timeline = document.querySelectorAll('.hr-timeline__item');

    function apply() {
      var q = (search && search.value || '').trim().toLowerCase();
      var d = (dept && dept.value || 'all').toLowerCase();

      birthdays.forEach(function (card) {
        var text = card.innerText.toLowerCase();
        var matchesQuery = !q || text.indexOf(q) !== -1;
        var matchesDept = d === 'all' || text.indexOf(d) !== -1;
        card.style.display = (matchesQuery && matchesDept) ? '' : 'none';
      });

      timeline.forEach(function (item) {
        var text = item.innerText.toLowerCase();
        var matchesQuery = !q || text.indexOf(q) !== -1;
        item.style.display = matchesQuery ? '' : 'none';
      });
    }

    if (search) search.addEventListener('input', apply);
    if (dept) dept.addEventListener('change', apply);
  }

  // ---- Init + theme hook ----
  function init() {
    if (typeof Chart === 'undefined') return;
    paintLegendDots();
    paintDeptDots();
    paintBirthdayAvatars();
    paintPayrollBars();
    paintPipelineBars();
    buildAll();
    wireFilters();
  }

  function rebuild() {
    destroyAll();
    buildAll();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  window.addEventListener('orchid:themechange', rebuild);
})();
