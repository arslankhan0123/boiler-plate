// =====================================================
// Orchid - Utility / Status pages (404, 500, coming-soon, maintenance)
// Namespaced with data-status-* hooks
// =====================================================

(function () {
  'use strict';

  // ---------- Toast helper (namespaced) ----------
  function ensureToaster() {
    var el = document.querySelector('[data-status-toaster]');
    if (el) return el;
    el = document.createElement('div');
    el.setAttribute('data-status-toaster', '');
    el.className = 'status-toaster';
    el.setAttribute('aria-live', 'polite');
    el.setAttribute('aria-atomic', 'true');
    document.body.appendChild(el);
    return el;
  }

  function showToast(message, variant) {
    if (!window.bootstrap || !window.bootstrap.Toast) {
      // Fallback if bootstrap isn't loaded yet
      console.log('[status]', message);
      return;
    }
    var toaster = ensureToaster();
    var wrap = document.createElement('div');
    var accent = variant || 'primary';
    var icon = 'bi-info-circle-fill';
    if (accent === 'success') icon = 'bi-check-circle-fill';
    else if (accent === 'danger') icon = 'bi-exclamation-octagon-fill';
    else if (accent === 'warning') icon = 'bi-exclamation-triangle-fill';

    wrap.className = 'toast align-items-center border-0 text-bg-' + accent;
    wrap.setAttribute('role', 'status');
    wrap.setAttribute('aria-live', 'polite');
    wrap.setAttribute('aria-atomic', 'true');
    wrap.innerHTML =
      '<div class="d-flex">' +
        '<div class="toast-body d-flex align-items-center gap-2">' +
          '<i class="bi ' + icon + '"></i>' +
          '<span></span>' +
        '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
      '</div>';
    wrap.querySelector('span').textContent = message;
    toaster.appendChild(wrap);
    var t = new window.bootstrap.Toast(wrap, { delay: 3200 });
    wrap.addEventListener('hidden.bs.toast', function () { wrap.remove(); });
    t.show();
  }

  // ---------- 404 - Report issue modal ----------
  function init404() {
    var form = document.querySelector('[data-status-report-form]');
    if (!form) return;
    form.addEventListener('submit', function (evt) {
      evt.preventDefault();
      if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
      }
      var modalEl = document.getElementById('reportIssueModal');
      var modal = modalEl && window.bootstrap ? window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl) : null;
      if (modal) modal.hide();
      form.reset();
      form.classList.remove('was-validated');
      showToast('Thanks! Your report has been sent to the team.', 'success');
    });
  }

  // ---------- 500 - Retry & incident id ----------
  function init500() {
    var incidentEl = document.querySelector('[data-status-incident-id]');
    if (incidentEl) {
      var hex = 'ABCDEF0123456789';
      function block(n) {
        var out = '';
        for (var i = 0; i < n; i++) out += hex.charAt(Math.floor(Math.random() * hex.length));
        return out;
      }
      incidentEl.textContent = 'INC-' + block(4) + '-' + block(4);
    }

    var retryBtn = document.querySelector('[data-status-retry]');
    if (!retryBtn) return;
    var clickCount = 0;
    var originalLabel = retryBtn.innerHTML;
    retryBtn.addEventListener('click', function () {
      if (retryBtn.disabled) return;
      retryBtn.disabled = true;
      retryBtn.innerHTML = '<span class="status-spinner" aria-hidden="true"></span>Retrying…';
      setTimeout(function () {
        clickCount += 1;
        // alternate: odd clicks succeed, even clicks fail
        if (clickCount % 2 === 1) {
          showToast('Retry successful — reloading connection…', 'success');
        } else {
          showToast('Still unavailable. Our team is investigating.', 'danger');
        }
        retryBtn.innerHTML = originalLabel;
        retryBtn.disabled = false;
      }, 2000);
    });
  }

  // ---------- Coming soon - countdown + subscribe + social ----------
  function initSoon() {
    var root = document.querySelector('[data-status-countdown]');
    var target = null;

    if (root) {
      var targetAttr = root.getAttribute('data-status-target');
      if (targetAttr) {
        var parsed = new Date(targetAttr);
        if (!isNaN(parsed.getTime())) target = parsed;
      }
      if (!target) {
        target = new Date();
        target.setDate(target.getDate() + 30);
      }

      var daysEl = root.querySelector('[data-status-days]');
      var hoursEl = root.querySelector('[data-status-hours]');
      var minsEl = root.querySelector('[data-status-minutes]');
      var secsEl = root.querySelector('[data-status-seconds]');

      function pad(n) { return (n < 10 ? '0' : '') + n; }
      function tick() {
        var now = new Date().getTime();
        var diff = target.getTime() - now;
        if (diff <= 0) {
          if (daysEl) daysEl.textContent = '00';
          if (hoursEl) hoursEl.textContent = '00';
          if (minsEl) minsEl.textContent = '00';
          if (secsEl) secsEl.textContent = '00';
          return;
        }
        var d = Math.floor(diff / (1000 * 60 * 60 * 24));
        var h = Math.floor((diff / (1000 * 60 * 60)) % 24);
        var m = Math.floor((diff / (1000 * 60)) % 60);
        var s = Math.floor((diff / 1000) % 60);
        if (daysEl) daysEl.textContent = pad(d);
        if (hoursEl) hoursEl.textContent = pad(h);
        if (minsEl) minsEl.textContent = pad(m);
        if (secsEl) secsEl.textContent = pad(s);
      }
      tick();
      setInterval(tick, 1000);
    }

    var form = document.querySelector('[data-status-subscribe]');
    if (form) {
      var input = form.querySelector('[data-status-subscribe-input]');
      var feedback = form.querySelector('[data-status-subscribe-feedback]');
      var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
      form.addEventListener('submit', function (evt) {
        evt.preventDefault();
        var value = (input && input.value || '').trim();
        if (!emailRe.test(value)) {
          if (feedback) {
            feedback.textContent = 'Please enter a valid email address.';
            feedback.classList.remove('is-ok');
          }
          if (input) input.focus();
          return;
        }
        if (feedback) {
          feedback.textContent = "You're on the list. Watch your inbox!";
          feedback.classList.add('is-ok');
        }
        if (input) input.value = '';
        showToast("You'll be the first to know!", 'success');
      });
    }

    var socials = document.querySelectorAll('[data-status-social]');
    socials.forEach(function (btn) {
      btn.addEventListener('click', function (evt) {
        evt.preventDefault();
        var name = btn.getAttribute('data-status-social') || 'the platform';
        showToast('Redirecting to ' + name + '…', 'primary');
      });
    });
  }

  // ---------- Maintenance - progress + ETA + tasks ----------
  function initMaintenance() {
    var bar = document.querySelector('[data-status-progress-bar]');
    var valueEl = document.querySelector('[data-status-progress-value]');
    if (bar) {
      var target = parseInt(bar.getAttribute('data-status-progress-target') || '68', 10);
      requestAnimationFrame(function () {
        setTimeout(function () {
          bar.style.width = target + '%';
        }, 200);
      });
      // Animate counter to match
      var current = 0;
      var start = performance.now();
      var duration = 1500;
      function step(now) {
        var t = Math.min(1, (now - start) / duration);
        // ease-out
        var eased = 1 - Math.pow(1 - t, 3);
        current = Math.round(target * eased);
        if (valueEl) valueEl.textContent = current + '%';
        if (t < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    }

    var etaEl = document.querySelector('[data-status-eta]');
    if (etaEl) {
      var hoursAhead = parseFloat(etaEl.getAttribute('data-status-eta-hours') || '2');
      var deadline = new Date().getTime() + hoursAhead * 60 * 60 * 1000;
      function pad(n) { return (n < 10 ? '0' : '') + n; }
      function updateEta() {
        var diff = deadline - new Date().getTime();
        if (diff <= 0) { etaEl.textContent = '00:00:00'; return; }
        var h = Math.floor(diff / (1000 * 60 * 60));
        var m = Math.floor((diff / (1000 * 60)) % 60);
        var s = Math.floor((diff / 1000) % 60);
        etaEl.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
      }
      updateEta();
      setInterval(updateEta, 1000);
    }
  }

  // ---------- Kickoff ----------
  document.addEventListener('DOMContentLoaded', function () {
    ensureToaster();
    init404();
    init500();
    initSoon();
    initMaintenance();
  });
})();
