// =====================================================
// Orchid - Charts (Chart.js configurations)
// Bar, Line, Area, Donut, Pie, Radial, Sparkline, Stacked Bar
// =====================================================

const palette = {
  primary: '#4f46e5',
  primarySoft: 'rgba(79, 70, 229, .15)',
  accent: '#22d3ee',
  accentSoft: 'rgba(34, 211, 238, .15)',
  purple: '#a855f7',
  purpleSoft: 'rgba(168, 85, 247, .15)',
  success: '#10b981',
  warning: '#f59e0b',
  danger:  '#ef4444',
  info:    '#0ea5e9',
  gray:    '#94a3b8',
};

const isDark = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';
const gridColor = () => (isDark() ? 'rgba(255,255,255,.06)' : 'rgba(15,18,32,.06)');
const tickColor = () => (isDark() ? '#8891a8' : '#6b7385');

const baseFont = { family: 'Inter, sans-serif', size: 11, weight: '500' };

const registry = new Map();

const commonOptions = () => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: isDark() ? '#171a2b' : '#ffffff',
      titleColor: isDark() ? '#e6e8f2' : '#1e2436',
      bodyColor: isDark() ? '#e6e8f2' : '#1e2436',
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
});

function makeGradient(ctx, area, color) {
  const g = ctx.createLinearGradient(0, area.top, 0, area.bottom);
  g.addColorStop(0, color.replace('OPACITY', '.35'));
  g.addColorStop(1, color.replace('OPACITY', '0'));
  return g;
}

/* ---------- Chart builders ---------- */

function sparkContacts(canvas) {
  return new Chart(canvas, {
    type: 'line',
    data: {
      labels: Array.from({ length: 12 }, (_, i) => i + 1),
      datasets: [{
        data: [12, 15, 10, 18, 14, 22, 19, 24, 21, 28, 26, 32],
        borderColor: palette.primary,
        backgroundColor: (context) => {
          const chart = context.chart;
          const { ctx: c, chartArea } = chart;
          if (!chartArea) return palette.primarySoft;
          return makeGradient(c, chartArea, 'rgba(79,70,229,OPACITY)');
        },
        fill: true,
        borderWidth: 2,
        tension: .4,
        pointRadius: 0,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: 0 },
      plugins: { legend: { display: false }, tooltip: { enabled: false } },
      scales: { x: { display: false }, y: { display: false } },
      elements: { point: { radius: 0 } },
    },
  });
}

function leadAnalytics(canvas) {
  const ctx = canvas.getContext('2d');
  return new Chart(canvas, {
    type: 'line',
    data: {
      labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
      datasets: [{
        label: 'Leads',
        data: [12, 19, 15, 25, 22, 30, 28],
        borderColor: palette.primary,
        backgroundColor: (context) => {
          const chart = context.chart;
          const { ctx: c, chartArea } = chart;
          if (!chartArea) return palette.primarySoft;
          return makeGradient(c, chartArea, 'rgba(79,70,229,OPACITY)');
        },
        fill: true,
        borderWidth: 2,
        tension: .4,
        pointRadius: 0,
        pointHoverRadius: 4,
      }],
    },
    options: {
      ...commonOptions(),
      scales: {
        x: { grid: { display: false }, ticks: { color: tickColor(), font: baseFont } },
        y: { display: false },
      },
    },
  });
}

function trafficSources(canvas) {
  return new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels: ['Direct', 'Organic', 'Social', 'Referral'],
      datasets: [{
        data: [38, 28, 20, 14],
        backgroundColor: [palette.primary, palette.accent, palette.purple, palette.warning],
        borderColor: isDark() ? '#171a2b' : '#ffffff',
        borderWidth: 3,
        hoverOffset: 6,
      }],
    },
    options: {
      ...commonOptions(),
      cutout: '68%',
      plugins: {
        ...commonOptions().plugins,
        legend: {
          display: true,
          position: 'bottom',
          labels: { color: tickColor(), font: baseFont, boxWidth: 8, boxHeight: 8, padding: 8, usePointStyle: true },
        },
      },
      scales: { x: { display: false }, y: { display: false } },
    },
  });
}

function earnings(canvas) {
  return new Chart(canvas, {
    type: 'line',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
      datasets: [{
        data: [30, 42, 38, 55, 48, 68, 74],
        borderColor: '#ffffff',
        backgroundColor: (context) => {
          const chart = context.chart;
          const { ctx: c, chartArea } = chart;
          if (!chartArea) return 'rgba(255,255,255,.2)';
          const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
          g.addColorStop(0, 'rgba(255,255,255,.45)');
          g.addColorStop(1, 'rgba(255,255,255,0)');
          return g;
        },
        fill: true,
        borderWidth: 2.5,
        tension: .4,
        pointRadius: 0,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: 0 },
      plugins: { legend: { display: false }, tooltip: { enabled: false } },
      scales: { x: { display: false }, y: { display: false } },
    },
  });
}

function activeDeals(canvas) {
  return new Chart(canvas, {
    type: 'pie',
    data: {
      labels: ['Won', 'Pending', 'Lost'],
      datasets: [{
        data: [28, 14, 6],
        backgroundColor: [palette.primary, palette.warning, palette.danger],
        borderColor: isDark() ? '#171a2b' : '#ffffff',
        borderWidth: 3,
      }],
    },
    options: {
      ...commonOptions(),
      plugins: {
        ...commonOptions().plugins,
        legend: { display: false },
      },
      scales: { x: { display: false }, y: { display: false } },
    },
  });
}

function revenue(canvas) {
  const ctx = canvas.getContext('2d');
  return new Chart(canvas, {
    type: 'bar',
    data: {
      labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
      datasets: [
        {
          label: 'Revenue',
          data: [28, 42, 35, 55, 48, 62, 74, 66, 78, 82, 71, 88],
          backgroundColor: (context) => {
            const chart = context.chart;
            const { ctx: c, chartArea } = chart;
            if (!chartArea) return palette.primary;
            const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            g.addColorStop(0, palette.primary);
            g.addColorStop(1, palette.accent);
            return g;
          },
          borderRadius: 6,
          barPercentage: .55,
          categoryPercentage: .6,
        },
        {
          label: 'Profit',
          data: [18, 24, 22, 34, 30, 42, 48, 44, 52, 56, 46, 60],
          backgroundColor: 'rgba(148, 163, 184, .35)',
          borderRadius: 6,
          barPercentage: .55,
          categoryPercentage: .6,
        },
      ],
    },
    options: {
      ...commonOptions(),
      plugins: {
        ...commonOptions().plugins,
        legend: {
          display: true,
          position: 'top',
          align: 'end',
          labels: { color: tickColor(), font: baseFont, boxWidth: 8, boxHeight: 8, usePointStyle: true },
        },
      },
    },
  });
}

function retention(canvas) {
  return new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels: ['Retained', 'Churned'],
      datasets: [{
        data: [82, 18],
        backgroundColor: [palette.primary, isDark() ? '#232842' : '#eef0f7'],
        borderWidth: 0,
        circumference: 270,
        rotation: -135,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '78%',
      plugins: { legend: { display: false }, tooltip: { enabled: false } },
    },
    plugins: [{
      id: 'orchidRetentionCenter',
      afterDraw(chart) {
        const { ctx, chartArea } = chart;
        if (!chartArea) return;
        const cx = (chartArea.left + chartArea.right) / 2;
        const cy = (chartArea.top + chartArea.bottom) / 2;
        ctx.save();
        ctx.fillStyle = isDark() ? '#e6e8f2' : '#1e2436';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.font = '700 20px Inter, sans-serif';
        ctx.fillText('82%', cx, cy - 4);
        ctx.font = '500 10px Inter, sans-serif';
        ctx.fillStyle = isDark() ? '#8891a8' : '#6b7385';
        ctx.fillText('Retention', cx, cy + 14);
        ctx.restore();
      },
    }],
  });
}

function dealsOverview(canvas) {
  return new Chart(canvas, {
    type: 'bar',
    data: {
      labels: ['Wk 1','Wk 2','Wk 3','Wk 4','Wk 5','Wk 6','Wk 7','Wk 8'],
      datasets: [
        { label: 'Leads',    data: [22,28,25,32,30,38,35,42], backgroundColor: palette.primary, borderRadius: 4, stack: 's' },
        { label: 'Qualified',data: [15,18,20,22,24,28,26,30], backgroundColor: palette.accent,  borderRadius: 4, stack: 's' },
        { label: 'Proposal', data: [10,12,14,16,18,20,22,24], backgroundColor: palette.purple,  borderRadius: 4, stack: 's' },
        { label: 'Won',      data: [5, 7, 8, 10,12,14,15,18], backgroundColor: palette.success, borderRadius: 4, stack: 's' },
      ],
    },
    options: {
      ...commonOptions(),
      plugins: {
        ...commonOptions().plugins,
        legend: {
          display: true,
          position: 'top',
          align: 'end',
          labels: { color: tickColor(), font: baseFont, boxWidth: 8, boxHeight: 8, usePointStyle: true, padding: 12 },
        },
      },
      scales: {
        x: { stacked: true, grid: { display: false }, ticks: { color: tickColor(), font: baseFont } },
        y: { stacked: true, grid: { color: gridColor() }, ticks: { color: tickColor(), font: baseFont } },
      },
    },
  });
}

/* ---------- Registry & bootstrap ---------- */

const builders = {
  sparkContacts,
  leadAnalytics,
  trafficSources,
  earnings,
  activeDeals,
  revenue,
  retention,
  dealsOverview,
};

function initCharts() {
  if (typeof Chart === 'undefined') return;
  Chart.defaults.font.family = 'Inter, sans-serif';
  Chart.defaults.color = tickColor();

  document.querySelectorAll('[data-orchid-chart]').forEach((canvas) => {
    const key = canvas.dataset.orchidChart;
    const builder = builders[key];
    if (!builder) return;
    const instance = builder(canvas);
    registry.set(canvas, instance);
  });
}

function destroyCharts() {
  registry.forEach((chart) => chart.destroy());
  registry.clear();
}

document.addEventListener('DOMContentLoaded', initCharts);

// Rebuild charts on theme change (colors depend on data-bs-theme)
document.addEventListener('orchid:themechange', () => {
  destroyCharts();
  initCharts();
});

