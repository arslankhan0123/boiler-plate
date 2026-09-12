/* ==========================================================================
   Orchid — System Pages Shared Toolkit
   Applies to: settings, integrations, audit-logs, system-logs, maintenance-mode
   Exposes:  window.OrchidSys (utilities) and per-page initializers.
   ========================================================================== */
(function () {
  'use strict';

  // ------------------------------------------------------------
  // Toast helper
  // ------------------------------------------------------------
  function ensureToastHolder() {
    var h = document.querySelector('.sys-toast-holder');
    if (!h) {
      h = document.createElement('div');
      h.className = 'sys-toast-holder';
      document.body.appendChild(h);
    }
    return h;
  }
  function orchidToast(message, opts) {
    opts = opts || {};
    var type = opts.type || 'info';
    var title = opts.title || null;
    var timeout = opts.timeout || 3200;
    var icons = { info: 'bi-info-circle', success: 'bi-check-circle', danger: 'bi-x-circle', warning: 'bi-exclamation-triangle' };
    var holder = ensureToastHolder();
    var el = document.createElement('div');
    el.className = 'sys-toast sys-toast--' + type;
    var iconHtml = '<i class="bi ' + (icons[type] || icons.info) + '"></i>';
    var titleHtml = title ? '<strong>' + escapeHtml(title) + '</strong>' : '';
    el.innerHTML = iconHtml + '<div>' + titleHtml + escapeHtml(message) + '</div>';
    holder.appendChild(el);
    requestAnimationFrame(function () { el.classList.add('is-visible'); });
    setTimeout(function () {
      el.classList.remove('is-visible');
      setTimeout(function () { el.remove(); }, 220);
    }, timeout);
  }

  // ------------------------------------------------------------
  // Formatters
  // ------------------------------------------------------------
  function pad(n) { return n < 10 ? '0' + n : '' + n; }
  function fmtDate(d) {
    if (!(d instanceof Date)) d = new Date(d);
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
  }
  function fmtTime(d) {
    if (!(d instanceof Date)) d = new Date(d);
    return pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
  }
  function relativeTime(d) {
    if (!(d instanceof Date)) d = new Date(d);
    var s = Math.floor((Date.now() - d.getTime()) / 1000);
    if (s < 0) return 'in the future';
    if (s < 5) return 'just now';
    if (s < 60) return s + 's ago';
    var m = Math.floor(s / 60);
    if (m < 60) return m + 'm ago';
    var h = Math.floor(m / 60);
    if (h < 24) return h + 'h ago';
    var days = Math.floor(h / 24);
    if (days < 30) return days + 'd ago';
    var months = Math.floor(days / 30);
    if (months < 12) return months + 'mo ago';
    return Math.floor(months / 12) + 'y ago';
  }
  function formatBytes(n) {
    if (!n || n < 0) return '0 B';
    var units = ['B', 'KB', 'MB', 'GB', 'TB'];
    var i = 0;
    while (n >= 1024 && i < units.length - 1) { n /= 1024; i++; }
    return (n < 10 ? n.toFixed(2) : n.toFixed(1)) + ' ' + units[i];
  }
  function formatJSON(obj) {
    try { return JSON.stringify(obj, null, 2); } catch (e) { return String(obj); }
  }
  function escapeHtml(str) {
    if (str == null) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  // Integration palette (letter -> gradient)
  var INT_PALETTE = {
    'S': ['#4A154B', '#611f69'],   // Slack purple
    'D': ['#5865F2', '#8b93f7'],   // Discord blurple
    'T': ['#464eb8', '#7b83eb'],   // Teams
    'G': ['#e11d48', '#fb7185'],   // Google
    'M': ['#7856ff', '#a78bfa'],   // Mixpanel
    'A': ['#1e88e5', '#42a5f5'],   // Amplitude / Auth0
    'St': ['#635bff', '#9d95ff'],  // Stripe
    'P': ['#003087', '#009cde'],   // PayPal
    'Sq': ['#000000', '#3f4c5b'],  // Square
    'Dr': ['#0061ff', '#60a5fa'],  // Dropbox
    'Aw': ['#ff9900', '#ffb84d'],  // AWS
    'Sf': ['#00a1e0', '#66d9f5'],  // Salesforce
    'H': ['#ff7a59', '#ffa987'],   // HubSpot
    'Mc': ['#ffe01b', '#c9a800'],  // Mailchimp
    'Sg': ['#1a82e2', '#5eb4ee'],  // SendGrid
    'R': ['#000000', '#525252'],   // Resend
    'O': ['#007dc1', '#5cb3e6'],   // Okta
    'Z': ['#03363d', '#0b6673'],   // Zendesk
    'I': ['#1f8ded', '#5bb2f2']    // Intercom
  };
  function integrationLogoStyle(key) {
    var p = INT_PALETTE[key] || ['#4f46e5', '#22d3ee'];
    return 'background: linear-gradient(135deg, ' + p[0] + ' 0%, ' + p[1] + ' 100%);';
  }

  var LOG_LEVEL_COLORS = {
    error: '#dc2626', warn: '#d97706', info: '#2563eb', debug: '#64748b'
  };

  // Highlight helper for search matches
  function highlight(text, term, useRegex) {
    if (!term) return escapeHtml(text);
    var safe = escapeHtml(text);
    if (useRegex) {
      try {
        var re = new RegExp('(' + term + ')', 'gi');
        return safe.replace(re, '<mark>$1</mark>');
      } catch (e) { return safe; }
    }
    var esc = term.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&');
    return safe.replace(new RegExp('(' + esc + ')', 'gi'), '<mark>$1</mark>');
  }

  // Escape RegExp used elsewhere
  function escapeRegex(str) { return str.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&'); }

  window.OrchidSys = {
    orchidToast: orchidToast,
    fmtDate: fmtDate,
    fmtTime: fmtTime,
    relativeTime: relativeTime,
    formatBytes: formatBytes,
    formatJSON: formatJSON,
    escapeHtml: escapeHtml,
    escapeRegex: escapeRegex,
    highlight: highlight,
    integrationLogoStyle: integrationLogoStyle,
    LOG_LEVEL_COLORS: LOG_LEVEL_COLORS
  };

  // ============================================================
  // Auto-init modules based on page-level data hook
  // ============================================================
  document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelector('[data-sys-page="settings"]')) initSettings();
    if (document.querySelector('[data-sys-page="integrations"]')) initIntegrations();
    if (document.querySelector('[data-sys-page="audit-logs"]')) initAuditLogs();
    if (document.querySelector('[data-sys-page="system-logs"]')) initSystemLogs();
    if (document.querySelector('[data-sys-page="maintenance"]')) initMaintenance();
  });

  // ============================================================
  // SETTINGS
  // ============================================================
  function initSettings() {
    var nav = document.querySelectorAll('.set-nav__link');
    var panels = document.querySelectorAll('.set-panel');
    nav.forEach(function (n) {
      n.addEventListener('click', function (e) {
        e.preventDefault();
        var target = n.getAttribute('data-panel');
        activatePanel(target);
      });
    });
    function activatePanel(id) {
      nav.forEach(function (n) { n.classList.toggle('is-active', n.getAttribute('data-panel') === id); });
      panels.forEach(function (p) { p.classList.toggle('is-active', p.id === 'panel-' + id); });
      var main = document.querySelector('#panel-' + id);
      if (main) main.scrollIntoView({ block: 'start', behavior: 'smooth' });
    }

    // Search settings
    var searchInput = document.querySelector('[data-set-search]');
    var results = document.querySelector('[data-set-results]');
    if (searchInput && results) {
      searchInput.addEventListener('input', function () {
        var term = this.value.trim().toLowerCase();
        if (!term) { results.classList.remove('is-open'); return; }
        var hits = [];
        panels.forEach(function (p) {
          var pid = p.id.replace('panel-', '');
          var pTitle = p.querySelector('.set-panel__head h2');
          var pName = pTitle ? pTitle.textContent : pid;
          p.querySelectorAll('label, h6, .set-panel__head p, .hint').forEach(function (l) {
            var text = l.textContent.trim();
            if (text.toLowerCase().indexOf(term) !== -1 && text.length < 90) {
              hits.push({ panel: pid, panelName: pName, text: text });
            }
          });
        });
        var unique = {};
        hits = hits.filter(function (h) { var k = h.panel + '|' + h.text; if (unique[k]) return false; unique[k] = 1; return true; }).slice(0, 12);
        if (!hits.length) {
          results.innerHTML = '<div class="set-nav-results__empty">No matches</div>';
        } else {
          results.innerHTML = hits.map(function (h) {
            return '<a class="set-nav-results__item" data-goto="' + escapeHtml(h.panel) + '">' + escapeHtml(h.text) + '<small>in ' + escapeHtml(h.panelName) + '</small></a>';
          }).join('');
          results.querySelectorAll('[data-goto]').forEach(function (a) {
            a.addEventListener('click', function () {
              activatePanel(a.getAttribute('data-goto'));
              results.classList.remove('is-open');
              searchInput.value = '';
            });
          });
        }
        results.classList.add('is-open');
      });
      document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !results.contains(e.target)) results.classList.remove('is-open');
      });
    }

    // Apply language bar widths from data-w
    document.querySelectorAll('.set-lang-item__bar span[data-w]').forEach(function (s) {
      var w = s.getAttribute('data-w');
      requestAnimationFrame(function () { s.style.width = w + '%'; });
    });

    // Save buttons -> toast
    document.querySelectorAll('[data-set-save]').forEach(function (b) {
      b.addEventListener('click', function () {
        orchidToast('Settings saved successfully.', { type: 'success', title: 'Saved' });
      });
    });

    // Branding — swatches
    var swatches = document.querySelectorAll('.set-color-swatch');
    var brandPreview = document.querySelector('[data-set-brand-preview]');
    var brandBtn = document.querySelector('[data-set-brand-btn]');
    var brandLogo = document.querySelector('[data-set-brand-logo]');
    swatches.forEach(function (s) {
      s.style.background = s.getAttribute('data-color');
      s.style.color = s.getAttribute('data-color');
      s.addEventListener('click', function () {
        swatches.forEach(function (x) { x.classList.remove('is-active'); });
        s.classList.add('is-active');
        var c = s.getAttribute('data-color');
        if (brandPreview) brandPreview.style.borderColor = c;
        if (brandBtn) brandBtn.style.background = c;
        if (brandLogo) brandLogo.style.background = c;
      });
    });

    // Regenerate / Revoke key
    document.querySelectorAll('[data-set-regen]').forEach(function (b) {
      b.addEventListener('click', function () {
        orchidToast('API key regenerated. Copy it now — it won’t be shown again.', { type: 'warning', title: 'Regenerated' });
      });
    });
    document.querySelectorAll('[data-set-revoke]').forEach(function (b) {
      b.addEventListener('click', function () {
        var row = b.closest('tr');
        if (row) row.remove();
        orchidToast('API key revoked.', { type: 'danger' });
      });
    });

    // Webhook test-fire
    document.querySelectorAll('[data-set-webhook-test]').forEach(function (b) {
      b.addEventListener('click', function () {
        orchidToast('Test event fired — 200 OK in 132ms', { type: 'success', title: 'Delivered' });
      });
    });

    // Backup now
    var backupBtn = document.querySelector('[data-set-backup-now]');
    if (backupBtn) {
      backupBtn.addEventListener('click', function () {
        backupBtn.disabled = true;
        var original = backupBtn.innerHTML;
        backupBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Backing up…';
        setTimeout(function () {
          backupBtn.disabled = false;
          backupBtn.innerHTML = original;
          orchidToast('Backup complete — 1.4 GB stored to S3.', { type: 'success', title: 'Backup ready' });
        }, 1800);
      });
    }
  }

  // ============================================================
  // INTEGRATIONS
  // ============================================================
  function initIntegrations() {
    var grid = document.querySelector('[data-int-grid]');
    if (!grid) return;

    var integrations = [
      { name: 'Slack', key: 'S',   cat: 'Communication', desc: 'Send channel notifications and receive slash commands.', connected: true },
      { name: 'Discord', key: 'D', cat: 'Communication', desc: 'Post updates to Discord servers via webhooks.', connected: false },
      { name: 'Microsoft Teams', key: 'T', cat: 'Communication', desc: 'Push alerts to Teams channels in real-time.', connected: false },
      { name: 'Google Analytics', key: 'G', cat: 'Analytics', desc: 'Sync page views, events, and conversion goals.', connected: true },
      { name: 'Mixpanel', key: 'M', cat: 'Analytics', desc: 'Track product events and user cohorts.', connected: false },
      { name: 'Amplitude', key: 'A', cat: 'Analytics', desc: 'Behavioral analytics with journey mapping.', connected: false },
      { name: 'Stripe', key: 'St', cat: 'Payments', desc: 'Accept card payments and subscription billing.', connected: true },
      { name: 'PayPal', key: 'P', cat: 'Payments', desc: 'Enable PayPal checkout at the register.', connected: false },
      { name: 'Square', key: 'Sq', cat: 'Payments', desc: 'Point-of-sale, invoicing and card readers.', connected: false },
      { name: 'Google Drive', key: 'G', cat: 'Storage', desc: 'Attach and preview Drive files inside Orchid.', connected: false },
      { name: 'Dropbox', key: 'Dr', cat: 'Storage', desc: 'Sync files and folders to Dropbox.', connected: true },
      { name: 'Amazon S3', key: 'Aw', cat: 'Storage', desc: 'Store backups and assets in S3 buckets.', connected: true },
      { name: 'Salesforce', key: 'Sf', cat: 'CRM', desc: 'Two-way contact and deal sync with SFDC.', connected: false },
      { name: 'HubSpot', key: 'H', cat: 'CRM', desc: 'Push leads to HubSpot workflows.', connected: false },
      { name: 'Mailchimp', key: 'Mc', cat: 'Email', desc: 'Sync audiences and trigger campaigns.', connected: false },
      { name: 'SendGrid', key: 'Sg', cat: 'Email', desc: 'Deliver transactional email at scale.', connected: true },
      { name: 'Resend', key: 'R', cat: 'Email', desc: 'Developer-first email API with templates.', connected: false },
      { name: 'Okta', key: 'O', cat: 'Auth', desc: 'Single sign-on with SAML and SCIM provisioning.', connected: false },
      { name: 'Auth0', key: 'A', cat: 'Auth', desc: 'Universal login, social & enterprise IdPs.', connected: true },
      { name: 'Google SSO', key: 'G', cat: 'Auth', desc: 'Sign in with Google Workspace accounts.', connected: true },
      { name: 'Zendesk', key: 'Z', cat: 'Support', desc: 'Sync support tickets and macros.', connected: false },
      { name: 'Intercom', key: 'I', cat: 'Support', desc: 'Chat with users directly from Orchid.', connected: false },
      { name: 'Zoom', key: 'D', cat: 'Communication', desc: 'Create meetings and join links inline.', connected: false },
      { name: 'GitHub', key: 'R', cat: 'DevOps', desc: 'Link PRs and commits to work items.', connected: true }
    ];

    var activeFilter = 'All';
    var searchTerm = '';
    var currentIntegration = null;

    function render() {
      var list = integrations.filter(function (i) {
        if (activeFilter === 'Connected' && !i.connected) return false;
        if (activeFilter === 'Available' && i.connected) return false;
        if (activeFilter === 'Popular' && ['Slack', 'Stripe', 'Google Analytics', 'Okta', 'GitHub', 'Dropbox'].indexOf(i.name) === -1) return false;
        if (['All', 'Connected', 'Available', 'Popular'].indexOf(activeFilter) === -1 && i.cat !== activeFilter) return false;
        if (searchTerm) {
          var t = searchTerm.toLowerCase();
          if (i.name.toLowerCase().indexOf(t) === -1 && i.desc.toLowerCase().indexOf(t) === -1) return false;
        }
        return true;
      });

      if (!list.length) {
        grid.innerHTML = '<div class="sys-empty" style="grid-column:1/-1"><i class="bi bi-search"></i><p>No integrations match your filters.</p></div>';
        return;
      }

      grid.innerHTML = list.map(function (i) {
        var pill = i.connected
          ? '<span class="int-card__pill"><i class="bi bi-circle-fill"></i>Connected</span>'
          : '';
        var btn = i.connected
          ? '<button type="button" class="btn btn-sm btn-outline-secondary" data-int-manage="' + escapeHtml(i.name) + '"><i class="bi bi-sliders me-1"></i>Manage</button>'
          : '<button type="button" class="btn btn-sm btn-primary" data-int-connect="' + escapeHtml(i.name) + '"><i class="bi bi-plug me-1"></i>Connect</button>';
        var initial = i.name.charAt(0).toUpperCase();
        return '' +
          '<div class="int-card" data-int-name="' + escapeHtml(i.name) + '">' +
            '<div class="int-card__head">' +
              '<span class="int-card__logo" data-logo-key="' + escapeHtml(i.key) + '">' + escapeHtml(initial) + '</span>' +
              pill +
            '</div>' +
            '<div>' +
              '<h6 class="int-card__title">' + escapeHtml(i.name) + '</h6>' +
              '<p class="int-card__desc">' + escapeHtml(i.desc) + '</p>' +
            '</div>' +
            '<div class="int-card__meta">' +
              '<span class="int-cat-chip">' + escapeHtml(i.cat) + '</span>' +
              btn +
            '</div>' +
          '</div>';
      }).join('');

      grid.querySelectorAll('.int-card__logo').forEach(function (el) {
        el.setAttribute('style', integrationLogoStyle(el.getAttribute('data-logo-key')));
      });
      grid.querySelectorAll('[data-int-connect]').forEach(function (b) {
        b.addEventListener('click', function (e) {
          e.stopPropagation();
          openConnectModal(b.getAttribute('data-int-connect'));
        });
      });
      grid.querySelectorAll('[data-int-manage]').forEach(function (b) {
        b.addEventListener('click', function (e) {
          e.stopPropagation();
          openManageDrawer(b.getAttribute('data-int-manage'));
        });
      });
      grid.querySelectorAll('.int-card').forEach(function (c) {
        c.addEventListener('click', function () {
          var name = c.getAttribute('data-int-name');
          var found = integrations.filter(function (i) { return i.name === name; })[0];
          if (found && found.connected) openManageDrawer(name);
        });
      });
      updateTabCounts();
    }

    function updateTabCounts() {
      var counts = {
        All: integrations.length,
        Connected: integrations.filter(function (i) { return i.connected; }).length,
        Available: integrations.filter(function (i) { return !i.connected; }).length
      };
      document.querySelectorAll('[data-int-tab]').forEach(function (t) {
        var key = t.getAttribute('data-int-tab');
        var c = t.querySelector('.int-tabs__count');
        if (c && counts[key] != null) c.textContent = counts[key];
      });
    }

    // Tabs
    document.querySelectorAll('[data-int-tab]').forEach(function (t) {
      t.addEventListener('click', function () {
        document.querySelectorAll('[data-int-tab]').forEach(function (x) { x.classList.remove('is-active'); });
        t.classList.add('is-active');
        activeFilter = t.getAttribute('data-int-tab');
        render();
      });
    });

    // Category select
    var catFilter = document.querySelector('[data-int-category]');
    if (catFilter) catFilter.addEventListener('change', function () {
      activeFilter = this.value || 'All';
      document.querySelectorAll('[data-int-tab]').forEach(function (x) { x.classList.remove('is-active'); });
      var m = document.querySelector('[data-int-tab="' + activeFilter + '"]');
      if (m) m.classList.add('is-active');
      render();
    });

    var searchInput = document.querySelector('[data-int-search]');
    if (searchInput) searchInput.addEventListener('input', function () { searchTerm = this.value; render(); });

    // Connect modal
    var modalEl = document.getElementById('intConnectModal');
    var modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    function openConnectModal(name) {
      currentIntegration = name;
      var found = integrations.filter(function (i) { return i.name === name; })[0];
      if (!found || !modal) return;
      var logo = modalEl.querySelector('[data-int-modal-logo]');
      var title = modalEl.querySelector('[data-int-modal-title]');
      var desc = modalEl.querySelector('[data-int-modal-desc]');
      logo.setAttribute('style', integrationLogoStyle(found.key));
      logo.textContent = found.name.charAt(0);
      title.textContent = 'Connect ' + found.name;
      desc.textContent = 'Orchid is requesting the following access to your ' + found.name + ' account.';
      modal.show();
    }

    var confirmBtn = document.querySelector('[data-int-connect-confirm]');
    if (confirmBtn) confirmBtn.addEventListener('click', function () {
      if (!currentIntegration) return;
      integrations = integrations.map(function (i) {
        return i.name === currentIntegration ? Object.assign({}, i, { connected: true }) : i;
      });
      if (modal) modal.hide();
      orchidToast(currentIntegration + ' connected successfully.', { type: 'success', title: 'Connected' });
      currentIntegration = null;
      render();
    });

    // Manage drawer
    var drawerEl = document.getElementById('intManageDrawer');
    var drawer = drawerEl ? new bootstrap.Offcanvas(drawerEl) : null;
    function openManageDrawer(name) {
      currentIntegration = name;
      var found = integrations.filter(function (i) { return i.name === name; })[0];
      if (!found || !drawer) return;
      drawerEl.querySelector('[data-int-drawer-title]').textContent = found.name;
      drawerEl.querySelector('[data-int-drawer-cat]').textContent = found.cat;
      var logo = drawerEl.querySelector('[data-int-drawer-logo]');
      logo.setAttribute('style', integrationLogoStyle(found.key));
      logo.textContent = found.name.charAt(0);
      drawer.show();
    }

    var disconnectBtn = document.querySelector('[data-int-disconnect]');
    if (disconnectBtn) disconnectBtn.addEventListener('click', function () {
      if (!currentIntegration) return;
      if (!confirm('Disconnect ' + currentIntegration + '? Data will no longer sync.')) return;
      integrations = integrations.map(function (i) {
        return i.name === currentIntegration ? Object.assign({}, i, { connected: false }) : i;
      });
      if (drawer) drawer.hide();
      orchidToast(currentIntegration + ' disconnected.', { type: 'danger' });
      currentIntegration = null;
      render();
    });

    render();
  }

  // ============================================================
  // AUDIT LOGS
  // ============================================================
  function initAuditLogs() {
    var tbody = document.querySelector('[data-audit-body]');
    if (!tbody) return;

    var events = generateAuditEvents();

    function generateAuditEvents() {
      var actors = [
        { name: 'Alex Kim', initials: 'AK', color: 'primary' },
        { name: 'Sarah Miller', initials: 'SM', color: 'info' },
        { name: 'James Doe', initials: 'JD', color: 'success' },
        { name: 'Ava Lee', initials: 'AL', color: 'warning' },
        { name: 'Ryan Green', initials: 'RG', color: 'danger' },
        { name: 'Emma Watson', initials: 'EW', color: 'primary' }
      ];
      var ips = ['192.168.4.21', '10.0.0.14', '172.16.8.9', '203.0.113.42', '198.51.100.7'];
      var locs = ['New York, US', 'London, UK', 'Berlin, DE', 'Sydney, AU', 'Toronto, CA'];
      var templates = [
        { action: 'create', label: 'Create', resource: 'Deals', sev: 'info', before: null, after: { title: 'Acme Enterprise Deal', value: 48000, stage: 'qualified' } },
        { action: 'update', label: 'Update', resource: 'Users', sev: 'info', before: { role: 'editor', mfa: false }, after: { role: 'admin', mfa: true } },
        { action: 'delete', label: 'Delete', resource: 'Files', sev: 'warning', before: { name: 'q2-forecast.xlsx', size: '2.4MB' }, after: null },
        { action: 'login', label: 'Login', resource: 'Auth', sev: 'info', before: null, after: { method: 'password', mfa: true } },
        { action: 'logout', label: 'Logout', resource: 'Auth', sev: 'info', before: null, after: { sessionDuration: '2h 14m' } },
        { action: 'export', label: 'Export', resource: 'Invoices', sev: 'info', before: null, after: { format: 'CSV', rows: 1240 } },
        { action: 'import', label: 'Import', resource: 'Contacts', sev: 'info', before: null, after: { format: 'CSV', rows: 320, errors: 4 } },
        { action: 'perm', label: 'Permission Change', resource: 'Roles', sev: 'warning', before: { canDelete: false }, after: { canDelete: true } },
        { action: 'setting', label: 'Setting Change', resource: 'Settings', sev: 'info', before: { timezone: 'UTC' }, after: { timezone: 'America/New_York' } },
        { action: 'delete', label: 'Delete', resource: 'API Keys', sev: 'critical', before: { name: 'production-key-prod-01' }, after: null },
        { action: 'login', label: 'Failed Login', resource: 'Auth', sev: 'critical', before: null, after: { reason: 'invalid credentials', attempts: 5 } }
      ];
      var out = [];
      var now = Date.now();
      for (var i = 0; i < 30; i++) {
        var t = templates[i % templates.length];
        var actor = actors[i % actors.length];
        out.push({
          id: 'evt_' + (10240 + i),
          time: new Date(now - (i * (1000 * 60 * (8 + (i % 13))))),
          actor: actor,
          action: t.action,
          label: t.label,
          resource: t.resource,
          resourceId: '#' + (4820 + i * 17),
          sev: t.sev,
          ip: ips[i % ips.length],
          loc: locs[i % locs.length],
          before: t.before,
          after: t.after,
          reqId: 'req_' + Math.random().toString(36).slice(2, 12),
          sessionId: 'sess_' + Math.random().toString(36).slice(2, 10),
          ua: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 12_5) AppleWebKit/537.36'
        });
      }
      return out;
    }

    var filters = { search: '', actor: '', action: '', resource: '', sev: '' };

    function buildDiff(before, after) {
      var lines = [];
      var keys = {};
      if (before) Object.keys(before).forEach(function (k) { keys[k] = 1; });
      if (after) Object.keys(after).forEach(function (k) { keys[k] = 1; });
      var ks = Object.keys(keys);
      if (!ks.length) {
        if (before) lines.push({ mark: '-', text: JSON.stringify(before), cls: 'rem' });
        if (after)  lines.push({ mark: '+', text: JSON.stringify(after), cls: 'add' });
      } else {
        lines.push({ mark: ' ', text: '{', cls: 'same' });
        ks.forEach(function (k) {
          var bv = before ? before[k] : undefined;
          var av = after ? after[k] : undefined;
          if (bv === undefined && av !== undefined) {
            lines.push({ mark: '+', text: '  "' + k + '": ' + JSON.stringify(av), cls: 'add' });
          } else if (bv !== undefined && av === undefined) {
            lines.push({ mark: '-', text: '  "' + k + '": ' + JSON.stringify(bv), cls: 'rem' });
          } else if (JSON.stringify(bv) !== JSON.stringify(av)) {
            lines.push({ mark: '-', text: '  "' + k + '": ' + JSON.stringify(bv), cls: 'rem' });
            lines.push({ mark: '+', text: '  "' + k + '": ' + JSON.stringify(av), cls: 'add' });
          } else {
            lines.push({ mark: ' ', text: '  "' + k + '": ' + JSON.stringify(bv), cls: 'same' });
          }
        });
        lines.push({ mark: ' ', text: '}', cls: 'same' });
      }
      return lines.map(function (l) {
        return '<span class="audit-diff__line ' + l.cls + '" data-mark="' + l.mark + '">' + escapeHtml(l.text) + '</span>';
      }).join('');
    }

    function render() {
      var list = events.filter(function (e) {
        if (filters.search) {
          var t = filters.search.toLowerCase();
          var hay = (e.actor.name + ' ' + e.label + ' ' + e.resource + ' ' + e.resourceId + ' ' + e.ip).toLowerCase();
          if (hay.indexOf(t) === -1) return false;
        }
        if (filters.actor && e.actor.name !== filters.actor) return false;
        if (filters.action && e.action !== filters.action) return false;
        if (filters.resource && e.resource !== filters.resource) return false;
        if (filters.sev && e.sev !== filters.sev) return false;
        return true;
      });

      if (!list.length) {
        tbody.innerHTML = '<tr><td colspan="7"><div class="sys-empty"><i class="bi bi-inbox"></i><p>No audit events match your filters.</p></div></td></tr>';
        updateCount(0);
        return;
      }
      updateCount(list.length);

      tbody.innerHTML = list.map(function (e) {
        var actorHtml =
          '<div class="audit-actor">' +
            '<span class="avatar avatar-xs bg-' + e.actor.color + '-subtle text-' + e.actor.color + '">' + escapeHtml(e.actor.initials) + '</span>' +
            '<div><div>' + escapeHtml(e.actor.name) + '</div><small>' + escapeHtml(e.actor.name.toLowerCase().replace(' ', '.') + '@orchid.io') + '</small></div>' +
          '</div>';
        var detailId = 'det_' + e.id;
        return '' +
          '<tr class="audit-row" data-target="' + detailId + '">' +
            '<td class="audit-row__time">' + fmtDate(e.time) + '&nbsp;' + fmtTime(e.time) + '</td>' +
            '<td>' + actorHtml + '</td>' +
            '<td><span class="audit-action-badge ' + e.action + '">' + escapeHtml(e.label) + '</span></td>' +
            '<td>' + escapeHtml(e.resource) + ' <span class="text-body-secondary">' + escapeHtml(e.resourceId) + '</span></td>' +
            '<td><span class="audit-sev ' + e.sev + '"></span>' + escapeHtml(e.sev.charAt(0).toUpperCase() + e.sev.slice(1)) + '</td>' +
            '<td><code>' + escapeHtml(e.ip) + '</code><br><small class="text-body-secondary">' + escapeHtml(e.loc) + '</small></td>' +
            '<td class="text-end"><i class="bi bi-chevron-right"></i></td>' +
          '</tr>' +
          '<tr class="audit-detail-row" id="' + detailId + '"><td colspan="7">' +
            '<div class="audit-detail"><div class="audit-detail__grid">' +
              '<div><h6>Change diff (before &rarr; after)</h6><div class="audit-diff">' + buildDiff(e.before, e.after) + '</div></div>' +
              '<div><h6>Request metadata</h6><ul class="audit-meta-list">' +
                '<li><span class="k">Event ID</span><span class="v">' + escapeHtml(e.id) + '</span></li>' +
                '<li><span class="k">Request ID</span><span class="v">' + escapeHtml(e.reqId) + '</span></li>' +
                '<li><span class="k">Session ID</span><span class="v">' + escapeHtml(e.sessionId) + '</span></li>' +
                '<li><span class="k">User-Agent</span><span class="v">' + escapeHtml(e.ua) + '</span></li>' +
                '<li><span class="k">IP</span><span class="v">' + escapeHtml(e.ip) + '</span></li>' +
                '<li><span class="k">Location</span><span class="v">' + escapeHtml(e.loc) + '</span></li>' +
              '</ul></div>' +
            '</div></div>' +
          '</td></tr>';
      }).join('');

      tbody.querySelectorAll('.audit-row').forEach(function (row) {
        row.addEventListener('click', function () {
          var id = row.getAttribute('data-target');
          var det = document.getElementById(id);
          if (!det) return;
          row.classList.toggle('is-open');
          det.classList.toggle('is-open');
          var chev = row.querySelector('.bi-chevron-right, .bi-chevron-down');
          if (chev) chev.classList.toggle('bi-chevron-down');
        });
      });
    }

    function updateCount(n) {
      var el = document.querySelector('[data-audit-count]');
      if (el) el.textContent = n;
    }

    document.querySelectorAll('[data-audit-filter]').forEach(function (input) {
      input.addEventListener('input', function () {
        filters[input.getAttribute('data-audit-filter')] = input.value;
        render();
      });
      input.addEventListener('change', function () {
        filters[input.getAttribute('data-audit-filter')] = input.value;
        render();
      });
    });

    var reset = document.querySelector('[data-audit-reset]');
    if (reset) reset.addEventListener('click', function () {
      filters = { search: '', actor: '', action: '', resource: '', sev: '' };
      document.querySelectorAll('[data-audit-filter]').forEach(function (i) { i.value = ''; });
      render();
    });

    var exportBtn = document.querySelector('[data-audit-export]');
    if (exportBtn) exportBtn.addEventListener('click', function () {
      orchidToast('Exported ' + events.length + ' audit events to CSV.', { type: 'success', title: 'Export ready' });
    });

    render();
  }

  // ============================================================
  // SYSTEM LOGS
  // ============================================================
  function initSystemLogs() {
    var list = document.querySelector('[data-slog-list]');
    if (!list) return;

    var services = ['api', 'web', 'worker', 'db', 'cache', 'cron'];
    var messages = [
      { lvl: 'error', svc: 'db',     m: 'Failed to acquire DB connection after 5000ms', ctx: { pool: 'primary', size: 20, waiting: 4 } },
      { lvl: 'warn',  svc: 'cache',  m: 'Cache miss for key user:4821', ctx: { key: 'user:4821', ttl: 300 } },
      { lvl: 'info',  svc: 'api',    m: 'Handled POST /api/deals in 234ms', ctx: { status: 201, userId: 4821 } },
      { lvl: 'debug', svc: 'worker', m: 'Job report.generate started with args {reportId=482}', ctx: { queue: 'reports', jobId: 'j_9821' } },
      { lvl: 'info',  svc: 'api',    m: 'GET /api/users?page=2 -> 200 in 41ms', ctx: { rows: 25, cache: 'hit' } },
      { lvl: 'warn',  svc: 'api',    m: 'Rate limit approaching for tenant tnt_2938 (92/100)', ctx: { window: '1m', tenant: 'tnt_2938' } },
      { lvl: 'error', svc: 'worker', m: 'Job invoice.send failed: SMTP timeout to smtp.sendgrid.net', ctx: { jobId: 'j_9822', retries: 3 } },
      { lvl: 'info',  svc: 'cron',   m: 'Cron heartbeat.check completed in 12ms', ctx: {} },
      { lvl: 'debug', svc: 'cache',  m: 'Evicted 128 keys under memory pressure', ctx: { evicted: 128, freeMb: 240 } },
      { lvl: 'info',  svc: 'web',    m: 'User alex@orchid.io logged in from 192.168.4.21', ctx: { userId: 1, ip: '192.168.4.21' } },
      { lvl: 'warn',  svc: 'db',     m: 'Slow query 1240ms on invoices: SELECT * FROM invoices WHERE tenant = ?', ctx: { duration: 1240 } },
      { lvl: 'error', svc: 'api',    m: 'Unhandled exception in POST /api/webhooks: TypeError undefined is not an object', ctx: { stack: 'at webhookHandler.js:82' } },
      { lvl: 'info',  svc: 'worker', m: 'Processed 240 email queue items in 3.2s', ctx: { throughput: 75 } },
      { lvl: 'debug', svc: 'api',    m: 'Auth token validated for session sess_9821', ctx: { session: 'sess_9821' } },
      { lvl: 'info',  svc: 'api',    m: 'GET /api/dashboard -> 200 in 88ms', ctx: {} },
      { lvl: 'warn',  svc: 'worker', m: 'Retrying job export.contacts (attempt 2 of 5)', ctx: { jobId: 'j_9823' } },
      { lvl: 'error', svc: 'cache',  m: 'Redis connection lost, falling back to memory store', ctx: { addr: 'redis://cache-01:6379' } },
      { lvl: 'info',  svc: 'cache',  m: 'Redis connection restored', ctx: {} },
      { lvl: 'info',  svc: 'api',    m: 'PATCH /api/settings/branding -> 200 in 62ms', ctx: { userId: 1 } },
      { lvl: 'debug', svc: 'db',     m: 'Migration 20260722_add_index_deals_stage applied', ctx: {} },
      { lvl: 'info',  svc: 'web',    m: 'Rendered /dashboard SSR in 74ms', ctx: {} },
      { lvl: 'warn',  svc: 'api',    m: 'Deprecated endpoint /api/v1/users hit', ctx: { by: 'legacy-client' } },
      { lvl: 'error', svc: 'db',     m: 'Failed to acquire DB connection after 5000ms', ctx: { pool: 'replica' } },
      { lvl: 'info',  svc: 'cron',   m: 'Cron nightly.backup started', ctx: {} },
      { lvl: 'info',  svc: 'cron',   m: 'Cron nightly.backup completed in 4m 22s, 1.4GB uploaded', ctx: { size: '1.4GB' } },
      { lvl: 'debug', svc: 'worker', m: 'Enqueued 4 webhook.deliver jobs', ctx: { count: 4 } },
      { lvl: 'info',  svc: 'api',    m: 'POST /api/auth/logout -> 204 in 14ms', ctx: {} },
      { lvl: 'warn',  svc: 'cache',  m: 'Cache eviction rate 20% above baseline', ctx: {} },
      { lvl: 'info',  svc: 'api',    m: 'GET /api/audit-logs?range=7d -> 200 in 156ms', ctx: { rows: 480 } },
      { lvl: 'error', svc: 'api',    m: 'Payment webhook signature mismatch from stripe.com', ctx: { code: 'invalid_sig' } },
      { lvl: 'debug', svc: 'web',    m: 'HMR update accepted for src/Dashboard.tsx', ctx: {} },
      { lvl: 'info',  svc: 'api',    m: 'PUT /api/users/482 -> 200 in 74ms', ctx: {} },
      { lvl: 'warn',  svc: 'worker', m: 'Worker memory usage at 82% (658MB / 800MB)', ctx: { rssMb: 658 } },
      { lvl: 'info',  svc: 'db',     m: 'Connection pool resized 10 -> 20', ctx: {} },
      { lvl: 'error', svc: 'cron',   m: 'Cron report.weekly failed: query timed out', ctx: { after: '30s' } },
      { lvl: 'info',  svc: 'api',    m: 'DELETE /api/api-keys/482 -> 204 in 22ms', ctx: {} },
      { lvl: 'debug', svc: 'api',    m: 'Loaded feature flag matrix for tenant tnt_2938', ctx: { flags: 12 } },
      { lvl: 'info',  svc: 'web',    m: 'GET /settings 200 in 34ms', ctx: {} },
      { lvl: 'warn',  svc: 'db',     m: 'Long-running transaction 8.4s on tenant_2938', ctx: { duration: 8400 } },
      { lvl: 'info',  svc: 'api',    m: 'Handled POST /api/integrations/slack in 512ms', ctx: {} }
    ];

    var entries = messages.map(function (m, i) {
      return Object.assign({}, m, { id: 'log_' + i, time: new Date(Date.now() - (i * 1000 * (30 + i * 3))) });
    });

    var state = {
      activeLevels: { error: true, warn: true, info: true, debug: true },
      activeSvcs: { api: true, web: true, worker: true, db: true, cache: true, cron: true },
      search: '',
      regex: false,
      live: false
    };

    var liveTimer = null;

    function render() {
      var filtered = entries.filter(function (e) {
        if (!state.activeLevels[e.lvl]) return false;
        if (!state.activeSvcs[e.svc]) return false;
        if (state.search) {
          try {
            if (state.regex) {
              var re = new RegExp(state.search, 'i');
              if (!re.test(e.m)) return false;
            } else {
              if (e.m.toLowerCase().indexOf(state.search.toLowerCase()) === -1) return false;
            }
          } catch (_) { return true; }
        }
        return true;
      });

      var visibleEl = document.querySelector('[data-slog-visible]');
      var totalEl = document.querySelector('[data-slog-total]');
      if (visibleEl) visibleEl.textContent = filtered.length;
      if (totalEl) totalEl.textContent = entries.length;

      if (!filtered.length) {
        list.innerHTML = '<div class="sys-empty"><i class="bi bi-file-earmark-code"></i><p>No log entries match your filters.</p></div>';
        return;
      }

      list.innerHTML = filtered.map(function (e) {
        return '' +
          '<div class="slog-entry" data-id="' + e.id + '">' +
            '<span class="slog-entry__time" title="' + fmtDate(e.time) + ' ' + fmtTime(e.time) + '">' + relativeTime(e.time) + '</span>' +
            '<span class="slog-entry__level lvl-' + e.lvl + '">' + e.lvl.toUpperCase() + '</span>' +
            '<span class="slog-entry__svc">' + e.svc + '</span>' +
            '<div class="slog-entry__msg">' + highlight(e.m, state.search, state.regex) +
              '<pre class="slog-entry__ctx">' + escapeHtml(formatJSON(e.ctx)) + '</pre>' +
            '</div>' +
            '<button type="button" class="slog-entry__ctx-btn" aria-label="Toggle context"><i class="bi bi-braces"></i></button>' +
          '</div>';
      }).join('');

      list.querySelectorAll('.slog-entry').forEach(function (row) {
        row.addEventListener('click', function () { row.classList.toggle('is-open'); });
      });
    }

    // Level chips
    document.querySelectorAll('[data-slog-level]').forEach(function (chip) {
      chip.addEventListener('click', function () {
        var l = chip.getAttribute('data-slog-level');
        state.activeLevels[l] = !state.activeLevels[l];
        chip.classList.toggle('is-on', state.activeLevels[l]);
        render();
      });
    });

    // Service select (multi via checkboxes)
    document.querySelectorAll('[data-slog-svc]').forEach(function (cb) {
      cb.addEventListener('change', function () {
        state.activeSvcs[cb.value] = cb.checked;
        render();
      });
    });

    // Search + regex
    var searchInput = document.querySelector('[data-slog-search]');
    if (searchInput) searchInput.addEventListener('input', function () { state.search = this.value; render(); });
    var regexBtn = document.querySelector('[data-slog-regex]');
    if (regexBtn) regexBtn.addEventListener('click', function () {
      state.regex = !state.regex;
      regexBtn.classList.toggle('is-on', state.regex);
      render();
    });

    // Live tail
    var liveBtn = document.querySelector('[data-slog-live]');
    if (liveBtn) liveBtn.addEventListener('click', function () {
      state.live = !state.live;
      liveBtn.classList.toggle('is-on', state.live);
      liveBtn.querySelector('.slog-live__label').textContent = state.live ? 'Live tail on' : 'Live tail';
      if (state.live) startLive(); else stopLive();
    });

    function startLive() {
      if (liveTimer) clearInterval(liveTimer);
      liveTimer = setInterval(function () {
        var pool = messages;
        var seed = pool[Math.floor(Math.random() * pool.length)];
        var next = Object.assign({}, seed, { id: 'log_' + Date.now(), time: new Date() });
        entries.unshift(next);
        if (entries.length > 220) entries.pop();
        render();
        var first = list.querySelector('.slog-entry');
        if (first) first.classList.add('is-new');
      }, 2000);
    }
    function stopLive() {
      if (liveTimer) { clearInterval(liveTimer); liveTimer = null; }
    }

    var exportBtn = document.querySelector('[data-slog-export]');
    if (exportBtn) exportBtn.addEventListener('click', function () {
      orchidToast('Exported ' + entries.length + ' log lines to log-export.txt', { type: 'success', title: 'Downloaded' });
    });

    // Volume chart
    var chartEl = document.querySelector('[data-slog-chart]');
    if (chartEl && window.Chart) {
      new Chart(chartEl.getContext('2d'), {
        type: 'line',
        data: {
          labels: ['0m', '5m', '10m', '15m', '20m', '25m', '30m', '35m', '40m', '45m', '50m', '55m', '60m'],
          datasets: [{
            data: [12, 18, 14, 24, 33, 28, 42, 38, 30, 45, 52, 60, 48],
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79,70,229,.14)',
            fill: true,
            tension: .35,
            pointRadius: 0,
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false }, tooltip: { enabled: true } },
          scales: {
            x: { grid: { display: false }, ticks: { color: '#8891a8', font: { size: 9 } } },
            y: { grid: { color: 'rgba(148,163,184,.1)' }, ticks: { color: '#8891a8', font: { size: 9 } } }
          }
        }
      });
    }

    render();
  }

  // ============================================================
  // MAINTENANCE MODE
  // ============================================================
  function initMaintenance() {
    var toggle = document.querySelector('[data-maint-toggle]');
    var heroCard = document.querySelector('[data-maint-hero]');
    var statusText = document.querySelector('[data-maint-status]');
    var confirmModalEl = document.getElementById('maintConfirmModal');
    var confirmModal = confirmModalEl ? new bootstrap.Modal(confirmModalEl) : null;
    var confirmBtn = document.querySelector('[data-maint-confirm]');
    var confirmCheck = document.querySelector('[data-maint-doublecheck]');

    function reflectState(on) {
      if (!heroCard || !statusText) return;
      heroCard.classList.toggle('is-on', on);
      var icon = heroCard.querySelector('.maint-hero-toggle__icon i');
      if (icon) icon.className = on ? 'bi bi-cone-striped' : 'bi bi-check-circle';
      statusText.textContent = on
        ? 'Maintenance mode is ACTIVE. Visitors will see the maintenance page.'
        : 'System is live. All visitors can access the app normally.';
      var status = heroCard.querySelector('[data-maint-status-badge]');
      if (status) {
        status.className = 'badge ' + (on ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success');
        status.textContent = on ? 'Active' : 'Live';
      }
    }

    if (toggle) toggle.addEventListener('change', function (e) {
      if (this.checked) {
        // Prevent immediate toggle — confirm first
        this.checked = false;
        if (confirmModal) confirmModal.show();
      } else {
        reflectState(false);
        orchidToast('Maintenance mode disabled. System is live.', { type: 'success', title: 'Disabled' });
      }
    });
    if (confirmCheck && confirmBtn) confirmCheck.addEventListener('change', function () { confirmBtn.disabled = !this.checked; });
    if (confirmBtn) confirmBtn.addEventListener('click', function () {
      if (toggle) toggle.checked = true;
      reflectState(true);
      if (confirmModal) confirmModal.hide();
      if (confirmCheck) { confirmCheck.checked = false; confirmBtn.disabled = true; }
      orchidToast('Maintenance mode enabled. Whitelisted IPs may still access the site.', { type: 'warning', title: 'Enabled' });
    });

    // Preview live updates
    var head = document.querySelector('[data-maint-headline]');
    var body = document.querySelector('[data-maint-body-text]');
    var eta = document.querySelector('[data-maint-eta]');
    var progressToggle = document.querySelector('[data-maint-progress]');

    var pHead = document.querySelector('[data-preview-headline]');
    var pBody = document.querySelector('[data-preview-body]');
    var pEta = document.querySelector('[data-preview-eta]');
    var pProgress = document.querySelector('[data-preview-progress]');

    function updatePreview() {
      if (head && pHead) pHead.textContent = head.value || 'We’ll be right back';
      if (body && pBody) pBody.textContent = body.value || 'Our team is deploying updates. Thank you for your patience.';
      if (eta && pEta) pEta.textContent = eta.value ? ('Estimated back online: ' + eta.value) : 'Estimated back online: shortly';
      if (progressToggle && pProgress) pProgress.style.display = progressToggle.checked ? 'block' : 'none';
    }
    [head, body, eta].forEach(function (el) { if (el) el.addEventListener('input', updatePreview); });
    if (progressToggle) progressToggle.addEventListener('change', updatePreview);
    updatePreview();

    // Preview in new tab -> maintenance.html
    var pv = document.querySelector('[data-maint-preview-open]');
    if (pv) pv.addEventListener('click', function () {
      window.open('maintenance.html', '_blank');
    });

    // Schedule form
    var scheduleForm = document.querySelector('[data-maint-schedule-form]');
    var scheduleTable = document.querySelector('[data-maint-schedule-body]');
    if (scheduleForm && scheduleTable) scheduleForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var start = scheduleForm.querySelector('[name="startAt"]').value;
      var end = scheduleForm.querySelector('[name="endAt"]').value;
      var tz = scheduleForm.querySelector('[name="tz"]').value;
      if (!start || !end) { orchidToast('Please pick a start and end time.', { type: 'danger' }); return; }
      var row = document.createElement('tr');
      row.innerHTML =
        '<td>' + escapeHtml(start.replace('T', ' ')) + '</td>' +
        '<td>' + escapeHtml(end.replace('T', ' ')) + '</td>' +
        '<td>' + escapeHtml(tz) + '</td>' +
        '<td><span class="maint-schedule-badge upcoming">Upcoming</span></td>' +
        '<td class="text-end"><button class="btn btn-sm btn-icon" type="button" data-remove-row><i class="bi bi-trash"></i></button></td>';
      scheduleTable.appendChild(row);
      row.querySelector('[data-remove-row]').addEventListener('click', function () { row.remove(); orchidToast('Scheduled window removed.', { type: 'info' }); });
      orchidToast('Maintenance window scheduled.', { type: 'success', title: 'Scheduled' });
      scheduleForm.reset();
    });

    // IP whitelist
    var ipForm = document.querySelector('[data-maint-ip-form]');
    var ipList = document.querySelector('[data-maint-ip-list]');
    if (ipForm && ipList) {
      ipForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var val = ipForm.querySelector('input[name="ip"]').value.trim();
        var label = ipForm.querySelector('input[name="label"]').value.trim() || 'Untitled';
        if (!val) return;
        addIp(val, label);
        ipForm.reset();
        orchidToast('IP ' + val + ' added to whitelist.', { type: 'success' });
      });
      ipList.querySelectorAll('[data-ip-remove]').forEach(bindRemove);
      function addIp(v, l) {
        var el = document.createElement('div');
        el.className = 'maint-ip-item';
        el.innerHTML = '<span>' + escapeHtml(v) + ' <small>&middot; ' + escapeHtml(l) + '</small></span>' +
          '<button type="button" data-ip-remove aria-label="Remove IP"><i class="bi bi-x-lg"></i></button>';
        ipList.appendChild(el);
        bindRemove(el.querySelector('[data-ip-remove]'));
      }
      function bindRemove(btn) {
        btn.addEventListener('click', function () { btn.closest('.maint-ip-item').remove(); orchidToast('IP removed.', { type: 'info' }); });
      }
    }
  }

})();
