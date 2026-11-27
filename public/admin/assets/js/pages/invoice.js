/* =====================================================
   Orchid - Invoice page interactions
   ===================================================== */
(function () {
  'use strict';

  const root = document.querySelector('[data-invoice-root]');
  if (!root) return;

  const bs = window.bootstrap;

  /* ---------- Toast helper ---------- */
  let toastRegion = document.querySelector('.invoice-toast-region');
  if (!toastRegion) {
    toastRegion = document.createElement('div');
    toastRegion.className = 'invoice-toast-region';
    toastRegion.setAttribute('aria-live', 'polite');
    document.body.appendChild(toastRegion);
  }

  function showToast(message, variant, timeout) {
    variant = variant || 'default';
    timeout = timeout || 3200;
    const icons = {
      default: 'bi-info-circle-fill',
      success: 'bi-check-circle-fill',
      danger: 'bi-exclamation-octagon-fill'
    };
    const t = document.createElement('div');
    t.className = 'invoice-toast invoice-toast--' + variant;
    t.innerHTML = '<i class="bi ' + (icons[variant] || icons.default) + '"></i><span>' + message + '</span>';
    toastRegion.appendChild(t);
    setTimeout(() => {
      t.classList.add('is-out');
      setTimeout(() => t.remove(), 250);
    }, timeout);
    return t;
  }

  /* ---------- Countdown ---------- */
  function updateCountdown() {
    const el = root.querySelector('[data-invoice-countdown]');
    if (!el) return;
    const dueStr = el.getAttribute('data-invoice-due');
    const status = (root.querySelector('[data-invoice-status]') || {}).getAttribute
      ? root.querySelector('[data-invoice-status]').getAttribute('data-invoice-status')
      : 'sent';
    if (status === 'paid') {
      el.classList.remove('is-overdue');
      el.classList.add('is-paid');
      el.innerHTML = '<i class="bi bi-check-circle-fill"></i>Paid in full';
      return;
    }
    if (status === 'void') {
      el.classList.remove('is-overdue', 'is-paid');
      el.innerHTML = '<i class="bi bi-slash-circle"></i>Invoice voided';
      return;
    }
    const due = new Date(dueStr + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diff = Math.round((due - today) / (1000 * 60 * 60 * 24));
    if (diff < 0) {
      el.classList.add('is-overdue');
      el.classList.remove('is-paid');
      el.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>Overdue by ' + Math.abs(diff) + ' day' + (Math.abs(diff) === 1 ? '' : 's');
    } else if (diff === 0) {
      el.classList.remove('is-overdue', 'is-paid');
      el.innerHTML = '<i class="bi bi-clock"></i>Due today';
    } else {
      el.classList.remove('is-overdue', 'is-paid');
      el.innerHTML = '<i class="bi bi-clock"></i>Due in ' + diff + ' day' + (diff === 1 ? '' : 's');
    }
  }
  updateCountdown();

  /* ---------- Log timeline helper ---------- */
  function addLogEntry(text, meta, kind) {
    const list = root.querySelector('[data-invoice-log]');
    if (!list) return;
    const li = document.createElement('li');
    if (kind) li.className = 'is-' + kind;
    li.innerHTML = '<span>' + text + '</span><span class="invoice-log__meta">' + meta + '</span>';
    list.insertBefore(li, list.firstChild);
  }

  function todayStr() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + day;
  }

  /* ---------- Action buttons ---------- */
  // Download PDF
  const downloadBtn = root.querySelector('[data-invoice-action="download"]');
  if (downloadBtn) {
    downloadBtn.addEventListener('click', () => {
      showToast('Preparing PDF…', 'default', 1900);
      downloadBtn.disabled = true;
      setTimeout(() => {
        showToast('Downloaded invoice-ORB-2026-0284.pdf', 'success');
        downloadBtn.disabled = false;
      }, 2000);
    });
  }

  // Print
  const printBtn = root.querySelector('[data-invoice-action="print"]');
  if (printBtn) {
    printBtn.addEventListener('click', () => window.print());
  }

  // Duplicate
  const dupBtn = root.querySelector('[data-invoice-action="duplicate"]');
  if (dupBtn) {
    dupBtn.addEventListener('click', () => {
      showToast('Duplicated as ORB-2026-0285 (Draft)', 'success');
    });
  }

  /* ---------- Email modal ---------- */
  const emailModalEl = document.getElementById('invoiceEmailModal');
  const emailForm = document.getElementById('invoiceEmailForm');
  if (emailModalEl && emailForm && bs) {
    const modal = new bs.Modal(emailModalEl);
    const openBtn = root.querySelector('[data-invoice-action="email"]');
    if (openBtn) openBtn.addEventListener('click', () => modal.show());
    emailForm.addEventListener('submit', (e) => {
      e.preventDefault();
      if (!emailForm.checkValidity()) {
        emailForm.classList.add('was-validated');
        return;
      }
      const to = emailForm.querySelector('#invEmailTo').value.trim();
      modal.hide();
      emailForm.classList.remove('was-validated');
      addLogEntry('Sent to ' + to, todayStr(), null);
      showToast('Invoice sent to ' + to, 'success');
    });
  }

  /* ---------- Recurring modal ---------- */
  const recModalEl = document.getElementById('invoiceRecurringModal');
  const recForm = document.getElementById('invoiceRecurringForm');
  if (recModalEl && recForm && bs) {
    const modal = new bs.Modal(recModalEl);
    const openBtn = root.querySelector('[data-invoice-action="recurring"]');
    if (openBtn) openBtn.addEventListener('click', () => modal.show());
    recForm.addEventListener('submit', (e) => {
      e.preventDefault();
      if (!recForm.checkValidity()) {
        recForm.classList.add('was-validated');
        return;
      }
      const freq = recForm.querySelector('#invRecFreq').value;
      modal.hide();
      recForm.classList.remove('was-validated');
      showToast('Converted to recurring (' + freq + ')', 'success');
    });
  }

  /* ---------- Mark as Paid modal ---------- */
  const paidModalEl = document.getElementById('invoicePaidModal');
  if (paidModalEl && bs) {
    const modal = new bs.Modal(paidModalEl);
    const openBtn = root.querySelector('[data-invoice-action="paid"]');
    const confirmBtn = paidModalEl.querySelector('[data-invoice-confirm="paid"]');
    if (openBtn) openBtn.addEventListener('click', () => modal.show());
    if (confirmBtn) {
      confirmBtn.addEventListener('click', () => {
        // Update status badge
        const paper = root.querySelector('.invoice-paper');
        const docBadge = root.querySelector('[data-invoice-doc-badge]');
        const sideBadge = root.querySelector('[data-invoice-side-badge]');
        const statusEl = root.querySelector('[data-invoice-status]');
        const balanceEl = root.querySelector('[data-invoice-balance]');
        const amountLabel = root.querySelector('[data-invoice-side-amount-label]');
        const sideAmount = root.querySelector('[data-invoice-side-amount]');
        if (statusEl) statusEl.setAttribute('data-invoice-status', 'paid');
        if (paper) { paper.classList.remove('is-void'); paper.classList.add('is-paid'); }
        [docBadge, sideBadge].forEach(b => {
          if (!b) return;
          b.className = 'invoice-status-badge invoice-status-badge--paid';
          b.innerHTML = '<i class="bi bi-check-circle-fill"></i>Paid';
        });
        if (balanceEl) balanceEl.textContent = '$0.00';
        if (sideAmount) sideAmount.textContent = '$0.00';
        if (amountLabel) amountLabel.textContent = 'Balance due';
        // disable send/paid actions
        root.querySelectorAll('[data-invoice-action="paid"], [data-invoice-action="void"]').forEach(b => b.setAttribute('disabled', 'disabled'));
        addLogEntry('Marked as paid', todayStr(), 'paid');
        updateCountdown();
        modal.hide();
        showToast('Invoice marked as paid', 'success');
      });
    }
  }

  /* ---------- Void modal ---------- */
  const voidModalEl = document.getElementById('invoiceVoidModal');
  if (voidModalEl && bs) {
    const modal = new bs.Modal(voidModalEl);
    const openBtn = root.querySelector('[data-invoice-action="void"]');
    const confirmBtn = voidModalEl.querySelector('[data-invoice-confirm="void"]');
    const input = voidModalEl.querySelector('#invVoidInput');
    function syncBtn() {
      if (!confirmBtn || !input) return;
      confirmBtn.disabled = input.value.trim().toUpperCase() !== 'VOID';
    }
    if (input) input.addEventListener('input', syncBtn);
    if (openBtn) openBtn.addEventListener('click', () => {
      if (input) input.value = '';
      syncBtn();
      modal.show();
    });
    if (confirmBtn) {
      confirmBtn.addEventListener('click', () => {
        if (confirmBtn.disabled) return;
        const paper = root.querySelector('.invoice-paper');
        const docBadge = root.querySelector('[data-invoice-doc-badge]');
        const sideBadge = root.querySelector('[data-invoice-side-badge]');
        const statusEl = root.querySelector('[data-invoice-status]');
        if (statusEl) statusEl.setAttribute('data-invoice-status', 'void');
        if (paper) { paper.classList.add('is-void'); paper.classList.remove('is-paid'); }
        [docBadge, sideBadge].forEach(b => {
          if (!b) return;
          b.className = 'invoice-status-badge invoice-status-badge--void';
          b.innerHTML = '<i class="bi bi-slash-circle"></i>Void';
        });
        root.querySelectorAll('[data-invoice-action="paid"], [data-invoice-action="void"], [data-invoice-action="email"], [data-invoice-action="recurring"]')
          .forEach(b => b.setAttribute('disabled', 'disabled'));
        addLogEntry('Invoice voided', todayStr(), null);
        updateCountdown();
        modal.hide();
        showToast('Invoice has been voided', 'danger');
      });
    }
  }

  /* ---------- Attach files drop zone ---------- */
  const drop = root.querySelector('[data-invoice-drop]');
  const filesList = root.querySelector('[data-invoice-files]');
  const fileInput = root.querySelector('[data-invoice-file-input]');

  function humanSize(bytes) {
    if (!bytes) return '—';
    const u = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    let n = bytes;
    while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
    return n.toFixed(n < 10 && i > 0 ? 1 : 0) + ' ' + u[i];
  }

  function iconFor(name) {
    const ext = (name.split('.').pop() || '').toLowerCase();
    if (ext === 'pdf') return 'bi-file-earmark-pdf';
    if (['xls', 'xlsx', 'csv'].indexOf(ext) !== -1) return 'bi-file-earmark-spreadsheet';
    if (['doc', 'docx'].indexOf(ext) !== -1) return 'bi-file-earmark-word';
    if (['png', 'jpg', 'jpeg', 'gif', 'webp'].indexOf(ext) !== -1) return 'bi-file-earmark-image';
    if (['zip', 'rar', '7z'].indexOf(ext) !== -1) return 'bi-file-earmark-zip';
    return 'bi-file-earmark';
  }

  function addFile(name, size) {
    if (!filesList) return;
    const li = document.createElement('li');
    li.innerHTML =
      '<i class="bi ' + iconFor(name) + '"></i>' +
      '<span class="invoice-files__name">' + name + '</span>' +
      '<span class="invoice-files__size">' + humanSize(size) + '</span>' +
      '<button type="button" class="invoice-files__remove" aria-label="Remove"><i class="bi bi-x-lg"></i></button>';
    li.querySelector('.invoice-files__remove').addEventListener('click', () => li.remove());
    filesList.appendChild(li);
  }

  // Attach handlers to any pre-rendered remove buttons
  if (filesList) {
    filesList.querySelectorAll('.invoice-files__remove').forEach(btn => {
      btn.addEventListener('click', () => btn.closest('li').remove());
    });
  }

  if (drop) {
    drop.addEventListener('click', () => { if (fileInput) fileInput.click(); });
    drop.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); if (fileInput) fileInput.click(); }
    });
    ['dragenter', 'dragover'].forEach(evt => {
      drop.addEventListener(evt, (e) => { e.preventDefault(); drop.classList.add('is-drag'); });
    });
    ['dragleave', 'drop'].forEach(evt => {
      drop.addEventListener(evt, (e) => { e.preventDefault(); drop.classList.remove('is-drag'); });
    });
    drop.addEventListener('drop', (e) => {
      const files = e.dataTransfer && e.dataTransfer.files;
      if (!files || !files.length) return;
      Array.prototype.forEach.call(files, f => addFile(f.name, f.size));
      showToast(files.length + ' file' + (files.length === 1 ? '' : 's') + ' attached', 'success');
    });
  }
  if (fileInput) {
    fileInput.addEventListener('change', (e) => {
      const files = e.target.files;
      if (!files || !files.length) return;
      Array.prototype.forEach.call(files, f => addFile(f.name, f.size));
      showToast(files.length + ' file' + (files.length === 1 ? '' : 's') + ' attached', 'success');
      fileInput.value = '';
    });
  }
})();
