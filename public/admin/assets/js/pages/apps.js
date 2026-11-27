/* =====================================================
   Orchid - Productivity Apps JS (Notes / Todo / Bookmarks / KB)
   Hooks via [data-apps-*] and per-page hooks
   ===================================================== */
(function () {
  'use strict';

  // ---------- Toolkit ----------
  function ensureToastRegion() {
    var region = document.querySelector('.apps-toast-region');
    if (!region) {
      region = document.createElement('div');
      region.className = 'apps-toast-region';
      region.setAttribute('aria-live', 'polite');
      region.setAttribute('aria-atomic', 'true');
      document.body.appendChild(region);
    }
    return region;
  }
  function orchidToast(msg, variant) {
    variant = variant || 'primary';
    var region = ensureToastRegion();
    var wrap = document.createElement('div');
    wrap.className = 'toast align-items-center border-0 text-bg-' + variant;
    wrap.setAttribute('role', 'status');
    wrap.innerHTML =
      '<div class="d-flex">' +
        '<div class="toast-body">' + msg + '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
      '</div>';
    region.appendChild(wrap);
    if (window.bootstrap && window.bootstrap.Toast) {
      var t = new window.bootstrap.Toast(wrap, { delay: 2800 });
      t.show();
      wrap.addEventListener('hidden.bs.toast', function () { wrap.remove(); });
    } else {
      setTimeout(function () { wrap.remove(); }, 2800);
    }
  }
  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }
  function truncate(str, n) {
    str = String(str || '');
    return str.length > n ? str.slice(0, n - 1) + '…' : str;
  }
  function fmtDate(d) {
    d = (d instanceof Date) ? d : new Date(d);
    if (isNaN(d)) return '';
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
  }
  function relativeTime(d) {
    d = (d instanceof Date) ? d : new Date(d);
    if (isNaN(d)) return '';
    var diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return fmtDate(d);
  }

  window.Apps = { toast: orchidToast, fmtDate: fmtDate, relativeTime: relativeTime, truncate: truncate, escapeHtml: escapeHtml };

  document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelector('[data-apps-page="notes"]')) initNotes();
    if (document.querySelector('[data-apps-page="todo"]')) initTodo();
    if (document.querySelector('[data-apps-page="bookmarks"]')) initBookmarks();
    if (document.querySelector('[data-apps-page="kb"]')) initKB();
    // Prevent forms marked no-action from doing native submit
    document.addEventListener('submit', function (e) {
      if (e.target.closest('[data-apps-noaction]')) e.preventDefault();
    });
  });

  // =========================================================
  // NOTES PAGE
  // =========================================================
  function initNotes() {
    var COLORS = ['default','yellow','green','blue','pink','purple'];
    var FOLDERS = [
      { id: 'work',     name: 'Work',          color: 'indigo' },
      { id: 'personal', name: 'Personal',      color: 'emerald' },
      { id: 'ideas',    name: 'Ideas',         color: 'amber' },
      { id: 'meetings', name: 'Meeting Notes', color: 'sky' }
    ];
    var TAGS = ['product','design','sprint','research','journal','todo','client','draft'];

    var notes = [
      { id:1,  title:'Q3 planning kickoff notes', folder:'meetings', color:'yellow', pinned:true, starred:true, archived:false, tags:['product','sprint'], modified: Date.now() - 3600*1000*2,
        body:'Attendees: Alex, Priya, James, Sarah\n\nKey outcomes:\n- Prioritize Aurora launch over Beacon refactor\n- Hiring freeze lifted for design\n- Move retro to Thursdays\n\nOwners:\n- Priya: draft roadmap by Friday\n- James: talk to Legal about ToS\n- Alex: user interviews next sprint' },
      { id:2,  title:'Aurora mobile IA', folder:'work', color:'default', pinned:true, starred:false, archived:false, tags:['design','draft'], modified: Date.now() - 3600*1000*6,
        body:'Explore a 5-tab bottom nav vs 3-tab + hub pattern. Watch how Linear handles quick capture. Look at Notion mobile new-page flow — the sheet reveal is nice.' },
      { id:3,  title:'Book recommendations', folder:'personal', color:'green', pinned:false, starred:true, archived:false, tags:['journal'], modified: Date.now() - 86400*1000,
        body:'- The Making of a Manager (Julie Zhuo)\n- Ruined by Design (Mike Monteiro)\n- Working in Public (Nadia Eghbal)\n- Slow Productivity (Cal Newport)' },
      { id:4,  title:'One-on-one with Priya — Jan 8', folder:'meetings', color:'default', pinned:false, starred:false, archived:false, tags:['client'], modified: Date.now() - 86400*1000*2,
        body:'Priya feels blocked on the analytics dashboard — needs a decision on which chart lib. Wants to try Recharts. Approve if it stays under 40kb gzipped. Follow-up: pair on the empty-states next Tuesday.' },
      { id:5,  title:'Ideas: gamified onboarding', folder:'ideas', color:'purple', pinned:false, starred:false, archived:false, tags:['product','research'],  modified: Date.now() - 86400*1000*3,
        body:'What if the setup wizard awarded XP for each configured integration? Not literal gaming — subtle. See Duolingo streaks, Superhuman celebrations. Risk: feels childish for enterprise.' },
      { id:6,  title:'Grocery + errands', folder:'personal', color:'pink', pinned:false, starred:false, archived:false, tags:['todo'], modified: Date.now() - 86400*1000*4,
        body:'Eggs, oat milk, bread, spinach, apples, coffee beans. Drop off dry cleaning. Pick up prescription. Return the router to Amazon by Sunday.' },
      { id:7,  title:'API rate limiting research', folder:'work', color:'blue', pinned:false, starred:false, archived:false, tags:['research','draft'], modified: Date.now() - 86400*1000*5,
        body:'Token bucket vs leaky bucket. Cloudflare uses sliding window log. For our free tier, 60 req/min with burst 20 seems standard. Need to document upgrade path clearly.' },
      { id:8,  title:'Weekly reflection — Jan 5', folder:'personal', color:'default', pinned:false, starred:false, archived:false, tags:['journal'], modified: Date.now() - 86400*1000*6,
        body:'What worked: shipping the settings redesign felt clean.\nWhat did not: too many context switches Wednesday.\nNext week: block Tues/Thurs mornings for deep work.' },
      { id:9,  title:'Interview prep: Senior PM', folder:'work', color:'yellow', pinned:false, starred:false, archived:false, tags:['client'], modified: Date.now() - 86400*1000*7,
        body:'Screen: Rina, Wed 2pm. Ask about: PM/EM boundary, tough tradeoff story, how she coaches jr PMs. Send Aurora case brief 24h before.' },
      { id:10, title:'Design system tokens audit', folder:'work', color:'default', pinned:false, starred:false, archived:false, tags:['design'], modified: Date.now() - 86400*1000*9,
        body:'Semantic tokens are inconsistent. Need to align: --surface / --bg / --hover. Currently 14 shades of grey in use, aim for 6. Migration plan draft in the Figma file.' },
      { id:11, title:'Trip: Lisbon Feb 12-19', folder:'personal', color:'green', pinned:false, starred:false, archived:false, tags:['journal'], modified: Date.now() - 86400*1000*10,
        body:'Hotel: Memmo Alfama (confirmed).\nMust-do: LX Factory, day trip to Sintra, pastel de nata at Manteigaria.\nBook: tram 28 early morning to avoid crowds.' },
      { id:12, title:'Product retrospective template', folder:'ideas', color:'default', pinned:false, starred:false, archived:false, tags:['product'], modified: Date.now() - 86400*1000*12,
        body:'Sections: What went well, What did not, What surprised us, What we will try next. Time-box each to 8 min. End with 1 concrete owner per action.' },
      { id:13, title:'Fitness: new lifting split', folder:'personal', color:'blue', pinned:false, starred:false, archived:false, tags:['journal'], modified: Date.now() - 86400*1000*14,
        body:'Upper/Lower/Push/Pull, 4 days. Deload every 5th week. Track squat, bench, deadlift, OHP progression.' },
      { id:14, title:'Support macro rewrites', folder:'work', color:'default', pinned:false, starred:false, archived:true, tags:['draft'], modified: Date.now() - 86400*1000*20,
        body:'Migrated to the new tone-of-voice guide. Removed corporate stiffness from billing macros. Kept legal language for refund confirmations.' },
      { id:15, title:'Blog post: shipping culture', folder:'ideas', color:'purple', pinned:false, starred:true, archived:false, tags:['draft','research'], modified: Date.now() - 86400*1000*11,
        body:'Thesis: shipping culture is not about velocity — it is about how the team feels between ships. Draft: 3 anecdotes, 1 counter-example, close with a checklist.' }
    ];

    var state = {
      selectedId: 1,
      view: 'all', // all/starred/archived/trash + folder id + tag:xxx
      search: '',
      sort: 'modified',
      trash: []
    };

    var $sidebar = document.querySelector('[data-notes-sidebar]');
    var $list = document.querySelector('[data-notes-list]');
    var $editor = document.querySelector('[data-notes-editor]');
    var $search = document.querySelector('[data-notes-search]');
    var $sort = document.querySelector('[data-notes-sort]');
    var $newBtn = document.querySelector('[data-notes-new]');

    function counts() {
      var c = { all: 0, starred: 0, archived: 0, trash: state.trash.length };
      FOLDERS.forEach(function (f) { c['folder:' + f.id] = 0; });
      notes.forEach(function (n) {
        if (n.archived) c.archived++;
        else c.all++;
        if (n.starred && !n.archived) c.starred++;
        if (!n.archived) c['folder:' + n.folder] = (c['folder:' + n.folder] || 0) + 1;
      });
      return c;
    }

    function renderSidebar() {
      var c = counts();
      var html = '';
      html += '<ul class="apps-side-nav">';
      html += navItem('all',      'bi-journal-text',    'All Notes', c.all);
      html += navItem('starred',  'bi-star',            'Starred',   c.starred);
      html += navItem('archived', 'bi-archive',         'Archived',  c.archived);
      html += navItem('trash',    'bi-trash',           'Trash',     c.trash);
      html += '</ul>';

      html += '<div class="apps-side-heading">Folders <button type="button" title="New folder" aria-label="New folder"><i class="bi bi-plus-lg"></i></button></div>';
      html += '<ul class="apps-side-nav">';
      FOLDERS.forEach(function (f) {
        html += navItem('folder:' + f.id, null, '<span class="apps-dot apps-dot--' + f.color + ' me-2"></span>' + f.name, c['folder:' + f.id] || 0);
      });
      html += '</ul>';

      html += '<div class="apps-side-heading">Tags</div>';
      html += '<div class="d-flex flex-wrap gap-1 px-2">';
      TAGS.forEach(function (t) {
        var active = state.view === 'tag:' + t ? ' is-active' : '';
        html += '<button type="button" class="apps-chip' + active + '" data-notes-view="tag:' + t + '">#' + t + '</button>';
      });
      html += '</div>';

      $sidebar.innerHTML = html;
    }
    function navItem(view, icon, label, count) {
      var active = state.view === view ? ' is-active' : '';
      var iconHtml = icon ? '<i class="bi ' + icon + '"></i>' : '';
      return '<li><button type="button" class="apps-side-nav__item' + active + '" data-notes-view="' + view + '">' +
        iconHtml + '<span>' + label + '</span>' +
        '<span class="apps-count">' + count + '</span></button></li>';
    }

    function filteredNotes() {
      var list = notes.filter(function (n) {
        if (state.view === 'all') return !n.archived;
        if (state.view === 'starred') return n.starred && !n.archived;
        if (state.view === 'archived') return n.archived;
        if (state.view === 'trash') return false;
        if (state.view.indexOf('folder:') === 0) return !n.archived && n.folder === state.view.slice(7);
        if (state.view.indexOf('tag:') === 0) return !n.archived && n.tags.indexOf(state.view.slice(4)) > -1;
        return true;
      });
      if (state.view === 'trash') list = state.trash.slice();
      if (state.search) {
        var q = state.search.toLowerCase();
        list = list.filter(function (n) {
          return n.title.toLowerCase().indexOf(q) > -1 || n.body.toLowerCase().indexOf(q) > -1;
        });
      }
      list.sort(function (a, b) {
        if (a.pinned && !b.pinned) return -1;
        if (!a.pinned && b.pinned) return 1;
        if (state.sort === 'title') return a.title.localeCompare(b.title);
        if (state.sort === 'created') return b.id - a.id;
        return b.modified - a.modified;
      });
      return list;
    }

    function folderMeta(id) {
      for (var i = 0; i < FOLDERS.length; i++) if (FOLDERS[i].id === id) return FOLDERS[i];
      return { name: '', color: 'slate' };
    }

    function renderList() {
      var list = filteredNotes();
      if (!list.length) {
        $list.innerHTML = '<div class="apps-empty"><i class="bi bi-journal"></i>No notes found</div>';
        return;
      }
      var html = '<ul class="notes-list">';
      list.forEach(function (n) {
        var fm = folderMeta(n.folder);
        var active = n.id === state.selectedId ? ' is-active' : '';
        var pin = n.pinned ? '<i class="bi bi-pin-angle-fill notes-list__pin" aria-hidden="true"></i>' : '';
        var tagsHtml = n.tags.slice(0, 2).map(function (t) { return '<span class="apps-chip">#' + t + '</span>'; }).join('');
        html += '<li class="notes-list__item' + active + '" data-notes-select="' + n.id + '">' +
          pin +
          '<p class="notes-list__title"><span class="apps-dot apps-dot--' + fm.color + '"></span>' + escapeHtml(n.title) + '</p>' +
          '<p class="notes-list__snippet">' + escapeHtml(truncate(n.body, 140)) + '</p>' +
          '<div class="notes-list__meta">' +
            '<span>' + relativeTime(n.modified) + '</span>' +
            '<span>·</span>' +
            '<span>' + escapeHtml(fm.name) + '</span>' +
            (tagsHtml ? '<span class="ms-1"></span>' + tagsHtml : '') +
          '</div>' +
          '</li>';
      });
      html += '</ul>';
      $list.innerHTML = html;
    }

    function renderEditor() {
      var n = notes.filter(function (x) { return x.id === state.selectedId; })[0];
      if (!n) {
        var t = filteredNotes()[0];
        if (t) { state.selectedId = t.id; n = t; }
      }
      if (!n) {
        $editor.innerHTML = '<div class="apps-empty"><i class="bi bi-file-earmark-text"></i>Select or create a note to begin</div>';
        return;
      }
      var fm = folderMeta(n.folder);
      var swatches = COLORS.map(function (c) {
        return '<button type="button" class="notes-color-swatch' + (n.color === c ? ' is-active' : '') + '" data-color="' + c + '" data-notes-color="' + c + '" aria-label="Color ' + c + '"></button>';
      }).join('');
      var tagChips = n.tags.map(function (t) { return '<span class="apps-chip">#' + t + '</span>'; }).join(' ');

      $editor.innerHTML =
        '<div class="notes-editor tint-' + n.color + '" data-note-id="' + n.id + '">' +
          '<div class="notes-editor__title-wrap">' +
            '<div class="d-flex align-items-center gap-2 mb-2">' +
              '<span class="apps-dot apps-dot--' + fm.color + '"></span>' +
              '<small class="text-body-secondary">' + escapeHtml(fm.name) + '</small>' +
              '<div class="ms-auto d-flex gap-1">' +
                '<button type="button" class="btn btn-sm btn-icon" data-notes-star aria-label="Star"><i class="bi bi-star' + (n.starred ? '-fill text-warning' : '') + '"></i></button>' +
                '<button type="button" class="btn btn-sm btn-icon" data-notes-pin aria-label="Pin"><i class="bi bi-pin-angle' + (n.pinned ? '-fill text-primary' : '') + '"></i></button>' +
                '<button type="button" class="btn btn-sm btn-icon" data-notes-archive aria-label="Archive"><i class="bi bi-archive"></i></button>' +
                '<button type="button" class="btn btn-sm btn-icon text-danger" data-notes-delete aria-label="Delete"><i class="bi bi-trash"></i></button>' +
              '</div>' +
            '</div>' +
            '<input type="text" class="notes-editor__title" data-notes-title value="' + escapeHtml(n.title) + '" placeholder="Untitled note">' +
          '</div>' +
          '<div class="notes-editor__meta">' +
            '<span><i class="bi bi-clock me-1"></i>Modified ' + relativeTime(n.modified) + '</span>' +
            '<span>·</span>' +
            '<span>' + tagChips + '</span>' +
          '</div>' +
          '<div class="notes-editor__toolbar" role="toolbar" aria-label="Formatting">' +
            '<div class="btn-group btn-group-sm" role="group">' +
              '<button type="button" class="btn btn-outline-secondary" data-notes-fmt="bold" title="Bold"><i class="bi bi-type-bold"></i></button>' +
              '<button type="button" class="btn btn-outline-secondary" data-notes-fmt="italic" title="Italic"><i class="bi bi-type-italic"></i></button>' +
              '<button type="button" class="btn btn-outline-secondary" data-notes-fmt="underline" title="Underline"><i class="bi bi-type-underline"></i></button>' +
            '</div>' +
            '<span class="divider"></span>' +
            '<div class="btn-group btn-group-sm" role="group">' +
              '<button type="button" class="btn btn-outline-secondary" data-notes-fmt="ul" title="Bulleted list"><i class="bi bi-list-ul"></i></button>' +
              '<button type="button" class="btn btn-outline-secondary" data-notes-fmt="ol" title="Numbered list"><i class="bi bi-list-ol"></i></button>' +
              '<button type="button" class="btn btn-outline-secondary" data-notes-fmt="quote" title="Quote"><i class="bi bi-quote"></i></button>' +
            '</div>' +
            '<span class="divider"></span>' +
            '<div class="btn-group btn-group-sm" role="group">' +
              '<button type="button" class="btn btn-outline-secondary" data-notes-fmt="link" title="Insert link"><i class="bi bi-link-45deg"></i></button>' +
              '<button type="button" class="btn btn-outline-secondary" data-notes-fmt="image" title="Insert image"><i class="bi bi-image"></i></button>' +
              '<button type="button" class="btn btn-outline-secondary" data-notes-fmt="code" title="Code"><i class="bi bi-code-slash"></i></button>' +
            '</div>' +
            '<span class="divider"></span>' +
            '<button type="button" class="btn btn-sm btn-outline-secondary" data-notes-preview title="Preview"><i class="bi bi-eye"></i> Preview</button>' +
            '<div class="notes-color-swatches" role="group" aria-label="Note color">' + swatches + '</div>' +
          '</div>' +
          '<textarea class="notes-editor__body" data-notes-body placeholder="Start writing…">' + escapeHtml(n.body) + '</textarea>' +
          '<div class="apps-panel__foot">' +
            '<small class="text-body-secondary">Auto-saved · ' + fmtDate(n.modified) + '</small>' +
            '<button type="button" class="btn btn-sm btn-primary ms-auto" data-notes-save>Save note</button>' +
          '</div>' +
        '</div>';
    }

    function renderAll() {
      renderSidebar();
      renderList();
      renderEditor();
    }

    // Delegated events
    document.addEventListener('click', function (e) {
      var t;
      if ((t = e.target.closest('[data-notes-view]'))) {
        state.view = t.getAttribute('data-notes-view');
        state.selectedId = (filteredNotes()[0] || {}).id;
        renderAll();
      } else if ((t = e.target.closest('[data-notes-select]'))) {
        state.selectedId = parseInt(t.getAttribute('data-notes-select'), 10);
        renderList();
        renderEditor();
      } else if ((t = e.target.closest('[data-notes-color]'))) {
        var n = notes.filter(function (x) { return x.id === state.selectedId; })[0];
        if (!n) return;
        n.color = t.getAttribute('data-notes-color');
        n.modified = Date.now();
        renderEditor();
      } else if (e.target.closest('[data-notes-star]')) {
        var s = notes.filter(function (x) { return x.id === state.selectedId; })[0];
        if (!s) return; s.starred = !s.starred; s.modified = Date.now();
        renderAll();
        orchidToast(s.starred ? 'Note starred' : 'Star removed', 'primary');
      } else if (e.target.closest('[data-notes-pin]')) {
        var p = notes.filter(function (x) { return x.id === state.selectedId; })[0];
        if (!p) return; p.pinned = !p.pinned; p.modified = Date.now();
        renderAll();
      } else if (e.target.closest('[data-notes-archive]')) {
        var a = notes.filter(function (x) { return x.id === state.selectedId; })[0];
        if (!a) return; a.archived = !a.archived; a.modified = Date.now();
        renderAll();
        orchidToast(a.archived ? 'Note archived' : 'Restored from archive', 'secondary');
      } else if (e.target.closest('[data-notes-delete]')) {
        if (!window.confirm('Move this note to Trash?')) return;
        var d = notes.filter(function (x) { return x.id === state.selectedId; })[0];
        if (!d) return;
        state.trash.push(d);
        notes = notes.filter(function (x) { return x.id !== d.id; });
        state.selectedId = (filteredNotes()[0] || {}).id;
        renderAll();
        orchidToast('Moved to Trash', 'danger');
      } else if (e.target.closest('[data-notes-save]')) {
        orchidToast('Note saved', 'success');
      } else if (e.target.closest('[data-notes-preview]')) {
        orchidToast('Preview toggled', 'primary');
      } else if ((t = e.target.closest('[data-notes-fmt]'))) {
        try { document.execCommand(mapFmt(t.getAttribute('data-notes-fmt')), false, null); } catch (_) {}
      } else if (e.target.closest('[data-notes-new]')) {
        var maxId = notes.reduce(function (m, n) { return n.id > m ? n.id : m; }, 0);
        var nn = { id: maxId + 1, title: 'Untitled', folder: 'personal', color: 'default', pinned: false, starred: false, archived: false, tags: [], modified: Date.now(), body: '' };
        notes.unshift(nn);
        state.selectedId = nn.id;
        state.view = 'all';
        renderAll();
        orchidToast('New note created', 'success');
      }
    });

    function mapFmt(f) {
      var map = { bold: 'bold', italic: 'italic', underline: 'underline', ul: 'insertUnorderedList', ol: 'insertOrderedList' };
      return map[f] || 'bold';
    }

    document.addEventListener('input', function (e) {
      if (e.target.matches('[data-notes-title]')) {
        var n = notes.filter(function (x) { return x.id === state.selectedId; })[0];
        if (!n) return; n.title = e.target.value; n.modified = Date.now();
        // update list title live
        var li = document.querySelector('[data-notes-select="' + n.id + '"] .notes-list__title');
        if (li) li.lastChild.textContent = n.title;
      } else if (e.target.matches('[data-notes-body]')) {
        var b = notes.filter(function (x) { return x.id === state.selectedId; })[0];
        if (!b) return; b.body = e.target.value; b.modified = Date.now();
      } else if (e.target === $search) {
        state.search = e.target.value.trim();
        renderList();
      }
    });

    if ($sort) $sort.addEventListener('change', function () {
      state.sort = $sort.value; renderList();
    });

    renderAll();
  }

  // =========================================================
  // TODO PAGE
  // =========================================================
  function initTodo() {
    var LISTS = [
      { id: 'personal', name: 'Personal', color: 'emerald', icon: 'bi-person' },
      { id: 'work',     name: 'Work',     color: 'indigo',  icon: 'bi-briefcase' },
      { id: 'shopping', name: 'Shopping', color: 'amber',   icon: 'bi-cart' },
      { id: 'fitness',  name: 'Fitness',  color: 'rose',    icon: 'bi-heart-pulse' }
    ];

    var todos = [
      { id: 1, list:'work', text:'Review Aurora mobile designs with Priya', priority:1, due: dayOffset(0), tags:['design'], done:false },
      { id: 2, list:'work', text:'Send Q3 review docs to Priya', priority:2, due: dayOffset(1), tags:['docs'], done:false },
      { id: 3, list:'work', text:'Reply to Legal on data-processing addendum', priority:1, due: dayOffset(-1), tags:['legal'], done:false },
      { id: 4, list:'work', text:'Draft engineering all-hands slides', priority:3, due: dayOffset(4), tags:['presentation'], done:false },
      { id: 5, list:'work', text:'Approve marketing site copy', priority:3, due: dayOffset(6), tags:[], done:true },
      { id: 6, list:'work', text:'Interview Rina for senior PM role', priority:2, due: dayOffset(2), tags:['hiring'], done:false },
      { id: 7, list:'work', text:'Renew SSL certificate (production)', priority:1, due: dayOffset(9), tags:['ops'], done:false },
      { id: 8, list:'personal', text:'Book dentist appointment', priority:2, due: dayOffset(3), tags:[], done:false },
      { id: 9, list:'personal', text:'Renew passport', priority:3, due: dayOffset(20), tags:[], done:false },
      { id:10, list:'personal', text:'Call mom on Sunday', priority:2, due: dayOffset(5), tags:['family'], done:false },
      { id:11, list:'personal', text:'Water plants', priority:4, due: dayOffset(0), tags:[], done:true },
      { id:12, list:'personal', text:'Finish the memoir chapter', priority:3, due: dayOffset(8), tags:['writing'], done:false },
      { id:13, list:'shopping', text:'Buy groceries: eggs, milk, bread, spinach', priority:2, due: dayOffset(0), tags:['errand'], done:false },
      { id:14, list:'shopping', text:'Pick up new AirPods case', priority:4, due: dayOffset(2), tags:[], done:false },
      { id:15, list:'shopping', text:'Order coffee beans (Ritual)', priority:3, due: dayOffset(6), tags:[], done:true },
      { id:16, list:'shopping', text:'Buy birthday gift for Sam', priority:2, due: dayOffset(10), tags:['gift'], done:false },
      { id:17, list:'fitness',  text:'5k run before work', priority:3, due: dayOffset(0), tags:['cardio'], done:true },
      { id:18, list:'fitness',  text:'Yoga class 6pm Thursday', priority:3, due: dayOffset(3), tags:[], done:false },
      { id:19, list:'fitness',  text:'Deadlift PR attempt Saturday', priority:2, due: dayOffset(5), tags:['strength'], done:false },
      { id:20, list:'fitness',  text:'Meal prep for the week', priority:3, due: dayOffset(-2), tags:['prep'], done:false }
    ];

    function dayOffset(d) {
      var dt = new Date(); dt.setDate(dt.getDate() + d); dt.setHours(23, 59, 0, 0); return dt.getTime();
    }

    var state = { list: 'work', filter: 'all' };
    var $sidebar = document.querySelector('[data-todo-sidebar]');
    var $header  = document.querySelector('[data-todo-hdr]');
    var $ring    = document.querySelector('[data-todo-ring]');
    var $items   = document.querySelector('[data-todo-items]');
    var $tabs    = document.querySelector('[data-todo-tabs]');
    var $quick   = document.querySelector('[data-todo-quick]');

    function listMeta(id) {
      for (var i = 0; i < LISTS.length; i++) if (LISTS[i].id === id) return LISTS[i];
      return LISTS[0];
    }
    function listTodos(id) { return todos.filter(function (t) { return t.list === id; }); }
    function countActive(id) { return listTodos(id).filter(function (t) { return !t.done; }).length; }

    function renderSidebar() {
      var html = '<div class="apps-side-heading">Lists <button type="button" title="New list" aria-label="New list"><i class="bi bi-plus-lg"></i></button></div>';
      html += '<ul class="apps-side-nav">';
      LISTS.forEach(function (l) {
        var active = state.list === l.id ? ' is-active' : '';
        html += '<li><button type="button" class="apps-side-nav__item' + active + '" data-todo-list="' + l.id + '">' +
          '<i class="bi ' + l.icon + '"></i><span>' + l.name + '</span>' +
          '<span class="apps-count">' + countActive(l.id) + '</span></button></li>';
      });
      html += '</ul>';
      html += '<div class="apps-side-heading">Labels</div>';
      html += '<div class="todo-labels">' +
              '<span class="apps-chip"><span class="apps-dot apps-dot--rose"></span>Urgent</span>' +
              '<span class="apps-chip"><span class="apps-dot apps-dot--amber"></span>Deep work</span>' +
              '<span class="apps-chip"><span class="apps-dot apps-dot--sky"></span>Waiting</span>' +
              '<span class="apps-chip"><span class="apps-dot apps-dot--emerald"></span>Someday</span>' +
              '</div>';
      html += '<div class="apps-side-heading">Views</div>';
      html += '<ul class="apps-side-nav">' +
              '<li><button type="button" class="apps-side-nav__item"><i class="bi bi-calendar-check"></i>Today<span class="apps-count">' + todos.filter(function (t) { return !t.done && sameDay(t.due, new Date()); }).length + '</span></button></li>' +
              '<li><button type="button" class="apps-side-nav__item"><i class="bi bi-star"></i>High priority<span class="apps-count">' + todos.filter(function (t) { return !t.done && t.priority === 1; }).length + '</span></button></li>' +
              '<li><button type="button" class="apps-side-nav__item"><i class="bi bi-exclamation-triangle"></i>Overdue<span class="apps-count">' + todos.filter(function (t) { return !t.done && t.due < Date.now() && !sameDay(t.due, new Date()); }).length + '</span></button></li>' +
              '</ul>';
      $sidebar.innerHTML = html;
    }
    function sameDay(a, b) {
      a = new Date(a); b = new Date(b);
      return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    }
    function renderHeader() {
      var meta = listMeta(state.list);
      var all = listTodos(state.list);
      var done = all.filter(function (t) { return t.done; }).length;
      var pct = all.length ? Math.round(done / all.length * 100) : 0;
      $header.innerHTML =
        '<div class="d-flex align-items-center gap-3">' +
          '<div class="todo-ring" data-todo-ring><span class="todo-ring__val">' + pct + '%</span></div>' +
          '<div>' +
            '<h2><i class="bi ' + meta.icon + ' me-2 text-primary"></i>' + meta.name + '</h2>' +
            '<p class="lede">' + done + ' of ' + all.length + ' complete · ' + (all.length - done) + ' remaining</p>' +
          '</div>' +
        '</div>' +
        '<div class="ms-auto d-flex gap-2 align-items-center flex-wrap">' +
          '<button type="button" class="btn btn-sm btn-outline-secondary" data-todo-bulk="done"><i class="bi bi-check2-all me-1"></i>Mark all done</button>' +
          '<button type="button" class="btn btn-sm btn-outline-secondary" data-todo-bulk="clear"><i class="bi bi-x-circle me-1"></i>Clear completed</button>' +
          '<button type="button" class="btn btn-sm btn-outline-secondary" data-todo-bulk="export"><i class="bi bi-download me-1"></i>Export</button>' +
          '<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="offcanvas" data-bs-target="#todoAddCanvas"><i class="bi bi-plus-lg me-1"></i>Add task</button>' +
        '</div>';
      // set custom prop for ring
      var ring = $header.querySelector('[data-todo-ring]');
      if (ring) {
        ring.style.setProperty('--pct', pct);
        ring.style.setProperty('--clr', pct === 100 ? 'var(--bs-success)' : 'var(--orchid-primary)');
      }
    }
    function renderTabs() {
      var tabs = ['all', 'active', 'completed', 'overdue'];
      $tabs.innerHTML = tabs.map(function (t) {
        var count = filteredCount(t);
        var label = t.charAt(0).toUpperCase() + t.slice(1);
        return '<button type="button" class="' + (state.filter === t ? 'is-active' : '') + '" data-todo-tab="' + t + '">' +
          label + ' <span class="text-body-secondary ms-1">' + count + '</span></button>';
      }).join('');
    }
    function filteredCount(tab) {
      return filtered(tab).length;
    }
    function filtered(tab) {
      var list = listTodos(state.list);
      tab = tab || state.filter;
      if (tab === 'active') return list.filter(function (t) { return !t.done; });
      if (tab === 'completed') return list.filter(function (t) { return t.done; });
      if (tab === 'overdue') return list.filter(function (t) { return !t.done && t.due < Date.now() && !sameDay(t.due, new Date()); });
      return list;
    }
    function groupOf(t) {
      var today = new Date(); today.setHours(0,0,0,0);
      var end = new Date(today); end.setDate(end.getDate() + 7);
      if (t.due < today.getTime()) return 'Overdue';
      if (sameDay(t.due, today)) return 'Today';
      if (t.due < end.getTime()) return 'This Week';
      return 'Later';
    }

    function renderItems() {
      var list = filtered();
      if (!list.length) {
        $items.innerHTML = '<div class="apps-empty"><i class="bi bi-check2-square"></i>Nothing here — take a break!</div>';
        return;
      }
      // sort: not done first, then by due
      list.sort(function (a, b) {
        if (a.done !== b.done) return a.done ? 1 : -1;
        return a.due - b.due;
      });
      var grouped = {}, order = ['Overdue','Today','This Week','Later'];
      list.forEach(function (t) { var g = groupOf(t); (grouped[g] = grouped[g] || []).push(t); });
      var html = '<ul class="todo-items">';
      order.forEach(function (g) {
        if (!grouped[g]) return;
        html += '<li class="todo-group">' + g + '<span class="text-body-secondary fw-normal">' + grouped[g].length + '</span></li>';
        grouped[g].forEach(function (t) {
          var due = new Date(t.due);
          var overdue = !t.done && t.due < Date.now() && !sameDay(t.due, new Date());
          var dueCls = overdue ? ' is-overdue' : (sameDay(t.due, new Date()) ? ' is-today' : '');
          var tagsHtml = t.tags.map(function (x) { return '<span class="apps-chip">#' + x + '</span>'; }).join(' ');
          html += '<li class="todo-item' + (t.done ? ' is-done' : '') + '" draggable="true" data-todo-id="' + t.id + '">' +
            '<i class="bi bi-grip-vertical todo-item__handle" aria-hidden="true"></i>' +
            '<input type="checkbox" class="form-check-input todo-item__check" data-todo-check' + (t.done ? ' checked' : '') + ' aria-label="Complete">' +
            '<div class="todo-item__body">' +
              '<span class="todo-item__text">' + escapeHtml(t.text) + '</span>' +
              '<div class="todo-item__meta">' +
                '<span class="todo-due' + dueCls + '"><i class="bi bi-calendar-event me-1"></i>' + fmtDate(due) + '</span>' +
                tagsHtml +
              '</div>' +
            '</div>' +
            '<button type="button" class="todo-item__flag p' + t.priority + '" data-todo-flag title="Priority ' + t.priority + '" aria-label="Priority"><i class="bi bi-flag-fill"></i></button>' +
            '<div class="todo-item__actions">' +
              '<button type="button" class="btn btn-sm btn-icon" data-todo-edit aria-label="Edit"><i class="bi bi-pencil"></i></button>' +
              '<button type="button" class="btn btn-sm btn-icon text-danger" data-todo-del aria-label="Delete"><i class="bi bi-trash"></i></button>' +
            '</div>' +
            '</li>';
        });
      });
      html += '</ul>';
      $items.innerHTML = html;
      wireDrag();
    }
    function wireDrag() {
      var items = $items.querySelectorAll('.todo-item');
      var dragging = null;
      items.forEach(function (el) {
        el.addEventListener('dragstart', function () { dragging = el; el.classList.add('is-dragging'); });
        el.addEventListener('dragend',   function () { if (dragging) dragging.classList.remove('is-dragging'); dragging = null;
          items.forEach(function (i) { i.classList.remove('is-drop-target'); });
        });
        el.addEventListener('dragover', function (e) {
          e.preventDefault();
          items.forEach(function (i) { i.classList.remove('is-drop-target'); });
          el.classList.add('is-drop-target');
        });
        el.addEventListener('drop', function (e) {
          e.preventDefault();
          if (!dragging || dragging === el) return;
          var fromId = parseInt(dragging.getAttribute('data-todo-id'), 10);
          var toId = parseInt(el.getAttribute('data-todo-id'), 10);
          reorder(fromId, toId);
          renderAll();
        });
      });
    }
    function reorder(fromId, toId) {
      var from = todos.findIndex(function (t) { return t.id === fromId; });
      var to = todos.findIndex(function (t) { return t.id === toId; });
      if (from < 0 || to < 0) return;
      var it = todos.splice(from, 1)[0];
      todos.splice(to, 0, it);
    }

    function renderAll() {
      renderSidebar(); renderHeader(); renderTabs(); renderItems();
    }

    $sidebar.addEventListener('click', function (e) {
      var t = e.target.closest('[data-todo-list]');
      if (!t) return;
      state.list = t.getAttribute('data-todo-list');
      renderAll();
    });
    $tabs.addEventListener('click', function (e) {
      var t = e.target.closest('[data-todo-tab]');
      if (!t) return;
      state.filter = t.getAttribute('data-todo-tab');
      renderTabs(); renderItems();
    });
    $items.addEventListener('click', function (e) {
      var li = e.target.closest('.todo-item');
      if (!li) return;
      var id = parseInt(li.getAttribute('data-todo-id'), 10);
      var todo = todos.filter(function (t) { return t.id === id; })[0];
      if (!todo) return;
      if (e.target.closest('[data-todo-check]')) {
        todo.done = !todo.done;
        renderAll();
      } else if (e.target.closest('[data-todo-flag]')) {
        todo.priority = todo.priority >= 4 ? 1 : todo.priority + 1;
        renderItems();
      } else if (e.target.closest('[data-todo-del]')) {
        if (!window.confirm('Delete this task?')) return;
        todos = todos.filter(function (t) { return t.id !== id; });
        renderAll();
        orchidToast('Task deleted', 'danger');
      } else if (e.target.closest('[data-todo-edit]')) {
        var nv = window.prompt('Edit task', todo.text);
        if (nv != null) { todo.text = nv; renderItems(); orchidToast('Task updated', 'success'); }
      }
    });
    $quick.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      e.preventDefault();
      var raw = $quick.value.trim();
      if (!raw) return;
      var tagRe = /#([a-z0-9-]+)/gi, tags = [], m;
      while ((m = tagRe.exec(raw))) tags.push(m[1]);
      var prio = 4;
      var pMatch = raw.match(/!p([1-4])/i);
      if (pMatch) prio = parseInt(pMatch[1], 10);
      var text = raw.replace(tagRe, '').replace(/!p[1-4]/ig, '').replace(/\s+/g, ' ').trim();
      if (!text) return;
      var maxId = todos.reduce(function (m, t) { return t.id > m ? t.id : m; }, 0);
      todos.unshift({ id: maxId + 1, list: state.list, text: text, priority: prio, due: dayOffset(0), tags: tags, done: false });
      $quick.value = '';
      renderAll();
      orchidToast('Task added', 'success');
    });
    document.addEventListener('click', function (e) {
      var t = e.target.closest('[data-todo-bulk]');
      if (!t) return;
      var kind = t.getAttribute('data-todo-bulk');
      if (kind === 'done') {
        listTodos(state.list).forEach(function (x) { x.done = true; });
        renderAll(); orchidToast('All tasks marked done', 'success');
      } else if (kind === 'clear') {
        todos = todos.filter(function (x) { return !(x.list === state.list && x.done); });
        renderAll(); orchidToast('Completed tasks cleared', 'secondary');
      } else if (kind === 'export') {
        orchidToast('List exported to clipboard', 'primary');
      }
      var f = e.target.closest('[data-todo-offcanvas-save]');
      if (f) {
        var form = document.getElementById('todoAddForm');
        if (!form) return;
        var data = new FormData(form);
        var title = (data.get('title') || '').toString().trim();
        if (!title) { orchidToast('Enter a task title', 'danger'); return; }
        var list = (data.get('list') || state.list).toString();
        var due = (data.get('due') || '').toString();
        var prio = parseInt((data.get('priority') || '3').toString(), 10);
        var maxId = todos.reduce(function (m, t) { return t.id > m ? t.id : m; }, 0);
        todos.unshift({ id: maxId + 1, list: list, text: title, priority: prio, due: due ? new Date(due).getTime() : dayOffset(0), tags: [], done: false });
        form.reset();
        state.list = list;
        renderAll();
        orchidToast('Task added', 'success');
        var oc = bootstrap.Offcanvas.getInstance(document.getElementById('todoAddCanvas'));
        if (oc) oc.hide();
      }
    });

    renderAll();
  }

  // =========================================================
  // BOOKMARKS PAGE
  // =========================================================
  function initBookmarks() {
    var FOLDERS = [
      { id: 'work',    name: 'Work',           icon: 'bi-briefcase', color: 'indigo', children: [
        { id: 'design',      name: 'Design',        color: 'pink' },
        { id: 'engineering', name: 'Engineering',   color: 'cyan' },
        { id: 'product',     name: 'Product',       color: 'violet' }
      ]},
      { id: 'personal',name: 'Personal',       icon: 'bi-person',    color: 'emerald', children: [
        { id: 'learning',    name: 'Learning',      color: 'teal' },
        { id: 'reading',     name: 'Reading list',  color: 'amber' }
      ]},
      { id: 'archive', name: 'Archive',        icon: 'bi-archive',   color: 'slate', children: [] }
    ];

    var bookmarks = [
      { id:1,  title:'Bootstrap 5.3 docs',       url:'https://getbootstrap.com/docs/5.3/', desc:'Official Bootstrap 5.3 documentation — layout, components, utilities.', folder:'engineering', tags:['docs','reference'], color:'violet', date: Date.now() - 3600*1000*4 },
      { id:2,  title:'Orchid UI on GitHub',       url:'https://github.com/orchid-ui/orchid', desc:'Source, issues and roadmap for the Orchid design system.', folder:'engineering', tags:['tools','reference'], color:'slate', date: Date.now() - 86400*1000 },
      { id:3,  title:'Tailwind CSS reference',   url:'https://tailwindcss.com/docs', desc:'Utility-first CSS framework — atomic classes, config, responsive helpers.', folder:'design', tags:['docs','reference'], color:'cyan', date: Date.now() - 86400*1000*2 },
      { id:4,  title:'Dribbble — orchid collection', url:'https://dribbble.com/orchid', desc:'Curated Dribbble shots for admin dashboards, empty states and onboarding.', folder:'design', tags:['design','reference'], color:'pink', date: Date.now() - 86400*1000*3 },
      { id:5,  title:'Refactoring UI',           url:'https://refactoringui.com', desc:'Practical guide to building interfaces without a designer.', folder:'design', tags:['design','tutorial'], color:'rose', date: Date.now() - 86400*1000*5 },
      { id:6,  title:'Figma Community',          url:'https://figma.com/community', desc:'Free templates, plugins and design systems.', folder:'design', tags:['design','tools'], color:'violet', date: Date.now() - 86400*1000*6 },
      { id:7,  title:'CSS-Tricks — Complete Guide to Grid', url:'https://css-tricks.com/snippets/css/complete-guide-grid/', desc:'The canonical grid cheatsheet you keep bookmarking.', folder:'engineering', tags:['tutorial','reference'], color:'amber', date: Date.now() - 86400*1000*7 },
      { id:8,  title:'MDN — Web APIs',            url:'https://developer.mozilla.org/en-US/docs/Web/API', desc:'Every browser API you may ever need, well-documented.', folder:'engineering', tags:['docs','reference'], color:'indigo', date: Date.now() - 86400*1000*8 },
      { id:9,  title:'Stripe design principles',  url:'https://stripe.com/design', desc:'How Stripe thinks about product design and craft.', folder:'design', tags:['design','article'], color:'violet', date: Date.now() - 86400*1000*9 },
      { id:10, title:'Every.to — deep essays',    url:'https://every.to', desc:'Long-form writing on tech, work and craft.', folder:'reading', tags:['article'], color:'teal', date: Date.now() - 86400*1000*10 },
      { id:11, title:'Farnam Street',             url:'https://fs.blog', desc:'Mental models and decision-making.', folder:'reading', tags:['article'], color:'slate', date: Date.now() - 86400*1000*12 },
      { id:12, title:'Frontend Masters — TS deep dive', url:'https://frontendmasters.com/courses/typescript-v3/', desc:'Course notes and slides.', folder:'learning', tags:['tutorial','docs'], color:'rose', date: Date.now() - 86400*1000*14 },
      { id:13, title:'Excalidraw',               url:'https://excalidraw.com', desc:'Hand-drawn feel diagramming for whiteboard sessions.', folder:'product', tags:['tools'], color:'amber', date: Date.now() - 86400*1000*4 },
      { id:14, title:'Notion — product spec template', url:'https://notion.so/templates/spec', desc:'Template starting point for our product briefs.', folder:'product', tags:['tools','reference'], color:'slate', date: Date.now() - 86400*1000*3 },
      { id:15, title:'Linear — how we ship',      url:'https://linear.app/method', desc:'The Linear Method for building product.', folder:'product', tags:['article','reference'], color:'indigo', date: Date.now() - 86400*1000*11 },
      { id:16, title:'Coolors palette generator', url:'https://coolors.co', desc:'Fast palette exploration.', folder:'design', tags:['tools'], color:'teal', date: Date.now() - 86400*1000*16 },
      { id:17, title:'Unsplash for teams',        url:'https://unsplash.com', desc:'Royalty-free hero imagery.', folder:'design', tags:['tools'], color:'emerald', date: Date.now() - 86400*1000*17 },
      { id:18, title:'HN best of the week',       url:'https://news.ycombinator.com/best', desc:'Top HN threads from the past 7 days.', folder:'reading', tags:['article'], color:'amber', date: Date.now() - 86400*1000*20 },
      { id:19, title:'Josh W Comeau blog',        url:'https://joshwcomeau.com', desc:'Interactive posts on React, CSS and animation.', folder:'learning', tags:['tutorial','article'], color:'pink', date: Date.now() - 86400*1000*21 },
      { id:20, title:'A11Y Project checklist',    url:'https://a11yproject.com/checklist/', desc:'Accessibility checklist for every launch.', folder:'engineering', tags:['docs','reference'], color:'teal', date: Date.now() - 86400*1000*22 },
      { id:21, title:'Postman docs',              url:'https://learning.postman.com', desc:'API workspace docs and examples.', folder:'engineering', tags:['docs','tools'], color:'rose', date: Date.now() - 86400*1000*23 },
      { id:22, title:'GitHub CLI reference',      url:'https://cli.github.com/manual/', desc:'Command reference for gh.', folder:'engineering', tags:['docs','tools'], color:'slate', date: Date.now() - 86400*1000*24 },
      { id:23, title:'James Clear — Atomic Habits notes', url:'https://jamesclear.com/atomic-habits', desc:'Chapter summaries and cheat sheet.', folder:'reading', tags:['article'], color:'amber', date: Date.now() - 86400*1000*30 },
      { id:24, title:'Kent C. Dodds — Testing library', url:'https://testing-library.com/docs/', desc:'RTL patterns and best practice.', folder:'learning', tags:['docs','tutorial'], color:'cyan', date: Date.now() - 86400*1000*35 }
    ];

    var state = { folder: 'all', tag: null, view: 'grid', sort: 'recent', search: '', selection: [], expanded: { work: true, personal: true, archive: false } };

    var $tree = document.querySelector('[data-bm-tree]');
    var $tagCloud = document.querySelector('[data-bm-tagcloud]');
    var $grid = document.querySelector('[data-bm-grid]');
    var $search = document.querySelector('[data-bm-search]');
    var $sort = document.querySelector('[data-bm-sort]');
    var $viewToggle = document.querySelectorAll('[data-bm-view]');

    function countIn(folderId) {
      if (folderId === 'all') return bookmarks.length;
      // parent?
      for (var i = 0; i < FOLDERS.length; i++) {
        var f = FOLDERS[i];
        if (f.id === folderId) {
          var ids = f.children.map(function (c) { return c.id; });
          return bookmarks.filter(function (b) { return ids.indexOf(b.folder) > -1; }).length;
        }
      }
      return bookmarks.filter(function (b) { return b.folder === folderId; }).length;
    }

    function renderTree() {
      var html = '';
      html += '<button type="button" class="bm-tree__item ' + (state.folder === 'all' ? 'is-active' : '') + '" data-bm-folder="all">' +
              '<span class="bm-tree__caret bm-tree__caret--empty"></span>' +
              '<i class="bi bi-collection text-primary"></i><span>All bookmarks</span>' +
              '<span class="bm-tree__count">' + bookmarks.length + '</span></button>';
      html += '<ul class="bm-tree mt-2">';
      FOLDERS.forEach(function (f) {
        var open = !!state.expanded[f.id];
        var active = state.folder === f.id ? 'is-active' : '';
        html += '<li>' +
          '<button type="button" class="bm-tree__item ' + active + '" data-bm-folder="' + f.id + '" data-bm-toggle="' + f.id + '">' +
            '<i class="bi bi-caret-right-fill bm-tree__caret ' + (open ? 'is-open' : '') + (f.children.length ? '' : ' bm-tree__caret--empty') + '"></i>' +
            '<i class="bi ' + f.icon + '"></i><span>' + f.name + '</span>' +
            '<span class="bm-tree__count">' + countIn(f.id) + '</span>' +
          '</button>' +
          '<ul class="bm-tree__children ' + (open ? 'is-open' : '') + '">';
        f.children.forEach(function (c) {
          var cactive = state.folder === c.id ? 'is-active' : '';
          html += '<li><button type="button" class="bm-tree__item ' + cactive + '" data-bm-folder="' + c.id + '">' +
            '<span class="bm-tree__caret bm-tree__caret--empty"></span>' +
            '<span class="apps-dot apps-dot--' + c.color + '"></span><span>' + c.name + '</span>' +
            '<span class="bm-tree__count">' + countIn(c.id) + '</span>' +
            '</button></li>';
        });
        html += '</ul></li>';
      });
      html += '</ul>';
      $tree.innerHTML = html;
    }

    function tagFrequency() {
      var m = {};
      bookmarks.forEach(function (b) { b.tags.forEach(function (t) { m[t] = (m[t] || 0) + 1; }); });
      return m;
    }

    function renderTagCloud() {
      var freq = tagFrequency();
      var tags = Object.keys(freq).sort();
      $tagCloud.innerHTML = tags.map(function (t) {
        var lvl = freq[t] >= 8 ? 4 : freq[t] >= 5 ? 3 : freq[t] >= 3 ? 2 : 1;
        var active = state.tag === t ? ' is-active' : '';
        return '<button type="button" class="bm-tag' + active + '" data-freq="' + lvl + '" data-bm-tag="' + t + '">#' + t + ' <small class="opacity-75">' + freq[t] + '</small></button>';
      }).join('');
    }

    function filtered() {
      var list = bookmarks.slice();
      if (state.folder !== 'all') {
        // If parent folder, expand
        var p = FOLDERS.filter(function (f) { return f.id === state.folder; })[0];
        if (p) {
          var ids = p.children.map(function (c) { return c.id; });
          list = list.filter(function (b) { return ids.indexOf(b.folder) > -1; });
        } else {
          list = list.filter(function (b) { return b.folder === state.folder; });
        }
      }
      if (state.tag) list = list.filter(function (b) { return b.tags.indexOf(state.tag) > -1; });
      if (state.search) {
        var q = state.search.toLowerCase();
        list = list.filter(function (b) {
          return b.title.toLowerCase().indexOf(q) > -1 ||
                 b.url.toLowerCase().indexOf(q) > -1 ||
                 b.tags.some(function (t) { return t.indexOf(q) > -1; });
        });
      }
      if (state.sort === 'alpha') list.sort(function (a, b) { return a.title.localeCompare(b.title); });
      else if (state.sort === 'visits') list.sort(function (a, b) { return b.id - a.id; });
      else list.sort(function (a, b) { return b.date - a.date; });
      return list;
    }

    function folderName(id) {
      for (var i = 0; i < FOLDERS.length; i++) {
        var f = FOLDERS[i];
        if (f.id === id) return f.name;
        for (var j = 0; j < f.children.length; j++) if (f.children[j].id === id) return f.children[j].name;
      }
      return id;
    }

    function renderGrid() {
      var list = filtered();
      $grid.className = 'bm-grid' + (state.view === 'list' ? ' is-list' : '');
      if (!list.length) {
        $grid.innerHTML = '<div class="apps-empty"><i class="bi bi-bookmark"></i>No bookmarks match your filters</div>';
        return;
      }
      var html = '';
      list.forEach(function (b) {
        var initial = b.title[0].toUpperCase();
        var host = b.url.replace(/^https?:\/\//, '').replace(/\/.*/, '');
        var selected = state.selection.indexOf(b.id) > -1 ? ' is-selected' : '';
        var tagChips = b.tags.map(function (t) { return '<span class="apps-chip">#' + t + '</span>'; }).join(' ');
        html += '<div class="bm-card' + selected + '" data-bm-id="' + b.id + '">' +
          '<input type="checkbox" class="form-check-input bm-card__select" data-bm-check aria-label="Select"' + (selected ? ' checked' : '') + '>' +
          '<div class="bm-card__actions">' +
            '<a class="btn btn-sm btn-icon" href="' + escapeHtml(b.url) + '" target="_blank" rel="noopener" aria-label="Open"><i class="bi bi-box-arrow-up-right"></i></a>' +
            '<button type="button" class="btn btn-sm btn-icon" data-bm-copy aria-label="Copy link"><i class="bi bi-clipboard"></i></button>' +
            '<button type="button" class="btn btn-sm btn-icon" data-bm-edit aria-label="Edit" data-bs-toggle="modal" data-bs-target="#bmEditModal"><i class="bi bi-pencil"></i></button>' +
            '<button type="button" class="btn btn-sm btn-icon text-danger" data-bm-del aria-label="Delete"><i class="bi bi-trash"></i></button>' +
          '</div>' +
          '<div class="bm-card__head">' +
            '<div class="bm-fav c-' + b.color + '">' + initial + '</div>' +
            '<div class="bm-card__body">' +
              '<h4 class="bm-card__title">' + escapeHtml(b.title) + '</h4>' +
              '<a class="bm-card__url" href="' + escapeHtml(b.url) + '" target="_blank" rel="noopener">' + escapeHtml(host) + '</a>' +
            '</div>' +
          '</div>' +
          '<p class="bm-card__desc">' + escapeHtml(b.desc) + '</p>' +
          '<div class="d-flex flex-wrap gap-1">' + tagChips + '</div>' +
          '<div class="bm-card__foot">' +
            '<i class="bi bi-folder text-primary"></i>' + escapeHtml(folderName(b.folder)) +
            '<span class="ms-auto">' + relativeTime(b.date) + '</span>' +
          '</div>' +
        '</div>';
      });
      $grid.innerHTML = html;
    }

    function renderAll() { renderTree(); renderTagCloud(); renderGrid(); updateBulkBar(); }

    function updateBulkBar() {
      var bar = document.querySelector('[data-bm-bulk]');
      if (!bar) return;
      bar.classList.toggle('d-none', state.selection.length === 0);
      var cnt = bar.querySelector('[data-bm-selcount]');
      if (cnt) cnt.textContent = state.selection.length + ' selected';
    }

    $tree.addEventListener('click', function (e) {
      var caret = e.target.closest('.bm-tree__caret');
      var tog = e.target.closest('[data-bm-toggle]');
      if (caret && tog) {
        var id = tog.getAttribute('data-bm-toggle');
        state.expanded[id] = !state.expanded[id];
        renderTree();
        return;
      }
      var t = e.target.closest('[data-bm-folder]');
      if (!t) return;
      state.folder = t.getAttribute('data-bm-folder');
      state.tag = null;
      renderAll();
    });
    $tagCloud.addEventListener('click', function (e) {
      var t = e.target.closest('[data-bm-tag]');
      if (!t) return;
      var tag = t.getAttribute('data-bm-tag');
      state.tag = state.tag === tag ? null : tag;
      renderAll();
    });
    $grid.addEventListener('click', function (e) {
      var card = e.target.closest('.bm-card');
      if (!card) return;
      var id = parseInt(card.getAttribute('data-bm-id'), 10);
      if (e.target.closest('[data-bm-check]')) {
        var i = state.selection.indexOf(id);
        if (i > -1) state.selection.splice(i, 1); else state.selection.push(id);
        card.classList.toggle('is-selected');
        updateBulkBar();
        return;
      }
      if (e.target.closest('[data-bm-copy]')) {
        var b = bookmarks.filter(function (x) { return x.id === id; })[0];
        if (b && navigator.clipboard) navigator.clipboard.writeText(b.url).then(function () { orchidToast('Link copied', 'success'); }, function () { orchidToast('Copy failed', 'danger'); });
        else orchidToast('Link copied', 'success');
        return;
      }
      if (e.target.closest('[data-bm-del]')) {
        if (!window.confirm('Delete this bookmark?')) return;
        bookmarks = bookmarks.filter(function (x) { return x.id !== id; });
        renderAll();
        orchidToast('Bookmark deleted', 'danger');
      }
    });
    document.addEventListener('click', function (e) {
      var v = e.target.closest('[data-bm-view]');
      if (v) {
        state.view = v.getAttribute('data-bm-view');
        $viewToggle.forEach(function (b) { b.classList.remove('active'); });
        v.classList.add('active');
        renderGrid();
      }
      var bulk = e.target.closest('[data-bm-bulkaction]');
      if (bulk) {
        var kind = bulk.getAttribute('data-bm-bulkaction');
        if (kind === 'delete') {
          if (!window.confirm('Delete ' + state.selection.length + ' bookmarks?')) return;
          bookmarks = bookmarks.filter(function (b) { return state.selection.indexOf(b.id) === -1; });
          state.selection = [];
          renderAll();
          orchidToast('Bookmarks deleted', 'danger');
        } else if (kind === 'export') {
          orchidToast('Exported as HTML', 'success');
          state.selection = []; renderAll();
        } else if (kind === 'clear') {
          state.selection = []; renderAll();
        } else {
          orchidToast('Action queued', 'primary');
        }
      }
      var addSave = e.target.closest('[data-bm-add-save]');
      if (addSave) {
        var form = document.getElementById('bmAddForm');
        if (!form) return;
        var data = new FormData(form);
        var title = (data.get('title') || '').toString().trim();
        var url = (data.get('url') || '').toString().trim();
        if (!title || !url) { orchidToast('Title and URL required', 'danger'); return; }
        var maxId = bookmarks.reduce(function (m, b) { return b.id > m ? b.id : m; }, 0);
        bookmarks.unshift({
          id: maxId + 1,
          title: title, url: url,
          desc: (data.get('desc') || '').toString(),
          folder: (data.get('folder') || 'reading').toString(),
          tags: (data.get('tags') || '').toString().split(',').map(function (t) { return t.trim(); }).filter(Boolean),
          color: 'indigo', date: Date.now()
        });
        form.reset();
        var m = bootstrap.Modal.getInstance(document.getElementById('bmAddModal'));
        if (m) m.hide();
        renderAll(); orchidToast('Bookmark added', 'success');
      }
      var imp = e.target.closest('[data-bm-import]');
      if (imp) orchidToast('Bookmarks import ready', 'primary');
    });
    if ($search) $search.addEventListener('input', function () { state.search = $search.value.trim(); renderGrid(); });
    if ($sort) $sort.addEventListener('change', function () { state.sort = $sort.value; renderGrid(); });

    renderAll();
  }

  // =========================================================
  // KNOWLEDGE BASE PAGE
  // =========================================================
  function initKB() {
    var CATS = [
      { id: 'getting-started', name: 'Getting Started', icon: 'bi-rocket-takeoff', articles: [
        { id: 'gs-quickstart',   title: 'Quickstart: your first 15 minutes' },
        { id: 'gs-invite-team',  title: 'Inviting your team' },
        { id: 'gs-workspaces',   title: 'Understanding workspaces' }
      ]},
      { id: 'account-billing', name: 'Account & Billing', icon: 'bi-wallet2', articles: [
        { id: 'ab-billing-cycle', title: 'Understanding your billing cycle' },
        { id: 'ab-change-plan',   title: 'Upgrading or downgrading your plan' },
        { id: 'ab-invoices',      title: 'Where to find your invoices' }
      ]},
      { id: 'security', name: 'Security', icon: 'bi-shield-lock', articles: [
        { id: 'sec-sso-okta',    title: 'Setting up SSO with Okta' },
        { id: 'sec-2fa',         title: 'Enabling two-factor authentication' },
        { id: 'sec-audit-logs',  title: 'Reading your audit logs' }
      ]},
      { id: 'integrations', name: 'Integrations', icon: 'bi-plug', articles: [
        { id: 'int-slack',       title: 'Connecting Slack for notifications' },
        { id: 'int-webhooks',    title: 'Configuring outbound webhooks' }
      ]},
      { id: 'api',    name: 'API Reference', icon: 'bi-code-slash', articles: [
        { id: 'api-rate-limits', title: 'Rate limits and quotas' },
        { id: 'api-auth',        title: 'Authenticating API requests' }
      ]},
      { id: 'team',   name: 'Team Management', icon: 'bi-people', articles: [
        { id: 'tm-roles',        title: 'Roles and permissions overview' },
        { id: 'tm-inheritance',  title: 'How permissions inheritance works' }
      ]},
      { id: 'trouble',name: 'Troubleshooting', icon: 'bi-life-preserver', articles: [
        { id: 'tr-common-errors', title: 'Common error codes explained' },
        { id: 'tr-cant-login',    title: 'I can’t sign in — what to try' }
      ]}
    ];

    // long default article + short bodies for the rest
    var ARTICLES = {
      'sec-sso-okta': {
        cat: 'Security',
        title: 'How to set up SSO with your identity provider',
        author: 'Priya Menon',
        initials: 'PM',
        updated: 'Jan 12, 2026',
        reading: '5 min read',
        views: 4820,
        html: [
          '<p>Single Sign-On (SSO) lets your team sign in with your existing identity provider (IdP) — no more remembering another password. Orchid supports any SAML 2.0 IdP; this guide walks you through Okta specifically, but the same concepts apply to Azure AD, Google Workspace, OneLogin and JumpCloud.</p>',
          '<div class="kb-callout kb-callout--info"><i class="bi bi-info-circle"></i><div><strong>Before you begin</strong>SSO is available on <code>Business</code> and <code>Enterprise</code> plans. You will need admin access in both Orchid and your IdP.</div></div>',
          '<h2 id="prereq">Prerequisites</h2>',
          '<p>Make sure you have the following handy — it will save you jumping between tabs later.</p>',
          '<ul><li>An admin account in Orchid and in Okta.</li><li>Your Orchid workspace slug (found under <em>Settings &rarr; Workspace</em>).</li><li>The domain you want to enforce SSO on (e.g. <code>acme.com</code>).</li></ul>',
          '<h2 id="step1">Step 1 — Create an application in Okta</h2>',
          '<ol><li>In the Okta admin console open <strong>Applications</strong> and click <em>Create App Integration</em>.</li><li>Select <em>SAML 2.0</em> and click <em>Next</em>.</li><li>Name the app <code>Orchid</code>, upload the logo if you like, then hit <em>Next</em>.</li></ol>',
          '<h3 id="step1-fields">The fields you will need</h3>',
          '<p>Copy these values from Orchid&rsquo;s <em>Settings &rarr; Security &rarr; SSO</em> page.</p>',
          '<table><thead><tr><th>Okta field</th><th>Value from Orchid</th></tr></thead><tbody>' +
            '<tr><td>Single sign-on URL</td><td><code>https://sso.orchid.io/{workspace}/acs</code></td></tr>' +
            '<tr><td>Audience URI (SP Entity ID)</td><td><code>https://orchid.io/{workspace}</code></td></tr>' +
            '<tr><td>Name ID format</td><td><code>EmailAddress</code></td></tr>' +
            '<tr><td>Application username</td><td><code>Email</code></td></tr>' +
          '</tbody></table>',
          '<div class="kb-image"><i class="bi bi-image"></i> &nbsp;Screenshot — Okta SAML settings screen</div>',
          '<h2 id="step2">Step 2 — Map attributes</h2>',
          '<p>Add three attribute statements so Orchid knows who is signing in.</p>',
          '<pre><span class="cm"># attribute name  →  value</span>\n<span class="kw">email</span>      → user.email\n<span class="kw">firstName</span>  → user.firstName\n<span class="kw">lastName</span>   → user.lastName</pre>',
          '<div class="kb-callout kb-callout--tip"><i class="bi bi-lightbulb"></i><div><strong>Tip</strong>Send <code>groups</code> as a multi-value claim if you want to auto-provision team membership.</div></div>',
          '<h2 id="step3">Step 3 — Assign users and paste the metadata</h2>',
          '<ol><li>In Okta, open the <em>Assignments</em> tab and add the groups that should have Orchid access.</li><li>Back in Orchid&rsquo;s SSO page, paste the metadata URL from Okta.</li><li>Click <em>Test SSO</em>. You should be redirected to Okta and then back to Orchid as a signed-in user.</li></ol>',
          '<h3 id="step3-verify">Verify the round-trip</h3>',
          '<p>Try signing in from an incognito window. If you land on the dashboard, you are good.</p>',
          '<h2 id="step4">Step 4 — Enforce SSO on your domain</h2>',
          '<div class="kb-callout kb-callout--warning"><i class="bi bi-exclamation-triangle"></i><div><strong>Careful</strong>Once enforced, all users on <code>acme.com</code> can only sign in via Okta. Make sure at least one admin has a break-glass account on a different domain.</div></div>',
          '<p>Toggle <em>Enforce SSO</em> and add your domain to lock things down.</p>',
          '<h2 id="troubleshooting">Troubleshooting</h2>',
          '<h3 id="tb-loop">Redirect loop</h3>',
          '<p>Almost always caused by a stale session cookie. Ask the user to clear cookies for <code>orchid.io</code> and try again.</p>',
          '<h3 id="tb-mapping">Missing name after login</h3>',
          '<p>Double-check the <code>firstName</code> and <code>lastName</code> attribute statements in Okta.</p>',
          '<p class="mt-4">Still stuck? <a href="#">Open a support ticket</a> and include the SAML request/response from your browser dev tools.</p>'
        ].join('')
      },
      'gs-quickstart': { cat:'Getting Started', title:'Quickstart: your first 15 minutes', author:'Alex Kim', initials:'AK', updated:'Jan 2, 2026', reading:'3 min read', views:9021,
        html:'<p>Welcome to Orchid. In the next 15 minutes you will invite your team, connect your first integration, and ship your first workflow.</p><h2 id="tour">A quick tour</h2><p>Every screen has three regions: sidebar, workspace and inspector.</p><h2 id="invite">Invite your team</h2><p>Head to <em>Settings &rarr; Members</em> and paste up to 20 email addresses.</p><div class="kb-callout kb-callout--tip"><i class="bi bi-lightbulb"></i><div><strong>Tip</strong>Anyone on your verified domain can join automatically.</div></div><h2 id="ship">Ship something small</h2><p>Pick one workflow and follow it end-to-end.</p>' },
      'gs-invite-team': { cat:'Getting Started', title:'Inviting your team', author:'Alex Kim', initials:'AK', updated:'Jan 4, 2026', reading:'2 min read', views:3812,
        html:'<p>You can invite teammates by email, or share a magic link.</p><h2 id="email">By email</h2><p>Go to <em>Settings &rarr; Members &rarr; Invite</em>. Choose a role and hit send.</p><h2 id="link">By magic link</h2><p>Toggle <em>magic link</em>. Anyone with the link can join — regenerate it any time.</p>' },
      'gs-workspaces': { cat:'Getting Started', title:'Understanding workspaces', author:'Sarah Miller', initials:'SM', updated:'Jan 6, 2026', reading:'4 min read', views:2210,
        html:'<p>A workspace is the top-level container for your data, members and billing.</p><h2 id="one-vs-many">One workspace or many?</h2><p>Most teams need only one. Create additional workspaces for separate business units.</p>' },
      'ab-billing-cycle': { cat:'Account & Billing', title:'Understanding your billing cycle', author:'James Doe', initials:'JD', updated:'Dec 20, 2025', reading:'4 min read', views:5410,
        html:'<p>Your billing cycle starts on the day you first upgraded — not the 1st of the month.</p><h2 id="prorate">Proration</h2><p>Adding seats mid-cycle is prorated to the day.</p><h2 id="charges">When you are charged</h2><p>Cards are charged at the end of each cycle.</p>' },
      'ab-change-plan': { cat:'Account & Billing', title:'Upgrading or downgrading your plan', author:'James Doe', initials:'JD', updated:'Dec 22, 2025', reading:'3 min read', views:4103,
        html:'<p>You can change plans any time from <em>Settings &rarr; Billing</em>. Upgrades take effect immediately.</p><h2 id="down">Downgrades</h2><p>Downgrades take effect at the end of the current cycle so you do not lose paid time.</p>' },
      'ab-invoices': { cat:'Account & Billing', title:'Where to find your invoices', author:'James Doe', initials:'JD', updated:'Dec 15, 2025', reading:'2 min read', views:2988,
        html:'<p>Invoices live under <em>Settings &rarr; Billing &rarr; Invoices</em>. Download PDFs or share the receipt URL.</p>' },
      'sec-2fa': { cat:'Security', title:'Enabling two-factor authentication', author:'Priya Menon', initials:'PM', updated:'Jan 8, 2026', reading:'3 min read', views:6110,
        html:'<p>Turn on 2FA to add a second layer of protection.</p><h2 id="app">Authenticator app</h2><p>We recommend 1Password, Authy or Google Authenticator.</p><h2 id="recovery">Recovery codes</h2><p>Save your recovery codes offline.</p>' },
      'sec-audit-logs': { cat:'Security', title:'Reading your audit logs', author:'Priya Menon', initials:'PM', updated:'Jan 10, 2026', reading:'4 min read', views:1490,
        html:'<p>Audit logs record every workspace action — who, what, when.</p><h2 id="filter">Filtering</h2><p>Use the query bar to filter by actor, entity or IP.</p>' },
      'int-slack': { cat:'Integrations', title:'Connecting Slack for notifications', author:'Sarah Miller', initials:'SM', updated:'Jan 3, 2026', reading:'3 min read', views:3390,
        html:'<p>Route Orchid events into any Slack channel.</p><h2 id="install">Install the Slack app</h2><p>Open <em>Integrations</em> and click <em>Add to Slack</em>. Authorize the workspace.</p>' },
      'int-webhooks': { cat:'Integrations', title:'Configuring outbound webhooks', author:'Sarah Miller', initials:'SM', updated:'Dec 30, 2025', reading:'5 min read', views:1820,
        html:'<p>Webhooks push events to your own endpoint over HTTPS.</p><h2 id="signing">Signing requests</h2><p>Each request is signed with HMAC-SHA256 using your webhook secret.</p><pre><span class="fn">verify</span>(payload, header, secret)</pre>' },
      'api-rate-limits': { cat:'API Reference', title:'Rate limits and quotas', author:'Alex Kim', initials:'AK', updated:'Jan 11, 2026', reading:'4 min read', views:7233,
        html:'<p>API requests are rate-limited per workspace, not per token.</p><h2 id="limits">Current limits</h2><ul><li>Free: 60 req/min</li><li>Business: 600 req/min</li><li>Enterprise: custom</li></ul><h2 id="429">Handling 429s</h2><p>Back off and retry using the <code>Retry-After</code> header.</p>' },
      'api-auth': { cat:'API Reference', title:'Authenticating API requests', author:'Alex Kim', initials:'AK', updated:'Jan 5, 2026', reading:'3 min read', views:4801,
        html:'<p>Send your token as a Bearer header.</p><pre><span class="kw">GET</span> /v1/users\n<span class="kw">Authorization</span>: Bearer <span class="st">"sk_live_…"</span></pre>' },
      'tm-roles': { cat:'Team Management', title:'Roles and permissions overview', author:'Sarah Miller', initials:'SM', updated:'Jan 9, 2026', reading:'5 min read', views:2540,
        html:'<p>Orchid ships with four default roles: Owner, Admin, Editor and Viewer.</p><h2 id="custom">Custom roles</h2><p>Business plans and up can create custom roles with fine-grained permissions.</p>' },
      'tm-inheritance': { cat:'Team Management', title:'How permissions inheritance works', author:'Sarah Miller', initials:'SM', updated:'Jan 7, 2026', reading:'6 min read', views:1980,
        html:'<p>Permissions flow from workspace &rarr; project &rarr; item unless explicitly overridden.</p><h2 id="override">Overriding</h2><p>Any explicit setting on a child wins over the parent.</p>' },
      'tr-common-errors': { cat:'Troubleshooting', title:'Common error codes explained', author:'Ryan Green', initials:'RG', updated:'Dec 28, 2025', reading:'4 min read', views:3300,
        html:'<p>A quick reference to the errors you are most likely to hit.</p><h2 id="401">401 Unauthorized</h2><p>Your token is missing or expired.</p><h2 id="403">403 Forbidden</h2><p>Your token is valid but lacks permission.</p>' },
      'tr-cant-login': { cat:'Troubleshooting', title:'I can’t sign in — what to try', author:'Ryan Green', initials:'RG', updated:'Dec 27, 2025', reading:'3 min read', views:6120,
        html:'<p>Ninety percent of sign-in issues are a stale cookie. Try an incognito window first.</p><h2 id="reset">Reset your password</h2><p>Use the <em>Forgot password?</em> link on the sign-in page.</p>' }
    };

    var state = { articleId: 'sec-sso-okta', expanded: { security: true, 'getting-started': true } };

    var $tree = document.querySelector('[data-kb-tree]');
    var $viewer = document.querySelector('[data-kb-viewer]');
    var $search = document.querySelector('[data-kb-search]');
    var $searchDd = document.querySelector('[data-kb-searchdd]');

    function renderTree() {
      var html = '<ul class="kb-cat-tree">';
      CATS.forEach(function (c) {
        var open = !!state.expanded[c.id];
        html += '<li>' +
          '<button type="button" class="kb-cat ' + (open ? 'is-open' : '') + '" data-kb-cat="' + c.id + '">' +
            '<i class="bi bi-caret-right-fill kb-cat__caret ' + (open ? 'is-open' : '') + '"></i>' +
            '<i class="bi ' + c.icon + ' kb-cat__icon"></i><span>' + c.name + '</span>' +
            '<span class="kb-cat__count">' + c.articles.length + '</span>' +
          '</button>' +
          '<ul class="kb-articles-sub ' + (open ? 'is-open' : '') + '">';
        c.articles.forEach(function (a) {
          var active = state.articleId === a.id ? 'is-active' : '';
          html += '<li><button type="button" class="kb-article-link ' + active + '" data-kb-article="' + a.id + '">' +
            '<i class="bi bi-file-earmark-text"></i><span>' + a.title + '</span></button></li>';
        });
        html += '</ul></li>';
      });
      html += '</ul>';
      $tree.innerHTML = html;
    }

    function articleById(id) {
      var a = ARTICLES[id];
      if (a) return { id: id, meta: findMeta(id), full: a };
      return null;
    }
    function findMeta(id) {
      for (var i = 0; i < CATS.length; i++) {
        for (var j = 0; j < CATS[i].articles.length; j++) {
          if (CATS[i].articles[j].id === id) return CATS[i].articles[j];
        }
      }
      return {};
    }

    function related(id) {
      var thisCat = null;
      for (var i = 0; i < CATS.length; i++) {
        for (var j = 0; j < CATS[i].articles.length; j++) {
          if (CATS[i].articles[j].id === id) { thisCat = CATS[i]; break; }
        }
      }
      var pool = [];
      CATS.forEach(function (c) { c.articles.forEach(function (a) { if (a.id !== id) pool.push({ id: a.id, title: a.title, cat: c.name }); }); });
      if (thisCat) pool.sort(function (a, b) { return (b.cat === thisCat.name ? 1 : 0) - (a.cat === thisCat.name ? 1 : 0); });
      return pool.slice(0, 3);
    }

    function renderViewer() {
      var art = articleById(state.articleId);
      if (!art) { $viewer.innerHTML = '<div class="apps-empty"><i class="bi bi-book"></i>Select an article to read</div>'; return; }
      var a = art.full;
      var tocHtml = '';
      // Build TOC from h2/h3 markers
      var tmp = document.createElement('div'); tmp.innerHTML = a.html;
      var headings = tmp.querySelectorAll('h2, h3');
      if (headings.length) {
        tocHtml = '<div class="kb-toc"><div class="kb-toc__label">On this page</div><ul>';
        headings.forEach(function (h) {
          if (!h.id) h.id = 'sec-' + Math.random().toString(36).slice(2, 8);
          tocHtml += '<li class="' + h.tagName.toLowerCase() + '"><a href="#' + h.id + '" data-kb-toc>' + h.textContent + '</a></li>';
        });
        tocHtml += '</ul></div>';
      }
      var body = tmp.innerHTML;

      var relatedHtml = related(state.articleId).map(function (r) {
        return '<div class="kb-related__card" data-kb-related="' + r.id + '"><small>' + escapeHtml(r.cat) + '</small><p>' + escapeHtml(r.title) + '</p></div>';
      }).join('');

      $viewer.innerHTML =
        '<div class="kb-viewer">' +
          '<div>' +
            '<nav aria-label="breadcrumb"><ol class="breadcrumb small mb-2"><li class="breadcrumb-item"><a href="#">Help</a></li><li class="breadcrumb-item">' + escapeHtml(a.cat) + '</li><li class="breadcrumb-item active" aria-current="page">' + escapeHtml(a.title) + '</li></ol></nav>' +
            '<div class="kb-article-header">' +
              '<h1>' + escapeHtml(a.title) + '</h1>' +
              '<div class="kb-article-meta">' +
                '<span class="d-flex align-items-center gap-2"><span class="avatar bg-primary-subtle text-primary fw-semibold">' + a.initials + '</span>' + escapeHtml(a.author) + '</span>' +
                '<span><i class="bi bi-calendar3 me-1"></i>Updated ' + escapeHtml(a.updated) + '</span>' +
                '<span><i class="bi bi-clock me-1"></i>' + escapeHtml(a.reading) + '</span>' +
                '<span><i class="bi bi-eye me-1"></i>' + a.views.toLocaleString() + ' views</span>' +
                '<span class="ms-auto d-flex gap-2">' +
                  '<button type="button" class="btn btn-sm btn-outline-secondary" data-kb-copylink><i class="bi bi-link-45deg me-1"></i>Copy link</button>' +
                  '<button type="button" class="btn btn-sm btn-outline-secondary" data-kb-share><i class="bi bi-share me-1"></i>Share</button>' +
                '</span>' +
              '</div>' +
            '</div>' +
            '<article class="kb-body">' + body + '</article>' +
            '<div class="kb-feedback">' +
              '<p class="mb-1 fw-semibold">Was this article helpful?</p>' +
              '<p class="text-body-secondary small mb-2">124 of 138 people found this article helpful</p>' +
              '<div class="kb-feedback__buttons">' +
                '<button type="button" class="btn btn-outline-secondary" data-kb-vote="up"><i class="bi bi-hand-thumbs-up me-1"></i>Yes</button>' +
                '<button type="button" class="btn btn-outline-secondary" data-kb-vote="down"><i class="bi bi-hand-thumbs-down me-1"></i>No</button>' +
              '</div>' +
              '<p class="small mt-3 mb-0">Still stuck? <a href="#">Contact support</a></p>' +
            '</div>' +
            '<h3 class="mt-4 mb-2">Related articles</h3>' +
            '<div class="kb-related">' + relatedHtml + '</div>' +
            '<div class="kb-comments">' +
              '<h5 class="mb-3">Comments <span class="text-body-secondary fw-normal">(3)</span></h5>' +
              renderComment('Sarah Miller', 'SM', 'primary', '2 days ago', 'Great walkthrough. The bit about the break-glass account saved us — we almost locked ourselves out during a test rollout.') +
              renderComment('James Doe', 'JD', 'info', '5 days ago', 'Would love to see an Azure AD version of this. Happy to help draft it.') +
              renderComment('Ryan Green', 'RG', 'warning', 'Last week', 'Small thing: the SAML metadata URL format changed for Enterprise workspaces — worth a note.') +
            '</div>' +
          '</div>' +
          tocHtml +
        '</div>';

      // Scroll spy for TOC
      setupTocSpy();
    }

    function renderComment(name, initials, color, when, body) {
      return '<div class="kb-comment">' +
        '<span class="avatar avatar-sm bg-' + color + '-subtle text-' + color + ' fw-semibold">' + initials + '</span>' +
        '<div class="kb-comment__body">' +
          '<strong>' + escapeHtml(name) + '</strong>' +
          '<p>' + escapeHtml(body) + '</p>' +
          '<div class="kb-comment__meta"><span><i class="bi bi-clock me-1"></i>' + escapeHtml(when) + '</span>' +
          '<button type="button" class="btn btn-link btn-sm p-0"><i class="bi bi-reply me-1"></i>Reply</button>' +
          '<button type="button" class="btn btn-link btn-sm p-0"><i class="bi bi-hand-thumbs-up me-1"></i>Like</button></div>' +
        '</div></div>';
    }

    function setupTocSpy() {
      var main = $viewer.querySelector('.kb-body');
      var tocLinks = $viewer.querySelectorAll('[data-kb-toc]');
      if (!main || !tocLinks.length || !window.IntersectionObserver) return;
      var byId = {};
      tocLinks.forEach(function (a) { byId[a.getAttribute('href').slice(1)] = a; });
      var obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (!en.isIntersecting) return;
          var id = en.target.id;
          if (!byId[id]) return;
          tocLinks.forEach(function (l) { l.classList.remove('is-active'); });
          byId[id].classList.add('is-active');
        });
      }, { rootMargin: '-90px 0px -60% 0px' });
      main.querySelectorAll('h2, h3').forEach(function (h) { if (h.id) obs.observe(h); });
    }

    function renderAll() { renderTree(); renderViewer(); }

    $tree.addEventListener('click', function (e) {
      var cat = e.target.closest('[data-kb-cat]');
      if (cat) {
        var id = cat.getAttribute('data-kb-cat');
        state.expanded[id] = !state.expanded[id];
        renderTree();
        return;
      }
      var art = e.target.closest('[data-kb-article]');
      if (art) {
        state.articleId = art.getAttribute('data-kb-article');
        renderAll();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
    document.addEventListener('click', function (e) {
      var r = e.target.closest('[data-kb-related]');
      if (r) { state.articleId = r.getAttribute('data-kb-related'); renderAll(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
      var v = e.target.closest('[data-kb-vote]');
      if (v) {
        var vote = v.getAttribute('data-kb-vote');
        var group = v.closest('.kb-feedback__buttons');
        if (group) group.querySelectorAll('.btn').forEach(function (b) { b.classList.remove('is-selected'); });
        v.classList.add('is-selected');
        orchidToast(vote === 'up' ? 'Thanks — glad it helped!' : 'Thanks for the feedback', 'success');
      }
      if (e.target.closest('[data-kb-copylink]')) orchidToast('Link copied', 'success');
      if (e.target.closest('[data-kb-share]')) orchidToast('Share dialog opened', 'primary');
    });

    if ($search) {
      $search.addEventListener('input', function () {
        var q = $search.value.trim().toLowerCase();
        if (!q) { $searchDd.classList.remove('is-open'); $searchDd.innerHTML = ''; return; }
        var hits = [];
        for (var i = 0; i < CATS.length; i++) {
          for (var j = 0; j < CATS[i].articles.length; j++) {
            var a = CATS[i].articles[j];
            var full = ARTICLES[a.id];
            var hay = (a.title + ' ' + (full ? full.html.replace(/<[^>]+>/g, ' ') : '')).toLowerCase();
            if (hay.indexOf(q) > -1) hits.push({ id: a.id, title: a.title, cat: CATS[i].name, snippet: snippet(full ? full.html.replace(/<[^>]+>/g, ' ') : a.title, q) });
            if (hits.length >= 5) break;
          }
          if (hits.length >= 5) break;
        }
        if (!hits.length) { $searchDd.innerHTML = '<div class="kb-search-dd__item"><small>No results for “' + escapeHtml(q) + '”</small></div>'; }
        else {
          $searchDd.innerHTML = hits.map(function (h) {
            return '<button type="button" class="kb-search-dd__item" data-kb-search-hit="' + h.id + '"><strong>' + highlight(h.title, q) + '</strong><small>' + escapeHtml(h.cat) + ' · ' + h.snippet + '</small></button>';
          }).join('');
        }
        $searchDd.classList.add('is-open');
      });
      document.addEventListener('click', function (e) {
        if (!$searchDd.contains(e.target) && e.target !== $search) $searchDd.classList.remove('is-open');
        var hit = e.target.closest('[data-kb-search-hit]');
        if (hit) {
          state.articleId = hit.getAttribute('data-kb-search-hit');
          $search.value = ''; $searchDd.classList.remove('is-open');
          renderAll();
        }
      });
    }
    function snippet(text, q) {
      var i = text.toLowerCase().indexOf(q);
      if (i < 0) return escapeHtml(truncate(text, 80));
      var start = Math.max(0, i - 30);
      return escapeHtml(truncate(text.slice(start), 90));
    }
    function highlight(text, q) {
      var re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig');
      return escapeHtml(text).replace(re, '<mark>$1</mark>');
    }

    renderAll();
  }
})();
