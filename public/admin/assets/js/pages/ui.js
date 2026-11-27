/* =====================================================
   Orchid — UI Components Showcase (shared JS)
   ===================================================== */
(function () {
  'use strict';

  /* ---------- Toast container ---------- */
  function ensureToaster(position) {
    position = position || 'top-end';
    var id = 'ui-toaster-' + position;
    var el = document.getElementById(id);
    if (!el) {
      el = document.createElement('div');
      el.id = id;
      el.className = 'ui-toaster ui-toaster--' + position;
      document.body.appendChild(el);
    }
    return el;
  }

  function iconForVariant(v) {
    switch (v) {
      case 'success': return 'bi-check-circle-fill';
      case 'danger': return 'bi-exclamation-octagon-fill';
      case 'warning': return 'bi-exclamation-triangle-fill';
      case 'info': return 'bi-info-circle-fill';
      case 'dark': return 'bi-moon-stars-fill';
      default: return 'bi-bell-fill';
    }
  }

  function orchidToast(message, variant, options) {
    if (!window.bootstrap || !window.bootstrap.Toast) return null;
    variant = variant || 'primary';
    options = options || {};
    var position = options.position || 'top-end';
    var title = options.title || '';
    var actionText = options.actionText || null;
    var onAction = options.onAction || null;
    var autohide = options.autohide !== false;
    var delay = options.delay || 4000;
    var withProgress = !!options.progress;

    var container = ensureToaster(position);
    var toast = document.createElement('div');
    toast.className = 'toast text-bg-' + variant + ' border-0 shadow-sm';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');

    var actionHtml = '';
    if (actionText) {
      actionHtml = '<button type="button" class="btn btn-sm btn-light ms-2" data-ui-toast-action>' + actionText + '</button>';
    }
    var titleHtml = title ? '<strong class="me-auto">' + title + '</strong>' : '';
    var iconClass = iconForVariant(variant);

    if (title) {
      toast.innerHTML =
        '<div class="toast-header text-bg-' + variant + ' border-0">' +
          '<i class="bi ' + iconClass + ' me-2"></i>' + titleHtml +
          '<small>Just now</small>' +
          '<button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast" aria-label="Close"></button>' +
        '</div>' +
        '<div class="toast-body d-flex align-items-center">' +
          '<div class="flex-grow-1">' + message + '</div>' +
          actionHtml +
        '</div>' +
        (withProgress ? '<div class="alert-countdown__bar" data-ui-progress></div>' : '');
    } else {
      toast.innerHTML =
        '<div class="d-flex">' +
          '<div class="toast-body d-flex align-items-center gap-2">' +
            '<i class="bi ' + iconClass + '"></i>' +
            '<span class="flex-grow-1">' + message + '</span>' +
            actionHtml +
          '</div>' +
          '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
        '</div>' +
        (withProgress ? '<div class="alert-countdown__bar" data-ui-progress></div>' : '');
    }

    container.appendChild(toast);

    if (withProgress) {
      var bar = toast.querySelector('[data-ui-progress]');
      if (bar) bar.style.animationDuration = (delay / 1000) + 's';
    }

    var bsToast = new bootstrap.Toast(toast, { autohide: autohide, delay: delay });
    if (actionText && onAction) {
      var actBtn = toast.querySelector('[data-ui-toast-action]');
      if (actBtn) {
        actBtn.addEventListener('click', function () {
          try { onAction(); } catch (e) {}
          bsToast.hide();
        });
      }
    }
    toast.addEventListener('hidden.bs.toast', function () { toast.remove(); });
    bsToast.show();
    return bsToast;
  }
  window.orchidToast = orchidToast;

  /* ---------- Copy to clipboard ---------- */
  function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text).then(function () {
        orchidToast('Copied to clipboard', 'success');
      }).catch(function () {
        fallbackCopy(text);
      });
    }
    return fallbackCopy(text);
  }
  function fallbackCopy(text) {
    try {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'absolute';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
      orchidToast('Copied to clipboard', 'success');
    } catch (e) {
      orchidToast('Copy failed', 'danger');
    }
  }
  window.copyToClipboard = copyToClipboard;

  /* ---------- Code preview toggle ---------- */
  function initCodePreviews() {
    document.querySelectorAll('[data-ui-toggle-code]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var section = btn.closest('.ui-section');
        if (!section) return;
        var code = section.querySelector('.ui-code');
        if (!code) return;
        var open = code.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', String(open));
        var label = btn.querySelector('[data-ui-code-label]');
        if (label) label.textContent = open ? 'Hide code' : 'Show code';
      });
    });

    document.querySelectorAll('[data-ui-copy-code]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var section = btn.closest('.ui-section');
        if (!section) return;
        var code = section.querySelector('.ui-code');
        if (!code) return;
        copyToClipboard(code.textContent.trim());
      });
    });
  }

  /* ---------- TOC scroll spy ---------- */
  function initTOC() {
    var links = document.querySelectorAll('[data-ui-toc-link]');
    if (!links.length) return;
    var linkMap = {};
    links.forEach(function (l) {
      var id = (l.getAttribute('href') || '').replace('#', '');
      if (id) linkMap[id] = l;
    });

    var sections = [];
    Object.keys(linkMap).forEach(function (id) {
      var s = document.getElementById(id);
      if (s) sections.push(s);
    });

    if (!('IntersectionObserver' in window)) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          Object.keys(linkMap).forEach(function (id) { linkMap[id].classList.remove('is-active'); });
          var id = entry.target.id;
          if (linkMap[id]) linkMap[id].classList.add('is-active');
        }
      });
    }, { rootMargin: '-88px 0px -60% 0px', threshold: 0 });

    sections.forEach(function (s) { observer.observe(s); });

    // smooth scroll for TOC links
    links.forEach(function (l) {
      l.addEventListener('click', function (e) {
        var href = l.getAttribute('href') || '';
        if (href.indexOf('#') !== 0) return;
        var target = document.getElementById(href.slice(1));
        if (target) {
          e.preventDefault();
          window.scrollTo({ top: target.getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth' });
        }
      });
    });
  }

  /* ---------- Bootstrap components init ---------- */
  function initTooltips() {
    if (!window.bootstrap) return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
      var opts = {};
      if (el.getAttribute('data-bs-html') === 'true') opts.html = true;
      new bootstrap.Tooltip(el, opts);
    });
  }
  function initPopovers() {
    if (!window.bootstrap) return;
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
      var opts = {};
      if (el.getAttribute('data-bs-html') === 'true') opts.html = true;
      if (el.getAttribute('data-bs-trigger')) opts.trigger = el.getAttribute('data-bs-trigger');
      new bootstrap.Popover(el, opts);
    });
  }

  /* ---------- Loading buttons ---------- */
  function initLoadingButtons() {
    document.querySelectorAll('[data-ui-loading]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (btn.classList.contains('is-loading')) return;
        var original = btn.innerHTML;
        btn.classList.add('is-loading');
        btn.setAttribute('disabled', 'disabled');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + (btn.dataset.uiLoadingText || 'Loading…');
        setTimeout(function () {
          btn.classList.remove('is-loading');
          btn.removeAttribute('disabled');
          btn.innerHTML = original;
          orchidToast(btn.dataset.uiLoadingDone || 'Done!', 'success');
        }, 1500);
      });
    });
  }

  /* ---------- Toast triggers (buttons) ---------- */
  function initToastTriggers() {
    document.querySelectorAll('[data-ui-toast]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var variant = btn.dataset.uiToast || 'primary';
        var position = btn.dataset.uiPosition || 'top-end';
        var title = btn.dataset.uiTitle || '';
        var message = btn.dataset.uiMessage || 'This is a toast notification';
        var progress = btn.dataset.uiProgress === 'true';
        var actionText = btn.dataset.uiAction || null;
        orchidToast(message, variant, {
          position: position, title: title, progress: progress,
          actionText: actionText,
          onAction: function () { orchidToast('Action confirmed', 'info'); }
        });
      });
    });

    // Fire multiple toasts (stack demo)
    document.querySelectorAll('[data-ui-stack]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var msgs = [
          { m: 'New order received — #A-2481', v: 'success', t: 'Order' },
          { m: 'Payment processed — $2,400', v: 'primary', t: 'Payment' },
          { m: 'Weekly report is ready', v: 'info', t: 'Report' },
          { m: 'Server load reached 78%', v: 'warning', t: 'Alert' },
          { m: 'Deployment queued', v: 'dark', t: 'Deploy' }
        ];
        msgs.forEach(function (msg, i) {
          setTimeout(function () {
            orchidToast(msg.m, msg.v, { title: msg.t });
          }, i * 250);
        });
      });
    });

    // Undo delete pattern
    document.querySelectorAll('[data-ui-undo]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        orchidToast('Item moved to trash', 'dark', {
          title: 'Deleted', actionText: 'Undo', autohide: true, delay: 5000,
          onAction: function () { orchidToast('Restored successfully', 'success'); }
        });
      });
    });
  }

  /* ---------- Dismissible countdown alert ---------- */
  function initCountdownAlerts() {
    document.querySelectorAll('[data-ui-countdown-alert]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = document.querySelector(btn.dataset.uiCountdownAlert);
        if (!target) return;
        target.classList.remove('d-none');
        var bar = target.querySelector('.alert-countdown__bar');
        if (bar) {
          bar.style.animation = 'none';
          bar.offsetHeight;
          bar.style.animation = '';
        }
        setTimeout(function () { target.classList.add('d-none'); }, 6000);
      });
    });
  }

  /* ---------- Radial progress animation ---------- */
  function initRadial() {
    document.querySelectorAll('[data-ui-radial]').forEach(function (el) {
      var value = parseInt(el.dataset.uiRadial, 10) || 0;
      var circle = el.querySelector('.ui-radial__fg');
      if (!circle) return;
      var r = parseFloat(circle.getAttribute('r'));
      var c = 2 * Math.PI * r;
      circle.setAttribute('stroke-dasharray', c);
      circle.setAttribute('stroke-dashoffset', c);
      setTimeout(function () {
        circle.setAttribute('stroke-dashoffset', c - (c * value / 100));
      }, 250);
    });
  }

  /* ---------- Progress bar animate-on-load ---------- */
  function initProgressAnimate() {
    document.querySelectorAll('[data-ui-progress-target]').forEach(function (bar) {
      var target = parseInt(bar.dataset.uiProgressTarget, 10) || 0;
      bar.style.width = '0%';
      setTimeout(function () { bar.style.width = target + '%'; }, 200);
    });
  }

  /* ---------- Modal wizard ---------- */
  function initWizard() {
    document.querySelectorAll('[data-ui-wizard]').forEach(function (wiz) {
      var steps = wiz.querySelectorAll('[data-ui-wizard-step]');
      var dots = wiz.querySelectorAll('.ui-wizard-steps__step');
      var prevBtn = wiz.querySelector('[data-ui-wizard-prev]');
      var nextBtn = wiz.querySelector('[data-ui-wizard-next]');
      var finishBtn = wiz.querySelector('[data-ui-wizard-finish]');
      var current = 0;

      function render() {
        steps.forEach(function (s, i) { s.classList.toggle('d-none', i !== current); });
        dots.forEach(function (d, i) {
          d.classList.toggle('is-active', i === current);
          d.classList.toggle('is-done', i < current);
        });
        if (prevBtn) prevBtn.disabled = current === 0;
        if (nextBtn) nextBtn.classList.toggle('d-none', current === steps.length - 1);
        if (finishBtn) finishBtn.classList.toggle('d-none', current !== steps.length - 1);
      }
      if (nextBtn) nextBtn.addEventListener('click', function () { if (current < steps.length - 1) { current++; render(); } });
      if (prevBtn) prevBtn.addEventListener('click', function () { if (current > 0) { current--; render(); } });
      if (finishBtn) finishBtn.addEventListener('click', function () {
        var modal = bootstrap.Modal.getInstance(wiz.closest('.modal'));
        if (modal) modal.hide();
        orchidToast('Wizard completed successfully', 'success');
        current = 0; render();
      });
      render();
    });
  }

  /* ---------- Confirmation modal (typed) ---------- */
  function initConfirmTyped() {
    document.querySelectorAll('[data-ui-confirm-typed]').forEach(function (input) {
      var btn = document.querySelector(input.dataset.uiConfirmTyped);
      if (!btn) return;
      input.addEventListener('input', function () {
        btn.disabled = input.value.trim().toUpperCase() !== 'DELETE';
      });
    });
    document.querySelectorAll('[data-ui-confirm-action]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var modal = bootstrap.Modal.getInstance(btn.closest('.modal'));
        if (modal) modal.hide();
        orchidToast('Account deleted permanently', 'danger', { title: 'Deleted' });
      });
    });
  }

  /* ---------- Gallery lightbox ---------- */
  function initLightbox() {
    var lightbox = document.querySelector('[data-ui-lightbox]');
    if (!lightbox) return;
    var img = lightbox.querySelector('.ui-gallery-lightbox__img');
    var close = lightbox.querySelector('.ui-gallery-lightbox__close');

    document.querySelectorAll('[data-ui-gallery-item]').forEach(function (item) {
      item.addEventListener('click', function () {
        var bg = item.querySelector('.ui-gallery__thumb').style.background ||
                 getComputedStyle(item.querySelector('.ui-gallery__thumb')).background;
        img.style.background = bg;
        img.textContent = item.dataset.uiGalleryItem || '';
        lightbox.classList.add('is-open');
      });
    });
    if (close) close.addEventListener('click', function () { lightbox.classList.remove('is-open'); });
    lightbox.addEventListener('click', function (e) { if (e.target === lightbox) lightbox.classList.remove('is-open'); });
  }

  /* ---------- Load more / infinite ---------- */
  function initLoadMore() {
    document.querySelectorAll('[data-ui-load-more]').forEach(function (btn) {
      var list = document.querySelector(btn.dataset.uiLoadMore);
      var count = 0;
      var labels = ['Item', 'Record', 'Result', 'Entry'];
      btn.addEventListener('click', function () {
        if (!list) return;
        for (var i = 0; i < 3; i++) {
          count++;
          var li = document.createElement('li');
          li.innerHTML = '<i class="bi bi-arrow-right-circle text-primary"></i>' +
                         '<span class="flex-grow-1">' + labels[Math.floor(Math.random() * labels.length)] + ' #' + (100 + count) + '</span>' +
                         '<small class="text-body-secondary">Just added</small>';
          list.appendChild(li);
        }
      });
    });
  }

  /* ---------- Toast on form submit ---------- */
  function initFormToasts() {
    document.querySelectorAll('[data-ui-form-toast]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        orchidToast(form.dataset.uiFormToast || 'Form submitted', 'success', { title: 'Success' });
        form.reset();
      });
    });
  }

  /* ---------- Multi-select dropdown (custom) ---------- */
  function initMultiSelect() {
    document.querySelectorAll('[data-ui-multi]').forEach(function (dd) {
      var label = dd.querySelector('[data-ui-multi-label]');
      var boxes = dd.querySelectorAll('input[type="checkbox"]');
      function update() {
        var checked = Array.from(boxes).filter(function (b) { return b.checked; }).map(function (b) { return b.value; });
        if (label) label.textContent = checked.length ? checked.length + ' selected' : (dd.dataset.uiMulti || 'Select…');
      }
      boxes.forEach(function (b) { b.addEventListener('change', update); });
      update();
    });
  }

  /* ---------- Dropdown search ---------- */
  function initDropdownSearch() {
    document.querySelectorAll('[data-ui-dropdown-search]').forEach(function (input) {
      input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        var items = input.closest('.dropdown-menu').querySelectorAll('[data-ui-searchable]');
        items.forEach(function (i) {
          var text = (i.textContent || '').toLowerCase();
          i.classList.toggle('d-none', q && text.indexOf(q) === -1);
        });
      });
      // prevent dropdown close when typing
      input.addEventListener('click', function (e) { e.stopPropagation(); });
    });
  }

  /* ---------- Init all ---------- */
  document.addEventListener('DOMContentLoaded', function () {
    initTooltips();
    initPopovers();
    initCodePreviews();
    initTOC();
    initLoadingButtons();
    initToastTriggers();
    initCountdownAlerts();
    initRadial();
    initProgressAnimate();
    initWizard();
    initConfirmTyped();
    initLightbox();
    initLoadMore();
    initFormToasts();
    initMultiSelect();
    initDropdownSearch();
  });
})();
