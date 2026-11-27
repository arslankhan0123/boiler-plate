/* Orchid — Meetings page */
(function () {
  'use strict';
  if (!window.PM) return;

  const $ = (s, r) => (r || document).querySelector(s);
  const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
  const U = PM.USERS;
  const TODAY = new Date(2026, 6, 23); // 2026-07-23
  const NOW = new Date(2026, 6, 23, 11, 15);

  function mtg(day, hour, min, duration, type, title, organizer, attendees, desc, status, agenda) {
    const start = new Date(2026, 6, day, hour, min || 0);
    const end   = new Date(start.getTime() + duration * 60000);
    return {
      id: 'm' + Math.random().toString(36).slice(2,8),
      title, type, start, end, duration, status: status || autoStatus(start, end), organizer,
      attendees: attendees || [], desc: desc || '', agenda: agenda || defaultAgenda(),
      notes: 'Notes will appear here after the meeting.', recording: status === 'ended' ? '#recording-' + Math.random().toString(36).slice(2,6) : null
    };
  }
  function autoStatus(s, e) {
    if (NOW >= s && NOW <= e) return 'live';
    if (NOW > e) return 'ended';
    return 'upcoming';
  }
  function defaultAgenda() {
    return ['Welcome & context (5m)', 'Discussion (20m)', 'Decisions & action items (5m)'];
  }

  const MEETINGS = [
    // Today
    mtg(23, 9,  0,  30, 'video',     'Standup',                         'u11', ['u1','u3','u4','u5','u7'], 'Daily engineering standup.',                'ended'),
    mtg(23, 11, 15, 30, 'video',     '1:1 with Ananya',                 'u1',  ['u1','u11'],                'Weekly one-on-one.',                        'live'),
    mtg(23, 14, 0,  60, 'video',     'Design Critique: Checkout',       'u2',  ['u2','u6','u1','u4'],       'Review new checkout designs.',              'upcoming'),
    mtg(23, 16, 30, 45, 'in-person', 'Coffee with Sofia',               'u1',  ['u1','u10'],                'Optional catch-up.',                        'upcoming'),
    // Tomorrow
    mtg(24, 10, 0,  60, 'video',     'Q3 Planning',                     'u1',  ['u1','u2','u3','u11','u13'], 'Refine Q3 initiatives.',                    'upcoming'),
    mtg(24, 13, 30, 30, 'phone',     'Vendor call: Analytics tool',     'u8',  ['u8','u1'],                 'Evaluate analytics vendor.',                'upcoming'),
    mtg(24, 15, 0,  30, 'video',     '1:1 with Sarah',                  'u1',  ['u1','u2'],                 'Design career growth.',                     'upcoming'),
    // This week
    mtg(25, 10, 0,  45, 'video',     'Client Kickoff: Titan Media',     'u14', ['u1','u14','u2','u10'],     'Onboard Titan Media team.',                 'upcoming'),
    mtg(25, 15, 0,  60, 'in-person', 'Sprint review',                   'u11', ['u1','u3','u4','u5','u7'],  'Review sprint outcomes.',                   'upcoming'),
    mtg(27, 9,  30, 30, 'video',     'Standup',                         'u11', ['u1','u3','u4'],            'Daily sync.',                               'upcoming'),
    mtg(28, 14, 0,  90, 'in-person', 'People Ops offsite prep',         'u16', ['u16','u1'],                'Plan Q3 offsite agenda.',                   'upcoming'),
    mtg(29, 11, 0,  30, 'phone',     'Investor briefing',               'u1',  ['u1'],                     'Monthly investor call.',                    'upcoming'),
    // Later
    mtg(31, 10, 0,  60, 'video',     'Company all-hands',               'u1',  ['u1','u2','u3','u4','u5','u6','u7','u8','u10','u11','u13','u14','u16'], 'Monthly company update.', 'upcoming'),
    // Past week (Ended / cancelled) — before "today"
    mtg(20, 15, 0,  60, 'video',     'Payments API design review',      'u3',  ['u3','u7','u5'],            'Reviewed migration approach.',              'ended'),
    mtg(21, 10, 0,  30, 'video',     'Marketing weekly',                'u13', ['u13','u10','u1'],          'Weekly marketing sync.',                    'ended'),
    mtg(22, 14, 0,  45, 'phone',     'Prospect call: Acme Co',          'u14', ['u14','u1'],                'Discovery call.',                           'ended'),
    mtg(22, 16, 0,  60, 'video',     'Board update prep',               'u1',  ['u1','u11'],                'Prep slides.',                              'cancelled'),
    mtg(19, 9,  0,  30, 'in-person', 'Coffee with Yuki',                'u1',  ['u1','u15'],                'Casual catch-up.',                          'ended')
  ];

  const state = { search: '', type: '', organizer: '', range: '', poolAttendees: [] };

  function populate() {
    const org = $('[data-mtg-filter="organizer"]');
    U.forEach(u => org.insertAdjacentHTML('beforeend', `<option value="${u.id}">${PM.escape(u.name)}</option>`));
    $('#mtgDate').value = PM.dateISO(TODAY);
    $('#mtgTime').value = '10:00';
  }

  /* --- Filter + group --- */
  function inRange(m, r) {
    const diff = Math.round((PM.startOfDay(m.start) - PM.startOfDay(TODAY)) / 86400000);
    if (r === 'today')    return diff === 0;
    if (r === 'tomorrow') return diff === 1;
    if (r === 'week')     return diff >= 0 && diff <= 7;
    if (r === 'later')    return diff > 7;
    return true;
  }
  function filtered() {
    return MEETINGS.filter(m => {
      if (state.search && !m.title.toLowerCase().includes(state.search.toLowerCase())) return false;
      if (state.type && m.type !== state.type) return false;
      if (state.organizer && m.organizer !== state.organizer) return false;
      if (state.range && !inRange(m, state.range)) return false;
      return true;
    }).sort((a, b) => a.start - b.start);
  }

  function groupOf(m) {
    const diff = Math.round((PM.startOfDay(m.start) - PM.startOfDay(TODAY)) / 86400000);
    if (diff < 0) return { key: 'z_past', label: 'Past' };
    if (diff === 0) return { key: 'a_today', label: 'Today' };
    if (diff === 1) return { key: 'b_tomorrow', label: 'Tomorrow' };
    if (diff <= 7) return { key: 'c_thisweek', label: 'This week' };
    return { key: 'd_later', label: 'Later' };
  }

  const TYPE_ICONS = { video: 'bi-camera-video', 'in-person': 'bi-people', phone: 'bi-telephone' };

  function renderRow(m) {
    const org = PM.userById(m.organizer);
    const isLive = m.status === 'live';
    const badge = isLive
      ? `<span class="mtg-status live"><span class="mtg-live-dot"></span>LIVE</span>`
      : `<span class="mtg-status ${m.status}">${m.status}</span>`;
    const joinBtn = m.status === 'live'
      ? `<button type="button" class="btn btn-sm mtg-join-live" data-mtg-join data-id="${m.id}"><i class="bi bi-camera-video-fill me-1"></i>Join now</button>`
      : m.status === 'upcoming'
        ? `<button type="button" class="btn btn-sm ${m.type === 'video' ? 'btn-primary' : 'btn-outline-secondary'}" data-mtg-join data-id="${m.id}"><i class="bi bi-${m.type === 'video' ? 'camera-video' : m.type === 'phone' ? 'telephone' : 'geo-alt'} me-1"></i>${m.type === 'video' ? 'Join' : m.type === 'phone' ? 'Dial' : 'Directions'}</button>`
        : `<button type="button" class="btn btn-sm btn-outline-secondary" data-mtg-view data-id="${m.id}">Details</button>`;
    const attHtml = PM.renderAvatarStack(m.attendees.map(id => PM.userById(id)).filter(Boolean), 4);
    return `
      <div class="mtg-item ${isLive ? 'is-live' : ''}" data-mtg-item data-id="${m.id}">
        <div class="mtg-item__icon ${m.type}"><i class="bi ${TYPE_ICONS[m.type]}"></i></div>
        <div class="mtg-item__body">
          <p class="mtg-item__title">${PM.escape(m.title)}</p>
          <div class="mtg-item__meta">
            <span><i class="bi bi-clock me-1"></i>${PM.fmtTime(m.start)} — ${PM.fmtTime(m.end)}</span>
            <span><i class="bi bi-hourglass-split me-1"></i>${m.duration} min</span>
            <span><i class="bi bi-person me-1"></i>${PM.escape(org.name)}</span>
            ${attHtml}
            ${badge}
          </div>
        </div>
        <div class="mtg-item__actions">
          ${joinBtn}
          <button type="button" class="btn btn-sm btn-icon" data-mtg-view data-id="${m.id}" aria-label="Details"><i class="bi bi-info-circle"></i></button>
        </div>
      </div>`;
  }

  function render() {
    const list = filtered();
    const host = $('[data-mtg-list]');
    const empty = $('[data-mtg-empty]');
    empty.classList.toggle('d-none', !!list.length);
    if (!list.length) { host.innerHTML = ''; return; }
    const groups = new Map();
    const order = ['a_today','b_tomorrow','c_thisweek','d_later','z_past'];
    list.forEach(m => {
      const g = groupOf(m);
      if (!groups.has(g.key)) groups.set(g.key, { label: g.label, items: [] });
      groups.get(g.key).items.push(m);
    });
    host.innerHTML = order.filter(k => groups.has(k)).map(k => {
      const g = groups.get(k);
      return `<section class="mtg-group">
        <div class="mtg-group__head">${g.label}<span class="count">${g.items.length}</span></div>
        ${g.items.map(renderRow).join('')}
      </section>`;
    }).join('');
  }

  /* --- Detail modal --- */
  function openDetail(id) {
    const m = MEETINGS.find(x => x.id === id);
    if (!m) return;
    $('[data-mtg-detail-title]').textContent = m.title;
    const org = PM.userById(m.organizer);
    const badge = m.status === 'live'
      ? '<span class="mtg-status live"><span class="mtg-live-dot"></span>LIVE</span>'
      : `<span class="mtg-status ${m.status}">${m.status}</span>`;
    $('[data-mtg-detail-meta]').innerHTML = [
      `<span class="mtg-item__icon ${m.type}" style="width:28px;height:28px;font-size:.8rem"><i class="bi ${TYPE_ICONS[m.type]}"></i></span>`,
      `<small class="text-body-secondary">${PM.fmtDate(m.start)} · ${PM.fmtTime(m.start)}–${PM.fmtTime(m.end)}</small>`,
      badge
    ].join(' ');
    $('[data-mtg-detail-desc]').textContent = m.desc || 'No description.';
    $('[data-mtg-detail-attendees]').innerHTML = m.attendees.map((uid, i) => {
      const u = PM.userById(uid);
      const rsvp = ['Accepted','Accepted','Tentative','Declined'][i % 4];
      const rsvpCls = { Accepted: 'success', Tentative: 'warning', Declined: 'danger' }[rsvp];
      return `<li class="d-flex align-items-center gap-2 py-2 border-bottom">
        ${PM.renderAvatar(u, 30)}
        <div class="flex-grow-1"><strong class="small">${PM.escape(u.name)}</strong>${uid === m.organizer ? ' <span class="badge bg-primary-subtle text-primary ms-1">Organizer</span>' : ''}<div class="text-body-secondary small">${PM.escape(u.email)}</div></div>
        <span class="badge bg-${rsvpCls}-subtle text-${rsvpCls}">${rsvp}</span>
      </li>`;
    }).join('');
    $('[data-mtg-detail-agenda]').innerHTML = m.agenda.map(a => `<li>${PM.escape(a)}</li>`).join('');
    $('[data-mtg-detail-notes]').textContent = m.notes;
    $('[data-mtg-detail-recording-wrap]').classList.toggle('d-none', !m.recording);
    if (m.recording) $('[data-mtg-detail-recording]').setAttribute('href', m.recording);
    const joinBtn = $('[data-mtg-detail-join]');
    joinBtn.classList.toggle('mtg-join-live', m.status === 'live');
    joinBtn.disabled = m.status === 'ended' || m.status === 'cancelled';
    joinBtn.setAttribute('data-id', m.id);
    bootstrap.Modal.getOrCreateInstance('#mtgDetailModal').show();
  }

  /* --- Attendee picker in schedule form --- */
  function renderPool() {
    const pool = $('[data-mtg-pool]');
    Array.from(pool.querySelectorAll('.pill')).forEach(p => p.remove());
    const input = pool.querySelector('input');
    state.poolAttendees.forEach(uid => {
      const u = PM.userById(uid);
      if (!u) return;
      const pill = document.createElement('span');
      pill.className = 'pill';
      pill.innerHTML = `${PM.renderAvatar(u, 18)}${PM.escape(u.name)}<button type="button" data-mtg-attendee-remove data-uid="${uid}" aria-label="Remove"><i class="bi bi-x"></i></button>`;
      pool.insertBefore(pill, input);
    });
  }
  function refreshSuggest(q) {
    const box = $('[data-mtg-suggest]');
    if (!q) { box.classList.add('d-none'); return; }
    const matches = U.filter(u => state.poolAttendees.indexOf(u.id) === -1 && u.name.toLowerCase().includes(q.toLowerCase())).slice(0, 6);
    if (!matches.length) { box.classList.add('d-none'); return; }
    box.innerHTML = matches.map(u => `<div class="item" data-mtg-attendee-add data-uid="${u.id}">${PM.renderAvatar(u, 22)}<div><strong>${PM.escape(u.name)}</strong><div class="text-body-secondary" style="font-size:.68rem">${PM.escape(u.email)}</div></div></div>`).join('');
    box.classList.remove('d-none');
  }

  /* --- Bindings --- */
  function bind() {
    $('[data-mtg-search]').addEventListener('input', PM.debounce(e => { state.search = e.target.value.trim(); render(); }, 150));
    ['type','organizer','range'].forEach(k => {
      $(`[data-mtg-filter="${k}"]`).addEventListener('change', e => { state[k] = e.target.value; render(); });
    });

    document.addEventListener('click', e => {
      const join = e.target.closest('[data-mtg-join]');
      if (join) { PM.orchidToast('Opening meeting…', 'primary'); return; }
      const view = e.target.closest('[data-mtg-view]');
      if (view) { openDetail(view.getAttribute('data-id')); return; }
      const item = e.target.closest('[data-mtg-item]');
      if (item && !e.target.closest('button, a')) { openDetail(item.getAttribute('data-id')); return; }
      const add = e.target.closest('[data-mtg-attendee-add]');
      if (add) {
        const uid = add.getAttribute('data-uid');
        if (state.poolAttendees.indexOf(uid) === -1) state.poolAttendees.push(uid);
        $('[data-mtg-attendee-input]').value = '';
        refreshSuggest('');
        renderPool();
        return;
      }
      const rem = e.target.closest('[data-mtg-attendee-remove]');
      if (rem) {
        const uid = rem.getAttribute('data-uid');
        state.poolAttendees = state.poolAttendees.filter(x => x !== uid);
        renderPool();
      }
    });

    $('[data-mtg-attendee-input]').addEventListener('input', PM.debounce(e => refreshSuggest(e.target.value.trim()), 120));

    $('[data-mtg-detail-cancel]').addEventListener('click', async () => {
      const id = $('[data-mtg-detail-join]').getAttribute('data-id');
      const ok = await PM.confirm({ title: 'Cancel meeting', message: 'Cancel this meeting and notify attendees?', okText: 'Cancel meeting', okVariant: 'danger' });
      if (!ok) return;
      const m = MEETINGS.find(x => x.id === id);
      if (m) m.status = 'cancelled';
      bootstrap.Modal.getInstance('#mtgDetailModal').hide();
      render();
      PM.orchidToast('Meeting cancelled', 'danger');
    });
    $('[data-mtg-detail-reschedule]').addEventListener('click', () => PM.orchidToast('Reschedule flow — coming soon', 'primary'));
    $('[data-mtg-detail-join]').addEventListener('click', () => PM.orchidToast('Opening meeting…', 'primary'));

    // Schedule form submit
    $('[data-mtg-form]').addEventListener('submit', e => {
      e.preventDefault();
      const form = e.target;
      if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
      form.classList.remove('was-validated');
      const title = $('#mtgTitle').value.trim();
      const date = $('#mtgDate').value;
      const time = $('#mtgTime').value;
      const [Y, M, D] = date.split('-').map(Number);
      const [h, mi] = time.split(':').map(Number);
      const dur = parseInt($('#mtgDuration').value, 10);
      const type = $('#mtgType').value;
      const start = new Date(Y, M - 1, D, h, mi);
      const end   = new Date(start.getTime() + dur * 60000);
      MEETINGS.push({
        id: 'm' + Math.random().toString(36).slice(2,8),
        title, type, start, end, duration: dur, status: autoStatus(start, end),
        organizer: 'u1', attendees: state.poolAttendees.slice(),
        desc: $('#mtgNotes').value.trim(), agenda: defaultAgenda(), notes: '—', recording: null
      });
      state.poolAttendees = [];
      form.reset();
      $('#mtgDate').value = PM.dateISO(TODAY);
      $('#mtgTime').value = '10:00';
      renderPool();
      render();
      PM.orchidToast('Invites sent · meeting scheduled', 'success');
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    populate();
    renderPool();
    render();
    bind();
  });
})();
