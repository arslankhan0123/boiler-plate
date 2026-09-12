/* =====================================================
   Orchid — Communication pages JS
   Shared toolkit + per-page controllers
   ===================================================== */
(function () {
  'use strict';

  /* ---------- Utils ---------- */
  const $ = (sel, root) => (root || document).querySelector(sel);
  const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

  function esc(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function pad(n) { return n < 10 ? '0' + n : '' + n; }

  function fmtDate(d) {
    d = d instanceof Date ? d : new Date(d);
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
  }

  function fmtTime(d) {
    d = d instanceof Date ? d : new Date(d);
    let h = d.getHours();
    const m = pad(d.getMinutes());
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return h + ':' + m + ' ' + ampm;
  }

  function relativeTime(d) {
    d = d instanceof Date ? d : new Date(d);
    const now = Date.now();
    const diff = Math.round((now - d.getTime()) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 172800) return 'yesterday';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return fmtDate(d);
  }

  function dayLabel(d) {
    d = d instanceof Date ? d : new Date(d);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(today.getDate() - 1);
    if (d.toDateString() === today.toDateString()) return 'Today';
    if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
    return fmtDate(d);
  }

  function linkify(text) {
    return esc(text).replace(/(https?:\/\/[^\s]+)/g, '<a href="#" target="_blank" rel="noopener">$1</a>');
  }

  // markdown-lite: **bold**, _italic_, `code`
  function mdLite(text) {
    let s = esc(text);
    s = s.replace(/`([^`\n]+)`/g, '<code>$1</code>');
    s = s.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
    s = s.replace(/_([^_\n]+)_/g, '<em>$1</em>');
    s = s.replace(/@(\w+)/g, '<span class="chat-mention">@$1</span>');
    s = s.replace(/\n/g, '<br>');
    return s;
  }

  /* ---------- Shared users ---------- */
  const USERS = [
    { id: 'u1', name: 'Priya Menon', role: 'Product Manager', color: 1, initials: 'PM', status: 'online' },
    { id: 'u2', name: 'Marcus Chen', role: 'Engineering Lead', color: 2, initials: 'MC', status: 'online' },
    { id: 'u3', name: 'Sofia García', role: 'Sr. Designer', color: 3, initials: 'SG', status: 'away' },
    { id: 'u4', name: 'Yuki Tanaka', role: 'iOS Engineer', color: 4, initials: 'YT', status: 'online' },
    { id: 'u5', name: 'Ananya Rao', role: 'QA Engineer', color: 5, initials: 'AR', status: 'busy' },
    { id: 'u6', name: 'David Okafor', role: 'DevOps', color: 6, initials: 'DO', status: 'offline' },
    { id: 'u7', name: 'Isabella Rossi', role: 'Marketing', color: 7, initials: 'IR', status: 'online' },
    { id: 'u8', name: 'Liam O\'Connor', role: 'Support Lead', color: 8, initials: 'LO', status: 'away' },
    { id: 'me', name: 'Alex Kim', role: 'Administrator', color: 1, initials: 'AK', status: 'online' }
  ];
  const userById = (id) => USERS.find(u => u.id === id) || USERS[0];

  function renderAvatar(user, size) {
    size = size || 'md';
    const dot = user.status && size !== 'xs'
      ? '<span class="comms-avatar__dot comms-avatar__dot--' + esc(user.status) + '" aria-hidden="true"></span>'
      : '';
    return '<span class="comms-avatar comms-avatar--' + esc(size) + ' comms-avatar-color-' + esc(user.color) +
      '" aria-hidden="true">' + esc(user.initials) + dot + '</span>';
  }

  /* ---------- Toast ---------- */
  function ensureToastHost() {
    let host = $('#commsToastHost');
    if (!host) {
      host = document.createElement('div');
      host.id = 'commsToastHost';
      host.className = 'comms-toast-host';
      host.setAttribute('aria-live', 'polite');
      document.body.appendChild(host);
    }
    return host;
  }

  function orchidToast(message, type) {
    type = type || 'info';
    const host = ensureToastHost();
    const el = document.createElement('div');
    el.className = 'comms-toast comms-toast--' + type;
    const icons = { info: 'bi-info-circle-fill', success: 'bi-check-circle-fill', danger: 'bi-exclamation-triangle-fill' };
    el.innerHTML = '<i class="bi ' + (icons[type] || icons.info) + '" aria-hidden="true"></i><span>' + esc(message) + '</span>';
    host.appendChild(el);
    setTimeout(() => {
      el.style.transition = 'opacity 200ms ease, transform 200ms ease';
      el.style.opacity = '0';
      el.style.transform = 'translateY(6px)';
      setTimeout(() => el.remove(), 220);
    }, 3200);
  }

  /* Expose */
  window.OrchidComms = {
    USERS, userById, renderAvatar,
    fmtDate, fmtTime, relativeTime, dayLabel,
    linkify, mdLite, esc, orchidToast, $, $$
  };

  /* =====================================================
     MESSAGES page controller
     ===================================================== */
  function initMessages(root) {
    const listEl = $('[data-msg-list]', root);
    const threadEl = $('[data-msg-thread]', root);
    const threadHeader = $('[data-msg-thread-header]', root);
    const threadBody = $('[data-msg-thread-body]', root);
    const composerText = $('[data-msg-composer-text]', root);
    const composerSend = $('[data-msg-composer-send]', root);
    const typingEl = $('[data-msg-typing]', root);
    const searchInput = $('[data-msg-search]', root);
    const tabs = $$('[data-msg-tab]', root);
    const backBtn = $('[data-msg-back]', root);

    const conversations = [
      { id: 'c1', userId: 'u1', pinned: true, muted: false, unread: 2, last: 'Sounds good — want to jump on a quick call after standup?', time: new Date(Date.now() - 6*60000), tab: 'focused' },
      { id: 'c2', userId: 'u2', pinned: false, muted: false, unread: 0, last: 'Merged the PR, ready for QA. Nice job on the caching layer.', time: new Date(Date.now() - 42*60000), tab: 'focused' },
      { id: 'c3', userId: 'u3', pinned: true, muted: false, unread: 1, last: 'Sending over the Aurora v3 mocks in a bit.', time: new Date(Date.now() - 2*3600000), tab: 'focused' },
      { id: 'c4', userId: 'u4', pinned: false, muted: true, unread: 0, last: 'You: no worries — take your time', time: new Date(Date.now() - 5*3600000), tab: 'other' },
      { id: 'c5', userId: 'u5', pinned: false, muted: false, unread: 4, last: 'Found 3 more regressions on iOS 17 — thread inside.', time: new Date(Date.now() - 8*3600000), tab: 'focused' },
      { id: 'c6', userId: 'u6', pinned: false, muted: false, unread: 0, last: 'You: done, deployed to staging', time: new Date(Date.now() - 26*3600000), tab: 'other' },
      { id: 'c7', userId: 'u7', pinned: false, muted: false, unread: 0, last: 'Campaign copy is up for review whenever you have time', time: new Date(Date.now() - 2*86400000), tab: 'focused' },
      { id: 'c8', userId: 'u8', pinned: false, muted: true, unread: 0, last: 'Escalated TKT-4821 to the platform team', time: new Date(Date.now() - 3*86400000), tab: 'other' }
    ];

    const threads = {
      c1: [
        { by: 'u1', text: 'Hey Alex — got a minute?', t: Date.now() - 3*86400000 - 1000*3600*4 },
        { by: 'me', text: 'Sure, what\'s up?', t: Date.now() - 3*86400000 - 1000*3600*3.9 },
        { by: 'u1', text: 'The Q3 planning agenda draft is ready. Want you to sanity-check the OKR section.', t: Date.now() - 3*86400000 - 1000*3600*3.8 },
        { by: 'me', text: 'On it — where\'s the doc?', t: Date.now() - 3*86400000 - 1000*3600*3.7 },
        { by: 'u1', text: 'Sending link now.', t: Date.now() - 3*86400000 - 1000*3600*3.6 },
        { by: 'u1', text: 'Also — the exec sync moved to Thursday. FYI.', t: Date.now() - 86400000 - 3600000 },
        { by: 'me', text: 'Noted, thanks. I\'ll update my calendar.', t: Date.now() - 86400000 - 3000000 },
        { by: 'u1', text: 'By the way, marketing wants us to weigh in on the launch date before EOD.', t: Date.now() - 86400000 - 60000 },
        { by: 'me', text: 'I\'ll draft a response after lunch. My gut says we push it a week.', t: Date.now() - 86400000 + 3600000 },
        { by: 'u1', text: 'Agreed. Let\'s align on it in our 1:1.', t: Date.now() - 86400000 + 4000000 },
        { by: 'me', text: 'Sounds good. See you then.', t: Date.now() - 2*3600000 },
        { by: 'u1', text: 'Quick one — did you get the invite for the exec sync?', t: Date.now() - 1800000 },
        { by: 'me', text: 'Yep, on my calendar.', t: Date.now() - 1500000 },
        { by: 'u1', text: 'Perfect. Also, I moved the design review to 3pm your time.', t: Date.now() - 900000 },
        { by: 'u1', text: 'Sounds good — want to jump on a quick call after standup?', t: Date.now() - 6*60000 }
      ],
      c2: [
        { by: 'u2', text: 'PR #482 is up — cache invalidation refactor.', t: Date.now() - 3*3600000 },
        { by: 'me', text: 'Cool, reviewing now.', t: Date.now() - 2.9*3600000 },
        { by: 'me', text: 'Left a few comments, nothing blocking.', t: Date.now() - 2.5*3600000 },
        { by: 'u2', text: 'Addressed everything. Second look?', t: Date.now() - 90*60000 },
        { by: 'me', text: 'Looks clean. Approving.', t: Date.now() - 60*60000 },
        { by: 'u2', text: 'Merged the PR, ready for QA. Nice job on the caching layer.', t: Date.now() - 42*60000 }
      ],
      c3: [
        { by: 'u3', text: 'Aurora v3 explorations are almost ready.', t: Date.now() - 5*3600000 },
        { by: 'me', text: 'Excited! Any big changes from v2?', t: Date.now() - 4.5*3600000 },
        { by: 'u3', text: 'New color system, tighter type scale, and refreshed empty states.', t: Date.now() - 4*3600000 },
        { by: 'u3', text: 'Sending over the Aurora v3 mocks in a bit.', t: Date.now() - 2*3600000 }
      ],
      c4: [
        { by: 'u4', text: 'iOS build is red on main — investigating.', t: Date.now() - 8*3600000 },
        { by: 'me', text: 'Take your time — need any help?', t: Date.now() - 7*3600000 },
        { by: 'me', text: 'no worries — take your time', t: Date.now() - 5*3600000 }
      ],
      c5: [
        { by: 'u5', text: 'Started regression sweep on 17.5', t: Date.now() - 12*3600000 },
        { by: 'u5', text: 'Push notifications are dropping when app is backgrounded > 30 min.', t: Date.now() - 10*3600000 },
        { by: 'u5', text: 'Also — dark mode contrast issue on the onboarding CTA.', t: Date.now() - 9*3600000 },
        { by: 'u5', text: 'Found 3 more regressions on iOS 17 — thread inside.', t: Date.now() - 8*3600000 }
      ],
      c6: [
        { by: 'u6', text: 'Deploying the api-latency fix to staging.', t: Date.now() - 30*3600000 },
        { by: 'me', text: 'done, deployed to staging', t: Date.now() - 26*3600000 }
      ],
      c7: [
        { by: 'u7', text: 'Campaign copy is up for review whenever you have time', t: Date.now() - 2*86400000 }
      ],
      c8: [
        { by: 'u8', text: 'Escalated TKT-4821 to the platform team', t: Date.now() - 3*86400000 }
      ]
    };

    let currentTab = 'focused';
    let currentId = 'c1';
    let searchQ = '';

    function renderList() {
      const q = searchQ.toLowerCase();
      const items = conversations.filter(c => {
        const u = userById(c.userId);
        const inTab = currentTab === 'all' || c.tab === currentTab;
        const inSearch = !q || u.name.toLowerCase().includes(q) || c.last.toLowerCase().includes(q);
        return inTab && inSearch;
      });
      // Pinned first
      items.sort((a, b) => (b.pinned - a.pinned) || (b.time - a.time));

      if (!items.length) {
        listEl.innerHTML = '<div class="comms-empty"><i class="bi bi-inbox"></i><h6>No conversations</h6><p>Try a different search or tab.</p></div>';
        return;
      }
      listEl.innerHTML = items.map(c => {
        const u = userById(c.userId);
        const active = c.id === currentId ? ' is-active' : '';
        const unreadCls = c.unread ? ' is-unread' : '';
        const badge = c.unread ? '<span class="msg-item__badge">' + c.unread + '</span>' : '';
        const pin = c.pinned ? '<i class="bi bi-pin-angle-fill" title="Pinned"></i>' : '';
        const mute = c.muted ? '<i class="bi bi-bell-slash" title="Muted"></i>' : '';
        return '<div class="msg-item' + active + unreadCls + '" data-msg-item="' + c.id + '" role="button" tabindex="0">' +
          renderAvatar(u, 'md') +
          '<div class="msg-item__body">' +
            '<div class="msg-item__top">' +
              '<span class="msg-item__name">' + esc(u.name) + '</span>' +
              '<span class="msg-item__time">' + relativeTime(c.time) + '</span>' +
            '</div>' +
            '<p class="msg-item__snippet">' + esc(c.last) + '</p>' +
            '<div class="msg-item__meta">' + pin + mute + badge + '</div>' +
          '</div>' +
        '</div>';
      }).join('');
    }

    function renderThread() {
      const c = conversations.find(x => x.id === currentId);
      if (!c) {
        threadHeader.innerHTML = '';
        threadBody.innerHTML = '<div class="comms-empty"><i class="bi bi-chat-dots"></i><h6>Select a conversation</h6><p>Pick a message from the list to start chatting.</p></div>';
        return;
      }
      const u = userById(c.userId);
      threadHeader.innerHTML =
        renderAvatar(u, 'md') +
        '<div>' +
          '<h6 class="mb-0">' + esc(u.name) + '</h6>' +
          '<small class="text-body-secondary">' + esc(u.role) + ' · ' + esc(u.status === 'online' ? 'Online now' : u.status) + '</small>' +
        '</div>' +
        '<div class="msg-thread__actions">' +
          '<button class="btn btn-icon" type="button" aria-label="Voice call"><i class="bi bi-telephone"></i></button>' +
          '<button class="btn btn-icon" type="button" aria-label="Video call"><i class="bi bi-camera-video"></i></button>' +
          '<button class="btn btn-icon" type="button" data-bs-toggle="offcanvas" data-bs-target="#msgDetails" aria-label="Conversation details"><i class="bi bi-info-circle"></i></button>' +
        '</div>';

      const msgs = threads[c.id] || [];
      let lastDay = '';
      const html = msgs.map((m) => {
        const dLabel = dayLabel(new Date(m.t));
        let day = '';
        if (dLabel !== lastDay) {
          day = '<div class="msg-day-divider"><span>' + esc(dLabel) + '</span></div>';
          lastDay = dLabel;
        }
        const isMe = m.by === 'me';
        const user = userById(m.by);
        const check = isMe ? '<i class="bi bi-check2-all msg-check--read" aria-hidden="true"></i>' : '';
        return day +
          '<div class="msg-bubble' + (isMe ? ' msg-bubble--me' : '') + '">' +
            (isMe ? '' : renderAvatar(user, 'sm')) +
            '<div>' +
              '<div class="msg-bubble__body">' + linkify(m.text) + '</div>' +
              '<div class="msg-bubble__meta">' + fmtTime(new Date(m.t)) + ' ' + check + '</div>' +
            '</div>' +
          '</div>';
      }).join('');
      threadBody.innerHTML = html || '<div class="comms-empty"><i class="bi bi-chat"></i><h6>No messages yet</h6><p>Say hi 👋</p></div>';
      threadBody.scrollTop = threadBody.scrollHeight;

      // mark read
      c.unread = 0;
      renderList();

      // occasional typing indicator
      typingEl.style.display = 'none';
      if (Math.random() < 0.35 && c.userId !== 'me') {
        setTimeout(() => {
          typingEl.textContent = '';
          typingEl.innerHTML = '<span>' + esc(u.name.split(' ')[0]) + ' is typing</span><span class="msg-typing__dots"><span></span><span></span><span></span></span>';
          typingEl.style.display = 'flex';
          setTimeout(() => { typingEl.style.display = 'none'; }, 3500);
        }, 900);
      }
    }

    function send() {
      const text = (composerText.value || '').trim();
      if (!text) return;
      const c = conversations.find(x => x.id === currentId);
      if (!c) return;
      const now = Date.now();
      threads[c.id] = threads[c.id] || [];
      threads[c.id].push({ by: 'me', text, t: now });
      c.last = 'You: ' + text;
      c.time = new Date(now);
      composerText.value = '';
      renderThread();
      // echo bot 1s later
      setTimeout(() => {
        const echoes = ['Got it 👍', 'Thanks for the heads up!', 'Sounds good.', 'Will circle back on that.', 'Appreciate it.'];
        const reply = echoes[Math.floor(Math.random() * echoes.length)];
        threads[c.id].push({ by: c.userId, text: reply, t: Date.now() });
        c.last = reply;
        c.time = new Date();
        renderThread();
      }, 1200);
    }

    listEl.addEventListener('click', (e) => {
      const item = e.target.closest('[data-msg-item]');
      if (!item) return;
      currentId = item.dataset.msgItem;
      root.classList.add('is-thread-open');
      renderList();
      renderThread();
    });

    tabs.forEach(t => t.addEventListener('click', () => {
      tabs.forEach(x => x.classList.remove('is-active'));
      t.classList.add('is-active');
      currentTab = t.dataset.msgTab;
      renderList();
    }));

    searchInput && searchInput.addEventListener('input', (e) => {
      searchQ = e.target.value;
      renderList();
    });

    composerSend && composerSend.addEventListener('click', send);
    composerText && composerText.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
    });

    backBtn && backBtn.addEventListener('click', () => root.classList.remove('is-thread-open'));

    const newBtn = $('[data-msg-new]', root);
    newBtn && newBtn.addEventListener('click', () => orchidToast('Opening new message composer…', 'info'));

    renderList();
    renderThread();
  }

  /* =====================================================
     CHAT page controller
     ===================================================== */
  function initChat(root) {
    const messagesEl = $('[data-chat-messages]', root);
    const headerEl = $('[data-chat-header]', root);
    const composerInput = $('[data-chat-composer-input]', root);
    const composerSend = $('[data-chat-composer-send]', root);
    const mentionPicker = $('[data-chat-mention-picker]', root);
    const detailsBody = $('[data-chat-details-body]', root);

    const channels = {
      general: { name: 'general', topic: 'Company-wide announcements and work-based matters', members: 42 },
      engineering: { name: 'engineering', topic: 'Backend, frontend, mobile — all things code', members: 18 },
      design: { name: 'design', topic: 'Design crits, systems, and inspiration', members: 9 },
      random: { name: 'random', topic: 'Non-work chatter, memes, and pets', members: 38 },
      incidents: { name: 'incidents', topic: '🚨 Production incidents and post-mortems', members: 12 },
      product: { name: 'product', topic: 'Roadmap, discovery, and specs', members: 15 }
    };

    const channelMessages = {
      general: [
        { by: 'u2', text: 'Morning team ☕ heads up: staging is being upgraded 10am–11am UTC.', t: Date.now() - 4.5*3600000, reactions: [{e:'☕', c:3, mine:false}, {e:'👍', c:6, mine:true}] },
        { by: 'u1', text: 'Reminder: Q3 planning agenda is due EOW. Please add your team\'s OKRs to the doc.', t: Date.now() - 4*3600000, reactions: [{e:'📌', c:4, mine:false}] },
        { by: 'u3', text: 'Aurora v3 explorations dropping later today — I\'d love async feedback in #design.', t: Date.now() - 3.5*3600000, reactions: [{e:'🎨', c:5, mine:true}, {e:'👀', c:2, mine:false}], thread: 3 },
        { by: 'u4', text: 'iOS 17.5 build passing on main again. Root cause was a stale certificate — fix in `MobileConfig.plist`.', t: Date.now() - 3*3600000, reactions: [{e:'🎉', c:9, mine:false}] },
        { by: 'u7', text: 'New landing page copy is live for internal review: **launch/aurora**. Feedback welcome!', t: Date.now() - 2.5*3600000, reactions: [{e:'🙌', c:4, mine:false}] },
        { by: 'u2', text: 'Quick reminder: pls tag PRs with `needs-review` so the bot pings the right folks. cc @priya', t: Date.now() - 2*3600000, reactions: [] },
        { by: 'u5', text: 'Filed 3 new bugs from the regression sweep. Priority triage at 4pm today.', t: Date.now() - 105*60000, reactions: [{e:'🐛', c:3, mine:false}], thread: 5 },
        { by: 'u6', text: 'API latency is back to baseline after the connection-pool tweak. Grafana looking clean.', t: Date.now() - 85*60000, reactions: [{e:'📉', c:6, mine:true}] },
        { by: 'u8', text: 'Support queue is caught up 🎉 Big shoutout to @isabella for the weekend coverage.', t: Date.now() - 65*60000, reactions: [{e:'🎉', c:8, mine:false}, {e:'💪', c:4, mine:true}] },
        { by: 'me', text: 'Thanks everyone for the strong week — see the roll-up in _Q3-planning-agenda.doc_.', t: Date.now() - 55*60000, reactions: [{e:'❤️', c:12, mine:false}] },
        { by: 'u1', text: 'Product review is moved to 3pm PT tomorrow — calendar invites updated.', t: Date.now() - 40*60000, reactions: [{e:'📅', c:3, mine:false}] },
        { by: 'u3', text: 'Design crit in 10 min in #design — come with strong opinions and warm hearts 💛', t: Date.now() - 30*60000, reactions: [{e:'💛', c:5, mine:true}] },
        { by: 'u2', text: 'Deploy window opens in `1h`. Freeze all merges to main until then please.', t: Date.now() - 22*60000, reactions: [] },
        { by: 'u4', text: 'One more thing — the notifications module is now feature-flagged as `notifs_v2`. Toggle via the admin panel.', t: Date.now() - 15*60000, reactions: [{e:'🚩', c:2, mine:false}] },
        { by: 'u5', text: 'Reproducing that dark-mode contrast bug — will drop details in the ticket.', t: Date.now() - 8*60000, reactions: [] },
        { by: 'u7', text: 'Sending the launch checklist for a sanity pass — @alex can you take a look when you have a sec?', t: Date.now() - 4*60000, reactions: [] },
        { by: 'me', text: 'On it 👀', t: Date.now() - 2*60000, reactions: [{e:'👀', c:1, mine:false}] },
        { by: 'u6', text: 'Just merged the CI cache speedup — build times down ~28%.', t: Date.now() - 60000, reactions: [{e:'🚀', c:7, mine:true}] },
        { by: 'u1', text: 'That\'s huge, thanks @david 🙏', t: Date.now() - 45000, reactions: [] },
        { by: 'u2', text: 'PSA: standup notes template updated in the wiki — please use it starting Monday.', t: Date.now() - 30000, reactions: [{e:'📝', c:3, mine:false}] },
        { by: 'u3', text: 'Quick sketch of the empty-state I\'m proposing — thoughts?', t: Date.now() - 20000, reactions: [{e:'🔥', c:4, mine:true}], thread: 2 },
        { by: 'u8', text: 'Escalating one from the queue — customer waiting on our reply for `4h+`.', t: Date.now() - 15000, reactions: [] },
        { by: 'u5', text: 'Grabbing it now.', t: Date.now() - 8000, reactions: [] },
        { by: 'me', text: 'Great teamwork today folks 🎉', t: Date.now() - 3000, reactions: [{e:'❤️', c:9, mine:false}, {e:'🎉', c:5, mine:false}] },
        { by: 'u4', text: 'wrapping up for the day — cheers all 👋', t: Date.now() - 1000, reactions: [{e:'👋', c:4, mine:false}] }
      ],
      engineering: [
        { by: 'u2', text: 'PR queue: `4` waiting for review. Please prioritize @yuki\'s auth refactor.', t: Date.now() - 3*3600000, reactions: [{e:'👀', c:2, mine:false}] },
        { by: 'u4', text: 'Auth refactor is broken up into 3 small PRs to make review easier.', t: Date.now() - 2*3600000, reactions: [{e:'🙏', c:3, mine:true}] }
      ],
      design: [
        { by: 'u3', text: 'Dropped Aurora v3 explorations — please add comments directly in Figma.', t: Date.now() - 3600000, reactions: [{e:'🎨', c:4, mine:false}] }
      ],
      random: [
        { by: 'u7', text: 'Coffee recommendations for the Berlin office? asking for a friend ☕', t: Date.now() - 2*3600000, reactions: [{e:'☕', c:6, mine:false}] }
      ],
      incidents: [
        { by: 'u6', text: '🚨 Elevated 5xx on payments-api — investigating. Runbook link in thread.', t: Date.now() - 6*3600000, reactions: [{e:'🚨', c:3, mine:false}] }
      ],
      product: [
        { by: 'u1', text: 'Q3 roadmap draft is up for comments — deadline Friday.', t: Date.now() - 5*3600000, reactions: [{e:'📋', c:2, mine:false}] }
      ]
    };

    const dmList = [
      { userId: 'u1', unread: 2 },
      { userId: 'u2', unread: 0 },
      { userId: 'u3', unread: 1 },
      { userId: 'u5', unread: 0 },
      { userId: 'u7', unread: 0 }
    ];

    let currentChannel = 'general';

    function userColorClass(id) {
      const map = { u1:'u1', u2:'u2', u3:'u3', u4:'u4', u5:'u5', u6:'u6', u7:'u7', u8:'u1', me:'u2' };
      return 'chat-msg__name--' + (map[id] || 'u1');
    }

    function renderHeader() {
      const c = channels[currentChannel];
      headerEl.innerHTML =
        '<div>' +
          '<h6 class="chat-header__name"><i class="bi bi-hash"></i>' + esc(c.name) + '</h6>' +
          '<p class="chat-header__topic">' + esc(c.topic) + '</p>' +
        '</div>' +
        '<div class="chat-header__actions">' +
          '<span class="badge bg-primary-subtle text-primary"><i class="bi bi-people"></i> ' + c.members + '</span>' +
          '<button class="btn btn-icon" type="button" aria-label="Pinned"><i class="bi bi-pin-angle"></i></button>' +
          '<button class="btn btn-icon" type="button" aria-label="Search"><i class="bi bi-search"></i></button>' +
          '<button class="btn btn-icon" type="button" aria-label="Details"><i class="bi bi-info-circle"></i></button>' +
        '</div>';
    }

    function renderMessages() {
      const msgs = channelMessages[currentChannel] || [];
      let lastDay = '';
      const html = msgs.map((m, idx) => {
        const dLabel = dayLabel(new Date(m.t));
        let day = '';
        if (dLabel !== lastDay) {
          day = '<div class="msg-day-divider"><span>' + esc(dLabel) + '</span></div>';
          lastDay = dLabel;
        }
        const u = userById(m.by);
        const reactions = (m.reactions || []).map((r, i) =>
          '<button class="chat-reaction' + (r.mine ? ' is-mine' : '') + '" data-chat-react="' + idx + ':' + i + '" type="button">' +
            '<span>' + esc(r.e) + '</span><span class="chat-reaction__count">' + r.c + '</span>' +
          '</button>'
        ).join('');
        const addReaction = '<button class="chat-reaction chat-reaction--add" data-chat-react-add="' + idx + '" type="button" title="Add reaction"><i class="bi bi-emoji-smile"></i></button>';
        const thread = m.thread ? '<button class="chat-thread-hint" type="button" data-chat-thread="' + idx + '"><i class="bi bi-chat-left"></i> ' + m.thread + ' replies</button>' : '';
        return day +
          '<div class="chat-msg" data-chat-msg="' + idx + '">' +
            renderAvatar(u, 'sm') +
            '<div class="chat-msg__body">' +
              '<div class="chat-msg__head">' +
                '<span class="chat-msg__name ' + userColorClass(m.by) + '">' + esc(u.name) + '</span>' +
                '<span class="chat-msg__time">' + fmtTime(new Date(m.t)) + '</span>' +
              '</div>' +
              '<div class="chat-msg__text">' + mdLite(m.text) + '</div>' +
              (reactions || m.reactions ? '<div class="chat-reactions">' + reactions + addReaction + '</div>' : '') +
              thread +
            '</div>' +
            '<div class="chat-msg__hover" role="toolbar" aria-label="Message actions">' +
              '<button type="button" data-chat-action="react" title="React"><i class="bi bi-emoji-smile"></i></button>' +
              '<button type="button" data-chat-action="thread" title="Reply in thread"><i class="bi bi-chat-left"></i></button>' +
              '<button type="button" data-chat-action="save" title="Save"><i class="bi bi-bookmark"></i></button>' +
              '<button type="button" data-chat-action="more" title="More"><i class="bi bi-three-dots"></i></button>' +
            '</div>' +
          '</div>';
      }).join('');
      messagesEl.innerHTML = html;
      messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function renderDetails() {
      const memberIds = ['u1','u2','u3','u4','u5','u6','u7','me'];
      detailsBody.innerHTML =
        '<div class="chat-details__section-title">Members · ' + memberIds.length + '</div>' +
        memberIds.map(id => {
          const u = userById(id);
          return '<div class="chat-member">' + renderAvatar(u, 'sm') +
            '<div><p class="chat-member__name">' + esc(u.name) + (id === 'me' ? ' (you)' : '') + '</p><p class="chat-member__role">' + esc(u.role) + '</p></div></div>';
        }).join('') +
        '<div class="chat-details__section-title mt-3">Shared files</div>' +
        '<div class="chat-member"><span class="mail-attachment__icon mail-attachment__icon--pdf comms-attach-icon-sm"><i class="bi bi-file-earmark-pdf"></i></span><div><p class="chat-member__name">Q3-planning-agenda.pdf</p><p class="chat-member__role">1.2 MB · shared by Priya</p></div></div>' +
        '<div class="chat-member"><span class="mail-attachment__icon mail-attachment__icon--img comms-attach-icon-sm"><i class="bi bi-file-earmark-image"></i></span><div><p class="chat-member__name">aurora-v3-preview.png</p><p class="chat-member__role">3.8 MB · shared by Sofia</p></div></div>';
    }

    function renderSidebar() {
      const dmContainer = $('[data-chat-dm-list]', root);
      if (dmContainer) {
        dmContainer.innerHTML = dmList.map(dm => {
          const u = userById(dm.userId);
          const badge = dm.unread ? '<span class="chat-nav-item__badge">' + dm.unread + '</span>' : '';
          return '<button class="chat-nav-item' + (dm.unread ? ' is-unread' : '') + '" type="button" data-chat-dm="' + dm.userId + '">' +
            '<span class="chat-nav-item__avatar comms-avatar-color-' + u.color + '">' + esc(u.initials) +
              (u.status === 'online' ? '<span class="chat-nav-item__dot"></span>' : '') +
            '</span>' +
            '<span>' + esc(u.name) + '</span>' + badge +
          '</button>';
        }).join('');
      }
    }

    // Section collapse
    $$('.chat-nav-section__header', root).forEach(h => {
      h.addEventListener('click', () => h.parentElement.classList.toggle('is-collapsed'));
    });

    // Channel click
    root.addEventListener('click', (e) => {
      const link = e.target.closest('[data-chat-channel]');
      if (link) {
        currentChannel = link.dataset.chatChannel;
        $$('[data-chat-channel]', root).forEach(a => a.classList.remove('is-active'));
        link.classList.add('is-active');
        renderHeader(); renderMessages();
        return;
      }
      const dm = e.target.closest('[data-chat-dm]');
      if (dm) {
        orchidToast('Opening DM with ' + userById(dm.dataset.chatDm).name.split(' ')[0] + '…', 'info');
        return;
      }
      const react = e.target.closest('[data-chat-react]');
      if (react) {
        const [midx, ridx] = react.dataset.chatReact.split(':').map(Number);
        const r = (channelMessages[currentChannel][midx].reactions || [])[ridx];
        if (r) { r.mine = !r.mine; r.c += r.mine ? 1 : -1; if (r.c <= 0) channelMessages[currentChannel][midx].reactions.splice(ridx, 1); }
        renderMessages();
        return;
      }
      const addReact = e.target.closest('[data-chat-react-add]');
      if (addReact) {
        const midx = Number(addReact.dataset.chatReactAdd);
        channelMessages[currentChannel][midx].reactions = channelMessages[currentChannel][midx].reactions || [];
        channelMessages[currentChannel][midx].reactions.push({e:'👍', c:1, mine:true});
        renderMessages();
        return;
      }
      const thread = e.target.closest('[data-chat-thread]');
      if (thread) { orchidToast('Opening thread…', 'info'); return; }
      const hoverAction = e.target.closest('[data-chat-action]');
      if (hoverAction) {
        const map = { react:'Reaction picker', thread:'Reply in thread', save:'Message saved', more:'More actions' };
        orchidToast(map[hoverAction.dataset.chatAction], hoverAction.dataset.chatAction === 'save' ? 'success' : 'info');
      }
    });

    // Composer send
    function sendMsg() {
      const text = (composerInput.textContent || composerInput.value || '').trim();
      if (!text) return;
      channelMessages[currentChannel].push({ by: 'me', text, t: Date.now(), reactions: [] });
      if (composerInput.tagName === 'TEXTAREA') composerInput.value = ''; else composerInput.textContent = '';
      renderMessages();
    }
    composerSend && composerSend.addEventListener('click', sendMsg);
    composerInput && composerInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMsg(); }
    });

    // Mention picker
    composerInput && composerInput.addEventListener('input', (e) => {
      const text = composerInput.value != null ? composerInput.value : composerInput.textContent;
      const last = text.split(/\s/).pop();
      if (last && last.startsWith('@') && last.length < 12) {
        const q = last.slice(1).toLowerCase();
        const results = USERS.filter(u => u.id !== 'me' && u.name.toLowerCase().includes(q)).slice(0, 5);
        if (results.length) {
          mentionPicker.innerHTML = results.map(u =>
            '<div class="chat-mention-picker__item" data-chat-mention="' + u.name.split(' ')[0].toLowerCase() + '">' +
              renderAvatar(u, 'xs') + '<span>' + esc(u.name) + '</span>' +
            '</div>').join('');
          mentionPicker.classList.add('is-open');
          return;
        }
      }
      mentionPicker.classList.remove('is-open');
    });
    mentionPicker && mentionPicker.addEventListener('click', (e) => {
      const item = e.target.closest('[data-chat-mention]');
      if (!item) return;
      const name = item.dataset.chatMention;
      const currentText = composerInput.value != null ? composerInput.value : composerInput.textContent;
      const newText = currentText.replace(/@\S*$/, '@' + name + ' ');
      if (composerInput.value != null) composerInput.value = newText; else composerInput.textContent = newText;
      mentionPicker.classList.remove('is-open');
      composerInput.focus();
    });

    renderHeader();
    renderMessages();
    renderDetails();
    renderSidebar();
  }

  /* =====================================================
     EMAIL page controller
     ===================================================== */
  function initEmail(root) {
    const listEl = $('[data-mail-list]', root);
    const previewEl = $('[data-mail-preview]', root);

    const emails = [
      { id:'m1', from:'Priya Menon', email:'priya@orchid.io', avatar:1, subject:'Q3 planning agenda — draft ready', snippet:'Hey — the Q3 planning agenda draft is up. I\'d love your eyes on the OKR section before Friday…', body:'<p>Hey Alex,</p><p>The Q3 planning agenda draft is up. I\'d love your eyes on the OKR section before Friday so we can circulate it to the exec team early next week.</p><p>Key questions I\'m hoping to answer:</p><p>1. Do the top-line goals feel ambitious but achievable?<br>2. Are the cross-functional dependencies flagged clearly?<br>3. Any glaring gaps in the roadmap?</p><p>Feel free to leave inline comments or ping me on Slack.</p><p>Thanks,<br>Priya</p>', time: new Date(Date.now() - 15*60000), labels:['work'], starred:true, unread:true, folder:'inbox', hasAttach:true },
      { id:'m2', from:'Marcus Chen', email:'marcus@orchid.io', avatar:2, subject:'Can you review PR #482?', snippet:'Cache invalidation refactor is up. Should be a straightforward review — main thing to sanity-check is…', body:'<p>Hi Alex,</p><p>Cache invalidation refactor is up as PR #482. Should be a straightforward review — main thing to sanity-check is the new eviction policy under high load.</p><p>Benchmarks are in the PR description.</p><p>—M</p>', time: new Date(Date.now() - 42*60000), labels:['work'], starred:false, unread:true, folder:'inbox' },
      { id:'m3', from:'Sofia García', email:'sofia@orchid.io', avatar:3, subject:'Client feedback on Aurora designs', snippet:'Just wrapped a 60-min review with the client. Overall very positive — a few asks on the empty states…', body:'<p>Hi team,</p><p>Just wrapped a 60-min review with the client on the Aurora v3 designs. Overall very positive — a few asks on the empty states and iconography that I\'ll roll into v3.1.</p><p>Notes are attached.</p><p>Best,<br>Sofia</p>', time: new Date(Date.now() - 2*3600000), labels:['clients','work'], starred:true, unread:false, folder:'inbox', hasAttach:true },
      { id:'m4', from:'DevOps Bot', email:'noreply@orchid.io', avatar:6, subject:'Server alert: API latency spike', snippet:'p99 latency crossed 500ms threshold at 14:22 UTC on payments-api. Auto-mitigation triggered…', body:'<p><strong>Alert:</strong> payments-api p99 latency crossed 500ms threshold at 14:22 UTC.</p><p>Auto-mitigation triggered. Latency returned to baseline at 14:31 UTC.</p><p>See runbook: <a href="#">payments-api-latency</a></p>', time: new Date(Date.now() - 4*3600000), labels:['work'], starred:false, unread:true, folder:'inbox' },
      { id:'m5', from:'Stripe', email:'invoices@stripe.com', avatar:5, subject:'Your December invoice is ready', snippet:'Your invoice for December 2026 is available. Total: $2,840.00.', body:'<p>Hi Alex,</p><p>Your Stripe invoice for December 2026 is available.</p><p>Total: $2,840.00<br>Due: Jan 31, 2027</p><p>Thanks for using Stripe!</p>', time: new Date(Date.now() - 6*3600000), labels:['finance'], starred:false, unread:false, folder:'inbox', hasAttach:true },
      { id:'m6', from:'Ananya Rao', email:'ananya@orchid.io', avatar:5, subject:'iOS 17.5 regression report', snippet:'Full sweep is done. 3 new regressions filed, 1 blocker — details inside.', body:'<p>Hi Alex,</p><p>Full iOS 17.5 regression sweep is complete.</p><p>3 new regressions filed, 1 blocker (push notifications on cold-boot).</p><p>Details in the linked doc.</p><p>—Ananya</p>', time: new Date(Date.now() - 8*3600000), labels:['work'], starred:false, unread:false, folder:'inbox' },
      { id:'m7', from:'Isabella Rossi', email:'isabella@orchid.io', avatar:7, subject:'Launch checklist — sanity pass?', snippet:'Can you take 15 min today to skim the launch checklist? I want a second set of eyes before we go…', body:'<p>Hey Alex,</p><p>Can you take 15 min today to skim the launch checklist? I want a second set of eyes before we go external.</p><p>Doc is linked in the calendar invite.</p><p>Thanks!<br>Iz</p>', time: new Date(Date.now() - 10*3600000), labels:['work'], starred:true, unread:false, folder:'inbox' },
      { id:'m8', from:'The Verge', email:'newsletter@theverge.com', avatar:8, subject:'This week in tech — 5 stories you missed', snippet:'From AI regulation to the return of foldables — here are the biggest stories from the past week.', body:'<p>Your weekly tech digest.</p>', time: new Date(Date.now() - 22*3600000), labels:['newsletters'], starred:false, unread:false, folder:'inbox' },
      { id:'m9', from:'David Okafor', email:'david@orchid.io', avatar:6, subject:'CI cache speedup merged 🚀', snippet:'Build times down ~28% across the fleet. Thanks to everyone who reviewed.', body:'<p>Hi team,</p><p>CI cache speedup is merged. Build times down ~28% across the fleet.</p><p>Thanks to everyone who reviewed the PR.</p><p>—D</p>', time: new Date(Date.now() - 26*3600000), labels:['work'], starred:false, unread:false, folder:'inbox' },
      { id:'m10', from:'Yuki Tanaka', email:'yuki@orchid.io', avatar:4, subject:'Auth refactor — broken into 3 PRs', snippet:'Split the auth refactor into 3 smaller PRs to make review easier. First one is up.', body:'<p>Hi Alex,</p><p>Split the auth refactor into 3 smaller PRs to make review easier. First one is up.</p><p>—Yuki</p>', time: new Date(Date.now() - 2*86400000), labels:['work'], starred:false, unread:false, folder:'inbox' },
      { id:'m11', from:'Liam O\'Connor', email:'liam@orchid.io', avatar:8, subject:'Weekly support digest', snippet:'42 tickets closed this week, 3 escalations, SLA compliance at 96%.', body:'<p>Weekly digest attached.</p>', time: new Date(Date.now() - 3*86400000), labels:['work'], starred:false, unread:false, folder:'inbox' },
      { id:'m12', from:'Notion', email:'team@notion.so', avatar:5, subject:'Your workspace was updated', snippet:'3 pages were updated in your Product workspace today.', body:'<p>Notion update.</p>', time: new Date(Date.now() - 3*86400000), labels:['newsletters'], starred:false, unread:false, folder:'inbox' },
      { id:'m13', from:'Mom ❤️', email:'mom@example.com', avatar:3, subject:'Sunday dinner?', snippet:'Just checking — are you coming over for dinner Sunday? Making your favorite.', body:'<p>Hi honey! Are you coming over for dinner Sunday? Making your favorite.</p><p>Love,<br>Mom</p>', time: new Date(Date.now() - 4*86400000), labels:['personal'], starred:true, unread:false, folder:'inbox' },
      { id:'m14', from:'GitHub', email:'noreply@github.com', avatar:2, subject:'Security alert for orchid-web', snippet:'A vulnerability was found in a dependency of your repository.', body:'<p>Dependabot alert.</p>', time: new Date(Date.now() - 5*86400000), labels:['work'], starred:false, unread:false, folder:'inbox' },
      { id:'m15', from:'Acme Corp', email:'ap@acme.com', avatar:4, subject:'Purchase order #PO-2934', snippet:'Please find attached PO #PO-2934 for approval.', body:'<p>PO attached.</p>', time: new Date(Date.now() - 6*86400000), labels:['clients','finance'], starred:false, unread:false, folder:'inbox', hasAttach:true }
    ];

    let currentFolder = 'inbox';
    let currentId = 'm1';

    function renderFolders() {
      $$('.mail-folder', root).forEach(f => f.classList.toggle('is-active', f.dataset.mailFolder === currentFolder));
    }

    function renderList() {
      const items = emails.filter(e => e.folder === currentFolder);
      if (!items.length) {
        listEl.innerHTML = '<div class="comms-empty"><i class="bi bi-inbox"></i><h6>Nothing here</h6><p>This folder is empty.</p></div>';
        return;
      }
      listEl.innerHTML = items.map(e => {
        const labels = (e.labels || []).map(l => '<span class="mail-row__label mail-row__label--' + esc(l) + '">' + esc(l) + '</span>').join('');
        return '<div class="mail-row' + (e.id === currentId ? ' is-active' : '') + (e.unread ? ' is-unread' : '') + '" data-mail-row="' + e.id + '">' +
          '<div><input class="form-check-input" type="checkbox" aria-label="Select"></div>' +
          '<button class="mail-row__star' + (e.starred ? ' is-starred' : '') + '" data-mail-star="' + e.id + '" aria-label="Star" type="button"><i class="bi bi-star' + (e.starred ? '-fill' : '') + '"></i></button>' +
          renderAvatar({initials: e.from.split(' ').map(x=>x[0]).join('').slice(0,2), color: e.avatar}, 'sm') +
          '<div class="mail-row__main">' +
            '<p class="mail-row__from">' + esc(e.from) + '</p>' +
            '<p class="mail-row__subject">' + esc(e.subject) + ' <small>— ' + esc(e.snippet) + '</small></p>' +
            (labels ? '<div class="mail-row__labels">' + labels + '</div>' : '') +
          '</div>' +
          '<div class="mail-row__meta">' +
            (e.hasAttach ? '<i class="bi bi-paperclip text-body-secondary"></i>' : '') +
            '<span class="mail-row__time">' + relativeTime(e.time) + '</span>' +
          '</div>' +
        '</div>';
      }).join('');
    }

    function renderPreview() {
      const e = emails.find(x => x.id === currentId);
      if (!e) { previewEl.innerHTML = '<div class="comms-empty"><i class="bi bi-envelope-open"></i><h6>No email selected</h6><p>Pick a message from the list to view it here.</p></div>'; return; }
      const attachments = e.hasAttach ? '<div class="mail-attachment-grid">' +
        '<div class="mail-attachment"><span class="mail-attachment__icon mail-attachment__icon--pdf"><i class="bi bi-file-earmark-pdf"></i></span><div><p class="mail-attachment__name">' + esc(e.subject.slice(0,22)) + '.pdf</p><p class="mail-attachment__size">1.4 MB</p></div><button class="btn btn-sm btn-icon ms-auto" aria-label="Download"><i class="bi bi-download"></i></button></div>' +
        '<div class="mail-attachment"><span class="mail-attachment__icon mail-attachment__icon--doc"><i class="bi bi-file-earmark-word"></i></span><div><p class="mail-attachment__name">notes.docx</p><p class="mail-attachment__size">312 KB</p></div><button class="btn btn-sm btn-icon ms-auto" aria-label="Download"><i class="bi bi-download"></i></button></div>' +
        '</div>' : '';
      const thread = ['m1','m3'].includes(e.id) ? '<h6 class="mt-4 mb-2">Related in this thread</h6>' +
        '<div class="mail-thread-item"><div class="mail-thread-item__head">' + renderAvatar({initials:'AK',color:1}, 'xs') + '<p>You</p><small>' + fmtDate(new Date(Date.now() - 86400000)) + '</small></div><div class="mail-thread-item__body">Thanks Priya — I\'ll review the OKR section tonight and drop comments.</div></div>' +
        '<div class="mail-thread-item"><div class="mail-thread-item__head">' + renderAvatar({initials:'PM',color:1}, 'xs') + '<p>Priya Menon</p><small>' + fmtDate(new Date(Date.now() - 172800000)) + '</small></div><div class="mail-thread-item__body">Great — no rush. Just before Friday would be perfect.</div></div>' +
        '<div class="mail-thread-item"><div class="mail-thread-item__head">' + renderAvatar({initials:'AK',color:1}, 'xs') + '<p>You</p><small>' + fmtDate(new Date(Date.now() - 259200000)) + '</small></div><div class="mail-thread-item__body">Received, thanks!</div></div>' : '';

      previewEl.innerHTML =
        '<div class="mail-preview__header">' +
          '<h4 class="mail-preview__subject">' + esc(e.subject) + '</h4>' +
          '<div class="mail-preview__actions">' +
            '<button class="btn btn-sm btn-outline-secondary" data-mail-reply><i class="bi bi-reply me-1"></i>Reply</button>' +
            '<button class="btn btn-sm btn-outline-secondary"><i class="bi bi-reply-all me-1"></i>Reply all</button>' +
            '<button class="btn btn-sm btn-outline-secondary"><i class="bi bi-forward me-1"></i>Forward</button>' +
            '<button class="btn btn-sm btn-icon" aria-label="Star"><i class="bi bi-star"></i></button>' +
            '<button class="btn btn-sm btn-icon" aria-label="Archive"><i class="bi bi-archive"></i></button>' +
            '<button class="btn btn-sm btn-icon" aria-label="Delete" data-mail-delete><i class="bi bi-trash"></i></button>' +
            '<button class="btn btn-sm btn-icon" aria-label="Print"><i class="bi bi-printer"></i></button>' +
            '<button class="btn btn-sm btn-icon" aria-label="More"><i class="bi bi-three-dots-vertical"></i></button>' +
          '</div>' +
          '<div class="mail-preview__from">' +
            renderAvatar({initials: e.from.split(' ').map(x=>x[0]).join('').slice(0,2), color: e.avatar}, 'md') +
            '<div>' +
              '<p class="mail-preview__from-name">' + esc(e.from) + ' &lt;' + esc(e.email) + '&gt;</p>' +
              '<p class="mail-preview__from-email">to me · ' + fmtDate(e.time) + ' at ' + fmtTime(e.time) + '</p>' +
            '</div>' +
          '</div>' +
        '</div>' +
        '<div class="mail-preview__body comms-scroll">' +
          e.body + attachments + thread +
          '<p class="text-body-secondary mt-4"><small>—<br>' + esc(e.from) + '<br>' + esc(e.email) + '</small></p>' +
        '</div>' +
        '<div class="mail-reply" data-mail-reply-wrap>' +
          '<button class="mail-reply__toggle" type="button" data-mail-reply><i class="bi bi-reply me-2"></i>Click here to Reply</button>' +
          '<div class="mail-reply__form">' +
            '<textarea placeholder="Write your reply…"></textarea>' +
            '<div class="mail-reply__form-actions">' +
              '<div class="d-flex gap-1"><button class="btn btn-sm btn-icon" aria-label="Attach"><i class="bi bi-paperclip"></i></button><button class="btn btn-sm btn-icon" aria-label="Emoji"><i class="bi bi-emoji-smile"></i></button></div>' +
              '<div><button class="btn btn-sm btn-outline-secondary" data-mail-cancel-reply>Cancel</button> <button class="btn btn-sm btn-primary" data-mail-send-reply>Send</button></div>' +
            '</div>' +
          '</div>' +
        '</div>';
      e.unread = false;
      renderList();
    }

    root.addEventListener('click', (e) => {
      const folder = e.target.closest('[data-mail-folder]');
      if (folder) { currentFolder = folder.dataset.mailFolder; renderFolders(); renderList(); return; }
      const row = e.target.closest('[data-mail-row]');
      if (row && !e.target.closest('[data-mail-star]') && !e.target.closest('.form-check-input')) {
        currentId = row.dataset.mailRow;
        root.classList.add('is-mail-open');
        renderList(); renderPreview();
        return;
      }
      const star = e.target.closest('[data-mail-star]');
      if (star) {
        const em = emails.find(x => x.id === star.dataset.mailStar);
        if (em) { em.starred = !em.starred; renderList(); orchidToast(em.starred ? 'Starred' : 'Removed star', 'info'); }
        return;
      }
      if (e.target.closest('[data-mail-back]')) { root.classList.remove('is-mail-open'); return; }
      if (e.target.closest('[data-mail-reply]')) {
        const wrap = $('[data-mail-reply-wrap]', root);
        wrap && wrap.classList.add('is-open');
        return;
      }
      if (e.target.closest('[data-mail-cancel-reply]')) {
        const wrap = $('[data-mail-reply-wrap]', root);
        wrap && wrap.classList.remove('is-open');
        return;
      }
      if (e.target.closest('[data-mail-send-reply]')) {
        orchidToast('Reply sent', 'success');
        const wrap = $('[data-mail-reply-wrap]', root);
        wrap && wrap.classList.remove('is-open');
        const ta = $('textarea', wrap);
        if (ta) ta.value = '';
        return;
      }
      if (e.target.closest('[data-mail-delete]')) {
        if (confirm('Move this email to trash?')) {
          const idx = emails.findIndex(x => x.id === currentId);
          if (idx > -1) { emails[idx].folder = 'trash'; currentId = emails.find(x => x.folder === currentFolder)?.id || ''; renderList(); renderPreview(); orchidToast('Moved to trash', 'success'); }
        }
      }
      if (e.target.closest('[data-mail-compose]')) {
        const modalEl = $('#mailCompose');
        if (modalEl && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modalEl).show();
      }
    });

    renderFolders();
    renderList();
    renderPreview();
  }

  /* =====================================================
     NOTIFICATIONS page controller
     ===================================================== */
  function initNotifications(root) {
    const feedEl = $('[data-notif-feed]', root);
    const chipEls = $$('[data-notif-chip]', root);
    let currentFilter = 'all';

    const notifs = [
      { id:'n1', type:'mention', actor:'Priya Menon', actorId:'u1', title:'mentioned you in <a href="#">#general</a>', snippet:'"@alex can you take a look at the launch checklist when you have a sec?"', time: new Date(Date.now() - 4*60000), unread:true, action:'Reply' },
      { id:'n2', type:'comment', actor:'Sofia García', actorId:'u3', title:'commented on <a href="#">Aurora v3 designs</a>', snippet:'Love the new empty state — could we try a slightly warmer tone?', time: new Date(Date.now() - 20*60000), unread:true, action:'View' },
      { id:'n3', type:'security', actor:'Security', actorId:'u2', title:'New sign-in from Chrome on macOS', snippet:'Location: San Francisco, CA · IP 172.58.x.x', time: new Date(Date.now() - 60*60000), unread:true, action:'Review' },
      { id:'n4', type:'deal', actor:'System', actorId:'u2', title:'Deal moved to <strong>Won</strong>', snippet:'Acme Corp — $48,000 ARR closed by Isabella Rossi', time: new Date(Date.now() - 90*60000), unread:false, action:'Open' },
      { id:'n5', type:'task', actor:'Marcus Chen', actorId:'u2', title:'assigned you a task', snippet:'"Review PR #482 — cache invalidation refactor" · due today', time: new Date(Date.now() - 2*3600000), unread:true, action:'Open' },
      { id:'n6', type:'system', actor:'Orchid', actorId:'u6', title:'Weekly report is ready', snippet:'Your team\'s performance summary for this week is available.', time: new Date(Date.now() - 3*3600000), unread:false, action:'View' },
      { id:'n7', type:'team', actor:'Yuki Tanaka', actorId:'u4', title:'joined <a href="#">#mobile</a>', snippet:'Welcome them to the channel 👋', time: new Date(Date.now() - 5*3600000), unread:false, action:'Say hi' },
      { id:'n8', type:'like', actor:'Ananya Rao', actorId:'u5', title:'reacted 🎉 to your message', snippet:'"Great teamwork today folks 🎉" in #general', time: new Date(Date.now() - 6*3600000), unread:false, action:'View' },
      { id:'n9', type:'follow', actor:'David Okafor', actorId:'u6', title:'started following you', snippet:'DevOps · San Francisco', time: new Date(Date.now() - 7*3600000), unread:false, action:'Follow back' },
      { id:'n10', type:'security', actor:'Security', actorId:'u2', title:'Password changed successfully', snippet:'Your account password was updated 2 hours ago.', time: new Date(Date.now() - 9*3600000), unread:false, action:'Review' },
      // Yesterday
      { id:'n11', type:'mention', actor:'Isabella Rossi', actorId:'u7', title:'mentioned you in <a href="#">launch/aurora</a>', snippet:'"@alex nice work on the messaging framework"', time: new Date(Date.now() - 26*3600000), unread:false, action:'Reply' },
      { id:'n12', type:'deal', actor:'System', actorId:'u2', title:'Deal moved to <strong>Negotiation</strong>', snippet:'Nova.io — $22,000 ARR · owner: Priya Menon', time: new Date(Date.now() - 30*3600000), unread:false, action:'Open' },
      { id:'n13', type:'comment', actor:'Liam O\'Connor', actorId:'u8', title:'replied to your comment on <a href="#">TKT-4821</a>', snippet:'"Confirmed — escalating to the platform team now."', time: new Date(Date.now() - 32*3600000), unread:false, action:'View' },
      { id:'n14', type:'task', actor:'Priya Menon', actorId:'u1', title:'completed a task you assigned', snippet:'"Draft Q3 planning agenda" — marked done', time: new Date(Date.now() - 34*3600000), unread:false, action:'View' },
      { id:'n15', type:'system', actor:'Billing', actorId:'u2', title:'Your invoice is available', snippet:'December 2026 invoice — $2,840 · due Jan 31', time: new Date(Date.now() - 36*3600000), unread:false, action:'Download' },
      // Earlier
      { id:'n16', type:'team', actor:'Sofia García', actorId:'u3', title:'shared a file in <a href="#">#design</a>', snippet:'aurora-v3-preview.png (3.8 MB)', time: new Date(Date.now() - 3*86400000), unread:false, action:'Open' },
      { id:'n17', type:'like', actor:'Marcus Chen', actorId:'u2', title:'reacted ❤️ to your message', snippet:'"Merged the PR, ready for QA"', time: new Date(Date.now() - 3*86400000 - 3600000), unread:false, action:'View' },
      { id:'n18', type:'follow', actor:'Yuki Tanaka', actorId:'u4', title:'accepted your team invite', snippet:'iOS Engineer joined the mobile squad', time: new Date(Date.now() - 4*86400000), unread:false, action:'View' },
      { id:'n19', type:'security', actor:'Security', actorId:'u2', title:'Two-factor authentication enabled', snippet:'Great job — your account is more secure now.', time: new Date(Date.now() - 5*86400000), unread:false, action:'Review' },
      { id:'n20', type:'system', actor:'Orchid', actorId:'u6', title:'Storage 78% full', snippet:'Consider upgrading your plan or clearing old assets.', time: new Date(Date.now() - 5*86400000 - 3600000), unread:false, action:'Upgrade' },
      { id:'n21', type:'deal', actor:'System', actorId:'u2', title:'Deal moved to <strong>Lost</strong>', snippet:'Peak.io — $18,000 · reason: budget', time: new Date(Date.now() - 6*86400000), unread:false, action:'Open' },
      { id:'n22', type:'comment', actor:'Ananya Rao', actorId:'u5', title:'commented on <a href="#">iOS 17.5 regressions</a>', snippet:'"Repro attached in the ticket — happy to walk through."', time: new Date(Date.now() - 6*86400000 - 3600000), unread:false, action:'View' },
      { id:'n23', type:'task', actor:'Sofia García', actorId:'u3', title:'moved your task to <strong>Review</strong>', snippet:'"Empty state illustrations"', time: new Date(Date.now() - 7*86400000), unread:false, action:'Open' },
      { id:'n24', type:'mention', actor:'David Okafor', actorId:'u6', title:'mentioned you in <a href="#">#incidents</a>', snippet:'"@alex heads up — deploy window shifts to 4pm"', time: new Date(Date.now() - 8*86400000), unread:false, action:'Reply' },
      { id:'n25', type:'system', actor:'Orchid', actorId:'u6', title:'Welcome to Orchid 🎉', snippet:'Get started by completing your profile.', time: new Date(Date.now() - 14*86400000), unread:false, action:'Get started' }
    ];

    function iconFor(type) {
      const map = { mention:'bi-at', comment:'bi-chat-left-text', like:'bi-heart-fill', follow:'bi-person-plus', system:'bi-bell', security:'bi-shield-lock', team:'bi-people', deal:'bi-cash-coin', task:'bi-check2-square' };
      return map[type] || 'bi-bell';
    }

    function passesFilter(n) {
      if (currentFilter === 'all') return true;
      if (currentFilter === 'unread') return n.unread;
      if (currentFilter === 'mentions') return n.type === 'mention';
      if (currentFilter === 'team') return n.type === 'team' || n.type === 'follow';
      if (currentFilter === 'security') return n.type === 'security';
      return true;
    }

    function renderFeed() {
      const items = notifs.filter(passesFilter);
      if (!items.length) {
        feedEl.innerHTML = '<div class="comms-empty"><i class="bi bi-check-circle"></i><h6>All caught up</h6><p>You have no notifications in this filter.</p></div>';
        return;
      }
      let lastDay = '';
      const html = items.map(n => {
        const d = dayLabel(n.time);
        let head = '';
        if (d !== lastDay) { head = '<div class="notif-day">' + esc(d) + '</div>'; lastDay = d; }
        return head +
          '<div class="notif-item' + (n.unread ? ' is-unread' : '') + '" data-notif-item="' + n.id + '">' +
            '<div class="notif-item__check"><input class="form-check-input" type="checkbox" aria-label="Select" data-notif-check="' + n.id + '"></div>' +
            '<div class="notif-icon notif-icon--' + n.type + '"><i class="bi ' + iconFor(n.type) + '"></i></div>' +
            '<div class="notif-item__body">' +
              '<p class="notif-item__title"><strong>' + esc(n.actor) + '</strong> ' + n.title + '</p>' +
              '<p class="notif-item__snippet">' + esc(n.snippet) + '</p>' +
              '<div class="notif-item__meta"><i class="bi bi-clock"></i> ' + relativeTime(n.time) + '</div>' +
            '</div>' +
            '<div class="notif-item__actions">' +
              '<button class="btn btn-sm btn-outline-primary" data-notif-action="' + n.id + '">' + esc(n.action) + '</button>' +
              '<div class="dropdown">' +
                '<button class="btn btn-sm btn-icon" data-bs-toggle="dropdown" aria-label="Snooze"><i class="bi bi-alarm"></i></button>' +
                '<ul class="dropdown-menu dropdown-menu-end">' +
                  '<li><h6 class="dropdown-header">Snooze until</h6></li>' +
                  '<li><a class="dropdown-item" href="#" data-notif-snooze="1h">In 1 hour</a></li>' +
                  '<li><a class="dropdown-item" href="#" data-notif-snooze="4h">In 4 hours</a></li>' +
                  '<li><a class="dropdown-item" href="#" data-notif-snooze="tomorrow">Tomorrow</a></li>' +
                  '<li><a class="dropdown-item" href="#" data-notif-snooze="custom">Custom…</a></li>' +
                '</ul>' +
              '</div>' +
              '<button class="btn btn-sm btn-icon" data-notif-dismiss="' + n.id + '" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>' +
            '</div>' +
          '</div>';
      }).join('');
      feedEl.innerHTML = html;
    }

    chipEls.forEach(c => c.addEventListener('click', () => {
      chipEls.forEach(x => x.classList.remove('is-active'));
      c.classList.add('is-active');
      currentFilter = c.dataset.notifChip;
      renderFeed();
    }));

    feedEl.addEventListener('click', (e) => {
      const dismiss = e.target.closest('[data-notif-dismiss]');
      if (dismiss) {
        const i = notifs.findIndex(x => x.id === dismiss.dataset.notifDismiss);
        if (i > -1) { notifs.splice(i, 1); renderFeed(); orchidToast('Notification dismissed', 'info'); }
        return;
      }
      const action = e.target.closest('[data-notif-action]');
      if (action) {
        const n = notifs.find(x => x.id === action.dataset.notifAction);
        if (n) { n.unread = false; renderFeed(); orchidToast('Marked as read', 'success'); }
        return;
      }
      const snooze = e.target.closest('[data-notif-snooze]');
      if (snooze) { orchidToast('Snoozed for ' + snooze.dataset.notifSnooze, 'info'); return; }
      const item = e.target.closest('[data-notif-item]');
      if (item && !e.target.closest('button') && !e.target.closest('input')) {
        const n = notifs.find(x => x.id === item.dataset.notifItem);
        if (n) { n.unread = false; renderFeed(); }
      }
    });

    const markAll = $('[data-notif-mark-all]', root);
    markAll && markAll.addEventListener('click', () => { notifs.forEach(n => n.unread = false); renderFeed(); orchidToast('All notifications marked as read', 'success'); });

    const bulkDelete = $('[data-notif-bulk-delete]', root);
    bulkDelete && bulkDelete.addEventListener('click', () => {
      const checked = $$('[data-notif-check]:checked', root).map(c => c.dataset.notifCheck);
      if (!checked.length) { orchidToast('Select notifications first', 'info'); return; }
      if (confirm('Delete ' + checked.length + ' selected notification(s)?')) {
        checked.forEach(id => { const i = notifs.findIndex(x => x.id === id); if (i > -1) notifs.splice(i, 1); });
        renderFeed();
        orchidToast('Deleted ' + checked.length + ' notification(s)', 'success');
      }
    });

    const bulkUnread = $('[data-notif-bulk-unread]', root);
    bulkUnread && bulkUnread.addEventListener('click', () => {
      const checked = $$('[data-notif-check]:checked', root).map(c => c.dataset.notifCheck);
      checked.forEach(id => { const n = notifs.find(x => x.id === id); if (n) n.unread = true; });
      renderFeed();
      orchidToast('Marked as unread', 'success');
    });

    renderFeed();
  }

  /* =====================================================
     TICKETS page controller
     ===================================================== */
  function initTickets(root) {
    const tbody = $('[data-ticket-tbody]', root);
    const bulkBar = $('[data-ticket-bulk]', root);
    const drawerEl = $('#ticketDrawer');
    const drawerBody = $('[data-ticket-drawer-body]');
    const searchInput = $('[data-ticket-search]', root);
    const priorityFilter = $('[data-ticket-priority]', root);
    const statusFilter = $('[data-ticket-status]', root);

    const tickets = [
      { id:'TKT-4821', subject:'API latency spike on payments endpoint — customer impact', requester:'Nova.io', requesterName:'Emma Watson', avatar:1, channel:'email', agentId:'u6', priority:'urgent', status:'progress', sla:'red', slaText:'-24m', updated: new Date(Date.now() - 20*60000), tags:['api','payments'] },
      { id:'TKT-4820', subject:'Dark mode contrast issue on onboarding CTA', requester:'Acme Corp', requesterName:'James Doe', avatar:2, channel:'chat', agentId:'u3', priority:'high', status:'open', sla:'amber', slaText:'1h 12m', updated: new Date(Date.now() - 45*60000), tags:['ui','design'] },
      { id:'TKT-4819', subject:'Cannot import CSV — encoding error', requester:'Peak.io', requesterName:'Ryan Green', avatar:3, channel:'web', agentId:'u5', priority:'medium', status:'waiting', sla:'green', slaText:'4h 32m', updated: new Date(Date.now() - 90*60000), tags:['import','csv'] },
      { id:'TKT-4818', subject:'Weekly digest email not sending on Mondays', requester:'Bold.co', requesterName:'Ava Lee', avatar:4, channel:'email', agentId:'u8', priority:'medium', status:'open', sla:'green', slaText:'6h 05m', updated: new Date(Date.now() - 2*3600000), tags:['email','automation'] },
      { id:'TKT-4817', subject:'Two-factor SMS not arriving for +49 numbers', requester:'Zen Studios', requesterName:'Sarah Miller', avatar:5, channel:'twitter', agentId:'u2', priority:'high', status:'progress', sla:'amber', slaText:'54m', updated: new Date(Date.now() - 3*3600000), tags:['auth','sms'] },
      { id:'TKT-4816', subject:'Bulk update via API returns 500 randomly', requester:'Aurora Labs', requesterName:'Sofia García', avatar:6, channel:'api', agentId:'u2', priority:'urgent', status:'progress', sla:'red', slaText:'-1h 08m', updated: new Date(Date.now() - 4*3600000), tags:['api'] },
      { id:'TKT-4815', subject:'Missing export button on invoice detail', requester:'Nimbus HQ', requesterName:'Yuki Tanaka', avatar:7, channel:'web', agentId:'u3', priority:'low', status:'open', sla:'green', slaText:'22h', updated: new Date(Date.now() - 5*3600000), tags:['ui'] },
      { id:'TKT-4814', subject:'Charge dispute — customer disputes $284', requester:'Peak.io', requesterName:'Ryan Green', avatar:3, channel:'email', agentId:'u1', priority:'high', status:'waiting', sla:'amber', slaText:'2h 10m', updated: new Date(Date.now() - 6*3600000), tags:['billing'] },
      { id:'TKT-4813', subject:'Feature request: dark mode for reports', requester:'Bold.co', requesterName:'Ava Lee', avatar:4, channel:'web', agentId:'u3', priority:'low', status:'open', sla:'green', slaText:'3d', updated: new Date(Date.now() - 8*3600000), tags:['feature'] },
      { id:'TKT-4812', subject:'Cannot delete archived workspaces', requester:'Nova.io', requesterName:'Emma Watson', avatar:1, channel:'chat', agentId:'u5', priority:'medium', status:'resolved', sla:'green', slaText:'—', updated: new Date(Date.now() - 26*3600000), tags:['workspace'] },
      { id:'TKT-4811', subject:'Webhook retries failing after 3rd attempt', requester:'Aurora Labs', requesterName:'Sofia García', avatar:6, channel:'api', agentId:'u2', priority:'high', status:'resolved', sla:'green', slaText:'—', updated: new Date(Date.now() - 30*3600000), tags:['webhook','api'] },
      { id:'TKT-4810', subject:'Reset password link expiring too fast', requester:'Zen Studios', requesterName:'Sarah Miller', avatar:5, channel:'email', agentId:'u8', priority:'medium', status:'closed', sla:'green', slaText:'—', updated: new Date(Date.now() - 2*86400000), tags:['auth'] },
      { id:'TKT-4809', subject:'Onboarding tour skips step 3 randomly', requester:'Nimbus HQ', requesterName:'Yuki Tanaka', avatar:7, channel:'web', agentId:'u3', priority:'low', status:'closed', sla:'green', slaText:'—', updated: new Date(Date.now() - 3*86400000), tags:['onboarding'] },
      { id:'TKT-4808', subject:'Attachment upload fails for files > 15MB', requester:'Bold.co', requesterName:'Ava Lee', avatar:4, channel:'chat', agentId:'u5', priority:'medium', status:'resolved', sla:'green', slaText:'—', updated: new Date(Date.now() - 4*86400000), tags:['upload'] },
      { id:'TKT-4807', subject:'Timezone incorrect in scheduled reports', requester:'Peak.io', requesterName:'Ryan Green', avatar:3, channel:'email', agentId:'u1', priority:'medium', status:'closed', sla:'green', slaText:'—', updated: new Date(Date.now() - 6*86400000), tags:['reports'] }
    ];

    let selected = new Set();
    let currentTicketId = null;

    function channelIcon(ch) {
      const map = { email:'bi-envelope', chat:'bi-chat-dots', web:'bi-globe', api:'bi-braces', twitter:'bi-twitter' };
      return map[ch] || 'bi-question-circle';
    }
    function statusPill(s) {
      const map = { open:'Open', progress:'In Progress', waiting:'Waiting', resolved:'Resolved', closed:'Closed' };
      return '<span class="ticket-status-pill ticket-status-pill--' + s + '">' + esc(map[s]) + '</span>';
    }

    function filtered() {
      const q = (searchInput?.value || '').toLowerCase();
      const pr = priorityFilter?.value || '';
      const st = statusFilter?.value || '';
      return tickets.filter(t => {
        const inQ = !q || t.subject.toLowerCase().includes(q) || t.id.toLowerCase().includes(q) || t.requester.toLowerCase().includes(q);
        const inP = !pr || t.priority === pr;
        const inS = !st || t.status === st;
        return inQ && inP && inS;
      });
    }

    function renderTable() {
      const items = filtered();
      if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="10"><div class="comms-empty"><i class="bi bi-inbox"></i><h6>No tickets match</h6><p>Try adjusting your filters.</p></div></td></tr>';
        return;
      }
      tbody.innerHTML = items.map(t => {
        const agent = userById(t.agentId);
        const req = { initials: t.requesterName.split(' ').map(x=>x[0]).join('').slice(0,2), color: t.avatar };
        return '<tr data-ticket-row="' + t.id + '"' + (selected.has(t.id) ? ' class="is-selected"' : '') + '>' +
          '<td><input class="form-check-input" type="checkbox" data-ticket-select="' + t.id + '"' + (selected.has(t.id) ? ' checked' : '') + ' aria-label="Select ticket"></td>' +
          '<td><span class="ticket-priority-strip ticket-priority-strip--' + t.priority + '" title="' + t.priority + '"></span></td>' +
          '<td><span class="ticket-id">' + esc(t.id) + '</span></td>' +
          '<td><span class="ticket-subject">' + esc(t.subject) + '</span></td>' +
          '<td><div class="d-flex align-items-center gap-2">' + renderAvatar(req, 'sm') + '<div><p class="ticket-requester-name">' + esc(t.requesterName) + '</p><small class="text-body-secondary">' + esc(t.requester) + '</small></div></div></td>' +
          '<td><i class="bi ' + channelIcon(t.channel) + ' text-body-secondary" title="' + esc(t.channel) + '"></i></td>' +
          '<td>' + renderAvatar(agent, 'sm') + '</td>' +
          '<td><span class="ticket-sla-chip ticket-sla-chip--' + t.sla + '"><i class="bi bi-clock"></i>' + esc(t.slaText) + '</span></td>' +
          '<td>' + statusPill(t.status) + '</td>' +
          '<td class="text-body-secondary ticket-updated-cell">' + relativeTime(t.updated) + '</td>' +
        '</tr>';
      }).join('');
    }

    function openDrawer(id) {
      const t = tickets.find(x => x.id === id);
      if (!t) return;
      currentTicketId = id;
      const agent = userById(t.agentId);
      const req = { initials: t.requesterName.split(' ').map(x=>x[0]).join('').slice(0,2), color: t.avatar };
      const $head = $('[data-ticket-drawer-head]');
      $head.innerHTML =
        '<h5>' + esc(t.subject) + '</h5>' +
        '<div class="ticket-drawer__head-meta">' +
          '<span class="ticket-id">' + esc(t.id) + '</span>' +
          statusPill(t.status) +
          '<span class="ticket-sla-chip ticket-sla-chip--' + t.sla + '"><i class="bi bi-clock"></i>' + esc(t.slaText) + '</span>' +
        '</div>';

      drawerBody.innerHTML =
        '<div class="ticket-conv-item">' +
          renderAvatar(req, 'sm') +
          '<div class="ticket-conv-item__body">' +
            '<div class="ticket-conv-item__head"><span class="ticket-conv-item__name">' + esc(t.requesterName) + '</span><small class="text-body-secondary">· ' + esc(t.requester) + '</small><span class="ticket-conv-item__time">' + relativeTime(new Date(t.updated.getTime() - 3600000)) + '</span></div>' +
            '<p class="ticket-conv-item__text">Hi team — we\'re seeing intermittent errors on ' + esc(t.subject.split('—')[0].trim().toLowerCase()) + '. It started around an hour ago and affects roughly 20% of our users. Can someone take a look ASAP? Happy to jump on a call if that helps.</p>' +
          '</div>' +
        '</div>' +
        '<div class="ticket-conv-item ticket-conv-item--agent">' +
          renderAvatar(agent, 'sm') +
          '<div class="ticket-conv-item__body">' +
            '<div class="ticket-conv-item__head"><span class="ticket-conv-item__name">' + esc(agent.name) + '</span><small class="text-body-secondary">· agent</small><span class="ticket-conv-item__time">' + relativeTime(new Date(t.updated.getTime() - 1800000)) + '</span></div>' +
            '<p class="ticket-conv-item__text">Hi ' + esc(t.requesterName.split(' ')[0]) + ' — thanks for the heads-up. I\'m pulling the logs now and will circle back within the hour with an update. In the meantime, would you mind sharing a request ID from one of the failed calls?</p>' +
          '</div>' +
        '</div>' +
        '<div class="ticket-conv-item">' +
          renderAvatar(req, 'sm') +
          '<div class="ticket-conv-item__body">' +
            '<div class="ticket-conv-item__head"><span class="ticket-conv-item__name">' + esc(t.requesterName) + '</span><span class="ticket-conv-item__time">' + relativeTime(t.updated) + '</span></div>' +
            '<p class="ticket-conv-item__text">Sure — request ID <code>req_84Kq2xF9</code>. Let me know what else you need.</p>' +
          '</div>' +
        '</div>' +
        '<div class="ticket-props">' +
          '<h6 class="ticket-props-title">Properties</h6>' +
          '<div class="ticket-prop"><span class="ticket-prop__label">Assignee</span><select class="form-select form-select-sm" data-ticket-assignee><option>' + esc(agent.name) + '</option>' + USERS.filter(u => u.id !== 'me' && u.id !== t.agentId).map(u => '<option>' + esc(u.name) + '</option>').join('') + '</select></div>' +
          '<div class="ticket-prop"><span class="ticket-prop__label">Priority</span><select class="form-select form-select-sm" data-ticket-priority-edit><option value="urgent"' + (t.priority==='urgent'?' selected':'') + '>Urgent</option><option value="high"' + (t.priority==='high'?' selected':'') + '>High</option><option value="medium"' + (t.priority==='medium'?' selected':'') + '>Medium</option><option value="low"' + (t.priority==='low'?' selected':'') + '>Low</option></select></div>' +
          '<div class="ticket-prop"><span class="ticket-prop__label">Status</span><select class="form-select form-select-sm" data-ticket-status-edit><option value="open"' + (t.status==='open'?' selected':'') + '>Open</option><option value="progress"' + (t.status==='progress'?' selected':'') + '>In Progress</option><option value="waiting"' + (t.status==='waiting'?' selected':'') + '>Waiting</option><option value="resolved"' + (t.status==='resolved'?' selected':'') + '>Resolved</option><option value="closed"' + (t.status==='closed'?' selected':'') + '>Closed</option></select></div>' +
          '<div class="ticket-prop"><span class="ticket-prop__label">Due date</span><input type="date" class="form-control form-control-sm" value="2026-07-30"></div>' +
          '<div class="ticket-prop"><span class="ticket-prop__label">Tags</span><div>' + t.tags.map(tag => '<span class="badge bg-primary-subtle text-primary me-1">' + esc(tag) + '</span>').join('') + '</div></div>' +
          '<div class="ticket-prop"><span class="ticket-prop__label">Linked issue</span><a href="#" class="small">#JIRA-2044</a></div>' +
        '</div>';

      // Compute drawer changes bind
      $('[data-ticket-assignee]').addEventListener('change', () => orchidToast('Assignee updated', 'success'));
      $('[data-ticket-priority-edit]').addEventListener('change', (e) => { t.priority = e.target.value; renderTable(); orchidToast('Priority updated', 'success'); });
      $('[data-ticket-status-edit]').addEventListener('change', (e) => { t.status = e.target.value; renderTable(); orchidToast('Status updated', 'success'); });

      if (window.bootstrap) bootstrap.Offcanvas.getOrCreateInstance(drawerEl).show();
    }

    tbody.addEventListener('click', (e) => {
      const checkbox = e.target.closest('[data-ticket-select]');
      if (checkbox) {
        const id = checkbox.dataset.ticketSelect;
        if (checkbox.checked) selected.add(id); else selected.delete(id);
        bulkBar.classList.toggle('is-visible', selected.size > 0);
        $('[data-ticket-bulk-count]').textContent = selected.size;
        renderTable();
        return;
      }
      const row = e.target.closest('[data-ticket-row]');
      if (row) openDrawer(row.dataset.ticketRow);
    });

    // filter inputs
    [searchInput, priorityFilter, statusFilter].filter(Boolean).forEach(el => el.addEventListener('input', renderTable));
    [priorityFilter, statusFilter].filter(Boolean).forEach(el => el.addEventListener('change', renderTable));

    // Ticket drawer composer
    const drawerSend = $('[data-ticket-drawer-send]');
    const drawerText = $('[data-ticket-drawer-text]');
    drawerSend && drawerSend.addEventListener('click', () => {
      const text = (drawerText.value || '').trim();
      if (!text) return;
      // append to conv
      const newBubble = document.createElement('div');
      newBubble.className = 'ticket-conv-item ticket-conv-item--agent';
      newBubble.innerHTML = renderAvatar(userById('me'), 'sm') +
        '<div class="ticket-conv-item__body"><div class="ticket-conv-item__head"><span class="ticket-conv-item__name">You</span><span class="ticket-conv-item__time">just now</span></div>' +
        '<p class="ticket-conv-item__text">' + esc(text).replace(/\n/g,'<br>') + '</p></div>';
      const conv = drawerBody;
      // insert before ticket-props
      const props = conv.querySelector('.ticket-props');
      conv.insertBefore(newBubble, props);
      drawerText.value = '';
      orchidToast('Reply sent', 'success');
    });

    // Canned responses
    const cannedSelect = $('[data-ticket-canned]');
    const canned = {
      'greeting': 'Hi there — thanks for reaching out! I\'m looking into this now and will get back to you shortly with an update.',
      'investigation': 'I\'ve reproduced the issue on our end and have escalated to the engineering team. I\'ll follow up as soon as we have a fix in flight.',
      'resolved': 'This should now be resolved on your end — please refresh and let us know if you continue to see the issue. Sorry for the disruption!',
      'closing': 'Since we haven\'t heard back, I\'m going to close this ticket for now. Please reply to reopen if the issue returns — happy to help.'
    };
    cannedSelect && cannedSelect.addEventListener('change', (e) => {
      const val = e.target.value;
      if (val && canned[val]) { drawerText.value = canned[val]; e.target.value = ''; }
    });

    // bulk actions
    $('[data-ticket-bulk-assign]')?.addEventListener('click', () => orchidToast('Assigned ' + selected.size + ' tickets', 'success'));
    $('[data-ticket-bulk-priority]')?.addEventListener('click', () => orchidToast('Priority updated for ' + selected.size + ' tickets', 'success'));
    $('[data-ticket-bulk-status]')?.addEventListener('click', () => orchidToast('Status updated for ' + selected.size + ' tickets', 'success'));
    $('[data-ticket-bulk-close]')?.addEventListener('click', () => {
      if (confirm('Close ' + selected.size + ' selected tickets?')) {
        selected.forEach(id => { const t = tickets.find(x=>x.id===id); if (t) t.status = 'closed'; });
        selected.clear();
        bulkBar.classList.remove('is-visible');
        renderTable();
        orchidToast('Tickets closed', 'success');
      }
    });

    renderTable();
  }

  /* =====================================================
     Boot per page
     ===================================================== */
  document.addEventListener('DOMContentLoaded', () => {
    const msg = document.querySelector('[data-comms-page="messages"]');
    if (msg) initMessages(msg);
    const chat = document.querySelector('[data-comms-page="chat"]');
    if (chat) initChat(chat);
    const mail = document.querySelector('[data-comms-page="email"]');
    if (mail) initEmail(mail);
    const notif = document.querySelector('[data-comms-page="notifications"]');
    if (notif) initNotifications(notif);
    const tix = document.querySelector('[data-comms-page="tickets"]');
    if (tix) initTickets(tix);
  });
})();
