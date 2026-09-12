/* =====================================================
   Orchid — Charts Showcase (shared toolkit)
   - Palette + theme helpers
   - Gradient helpers
   - Chart registry with theme-change rebuild
   - Toast, clipboard, code preview
   - Right-rail TOC scroll spy
   - Per-page chart config lookup via data-charts-page
   ===================================================== */
(function () {
  'use strict';

  /* ---------- Palette ---------- */
  const PALETTE = {
    indigo:  '#4f46e5',
    violet:  '#a855f7',
    cyan:    '#22d3ee',
    emerald: '#10b981',
    amber:   '#f59e0b',
    rose:    '#f43f5e',
    sky:     '#0ea5e9',
    orange:  '#f97316',
  };
  const PALETTE_LIST = [
    PALETTE.indigo, PALETTE.cyan, PALETTE.violet, PALETTE.emerald,
    PALETTE.amber, PALETTE.rose, PALETTE.sky, PALETTE.orange,
  ];

  const isDark = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';
  const gridColor = () => (isDark() ? 'rgba(255,255,255,.06)' : 'rgba(15,18,32,.06)');
  const tickColor = () => (isDark() ? '#8891a8' : '#6b7385');
  const surface = () => (isDark() ? '#171a2b' : '#ffffff');
  const textColor = () => (isDark() ? '#e6e8f2' : '#1e2436');
  const baseFont = { family: 'Inter, sans-serif', size: 11, weight: '500' };

  function hexToRgba(hex, alpha) {
    const h = hex.replace('#', '');
    const r = parseInt(h.substring(0, 2), 16);
    const g = parseInt(h.substring(2, 4), 16);
    const b = parseInt(h.substring(4, 6), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  function gradientFill(color, from, to) {
    return function (context) {
      const chart = context.chart;
      const { ctx, chartArea } = chart;
      if (!chartArea) return hexToRgba(color, from);
      const g = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
      g.addColorStop(0, hexToRgba(color, from));
      g.addColorStop(1, hexToRgba(color, to));
      return g;
    };
  }

  function gradientBarVertical(colorA, colorB) {
    return function (context) {
      const chart = context.chart;
      const { ctx, chartArea } = chart;
      if (!chartArea) return colorA;
      const g = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
      g.addColorStop(0, colorA);
      g.addColorStop(1, colorB);
      return g;
    };
  }

  /* ---------- Base options factory ---------- */
  function baseOptions(overrides) {
    const base = {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: {
          display: false,
          position: 'top',
          align: 'end',
          labels: { color: tickColor(), font: baseFont, boxWidth: 8, boxHeight: 8, usePointStyle: true, padding: 12 },
        },
        tooltip: {
          backgroundColor: surface(),
          titleColor: textColor(),
          bodyColor: textColor(),
          borderColor: isDark() ? '#232842' : '#e9ecf3',
          borderWidth: 1,
          padding: 10,
          titleFont: { ...baseFont, weight: '600' },
          bodyFont: baseFont,
          cornerRadius: 8,
          displayColors: true,
          boxPadding: 4,
        },
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: tickColor(), font: baseFont } },
        y: { grid: { color: gridColor(), drawBorder: false }, ticks: { color: tickColor(), font: baseFont } },
      },
    };
    return deepMerge(base, overrides || {});
  }

  function deepMerge(target, source) {
    const out = Array.isArray(target) ? target.slice() : Object.assign({}, target);
    Object.keys(source || {}).forEach((k) => {
      const sv = source[k];
      const tv = out[k];
      if (sv && typeof sv === 'object' && !Array.isArray(sv) && tv && typeof tv === 'object' && !Array.isArray(tv)) {
        out[k] = deepMerge(tv, sv);
      } else {
        out[k] = sv;
      }
    });
    return out;
  }

  /* ---------- Registry ---------- */
  const registry = new Map();          // canvas -> chart instance
  const configFns = new Map();         // key -> factory that returns Chart config
  const instanceMeta = new Map();      // canvas -> key
  const advancedHooks = [];            // functions to run after building charts

  function registerConfig(key, factory) {
    configFns.set(key, factory);
  }

  function registerAdvancedHook(fn) {
    advancedHooks.push(fn);
  }

  function initChart(canvas, config) {
    if (!canvas || typeof Chart === 'undefined') return null;
    const inst = new Chart(canvas, config);
    registry.set(canvas, inst);
    return inst;
  }

  function destroyAll() {
    registry.forEach((c) => { try { c.destroy(); } catch (_) { /* noop */ } });
    registry.clear();
  }

  function buildAll() {
    if (typeof Chart === 'undefined') return;
    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.color = tickColor();

    document.querySelectorAll('[data-charts-chart]').forEach((canvas) => {
      const key = canvas.getAttribute('data-charts-chart');
      const factory = configFns.get(key);
      if (!factory) return;
      instanceMeta.set(canvas, key);
      const cfg = factory(canvas);
      if (cfg) initChart(canvas, cfg);
    });

    advancedHooks.forEach((fn) => {
      try { fn(); } catch (e) { /* keep other hooks running */ }
    });
  }

  /* ---------- Toast ---------- */
  function ensureToastStack() {
    let stack = document.querySelector('.charts-toast-stack');
    if (!stack) {
      stack = document.createElement('div');
      stack.className = 'charts-toast-stack';
      stack.setAttribute('aria-live', 'polite');
      stack.setAttribute('aria-atomic', 'true');
      document.body.appendChild(stack);
    }
    return stack;
  }

  function orchidToast(title, body, variant) {
    const stack = ensureToastStack();
    const t = document.createElement('div');
    t.className = 'charts-toast' + (variant ? ' charts-toast--' + variant : '');
    const iconMap = { success: 'check-circle', warning: 'exclamation-triangle', danger: 'x-circle' };
    const iconName = iconMap[variant] || 'info-circle';
    t.innerHTML = ''
      + '<span class="charts-toast__icon"><i class="bi bi-' + iconName + '"></i></span>'
      + '<div>'
      +   '<p class="charts-toast__title"></p>'
      +   '<p class="charts-toast__body"></p>'
      + '</div>';
    t.querySelector('.charts-toast__title').textContent = title;
    t.querySelector('.charts-toast__body').textContent = body || '';
    stack.appendChild(t);
    requestAnimationFrame(() => t.classList.add('is-visible'));
    setTimeout(() => {
      t.classList.remove('is-visible');
      setTimeout(() => t.remove(), 260);
    }, 2600);
  }

  /* ---------- Clipboard + code preview ---------- */
  function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (_) { /* noop */ }
    ta.remove();
    return Promise.resolve();
  }

  function initPreviews() {
    document.querySelectorAll('[data-charts-preview-toggle]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const preview = btn.closest('.charts-preview');
        if (!preview) return;
        const open = preview.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        const icon = btn.querySelector('i');
        if (icon) icon.className = open ? 'bi bi-eye-slash' : 'bi bi-code-slash';
      });
    });

    document.querySelectorAll('[data-charts-copy]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const preview = btn.closest('.charts-preview');
        const code = preview ? preview.querySelector('pre') : null;
        if (!code) return;
        copyToClipboard(code.textContent).then(() => {
          orchidToast('Copied', 'Snippet copied to clipboard.', 'success');
        });
      });
    });
  }

  /* ---------- TOC scroll spy ---------- */
  function initTocScrollSpy() {
    const links = document.querySelectorAll('.charts-toc__link, .charts-toc-mobile__link');
    if (!links.length) return;
    const sections = Array.from(document.querySelectorAll('.charts-section'));
    if (!sections.length) return;

    const setActive = (id) => {
      links.forEach((l) => {
        const href = l.getAttribute('href');
        if (href === '#' + id) l.classList.add('is-active');
        else l.classList.remove('is-active');
      });
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) setActive(entry.target.id);
      });
    }, { rootMargin: '-40% 0px -50% 0px', threshold: 0 });

    sections.forEach((s) => { if (s.id) observer.observe(s); });

    links.forEach((l) => {
      l.addEventListener('click', (e) => {
        const href = l.getAttribute('href');
        if (!href || !href.startsWith('#')) return;
        const target = document.getElementById(href.slice(1));
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          setActive(href.slice(1));
        }
      });
    });
  }

  /* ---------- Expose API ---------- */
  const api = {
    PALETTE, PALETTE_LIST,
    isDark, gridColor, tickColor, surface, textColor,
    hexToRgba, gradientFill, gradientBarVertical,
    baseOptions, deepMerge,
    registerConfig, registerAdvancedHook,
    initChart, destroyAll, buildAll, registry, instanceMeta,
    orchidToast, copyToClipboard,
  };
  window.OrchidCharts = api;

  /* ---------- Section factories per page ---------- */
  const currentPage = () => (document.body.getAttribute('data-charts-page') || '');

  function daysAgoLabels(n) {
    const arr = [];
    const now = new Date();
    for (let i = n - 1; i >= 0; i--) {
      const d = new Date(now);
      d.setDate(now.getDate() - i);
      arr.push(d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }));
    }
    return arr;
  }
  function randSeries(len, min, max) {
    const arr = [];
    for (let i = 0; i < len; i++) arr.push(Math.round(min + Math.random() * (max - min)));
    return arr;
  }

  /* =============== LINE PAGE =============== */
  function registerLine() {
    registerConfig('line-basic', () => ({
      type: 'line',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [{
          label: 'Visitors',
          data: [420, 480, 520, 610, 720, 690, 780, 820, 870, 940, 990, 1080],
          borderColor: PALETTE.indigo,
          backgroundColor: hexToRgba(PALETTE.indigo, .15),
          borderWidth: 2,
          tension: 0,
          pointRadius: 3,
          pointBackgroundColor: PALETTE.indigo,
        }],
      },
      options: baseOptions(),
    }));

    registerConfig('line-multi', () => ({
      type: 'line',
      data: {
        labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        datasets: [
          { label: 'Sessions',   data: [820, 940, 1020, 1180, 1240, 1050, 890], borderColor: PALETTE.indigo,  backgroundColor: hexToRgba(PALETTE.indigo,.1),  tension: .35, borderWidth: 2, pointRadius: 2 },
          { label: 'Signups',    data: [120, 145, 168,  201,  240,  190,  150], borderColor: PALETTE.emerald, backgroundColor: hexToRgba(PALETTE.emerald,.1), tension: .35, borderWidth: 2, pointRadius: 2 },
          { label: 'Conversions',data: [42,  55,  62,   74,   88,   68,   50 ], borderColor: PALETTE.amber,   backgroundColor: hexToRgba(PALETTE.amber,.1),   tension: .35, borderWidth: 2, pointRadius: 2 },
        ],
      },
      options: baseOptions({ plugins: { legend: { display: true } } }),
    }));

    registerConfig('line-smooth', () => ({
      type: 'line',
      data: {
        labels: ['W1','W2','W3','W4','W5','W6','W7','W8'],
        datasets: [{
          label: 'Smoothed',
          data: [24, 32, 28, 44, 38, 56, 48, 62],
          borderColor: PALETTE.violet,
          backgroundColor: hexToRgba(PALETTE.violet,.15),
          borderWidth: 2.5,
          tension: .5,
          pointRadius: 0,
          fill: false,
        }],
      },
      options: baseOptions(),
    }));

    registerConfig('line-stepped', () => ({
      type: 'line',
      data: {
        labels: ['09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00'],
        datasets: [{
          label: 'Servers online',
          data: [12, 14, 14, 18, 22, 22, 20, 24],
          borderColor: PALETTE.emerald,
          backgroundColor: hexToRgba(PALETTE.emerald,.12),
          stepped: true,
          borderWidth: 2,
          pointRadius: 3,
          fill: true,
        }],
      },
      options: baseOptions(),
    }));

    registerConfig('line-dashed', () => ({
      type: 'line',
      data: {
        labels: ['Q1','Q2','Q3','Q4'],
        datasets: [
          { label: 'Actual',   data: [120, 180, 240, 320], borderColor: PALETTE.indigo, borderWidth: 2.5, tension: .3, pointRadius: 3 },
          { label: 'Target',   data: [140, 200, 260, 340], borderColor: PALETTE.amber,  borderWidth: 2,   tension: .3, pointRadius: 0, borderDash: [6,4] },
          { label: 'Forecast', data: [110, 170, 235, 330], borderColor: PALETTE.rose,   borderWidth: 2,   tension: .3, pointRadius: 0, borderDash: [2,3] },
        ],
      },
      options: baseOptions({ plugins: { legend: { display: true } } }),
    }));

    registerConfig('line-fill', () => ({
      type: 'line',
      data: {
        labels: daysAgoLabels(14),
        datasets: [{
          label: 'Revenue',
          data: [220, 280, 260, 320, 380, 340, 420, 460, 440, 520, 580, 540, 620, 680],
          borderColor: PALETTE.cyan,
          backgroundColor: gradientFill(PALETTE.cyan, .35, 0),
          borderWidth: 2,
          tension: .4,
          fill: true,
          pointRadius: 0,
        }],
      },
      options: baseOptions(),
    }));

    registerConfig('line-labeled', () => ({
      type: 'line',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun'],
        datasets: [{
          label: 'Users',
          data: [420, 520, 610, 780, 940, 1150],
          borderColor: PALETTE.indigo,
          backgroundColor: hexToRgba(PALETTE.indigo,.12),
          borderWidth: 2,
          tension: .35,
          pointRadius: 5,
          pointBackgroundColor: PALETTE.indigo,
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
        }],
      },
      options: baseOptions({
        plugins: {
          tooltip: { enabled: true },
        },
      }),
      plugins: [{
        id: 'valueLabels',
        afterDatasetsDraw(chart) {
          const { ctx } = chart;
          chart.data.datasets.forEach((ds, di) => {
            const meta = chart.getDatasetMeta(di);
            meta.data.forEach((pt, i) => {
              ctx.save();
              ctx.fillStyle = textColor();
              ctx.font = '600 10px Inter, sans-serif';
              ctx.textAlign = 'center';
              ctx.fillText(String(ds.data[i]), pt.x, pt.y - 10);
              ctx.restore();
            });
          });
        },
      }],
    }));

    registerConfig('line-annotation', () => {
      const data = [42, 55, 68, 52, 78, 92, 84, 106, 88, 74, 96, 110];
      const maxIdx = data.indexOf(Math.max.apply(null, data));
      const minIdx = data.indexOf(Math.min.apply(null, data));
      return {
        type: 'line',
        data: {
          labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
          datasets: [{
            label: 'Signups',
            data: data,
            borderColor: PALETTE.indigo,
            backgroundColor: gradientFill(PALETTE.indigo, .25, 0),
            borderWidth: 2,
            tension: .35,
            fill: true,
            pointRadius: data.map((_, i) => (i === maxIdx || i === minIdx ? 6 : 0)),
            pointBackgroundColor: data.map((_, i) => (i === maxIdx ? PALETTE.emerald : i === minIdx ? PALETTE.rose : PALETTE.indigo)),
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
          }],
        },
        options: baseOptions(),
        plugins: [{
          id: 'peakTroughLabels',
          afterDatasetsDraw(chart) {
            const meta = chart.getDatasetMeta(0);
            const ctx = chart.ctx;
            const draw = (i, label, color) => {
              const pt = meta.data[i];
              if (!pt) return;
              ctx.save();
              ctx.fillStyle = color;
              ctx.font = '700 10px Inter, sans-serif';
              ctx.textAlign = 'center';
              ctx.fillText(label, pt.x, pt.y - 12);
              ctx.restore();
            };
            draw(maxIdx, 'Peak', PALETTE.emerald);
            draw(minIdx, 'Trough', PALETTE.rose);
          },
        }],
      };
    });

    registerConfig('line-time', () => ({
      type: 'line',
      data: {
        labels: daysAgoLabels(30),
        datasets: [{
          label: 'Active users',
          data: randSeries(30, 320, 720),
          borderColor: PALETTE.sky,
          backgroundColor: gradientFill(PALETTE.sky, .3, 0),
          fill: true,
          borderWidth: 2,
          tension: .3,
          pointRadius: 0,
        }],
      },
      options: baseOptions({ scales: { x: { ticks: { maxTicksLimit: 8 } } } }),
    }));
  }

  /* =============== BAR PAGE =============== */
  function registerBar() {
    registerConfig('bar-basic', () => ({
      type: 'bar',
      data: {
        labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        datasets: [{
          label: 'Orders',
          data: [42, 58, 66, 74, 82, 60, 48],
          backgroundColor: PALETTE.indigo,
          borderRadius: 6,
          barPercentage: .55,
        }],
      },
      options: baseOptions(),
    }));

    registerConfig('bar-horizontal', () => ({
      type: 'bar',
      data: {
        labels: ['USA','UK','Germany','France','Japan','Canada','Brazil'],
        datasets: [{
          label: 'Revenue',
          data: [520, 380, 340, 280, 260, 220, 190],
          backgroundColor: PALETTE.cyan,
          borderRadius: 6,
        }],
      },
      options: baseOptions({ indexAxis: 'y' }),
    }));

    registerConfig('bar-grouped', () => ({
      type: 'bar',
      data: {
        labels: ['Q1','Q2','Q3','Q4'],
        datasets: [
          { label: 'Product A', data: [120, 180, 210, 280], backgroundColor: PALETTE.indigo, borderRadius: 5 },
          { label: 'Product B', data: [90, 140, 170, 220],  backgroundColor: PALETTE.cyan,   borderRadius: 5 },
          { label: 'Product C', data: [60, 110, 140, 180],  backgroundColor: PALETTE.violet, borderRadius: 5 },
        ],
      },
      options: baseOptions({ plugins: { legend: { display: true } } }),
    }));

    registerConfig('bar-stacked', () => ({
      type: 'bar',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun'],
        datasets: [
          { label: 'Direct',  data: [20, 28, 32, 40, 44, 50], backgroundColor: PALETTE.indigo, stack: 's', borderRadius: 4 },
          { label: 'Organic', data: [18, 24, 28, 34, 38, 46], backgroundColor: PALETTE.cyan,   stack: 's', borderRadius: 4 },
          { label: 'Paid',    data: [12, 16, 22, 28, 30, 36], backgroundColor: PALETTE.violet, stack: 's', borderRadius: 4 },
        ],
      },
      options: baseOptions({
        plugins: { legend: { display: true } },
        scales: { x: { stacked: true }, y: { stacked: true } },
      }),
    }));

    registerConfig('bar-stacked-100', () => ({
      type: 'bar',
      data: {
        labels: ['Q1','Q2','Q3','Q4'],
        datasets: [
          { label: 'Desktop', data: [55, 52, 48, 45], backgroundColor: PALETTE.indigo, stack: 's', borderRadius: 4 },
          { label: 'Mobile',  data: [35, 38, 42, 44], backgroundColor: PALETTE.cyan,   stack: 's', borderRadius: 4 },
          { label: 'Tablet',  data: [10, 10, 10, 11], backgroundColor: PALETTE.violet, stack: 's', borderRadius: 4 },
        ],
      },
      options: baseOptions({
        plugins: { legend: { display: true } },
        scales: { x: { stacked: true }, y: { stacked: true, max: 100, ticks: { callback: (v) => v + '%' } } },
      }),
    }));

    registerConfig('bar-gradient', () => ({
      type: 'bar',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug'],
        datasets: [{
          label: 'Revenue',
          data: [40, 60, 55, 78, 92, 108, 124, 140],
          backgroundColor: gradientBarVertical(PALETTE.indigo, PALETTE.cyan),
          borderRadius: 8,
          barPercentage: .55,
        }],
      },
      options: baseOptions(),
    }));

    registerConfig('bar-negative', () => ({
      type: 'bar',
      data: {
        labels: ['Product A','Product B','Product C','Product D','Product E','Product F'],
        datasets: [{
          label: 'YoY change',
          data: [24, -12, 34, -8, 16, -20],
          backgroundColor: (ctx) => (ctx.parsed.y >= 0 ? PALETTE.emerald : PALETTE.rose),
          borderRadius: 6,
        }],
      },
      options: baseOptions(),
    }));

    registerConfig('bar-labels', () => ({
      type: 'bar',
      data: {
        labels: ['A','B','C','D','E','F'],
        datasets: [{
          label: 'Score',
          data: [82, 74, 91, 68, 88, 76],
          backgroundColor: PALETTE.indigo,
          borderRadius: 6,
        }],
      },
      options: baseOptions(),
      plugins: [{
        id: 'barValueLabels',
        afterDatasetsDraw(chart) {
          const { ctx } = chart;
          chart.data.datasets.forEach((ds, di) => {
            const meta = chart.getDatasetMeta(di);
            meta.data.forEach((bar, i) => {
              ctx.save();
              ctx.fillStyle = textColor();
              ctx.font = '600 10px Inter, sans-serif';
              ctx.textAlign = 'center';
              ctx.fillText(String(ds.data[i]), bar.x, bar.y - 6);
              ctx.restore();
            });
          });
        },
      }],
    }));

    registerConfig('bar-range', () => ({
      type: 'bar',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun'],
        datasets: [{
          label: 'Temperature range (°C)',
          data: [[4,14],[6,17],[9,20],[12,24],[16,28],[19,32]],
          backgroundColor: gradientBarVertical(PALETTE.amber, PALETTE.rose),
          borderRadius: 999,
          barPercentage: .45,
        }],
      },
      options: baseOptions(),
    }));

    registerConfig('bar-sorted', () => {
      const raw = [
        { label: 'Berlin',  v: 340 },
        { label: 'Paris',   v: 480 },
        { label: 'London',  v: 620 },
        { label: 'Madrid',  v: 210 },
        { label: 'Rome',    v: 290 },
        { label: 'Vienna',  v: 180 },
        { label: 'Amsterdam', v: 400 },
      ].sort((a, b) => b.v - a.v);
      return {
        type: 'bar',
        data: {
          labels: raw.map((r) => r.label),
          datasets: [{
            label: 'Visitors',
            data: raw.map((r) => r.v),
            backgroundColor: raw.map((_, i) => PALETTE_LIST[i % PALETTE_LIST.length]),
            borderRadius: 6,
          }],
        },
        options: baseOptions({ indexAxis: 'y' }),
      };
    });
  }

  /* =============== AREA PAGE =============== */
  function registerArea() {
    registerConfig('area-basic', () => ({
      type: 'line',
      data: {
        labels: daysAgoLabels(14),
        datasets: [{
          label: 'Visits',
          data: [220, 280, 260, 320, 380, 340, 420, 460, 440, 520, 580, 540, 620, 680],
          borderColor: PALETTE.indigo,
          backgroundColor: gradientFill(PALETTE.indigo, .35, 0),
          fill: true,
          tension: .35,
          borderWidth: 2,
          pointRadius: 0,
        }],
      },
      options: baseOptions(),
    }));

    registerConfig('area-stacked', () => ({
      type: 'line',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug'],
        datasets: [
          { label: 'Direct',  data: [40, 50, 55, 68, 78, 82, 96, 110], borderColor: PALETTE.indigo, backgroundColor: hexToRgba(PALETTE.indigo, .3), fill: true, tension: .35, borderWidth: 1.5, pointRadius: 0 },
          { label: 'Organic', data: [30, 40, 42, 52, 66, 72, 80, 92],  borderColor: PALETTE.cyan,   backgroundColor: hexToRgba(PALETTE.cyan,   .3), fill: true, tension: .35, borderWidth: 1.5, pointRadius: 0 },
          { label: 'Paid',    data: [18, 22, 28, 30, 38, 44, 50, 58],  borderColor: PALETTE.violet, backgroundColor: hexToRgba(PALETTE.violet, .3), fill: true, tension: .35, borderWidth: 1.5, pointRadius: 0 },
        ],
      },
      options: baseOptions({
        plugins: { legend: { display: true } },
        scales: { y: { stacked: true } },
      }),
    }));

    registerConfig('area-smooth', () => ({
      type: 'line',
      data: {
        labels: daysAgoLabels(14),
        datasets: [{
          label: 'Signal',
          data: [12, 18, 20, 30, 22, 42, 38, 52, 44, 62, 58, 70, 82, 78],
          borderColor: PALETTE.violet,
          backgroundColor: gradientFill(PALETTE.violet, .35, 0),
          fill: true,
          tension: .55,
          borderWidth: 2,
          pointRadius: 0,
        }],
      },
      options: baseOptions(),
    }));

    registerConfig('area-stepped', () => ({
      type: 'line',
      data: {
        labels: ['00','04','08','12','16','20','24'],
        datasets: [{
          label: 'Load',
          data: [12, 14, 26, 38, 44, 30, 22],
          borderColor: PALETTE.emerald,
          backgroundColor: hexToRgba(PALETTE.emerald, .25),
          fill: true,
          stepped: true,
          borderWidth: 2,
          pointRadius: 2,
        }],
      },
      options: baseOptions(),
    }));

    registerConfig('area-stacked-100', () => {
      const a = [20, 28, 30, 32, 34, 40];
      const b = [50, 48, 46, 44, 42, 38];
      const c = [30, 24, 24, 24, 24, 22];
      return {
        type: 'line',
        data: {
          labels: ['Jan','Feb','Mar','Apr','May','Jun'],
          datasets: [
            { label: 'Chrome',  data: a, borderColor: PALETTE.indigo, backgroundColor: hexToRgba(PALETTE.indigo,.35), fill: true, tension: .3, pointRadius: 0 },
            { label: 'Safari',  data: b, borderColor: PALETTE.cyan,   backgroundColor: hexToRgba(PALETTE.cyan,.35),   fill: true, tension: .3, pointRadius: 0 },
            { label: 'Firefox', data: c, borderColor: PALETTE.violet, backgroundColor: hexToRgba(PALETTE.violet,.35), fill: true, tension: .3, pointRadius: 0 },
          ],
        },
        options: baseOptions({
          plugins: { legend: { display: true } },
          scales: { y: { stacked: true, max: 100, ticks: { callback: (v) => v + '%' } } },
        }),
      };
    });

    registerConfig('area-gradient', () => ({
      type: 'line',
      data: {
        labels: daysAgoLabels(14),
        datasets: [
          { label: 'A', data: [20, 30, 28, 42, 38, 55, 48, 62, 58, 74, 68, 80, 92, 88], borderColor: PALETTE.indigo, backgroundColor: gradientFill(PALETTE.indigo, .35, 0), fill: true, tension: .35, pointRadius: 0 },
          { label: 'B', data: [10, 15, 14, 22, 20, 30, 28, 38, 34, 46, 42, 54, 62, 58], borderColor: PALETTE.cyan,   backgroundColor: gradientFill(PALETTE.cyan,   .35, 0), fill: true, tension: .35, pointRadius: 0 },
        ],
      },
      options: baseOptions({ plugins: { legend: { display: true } } }),
    }));

    registerConfig('area-annotation', () => ({
      type: 'line',
      data: {
        labels: daysAgoLabels(20),
        datasets: [{
          label: 'Traffic',
          data: [12, 18, 22, 28, 32, 44, 52, 60, 58, 72, 80, 68, 74, 82, 88, 96, 104, 92, 110, 118],
          borderColor: PALETTE.indigo,
          backgroundColor: gradientFill(PALETTE.indigo, .3, 0),
          fill: true, tension: .35, borderWidth: 2, pointRadius: 0,
        }],
      },
      options: baseOptions(),
      plugins: [{
        id: 'bandHighlight',
        beforeDatasetsDraw(chart) {
          const { ctx, chartArea, scales } = chart;
          if (!chartArea) return;
          const xStart = scales.x.getPixelForValue(6);
          const xEnd = scales.x.getPixelForValue(11);
          ctx.save();
          ctx.fillStyle = hexToRgba(PALETTE.amber, .12);
          ctx.fillRect(xStart, chartArea.top, xEnd - xStart, chartArea.bottom - chartArea.top);
          ctx.fillStyle = PALETTE.amber;
          ctx.font = '700 10px Inter, sans-serif';
          ctx.fillText('Promotion window', xStart + 6, chartArea.top + 14);
          ctx.restore();
        },
      }],
    }));

    registerConfig('area-sparkline', () => ({
      type: 'line',
      data: {
        labels: Array.from({ length: 20 }, (_, i) => i + 1),
        datasets: [{
          data: [4, 6, 5, 8, 7, 9, 8, 11, 10, 13, 12, 15, 14, 17, 16, 19, 18, 21, 20, 24],
          borderColor: PALETTE.emerald,
          backgroundColor: gradientFill(PALETTE.emerald, .4, 0),
          fill: true,
          tension: .4,
          borderWidth: 2,
          pointRadius: 0,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: { x: { display: false }, y: { display: false } },
      },
    }));

    registerConfig('area-comparison', () => ({
      type: 'line',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [
          { label: 'This year', data: [42, 55, 62, 78, 88, 106, 124, 118, 140, 156, 168, 180], borderColor: PALETTE.indigo, backgroundColor: gradientFill(PALETTE.indigo, .3, 0), fill: true, tension: .35, borderWidth: 2, pointRadius: 0 },
          { label: 'Last year', data: [32, 40, 50, 60, 68, 82,  96,  92,  108, 120, 128, 138], borderColor: PALETTE.gray || '#94a3b8', backgroundColor: 'rgba(148,163,184,.2)', fill: true, tension: .35, borderWidth: 2, borderDash: [4,4], pointRadius: 0 },
        ],
      },
      options: baseOptions({ plugins: { legend: { display: true } } }),
    }));
  }

  /* =============== PIE PAGE =============== */
  function registerPie() {
    const donutBorder = () => (isDark() ? '#171a2b' : '#ffffff');

    registerConfig('pie-basic', () => ({
      type: 'pie',
      data: {
        labels: ['Direct','Organic','Social','Referral'],
        datasets: [{
          data: [38, 28, 20, 14],
          backgroundColor: [PALETTE.indigo, PALETTE.cyan, PALETTE.violet, PALETTE.amber],
          borderColor: donutBorder(), borderWidth: 3,
        }],
      },
      options: baseOptions({
        plugins: { legend: { display: true, position: 'right' } },
        scales: { x: { display: false }, y: { display: false } },
      }),
    }));

    registerConfig('pie-labels', () => {
      const data = [38, 28, 20, 14];
      const total = data.reduce((a,b)=>a+b, 0);
      return {
        type: 'pie',
        data: {
          labels: ['Direct','Organic','Social','Referral'],
          datasets: [{
            data: data,
            backgroundColor: [PALETTE.indigo, PALETTE.cyan, PALETTE.violet, PALETTE.amber],
            borderColor: donutBorder(), borderWidth: 3,
          }],
        },
        options: baseOptions({
          plugins: { legend: { display: true, position: 'bottom' } },
          scales: { x: { display: false }, y: { display: false } },
        }),
        plugins: [{
          id: 'pieSliceLabels',
          afterDatasetsDraw(chart) {
            const { ctx } = chart;
            const meta = chart.getDatasetMeta(0);
            meta.data.forEach((arc, i) => {
              const val = chart.data.datasets[0].data[i];
              const pct = Math.round(val / total * 100);
              const pos = arc.tooltipPosition();
              ctx.save();
              ctx.fillStyle = '#fff';
              ctx.font = '700 11px Inter, sans-serif';
              ctx.textAlign = 'center';
              ctx.textBaseline = 'middle';
              ctx.fillText(pct + '%', pos.x, pos.y);
              ctx.restore();
            });
          },
        }],
      };
    });

    registerConfig('pie-highlight', () => ({
      type: 'pie',
      data: {
        labels: ['A','B','C','D','E'],
        datasets: [{
          data: [22, 34, 18, 14, 12],
          backgroundColor: PALETTE_LIST.slice(0,5),
          borderColor: donutBorder(), borderWidth: 3,
          hoverOffset: 20,
        }],
      },
      options: baseOptions({
        plugins: { legend: { display: true, position: 'bottom' } },
        scales: { x: { display: false }, y: { display: false } },
        animation: { animateRotate: true, animateScale: true },
      }),
    }));

    registerConfig('pie-center', () => ({
      type: 'doughnut',
      data: {
        labels: ['Complete','Remaining'],
        datasets: [{
          data: [72, 28],
          backgroundColor: [PALETTE.indigo, isDark() ? '#232842' : '#eef0f7'],
          borderWidth: 0,
        }],
      },
      options: baseOptions({
        cutout: '68%',
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: { x: { display: false }, y: { display: false } },
      }),
      plugins: [{
        id: 'centerText',
        afterDraw(chart) {
          const { ctx, chartArea } = chart;
          if (!chartArea) return;
          const cx = (chartArea.left + chartArea.right) / 2;
          const cy = (chartArea.top + chartArea.bottom) / 2;
          ctx.save();
          ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
          ctx.fillStyle = textColor();
          ctx.font = '700 22px Inter, sans-serif';
          ctx.fillText('72%', cx, cy - 4);
          ctx.fillStyle = tickColor();
          ctx.font = '500 11px Inter, sans-serif';
          ctx.fillText('Complete', cx, cy + 16);
          ctx.restore();
        },
      }],
    }));

    ['top','right','bottom','left'].forEach((pos) => {
      registerConfig('pie-legend-' + pos, () => ({
        type: 'pie',
        data: {
          labels: ['A','B','C','D'],
          datasets: [{
            data: [30, 25, 25, 20],
            backgroundColor: PALETTE_LIST.slice(0,4),
            borderColor: donutBorder(), borderWidth: 3,
          }],
        },
        options: baseOptions({
          plugins: { legend: { display: true, position: pos } },
          scales: { x: { display: false }, y: { display: false } },
        }),
      }));
    });

    registerConfig('pie-custom', () => ({
      type: 'pie',
      data: {
        labels: ['Aurora','Cobalt','Sunset','Meadow','Coral'],
        datasets: [{
          data: [24, 18, 22, 16, 20],
          backgroundColor: ['#6366f1','#0ea5e9','#f97316','#10b981','#f43f5e'],
          borderColor: donutBorder(), borderWidth: 3,
        }],
      },
      options: baseOptions({
        plugins: { legend: { display: true, position: 'bottom' } },
        scales: { x: { display: false }, y: { display: false } },
      }),
    }));

    registerConfig('pie-nested', () => ({
      type: 'doughnut',
      data: {
        labels: ['Inner A','Inner B','Inner C'],
        datasets: [
          { label: 'Inner', data: [40, 30, 30], backgroundColor: [PALETTE.indigo, PALETTE.cyan, PALETTE.violet], borderColor: donutBorder(), borderWidth: 2 },
          { label: 'Outer', data: [20, 15, 20, 20, 12, 13], backgroundColor: [PALETTE.indigo, hexToRgba(PALETTE.indigo,.6), PALETTE.cyan, hexToRgba(PALETTE.cyan,.6), PALETTE.violet, hexToRgba(PALETTE.violet,.6)], borderColor: donutBorder(), borderWidth: 2 },
        ],
      },
      options: baseOptions({
        cutout: '30%',
        plugins: { legend: { display: false } },
        scales: { x: { display: false }, y: { display: false } },
      }),
    }));
  }

  /* =============== DONUT PAGE =============== */
  function registerDonut() {
    const donutBorder = () => (isDark() ? '#171a2b' : '#ffffff');

    registerConfig('donut-basic', () => ({
      type: 'doughnut',
      data: {
        labels: ['Chrome','Safari','Edge','Firefox','Other'],
        datasets: [{
          data: [58, 20, 10, 8, 4],
          backgroundColor: PALETTE_LIST.slice(0,5),
          borderColor: donutBorder(), borderWidth: 3,
        }],
      },
      options: baseOptions({
        cutout: '65%',
        plugins: { legend: { display: true, position: 'bottom' } },
        scales: { x: { display: false }, y: { display: false } },
      }),
    }));

    registerConfig('donut-center', () => ({
      type: 'doughnut',
      data: {
        labels: ['Sales','Target'],
        datasets: [{
          data: [86, 14],
          backgroundColor: [PALETTE.indigo, isDark() ? '#232842' : '#eef0f7'],
          borderWidth: 0,
        }],
      },
      options: baseOptions({
        cutout: '72%',
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: { x: { display: false }, y: { display: false } },
      }),
      plugins: [{
        id: 'donutCenter',
        afterDraw(chart) {
          const { ctx, chartArea } = chart;
          if (!chartArea) return;
          const cx = (chartArea.left + chartArea.right) / 2;
          const cy = (chartArea.top + chartArea.bottom) / 2;
          ctx.save();
          ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
          ctx.fillStyle = textColor(); ctx.font = '800 28px Inter, sans-serif';
          ctx.fillText('$86K', cx, cy - 8);
          ctx.fillStyle = tickColor(); ctx.font = '500 11px Inter, sans-serif';
          ctx.fillText('Sales this month', cx, cy + 16);
          ctx.restore();
        },
      }],
    }));

    registerConfig('donut-semi', () => ({
      type: 'doughnut',
      data: {
        labels: ['Score','Rest'],
        datasets: [{
          data: [72, 28],
          backgroundColor: [PALETTE.indigo, isDark() ? '#232842' : '#eef0f7'],
          borderWidth: 0,
          circumference: 180,
          rotation: 270,
        }],
      },
      options: baseOptions({
        cutout: '75%',
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: { x: { display: false }, y: { display: false } },
      }),
      plugins: [{
        id: 'semiCenter',
        afterDraw(chart) {
          const { ctx, chartArea } = chart;
          if (!chartArea) return;
          const cx = (chartArea.left + chartArea.right) / 2;
          const cy = chartArea.bottom - 20;
          ctx.save();
          ctx.textAlign = 'center';
          ctx.fillStyle = textColor(); ctx.font = '800 26px Inter, sans-serif';
          ctx.fillText('72', cx, cy);
          ctx.fillStyle = tickColor(); ctx.font = '500 11px Inter, sans-serif';
          ctx.fillText('Gauge score', cx, cy + 16);
          ctx.restore();
        },
      }],
    }));

    registerConfig('donut-multi-ring', () => ({
      type: 'doughnut',
      data: {
        labels: ['Team A','Team B','Team C'],
        datasets: [
          { data: [40, 30, 30], backgroundColor: [PALETTE.indigo, PALETTE.cyan, PALETTE.violet], borderColor: donutBorder(), borderWidth: 2 },
          { data: [55, 25, 20], backgroundColor: [hexToRgba(PALETTE.indigo,.7), hexToRgba(PALETTE.cyan,.7), hexToRgba(PALETTE.violet,.7)], borderColor: donutBorder(), borderWidth: 2 },
          { data: [30, 30, 40], backgroundColor: [hexToRgba(PALETTE.indigo,.4), hexToRgba(PALETTE.cyan,.4), hexToRgba(PALETTE.violet,.4)], borderColor: donutBorder(), borderWidth: 2 },
        ],
      },
      options: baseOptions({
        cutout: '30%',
        plugins: { legend: { display: false } },
        scales: { x: { display: false }, y: { display: false } },
      }),
    }));

    registerConfig('donut-cutout', () => ({
      type: 'doughnut',
      data: {
        labels: ['A','B','C','D'],
        datasets: [{
          data: [30, 25, 25, 20],
          backgroundColor: PALETTE_LIST.slice(0,4),
          borderColor: donutBorder(), borderWidth: 3,
        }],
      },
      options: baseOptions({
        cutout: '55%',
        plugins: { legend: { display: true, position: 'bottom' } },
        scales: { x: { display: false }, y: { display: false } },
      }),
    }));

    registerConfig('donut-rounded', () => ({
      type: 'doughnut',
      data: {
        labels: ['Progress','Rest'],
        datasets: [{
          data: [65, 35],
          backgroundColor: [PALETTE.emerald, isDark() ? '#232842' : '#eef0f7'],
          borderWidth: 0,
          borderRadius: 20,
        }],
      },
      options: baseOptions({
        cutout: '78%',
        plugins: { legend: { display: false } },
        scales: { x: { display: false }, y: { display: false } },
      }),
    }));

    registerConfig('donut-gradient', () => ({
      type: 'doughnut',
      data: {
        labels: ['A','B','C'],
        datasets: [{
          data: [40, 30, 30],
          backgroundColor: (ctx) => {
            const chart = ctx.chart;
            const { ctx: c, chartArea } = chart;
            if (!chartArea) return [PALETTE.indigo, PALETTE.cyan, PALETTE.violet];
            const size = Math.min(chartArea.right - chartArea.left, chartArea.bottom - chartArea.top);
            const cx = (chartArea.left + chartArea.right) / 2;
            const cy = (chartArea.top + chartArea.bottom) / 2;
            const grads = [
              [PALETTE.indigo, PALETTE.cyan],
              [PALETTE.violet, PALETTE.rose],
              [PALETTE.amber,  PALETTE.orange],
            ];
            const idx = ctx.dataIndex;
            if (idx == null) return PALETTE.indigo;
            const g = c.createRadialGradient(cx, cy, size/6, cx, cy, size/2);
            g.addColorStop(0, grads[idx][0]);
            g.addColorStop(1, grads[idx][1]);
            return g;
          },
          borderColor: donutBorder(),
          borderWidth: 3,
        }],
      },
      options: baseOptions({
        cutout: '55%',
        plugins: { legend: { display: true, position: 'bottom' } },
        scales: { x: { display: false }, y: { display: false } },
      }),
    }));

    registerConfig('donut-compare-a', () => ({
      type: 'doughnut',
      data: {
        labels: ['Won','Lost','Pending'],
        datasets: [{
          data: [58, 22, 20],
          backgroundColor: [PALETTE.emerald, PALETTE.rose, PALETTE.amber],
          borderColor: donutBorder(), borderWidth: 3,
        }],
      },
      options: baseOptions({
        cutout: '65%',
        plugins: { legend: { display: true, position: 'bottom' } },
        scales: { x: { display: false }, y: { display: false } },
      }),
    }));
    registerConfig('donut-compare-b', () => ({
      type: 'doughnut',
      data: {
        labels: ['Won','Lost','Pending'],
        datasets: [{
          data: [72, 12, 16],
          backgroundColor: [PALETTE.emerald, PALETTE.rose, PALETTE.amber],
          borderColor: donutBorder(), borderWidth: 3,
        }],
      },
      options: baseOptions({
        cutout: '65%',
        plugins: { legend: { display: true, position: 'bottom' } },
        scales: { x: { display: false }, y: { display: false } },
      }),
    }));

    // Slider hook: cutout control
    registerAdvancedHook(() => {
      const slider = document.querySelector('[data-charts-cutout-slider]');
      const out = document.querySelector('[data-charts-cutout-out]');
      if (!slider) return;
      const target = document.querySelector('[data-charts-chart="donut-cutout"]');
      const inst = target && registry.get(target);
      if (!inst) return;
      const apply = () => {
        const v = parseInt(slider.value, 10);
        if (out) out.value = v + '%';
        inst.options.cutout = v + '%';
        inst.update();
      };
      slider.addEventListener('input', apply);
      apply();
    });
  }

  /* =============== RADAR PAGE =============== */
  function registerRadar() {
    const radarBase = (overrides) => baseOptions(deepMerge({
      scales: {
        r: {
          beginAtZero: true,
          angleLines: { color: gridColor() },
          grid: { color: gridColor() },
          pointLabels: { color: tickColor(), font: baseFont },
          ticks: { color: tickColor(), backdropColor: 'transparent', font: baseFont },
        },
        x: undefined, y: undefined,
      },
    }, overrides || {}));

    registerConfig('radar-basic', () => ({
      type: 'radar',
      data: {
        labels: ['Speed','Reliability','Comfort','Safety','Efficiency','Price'],
        datasets: [{
          label: 'Score',
          data: [75, 82, 68, 90, 70, 60],
          borderColor: PALETTE.indigo,
          backgroundColor: hexToRgba(PALETTE.indigo, .2),
          borderWidth: 2,
          pointBackgroundColor: PALETTE.indigo,
        }],
      },
      options: radarBase(),
    }));

    registerConfig('radar-multi', () => ({
      type: 'radar',
      data: {
        labels: ['Speed','Reliability','Comfort','Safety','Efficiency','Price'],
        datasets: [
          { label: 'Model A', data: [80, 70, 65, 88, 72, 55], borderColor: PALETTE.indigo, backgroundColor: hexToRgba(PALETTE.indigo,.2), borderWidth: 2, pointBackgroundColor: PALETTE.indigo },
          { label: 'Model B', data: [65, 85, 78, 74, 82, 68], borderColor: PALETTE.cyan,   backgroundColor: hexToRgba(PALETTE.cyan,.2),   borderWidth: 2, pointBackgroundColor: PALETTE.cyan   },
        ],
      },
      options: radarBase({ plugins: { legend: { display: true } } }),
    }));

    registerConfig('radar-filled', () => ({
      type: 'radar',
      data: {
        labels: ['Design','Perf','SEO','Access','Best Pract.','PWA'],
        datasets: [{
          label: 'Lighthouse',
          data: [92, 88, 95, 90, 96, 82],
          borderColor: PALETTE.emerald,
          backgroundColor: hexToRgba(PALETTE.emerald, .35),
          borderWidth: 2,
          fill: true,
          pointBackgroundColor: PALETTE.emerald,
        }],
      },
      options: radarBase(),
    }));

    registerConfig('radar-scale', () => ({
      type: 'radar',
      data: {
        labels: ['Vision','Strategy','Execution','Leadership','Innovation'],
        datasets: [{
          label: 'Rating (0-10)',
          data: [8, 7, 9, 6, 8],
          borderColor: PALETTE.violet,
          backgroundColor: hexToRgba(PALETTE.violet,.25),
          borderWidth: 2,
          pointBackgroundColor: PALETTE.violet,
        }],
      },
      options: radarBase({ scales: { r: { min: 0, max: 10, ticks: { stepSize: 2 } } } }),
    }));

    registerConfig('radar-styles', () => ({
      type: 'radar',
      data: {
        labels: ['A','B','C','D','E','F'],
        datasets: [
          { label: 'Triangles', data: [70, 82, 68, 74, 60, 88], borderColor: PALETTE.indigo, backgroundColor: hexToRgba(PALETTE.indigo,.15), borderWidth: 2, pointStyle: 'triangle', pointRadius: 6, pointBackgroundColor: PALETTE.indigo },
          { label: 'Stars',     data: [55, 68, 78, 60, 72, 70], borderColor: PALETTE.amber,  backgroundColor: hexToRgba(PALETTE.amber,.15),  borderWidth: 2, pointStyle: 'rectRot', pointRadius: 6, pointBackgroundColor: PALETTE.amber },
        ],
      },
      options: radarBase({ plugins: { legend: { display: true } } }),
    }));

    registerConfig('radar-team', () => ({
      type: 'radar',
      data: {
        labels: ['Frontend','Backend','DevOps','Design','Testing','Comms'],
        datasets: [
          { label: 'Alex',   data: [90, 60, 40, 82, 75, 70], borderColor: PALETTE.indigo, backgroundColor: hexToRgba(PALETTE.indigo,.15), borderWidth: 2 },
          { label: 'Priya',  data: [55, 92, 80, 40, 82, 68], borderColor: PALETTE.emerald,backgroundColor: hexToRgba(PALETTE.emerald,.15),borderWidth: 2 },
          { label: 'Marco',  data: [65, 70, 92, 60, 60, 90], borderColor: PALETTE.violet, backgroundColor: hexToRgba(PALETTE.violet,.15), borderWidth: 2 },
        ],
      },
      options: radarBase({ plugins: { legend: { display: true } } }),
    }));
  }

  /* =============== POLAR PAGE =============== */
  function registerPolar() {
    const polarBase = (overrides) => baseOptions(deepMerge({
      scales: {
        r: {
          angleLines: { color: gridColor() },
          grid: { color: gridColor() },
          ticks: { color: tickColor(), backdropColor: 'transparent', font: baseFont },
        },
        x: undefined, y: undefined,
      },
    }, overrides || {}));

    registerConfig('polar-basic', () => ({
      type: 'polarArea',
      data: {
        labels: ['Design','Dev','QA','Ops','Sales'],
        datasets: [{
          data: [22, 34, 18, 26, 30],
          backgroundColor: PALETTE_LIST.slice(0,5).map((c) => hexToRgba(c, .6)),
          borderColor: PALETTE_LIST.slice(0,5),
          borderWidth: 1,
        }],
      },
      options: polarBase({ plugins: { legend: { display: true, position: 'bottom' } } }),
    }));

    registerConfig('polar-multi', () => ({
      type: 'polarArea',
      data: {
        labels: ['A','B','C','D','E','F','G','H'],
        datasets: [{
          data: [10, 14, 18, 22, 26, 20, 16, 12],
          backgroundColor: PALETTE_LIST.map((c) => hexToRgba(c, .65)),
          borderColor: PALETTE_LIST,
          borderWidth: 1,
        }],
      },
      options: polarBase({ plugins: { legend: { display: false } } }),
    }));

    registerConfig('polar-angle', () => ({
      type: 'polarArea',
      data: {
        labels: ['Alpha','Beta','Gamma','Delta','Epsilon','Zeta'],
        datasets: [{
          data: [18, 22, 30, 24, 28, 20],
          backgroundColor: PALETTE_LIST.slice(0,6).map((c) => hexToRgba(c,.6)),
          borderColor: PALETTE_LIST.slice(0,6),
          borderWidth: 1,
        }],
      },
      options: polarBase({
        rotation: -60,
        plugins: { legend: { display: true, position: 'bottom' } },
      }),
    }));

    registerConfig('polar-sized', () => ({
      type: 'polarArea',
      data: {
        labels: ['Small','Medium','Large','X-Large','XX-Large'],
        datasets: [{
          data: [8, 18, 34, 22, 12],
          backgroundColor: [PALETTE.indigo, PALETTE.cyan, PALETTE.violet, PALETTE.emerald, PALETTE.amber].map((c) => hexToRgba(c,.6)),
          borderColor: [PALETTE.indigo, PALETTE.cyan, PALETTE.violet, PALETTE.emerald, PALETTE.amber],
          borderWidth: 1,
        }],
      },
      options: polarBase({ plugins: { legend: { display: true, position: 'bottom' } } }),
    }));

    registerConfig('polar-legend', () => ({
      type: 'polarArea',
      data: {
        labels: ['Direct','Organic','Social','Referral','Email'],
        datasets: [{
          data: [24, 30, 18, 14, 14],
          backgroundColor: PALETTE_LIST.slice(0,5).map((c) => hexToRgba(c,.6)),
          borderColor: PALETTE_LIST.slice(0,5),
          borderWidth: 1,
        }],
      },
      options: polarBase({ plugins: { legend: { display: true, position: 'right' } } }),
    }));

    registerConfig('polar-time', () => {
      const labels = Array.from({ length: 12 }, (_, i) => ((i * 2).toString().padStart(2, '0')) + ':00');
      const data = [3, 2, 4, 8, 14, 20, 28, 34, 30, 22, 16, 10];
      return {
        type: 'polarArea',
        data: {
          labels: labels,
          datasets: [{
            data: data,
            backgroundColor: data.map((v) => hexToRgba(PALETTE.indigo, .25 + Math.min(.5, v / 50))),
            borderColor: PALETTE.indigo,
            borderWidth: 1,
          }],
        },
        options: polarBase({ plugins: { legend: { display: false } } }),
      };
    });
  }

  /* =============== MIXED PAGE =============== */
  function registerMixed() {
    registerConfig('mixed-bar-line', () => ({
      type: 'bar',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug'],
        datasets: [
          { type: 'bar',  label: 'Revenue', data: [40, 55, 62, 78, 88, 106, 124, 118], backgroundColor: PALETTE.indigo, borderRadius: 6, order: 2, yAxisID: 'y' },
          { type: 'line', label: 'Growth %', data: [8, 12, 6, 18, 14, 22, 26, 20], borderColor: PALETTE.amber, backgroundColor: PALETTE.amber, borderWidth: 2, tension: .35, pointBackgroundColor: PALETTE.amber, order: 1, yAxisID: 'y1' },
        ],
      },
      options: baseOptions({
        plugins: { legend: { display: true } },
        scales: {
          y: { position: 'left', title: { display: true, text: 'Revenue ($k)', color: tickColor() } },
          y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { color: tickColor(), callback: (v) => v + '%' } },
        },
      }),
    }));

    registerConfig('mixed-bar-area', () => ({
      type: 'bar',
      data: {
        labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
        datasets: [
          { type: 'bar',  label: 'Orders', data: [28, 34, 42, 38, 48, 30, 24], backgroundColor: hexToRgba(PALETTE.cyan, .7), borderRadius: 6, order: 2 },
          { type: 'line', label: 'Traffic', data: [40, 50, 62, 55, 72, 44, 36], borderColor: PALETTE.indigo, backgroundColor: gradientFill(PALETTE.indigo, .3, 0), fill: true, tension: .35, borderWidth: 2, pointRadius: 0, order: 1 },
        ],
      },
      options: baseOptions({ plugins: { legend: { display: true } } }),
    }));

    registerConfig('mixed-line-scatter', () => ({
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug'],
        datasets: [
          { type: 'line',    label: 'Trend', data: [20, 30, 42, 50, 65, 72, 88, 98], borderColor: PALETTE.indigo, backgroundColor: hexToRgba(PALETTE.indigo,.15), tension: .3, borderWidth: 2, pointRadius: 0 },
          { type: 'scatter', label: 'Outliers', data: [{x:'Feb',y:64},{x:'Apr',y:22},{x:'Jun',y:106},{x:'Jul',y:44}], backgroundColor: PALETTE.rose, pointRadius: 6 },
        ],
      },
      options: baseOptions({ plugins: { legend: { display: true } } }),
    }));

    registerConfig('mixed-multi-axis', () => ({
      type: 'bar',
      data: {
        labels: ['Q1','Q2','Q3','Q4'],
        datasets: [
          { type: 'bar',  label: 'Revenue ($k)', data: [420, 520, 640, 780], backgroundColor: PALETTE.indigo, borderRadius: 6, yAxisID: 'y' },
          { type: 'line', label: 'Deals count',  data: [42, 58, 72, 96],      borderColor: PALETTE.emerald, backgroundColor: PALETTE.emerald, borderWidth: 2, tension: .3, yAxisID: 'y1' },
        ],
      },
      options: baseOptions({
        plugins: { legend: { display: true } },
        scales: {
          y: { position: 'left', title: { display: true, text: '$ (thousands)', color: tickColor() } },
          y1: { position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Count', color: tickColor() } },
        },
      }),
    }));

    registerConfig('mixed-marketing', () => ({
      type: 'bar',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun'],
        datasets: [
          { type: 'bar', label: 'Marketing spend ($k)', data: [22, 28, 24, 34, 40, 46], backgroundColor: gradientBarVertical(PALETTE.violet, PALETTE.rose), borderRadius: 6, yAxisID: 'y' },
          { type: 'line', label: 'Conversion rate (%)', data: [1.8, 2.1, 1.9, 2.6, 3.2, 3.8], borderColor: PALETTE.emerald, borderWidth: 2.5, tension: .35, pointRadius: 4, pointBackgroundColor: PALETTE.emerald, yAxisID: 'y1' },
        ],
      },
      options: baseOptions({
        plugins: { legend: { display: true } },
        scales: {
          y: { position: 'left', title: { display: true, text: 'Spend ($k)', color: tickColor() } },
          y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { callback: (v) => v + '%', color: tickColor() }, title: { display: true, text: 'Conv. rate', color: tickColor() } },
        },
      }),
    }));
  }

  /* =============== CHART.JS OVERVIEW =============== */
  function registerOverview() {
    registerConfig('ov-line', () => ({
      type: 'line',
      data: { labels: ['A','B','C','D','E','F'], datasets: [{ data: [20, 32, 28, 44, 40, 56], borderColor: PALETTE.indigo, backgroundColor: hexToRgba(PALETTE.indigo,.15), tension: .35, borderWidth: 2, pointRadius: 0 }] },
      options: baseOptions(),
    }));
    registerConfig('ov-bar', () => ({
      type: 'bar',
      data: { labels: ['A','B','C','D','E','F'], datasets: [{ data: [30, 42, 38, 52, 48, 62], backgroundColor: PALETTE.cyan, borderRadius: 5 }] },
      options: baseOptions(),
    }));
    registerConfig('ov-area', () => ({
      type: 'line',
      data: { labels: ['A','B','C','D','E','F'], datasets: [{ data: [10, 22, 20, 34, 30, 46], borderColor: PALETTE.violet, backgroundColor: gradientFill(PALETTE.violet, .35, 0), fill: true, tension: .4, borderWidth: 2, pointRadius: 0 }] },
      options: baseOptions(),
    }));
    registerConfig('ov-pie', () => ({
      type: 'pie',
      data: { labels: ['A','B','C','D'], datasets: [{ data: [30, 25, 25, 20], backgroundColor: PALETTE_LIST.slice(0,4), borderColor: surface(), borderWidth: 3 }] },
      options: baseOptions({ plugins: { legend: { display: false } }, scales: { x:{display:false}, y:{display:false} } }),
    }));
    registerConfig('ov-donut', () => ({
      type: 'doughnut',
      data: { labels: ['A','B','C'], datasets: [{ data: [50, 30, 20], backgroundColor: [PALETTE.indigo, PALETTE.cyan, PALETTE.violet], borderColor: surface(), borderWidth: 3 }] },
      options: baseOptions({ cutout: '65%', plugins: { legend: { display: false } }, scales: { x:{display:false}, y:{display:false} } }),
    }));
    registerConfig('ov-radar', () => ({
      type: 'radar',
      data: { labels: ['A','B','C','D','E'], datasets: [{ data: [65, 78, 60, 82, 70], borderColor: PALETTE.emerald, backgroundColor: hexToRgba(PALETTE.emerald,.25), borderWidth: 2 }] },
      options: baseOptions({ scales: { r: { angleLines: { color: gridColor() }, grid: { color: gridColor() }, pointLabels: { color: tickColor(), font: baseFont }, ticks: { display: false } }, x: undefined, y: undefined } }),
    }));
    registerConfig('ov-polar', () => ({
      type: 'polarArea',
      data: { labels: ['A','B','C','D','E'], datasets: [{ data: [12, 22, 18, 26, 30], backgroundColor: PALETTE_LIST.slice(0,5).map((c) => hexToRgba(c,.7)) }] },
      options: baseOptions({ scales: { r: { grid: { color: gridColor() }, ticks: { display: false } }, x: undefined, y: undefined } }),
    }));
    registerConfig('ov-mixed', () => ({
      type: 'bar',
      data: {
        labels: ['A','B','C','D','E','F'],
        datasets: [
          { type: 'bar', data: [30, 42, 38, 52, 48, 62], backgroundColor: PALETTE.cyan, borderRadius: 5 },
          { type: 'line', data: [20, 32, 28, 44, 40, 56], borderColor: PALETTE.indigo, borderWidth: 2, tension: .35, pointRadius: 0 },
        ],
      },
      options: baseOptions(),
    }));
    registerConfig('ov-sparkline', () => ({
      type: 'line',
      data: { labels: Array.from({length: 20}, (_,i)=>i), datasets: [{ data: [4,6,5,8,7,9,8,11,10,13,12,15,14,17,16,19,18,21,20,24], borderColor: PALETTE.rose, backgroundColor: gradientFill(PALETTE.rose,.35,0), fill: true, tension: .4, borderWidth: 2, pointRadius: 0 }] },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { enabled: false } }, scales: { x: { display: false }, y: { display: false } } },
    }));
    registerConfig('ov-scatter', () => ({
      type: 'scatter',
      data: {
        datasets: [{
          label: 'Points',
          data: Array.from({length: 30}, () => ({ x: Math.random() * 100, y: Math.random() * 100 })),
          backgroundColor: hexToRgba(PALETTE.amber, .7),
          pointRadius: 5,
        }],
      },
      options: baseOptions(),
    }));
  }

  /* =============== ADVANCED (Chart.js Recipes) =============== */
  function registerAdvanced() {
    // Section 1: Real-time streaming
    registerConfig('adv-realtime', () => ({
      type: 'line',
      data: {
        labels: Array.from({ length: 30 }, (_, i) => (i - 29) + 's'),
        datasets: [{
          label: 'CPU %',
          data: Array.from({ length: 30 }, () => Math.round(30 + Math.random() * 40)),
          borderColor: PALETTE.emerald,
          backgroundColor: gradientFill(PALETTE.emerald, .3, 0),
          fill: true, tension: .3, borderWidth: 2, pointRadius: 0,
        }],
      },
      options: baseOptions({
        animation: { duration: 300 },
        scales: { y: { min: 0, max: 100, ticks: { callback: (v) => v + '%' } } },
      }),
    }));

    registerAdvancedHook(() => {
      const canvas = document.querySelector('[data-charts-chart="adv-realtime"]');
      if (!canvas) return;
      const inst = registry.get(canvas);
      if (!inst) return;

      const toggle = document.querySelector('[data-charts-realtime-toggle]');
      const indicator = document.querySelector('[data-charts-realtime-indicator]');
      const indicatorLabel = document.querySelector('[data-charts-realtime-label]');
      let timer = null;
      let counter = 0;

      const tick = () => {
        counter++;
        const ds = inst.data.datasets[0];
        const labels = inst.data.labels;
        ds.data.push(Math.round(30 + Math.random() * 40));
        labels.push('t+' + counter);
        if (ds.data.length > 30) { ds.data.shift(); labels.shift(); }
        inst.update('none');
      };
      const start = () => {
        if (timer) return;
        timer = setInterval(tick, 1000);
        if (indicator) indicator.classList.remove('is-off');
        if (indicatorLabel) indicatorLabel.textContent = 'Streaming';
        if (toggle) toggle.textContent = 'Pause';
      };
      const stop = () => {
        if (!timer) return;
        clearInterval(timer); timer = null;
        if (indicator) indicator.classList.add('is-off');
        if (indicatorLabel) indicatorLabel.textContent = 'Paused';
        if (toggle) toggle.textContent = 'Start';
      };
      if (toggle) {
        toggle.addEventListener('click', () => (timer ? stop() : start()));
      }
      start();
    });

    // Section 2: Drill-down bar
    const drillData = {
      'North America': [
        { id: 'US-001', name: 'Acme Corp', revenue: '$32,400' },
        { id: 'US-002', name: 'Wayne Enterprises', revenue: '$18,750' },
        { id: 'CA-014', name: 'Nova Labs', revenue: '$9,120' },
      ],
      'Europe': [
        { id: 'UK-002', name: 'Kingsly Ltd', revenue: '$14,300' },
        { id: 'DE-021', name: 'Berg AG', revenue: '$21,700' },
        { id: 'FR-042', name: 'Lumière SAS', revenue: '$6,900' },
      ],
      'Asia': [
        { id: 'JP-030', name: 'Sakura Co', revenue: '$27,110' },
        { id: 'SG-011', name: 'Bay Digital', revenue: '$11,240' },
      ],
      'LATAM': [
        { id: 'BR-007', name: 'Onda Media', revenue: '$8,410' },
        { id: 'MX-016', name: 'Sol Studios', revenue: '$5,970' },
      ],
      'MENA': [
        { id: 'AE-003', name: 'Falcon Group', revenue: '$16,320' },
        { id: 'EG-008', name: 'Nile Works', revenue: '$4,880' },
      ],
    };
    const drillLabels = Object.keys(drillData);
    registerConfig('adv-drilldown', () => ({
      type: 'bar',
      data: {
        labels: drillLabels,
        datasets: [{
          label: 'Revenue',
          data: [60270, 42900, 38350, 14380, 21200],
          backgroundColor: drillLabels.map((_, i) => PALETTE_LIST[i % PALETTE_LIST.length]),
          borderRadius: 6,
        }],
      },
      options: baseOptions({
        onHover: (e, els) => {
          e.native.target.style.cursor = els.length ? 'pointer' : 'default';
        },
      }),
    }));
    registerAdvancedHook(() => {
      const canvas = document.querySelector('[data-charts-chart="adv-drilldown"]');
      const tbody = document.querySelector('[data-charts-drill-body]');
      const label = document.querySelector('[data-charts-drill-region]');
      if (!canvas || !tbody) return;
      const inst = registry.get(canvas);
      if (!inst) return;
      const render = (region) => {
        tbody.innerHTML = '';
        (drillData[region] || []).forEach((row) => {
          const tr = document.createElement('tr');
          tr.innerHTML = '<td>' + row.id + '</td><td>' + row.name + '</td><td class="text-end fw-semibold">' + row.revenue + '</td>';
          tbody.appendChild(tr);
        });
        if (label) label.textContent = region;
      };
      canvas.addEventListener('click', (evt) => {
        const els = inst.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
        if (!els.length) return;
        const idx = els[0].index;
        const region = drillLabels[idx];
        render(region);
        orchidToast('Region: ' + region, (drillData[region] || []).length + ' records loaded', 'success');
      });
      render(drillLabels[0]);
    });

    // Section 3: Zoom + pan (manual range control)
    registerConfig('adv-zoom', () => ({
      type: 'line',
      data: {
        labels: daysAgoLabels(60),
        datasets: [{
          label: 'Latency (ms)',
          data: Array.from({ length: 60 }, (_, i) => 80 + 30 * Math.sin(i / 4) + Math.random() * 20),
          borderColor: PALETTE.sky,
          backgroundColor: gradientFill(PALETTE.sky, .25, 0),
          fill: true, tension: .3, borderWidth: 2, pointRadius: 0,
        }],
      },
      options: baseOptions({
        scales: { x: { ticks: { maxTicksLimit: 10 } } },
      }),
    }));
    registerAdvancedHook(() => {
      const canvas = document.querySelector('[data-charts-chart="adv-zoom"]');
      if (!canvas) return;
      const inst = registry.get(canvas);
      if (!inst) return;
      const state = { min: 0, max: 59 };
      const apply = () => {
        inst.options.scales.x.min = state.min;
        inst.options.scales.x.max = state.max;
        inst.update('none');
      };
      const zoomIn = () => {
        const span = state.max - state.min;
        if (span <= 6) return;
        state.min += Math.round(span * .1);
        state.max -= Math.round(span * .1);
        apply();
      };
      const zoomOut = () => {
        state.min = Math.max(0, state.min - 3);
        state.max = Math.min(59, state.max + 3);
        apply();
      };
      const reset = () => { state.min = 0; state.max = 59; apply(); };

      canvas.addEventListener('wheel', (e) => {
        e.preventDefault();
        if (e.deltaY < 0) zoomIn(); else zoomOut();
      }, { passive: false });

      const btnIn  = document.querySelector('[data-charts-zoom-in]');
      const btnOut = document.querySelector('[data-charts-zoom-out]');
      const btnRst = document.querySelector('[data-charts-zoom-reset]');
      if (btnIn) btnIn.addEventListener('click', zoomIn);
      if (btnOut) btnOut.addEventListener('click', zoomOut);
      if (btnRst) btnRst.addEventListener('click', () => { reset(); orchidToast('Zoom reset', 'Full range restored'); });
    });

    // Section 4: Custom plugin — current value overlay
    registerConfig('adv-plugin', () => ({
      type: 'line',
      data: {
        labels: Array.from({ length: 20 }, (_, i) => 'T' + (i+1)),
        datasets: [{
          label: 'Value',
          data: [42, 55, 68, 52, 78, 92, 84, 106, 88, 74, 96, 110, 128, 122, 140, 156, 148, 172, 168, 190],
          borderColor: PALETTE.violet,
          backgroundColor: gradientFill(PALETTE.violet, .3, 0),
          fill: true, tension: .35, borderWidth: 2, pointRadius: 0,
        }],
      },
      options: baseOptions(),
      plugins: [{
        id: 'currentValueOverlay',
        afterDraw(chart) {
          const meta = chart.getDatasetMeta(0);
          const last = meta.data[meta.data.length - 1];
          if (!last) return;
          const ctx = chart.ctx;
          const val = chart.data.datasets[0].data[chart.data.datasets[0].data.length - 1];
          ctx.save();
          // pulse ring
          ctx.beginPath();
          ctx.arc(last.x, last.y, 8, 0, Math.PI * 2);
          ctx.fillStyle = hexToRgba(PALETTE.violet, .18);
          ctx.fill();
          ctx.beginPath();
          ctx.arc(last.x, last.y, 4, 0, Math.PI * 2);
          ctx.fillStyle = PALETTE.violet;
          ctx.fill();
          // pill
          const label = 'Now: ' + val;
          ctx.font = '700 11px Inter, sans-serif';
          const w = ctx.measureText(label).width + 16;
          const h = 22;
          let px = last.x + 12;
          const py = last.y - h - 8;
          if (px + w > chart.chartArea.right) px = last.x - w - 12;
          ctx.fillStyle = PALETTE.violet;
          ctx.beginPath();
          const r = 6;
          ctx.moveTo(px + r, py);
          ctx.arcTo(px + w, py, px + w, py + h, r);
          ctx.arcTo(px + w, py + h, px, py + h, r);
          ctx.arcTo(px, py + h, px, py, r);
          ctx.arcTo(px, py, px + w, py, r);
          ctx.closePath();
          ctx.fill();
          ctx.fillStyle = '#fff';
          ctx.textAlign = 'center';
          ctx.textBaseline = 'middle';
          ctx.fillText(label, px + w/2, py + h/2);
          ctx.restore();
        },
      }],
    }));

    // Section 5: Sync tooltips
    const makeSyncCfg = (color) => ({
      type: 'line',
      data: {
        labels: Array.from({ length: 24 }, (_, i) => (i + ':00')),
        datasets: [{
          label: 'Value',
          data: Array.from({ length: 24 }, (_, i) => Math.round(50 + 30 * Math.sin(i / 3) + Math.random() * 20)),
          borderColor: color,
          backgroundColor: hexToRgba(color, .15),
          fill: true, tension: .35, borderWidth: 2, pointRadius: 0,
        }],
      },
      options: baseOptions({ plugins: { tooltip: { mode: 'index', intersect: false } } }),
    });
    registerConfig('adv-sync-a', () => makeSyncCfg(PALETTE.indigo));
    registerConfig('adv-sync-b', () => makeSyncCfg(PALETTE.emerald));
    registerAdvancedHook(() => {
      const a = document.querySelector('[data-charts-chart="adv-sync-a"]');
      const b = document.querySelector('[data-charts-chart="adv-sync-b"]');
      if (!a || !b) return;
      const iA = registry.get(a);
      const iB = registry.get(b);
      if (!iA || !iB) return;
      const sync = (src, dst) => {
        src.canvas.addEventListener('mousemove', (evt) => {
          const els = src.getElementsAtEventForMode(evt, 'index', { intersect: false }, true);
          if (!els.length) return;
          const idx = els[0].index;
          dst.setActiveElements(dst.data.datasets.map((_, di) => ({ datasetIndex: di, index: idx })));
          dst.tooltip.setActiveElements(dst.data.datasets.map((_, di) => ({ datasetIndex: di, index: idx })), { x: 0, y: 0 });
          dst.update('none');
        });
        src.canvas.addEventListener('mouseleave', () => {
          dst.setActiveElements([]);
          dst.tooltip.setActiveElements([], { x: 0, y: 0 });
          dst.update('none');
        });
      };
      sync(iA, iB);
      sync(iB, iA);
    });

    // Section 6: Annotation lines (events)
    registerConfig('adv-events', () => {
      const events = [
        { idx: 4,  label: 'Deploy v1.2', color: PALETTE.emerald },
        { idx: 9,  label: 'Incident',    color: PALETTE.rose },
        { idx: 14, label: 'Feature flag', color: PALETTE.amber },
      ];
      return {
        type: 'line',
        data: {
          labels: Array.from({ length: 20 }, (_, i) => 'Day ' + (i+1)),
          datasets: [{
            label: 'Errors',
            data: [12, 14, 10, 16, 18, 34, 22, 24, 20, 48, 42, 36, 30, 28, 60, 40, 32, 28, 24, 22],
            borderColor: PALETTE.indigo,
            backgroundColor: gradientFill(PALETTE.indigo, .25, 0),
            fill: true, tension: .3, borderWidth: 2, pointRadius: 0,
          }],
        },
        options: baseOptions(),
        plugins: [{
          id: 'eventLines',
          afterDatasetsDraw(chart) {
            const { ctx, chartArea, scales } = chart;
            if (!chartArea) return;
            events.forEach((ev) => {
              const x = scales.x.getPixelForValue(ev.idx);
              ctx.save();
              ctx.strokeStyle = ev.color;
              ctx.lineWidth = 1.5;
              ctx.setLineDash([4, 4]);
              ctx.beginPath();
              ctx.moveTo(x, chartArea.top);
              ctx.lineTo(x, chartArea.bottom);
              ctx.stroke();
              ctx.setLineDash([]);
              // label pill
              ctx.font = '600 10px Inter, sans-serif';
              const w = ctx.measureText(ev.label).width + 12;
              const h = 18;
              ctx.fillStyle = ev.color;
              const px = x - w/2;
              const py = chartArea.top - h - 4;
              const r = 6;
              ctx.beginPath();
              ctx.moveTo(px + r, py);
              ctx.arcTo(px + w, py, px + w, py + h, r);
              ctx.arcTo(px + w, py + h, px, py + h, r);
              ctx.arcTo(px, py + h, px, py, r);
              ctx.arcTo(px, py, px + w, py, r);
              ctx.closePath();
              ctx.fill();
              ctx.fillStyle = '#fff';
              ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
              ctx.fillText(ev.label, x, py + h/2);
              ctx.restore();
            });
          },
        }],
      };
    });

    // Section 7: Dynamic data source
    const productSets = {
      'Product A': { color: PALETTE.indigo,  data: [12, 22, 34, 44, 58, 66, 80, 92, 104, 118, 130, 148] },
      'Product B': { color: PALETTE.emerald, data: [30, 28, 42, 38, 52, 58, 72, 68,  84,  92,  106, 118] },
      'Product C': { color: PALETTE.rose,    data: [8,  16, 20, 24, 30, 40, 44, 60,  56,  72,  84,  102] },
    };
    registerConfig('adv-dynamic', () => ({
      type: 'line',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [{
          label: 'Product A',
          data: productSets['Product A'].data,
          borderColor: productSets['Product A'].color,
          backgroundColor: gradientFill(productSets['Product A'].color, .3, 0),
          fill: true, tension: .35, borderWidth: 2, pointRadius: 0,
        }],
      },
      options: baseOptions({ animation: { duration: 600, easing: 'easeOutQuart' } }),
    }));
    registerAdvancedHook(() => {
      const select = document.querySelector('[data-charts-dynamic-select]');
      if (!select) return;
      const canvas = document.querySelector('[data-charts-chart="adv-dynamic"]');
      const inst = canvas && registry.get(canvas);
      if (!inst) return;
      select.addEventListener('change', () => {
        const key = select.value;
        const set = productSets[key];
        if (!set) return;
        inst.data.datasets[0].label = key;
        inst.data.datasets[0].data = set.data;
        inst.data.datasets[0].borderColor = set.color;
        inst.data.datasets[0].backgroundColor = gradientFill(set.color, .3, 0);
        inst.update();
        orchidToast('Loaded ' + key, 'Dataset swapped with animation');
      });
    });
  }

  /* ---------- Wire per-page ---------- */
  function registerForPage() {
    const p = currentPage();
    if (p === 'line')     registerLine();
    if (p === 'bar')      registerBar();
    if (p === 'area')     registerArea();
    if (p === 'pie')      registerPie();
    if (p === 'donut')    registerDonut();
    if (p === 'radar')    registerRadar();
    if (p === 'polar')    registerPolar();
    if (p === 'mixed')    registerMixed();
    if (p === 'chartjs')  registerOverview();
    if (p === 'apex')     registerAdvanced();
  }

  /* ---------- Boot ---------- */
  function boot() {
    registerForPage();
    buildAll();
    initPreviews();
    initTocScrollSpy();

    // Common variant toggle (data-charts-variant-group + data-charts-variant)
    document.querySelectorAll('[data-charts-variant-group]').forEach((group) => {
      const target = group.getAttribute('data-charts-variant-group');
      const canvas = document.querySelector('[data-charts-chart="' + target + '"]');
      if (!canvas) return;
      const inst = registry.get(canvas);
      if (!inst) return;
      const original = JSON.parse(JSON.stringify(inst.data.datasets[0].data));
      const variants = {
        low:  original.map((v) => (typeof v === 'number' ? Math.round(v * .6) : v)),
        med:  original,
        high: original.map((v) => (typeof v === 'number' ? Math.round(v * 1.35) : v)),
      };
      group.querySelectorAll('.charts-segmented__btn').forEach((btn) => {
        btn.addEventListener('click', () => {
          group.querySelectorAll('.charts-segmented__btn').forEach((b) => b.classList.remove('is-active'));
          btn.classList.add('is-active');
          const key = btn.getAttribute('data-charts-variant');
          if (variants[key]) {
            inst.data.datasets[0].data = variants[key].slice();
            inst.update();
          }
        });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', boot);

  document.addEventListener('orchid:themechange', () => {
    if (typeof Chart === 'undefined') return;
    Chart.defaults.color = tickColor();
    destroyAll();
    // Small delay so CSS vars settle
    setTimeout(() => {
      buildAll();
      orchidToast('Theme updated', 'Charts rebuilt with new palette');
    }, 60);
  });
})();
