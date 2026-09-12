/* Orchid — Tasks page */
(function () {
  'use strict';
  if (!window.PM) { console.warn('PM toolkit missing'); return; }

  const $ = (sel, root) => (root || document).querySelector(sel);
  const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

  const TODAY = new Date(2026, 6, 23); // 2026-07-23 (context)
  const addDays = PM.addDays;

  /* -------- Seed ~25 tasks -------- */
  const PROJECTS = PM.PROJECTS;
  const U = PM.USERS;

  const TAGS_POOL = ['ux', 'design-system', 'api', 'perf', 'a11y', 'infra', 'security', 'onboarding', 'refactor', 'copy', 'billing', 'auth', 'mobile'];
  function pickTags() {
    const shuffled = TAGS_POOL.slice().sort(() => 0.5 - Math.random());
    return shuffled.slice(0, 1 + Math.floor(Math.random() * 2));
  }

  const TASKS = [
    { title: 'Design onboarding checklist flow',       project: 'p4', assignees: ['u2','u6'], priority: 'P1', status: 'in-progress', due: addDays(TODAY, 2),  progress: 40, tags: ['ux','onboarding'] },
    { title: 'Fix crash on push notification tap',      project: 'p1', assignees: ['u9','u5'], priority: 'P0', status: 'blocked',     due: addDays(TODAY, -1), progress: 60, tags: ['mobile'] },
    { title: 'Migrate legacy /charge endpoint',         project: 'p3', assignees: ['u3'],       priority: 'P0', status: 'in-progress', due: addDays(TODAY, 4),  progress: 55, tags: ['api','billing'] },
    { title: 'Hero copy for marketing site',            project: 'p2', assignees: ['u10','u2'], priority: 'P2', status: 'done',        due: addDays(TODAY, -3), progress: 100, tags: ['copy'] },
    { title: 'Add empty state for offline mode',        project: 'p1', assignees: ['u4'],       priority: 'P2', status: 'not-started', due: addDays(TODAY, 12), progress: 0,   tags: ['ux','mobile'] },
    { title: 'Segment webhook events by tenant',        project: 'p3', assignees: ['u7','u5'],  priority: 'P1', status: 'in-progress', due: addDays(TODAY, 6),  progress: 30, tags: ['api'] },
    { title: 'Q3 blog editorial calendar',              project: 'p2', assignees: ['u10','u13'], priority: 'P2', status: 'in-progress', due: addDays(TODAY, 5),  progress: 65, tags: ['copy'] },
    { title: 'Refactor auth guard middleware',          project: 'p3', assignees: ['u3','u11'], priority: 'P1', status: 'not-started', due: addDays(TODAY, 8),  progress: 0,   tags: ['auth','refactor'] },
    { title: 'Titan Media contract kickoff plan',       project: 'p5', assignees: ['u1','u14'], priority: 'P0', status: 'in-progress', due: addDays(TODAY, 1),  progress: 25, tags: [] },
    { title: 'Accessibility audit — checkout',          project: 'p2', assignees: ['u2','u6'],  priority: 'P1', status: 'in-progress', due: addDays(TODAY, 3),  progress: 45, tags: ['a11y'] },
    { title: 'Password reset flow polish',              project: 'p4', assignees: ['u4','u2'],  priority: 'P2', status: 'done',        due: addDays(TODAY, -5), progress: 100, tags: ['auth'] },
    { title: 'Analytics dashboard tab redesign',        project: 'p2', assignees: ['u2'],       priority: 'P2', status: 'in-progress', due: addDays(TODAY, 9),  progress: 20, tags: ['design-system'] },
    { title: 'Rate-limit throttling for /reports',      project: 'p3', assignees: ['u5','u7'],  priority: 'P1', status: 'blocked',     due: addDays(TODAY, 0),  progress: 70, tags: ['perf','api'] },
    { title: 'Mobile app dark mode theme tokens',       project: 'p1', assignees: ['u9','u2'],  priority: 'P2', status: 'not-started', due: addDays(TODAY, 14), progress: 0,   tags: ['mobile','design-system'] },
    { title: 'Update legal footer copy',                project: 'p2', assignees: ['u10'],      priority: 'P2', status: 'done',        due: addDays(TODAY, -7), progress: 100, tags: ['copy'] },
    { title: 'Titan Media asset kit',                   project: 'p5', assignees: ['u2','u10'], priority: 'P1', status: 'in-progress', due: addDays(TODAY, 7),  progress: 40, tags: ['ux'] },
    { title: 'Investigate memory leak on Android',      project: 'p1', assignees: ['u9','u12'], priority: 'P0', status: 'in-progress', due: addDays(TODAY, 2),  progress: 55, tags: ['mobile','perf'] },
    { title: 'Retry queue for failed webhooks',         project: 'p3', assignees: ['u5'],       priority: 'P1', status: 'not-started', due: addDays(TODAY, 11), progress: 0,   tags: ['infra','api'] },
    { title: 'Empty inbox illustration',                project: 'p4', assignees: ['u2'],       priority: 'P2', status: 'in-progress', due: addDays(TODAY, 6),  progress: 60, tags: ['ux'] },
    { title: 'Add SSO for enterprise plan',             project: 'p3', assignees: ['u3','u7'],  priority: 'P0', status: 'blocked',     due: addDays(TODAY, 10), progress: 20, tags: ['auth','security'] },
    { title: 'Weekly newsletter template',              project: 'p2', assignees: ['u13','u10'], priority: 'P2', status: 'not-started', due: addDays(TODAY, 4),  progress: 0,   tags: ['copy'] },
    { title: 'Feature flag rollout for signup v2',      project: 'p4', assignees: ['u1','u3'],  priority: 'P1', status: 'in-progress', due: addDays(TODAY, 3),  progress: 50, tags: ['onboarding'] },
    { title: 'Client onboarding call — Titan Media',    project: 'p5', assignees: ['u1'],       priority: 'P2', status: 'done',        due: addDays(TODAY, -2), progress: 100, tags: [] },
    { title: 'Compress home page images (WebP)',        project: 'p2', assignees: ['u4','u7'],  priority: 'P2', status: 'in-progress', due: addDays(TODAY, 5),  progress: 80, tags: ['perf'] },
    { title: 'Draft Q3 all-hands agenda',               project: 'p4', assignees: ['u1','u16'], priority: 'P2', status: 'not-started', due: addDays(TODAY, 15), progress: 0,   tags: [] }
  ].map((t, i) => Object.assign({ id: 't' + (i + 1) }, t));

  // Attach fake comments/subtasks/related lazily on open
  function detailPacket(t) {
    return {
      description: 'Break this task into concrete outcomes and owners. Sync with dependencies before starting on Monday.',
      subtasks: [
        { id: 's1', text: 'Draft solution outline', done: t.progress >= 25 },
        { id: 's2', text: 'Review with lead',      done: t.progress >= 50 },
        { id: 's3', text: 'Implement + tests',     done: t.progress >= 80 },
        { id: 's4', text: 'Ship and monitor',      done: t.progress >= 100 }
      ],
      attachments: [
        { name: 'brief.pdf',   icon: 'bi-file-earmark-pdf', size: '1.2 MB' },
        { name: 'mockups.fig', icon: 'bi-file-earmark-image', size: '840 KB' }
      ],
      related: TASKS.filter(o => o.project === t.project && o.id !== t.id).slice(0, 3),
      comments: [
        { user: PM.userById(t.assignees[0]), text: 'Kicking this off today, will post updates in #product.', when: PM.addDays(TODAY, -1) },
        { user: PM.userById('u1'),          text: 'Great — flag anything blocked.', when: PM.addDays(TODAY, -1) }
      ]
    };
  }

  const state = {
    view: 'list',
    filters: { q: '', priority: '', project: '', assignee: '', status: '', due: '' },
    group: ''
  };

  /* ----- Populate filter dropdowns ----- */
  function populateFilters() {
    const projSel = $('[data-tasks-filter="project"]');
    PROJECTS.forEach(p => projSel.insertAdjacentHTML('beforeend', `<option value="${p.id}">${PM.escape(p.name)}</option>`));
    const asgSel = $('[data-tasks-filter="assignee"]');
    U.forEach(u => asgSel.insertAdjacentHTML('beforeend', `<option value="${u.id}">${PM.escape(u.name)}</option>`));
    const newProj = $('[data-tasks-new-project]');
    PROJECTS.forEach(p => newProj.insertAdjacentHTML('beforeend', `<option value="${p.id}">${PM.escape(p.name)}</option>`));
    const newAsg = $('[data-tasks-new-assignee]');
    U.forEach(u => newAsg.insertAdjacentHTML('beforeend', `<option value="${u.id}">${PM.escape(u.name)}</option>`));
  }

  /* ----- Filtering ----- */
  function applyFilters(list) {
    const f = state.filters;
    const now = TODAY;
    return list.filter(t => {
      if (f.q && !t.title.toLowerCase().includes(f.q.toLowerCase())) return false;
      if (f.priority && t.priority !== f.priority) return false;
      if (f.project && t.project !== f.project) return false;
      if (f.assignee && t.assignees.indexOf(f.assignee) === -1) return false;
      if (f.status && t.status !== f.status) return false;
      if (f.due) {
        const diff = Math.round((PM.startOfDay(t.due) - PM.startOfDay(now)) / 86400000);
        if (f.due === 'overdue' && !(diff < 0 && t.status !== 'done')) return false;
        if (f.due === 'today' && diff !== 0) return false;
        if (f.due === 'week' && (diff < 0 || diff > 7)) return false;
        if (f.due === 'month' && (diff < 0 || diff > 31)) return false;
      }
      return true;
    });
  }

  /* ----- Renderers ----- */
  function renderPriority(p) {
    const cls = p.toLowerCase();
    const icon = p === 'P0' ? 'bi-fire' : p === 'P1' ? 'bi-arrow-up-circle' : 'bi-dash-circle';
    return `<span class="tasks-priority ${cls}"><i class="bi ${icon}"></i>${p}</span>`;
  }
  function renderStatus(s) {
    const labels = { 'not-started': 'Not Started', 'in-progress': 'In Progress', blocked: 'Blocked', done: 'Done' };
    const icon = { 'not-started': 'bi-circle', 'in-progress': 'bi-play-circle', blocked: 'bi-exclamation-octagon', done: 'bi-check-circle' }[s];
    return `<span class="tasks-status ${s}"><i class="bi ${icon}"></i>${labels[s]}</span>`;
  }
  function renderDue(d) {
    const diff = Math.round((PM.startOfDay(d) - PM.startOfDay(TODAY)) / 86400000);
    let cls = '';
    if (diff < 0) cls = 'overdue';
    else if (diff <= 2) cls = 'soon';
    return `<span class="tasks-due ${cls}">${PM.fmtDateShort(d)}</span>`;
  }
  function renderProjectChip(pid) {
    const proj = PROJECTS.find(p => p.id === pid);
    const c = PM.PROJECT_COLORS[proj.name] || { bg: 'var(--orchid-hover)', color: 'var(--orchid-text)' };
    return `<span class="tasks-project-chip" style="background:${c.bg};color:${c.color}">${PM.escape(proj.name)}</span>`;
  }
  function renderAssignees(ids) {
    const users = ids.map(id => PM.userById(id)).filter(Boolean);
    return PM.renderAvatarStack(users, 3);
  }
  function renderProgress(pct) {
    return `<div class="tasks-progress" role="progressbar" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100" aria-label="Progress"><span data-w="${pct}"></span></div>`;
  }
  function renderRowActions(id) {
    return `
      <div class="dropdown">
        <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-label="Task actions" data-tasks-actions-btn>
          <i class="bi bi-three-dots-vertical"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="#" data-tasks-action="edit" data-id="${id}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
          <li><a class="dropdown-item" href="#" data-tasks-action="reassign" data-id="${id}"><i class="bi bi-person me-2"></i>Reassign</a></li>
          <li><a class="dropdown-item" href="#" data-tasks-action="priority" data-id="${id}"><i class="bi bi-flag me-2"></i>Change priority</a></li>
          <li><a class="dropdown-item" href="#" data-tasks-action="duplicate" data-id="${id}"><i class="bi bi-copy me-2"></i>Duplicate</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="#" data-tasks-action="archive" data-id="${id}"><i class="bi bi-archive me-2"></i>Archive</a></li>
          <li><a class="dropdown-item text-danger" href="#" data-tasks-action="delete" data-id="${id}"><i class="bi bi-trash me-2"></i>Delete</a></li>
        </ul>
      </div>`;
  }

  function renderRow(t) {
    return `
      <tr data-tasks-row data-id="${t.id}">
        <td><input class="form-check-input" type="checkbox" aria-label="Select task" data-tasks-check></td>
        <td class="tasks-title-cell">
          <div class="title">${PM.escape(t.title)}</div>
          ${t.tags.length ? '<div class="tags">' + t.tags.map(tg => `<span class="tag">#${tg}</span>`).join('') + '</div>' : ''}
        </td>
        <td>${renderProjectChip(t.project)}</td>
        <td>${renderAssignees(t.assignees)}</td>
        <td>${renderPriority(t.priority)}</td>
        <td>${renderStatus(t.status)}</td>
        <td>${renderDue(t.due)}</td>
        <td class="text-center">${renderProgress(t.progress)}</td>
        <td class="text-end">${renderRowActions(t.id)}</td>
      </tr>`;
  }

  function groupTasks(list) {
    if (!state.group) return [{ label: null, tasks: list }];
    const map = new Map();
    list.forEach(t => {
      let k, l;
      if (state.group === 'project') { const p = PROJECTS.find(x => x.id === t.project); k = t.project; l = p ? p.name : '—'; }
      else if (state.group === 'assignee') { const u = PM.userById(t.assignees[0]); k = t.assignees[0] || '_none'; l = u ? u.name : 'Unassigned'; }
      else if (state.group === 'priority') { k = t.priority; l = t.priority; }
      else if (state.group === 'week') {
        const diff = Math.round((PM.startOfDay(t.due) - PM.startOfDay(TODAY)) / 86400000);
        if (diff < 0) { k = 'z_past'; l = 'Overdue'; }
        else if (diff < 7) { k = 'a_wk1'; l = 'This week'; }
        else if (diff < 14) { k = 'b_wk2'; l = 'Next week'; }
        else { k = 'c_later'; l = 'Later'; }
      }
      if (!map.has(k)) map.set(k, { label: l, tasks: [] });
      map.get(k).tasks.push(t);
    });
    return Array.from(map.values());
  }

  function renderList() {
    const container = $('[data-tasks-list-body]');
    const list = applyFilters(TASKS);
    if (!list.length) {
      container.innerHTML = `<div class="pm-empty"><i class="bi bi-check2-square"></i><p class="mb-0">No tasks match your filters.</p></div>`;
      return;
    }
    const groups = groupTasks(list);
    const html = groups.map(g => {
      const head = g.label ? `<div class="tasks-group-head"><span>${PM.escape(g.label)}</span><span class="count">${g.tasks.length}</span></div>` : '';
      return head + `<div class="table-responsive"><table class="table tasks-table align-middle mb-0">
        <thead><tr>
          <th></th>
          <th>Task</th><th>Project</th><th>Assignees</th><th>Priority</th><th>Status</th><th>Due</th><th class="text-center">Progress</th><th class="text-end">Actions</th>
        </tr></thead>
        <tbody>${g.tasks.map(renderRow).join('')}</tbody>
      </table></div>`;
    }).join('');
    container.innerHTML = html;
    // apply progress widths
    $$('[data-w]', container).forEach(el => el.style.width = el.getAttribute('data-w') + '%');
  }

  function renderBoard() {
    const cols = [
      { key: 'not-started', label: 'Not Started', icon: 'bi-circle' },
      { key: 'in-progress', label: 'In Progress', icon: 'bi-play-circle' },
      { key: 'blocked',     label: 'Blocked',     icon: 'bi-exclamation-octagon' },
      { key: 'done',        label: 'Done',        icon: 'bi-check-circle' }
    ];
    const list = applyFilters(TASKS);
    const boardEl = $('[data-tasks-board]');
    boardEl.innerHTML = cols.map(c => {
      const items = list.filter(t => t.status === c.key);
      const cards = items.map(t => `
        <div class="tasks-card" data-tasks-card data-id="${t.id}">
          <h6 class="tasks-card__title">${PM.escape(t.title)}</h6>
          <div class="tasks-card__meta">
            ${renderProjectChip(t.project)}
            ${renderPriority(t.priority)}
          </div>
          <div class="tasks-card__foot">
            <div class="d-flex align-items-center gap-2">
              ${renderAssignees(t.assignees)}
              <span class="small text-body-secondary">${PM.fmtDateShort(t.due)}</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" data-tasks-open data-id="${t.id}">Open</button>
          </div>
        </div>`).join('') || `<div class="pm-empty"><i class="bi bi-inbox"></i><p class="mb-0 small">Nothing here yet.</p></div>`;
      return `<div class="tasks-col tasks-col--${c.key}">
        <div class="tasks-col__head">
          <div class="d-flex align-items-center gap-2"><span class="tasks-col__accent"></span>${c.label}</div>
          <span class="count">${items.length}</span>
        </div>
        ${cards}
      </div>`;
    }).join('');
  }

  function render() {
    if (state.view === 'list') renderList(); else renderBoard();
  }

  /* ----- Task detail modal ----- */
  function openDetail(id) {
    const t = TASKS.find(x => x.id === id);
    if (!t) return;
    const packet = detailPacket(t);
    $('[data-tasks-detail-title]').textContent = t.title;
    $('[data-tasks-detail-priority]').innerHTML = renderPriority(t.priority);
    $('[data-tasks-detail-desc]').textContent = packet.description;
    $('[data-tasks-detail-meta]').innerHTML = [
      `<div><small class="text-body-secondary d-block">Project</small>${renderProjectChip(t.project)}</div>`,
      `<div><small class="text-body-secondary d-block">Status</small>${renderStatus(t.status)}</div>`,
      `<div><small class="text-body-secondary d-block">Due</small>${renderDue(t.due)}</div>`,
      `<div><small class="text-body-secondary d-block">Assignees</small>${renderAssignees(t.assignees)}</div>`
    ].join('');
    $('[data-tasks-detail-subtasks]').innerHTML = packet.subtasks.map(s => `
      <label class="tasks-subtask">
        <input type="checkbox" class="form-check-input" ${s.done ? 'checked' : ''} data-tasks-subtask="${s.id}">
        <span class="${s.done ? 'text-decoration-line-through text-body-secondary' : ''}">${PM.escape(s.text)}</span>
      </label>`).join('');
    $('[data-tasks-detail-attachments]').innerHTML = packet.attachments.map(a => `
      <a href="#" class="d-inline-flex align-items-center gap-2 text-decoration-none border rounded px-2 py-1 small">
        <i class="bi ${a.icon} text-primary"></i>${PM.escape(a.name)}<span class="text-body-secondary">· ${a.size}</span>
      </a>`).join('');
    $('[data-tasks-detail-related]').innerHTML = packet.related.map(r =>
      `<li><a href="#" class="text-decoration-none" data-tasks-open data-id="${r.id}"><i class="bi bi-arrow-return-right me-1 text-body-secondary"></i>${PM.escape(r.title)}</a></li>`
    ).join('') || '<li class="text-body-secondary">No related tasks.</li>';
    const commentsHtml = packet.comments.map(c => `
      <div class="tasks-detail-comment">
        ${PM.renderAvatar(c.user, 30)}
        <div class="flex-grow-1">
          <p><strong>${PM.escape(c.user.name)}</strong> <small>${PM.relativeTime(c.when)}</small></p>
          <p class="mb-0">${PM.escape(c.text)}</p>
        </div>
      </div>`).join('');
    $('[data-tasks-detail-comments]').innerHTML = commentsHtml;

    bootstrap.Modal.getOrCreateInstance('#tasksDetailModal').show();
  }

  /* ----- Events ----- */
  function bind() {
    // View toggle
    $$('input[name="tasksView"]').forEach(r => r.addEventListener('change', () => {
      state.view = r.value;
      $('[data-tasks-view-container="list"]').classList.toggle('d-none', state.view !== 'list');
      $('[data-tasks-view-container="board"]').classList.toggle('d-none', state.view !== 'board');
      render();
    }));

    // Search + filters
    $('[data-tasks-search]').addEventListener('input', PM.debounce(e => {
      state.filters.q = e.target.value.trim(); render();
    }, 150));
    ['priority','project','assignee','status','due'].forEach(k => {
      $('[data-tasks-filter="' + k + '"]').addEventListener('change', e => {
        state.filters[k] = e.target.value; render();
      });
    });
    $('[data-tasks-group]').addEventListener('change', e => {
      state.group = e.target.value; render();
    });

    // Open detail via row / card click
    document.addEventListener('click', e => {
      const openBtn = e.target.closest('[data-tasks-open]');
      if (openBtn) { e.preventDefault(); openDetail(openBtn.getAttribute('data-id')); return; }
      const row = e.target.closest('[data-tasks-row]');
      if (row && !e.target.closest('[data-tasks-check], [data-tasks-actions-btn], .dropdown-menu, [data-tasks-action]')) {
        openDetail(row.getAttribute('data-id')); return;
      }
      const card = e.target.closest('[data-tasks-card]');
      if (card && !e.target.closest('[data-tasks-open]')) {
        openDetail(card.getAttribute('data-id'));
      }
    });

    // Row actions
    document.addEventListener('click', async e => {
      const link = e.target.closest('[data-tasks-action]');
      if (!link) return;
      e.preventDefault();
      const action = link.getAttribute('data-tasks-action');
      const id = link.getAttribute('data-id');
      const t = TASKS.find(x => x.id === id);
      if (!t) return;
      if (action === 'delete') {
        const ok = await PM.confirm({ title: 'Delete task', message: 'Delete "' + t.title + '"? This cannot be undone.', okText: 'Delete', okVariant: 'danger' });
        if (ok) {
          const i = TASKS.indexOf(t); TASKS.splice(i, 1); render();
          PM.orchidToast('Task deleted', 'danger');
        }
        return;
      }
      if (action === 'archive') {
        const i = TASKS.indexOf(t); TASKS.splice(i, 1); render();
        PM.orchidToast('Task archived', 'secondary'); return;
      }
      if (action === 'duplicate') {
        const copy = Object.assign({}, t, { id: 't' + (TASKS.length + 1), title: t.title + ' (copy)' });
        TASKS.unshift(copy); render();
        PM.orchidToast('Task duplicated', 'success'); return;
      }
      if (action === 'edit')      { PM.orchidToast('Edit task — coming soon', 'primary'); return; }
      if (action === 'reassign')  { PM.orchidToast('Reassign — pick a user', 'primary'); return; }
      if (action === 'priority')  { PM.orchidToast('Change priority — coming soon', 'primary'); return; }
    });

    // New task modal
    $('[data-tasks-new]').addEventListener('click', () => {
      $('[data-tasks-new-form]').reset();
      bootstrap.Modal.getOrCreateInstance('#tasksNewModal').show();
    });
    $('[data-tasks-new-form]').addEventListener('submit', e => {
      e.preventDefault();
      const form = e.target;
      if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
      const t = {
        id: 't' + (TASKS.length + 1),
        title: $('#tasksNewTitleInput').value.trim(),
        project: $('#tasksNewProject').value,
        assignees: [$('#tasksNewAssignee').value].filter(Boolean),
        priority: $('#tasksNewPriority').value,
        status: $('#tasksNewStatus').value,
        due: $('#tasksNewDue').value ? new Date($('#tasksNewDue').value) : PM.addDays(TODAY, 7),
        progress: 0,
        tags: pickTags()
      };
      TASKS.unshift(t);
      bootstrap.Modal.getInstance('#tasksNewModal').hide();
      form.classList.remove('was-validated');
      render();
      PM.orchidToast('Task created', 'success');
    });

    // Comment form
    $('[data-tasks-detail-comment-form]').addEventListener('submit', e => {
      e.preventDefault();
      const input = $('[data-tasks-detail-comment-input]');
      const text = input.value.trim();
      if (!text) return;
      const html = `<div class="tasks-detail-comment">${PM.renderAvatar(PM.userById('u1'), 30)}<div class="flex-grow-1">
        <p><strong>Alex Kim</strong> <small>just now</small></p><p class="mb-0">${PM.escape(text)}</p></div></div>`;
      $('[data-tasks-detail-comments]').insertAdjacentHTML('beforeend', html);
      input.value = '';
    });

    // Subtask toggle
    document.addEventListener('change', e => {
      if (e.target.matches('[data-tasks-subtask]')) {
        const label = e.target.nextElementSibling;
        if (label) label.classList.toggle('text-decoration-line-through', e.target.checked);
      }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    populateFilters();
    bind();
    render();
  });
})();
