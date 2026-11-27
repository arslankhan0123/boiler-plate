/* Orchid — Teams page */
(function () {
  'use strict';
  if (!window.PM) return;

  const $ = (s, r) => (r || document).querySelector(s);
  const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
  const U = PM.USERS;

  /* Team dataset — 8 teams */
  const TEAMS = [
    { id: 'tm1', name: 'Engineering',  desc: 'Ship reliable software for millions.', lead: 'u11', members: ['u3','u4','u5','u7','u9','u11','u12'], projects: [{ name:'Aurora Mobile App v2', progress: 65 }, { name:'Payments API Migration', progress: 40 }, { name:'Website Redesign Q3', progress: 55 }] },
    { id: 'tm2', name: 'Design',       desc: 'Craft interfaces people love.',         lead: 'u2',  members: ['u2','u6','u10'], projects: [{ name:'Website Redesign Q3', progress: 72 }, { name:'Aurora Mobile App v2', progress: 60 }] },
    { id: 'tm3', name: 'Product',      desc: 'Strategy, roadmap and outcomes.',       lead: 'u1',  members: ['u1','u6','u16'], projects: [{ name:'Onboarding Revamp', progress: 45 }, { name:'Titan Media Rollout', progress: 22 }] },
    { id: 'tm4', name: 'Marketing',    desc: 'Tell the story to the world.',          lead: 'u13', members: ['u13','u10','u14'], projects: [{ name:'Website Redesign Q3', progress: 68 }] },
    { id: 'tm5', name: 'Sales',        desc: 'Grow revenue by helping customers.',    lead: 'u14', members: ['u14','u1','u13'], projects: [{ name:'Titan Media Rollout', progress: 34 }] },
    { id: 'tm6', name: 'People Ops',   desc: 'Hire, grow and support the team.',      lead: 'u16', members: ['u16','u1'], projects: [] },
    { id: 'tm7', name: 'Data',         desc: 'Insight from measurement.',             lead: 'u15', members: ['u15','u8','u5','u11'], projects: [{ name:'Payments API Migration', progress: 30 }] },
    { id: 'tm8', name: 'Support',      desc: 'Delighting every customer.',            lead: 'u4',  members: ['u4','u10','u16','u6'], projects: [{ name:'Onboarding Revamp', progress: 52 }] }
  ];
  const MY_TEAM_IDS = ['tm1','tm3','tm7']; // "My teams"

  const state = { search: '', scope: 'all', activeTeamId: null };
  let addMemberModal, newModal, drawer;

  /* ---------- Render team cards ---------- */
  function renderCards() {
    const grid = $('[data-teams-grid]');
    const empty = $('[data-teams-empty]');
    const filtered = TEAMS.filter(t => {
      if (state.scope === 'mine' && MY_TEAM_IDS.indexOf(t.id) === -1) return false;
      if (state.search && !t.name.toLowerCase().includes(state.search.toLowerCase())) return false;
      return true;
    });
    $('[data-teams-count]').textContent = filtered.length + ' team' + (filtered.length === 1 ? '' : 's');
    empty.classList.toggle('d-none', !!filtered.length);
    grid.innerHTML = filtered.map(t => {
      const members = t.members.map(id => PM.userById(id)).filter(Boolean);
      const lead = PM.userById(t.lead);
      const gradient = PM.TEAM_GRADIENTS[t.name] || 'linear-gradient(135deg,#6366f1,#22d3ee)';
      return `
        <article class="teams-card" data-teams-card data-id="${t.id}">
          <div class="teams-card__band" data-teams-band="${t.id}"></div>
          <div class="teams-card__body">
            <h3 class="teams-card__title">${PM.escape(t.name)}</h3>
            <p class="text-body-secondary small mb-2">${PM.escape(t.desc)}</p>
            <div class="teams-card__meta">
              <span class="pm-chip" style="background:rgba(79,70,229,.10);color:#4f46e5"><i class="bi bi-people"></i>${members.length} members</span>
              <span class="pm-chip" style="background:rgba(16,185,129,.10);color:#047857"><i class="bi bi-briefcase"></i>${t.projects.length} projects</span>
            </div>
            <div class="mt-1">${PM.renderAvatarStack(members, 5)}</div>
            <div class="teams-card__lead">
              ${PM.renderAvatar(lead, 30)}
              <div class="flex-grow-1">
                <span class="lead-label d-block">Team lead</span>
                <strong>${PM.escape(lead.name)}</strong>
              </div>
            </div>
            <div class="teams-card__actions">
              <button type="button" class="btn btn-primary btn-sm flex-grow-1" data-teams-view data-id="${t.id}"><i class="bi bi-arrow-right-circle me-1"></i>View team</button>
              <button type="button" class="btn btn-outline-secondary btn-sm" data-teams-add-member-btn data-id="${t.id}" aria-label="Add member"><i class="bi bi-person-plus"></i></button>
            </div>
          </div>
        </article>`;
    }).join('');
    // apply gradients (no inline style attribute in HTML strings for compliance would be OK — style is dynamic; setting via JS is not "style=''" in HTML source)
    $$('[data-teams-band]', grid).forEach(band => {
      const id = band.getAttribute('data-teams-band');
      const t = TEAMS.find(x => x.id === id);
      band.style.background = PM.TEAM_GRADIENTS[t.name] || 'linear-gradient(135deg,#6366f1,#22d3ee)';
    });
  }

  /* ---------- Drawer ---------- */
  function openDrawer(id) {
    const t = TEAMS.find(x => x.id === id);
    if (!t) return;
    state.activeTeamId = id;
    const hero = $('[data-teams-drawer-hero]');
    hero.style.background = PM.TEAM_GRADIENTS[t.name] || 'linear-gradient(135deg,#6366f1,#22d3ee)';
    $('[data-teams-drawer-name]').textContent = t.name;
    $('[data-teams-drawer-desc]').textContent = t.desc;
    const members = t.members.map(id => PM.userById(id)).filter(Boolean);
    $('[data-teams-drawer-chips]').innerHTML = [
      `<span class="pm-chip" style="background:rgba(255,255,255,.22);color:#fff"><i class="bi bi-people"></i>${members.length} members</span>`,
      `<span class="pm-chip" style="background:rgba(255,255,255,.22);color:#fff"><i class="bi bi-briefcase"></i>${t.projects.length} projects</span>`
    ].join('');

    // Members
    $('[data-teams-members]').innerHTML = members.map((u, i) => {
      const role = i === 0 ? 'Team lead' : (i < 3 ? 'Admin' : 'Member');
      const joined = PM.fmtDate(PM.addDays(new Date(2026, 6, 23), -30 - i * 12));
      return `
        <div class="teams-member-row">
          ${PM.renderAvatar(u, 36)}
          <div class="teams-member-row__meta">
            <p>${PM.escape(u.name)}</p>
            <small>${PM.escape(u.role)} · joined ${joined}</small>
          </div>
          <span class="badge bg-primary-subtle text-primary me-2">${role}</span>
          <div class="dropdown">
            <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="#" data-teams-member-action="change-role" data-uid="${u.id}"><i class="bi bi-shield me-2"></i>Change role</a></li>
              <li><a class="dropdown-item text-danger" href="#" data-teams-member-action="remove" data-uid="${u.id}"><i class="bi bi-x-circle me-2"></i>Remove</a></li>
            </ul>
          </div>
        </div>`;
    }).join('');

    // Projects
    $('[data-teams-projects]').innerHTML = t.projects.length ? t.projects.map(p => `
      <div class="teams-proj-row">
        <div class="d-flex justify-content-between align-items-center">
          <strong class="small">${PM.escape(p.name)}</strong>
          <small class="text-body-secondary">${p.progress}%</small>
        </div>
        <div class="teams-proj-row__bar"><span data-w="${p.progress}"></span></div>
      </div>`).join('') : '<p class="text-body-secondary small mb-0">No active projects.</p>';
    $$('[data-w]', $('[data-teams-projects]')).forEach(el => el.style.width = el.getAttribute('data-w') + '%');

    // Files
    $('[data-teams-files]').innerHTML = [
      { name:'Team charter.pdf', size:'420 KB', icon:'bi-file-earmark-pdf' },
      { name:'Roadmap Q3.xlsx',  size:'2.1 MB', icon:'bi-file-earmark-spreadsheet' },
      { name:'Brand assets.zip', size:'15.4 MB',icon:'bi-file-earmark-zip' }
    ].map(f => `<li class="d-flex align-items-center gap-2 py-2 border-bottom small"><i class="bi ${f.icon} text-primary"></i><span class="flex-grow-1">${f.name}</span><span class="text-body-secondary">${f.size}</span></li>`).join('');

    // Activity
    $('[data-teams-activity]').innerHTML = [
      { who:'Alex Kim',     what:'invited Marcus Weber to the team', when:'2h ago' },
      { who:'Sarah Miller', what:'uploaded Brand assets.zip',        when:'yesterday' },
      { who:'James Doe',    what:'moved a task to Done',              when:'2d ago' }
    ].map(a => `<li class="d-flex align-items-start gap-2 py-2 border-bottom small">
      <span class="orchid-dot bg-primary mt-2"></span>
      <div class="flex-grow-1"><strong>${a.who}</strong> ${a.what}<div class="text-body-secondary">${a.when}</div></div>
    </li>`).join('');

    bootstrap.Offcanvas.getOrCreateInstance('#teamsDrawer').show();
  }

  /* ---------- Add Member modal ---------- */
  function refreshAddMemberList(q) {
    const t = TEAMS.find(x => x.id === state.activeTeamId);
    const select = $('[data-teams-add-list]');
    const list = U.filter(u => (!t || t.members.indexOf(u.id) === -1) && (!q || u.name.toLowerCase().includes(q.toLowerCase())));
    select.innerHTML = list.map(u => `<option value="${u.id}">${PM.escape(u.name)} — ${PM.escape(u.role)}</option>`).join('');
  }

  /* ---------- Bindings ---------- */
  function bind() {
    $('[data-teams-search]').addEventListener('input', PM.debounce(e => { state.search = e.target.value; renderCards(); }, 150));
    $$('input[name="teamsScope"]').forEach(r => r.addEventListener('change', () => { state.scope = r.value; renderCards(); }));

    document.addEventListener('click', e => {
      const view = e.target.closest('[data-teams-view]');
      if (view) { e.preventDefault(); openDrawer(view.getAttribute('data-id')); return; }
      const card = e.target.closest('[data-teams-card]');
      if (card && !e.target.closest('button, a')) { openDrawer(card.getAttribute('data-id')); return; }
      const add = e.target.closest('[data-teams-add-member-btn], [data-teams-add-member]');
      if (add) {
        e.preventDefault();
        const id = add.getAttribute('data-id') || state.activeTeamId;
        if (id) state.activeTeamId = id;
        refreshAddMemberList('');
        bootstrap.Modal.getOrCreateInstance('#teamsAddMemberModal').show();
        return;
      }
      const memAct = e.target.closest('[data-teams-member-action]');
      if (memAct) {
        e.preventDefault();
        const act = memAct.getAttribute('data-teams-member-action');
        const uid = memAct.getAttribute('data-uid');
        if (act === 'remove') {
          PM.confirm({ title: 'Remove member', message: 'Remove this member from the team?', okText: 'Remove' }).then(ok => {
            if (!ok) return;
            const t = TEAMS.find(x => x.id === state.activeTeamId);
            t.members = t.members.filter(id => id !== uid);
            openDrawer(state.activeTeamId);
            renderCards();
            PM.orchidToast('Member removed', 'danger');
          });
        } else if (act === 'change-role') {
          PM.orchidToast('Role updated', 'success');
        }
      }
    });

    $('[data-teams-new]').addEventListener('click', () => {
      $('[data-teams-new-form]').reset();
      const sel = $('[data-teams-new-lead]');
      sel.innerHTML = U.map(u => `<option value="${u.id}">${PM.escape(u.name)}</option>`).join('');
      bootstrap.Modal.getOrCreateInstance('#teamsNewModal').show();
    });

    $('[data-teams-new-form]').addEventListener('submit', e => {
      e.preventDefault();
      const form = e.target;
      if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
      const t = {
        id: 'tm' + (TEAMS.length + 1),
        name: $('#teamsNewName').value.trim(),
        desc: $('#teamsNewDesc').value.trim() || 'A new team.',
        lead: $('#teamsNewLead').value,
        members: [$('#teamsNewLead').value],
        projects: []
      };
      TEAMS.push(t);
      form.classList.remove('was-validated');
      bootstrap.Modal.getInstance('#teamsNewModal').hide();
      renderCards();
      PM.orchidToast('Team created', 'success');
    });

    $('[data-teams-add-search]').addEventListener('input', e => refreshAddMemberList(e.target.value.trim()));
    $('[data-teams-add-form]').addEventListener('submit', e => {
      e.preventDefault();
      const form = e.target;
      const sel = $('[data-teams-add-list]');
      if (!sel.value) { sel.classList.add('is-invalid'); form.classList.add('was-validated'); return; }
      sel.classList.remove('is-invalid');
      const t = TEAMS.find(x => x.id === state.activeTeamId);
      if (t && t.members.indexOf(sel.value) === -1) t.members.push(sel.value);
      const invite = $('#teamsAddInvite').checked;
      bootstrap.Modal.getInstance('#teamsAddMemberModal').hide();
      openDrawer(state.activeTeamId);
      renderCards();
      PM.orchidToast('Member added' + (invite ? ' · invitation sent' : ''), 'success');
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    bind();
    renderCards();
  });
})();
