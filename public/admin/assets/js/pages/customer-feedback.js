/* Orchid — Customer Feedback */
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    var B = window.BizExtras;
    if (!B) return;

    var feedback = [
      { id: 'f1',  ch: 'Survey',  cust: 'Emma Watson',   rating: 5, nps: 10, title: 'Best onboarding experience we have had',      snippet: 'The setup wizard walked us through every step. Our engineers were productive on day one.', cats: ['UX', 'General'], sent: 'Positive', status: 'Responded', date: '2026-07-22' },
      { id: 'f2',  ch: 'Support', cust: 'James Doe',     rating: 2, nps: 3,  title: 'Invoice generation is slow',                 snippet: 'It takes 4-5 seconds to generate a single invoice PDF. Multiply that by our 80 orders/day…',  cats: ['Bug'],     sent: 'Negative', status: 'In Review', date: '2026-07-21' },
      { id: 'f3',  ch: 'In-App',  cust: 'Sarah Miller',  rating: 4, nps: 8,  title: 'Dark mode ❤️',                                 snippet: 'Finally. My eyes thank you.',                                                                cats: ['UX'],      sent: 'Positive', status: 'Resolved',  date: '2026-07-21' },
      { id: 'f4',  ch: 'Email',   cust: 'Ryan Green',    rating: 1, nps: 1,  title: 'Cancelling my subscription',                 snippet: 'Pricing went up 40% without warning. This is not the way to treat long-time customers.',       cats: ['Pricing'], sent: 'Negative', status: 'New',       date: '2026-07-20' },
      { id: 'f5',  ch: 'Survey',  cust: 'Ava Lee',       rating: 4, nps: 8,  title: 'Great product, missing bulk actions',        snippet: 'Would love checkbox selection on the orders table for batch operations.',                     cats: ['Feature Request', 'UX'], sent: 'Positive', status: 'In Review', date: '2026-07-20' },
      { id: 'f6',  ch: 'Support', cust: 'Michael Chen',  rating: 5, nps: 10, title: 'Support team is phenomenal',                 snippet: 'Sarah resolved our SSO issue within 30 minutes on a Sunday. That is the level of service that keeps us here.', cats: ['Support'], sent: 'Positive', status: 'Resolved', date: '2026-07-19' },
      { id: 'f7',  ch: 'In-App',  cust: 'Olivia Brown',  rating: 3, nps: 6,  title: 'Notifications too noisy',                    snippet: 'Getting emails for everything. Need better granular controls.',                                cats: ['Feature Request'], sent: 'Neutral', status: 'In Review', date: '2026-07-19' },
      { id: 'f8',  ch: 'Survey',  cust: 'Noah Wilson',   rating: 5, nps: 9,  title: 'API is a joy to work with',                  snippet: 'Clear docs, sensible defaults, consistent naming. 10/10.',                                     cats: ['General'], sent: 'Positive', status: 'Responded', date: '2026-07-18' },
      { id: 'f9',  ch: 'Email',   cust: 'Isabella Martinez', rating: 3, nps: 6, title: 'Trial extension request',                snippet: 'We need another 2 weeks to complete our procurement process.',                                cats: ['General'], sent: 'Neutral',  status: 'Responded', date: '2026-07-18' },
      { id: 'f10', ch: 'Support', cust: 'Lucas Anderson', rating: 5, nps: 10, title: 'Compliance certifications very helpful',    snippet: 'Your SOC 2 report answered every question our security team had.',                          cats: ['General'], sent: 'Positive', status: 'Resolved', date: '2026-07-17' },
      { id: 'f11', ch: 'In-App',  cust: 'Sophia Taylor', rating: 4, nps: 8,  title: 'Loving the new analytics dashboard',         snippet: 'The customer health score is exactly what we needed for our monthly review.',                 cats: ['UX'],      sent: 'Positive', status: 'Responded', date: '2026-07-16' },
      { id: 'f12', ch: 'Survey',  cust: 'Mason Rodriguez', rating: 3, nps: 7, title: 'Mobile app needs work',                     snippet: 'Desktop is fantastic, but the mobile experience feels neglected.',                             cats: ['UX', 'Feature Request'], sent: 'Neutral', status: 'New', date: '2026-07-16' },
      { id: 'f13', ch: 'Email',   cust: 'Amelia Wright', rating: 2, nps: 4,  title: 'Reporting is limited',                       snippet: 'Cannot filter reports by custom fields. This is a dealbreaker for our workflow.',              cats: ['Feature Request'], sent: 'Negative', status: 'In Review', date: '2026-07-15' },
      { id: 'f14', ch: 'Support', cust: 'Ethan Thompson', rating: 5, nps: 9, title: 'Fast turnaround on custom quote',            snippet: 'Sales team pulled together a proposal in 24 hours. Impressed.',                                cats: ['Support', 'Pricing'], sent: 'Positive', status: 'Resolved', date: '2026-07-15' },
      { id: 'f15', ch: 'In-App',  cust: 'Mia Garcia',    rating: 4, nps: 8,  title: 'Would pay for AI summaries',                 snippet: 'If you added AI-powered ticket summaries, we would upgrade to the top plan tomorrow.',           cats: ['Feature Request'], sent: 'Positive', status: 'In Review', date: '2026-07-14' },
      { id: 'f16', ch: 'Survey',  cust: 'Alexander King', rating: 5, nps: 10, title: 'Enterprise-grade product, small-team feel', snippet: 'Best of both worlds. Keep it up.',                                                             cats: ['General'], sent: 'Positive', status: 'Responded', date: '2026-07-14' },
      { id: 'f17', ch: 'Email',   cust: 'Charlotte Scott', rating: 3, nps: 7, title: 'Slack integration is broken',                snippet: 'The Slack notifications stopped working after last last week update.',                        cats: ['Bug'],     sent: 'Neutral',  status: 'New',       date: '2026-07-13' },
      { id: 'f18', ch: 'Support', cust: 'Henry Adams',   rating: 5, nps: 10, title: 'Migration went smoothly',                    snippet: 'Data import from our legacy system was seamless.',                                            cats: ['Support'], sent: 'Positive', status: 'Resolved', date: '2026-07-13' },
      { id: 'f19', ch: 'In-App',  cust: 'Evelyn Baker',  rating: 4, nps: 8,  title: 'Dashboard widgets are great',                snippet: 'Now let us drag them to reorder!',                                                            cats: ['Feature Request', 'UX'], sent: 'Positive', status: 'In Review', date: '2026-07-12' },
      { id: 'f20', ch: 'Survey',  cust: 'Sebastian Nelson', rating: 5, nps: 10, title: 'Best-in-class SaaS',                       snippet: 'We evaluated 6 competitors — Orchid won on every dimension.',                                  cats: ['General'], sent: 'Positive', status: 'Responded', date: '2026-07-12' }
    ];

    var state = { search: '', channel: '', sentiment: '', status: '' };

    function chIcon(ch) {
      return { Survey: 'bi-clipboard-check', Email: 'bi-envelope', Support: 'bi-life-preserver', 'In-App': 'bi-window' }[ch] || 'bi-chat';
    }
    function chColor(ch) {
      return { Survey: '#4f46e5', Email: '#0ea5e9', Support: '#f59e0b', 'In-App': '#22c55e' }[ch] || '#8b5cf6';
    }
    function statusBadge(s) {
      var map = { New: 'bg-primary-subtle text-primary', 'In Review': 'bg-warning-subtle text-warning', Responded: 'bg-info-subtle text-info', Resolved: 'bg-success-subtle text-success' };
      return '<span class="badge ' + (map[s] || '') + '">' + s + '</span>';
    }
    function catChip(c) {
      var map = { Bug: '#ef4444', 'Feature Request': '#4f46e5', UX: '#0ea5e9', Pricing: '#f59e0b', Support: '#22c55e', General: '#8b5cf6' };
      var col = map[c] || '#6b7280';
      return '<span class="biz-pill" style="background:' + col + '18;color:' + col + '">' + c + '</span>';
    }

    var listEl = document.querySelector('[data-cfb-list]');

    function filtered() {
      var q = state.search.toLowerCase();
      return feedback.filter(function (f) {
        if (state.channel && f.ch !== state.channel) return false;
        if (state.sentiment && f.sent !== state.sentiment) return false;
        if (state.status && f.status !== state.status) return false;
        if (q && !(f.title.toLowerCase().includes(q) || f.snippet.toLowerCase().includes(q) || f.cust.toLowerCase().includes(q))) return false;
        return true;
      });
    }

    function render() {
      var arr = filtered();
      if (!arr.length) {
        listEl.innerHTML = '<div class="biz-empty"><i class="bi bi-inbox"></i><p class="mb-0">No feedback matches your filters</p></div>';
        return;
      }
      listEl.innerHTML = arr.map(function (f) {
        var col = chColor(f.ch);
        return '<div class="cfb-card" data-cfb-id="' + f.id + '">' +
          '<div class="cfb-card__hd">' +
            '<span class="cfb-card__channel" style="background:' + col + '18;color:' + col + '"><i class="bi ' + chIcon(f.ch) + '"></i></span>' +
            B.renderAvatar(f.cust, 'xs') +
            '<div class="flex-grow-1"><p class="mb-0 fw-semibold small">' + B.escapeHtml(f.cust) + '</p><small class="text-body-secondary">' + f.ch + ' · ' + B.fmtDate(f.date) + '</small></div>' +
            '<div class="text-end">' + B.renderStars(f.rating) + '<small class="d-block text-body-secondary">NPS ' + f.nps + '</small></div>' +
          '</div>' +
          '<p class="cfb-card__title">' + B.escapeHtml(f.title) + '</p>' +
          '<p class="cfb-card__snippet">' + B.escapeHtml(f.snippet) + '</p>' +
          '<div class="cfb-card__ft">' +
            '<div class="d-flex flex-wrap gap-1">' + f.cats.map(catChip).join('') + ' <span class="cfb-sent cfb-sent--' + f.sent.toLowerCase() + '">' + f.sent + '</span></div>' +
            '<div class="d-flex align-items-center gap-2">' + statusBadge(f.status) +
              '<button class="btn btn-sm btn-outline-primary" type="button" data-cfb-reply="' + f.id + '"><i class="bi bi-reply me-1"></i>Reply</button>' +
              '<button class="btn btn-sm btn-outline-secondary" type="button" data-cfb-assign="' + f.id + '"><i class="bi bi-person-plus me-1"></i>Assign</button>' +
            '</div>' +
          '</div>' +
        '</div>';
      }).join('');
      B.bindAvatarColors(listEl);
    }

    document.querySelector('[data-cfb-search]').addEventListener('input', B.debounce(function (e) { state.search = e.target.value; render(); }, 200));
    ['[data-cfb-channel]', '[data-cfb-sentiment]', '[data-cfb-status]'].forEach(function (sel, i) {
      document.querySelector(sel).addEventListener('change', function (e) {
        state[['channel','sentiment','status'][i]] = e.target.value;
        render();
      });
    });

    // Reply modal
    var replyEl = document.getElementById('cfbReplyModal');
    var replyModal = bootstrap.Modal.getOrCreateInstance(replyEl);
    var currentId = null;
    listEl.addEventListener('click', function (e) {
      var r = e.target.closest('[data-cfb-reply]');
      if (r) {
        currentId = r.getAttribute('data-cfb-reply');
        var f = feedback.find(function (x) { return x.id === currentId; });
        document.querySelector('[data-cfb-reply-ctx]').textContent = 'Replying to ' + f.cust + ' · "' + f.title + '"';
        replyModal.show();
      }
      var a = e.target.closest('[data-cfb-assign]');
      if (a) B.toast('Assigned to Sarah Miller', 'success');
    });
    document.querySelector('[data-cfb-reply-form]').addEventListener('submit', function (e) {
      e.preventDefault();
      var f = feedback.find(function (x) { return x.id === currentId; });
      if (f) {
        var resolve = document.getElementById('cfbReplyResolve').checked;
        f.status = resolve ? 'Resolved' : 'Responded';
        render();
      }
      B.toast('Reply sent', 'success');
      replyModal.hide();
      this.reset();
    });

    document.querySelector('[data-cfb-survey-launch]').addEventListener('click', function () {
      bootstrap.Modal.getInstance(document.getElementById('cfbSurveyModal')).hide();
      B.toast('Survey queued — reaches 4,120 customers over 24h', 'success');
    });

    // Tag cloud
    var tagCounts = {};
    feedback.forEach(function (f) { f.cats.forEach(function (c) { tagCounts[c] = (tagCounts[c] || 0) + 1; }); });
    // Add some synthetic frequent tags
    ['onboarding', 'sso', 'billing', 'performance', 'reports', 'integrations', 'mobile', 'api', 'notifications'].forEach(function (t) {
      tagCounts[t] = 2 + Math.floor(Math.random() * 8);
    });
    var cloud = document.querySelector('[data-cfb-cloud]');
    cloud.innerHTML = Object.keys(tagCounts).map(function (t) {
      var size = 0.7 + Math.min(tagCounts[t], 12) * 0.06;
      return '<span class="cfb-cloud__tag" style="font-size:' + size + 'rem">' + t + ' <small>·' + tagCounts[t] + '</small></span>';
    }).join('');

    // Charts (Chart.js loaded by shell)
    if (window.Chart) {
      var css = getComputedStyle(document.documentElement);
      var textCol = css.getPropertyValue('--orchid-text-muted') || '#6b7385';
      var npsEl = document.querySelector('[data-cfb-chart="nps"]');
      if (npsEl) {
        new Chart(npsEl, {
          type: 'doughnut',
          data: { labels: ['Promoters','Passives','Detractors'], datasets: [{ data: [58, 26, 16], backgroundColor: ['#10b981', '#f59e0b', '#ef4444'], borderWidth: 0 }] },
          options: { cutout: '72%', plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false }
        });
      }
      var sEl = document.querySelector('[data-cfb-chart="sentiment"]');
      if (sEl) {
        new Chart(sEl, {
          type: 'bar',
          data: {
            labels: ['Positive','Neutral','Negative'],
            datasets: [{ data: [62, 22, 16], backgroundColor: ['#10b981', '#94a3b8', '#ef4444'], borderRadius: 6, barThickness: 24 }]
          },
          options: { plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false, scales: { x: { grid: { display: false }, ticks: { color: textCol } }, y: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { color: textCol } } } }
        });
      }
    }

    // Paint KPI icons
    document.querySelectorAll('.biz-kpi__icon[data-bg]').forEach(function (el) {
      el.style.background = el.getAttribute('data-bg');
      el.style.color = el.getAttribute('data-fg');
    });

    render();
  });
})();
