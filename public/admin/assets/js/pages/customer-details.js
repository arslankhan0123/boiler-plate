/* Orchid — Customer Details */
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    var B = window.BizExtras;
    if (!B) return;

    // Paint tokens
    document.querySelectorAll('[data-bg]').forEach(function (el) { el.style.background = el.getAttribute('data-bg'); });
    document.querySelectorAll('[data-fg]').forEach(function (el) { el.style.color = el.getAttribute('data-fg'); });

    // Tab switching
    var tabs = document.querySelectorAll('[data-cdet-tab]');
    tabs.forEach(function (t) {
      t.addEventListener('click', function () {
        tabs.forEach(function (x) { x.classList.remove('is-active'); });
        t.classList.add('is-active');
        var key = t.getAttribute('data-cdet-tab');
        document.querySelectorAll('[data-cdet-panel]').forEach(function (p) {
          p.hidden = p.getAttribute('data-cdet-panel') !== key;
        });
      });
    });

    // Populate activity timeline
    var acts = [
      { icon: 'bi-cart-check', bg: 'rgba(79,70,229,.14)', fg: '#4f46e5', title: 'Placed order ORD-9821', sub: '$4,820 · 12 items', date: '2h ago' },
      { icon: 'bi-life-preserver', bg: 'rgba(245,158,11,.14)', fg: '#b45309', title: 'Opened support ticket TCK-9812', sub: 'SSO login failure', date: '6h ago' },
      { icon: 'bi-envelope-open', bg: 'rgba(14,165,233,.14)', fg: '#0284c7', title: 'Opened email · Q3 Renewal Options', sub: 'Clicked pricing link', date: '1d ago' },
      { icon: 'bi-camera-video', bg: 'rgba(139,92,246,.14)', fg: '#7c3aed', title: 'Attended discovery call', sub: '32 min · with Alex Kim, Sarah Miller', date: '2d ago' },
      { icon: 'bi-file-earmark-text', bg: 'rgba(16,185,129,.14)', fg: '#059669', title: 'Downloaded proposal.pdf', sub: 'Q3 Diagnostic Renewal', date: '3d ago' },
      { icon: 'bi-star-fill', bg: 'rgba(245,158,11,.14)', fg: '#b45309', title: 'Left 5-star review', sub: '"Consistently reliable service."', date: '5d ago' },
      { icon: 'bi-chat-dots', bg: 'rgba(14,165,233,.14)', fg: '#0284c7', title: 'Chat conversation', sub: 'Renewal timeline questions', date: '1w ago' }
    ];
    var tl = document.querySelector('[data-cdet-timeline]');
    if (tl) tl.innerHTML = acts.map(function (a) {
      return '<li class="cdet-timeline__item"><span class="cdet-timeline__ico" style="background:' + a.bg + ';color:' + a.fg + '"><i class="bi ' + a.icon + '"></i></span><p class="mb-0 fw-semibold">' + a.title + '</p><small class="text-body-secondary">' + a.sub + ' · ' + a.date + '</small></li>';
    }).join('');

    // Orders
    var orders = [
      { id: 'ORD-9821', date: '2026-07-22', items: 12, total: 4820, status: 'Shipped' },
      { id: 'ORD-9720', date: '2026-07-14', items:  8, total: 2680, status: 'Delivered' },
      { id: 'ORD-9611', date: '2026-07-02', items: 24, total: 8940, status: 'Delivered' },
      { id: 'ORD-9502', date: '2026-06-20', items:  6, total: 1420, status: 'Delivered' },
      { id: 'ORD-9401', date: '2026-06-08', items: 18, total: 6210, status: 'Delivered' },
      { id: 'ORD-9302', date: '2026-05-27', items: 10, total: 3180, status: 'Delivered' },
      { id: 'ORD-9210', date: '2026-05-15', items: 14, total: 4780, status: 'Delivered' },
      { id: 'ORD-9101', date: '2026-04-30', items:  4, total:  980, status: 'Delivered' },
      { id: 'ORD-9022', date: '2026-04-11', items: 22, total: 7620, status: 'Delivered' },
      { id: 'ORD-8918', date: '2026-03-28', items: 16, total: 5440, status: 'Delivered' }
    ];
    var oTable = document.querySelector('[data-cdet-orders-table] tbody');
    if (oTable) oTable.innerHTML = orders.map(function (o) {
      return '<tr><td><strong>' + o.id + '</strong></td><td>' + B.fmtDate(o.date) + '</td><td>' + o.items + '</td><td class="fw-semibold">' + B.fmtMoney(o.total) + '</td><td><span class="badge bg-success-subtle text-success">' + o.status + '</span></td><td class="text-end"><a href="#" class="small">Invoice</a></td></tr>';
    }).join('');

    // Comms
    var comms = [
      { type: 'email', title: 'Q3 Renewal Options', prev: 'Michael, here are the three renewal packages we discussed…', date: 'Today, 10:24 AM', dir: 'Sent' },
      { type: 'call',  title: 'Discovery call · 32 min', prev: 'Michael confirmed budget alignment. Interested in expanding to 6 more locations.', date: 'Yesterday, 3:00 PM', dir: 'Inbound' },
      { type: 'chat',  title: 'Support chat', prev: 'Question about SSO configuration for new offices', date: 'Jul 18', dir: 'Inbound' },
      { type: 'email', title: 'Onboarding checklist', prev: 'Please find attached the phase-2 rollout schedule…', date: 'Jul 12', dir: 'Sent' },
      { type: 'meeting', title: 'Executive review', prev: 'Presented product roadmap, healthcare compliance updates', date: 'Jul 05', dir: 'Meeting' }
    ];
    var iconMap = { email: 'bi-envelope', call: 'bi-telephone', chat: 'bi-chat-dots', meeting: 'bi-people' };
    var colorMap = { email: '#4f46e5', call: '#0ea5e9', chat: '#22c55e', meeting: '#f97316' };
    var cCont = document.querySelector('[data-cdet-comms]');
    if (cCont) cCont.innerHTML = comms.map(function (c) {
      return '<li class="d-flex gap-3 py-2 border-bottom"><span class="biz-avatar-circle md" style="background:' + colorMap[c.type] + '"><i class="bi ' + iconMap[c.type] + '"></i></span><div class="flex-grow-1"><div class="d-flex justify-content-between"><p class="mb-0 fw-semibold">' + c.title + '</p><small class="text-body-secondary">' + c.date + '</small></div><small class="text-body-secondary d-block">' + c.prev + '</small><span class="badge bg-primary-subtle text-primary mt-1">' + c.dir + '</span></div></li>';
    }).join('');

    // Files
    var files = [
      { name: 'Q3-Renewal-Proposal.pdf', size: '2.4 MB', ico: 'bi-file-earmark-pdf', color: '#ef4444' },
      { name: 'MSA-2026-signed.pdf', size: '480 KB', ico: 'bi-file-earmark-pdf', color: '#ef4444' },
      { name: 'Rollout-Timeline.xlsx', size: '128 KB', ico: 'bi-file-earmark-excel', color: '#22c55e' },
      { name: 'Compliance-Audit.docx', size: '316 KB', ico: 'bi-file-earmark-word', color: '#0ea5e9' },
      { name: 'Roadmap-Slides.pptx', size: '4.1 MB', ico: 'bi-file-earmark-slides', color: '#f97316' },
      { name: 'Logo-Assets.zip', size: '12.8 MB', ico: 'bi-file-earmark-zip', color: '#8b5cf6' }
    ];
    var fCont = document.querySelector('[data-cdet-files]');
    if (fCont) fCont.innerHTML = files.map(function (f) {
      return '<div class="col-md-6"><a href="#" class="cdet-file text-decoration-none"><span class="cdet-file__ico" style="background:' + f.color + '20;color:' + f.color + '"><i class="bi ' + f.ico + '"></i></span><div class="flex-grow-1"><p class="mb-0 fw-semibold small">' + f.name + '</p><small class="text-body-secondary">' + f.size + '</small></div><i class="bi bi-download text-body-secondary"></i></a></div>';
    }).join('');

    // Notes
    var notes = [
      { author: 'Alex Kim', body: 'Michael confirmed budget of $340K for phase-2 rollout. Wants proposal by end of month.', date: '2h ago' },
      { author: 'Sarah Miller', body: 'Legal reviewed MSA amendment — 2 minor redlines on data-residency clause. Sent to Michael for signature.', date: 'Yesterday' },
      { author: 'James Doe', body: 'Solutions engineering finished the SSO integration POC. All test cases passing.', date: '3d ago' }
    ];
    var nCont = document.querySelector('[data-cdet-notes]');
    function renderNotes() {
      nCont.innerHTML = notes.map(function (n, i) {
        return '<div class="cdet-note"><div class="cdet-note__head"><div class="d-flex align-items-center gap-2">' + B.renderAvatar(n.author, 'xs') + '<strong class="small">' + B.escapeHtml(n.author) + '</strong></div><div class="d-flex align-items-center gap-2"><small class="text-body-secondary">' + n.date + '</small><button class="biz-icon-btn" type="button" data-note-del="' + i + '" aria-label="Delete"><i class="bi bi-trash"></i></button></div></div><p class="mb-0 small">' + B.escapeHtml(n.body) + '</p></div>';
      }).join('');
      B.bindAvatarColors(nCont);
    }
    if (nCont) renderNotes();

    // Note form
    var noteForm = document.querySelector('[data-cdet-note-form]');
    if (noteForm) noteForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var t = document.querySelector('[data-cdet-note-title]').value.trim();
      var b = document.querySelector('[data-cdet-note-body]').value.trim();
      notes.unshift({ author: 'You', body: (t ? t + ': ' : '') + b, date: 'just now' });
      renderNotes();
      bootstrap.Modal.getInstance(document.getElementById('cdetNoteModal')).hide();
      noteForm.reset();
      B.toast('Note added', 'success');
      // switch to notes tab
      document.querySelector('[data-cdet-tab="notes"]').click();
    });
    if (nCont) nCont.addEventListener('click', function (e) {
      var d = e.target.closest('[data-note-del]');
      if (!d) return;
      notes.splice(+d.getAttribute('data-note-del'), 1);
      renderNotes();
      B.toast('Note deleted', 'info');
    });

    // Header action buttons
    document.querySelector('[data-cdet-message]').addEventListener('click', function () { B.toast('Message composer opened', 'info'); });
    document.querySelector('[data-cdet-call]').addEventListener('click', function () { B.toast('Dialing +1 617 555 0188…', 'info'); });
  });
})();
