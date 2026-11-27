/* Orchid — Calendar page */
(function () {
  'use strict';
  if (!window.PM) return;

  const $ = (s, r) => (r || document).querySelector(s);
  const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
  const CATS = Object.keys(PM.CATEGORY_COLORS);
  const TODAY = new Date(2026, 6, 23);

  /* ---------- Seed events for current month (~25) ---------- */
  function evt(day, hour, dur, title, cat, desc, attendees) {
    const start = new Date(2026, 6, day, hour, 0, 0);
    const end   = new Date(2026, 6, day, hour + Math.floor(dur), (dur - Math.floor(dur)) * 60, 0);
    return { id: 'e' + Math.random().toString(36).slice(2, 8), title, cat, desc, start, end, attendees: attendees || [] };
  }
  const EVENTS = [
    evt(1,  9,   1,   'Q3 Planning',                 'Meeting',    'Kickoff of Q3 roadmap discussion.', ['u1','u2','u3','u11']),
    evt(2,  10,  1,   'Design Critique: Checkout',   'Meeting',    'Walkthrough of new checkout flow.', ['u2','u6','u1']),
    evt(3,  14,  0.5, '1:1 with Ananya',             'Meeting',    'Weekly sync.', ['u1','u11']),
    evt(6,  9,   2,   'Focus block: RFC drafting',   'Focus Time', 'Deep work on infra RFC.', ['u3']),
    evt(6,  16,  1,   'Payments API demo',           'Meeting',    'Demo migration progress.', ['u3','u7','u5','u1']),
    evt(7,  0,   0,   'Aurora v2 code freeze',       'Deadline',   'No new features after this date.'),
    evt(8,  11,  1,   'Client Kickoff: Titan Media', 'Meeting',    'Introduce team and scope.', ['u1','u14','u2','u10']),
    evt(9,  15,  1,   'Onboarding walkthrough',      'Meeting',    'Present new signup flow.', ['u1','u4']),
    evt(10, 8,   2,   'Focus: SSO spike',            'Focus Time', 'Investigate SSO providers.', ['u3']),
    evt(13, 12,  1,   'Team lunch',                  'Personal',   'Optional team lunch — Rooftop Cafe.'),
    evt(14, 10,  1,   'Design Critique: Onboarding', 'Meeting',    'Review empty states.', ['u2','u4','u6']),
    evt(15, 0,   0,   'Marketing site launch',       'Deadline',   'Website Redesign Q3 goes live.'),
    evt(16, 9,   0.5, 'Standup',                     'Meeting',    'Daily sync.', ['u1','u3','u4','u5']),
    evt(17, 13,  1.5, '1:1 with Sarah',              'Meeting',    'Design career growth.', ['u1','u2']),
    evt(20, 10,  1,   'Q3 Board update',             'Company',    'Company all-hands.', ['u1','u11','u13']),
    evt(21, 15,  1,   'Sprint retro',                'Meeting',    'Retrospective.', ['u1','u3','u4','u5','u7']),
    evt(22, 9,   2,   'Focus: analytics migration',  'Focus Time', 'Move dashboards.', ['u8']),
    evt(23, 11,  0.5, 'Standup',                     'Meeting',    'Daily sync.', ['u1','u3','u4']),
    evt(23, 15,  1,   'Design review: dark theme',   'Meeting',    'Dark mode tokens review.', ['u2','u9']),
    evt(24, 10,  1,   'People Ops sync',             'Meeting',    'Q3 hiring plan.', ['u1','u16']),
    evt(27, 14,  1,   'Titan Media weekly sync',     'Meeting',    'Status check with client.', ['u1','u14','u2']),
    evt(28, 9,   2,   'Focus: SSO implementation',   'Focus Time', 'Ship SSO for enterprise.', ['u3','u7']),
    evt(29, 16,  1,   'Product review',              'Meeting',    'Product review with leadership.', ['u1','u2','u11']),
    evt(30, 0,   0,   'Q3 launch retrospective',     'Deadline',   'Wrap up Q3.'),
    evt(31, 12,  1,   'Team lunch',                  'Personal',   'Farewell lunch for Q3.')
  ];

  const state = {
    view: 'month',
    view_date: new Date(TODAY),      // main view anchor
    mini_date: new Date(TODAY),      // mini calendar anchor
    hiddenCats: new Set()
  };

  /* ---------- Legend ---------- */
  function renderLegend() {
    const el = $('[data-cal-legend]');
    el.innerHTML = CATS.map(c => `
      <button type="button" class="legend-chip" data-cal-legend-toggle data-cat="${c}">
        <span class="dot" data-cal-dot="${c}"></span>${c}
      </button>`).join('');
    $$('[data-cal-dot]', el).forEach(d => d.style.background = PM.CATEGORY_COLORS[d.getAttribute('data-cal-dot')]);
    $$('[data-cal-legend-toggle]', el).forEach(b => {
      b.addEventListener('click', () => {
        const c = b.getAttribute('data-cat');
        if (state.hiddenCats.has(c)) state.hiddenCats.delete(c); else state.hiddenCats.add(c);
        b.classList.toggle('off', state.hiddenCats.has(c));
        renderMain();
      });
    });
  }

  function visibleEvents() { return EVENTS.filter(e => !state.hiddenCats.has(e.cat)); }
  function eventsOn(date) {
    return visibleEvents().filter(e => PM.sameDay(e.start, date)).sort((a, b) => a.start - b.start);
  }

  /* ---------- Month view ---------- */
  function renderMonth() {
    const year = state.view_date.getFullYear();
    const month = state.view_date.getMonth();
    $('[data-cal-label]').textContent = PM.MONTHS[month] + ' ' + year;
    const cells = PM.getMonthGrid(year, month);
    const host = $('[data-cal-month]');
    host.innerHTML = cells.map(d => {
      const isOther = d.getMonth() !== month;
      const isToday = PM.sameDay(d, TODAY);
      const isWeekend = d.getDay() === 0 || d.getDay() === 6;
      const evs = eventsOn(d);
      const chips = evs.slice(0, 3).map(e => `<span class="cal-event-chip" data-cal-event="${e.id}" data-cat="${e.cat}">${PM.escape(e.title)}</span>`).join('');
      const more = evs.length > 3 ? `<span class="cal-more">+${evs.length - 3} more</span>` : '';
      return `<div class="cal-cell ${isOther ? 'is-other-month' : ''} ${isToday ? 'is-today' : ''} ${isWeekend ? 'is-weekend' : ''}" data-cal-day-cell="${PM.dateISO(d)}">
        <div class="cal-cell__date">${d.getDate()}</div>
        ${chips}${more}
      </div>`;
    }).join('');
    $$('[data-cal-event]', host).forEach(chip => {
      chip.style.background = PM.CATEGORY_COLORS[chip.getAttribute('data-cat')];
    });
  }

  /* ---------- Week / Day ---------- */
  function renderTimeGrid(startDate, days) {
    const wrap = days === 7 ? $('[data-cal-week]') : $('[data-cal-day]');
    const HOURS = []; for (let h = 7; h <= 20; h++) HOURS.push(h);
    const heads = [`<div class="cal-time-head"></div>`];
    for (let i = 0; i < days; i++) {
      const d = PM.addDays(startDate, i);
      heads.push(`<div class="cal-time-head">${PM.DAYS_SHORT[d.getDay()]} ${d.getDate()}</div>`);
    }
    let cells = '';
    HOURS.forEach(h => {
      cells += `<div class="cal-time-slot">${(h % 12 || 12)} ${h < 12 ? 'AM' : 'PM'}</div>`;
      for (let i = 0; i < days; i++) {
        cells += `<div class="cal-day-slot" data-cal-slot="${PM.dateISO(PM.addDays(startDate, i))}" data-cal-hour="${h}"></div>`;
      }
    });
    wrap.innerHTML = heads.join('') + cells;
    // absolute-positioned events
    for (let i = 0; i < days; i++) {
      const d = PM.addDays(startDate, i);
      const evs = eventsOn(d).filter(e => e.start.getHours() >= 7 && e.start.getHours() <= 20);
      evs.forEach(e => {
        const startCol = i + 2; // 1st col is time; grid col index for day i
        const startH = e.start.getHours() + e.start.getMinutes() / 60;
        const endH = Math.max(e.end.getHours() + e.end.getMinutes() / 60, startH + 0.5);
        const rowStart = HOURS.indexOf(Math.floor(startH));
        if (rowStart < 0) return;
        const top = (startH - Math.floor(startH)) * 44;
        const height = (endH - startH) * 44 - 4;
        // Insert event overlay into the slot cell for row Start
        const slotSelector = `[data-cal-slot="${PM.dateISO(d)}"][data-cal-hour="${Math.floor(startH)}"]`;
        const slot = wrap.querySelector(slotSelector);
        if (!slot) return;
        const ev = document.createElement('div');
        ev.className = 'cal-time-event';
        ev.setAttribute('data-cal-event', e.id);
        ev.style.background = PM.CATEGORY_COLORS[e.cat];
        ev.style.top = top + 'px';
        ev.style.height = height + 'px';
        ev.innerHTML = `${PM.escape(e.title)}<small>${PM.fmtTime(e.start)}–${PM.fmtTime(e.end)}</small>`;
        slot.appendChild(ev);
      });
    }
  }

  function renderWeek() {
    const d = new Date(state.view_date);
    const start = PM.addDays(d, -d.getDay()); // Sunday
    $('[data-cal-label]').textContent = 'Week of ' + PM.fmtDate(start);
    renderTimeGrid(start, 7);
  }
  function renderDay() {
    $('[data-cal-label]').textContent = PM.fmtDate(state.view_date);
    renderTimeGrid(state.view_date, 1);
  }

  /* ---------- Mini calendar + side lists ---------- */
  function renderMini() {
    const y = state.mini_date.getFullYear();
    const m = state.mini_date.getMonth();
    $('[data-cal-mini-label]').textContent = PM.MONTHS_SHORT[m] + ' ' + y;
    const cells = PM.getMonthGrid(y, m);
    const host = $('[data-cal-mini]');
    const heads = PM.DAYS_SHORT.map(d => `<div class="day-head">${d[0]}</div>`).join('');
    const days = cells.map(d => {
      const isOther = d.getMonth() !== m;
      const isToday = PM.sameDay(d, TODAY);
      const has = eventsOn(d).length > 0;
      return `<div class="day ${isOther ? 'other' : ''} ${isToday ? 'is-today' : ''} ${has ? 'has-event' : ''}" data-cal-mini-day="${PM.dateISO(d)}">${d.getDate()}</div>`;
    }).join('');
    host.innerHTML = heads + days;
  }
  function renderTodayList() {
    const evs = eventsOn(TODAY);
    const host = $('[data-cal-today-list]');
    host.innerHTML = evs.length ? evs.map(e => `
      <div class="cal-side-item" data-cal-event="${e.id}">
        <div class="cal-side-item__bar" data-cat="${e.cat}"></div>
        <div class="cal-side-item__meta"><p>${PM.escape(e.title)}</p><small>${e.end.getTime() === e.start.getTime() ? 'All day' : PM.fmtTime(e.start) + ' – ' + PM.fmtTime(e.end)}</small></div>
      </div>`).join('') : '<p class="text-body-secondary small mb-0">Nothing scheduled today.</p>';
    $$('[data-cat]', host).forEach(b => b.style.background = PM.CATEGORY_COLORS[b.getAttribute('data-cat')]);
  }
  function renderUpcoming() {
    const upcoming = visibleEvents().filter(e => e.start > TODAY).sort((a,b) => a.start - b.start).slice(0, 6);
    const host = $('[data-cal-upcoming-list]');
    host.innerHTML = upcoming.length ? upcoming.map(e => `
      <div class="cal-side-item" data-cal-event="${e.id}">
        <div class="cal-side-item__bar" data-cat="${e.cat}"></div>
        <div class="cal-side-item__meta"><p>${PM.escape(e.title)}</p><small>${PM.fmtDateShort(e.start)} · ${PM.fmtTime(e.start)}</small></div>
      </div>`).join('') : '<p class="text-body-secondary small mb-0">Nothing upcoming.</p>';
    $$('[data-cat]', host).forEach(b => b.style.background = PM.CATEGORY_COLORS[b.getAttribute('data-cat')]);
  }

  /* ---------- Main render ---------- */
  function renderMain() {
    const month = $('[data-cal-month]');
    const weekEl = $('[data-cal-week]');
    const dayEl = $('[data-cal-day]');
    const mHead = $('[data-cal-monthhead]');
    month.classList.toggle('d-none', state.view !== 'month');
    weekEl.classList.toggle('d-none', state.view !== 'week');
    dayEl.classList.toggle('d-none', state.view !== 'day');
    mHead.classList.toggle('d-none', state.view !== 'month');
    if (state.view === 'month') renderMonth();
    else if (state.view === 'week') renderWeek();
    else renderDay();
    renderTodayList();
    renderUpcoming();
  }

  /* ---------- Event modal ---------- */
  function openEventModal(id, prefillDateISO) {
    const modal = bootstrap.Modal.getOrCreateInstance('#calEventModal');
    const catSel = $('[data-cal-evt-cat]');
    catSel.innerHTML = CATS.map(c => `<option value="${c}">${c}</option>`).join('');
    const delBtn = $('[data-cal-evt-delete]');
    const dupBtn = $('[data-cal-evt-duplicate]');
    if (id) {
      const e = EVENTS.find(x => x.id === id);
      if (!e) return;
      $('#calEventTitle').innerHTML = '<i class="bi bi-calendar-event me-2 text-primary"></i>' + PM.escape(e.title);
      $('[data-cal-event-id]').value = e.id;
      $('[data-cal-evt-title]').value = e.title;
      $('[data-cal-evt-date]').value = PM.dateISO(e.start);
      $('[data-cal-evt-start]').value = pad(e.start.getHours()) + ':' + pad(e.start.getMinutes());
      $('[data-cal-evt-end]').value = pad(e.end.getHours()) + ':' + pad(e.end.getMinutes());
      $('[data-cal-evt-cat]').value = e.cat;
      $('[data-cal-evt-desc]').value = e.desc || '';
      $('[data-cal-evt-attendees]').innerHTML = (e.attendees || []).map(uid => {
        const u = PM.userById(uid); return u ? PM.renderAvatar(u, 30) : '';
      }).join('') || '<span class="text-body-secondary small">No attendees</span>';
      delBtn.classList.remove('d-none');
      dupBtn.classList.remove('d-none');
    } else {
      $('#calEventTitle').innerHTML = '<i class="bi bi-calendar-plus me-2 text-primary"></i>New event';
      $('[data-cal-event-id]').value = '';
      $('[data-cal-evt-title]').value = '';
      $('[data-cal-evt-date]').value = prefillDateISO || PM.dateISO(TODAY);
      $('[data-cal-evt-start]').value = '09:00';
      $('[data-cal-evt-end]').value = '10:00';
      $('[data-cal-evt-cat]').value = 'Meeting';
      $('[data-cal-evt-desc]').value = '';
      $('[data-cal-evt-attendees]').innerHTML = '';
      delBtn.classList.add('d-none');
      dupBtn.classList.add('d-none');
    }
    modal.show();
  }
  function pad(n) { return String(n).padStart(2, '0'); }

  /* ---------- Bindings ---------- */
  function bind() {
    $('[data-cal-prev]').addEventListener('click', () => { shift(-1); });
    $('[data-cal-next]').addEventListener('click', () => { shift(1); });
    $('[data-cal-today]').addEventListener('click', () => { state.view_date = new Date(TODAY); state.mini_date = new Date(TODAY); renderMain(); renderMini(); });

    $$('input[name="calView"]').forEach(r => r.addEventListener('change', () => { state.view = r.value; renderMain(); }));

    $('[data-cal-mini-prev]').addEventListener('click', () => { state.mini_date = new Date(state.mini_date.getFullYear(), state.mini_date.getMonth() - 1, 1); renderMini(); });
    $('[data-cal-mini-next]').addEventListener('click', () => { state.mini_date = new Date(state.mini_date.getFullYear(), state.mini_date.getMonth() + 1, 1); renderMini(); });

    document.addEventListener('click', e => {
      const chip = e.target.closest('[data-cal-event]');
      if (chip) { e.stopPropagation(); openEventModal(chip.getAttribute('data-cal-event')); return; }
      const cell = e.target.closest('[data-cal-day-cell]');
      if (cell) {
        const iso = cell.getAttribute('data-cal-day-cell');
        const d = new Date(iso + 'T00:00:00');
        const evs = eventsOn(d);
        if (evs.length) openDayModal(d, evs);
        else openEventModal(null, iso);
        return;
      }
      const slot = e.target.closest('[data-cal-slot]');
      if (slot && !e.target.closest('.cal-time-event')) {
        openEventModal(null, slot.getAttribute('data-cal-slot'));
      }
      const miniDay = e.target.closest('[data-cal-mini-day]');
      if (miniDay) {
        const iso = miniDay.getAttribute('data-cal-mini-day');
        state.view_date = new Date(iso + 'T00:00:00');
        if (state.view === 'month') state.view_date = new Date(state.view_date);
        renderMain();
      }
    });

    $('[data-cal-new]').addEventListener('click', () => openEventModal(null, PM.dateISO(state.view_date)));

    $('[data-cal-event-form]').addEventListener('submit', e => {
      e.preventDefault();
      const form = e.target;
      if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
      form.classList.remove('was-validated');
      const id = $('[data-cal-event-id]').value;
      const title = $('[data-cal-evt-title]').value.trim();
      const dateISO = $('[data-cal-evt-date]').value;
      const [sh, sm] = $('[data-cal-evt-start]').value.split(':').map(Number);
      const [eh, em] = $('[data-cal-evt-end]').value.split(':').map(Number);
      const [Y, M, D] = dateISO.split('-').map(Number);
      const start = new Date(Y, M - 1, D, sh || 0, sm || 0);
      const end   = new Date(Y, M - 1, D, eh || 0, em || 0);
      const cat = $('[data-cal-evt-cat]').value;
      const desc = $('[data-cal-evt-desc]').value.trim();
      if (id) {
        const ev = EVENTS.find(x => x.id === id);
        Object.assign(ev, { title, start, end, cat, desc });
        PM.orchidToast('Event updated', 'success');
      } else {
        EVENTS.push({ id: 'e' + Math.random().toString(36).slice(2, 8), title, start, end, cat, desc, attendees: [] });
        PM.orchidToast('Event created', 'success');
      }
      bootstrap.Modal.getInstance('#calEventModal').hide();
      renderMain();
      renderMini();
    });

    $('[data-cal-evt-delete]').addEventListener('click', async () => {
      const id = $('[data-cal-event-id]').value;
      const ok = await PM.confirm({ title: 'Delete event', message: 'Delete this event?', okText: 'Delete', okVariant: 'danger' });
      if (!ok) return;
      const i = EVENTS.findIndex(x => x.id === id);
      if (i > -1) EVENTS.splice(i, 1);
      bootstrap.Modal.getInstance('#calEventModal').hide();
      renderMain(); renderMini();
      PM.orchidToast('Event deleted', 'danger');
    });
    $('[data-cal-evt-duplicate]').addEventListener('click', () => {
      const id = $('[data-cal-event-id]').value;
      const e = EVENTS.find(x => x.id === id);
      if (!e) return;
      const copy = Object.assign({}, e, { id: 'e' + Math.random().toString(36).slice(2,8), title: e.title + ' (copy)' });
      EVENTS.push(copy);
      bootstrap.Modal.getInstance('#calEventModal').hide();
      renderMain(); renderMini();
      PM.orchidToast('Event duplicated', 'success');
    });

    $('[data-cal-day-add]').addEventListener('click', () => {
      const iso = $('[data-cal-day-title]').getAttribute('data-iso');
      bootstrap.Modal.getInstance('#calDayModal').hide();
      openEventModal(null, iso);
    });
  }

  function openDayModal(d, evs) {
    $('[data-cal-day-title]').textContent = PM.fmtDate(d);
    $('[data-cal-day-title]').setAttribute('data-iso', PM.dateISO(d));
    $('[data-cal-day-list]').innerHTML = evs.map(e => `
      <div class="cal-side-item mb-2" data-cal-event="${e.id}">
        <div class="cal-side-item__bar" style="background:${PM.CATEGORY_COLORS[e.cat]}"></div>
        <div class="cal-side-item__meta">
          <p>${PM.escape(e.title)}</p>
          <small>${e.end.getTime() === e.start.getTime() ? 'All day' : PM.fmtTime(e.start) + ' – ' + PM.fmtTime(e.end)} · ${e.cat}</small>
        </div>
      </div>`).join('');
    bootstrap.Modal.getOrCreateInstance('#calDayModal').show();
  }

  function shift(dir) {
    if (state.view === 'month') {
      state.view_date = new Date(state.view_date.getFullYear(), state.view_date.getMonth() + dir, 1);
    } else if (state.view === 'week') {
      state.view_date = PM.addDays(state.view_date, dir * 7);
    } else {
      state.view_date = PM.addDays(state.view_date, dir);
    }
    renderMain();
  }

  document.addEventListener('DOMContentLoaded', () => {
    renderLegend();
    renderMini();
    renderMain();
    bind();
  });
})();
