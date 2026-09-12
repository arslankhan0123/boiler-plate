/* =====================================================
   Orchid — Projects Dashboard page scripts
   Reads canvases with [data-projects-chart="..."]
   Rebuilds all charts on `orchid:themechange`
   ===================================================== */
(function () {
  'use strict';

  if (typeof window === 'undefined' || typeof document === 'undefined') return;
  if (typeof window.Chart === 'undefined') {
    // Chart.js not present — nothing to bootstrap for canvases.
    animateCapacityBars();
    animateStatusSegments();
    return;
  }

  var Chart = window.Chart;

  // Map of instances so we can destroy/rebuild on theme change
  var instances = new Map();

  // ------------------------------------------------------------
  // Theme palette helpers
  // ------------------------------------------------------------
  function getTheme() {
    return document.documentElement.getAttribute('data-bs-theme') || 'light';
  }
  function palette() {
    var dark = getTheme() === 'dark';
    return {
      dark: dark,
      text:      dark ? '#e6e8f2' : '#1e2436',
      muted:     dark ? '#8891a8' : '#6b7385',
      grid:      dark ? 'rgba(255,255,255,.06)' : 'rgba(15,18,32,.06)',
      tooltipBg: dark ? '#171a2b' : '#1e2436',
      tooltipFg: '#ffffff',
      surface:   dark ? '#171a2b' : '#ffffff',
      trackFill: dark ? 'rgba(255,255,255,.06)' : 'rgba(15,18,32,.06)',
      violet:  '#8b5cf6',
      purple:  '#a855f7',
      teal:    '#14b8a6',
      amber:   '#f59e0b'
    };
  }

  function gradient(ctx, area, colorTop, colorBottom) {
    if (!area) return colorTop;
    var g = ctx.createLinearGradient(0, area.top, 0, area.bottom);
    g.addColorStop(0, colorTop);
    g.addColorStop(1, colorBottom);
    return g;
  }

  function hexToRgba(hex, alpha) {
    var h = hex.replace('#', '');
    if (h.length === 3) h = h.split('').map(function (c) { return c + c; }).join('');
    var n = parseInt(h, 16);
    var r = (n >> 16) & 255, g = (n >> 8) & 255, b = n & 255;
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
  }

  // ------------------------------------------------------------
  // Chart factories per data-projects-chart key
  // ------------------------------------------------------------
  function buildRing(canvas, colorHex) {
    var p = palette();
    var pct = Math.max(0, Math.min(100, parseFloat(canvas.getAttribute('data-ring-pct') || (
      canvas.dataset.projectsChart === 'ringProjects'    ? 72 :
      canvas.dataset.projectsChart === 'ringTasks'       ? 64 :
      canvas.dataset.projectsChart === 'ringMilestones'  ? 64 :
      canvas.dataset.projectsChart === 'ringProductivity'? 87 : 60
    ))));

    return new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: ['Complete', 'Remaining'],
        datasets: [{
          data: [pct, 100 - pct],
          backgroundColor: [colorHex, p.trackFill],
          borderWidth: 0,
          borderRadius: [6, 0],
          spacing: 0,
          hoverOffset: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '78%',
        rotation: -90,
        circumference: 360,
        plugins: {
          legend: { display: false },
          tooltip: { enabled: false }
        },
        animation: { animateRotate: true, duration: 900 }
      }
    });
  }

  function buildTaskCompletion(canvas) {
    var p = palette();
    var ctx = canvas.getContext('2d');
    var chartArea;

    // 30 days of realistic data
    var labels = [];
    var today = new Date(2026, 6, 23); // 2026-07-23
    for (var i = 29; i >= 0; i--) {
      var d = new Date(today);
      d.setDate(today.getDate() - i);
      labels.push(d.getDate() + '/' + (d.getMonth() + 1));
    }
    var created = [12,15,10,14,18,20,16,11,9,13,17,21,19,15,12,18,22,20,17,14,19,23,25,20,18,16,21,24,22,19];
    var completed = [10,12,11,13,15,18,17,14,10,12,16,19,20,17,14,15,19,21,18,16,17,20,22,21,19,17,20,22,21,20];

    return new Chart(canvas, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Completed',
            data: completed,
            borderColor: p.violet,
            backgroundColor: function (c) {
              chartArea = c.chart.chartArea;
              return gradient(ctx, chartArea, hexToRgba(p.violet, .35), hexToRgba(p.violet, 0));
            },
            fill: true,
            tension: .4,
            pointRadius: 0,
            pointHoverRadius: 5,
            pointHoverBackgroundColor: p.violet,
            pointHoverBorderColor: p.surface,
            pointHoverBorderWidth: 2,
            borderWidth: 2.5
          },
          {
            label: 'Created',
            data: created,
            borderColor: p.purple,
            backgroundColor: function (c) {
              chartArea = c.chart.chartArea;
              return gradient(ctx, chartArea, hexToRgba(p.purple, .18), hexToRgba(p.purple, 0));
            },
            fill: true,
            tension: .4,
            pointRadius: 0,
            pointHoverRadius: 5,
            pointHoverBackgroundColor: p.purple,
            pointHoverBorderColor: p.surface,
            pointHoverBorderWidth: 2,
            borderWidth: 2,
            borderDash: [4, 4]
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
            backgroundColor: p.tooltipBg,
            titleColor: p.tooltipFg,
            bodyColor: p.tooltipFg,
            padding: 10,
            borderColor: 'transparent',
            borderWidth: 0,
            displayColors: true,
            boxPadding: 4,
            cornerRadius: 8
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: p.muted, font: { size: 10 }, maxTicksLimit: 8, autoSkip: true }
          },
          y: {
            beginAtZero: true,
            grid: { color: p.grid, drawBorder: false },
            ticks: { color: p.muted, font: { size: 10 }, stepSize: 5 }
          }
        }
      }
    });
  }

  var CHART_BUILDERS = {
    ringProjects:     function (c) { return buildRing(c, c.getAttribute('data-ring-color') || '#8b5cf6'); },
    ringTasks:        function (c) { return buildRing(c, c.getAttribute('data-ring-color') || '#a855f7'); },
    ringMilestones:   function (c) { return buildRing(c, c.getAttribute('data-ring-color') || '#14b8a6'); },
    ringProductivity: function (c) { return buildRing(c, c.getAttribute('data-ring-color') || '#f59e0b'); },
    taskCompletion:   buildTaskCompletion
  };

  // ------------------------------------------------------------
  // Build / rebuild lifecycle
  // ------------------------------------------------------------
  function buildAll() {
    var canvases = document.querySelectorAll('[data-projects-chart]');
    canvases.forEach(function (canvas) {
      var key = canvas.getAttribute('data-projects-chart');
      var factory = CHART_BUILDERS[key];
      if (!factory) return;
      try {
        var inst = factory(canvas);
        if (inst) instances.set(canvas, inst);
      } catch (err) {
        if (window.console) console.warn('[projects] chart build failed for', key, err);
      }
    });
  }

  function destroyAll() {
    instances.forEach(function (inst) {
      try { inst.destroy(); } catch (e) { /* noop */ }
    });
    instances.clear();
  }

  function rebuildAll() {
    destroyAll();
    buildAll();
  }

  // ------------------------------------------------------------
  // CSS-based visualisations
  // ------------------------------------------------------------
  function animateCapacityBars() {
    var els = document.querySelectorAll('[data-projects-capacity]');
    // Give the browser a paint tick so the transition runs
    window.requestAnimationFrame(function () {
      window.setTimeout(function () {
        els.forEach(function (el) {
          var v = Math.max(0, Math.min(100, parseFloat(el.getAttribute('data-projects-capacity')) || 0));
          el.style.width = v + '%';
        });
      }, 60);
    });
  }

  function animateStatusSegments() {
    // Ratios (values must sum to total active projects: 23)
    var segments = {
      ontrack: 8,
      risk:    4,
      delayed: 2,
      done:    4,
      pending: 3,
      hold:    2
    };
    var total = 0;
    Object.keys(segments).forEach(function (k) { total += segments[k]; });
    if (total === 0) return;

    Object.keys(segments).forEach(function (key) {
      var el = document.querySelector('[data-projects-status-seg="' + key + '"]');
      if (!el) return;
      var pct = (segments[key] / total) * 100;
      // Set width via style (dynamically computed, not a hard-coded style attr in HTML)
      el.style.width = pct.toFixed(2) + '%';
    });
  }

  // ------------------------------------------------------------
  // Boot
  // ------------------------------------------------------------
  function boot() {
    animateStatusSegments();
    animateCapacityBars();
    buildAll();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  document.addEventListener('orchid:themechange', rebuildAll);

  // Also observe direct attribute changes to data-bs-theme as a fallback
  try {
    var mo = new MutationObserver(function (mutations) {
      for (var i = 0; i < mutations.length; i++) {
        if (mutations[i].attributeName === 'data-bs-theme') {
          rebuildAll();
          return;
        }
      }
    });
    mo.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
  } catch (e) { /* noop */ }

})();
