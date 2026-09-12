/* ==============================================================
   Orchid — User Management (Access Control) — shared JS
   Covers: roles.html, permissions.html, user-activity.html, login-history.html
   ============================================================== */
(function () {
  'use strict';

  /* ---------- Shared toolkit ---------- */
  const AccessToolkit = {
    users: [
      { id: 'u1',  name: 'Alex Kim',       email: 'alex@orchid.io',    role: 'Super Admin', avatar: 'AK', color: 'primary' },
      { id: 'u2',  name: 'Sarah Miller',   email: 'sarah@orchid.io',   role: 'Admin',       avatar: 'SM', color: 'success' },
      { id: 'u3',  name: 'James Doe',      email: 'james@orchid.io',   role: 'Editor',      avatar: 'JD', color: 'info' },
      { id: 'u4',  name: 'Emma Watson',    email: 'emma@orchid.io',    role: 'Editor',      avatar: 'EW', color: 'primary' },
      { id: 'u5',  name: 'Ryan Green',     email: 'ryan@orchid.io',    role: 'Viewer',      avatar: 'RG', color: 'warning' },
      { id: 'u6',  name: 'Ava Lee',        email: 'ava@orchid.io',     role: 'Viewer',      avatar: 'AL', color: 'danger' },
      { id: 'u7',  name: 'Noah Patel',     email: 'noah@orchid.io',    role: 'Finance Manager', avatar: 'NP', color: 'info' },
      { id: 'u8',  name: 'Mia Chen',       email: 'mia@orchid.io',     role: 'Guest',       avatar: 'MC', color: 'secondary' },
      { id: 'u9',  name: 'Liam Rossi',     email: 'liam@orchid.io',    role: 'Admin',       avatar: 'LR', color: 'primary' },
      { id: 'u10', name: 'Zoe Nakamura',   email: 'zoe@orchid.io',     role: 'Editor',      avatar: 'ZN', color: 'success' }
    ],

    toast(message, variant) {
      variant = variant || 'primary';
      const container = ensureToastContainer();
      const el = document.createElement('div');
      el.className = 'toast align-items-center border-0 text-bg-' + variant;
      el.setAttribute('role', 'status');
      el.setAttribute('aria-live', 'polite');
      el.innerHTML =
        '<div class="d-flex">' +
          '<div class="toast-body">' + escapeHtml(message) + '</div>' +
          '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
        '</div>';
      container.appendChild(el);
      const t = new bootstrap.Toast(el, { delay: 3400 });
      t.show();
      el.addEventListener('hidden.bs.toast', () => el.remove());
    },

    formatRelative(date) {
      const d = date instanceof Date ? date : new Date(date);
      const diff = (Date.now() - d.getTime()) / 1000;
      if (diff < 60)   return Math.max(1, Math.floor(diff)) + 's ago';
      if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
      if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
      if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
      return d.toLocaleDateString();
    },

    formatDuration(sec) {
      if (!sec && sec !== 0) return '—';
      if (sec < 60) return sec + 's';
      const m = Math.floor(sec / 60);
      if (m < 60) return m + 'm ' + (sec % 60 ? (sec % 60) + 's' : '');
      const h = Math.floor(m / 60);
      return h + 'h ' + (m % 60) + 'm';
    },

    renderCountryFlag(iso) {
      if (!iso) return '';
      const cc = iso.toUpperCase();
      const codePoints = [...cc].map(c => 0x1F1E6 - 65 + c.charCodeAt(0));
      try { return String.fromCodePoint(...codePoints); }
      catch (e) { return cc; }
    }
  };

  window.AccessToolkit = AccessToolkit;

  function ensureToastContainer() {
    let c = document.querySelector('[data-access-toast-root]');
    if (!c) {
      c = document.createElement('div');
      c.className = 'toast-container position-fixed top-0 end-0 p-3';
      c.setAttribute('data-access-toast-root', '');
      c.style.zIndex = '1090';
      document.body.appendChild(c);
    }
    return c;
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, m =>
      ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[m])
    );
  }

  const $  = (sel, root) => (root || document).querySelector(sel);
  const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

  /* ==========================================================
     ROLES PAGE
     ========================================================== */
  function initRoles() {
    const root = $('[data-access-page="roles"]');
    if (!root) return;

    const permissionGroups = [
      { key: 'users',     label: 'Users',     icon: 'bi-people' },
      { key: 'content',   label: 'Content',   icon: 'bi-file-earmark-text' },
      { key: 'billing',   label: 'Billing',   icon: 'bi-wallet2' },
      { key: 'analytics', label: 'Analytics', icon: 'bi-graph-up' },
      { key: 'settings',  label: 'Settings',  icon: 'bi-sliders' },
      { key: 'api',       label: 'API',       icon: 'bi-braces' }
    ];
    const actions = ['view', 'create', 'edit', 'delete', 'approve'];

    function makePerms(vals) {
      const out = {};
      permissionGroups.forEach((g, i) => {
        out[g.key] = {};
        actions.forEach((a, ai) => { out[g.key][a] = !!(vals[i] && vals[i][ai]); });
      });
      return out;
    }

    const roles = [
      { id: 'r1', name: 'Super Admin', desc: 'Full unrestricted access to every resource.', users: 2, system: true, scope: 'admin', icon: 'bi-shield-fill-check',
        perms: makePerms([[1,1,1,1,1],[1,1,1,1,1],[1,1,1,1,1],[1,1,1,1,1],[1,1,1,1,1],[1,1,1,1,1]]) },
      { id: 'r2', name: 'Admin', desc: 'Manage most resources except system settings.', users: 5, system: true, scope: 'admin', icon: 'bi-shield-lock',
        perms: makePerms([[1,1,1,1,0],[1,1,1,1,1],[1,1,1,0,1],[1,0,0,0,0],[1,1,1,0,0],[1,0,0,0,0]]) },
      { id: 'r3', name: 'Editor', desc: 'Create and edit content, review analytics.', users: 12, system: true, scope: 'editor', icon: 'bi-pencil-square',
        perms: makePerms([[1,0,0,0,0],[1,1,1,0,1],[0,0,0,0,0],[1,0,0,0,0],[0,0,0,0,0],[0,0,0,0,0]]) },
      { id: 'r4', name: 'Viewer', desc: 'Read-only access to most content.', users: 24, system: true, scope: 'viewer', icon: 'bi-eye',
        perms: makePerms([[1,0,0,0,0],[1,0,0,0,0],[0,0,0,0,0],[1,0,0,0,0],[0,0,0,0,0],[0,0,0,0,0]]) },
      { id: 'r5', name: 'Guest', desc: 'Very limited preview access.', users: 3, system: true, scope: 'guest', icon: 'bi-person',
        perms: makePerms([[0,0,0,0,0],[1,0,0,0,0],[0,0,0,0,0],[0,0,0,0,0],[0,0,0,0,0],[0,0,0,0,0]]) },
      { id: 'r6', name: 'Finance Manager', desc: 'Custom role: full access to billing & finance reports.', users: 4, system: false, scope: 'finance', icon: 'bi-cash-coin',
        perms: makePerms([[1,0,0,0,0],[1,0,0,0,0],[1,1,1,1,1],[1,0,0,0,0],[0,0,0,0,0],[0,0,0,0,0]]) }
    ];

    const listEl   = $('[data-roles-list]', root);
    const detailEl = $('[data-roles-detail]', root);
    let activeRoleId = roles[0].id;
    let dirty = false;

    function renderList() {
      listEl.innerHTML = '';
      roles.forEach(r => {
        const li = document.createElement('li');
        li.className = 'roles-list__item' + (r.id === activeRoleId ? ' is-active' : '');
        li.dataset.scope = r.scope;
        li.dataset.roleId = r.id;
        li.setAttribute('role', 'button');
        li.setAttribute('tabindex', '0');
        li.innerHTML =
          '<span class="roles-list__icon"><i class="bi ' + r.icon + '"></i></span>' +
          '<div class="roles-list__body">' +
            '<p class="roles-list__name">' + escapeHtml(r.name) +
              (r.system ? ' <span class="roles-badge-system">System</span>' : '') +
            '</p>' +
            '<p class="roles-list__desc">' + escapeHtml(r.desc) + '</p>' +
          '</div>' +
          '<div class="roles-list__meta">' +
            '<span class="roles-list__count"><i class="bi bi-people"></i>' + r.users + '</span>' +
            '<i class="bi bi-chevron-right roles-chevron"></i>' +
          '</div>';
        listEl.appendChild(li);
      });
    }

    function renderDetail() {
      const r = roles.find(x => x.id === activeRoleId);
      if (!r) return;
      detailEl.innerHTML =
        '<div class="roles-detail__head">' +
          '<div>' +
            '<input class="form-control roles-detail__title" value="' + escapeHtml(r.name) + '" data-roles-name aria-label="Role name">' +
            '<div class="roles-detail__subline">' +
              '<span><i class="bi bi-people me-1"></i>' + r.users + ' users</span>' +
              '<a href="#" data-roles-view-users>View users</a>' +
              (r.system ? '<span class="roles-badge-system">System role</span>' : '') +
            '</div>' +
          '</div>' +
          '<div class="d-flex gap-2">' +
            '<button class="btn btn-sm btn-outline-secondary" type="button" data-roles-duplicate><i class="bi bi-files me-1"></i>Duplicate</button>' +
            '<button class="btn btn-sm btn-outline-danger" type="button" data-roles-delete' + (r.system ? ' disabled' : '') + '><i class="bi bi-trash me-1"></i>Delete</button>' +
          '</div>' +
        '</div>' +
        '<div class="roles-perm-groups" data-roles-perm-groups></div>' +
        renderUsersMini(r) +
        '<div class="roles-sticky-save">' +
          '<button class="btn btn-outline-secondary btn-sm" type="button" data-roles-reset>Reset</button>' +
          '<button class="btn btn-primary btn-sm" type="button" data-roles-save disabled>Save changes</button>' +
        '</div>';

      const groupsRoot = $('[data-roles-perm-groups]', detailEl);
      permissionGroups.forEach((g, gi) => {
        const groupEl = document.createElement('div');
        groupEl.className = 'roles-perm-group' + (gi === 0 ? ' is-open' : '');
        groupEl.dataset.group = g.key;
        const enabled = actions.filter(a => r.perms[g.key][a]).length;
        groupEl.innerHTML =
          '<button type="button" class="roles-perm-group__head" data-roles-group-toggle>' +
            '<span class="roles-perm-group__icon"><i class="bi ' + g.icon + '"></i></span>' +
            '<span>' + g.label + '</span>' +
            '<span class="roles-perm-group__count">' + enabled + ' / ' + actions.length + ' enabled</span>' +
            '<i class="bi bi-chevron-down roles-perm-group__chev"></i>' +
          '</button>' +
          '<div class="roles-perm-group__body">' +
            '<div class="roles-perm-row" role="row">' +
              '<span class="roles-perm-row__label text-body-secondary small">Action</span>' +
              actions.map(a =>
                '<span class="text-body-secondary small text-center">' + a.charAt(0).toUpperCase() + a.slice(1) + '</span>'
              ).join('') +
            '</div>' +
            '<div class="roles-perm-row">' +
              '<span class="roles-perm-row__label">Permissions</span>' +
              actions.map(a => {
                const on = r.perms[g.key][a];
                return '<div class="roles-perm-row__toggle">' +
                  '<div class="form-check form-switch m-0">' +
                    '<input class="form-check-input" type="checkbox" data-roles-perm data-group="' + g.key + '" data-action="' + a + '"' + (on ? ' checked' : '') + '>' +
                  '</div>' +
                '</div>';
              }).join('') +
            '</div>' +
          '</div>';
        groupsRoot.appendChild(groupEl);
      });
      dirty = false;
    }

    function renderUsersMini(r) {
      const usersOfRole = AccessToolkit.users.filter(u => u.role === r.name).slice(0, 5);
      const rows = usersOfRole.length ? usersOfRole.map(u =>
        '<tr>' +
          '<td><div class="d-flex align-items-center gap-2">' +
            '<span class="avatar avatar-sm bg-' + u.color + '-subtle text-' + u.color + ' fw-semibold">' + u.avatar + '</span>' +
            '<div><p class="mb-0 fw-medium">' + escapeHtml(u.name) + '</p>' +
            '<small class="text-body-secondary">' + escapeHtml(u.email) + '</small></div>' +
          '</div></td>' +
          '<td class="text-end"><button class="btn btn-sm btn-icon" type="button" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button></td>' +
        '</tr>'
      ).join('') : '<tr><td colspan="2" class="text-center text-body-secondary py-3">No users assigned to this role.</td></tr>';
      return '<div class="roles-users-mini">' +
        '<div class="d-flex justify-content-between align-items-center mb-2">' +
          '<h6 class="mb-0">Users with this role</h6>' +
          '<a href="users.html" class="small">See all ' + r.users + ' users</a>' +
        '</div>' +
        '<div class="table-responsive"><table class="table table-sm align-middle">' +
          '<tbody>' + rows + '</tbody>' +
        '</table></div>' +
      '</div>';
    }

    function markDirty() {
      dirty = true;
      const btn = $('[data-roles-save]', detailEl);
      if (btn) btn.disabled = false;
    }

    // events
    listEl.addEventListener('click', (e) => {
      const item = e.target.closest('.roles-list__item');
      if (!item) return;
      activeRoleId = item.dataset.roleId;
      $$('.roles-list__item', listEl).forEach(el => el.classList.toggle('is-active', el.dataset.roleId === activeRoleId));
      renderDetail();
    });
    listEl.addEventListener('keydown', (e) => {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      const item = e.target.closest('.roles-list__item');
      if (!item) return;
      e.preventDefault();
      item.click();
    });

    detailEl.addEventListener('click', (e) => {
      const grp = e.target.closest('[data-roles-group-toggle]');
      if (grp) {
        grp.parentElement.classList.toggle('is-open');
      }
      if (e.target.closest('[data-roles-duplicate]')) {
        const r = roles.find(x => x.id === activeRoleId);
        const copy = JSON.parse(JSON.stringify(r));
        copy.id = 'r' + (roles.length + 1);
        copy.name = r.name + ' (Copy)';
        copy.system = false;
        copy.scope = 'custom';
        copy.users = 0;
        roles.push(copy);
        activeRoleId = copy.id;
        renderList(); renderDetail();
        AccessToolkit.toast('Role duplicated as "' + copy.name + '".', 'success');
      }
      if (e.target.closest('[data-roles-delete]')) {
        const r = roles.find(x => x.id === activeRoleId);
        if (!r || r.system) return;
        confirmDelete(r);
      }
      if (e.target.closest('[data-roles-reset]')) {
        renderDetail();
        AccessToolkit.toast('Changes discarded.', 'secondary');
      }
      if (e.target.closest('[data-roles-save]')) {
        const btn = e.target.closest('[data-roles-save]');
        btn.disabled = true;
        dirty = false;
        AccessToolkit.toast('Role permissions saved.', 'success');
      }
      if (e.target.closest('[data-roles-view-users]')) {
        e.preventDefault();
        window.location.href = 'users.html';
      }
    });

    detailEl.addEventListener('change', (e) => {
      if (e.target.matches('[data-roles-perm]')) {
        const r = roles.find(x => x.id === activeRoleId);
        r.perms[e.target.dataset.group][e.target.dataset.action] = e.target.checked;
        // update count
        const groupEl = e.target.closest('.roles-perm-group');
        const count = actions.filter(a => r.perms[groupEl.dataset.group][a]).length;
        $('.roles-perm-group__count', groupEl).textContent = count + ' / ' + actions.length + ' enabled';
        markDirty();
      }
    });
    detailEl.addEventListener('input', (e) => {
      if (e.target.matches('[data-roles-name]')) {
        const r = roles.find(x => x.id === activeRoleId);
        r.name = e.target.value.trim() || r.name;
        markDirty();
        const active = $('.roles-list__item.is-active .roles-list__name', listEl);
        if (active) active.childNodes[0].nodeValue = r.name + ' ';
      }
    });

    function confirmDelete(r) {
      const modal = $('#rolesDeleteModal');
      $('[data-roles-delete-name]', modal).textContent = r.name;
      $('[data-roles-delete-count]', modal).textContent = r.users;
      const warn = $('[data-roles-delete-warn]', modal);
      warn.classList.toggle('d-none', r.users === 0);
      const inst = bootstrap.Modal.getOrCreateInstance(modal);
      inst.show();
      $('[data-roles-delete-confirm]', modal).onclick = () => {
        const idx = roles.findIndex(x => x.id === r.id);
        if (idx > -1) roles.splice(idx, 1);
        activeRoleId = roles[0].id;
        renderList(); renderDetail();
        inst.hide();
        AccessToolkit.toast('Role "' + r.name + '" deleted.', 'danger');
      };
    }

    // create modal
    const createModal = $('#rolesCreateModal');
    if (createModal) {
      // populate inherit select
      const sel = $('[data-roles-inherit]', createModal);
      sel.innerHTML = '<option value="">Start from scratch</option>' + roles.map(r =>
        '<option value="' + r.id + '">' + escapeHtml(r.name) + '</option>'
      ).join('');

      $('[data-roles-create-submit]', createModal).addEventListener('click', () => {
        const name = $('[data-roles-new-name]', createModal).value.trim();
        const desc = $('[data-roles-new-desc]', createModal).value.trim() || 'Custom role';
        const inheritId = sel.value;
        if (!name) { AccessToolkit.toast('Role name is required.', 'danger'); return; }
        const source = inheritId ? roles.find(r => r.id === inheritId) : null;
        const newRole = {
          id: 'r' + (roles.length + 1),
          name, desc, users: 0, system: false, scope: 'custom', icon: 'bi-shield',
          perms: source ? JSON.parse(JSON.stringify(source.perms)) : makePerms([[0,0,0,0,0],[0,0,0,0,0],[0,0,0,0,0],[0,0,0,0,0],[0,0,0,0,0],[0,0,0,0,0]])
        };
        roles.push(newRole);
        activeRoleId = newRole.id;
        renderList(); renderDetail();
        bootstrap.Modal.getInstance(createModal).hide();
        createModal.querySelector('form').reset();
        AccessToolkit.toast('Role "' + name + '" created.', 'success');
      });
    }

    renderList();
    renderDetail();
  }

  /* ==========================================================
     PERMISSIONS PAGE
     ========================================================== */
  function initPermissions() {
    const root = $('[data-access-page="permissions"]');
    if (!root) return;

    const roles = [
      { key: 'super', name: 'Super Admin' },
      { key: 'admin', name: 'Admin' },
      { key: 'editor', name: 'Editor' },
      { key: 'viewer', name: 'Viewer' },
      { key: 'guest',  name: 'Guest' },
      { key: 'finance', name: 'Finance Manager' }
    ];

    // 25 permissions across 5 categories
    const permissions = [
      // System
      { id: 'p1',  cat: 'system',  name: 'Manage Settings',      icon: 'bi-sliders',       desc: 'Global system settings' },
      { id: 'p2',  cat: 'system',  name: 'Audit Logs',           icon: 'bi-shield-check',  desc: 'Read audit trails' },
      { id: 'p3',  cat: 'system',  name: 'Maintenance Mode',     icon: 'bi-tools',         desc: 'Toggle maintenance' },
      { id: 'p4',  cat: 'system',  name: 'Integrations',         icon: 'bi-puzzle',        desc: 'Third-party integrations' },
      // Users
      { id: 'p5',  cat: 'users',   name: 'View Users',           icon: 'bi-people',        desc: 'List and search users' },
      { id: 'p6',  cat: 'users',   name: 'Invite Users',         icon: 'bi-person-plus',   desc: 'Send invitations' },
      { id: 'p7',  cat: 'users',   name: 'Manage Roles',         icon: 'bi-shield-lock',   desc: 'Assign roles' },
      { id: 'p8',  cat: 'users',   name: 'Impersonate',          icon: 'bi-incognito',     desc: 'Impersonate any user' },
      { id: 'p9',  cat: 'users',   name: 'Delete Users',         icon: 'bi-person-x',      desc: 'Remove accounts' },
      // Content
      { id: 'p10', cat: 'content', name: 'Publish Content',      icon: 'bi-globe',         desc: 'Push content live' },
      { id: 'p11', cat: 'content', name: 'Media Library',        icon: 'bi-images',        desc: 'Manage media assets' },
      { id: 'p12', cat: 'content', name: 'Pages',                icon: 'bi-file-earmark',  desc: 'Manage pages' },
      { id: 'p13', cat: 'content', name: 'Comments',             icon: 'bi-chat-square',   desc: 'Moderate comments' },
      { id: 'p14', cat: 'content', name: 'Categories',           icon: 'bi-tags',          desc: 'Content categorization' },
      // Billing
      { id: 'p15', cat: 'billing', name: 'View Invoices',        icon: 'bi-receipt',       desc: 'Access invoice list' },
      { id: 'p16', cat: 'billing', name: 'Create Invoices',      icon: 'bi-plus-square',   desc: 'Issue new invoices' },
      { id: 'p17', cat: 'billing', name: 'Process Refunds',      icon: 'bi-arrow-return-left', desc: 'Refund transactions' },
      { id: 'p18', cat: 'billing', name: 'Subscription Plans',   icon: 'bi-card-checklist', desc: 'Manage plans' },
      // API
      { id: 'p19', cat: 'api',     name: 'API Keys',             icon: 'bi-key',           desc: 'Generate and revoke' },
      { id: 'p20', cat: 'api',     name: 'Webhooks',             icon: 'bi-broadcast',     desc: 'Manage webhooks' },
      { id: 'p21', cat: 'api',     name: 'Rate Limits',          icon: 'bi-speedometer',   desc: 'Adjust rate limits' },
      // Reporting
      { id: 'p22', cat: 'reporting', name: 'View Reports',       icon: 'bi-file-earmark-text', desc: 'Access reports' },
      { id: 'p23', cat: 'reporting', name: 'Export Reports',     icon: 'bi-download',      desc: 'Export as CSV/PDF' },
      { id: 'p24', cat: 'reporting', name: 'Custom Dashboards',  icon: 'bi-grid-3x3',      desc: 'Build custom views' },
      { id: 'p25', cat: 'reporting', name: 'Scheduled Reports',  icon: 'bi-calendar-event', desc: 'Automated delivery' }
    ];

    const categories = [
      { key: 'system',    label: 'System' },
      { key: 'users',     label: 'Users' },
      { key: 'content',   label: 'Content' },
      { key: 'billing',   label: 'Billing' },
      { key: 'api',       label: 'API' },
      { key: 'reporting', label: 'Reporting' }
    ];

    const STATES = ['none', 'read', 'write', 'full'];
    const stateIcon = {
      none:  '<span aria-hidden="true">—</span>',
      read:  '<i class="bi bi-eye"></i>',
      write: '<i class="bi bi-pencil"></i>',
      full:  '<i class="bi bi-check2-square"></i>'
    };
    const stateLabel = { none:'None', read:'Read', write:'Read + Write', full:'Full access' };

    // build matrix data
    const matrix = {};
    permissions.forEach(p => {
      matrix[p.id] = {};
      roles.forEach(r => {
        // deterministic mock state
        let s = 0;
        if (r.key === 'super') s = 3;
        else if (r.key === 'admin')   s = p.cat === 'system' ? 2 : 3;
        else if (r.key === 'editor')  s = p.cat === 'content' ? 3 : (p.cat === 'reporting' ? 1 : 0);
        else if (r.key === 'viewer')  s = ['content','reporting'].indexOf(p.cat) > -1 ? 1 : 0;
        else if (r.key === 'guest')   s = p.id === 'p12' ? 1 : 0;
        else if (r.key === 'finance') s = p.cat === 'billing' ? 3 : (p.cat === 'reporting' ? 1 : 0);
        matrix[p.id][r.key] = STATES[s];
      });
    });

    const searchEl   = $('[data-perm-search]', root);
    const catEl      = $('[data-perm-cat]', root);
    const roleFilter = $('[data-perm-role-filter]', root);
    const tbody      = $('[data-perm-body]', root);
    const thead      = $('[data-perm-head]', root);
    const bulkBar    = $('[data-perm-bulk-bar]', root);
    const bulkCount  = $('[data-perm-bulk-count]', root);
    const bulkSet    = $('[data-perm-bulk-set]', root);
    const foldedCats = new Set();
    const selectedCells = new Set(); // key: pid+':'+rkey
    let visibleRoles = roles.map(r => r.key);

    function renderHead() {
      thead.innerHTML = '';
      const tr = document.createElement('tr');
      tr.innerHTML =
        '<th class="perm-col-resource">Resource</th>' +
        '<th class="perm-col-select"><input class="form-check-input" type="checkbox" data-perm-select-all aria-label="Select all"></th>' +
        roles.filter(r => visibleRoles.includes(r.key)).map(r =>
          '<th data-role-col="' + r.key + '">' + escapeHtml(r.name) + '</th>'
        ).join('');
      thead.appendChild(tr);
    }

    function renderBody() {
      tbody.innerHTML = '';
      const q = (searchEl.value || '').toLowerCase();
      const catFilter = catEl.value;
      const visibleRoleObjs = roles.filter(r => visibleRoles.includes(r.key));

      categories.forEach(cat => {
        if (catFilter && catFilter !== cat.key) return;
        const catPerms = permissions.filter(p =>
          p.cat === cat.key &&
          (!q || p.name.toLowerCase().includes(q) || p.desc.toLowerCase().includes(q))
        );
        if (!catPerms.length) return;

        const catRow = document.createElement('tr');
        catRow.className = 'perm-category-row' + (foldedCats.has(cat.key) ? ' is-collapsed' : '');
        catRow.dataset.cat = cat.key;
        catRow.innerHTML =
          '<td colspan="' + (2 + visibleRoleObjs.length) + '">' +
            '<i class="bi bi-chevron-down perm-chev"></i>' +
            cat.label +
            ' <span class="badge bg-primary-subtle text-primary ms-2">' + catPerms.length + '</span>' +
          '</td>';
        tbody.appendChild(catRow);

        if (foldedCats.has(cat.key)) return;

        catPerms.forEach(p => {
          const tr = document.createElement('tr');
          tr.dataset.pid = p.id;
          tr.innerHTML =
            '<td class="perm-cell-resource">' +
              '<div class="perm-resource">' +
                '<span class="perm-resource__icon"><i class="bi ' + p.icon + '"></i></span>' +
                '<div>' +
                  '<p class="perm-resource__name">' + escapeHtml(p.name) +
                    '<span class="perm-resource__chip">' + cat.label + '</span>' +
                  '</p>' +
                  '<small class="text-body-secondary" data-bs-toggle="tooltip" data-bs-title="' + escapeHtml(p.desc) + '">' + escapeHtml(p.desc) + '</small>' +
                '</div>' +
              '</div>' +
            '</td>' +
            '<td class="perm-cell-select">' +
              '<input class="form-check-input" type="checkbox" data-perm-row-select aria-label="Select row">' +
            '</td>' +
            visibleRoleObjs.map(r => {
              const st = matrix[p.id][r.key];
              const key = p.id + ':' + r.key;
              return '<td>' +
                '<button type="button" class="perm-cell-btn' + (selectedCells.has(key) ? ' is-selected' : '') + '" ' +
                  'data-perm-cell data-pid="' + p.id + '" data-role="' + r.key + '" data-state="' + st + '" ' +
                  'aria-label="' + escapeHtml(r.name) + ' — ' + stateLabel[st] + '">' +
                  stateIcon[st] +
                '</button>' +
              '</td>';
            }).join('');
          tbody.appendChild(tr);
        });
      });

      if (!tbody.children.length) {
        const tr = document.createElement('tr');
        tr.innerHTML = '<td colspan="' + (2 + visibleRoleObjs.length) + '" class="text-center text-body-secondary py-4">No permissions match your filters.</td>';
        tbody.appendChild(tr);
      }
    }

    function updateBulkBar() {
      const n = selectedCells.size;
      bulkBar.classList.toggle('d-none', n === 0);
      bulkCount.textContent = n;
    }

    function cycleState(cur) {
      const idx = STATES.indexOf(cur);
      return STATES[(idx + 1) % STATES.length];
    }

    // events
    searchEl.addEventListener('input', renderBody);
    catEl.addEventListener('change', renderBody);

    // role filter dropdown checkboxes
    roleFilter.innerHTML = roles.map(r =>
      '<li><label class="dropdown-item"><input type="checkbox" class="form-check-input me-2" data-perm-role-toggle value="' + r.key + '" checked> ' + escapeHtml(r.name) + '</label></li>'
    ).join('');
    roleFilter.addEventListener('change', (e) => {
      if (!e.target.matches('[data-perm-role-toggle]')) return;
      visibleRoles = $$('[data-perm-role-toggle]', roleFilter).filter(c => c.checked).map(c => c.value);
      renderHead(); renderBody();
    });

    tbody.addEventListener('click', (e) => {
      const catCell = e.target.closest('.perm-category-row');
      if (catCell) {
        const cat = catCell.dataset.cat;
        if (foldedCats.has(cat)) foldedCats.delete(cat); else foldedCats.add(cat);
        renderBody();
        return;
      }
      const cell = e.target.closest('[data-perm-cell]');
      if (cell) {
        const pid = cell.dataset.pid;
        const role = cell.dataset.role;
        const cur = matrix[pid][role];
        const next = cycleState(cur);
        // confirmation on downgrade for admin roles
        const idxCur = STATES.indexOf(cur);
        const idxNext = STATES.indexOf(next);
        if (idxNext < idxCur && (role === 'super' || role === 'admin')) {
          if (!confirm('Downgrading a critical role — are you sure?')) return;
        }
        matrix[pid][role] = next;
        cell.dataset.state = next;
        cell.innerHTML = stateIcon[next];
        AccessToolkit.toast('Permission set to "' + stateLabel[next] + '".', 'primary');
        return;
      }
    });

    tbody.addEventListener('change', (e) => {
      if (e.target.matches('[data-perm-row-select]')) {
        const tr = e.target.closest('tr');
        const pid = tr.dataset.pid;
        const cells = $$('[data-perm-cell]', tr);
        cells.forEach(c => {
          const key = pid + ':' + c.dataset.role;
          if (e.target.checked) selectedCells.add(key);
          else selectedCells.delete(key);
          c.classList.toggle('is-selected', e.target.checked);
        });
        updateBulkBar();
      }
    });

    thead.addEventListener('change', (e) => {
      if (e.target.matches('[data-perm-select-all]')) {
        const on = e.target.checked;
        $$('[data-perm-row-select]', tbody).forEach(cb => { cb.checked = on; cb.dispatchEvent(new Event('change', { bubbles: true })); });
      }
    });

    bulkSet.addEventListener('click', (e) => {
      const state = e.target.closest('[data-bulk-state]');
      if (!state) return;
      const s = state.dataset.bulkState;
      selectedCells.forEach(key => {
        const [pid, rkey] = key.split(':');
        matrix[pid][rkey] = s;
      });
      renderBody();
      AccessToolkit.toast(selectedCells.size + ' cells set to "' + stateLabel[s] + '".', 'success');
      selectedCells.clear();
      updateBulkBar();
    });

    $('[data-perm-export]', root).addEventListener('click', () => {
      AccessToolkit.toast('Matrix export scheduled — CSV will download shortly.', 'info');
    });

    renderHead();
    renderBody();
  }

  /* ==========================================================
     ACTIVITY PAGE
     ========================================================== */
  function initActivity() {
    const root = $('[data-access-page="activity"]');
    if (!root) return;

    const types = [
      { key: 'login',    verb: 'signed in',         icon: 'bi-box-arrow-in-right' },
      { key: 'logout',   verb: 'signed out',         icon: 'bi-box-arrow-right' },
      { key: 'create',   verb: 'created',           icon: 'bi-plus-lg' },
      { key: 'edit',     verb: 'updated',           icon: 'bi-pencil' },
      { key: 'delete',   verb: 'deleted',           icon: 'bi-trash' },
      { key: 'export',   verb: 'exported',          icon: 'bi-download' },
      { key: 'import',   verb: 'imported',          icon: 'bi-upload' },
      { key: 'perm',     verb: 'changed permissions on', icon: 'bi-shield' },
      { key: 'password', verb: 'reset password for', icon: 'bi-key' }
    ];
    const resources = ['Users', 'Deals', 'Invoices', 'Settings', 'Reports', 'Roles'];

    function makeItem(offsetMinutes, typeKey, actor, resource, suspicious) {
      return {
        id: 'e' + Math.random().toString(36).slice(2, 9),
        type: typeKey,
        actor,
        resource,
        resourceId: '#' + Math.floor(1000 + Math.random() * 9000),
        ts: new Date(Date.now() - offsetMinutes * 60 * 1000),
        ip: suspicious ? '203.0.113.' + Math.floor(Math.random()*250) : '192.168.1.' + Math.floor(Math.random()*250),
        suspicious: !!suspicious
      };
    }

    const items = [
      makeItem(1,   'login',   AccessToolkit.users[0], null, false),
      makeItem(4,   'edit',    AccessToolkit.users[1], 'Deals', false),
      makeItem(9,   'create',  AccessToolkit.users[2], 'Users', false),
      makeItem(18,  'perm',    AccessToolkit.users[0], 'Roles', true),
      makeItem(25,  'export',  AccessToolkit.users[6], 'Invoices', false),
      makeItem(41,  'delete',  AccessToolkit.users[1], 'Users', false),
      makeItem(55,  'edit',    AccessToolkit.users[3], 'Reports', false),
      makeItem(70,  'logout',  AccessToolkit.users[4], null, false),
      makeItem(90,  'login',   AccessToolkit.users[8], null, true),
      makeItem(120, 'password',AccessToolkit.users[0], 'Users', false),
      makeItem(140, 'import',  AccessToolkit.users[6], 'Invoices', false),
      makeItem(180, 'create',  AccessToolkit.users[9], 'Deals', false),
      makeItem(220, 'edit',    AccessToolkit.users[2], 'Deals', false),
      makeItem(260, 'login',   AccessToolkit.users[5], null, false),
      makeItem(320, 'logout',  AccessToolkit.users[0], null, false),
      // yesterday
      makeItem(1500, 'edit',   AccessToolkit.users[1], 'Settings', false),
      makeItem(1550, 'create', AccessToolkit.users[2], 'Reports', false),
      makeItem(1600, 'delete', AccessToolkit.users[0], 'Deals', false),
      makeItem(1650, 'perm',   AccessToolkit.users[0], 'Users', false),
      makeItem(1700, 'export', AccessToolkit.users[6], 'Reports', false),
      makeItem(1800, 'login',  AccessToolkit.users[3], null, false),
      makeItem(1900, 'login',  AccessToolkit.users[4], null, true),
      makeItem(2000, 'edit',   AccessToolkit.users[8], 'Deals', false),
      // 2 days
      makeItem(2900, 'create', AccessToolkit.users[1], 'Users', false),
      makeItem(3000, 'edit',   AccessToolkit.users[2], 'Invoices', false),
      makeItem(3100, 'delete', AccessToolkit.users[3], 'Reports', false),
      makeItem(3200, 'perm',   AccessToolkit.users[0], 'Roles', false),
      makeItem(3300, 'import', AccessToolkit.users[6], 'Users', false),
      makeItem(3400, 'export', AccessToolkit.users[9], 'Deals', false),
      makeItem(3500, 'login',  AccessToolkit.users[5], null, false)
    ];

    const feed        = $('[data-act-feed]', root);
    const userSel     = $('[data-act-user]', root);
    const dateBtns    = $$('[data-act-date]', root);
    const typeBoxes   = $$('[data-act-type]', root);
    const resBoxes    = $$('[data-act-res]', root);
    const suspOnly    = $('[data-act-suspicious]', root);
    const refreshBtn  = $('[data-act-refresh]', root);
    const autoToggle  = $('[data-act-auto]', root);
    const statTotal   = $('[data-act-stat="total"]', root);
    const statUsers   = $('[data-act-stat="users"]', root);
    const statFailed  = $('[data-act-stat="failed"]', root);
    const statAdmin   = $('[data-act-stat="admin"]', root);
    let dateRange = 'today';
    let autoTimer = null;

    // populate user select
    userSel.innerHTML = '<option value="">All users</option>' + AccessToolkit.users.map(u =>
      '<option value="' + u.id + '">' + escapeHtml(u.name) + '</option>'
    ).join('');

    function passesFilter(it) {
      const now = Date.now();
      let ageDays = (now - it.ts.getTime()) / 86400000;
      if (dateRange === 'today' && ageDays > 1) return false;
      if (dateRange === '7d' && ageDays > 7) return false;
      if (dateRange === '30d' && ageDays > 30) return false;

      const uid = userSel.value;
      if (uid && it.actor.id !== uid) return false;

      const allowedTypes = typeBoxes.filter(c => c.checked).map(c => c.value);
      if (allowedTypes.length && !allowedTypes.includes(it.type)) return false;

      const allowedRes = resBoxes.filter(c => c.checked).map(c => c.value);
      if (allowedRes.length && !allowedRes.includes(it.resource || '')) return false;

      if (suspOnly.checked && !it.suspicious) return false;
      return true;
    }

    function dayLabel(d) {
      const today = new Date(); today.setHours(0,0,0,0);
      const then = new Date(d);  then.setHours(0,0,0,0);
      const diff = Math.round((today - then) / 86400000);
      if (diff === 0) return 'Today';
      if (diff === 1) return 'Yesterday';
      return d.toLocaleDateString(undefined, { weekday:'short', month:'short', day:'numeric' });
    }

    function render() {
      const filtered = items.filter(passesFilter).sort((a,b) => b.ts - a.ts);
      feed.innerHTML = '';
      if (!filtered.length) {
        feed.innerHTML =
          '<div class="access-empty">' +
            '<i class="bi bi-inbox"></i>' +
            '<p class="mb-0">No activity matches your filters.</p>' +
          '</div>';
        return;
      }

      let currentDay = '';
      filtered.forEach(it => {
        const day = dayLabel(it.ts);
        if (day !== currentDay) {
          currentDay = day;
          const header = document.createElement('div');
          header.className = 'act-day-header';
          header.textContent = day;
          feed.appendChild(header);
        }
        const type = types.find(t => t.key === it.type);
        const li = document.createElement('div');
        li.className = 'act-feed__item' + (it.suspicious ? ' is-suspicious' : '');
        li.innerHTML =
          '<div class="act-actor-avatar bg-' + it.actor.color + '-subtle text-' + it.actor.color + '">' +
            escapeHtml(it.actor.avatar) +
            '<span class="act-icon-badge type-' + type.key + '"><i class="bi ' + type.icon + '"></i></span>' +
          '</div>' +
          '<div class="act-feed__body">' +
            '<p class="act-feed__sentence">' +
              '<strong>' + escapeHtml(it.actor.name) + '</strong> ' + type.verb + ' ' +
              (it.resource ? '<a href="#">' + escapeHtml(it.resource) + ' ' + it.resourceId + '</a>' : 'their session') +
            '</p>' +
            '<div class="act-feed__meta">' +
              '<span><i class="bi bi-clock me-1"></i>' + AccessToolkit.formatRelative(it.ts) + '</span>' +
              '<span><i class="bi bi-hdd-network me-1"></i>' + it.ip + '</span>' +
              (it.suspicious ? '<span class="act-suspicious-badge"><i class="bi bi-exclamation-triangle me-1"></i>Suspicious</span>' : '') +
            '</div>' +
          '</div>';
        feed.appendChild(li);
      });
      updateStats();
    }

    function updateStats() {
      const today = items.filter(it => (Date.now() - it.ts.getTime()) < 86400000);
      statTotal.textContent = today.length;
      statUsers.textContent = new Set(today.map(t => t.actor.id)).size;
      statFailed.textContent = today.filter(t => t.suspicious).length;
      statAdmin.textContent  = today.filter(t => ['Super Admin','Admin'].includes(t.actor.role)).length;
    }

    dateBtns.forEach(b => b.addEventListener('click', () => {
      dateRange = b.dataset.actDate;
      dateBtns.forEach(x => x.classList.toggle('active', x === b));
      render();
    }));
    userSel.addEventListener('change', render);
    typeBoxes.forEach(c => c.addEventListener('change', render));
    resBoxes.forEach(c => c.addEventListener('change', render));
    suspOnly.addEventListener('change', render);

    refreshBtn.addEventListener('click', () => {
      injectDemo(true);
      render();
      AccessToolkit.toast('Feed refreshed.', 'info');
    });

    autoToggle.addEventListener('change', () => {
      if (autoToggle.checked) {
        autoTimer = setInterval(() => {
          injectDemo(false);
          render();
          const first = feed.querySelector('.act-feed__item');
          if (first) first.classList.add('is-new');
        }, 8000);
        AccessToolkit.toast('Live feed enabled.', 'success');
      } else {
        clearInterval(autoTimer);
        autoTimer = null;
      }
    });

    function injectDemo(force) {
      const t = types[Math.floor(Math.random() * types.length)];
      const u = AccessToolkit.users[Math.floor(Math.random() * AccessToolkit.users.length)];
      const r = ['login','logout'].includes(t.key) ? null : resources[Math.floor(Math.random()*resources.length)];
      const susp = Math.random() < 0.15;
      const it = {
        id: 'e' + Math.random().toString(36).slice(2, 9),
        type: t.key, actor: u, resource: r, resourceId: '#' + Math.floor(1000 + Math.random() * 9000),
        ts: new Date(), ip: susp ? '203.0.113.' + Math.floor(Math.random()*250) : '192.168.1.' + Math.floor(Math.random()*250),
        suspicious: susp
      };
      items.unshift(it);
    }

    render();
  }

  /* ==========================================================
     LOGIN HISTORY PAGE
     ========================================================== */
  function initHistory() {
    const root = $('[data-access-page="history"]');
    if (!root) return;

    const sessions = [
      { id:'s1',  user: AccessToolkit.users[0], ts: new Date(Date.now() - 1000*60*3),   ip:'192.168.1.24',   country:'US', countryName:'United States', city:'San Francisco', device:'Desktop', browser:'Chrome 121', status:'success', mfa:true,  duration: 3480, x:24, y:44, mfaMethod:'TOTP (Authenticator)' },
      { id:'s2',  user: AccessToolkit.users[1], ts: new Date(Date.now() - 1000*60*18),  ip:'82.42.19.104',   country:'GB', countryName:'United Kingdom', city:'London', device:'Mobile',  browser:'Safari 17',  status:'success', mfa:true,  duration: 1240, x:47, y:32, mfaMethod:'SMS' },
      { id:'s3',  user: AccessToolkit.users[2], ts: new Date(Date.now() - 1000*60*35),  ip:'119.42.7.221',   country:'IN', countryName:'India', city:'Mumbai', device:'Desktop', browser:'Firefox 124',status:'success', mfa:false, duration: 8420, x:68, y:52, mfaMethod:'—' },
      { id:'s4',  user: AccessToolkit.users[3], ts: new Date(Date.now() - 1000*60*54),  ip:'85.214.132.9',   country:'DE', countryName:'Germany', city:'Berlin', device:'Desktop', browser:'Edge 121',   status:'failed',  mfa:false, duration: 0,     x:52, y:36, mfaMethod:'—' },
      { id:'s5',  user: AccessToolkit.users[4], ts: new Date(Date.now() - 1000*60*72),  ip:'126.14.55.9',    country:'JP', countryName:'Japan', city:'Tokyo', device:'Mobile',  browser:'Chrome 121', status:'success', mfa:true,  duration: 620,   x:82, y:48, mfaMethod:'Push' },
      { id:'s6',  user: AccessToolkit.users[5], ts: new Date(Date.now() - 1000*60*95),  ip:'177.32.9.14',    country:'BR', countryName:'Brazil', city:'São Paulo', device:'Tablet',  browser:'Safari 17',  status:'mfa',     mfa:false, duration: 40,    x:34, y:66, mfaMethod:'Pending' },
      { id:'s7',  user: AccessToolkit.users[6], ts: new Date(Date.now() - 1000*60*140), ip:'203.0.113.42',   country:'NG', countryName:'Nigeria', city:'Lagos', device:'Desktop', browser:'Chrome 121', status:'blocked', mfa:false, duration: 0,     x:50, y:60, mfaMethod:'—' },
      { id:'s8',  user: AccessToolkit.users[7], ts: new Date(Date.now() - 1000*60*180), ip:'115.70.19.44',   country:'AU', countryName:'Australia', city:'Sydney', device:'Mobile',  browser:'Firefox 124',status:'success', mfa:true,  duration: 2160, x:88, y:74, mfaMethod:'TOTP (Authenticator)' },
      { id:'s9',  user: AccessToolkit.users[8], ts: new Date(Date.now() - 1000*60*230), ip:'82.42.24.19',    country:'GB', countryName:'United Kingdom', city:'Manchester', device:'Desktop', browser:'Chrome 121', status:'success', mfa:true,  duration: 5210, x:48, y:32, mfaMethod:'Hardware key' },
      { id:'s10', user: AccessToolkit.users[9], ts: new Date(Date.now() - 1000*60*300), ip:'192.168.1.87',   country:'US', countryName:'United States', city:'New York', device:'Desktop', browser:'Edge 121',   status:'success', mfa:false, duration: 4180, x:27, y:42, mfaMethod:'—' },
      { id:'s11', user: AccessToolkit.users[0], ts: new Date(Date.now() - 1000*60*400), ip:'203.0.113.19',   country:'RU', countryName:'Russia', city:'Moscow', device:'Desktop', browser:'Chrome 121', status:'failed',  mfa:false, duration: 0,     x:60, y:30, mfaMethod:'—' },
      { id:'s12', user: AccessToolkit.users[1], ts: new Date(Date.now() - 1000*60*500), ip:'119.42.9.14',    country:'IN', countryName:'India', city:'Delhi', device:'Mobile',  browser:'Safari 17',  status:'success', mfa:true,  duration: 780,   x:68, y:48, mfaMethod:'SMS' },
      { id:'s13', user: AccessToolkit.users[2], ts: new Date(Date.now() - 1000*60*620), ip:'85.214.9.14',    country:'DE', countryName:'Germany', city:'Munich', device:'Desktop', browser:'Firefox 124',status:'success', mfa:true,  duration: 3620, x:52, y:38, mfaMethod:'TOTP (Authenticator)' },
      { id:'s14', user: AccessToolkit.users[3], ts: new Date(Date.now() - 1000*60*720), ip:'126.14.9.42',    country:'JP', countryName:'Japan', city:'Osaka', device:'Tablet',  browser:'Safari 17',  status:'success', mfa:false, duration: 1240, x:82, y:50, mfaMethod:'—' },
      { id:'s15', user: AccessToolkit.users[4], ts: new Date(Date.now() - 1000*60*830), ip:'177.32.42.4',    country:'BR', countryName:'Brazil', city:'Rio de Janeiro', device:'Mobile',  browser:'Chrome 121', status:'mfa',     mfa:false, duration: 24,    x:35, y:68, mfaMethod:'Pending' },
      { id:'s16', user: AccessToolkit.users[5], ts: new Date(Date.now() - 1000*60*940), ip:'203.0.113.14',   country:'NG', countryName:'Nigeria', city:'Abuja', device:'Desktop', browser:'Edge 121',   status:'blocked', mfa:false, duration: 0,     x:51, y:59, mfaMethod:'—' },
      { id:'s17', user: AccessToolkit.users[6], ts: new Date(Date.now() - 1000*60*1080),ip:'115.70.14.19',   country:'AU', countryName:'Australia', city:'Melbourne', device:'Desktop', browser:'Firefox 124',status:'success', mfa:true,  duration: 6120, x:87, y:76, mfaMethod:'Hardware key' },
      { id:'s18', user: AccessToolkit.users[7], ts: new Date(Date.now() - 1000*60*1200),ip:'192.168.1.194',  country:'US', countryName:'United States', city:'Austin', device:'Mobile',  browser:'Safari 17',  status:'success', mfa:true,  duration: 1420, x:26, y:47, mfaMethod:'Push' },
      { id:'s19', user: AccessToolkit.users[8], ts: new Date(Date.now() - 1000*60*1340),ip:'82.42.44.19',    country:'GB', countryName:'United Kingdom', city:'Bristol', device:'Desktop', browser:'Chrome 121', status:'success', mfa:false, duration: 2960, x:47, y:33, mfaMethod:'—' },
      { id:'s20', user: AccessToolkit.users[9], ts: new Date(Date.now() - 1000*60*1500),ip:'119.42.14.9',    country:'IN', countryName:'India', city:'Bangalore', device:'Desktop', browser:'Edge 121',   status:'success', mfa:true,  duration: 4210, x:69, y:53, mfaMethod:'TOTP (Authenticator)' }
    ];

    const tbody     = $('[data-hist-body]', root);
    const searchEl  = $('[data-hist-search]', root);
    const statusEl  = $('[data-hist-status]', root);
    const deviceEl  = $('[data-hist-device]', root);
    const countryEl = $('[data-hist-country]', root);
    const drawer    = $('#histDrawer');

    // populate country filter
    const countries = [...new Set(sessions.map(s => s.country))].sort();
    countryEl.innerHTML = '<option value="">All countries</option>' +
      countries.map(c => '<option value="' + c + '">' + AccessToolkit.renderCountryFlag(c) + ' ' + c + '</option>').join('');

    function statusMeta(status) {
      const map = {
        success: { cls: 'hist-status--success', icon: 'bi-check-circle', label: 'Success' },
        failed:  { cls: 'hist-status--failed',  icon: 'bi-x-circle',     label: 'Failed'  },
        blocked: { cls: 'hist-status--blocked', icon: 'bi-slash-circle', label: 'Blocked' },
        mfa:     { cls: 'hist-status--mfa',     icon: 'bi-shield-lock',  label: 'MFA Required' }
      };
      return map[status];
    }
    function deviceIcon(d) {
      return d === 'Mobile' ? 'bi-phone' : d === 'Tablet' ? 'bi-tablet' : 'bi-laptop';
    }

    function passes(s) {
      const q = (searchEl.value || '').toLowerCase();
      if (q && !(s.user.name.toLowerCase().includes(q) || s.user.email.toLowerCase().includes(q) || s.ip.includes(q))) return false;
      if (statusEl.value && s.status !== statusEl.value) return false;
      if (deviceEl.value && s.device !== deviceEl.value) return false;
      if (countryEl.value && s.country !== countryEl.value) return false;
      return true;
    }

    function render() {
      const list = sessions.filter(passes);
      tbody.innerHTML = '';
      if (!list.length) {
        tbody.innerHTML = '<tr><td colspan="10"><div class="access-empty"><i class="bi bi-inbox"></i><p class="mb-0">No sessions match your filters.</p></div></td></tr>';
        updateKpis(list);
        return;
      }
      list.forEach(s => {
        const st = statusMeta(s.status);
        const tr = document.createElement('tr');
        tr.dataset.sid = s.id;
        tr.innerHTML =
          '<td><div class="d-flex align-items-center gap-2">' +
            '<span class="avatar avatar-sm bg-' + s.user.color + '-subtle text-' + s.user.color + ' fw-semibold">' + s.user.avatar + '</span>' +
            '<div><p class="mb-0 fw-medium">' + escapeHtml(s.user.name) + '</p>' +
            '<small class="text-body-secondary">' + escapeHtml(s.user.email) + '</small></div>' +
          '</div></td>' +
          '<td><span title="' + s.ts.toLocaleString() + '">' + AccessToolkit.formatRelative(s.ts) + '</span></td>' +
          '<td class="font-monospace small">' + s.ip + '</td>' +
          '<td><span class="hist-flag">' + AccessToolkit.renderCountryFlag(s.country) + '</span>' + s.city + '</td>' +
          '<td><i class="bi ' + deviceIcon(s.device) + ' me-1"></i>' + s.device + '</td>' +
          '<td class="small text-body-secondary">' + s.browser + '</td>' +
          '<td><span class="hist-status ' + st.cls + '"><i class="bi ' + st.icon + '"></i>' + st.label + '</span></td>' +
          '<td class="text-center">' + (s.mfa ? '<i class="bi bi-shield-check text-success" aria-label="Yes"></i>' : '<i class="bi bi-shield-slash text-body-secondary" aria-label="No"></i>') + '</td>' +
          '<td class="small">' + AccessToolkit.formatDuration(s.duration) + '</td>' +
          '<td class="text-end">' +
            '<div class="dropdown">' +
              '<button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button>' +
              '<ul class="dropdown-menu dropdown-menu-end">' +
                '<li><a class="dropdown-item" href="#" data-hist-action="view" data-sid="' + s.id + '"><i class="bi bi-eye me-2"></i>View detail</a></li>' +
                '<li><a class="dropdown-item text-warning" href="#" data-hist-action="revoke" data-sid="' + s.id + '"><i class="bi bi-power me-2"></i>Revoke session</a></li>' +
                '<li><a class="dropdown-item text-danger" href="#" data-hist-action="block" data-sid="' + s.id + '"><i class="bi bi-slash-circle me-2"></i>Block IP</a></li>' +
              '</ul>' +
            '</div>' +
          '</td>';
        tbody.appendChild(tr);
      });
      updateKpis(list);
    }

    function updateKpis(list) {
      const today = list.filter(s => (Date.now() - s.ts.getTime()) < 86400000);
      $('[data-hist-kpi="sessions"]', root).textContent = today.length;
      $('[data-hist-kpi="failed"]', root).textContent   = today.filter(s => s.status === 'failed').length;
      $('[data-hist-kpi="ips"]', root).textContent      = new Set(today.map(s => s.ip)).size;
    }

    function openDrawer(sid) {
      const s = sessions.find(x => x.id === sid);
      if (!s) return;
      const st = statusMeta(s.status);
      $('[data-hist-drawer-body]', drawer).innerHTML =
        '<div class="hist-drawer__section">' +
          '<div class="d-flex align-items-center gap-3">' +
            '<span class="avatar bg-' + s.user.color + '-subtle text-' + s.user.color + ' fw-semibold">' + s.user.avatar + '</span>' +
            '<div>' +
              '<p class="mb-0 fw-semibold">' + escapeHtml(s.user.name) + '</p>' +
              '<small class="text-body-secondary">' + escapeHtml(s.user.email) + '</small>' +
            '</div>' +
            '<span class="hist-status ' + st.cls + ' ms-auto"><i class="bi ' + st.icon + '"></i>' + st.label + '</span>' +
          '</div>' +
        '</div>' +
        '<div class="hist-drawer__section">' +
          '<h6>Location</h6>' +
          '<div class="hist-map">' +
            '<iframe class="hist-map__frame" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade" ' +
              'title="Map showing ' + escapeHtml(s.city) + ', ' + escapeHtml(s.countryName) + '" ' +
              'src="https://maps.google.com/maps?q=' + encodeURIComponent(s.city + ', ' + s.countryName) + '&t=&z=11&ie=UTF8&iwloc=&output=embed"></iframe>' +
          '</div>' +
          '<dl class="hist-detail-grid mt-3">' +
            '<dt>IP address</dt><dd class="font-monospace">' + s.ip + '</dd>' +
            '<dt>Country</dt><dd>' + AccessToolkit.renderCountryFlag(s.country) + ' ' + s.countryName + '</dd>' +
            '<dt>City</dt><dd>' + s.city + '</dd>' +
          '</dl>' +
        '</div>' +
        '<div class="hist-drawer__section">' +
          '<h6>Device fingerprint</h6>' +
          '<dl class="hist-detail-grid">' +
            '<dt>Device</dt><dd><i class="bi ' + deviceIcon(s.device) + ' me-1"></i>' + s.device + '</dd>' +
            '<dt>Browser</dt><dd>' + s.browser + '</dd>' +
            '<dt>OS</dt><dd>' + (s.device === 'Mobile' ? 'iOS 17' : s.device === 'Tablet' ? 'iPadOS 17' : 'macOS 14') + '</dd>' +
            '<dt>Fingerprint</dt><dd class="font-monospace small">a3f9…b17d</dd>' +
          '</dl>' +
        '</div>' +
        '<div class="hist-drawer__section">' +
          '<h6>Authentication</h6>' +
          '<dl class="hist-detail-grid">' +
            '<dt>MFA used</dt><dd>' + (s.mfa ? '<i class="bi bi-shield-check text-success me-1"></i>Yes' : '<i class="bi bi-shield-slash text-body-secondary me-1"></i>No') + '</dd>' +
            '<dt>MFA method</dt><dd>' + s.mfaMethod + '</dd>' +
            '<dt>Started</dt><dd>' + s.ts.toLocaleString() + '</dd>' +
            '<dt>Duration</dt><dd>' + AccessToolkit.formatDuration(s.duration) + '</dd>' +
          '</dl>' +
        '</div>' +
        '<div class="hist-drawer__section">' +
          '<h6>Session actions</h6>' +
          '<ul class="list-unstyled small mb-0">' +
            '<li class="d-flex justify-content-between py-1"><span><i class="bi bi-eye me-1"></i>Viewed dashboard</span><span class="text-body-secondary">2m</span></li>' +
            '<li class="d-flex justify-content-between py-1"><span><i class="bi bi-pencil me-1"></i>Updated invoice #4820</span><span class="text-body-secondary">4m</span></li>' +
            '<li class="d-flex justify-content-between py-1"><span><i class="bi bi-download me-1"></i>Exported report</span><span class="text-body-secondary">6m</span></li>' +
            '<li class="d-flex justify-content-between py-1"><span><i class="bi bi-shield me-1"></i>Changed permissions</span><span class="text-body-secondary">8m</span></li>' +
          '</ul>' +
        '</div>' +
        '<div class="hist-drawer__section d-flex flex-wrap gap-2">' +
          '<button class="btn btn-outline-warning btn-sm" type="button" data-hist-drawer-action="revoke"><i class="bi bi-power me-1"></i>Revoke session</button>' +
          '<button class="btn btn-outline-danger btn-sm" type="button" data-hist-drawer-action="force"><i class="bi bi-box-arrow-right me-1"></i>Force logout</button>' +
          '<button class="btn btn-danger btn-sm" type="button" data-hist-drawer-action="block"><i class="bi bi-slash-circle me-1"></i>Block IP</button>' +
        '</div>';

      $('[data-hist-drawer-title]', drawer).textContent = 'Session — ' + s.user.name;

      $$('[data-hist-drawer-action]', drawer).forEach(btn => {
        btn.onclick = () => {
          const action = btn.dataset.histDrawerAction;
          if (!confirm('Confirm: ' + action + '?')) return;
          if (action === 'revoke') AccessToolkit.toast('Session revoked.', 'warning');
          if (action === 'force')  AccessToolkit.toast('User forcibly logged out.', 'danger');
          if (action === 'block')  AccessToolkit.toast('IP ' + s.ip + ' blocked.', 'danger');
          bootstrap.Offcanvas.getInstance(drawer).hide();
        };
      });

      bootstrap.Offcanvas.getOrCreateInstance(drawer).show();
    }

    tbody.addEventListener('click', (e) => {
      const action = e.target.closest('[data-hist-action]');
      if (action) {
        e.preventDefault();
        e.stopPropagation();
        const sid = action.dataset.sid;
        const a = action.dataset.histAction;
        if (a === 'view') openDrawer(sid);
        if (a === 'revoke') {
          if (confirm('Revoke this session?')) AccessToolkit.toast('Session revoked.', 'warning');
        }
        if (a === 'block') {
          if (confirm('Block this IP address?')) AccessToolkit.toast('IP blocked.', 'danger');
        }
        return;
      }
      const tr = e.target.closest('tr[data-sid]');
      if (tr) openDrawer(tr.dataset.sid);
    });

    [searchEl, statusEl, deviceEl, countryEl].forEach(el => el.addEventListener('input', render));
    [statusEl, deviceEl, countryEl].forEach(el => el.addEventListener('change', render));

    $('[data-hist-dismiss-alert]', root).addEventListener('click', (e) => {
      e.target.closest('.hist-alert-banner').remove();
    });

    render();
  }

  function worldMapSvg() {
    // simple stylized world map background
    return '<svg viewBox="0 0 100 50" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">' +
      '<rect width="100" height="50" fill="var(--orchid-hover)"/>' +
      '<path fill="rgba(79,70,229,.20)" d="M12 22 Q15 18 20 20 T30 22 L32 30 L28 34 L20 32 L14 30 Z"/>' +
      '<path fill="rgba(79,70,229,.20)" d="M35 18 Q40 15 46 18 L48 24 L44 28 L38 26 Z"/>' +
      '<path fill="rgba(79,70,229,.20)" d="M46 22 Q52 20 58 22 L60 30 L56 34 L50 32 L48 28 Z"/>' +
      '<path fill="rgba(79,70,229,.20)" d="M62 18 Q70 16 78 20 L82 24 L88 22 L86 32 L78 36 L70 34 L64 28 Z"/>' +
      '<path fill="rgba(79,70,229,.20)" d="M82 38 Q86 36 90 38 L92 44 L86 46 L82 44 Z"/>' +
      '<path fill="rgba(79,70,229,.20)" d="M20 34 Q26 34 32 36 L34 44 L28 46 L22 44 Z"/>' +
      '</svg>';
  }

  /* ---------- init ---------- */
  document.addEventListener('DOMContentLoaded', () => {
    initRoles();
    initPermissions();
    initActivity();
    initHistory();
  });
})();
