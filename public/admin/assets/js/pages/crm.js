// =====================================================
// Orchid - CRM Dashboard page charts
// Namespace: data-crm-chart="..."
// Rebuilds on `orchid:themechange` event
// =====================================================

(function () {
  const crmPalette = {
    indigo:  '#6366f1',
    violet:  '#a855f7',
    amber:   '#f59e0b',
    orange:  '#f97316',
    emerald: '#10b981',
    rose:    '#f43f5e',
    slate:   '#64748b',
  };

  const isDark = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';
  const gridColor = () => (isDark() ? 'rgba(255,255,255,.06)' : 'rgba(15,18,32,.06)');
  const tickColor = () => (isDark() ? '#8891a8' : '#6b7385');
  const surfaceBg = () => (isDark() ? '#171a2b' : '#ffffff');

  const baseFont = { family: 'Inter, sans-serif', size: 11, weight: '500' };

  const registry = new Map();

  function baseOptions() {
    return {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: surfaceBg(),
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
    };
  }

  function sparkLine(canvas, data, color) {
    return new Chart(canvas, {
      type: 'line',
      data: {
        labels: data.map((_, i) => i + 1),
        datasets: [{
          data,
          borderColor: color,
          backgroundColor: (ctx) => {
            const chart = ctx.chart;
            const { ctx: c, chartArea } = chart;
            if (!chartArea) return 'transparent';
            const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            g.addColorStop(0, color + '55');
            g.addColorStop(1, color + '00');
            return g;
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

  const builders = {
    sparkLeads(c) { return sparkLine(c, [18, 22, 20, 28, 26, 34, 30, 38, 42, 40, 48, 52], crmPalette.indigo); },
    sparkOpps(c)  { return sparkLine(c, [12, 14, 18, 15, 22, 25, 28, 26, 32, 30, 34, 38], crmPalette.violet); },
    sparkValue(c) { return sparkLine(c, [42, 48, 45, 58, 62, 55, 68, 74, 72, 82, 88, 96], crmPalette.amber); },
    sparkWin(c)   { return sparkLine(c, [28, 32, 30, 34, 36, 33, 38, 40, 37, 42, 39, 34], crmPalette.emerald); },

    conversion(canvas) {
      const weeks = ['W1','W2','W3','W4','W5','W6','W7','W8','W9','W10','W11','W12'];
      const mkFill = (color) => (ctx) => {
        const chart = ctx.chart;
        const { ctx: c, chartArea } = chart;
        if (!chartArea) return 'transparent';
        const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
        g.addColorStop(0, color + '33');
        g.addColorStop(1, color + '00');
        return g;
      };
      return new Chart(canvas, {
        type: 'line',
        data: {
          labels: weeks,
          datasets: [
            {
              label: 'New',
              data: [180, 210, 195, 230, 248, 220, 265, 280, 260, 295, 310, 328],
              borderColor: crmPalette.indigo,
              backgroundColor: mkFill(crmPalette.indigo),
              borderWidth: 2.5,
              tension: .4,
              pointRadius: 0,
              pointHoverRadius: 5,
              fill: true,
            },
            {
              label: 'Qualified',
              data: [110, 128, 120, 142, 156, 138, 168, 182, 172, 195, 208, 224],
              borderColor: crmPalette.violet,
              backgroundColor: mkFill(crmPalette.violet),
              borderWidth: 2.5,
              tension: .4,
              pointRadius: 0,
              pointHoverRadius: 5,
              fill: true,
            },
            {
              label: 'Won',
              data: [42, 48, 45, 58, 66, 54, 72, 82, 76, 92, 105, 118],
              borderColor: crmPalette.amber,
              backgroundColor: mkFill(crmPalette.amber),
              borderWidth: 2.5,
              tension: .4,
              pointRadius: 0,
              pointHoverRadius: 5,
              fill: true,
            },
          ],
        },
        options: {
          ...baseOptions(),
          interaction: { mode: 'index', intersect: false },
        },
      });
    },

    sources(canvas) {
      return new Chart(canvas, {
        type: 'doughnut',
        data: {
          labels: ['Referral', 'Organic', 'Paid', 'Social'],
          datasets: [{
            data: [42, 28, 18, 12],
            backgroundColor: [crmPalette.indigo, crmPalette.violet, crmPalette.amber, crmPalette.emerald],
            borderColor: surfaceBg(),
            borderWidth: 3,
            hoverOffset: 8,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '68%',
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: surfaceBg(),
              titleColor: isDark() ? '#e6e8f2' : '#1e2436',
              bodyColor: isDark() ? '#e6e8f2' : '#1e2436',
              borderColor: isDark() ? '#232842' : '#e9ecf3',
              borderWidth: 1,
              padding: 10,
              cornerRadius: 8,
              callbacks: {
                label: (ctx) => ` ${ctx.label}: ${ctx.parsed}%`,
              },
            },
          },
        },
        plugins: [{
          id: 'crmSourcesCenter',
          afterDraw(chart) {
            const { ctx, chartArea } = chart;
            if (!chartArea) return;
            const cx = (chartArea.left + chartArea.right) / 2;
            const cy = (chartArea.top + chartArea.bottom) / 2;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = isDark() ? '#e6e8f2' : '#1e2436';
            ctx.font = '800 20px Inter, sans-serif';
            ctx.fillText('2,847', cx, cy - 6);
            ctx.font = '600 10px Inter, sans-serif';
            ctx.fillStyle = tickColor();
            ctx.fillText('Total leads', cx, cy + 14);
            ctx.restore();
          },
        }],
      });
    },

    revByRep(canvas) {
      return new Chart(canvas, {
        type: 'bar',
        data: {
          labels: ['Priya Menon', 'Marcus Chen', 'Sofia García', 'Daniel Kowalski', 'Aisha Nwosu', 'Rafael Lima', 'Ines Dubois'],
          datasets: [{
            label: 'Revenue ($K)',
            data: [342, 298, 264, 218, 187, 156, 128],
            backgroundColor: (ctx) => {
              const chart = ctx.chart;
              const { ctx: c, chartArea } = chart;
              if (!chartArea) return crmPalette.amber;
              const g = c.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
              g.addColorStop(0, crmPalette.amber);
              g.addColorStop(1, crmPalette.orange);
              return g;
            },
            borderRadius: 6,
            barPercentage: .65,
            categoryPercentage: .7,
          }],
        },
        options: {
          ...baseOptions(),
          indexAxis: 'y',
          plugins: {
            ...baseOptions().plugins,
            tooltip: {
              ...baseOptions().plugins.tooltip,
              callbacks: {
                label: (ctx) => ` $${ctx.parsed.x}K closed`,
              },
            },
          },
          scales: {
            x: { grid: { color: gridColor() }, ticks: { color: tickColor(), font: baseFont, callback: (v) => '$' + v + 'K' } },
            y: { grid: { display: false }, ticks: { color: tickColor(), font: baseFont } },
          },
        },
      });
    },
  };

  function initCharts() {
    if (typeof Chart === 'undefined') return;
    document.querySelectorAll('[data-crm-chart]').forEach((canvas) => {
      const key = canvas.dataset.crmChart;
      const builder = builders[key];
      if (!builder) return;
      const instance = builder(canvas);
      if (instance) registry.set(canvas, instance);
    });
  }

  function destroyCharts() {
    registry.forEach((chart) => chart.destroy());
    registry.clear();
  }

  document.addEventListener('DOMContentLoaded', initCharts);

  document.addEventListener('orchid:themechange', () => {
    destroyCharts();
    initCharts();
  });
})();
