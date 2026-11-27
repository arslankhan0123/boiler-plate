/* =====================================================
   Orchid - Support Pages JS (Pricing / FAQ / Help Center)
   Hooks via [data-support-*]
   ===================================================== */
(function () {
  'use strict';

  // ---------- Toast helper ----------
  function ensureToastRegion() {
    var region = document.querySelector('.support-toast-region');
    if (!region) {
      region = document.createElement('div');
      region.className = 'support-toast-region';
      region.setAttribute('aria-live', 'polite');
      region.setAttribute('aria-atomic', 'true');
      document.body.appendChild(region);
    }
    return region;
  }

  function showToast(message, variant) {
    variant = variant || 'primary';
    var region = ensureToastRegion();
    var wrap = document.createElement('div');
    wrap.className = 'toast align-items-center border-0 text-bg-' + variant;
    wrap.setAttribute('role', 'status');
    wrap.setAttribute('aria-live', 'polite');
    wrap.innerHTML =
      '<div class="d-flex">' +
        '<div class="toast-body">' + message + '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
      '</div>';
    region.appendChild(wrap);
    if (window.bootstrap && window.bootstrap.Toast) {
      var t = new window.bootstrap.Toast(wrap, { delay: 3200 });
      t.show();
      wrap.addEventListener('hidden.bs.toast', function () { wrap.remove(); });
    } else {
      setTimeout(function () { wrap.remove(); }, 3200);
    }
  }

  // ---------- Escape RegExp helper ----------
  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  // ==========================================================
  // PRICING PAGE
  // ==========================================================
  function initPricing() {
    var toggle = document.querySelector('[data-support-billing]');
    if (!toggle) return;

    var labelMonthly = document.querySelector('[data-support-billing-label="monthly"]');
    var labelYearly = document.querySelector('[data-support-billing-label="yearly"]');
    var YEAR_DISCOUNT = 0.20;

    function setBilling(isYearly) {
      toggle.setAttribute('aria-checked', isYearly ? 'true' : 'false');
      if (labelMonthly) labelMonthly.classList.toggle('is-active', !isYearly);
      if (labelYearly) labelYearly.classList.toggle('is-active', isYearly);

      document.querySelectorAll('[data-support-price]').forEach(function (el) {
        var base = parseFloat(el.getAttribute('data-support-price'));
        if (isNaN(base)) return;
        var final = isYearly ? Math.round(base * (1 - YEAR_DISCOUNT)) : base;
        el.textContent = final;
      });

      document.querySelectorAll('[data-support-period]').forEach(function (el) {
        el.textContent = isYearly ? '/user/mo, billed yearly' : '/user/month';
      });
    }

    toggle.addEventListener('click', function () {
      var next = toggle.getAttribute('aria-checked') !== 'true';
      setBilling(next);
    });

    if (labelMonthly) labelMonthly.addEventListener('click', function () { setBilling(false); });
    if (labelYearly) labelYearly.addEventListener('click', function () { setBilling(true); });

    // Trial CTAs
    document.querySelectorAll('[data-support-trial]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var plan = btn.getAttribute('data-support-trial') || 'plan';
        showToast('Redirecting to sign-up for the ' + escapeHtml(plan) + ' plan…', 'primary');
      });
    });

    // Add-on CTAs
    document.querySelectorAll('[data-support-addon]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var name = btn.getAttribute('data-support-addon') || 'add-on';
        showToast(escapeHtml(name) + ' added to your quote.', 'success');
      });
    });

    // Contact sales / book demo modal submission
    var contactForm = document.querySelector('[data-support-contact-form]');
    if (contactForm) {
      contactForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!contactForm.checkValidity()) {
          contactForm.classList.add('was-validated');
          return;
        }
        var modalEl = contactForm.closest('.modal');
        if (modalEl && window.bootstrap) {
          var m = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
          m.hide();
        }
        contactForm.classList.remove('was-validated');
        contactForm.reset();
        showToast('Thanks! Our sales team will reach out within 1 business day.', 'success');
      });
    }

    var demoForm = document.querySelector('[data-support-demo-form]');
    if (demoForm) {
      demoForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!demoForm.checkValidity()) {
          demoForm.classList.add('was-validated');
          return;
        }
        var modalEl = demoForm.closest('.modal');
        if (modalEl && window.bootstrap) {
          var m = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
          m.hide();
        }
        demoForm.classList.remove('was-validated');
        demoForm.reset();
        showToast('Demo request booked. You\'ll get a calendar invite shortly.', 'success');
      });
    }
  }

  // ==========================================================
  // FAQ PAGE
  // ==========================================================
  function initFaq() {
    var searchInput = document.querySelector('[data-support-faq-search]');
    var chips = document.querySelectorAll('[data-support-faq-chip]');
    var items = document.querySelectorAll('[data-support-faq-item]');
    var groups = document.querySelectorAll('[data-support-faq-group]');
    var popularItems = document.querySelectorAll('[data-support-faq-popular]');
    var popularSection = document.querySelector('[data-support-faq-popular-section]');
    var empty = document.querySelector('[data-support-faq-empty]');
    var emptyTerm = document.querySelector('[data-support-faq-empty-term]');
    var chipsFound = document.querySelectorAll('[data-support-faq-chip]').length;

    if (!items.length && !popularItems.length) return;

    var activeCategory = 'all';
    var query = '';

    function refresh() {
      var qLower = query.trim().toLowerCase();
      var totalVisible = 0;

      // Filter category groups
      groups.forEach(function (group) {
        var cat = group.getAttribute('data-support-faq-group');
        var groupMatchesCat = (activeCategory === 'all' || activeCategory === cat);
        var groupVisibleCount = 0;

        group.querySelectorAll('[data-support-faq-item]').forEach(function (item) {
          var q = (item.getAttribute('data-support-question') || '').toLowerCase();
          var a = (item.getAttribute('data-support-answer') || '').toLowerCase();
          var qMatches = !qLower || q.indexOf(qLower) !== -1 || a.indexOf(qLower) !== -1;
          var visible = groupMatchesCat && qMatches;
          item.classList.toggle('is-hidden', !visible);
          if (visible) groupVisibleCount++;
        });

        group.classList.toggle('is-hidden', groupVisibleCount === 0);
        totalVisible += groupVisibleCount;
      });

      // Filter popular items
      var popularVisibleCount = 0;
      popularItems.forEach(function (item) {
        var q = (item.getAttribute('data-support-question') || '').toLowerCase();
        var cat = item.getAttribute('data-support-category') || '';
        var qMatches = !qLower || q.indexOf(qLower) !== -1;
        var catMatches = (activeCategory === 'all' || activeCategory === cat);
        var visible = qMatches && catMatches;
        item.classList.toggle('is-hidden', !visible);
        if (visible) popularVisibleCount++;
      });
      if (popularSection) {
        popularSection.classList.toggle('is-hidden', popularVisibleCount === 0);
      }

      totalVisible += popularVisibleCount;

      if (empty) {
        var showEmpty = totalVisible === 0;
        empty.classList.toggle('is-visible', showEmpty);
        if (showEmpty && emptyTerm) {
          emptyTerm.textContent = query.trim() ? '"' + query.trim() + '"' : 'this filter';
        }
      }
    }

    if (searchInput) {
      searchInput.addEventListener('input', function () {
        query = searchInput.value;
        refresh();
      });
    }

    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        chips.forEach(function (c) { c.classList.remove('is-active'); });
        chip.classList.add('is-active');
        activeCategory = chip.getAttribute('data-support-faq-chip') || 'all';
        refresh();
      });
    });

    // Contact / community buttons
    document.querySelectorAll('[data-support-faq-contact]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var type = btn.getAttribute('data-support-faq-contact');
        var msg = 'Opening support…';
        if (type === 'chat') msg = 'Starting live chat with an agent…';
        if (type === 'email') msg = 'A ticket has been opened. Check your inbox.';
        if (type === 'community') msg = 'Redirecting to the community forum…';
        showToast(msg, 'primary');
      });
    });

    // Initial count on chips
    if (chipsFound) refresh();
  }

  // ==========================================================
  // HELP CENTER PAGE
  // ==========================================================
  function initHelpCenter() {
    var searchInput = document.querySelector('[data-support-help-search]');
    var searchBtn = document.querySelector('[data-support-help-search-btn]');
    var count = document.querySelector('[data-support-help-count]');
    var articles = document.querySelectorAll('[data-support-help-article]');
    var chips = document.querySelectorAll('[data-support-help-chip]');

    if (!searchInput) return;

    function refresh() {
      var q = (searchInput.value || '').trim().toLowerCase();
      var visible = 0;
      articles.forEach(function (a) {
        var title = (a.getAttribute('data-support-help-article') || '').toLowerCase();
        var match = !q || title.indexOf(q) !== -1;
        a.classList.toggle('is-hidden', !match);
        if (match) visible++;
      });
      if (count) {
        if (q) count.textContent = visible + ' article' + (visible === 1 ? '' : 's') + ' found for "' + searchInput.value.trim() + '"';
        else count.textContent = '';
      }
    }

    searchInput.addEventListener('input', refresh);

    if (searchBtn) {
      searchBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (!searchInput.value.trim()) {
          showToast('Type something to search the knowledge base.', 'warning');
          return;
        }
        refresh();
      });
    }

    chips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        var term = chip.getAttribute('data-support-help-chip') || '';
        searchInput.value = term;
        searchInput.focus();
        refresh();
      });
    });

    // Category tile clicks
    document.querySelectorAll('[data-support-help-tile]').forEach(function (tile) {
      tile.addEventListener('click', function (e) {
        e.preventDefault();
        var name = tile.getAttribute('data-support-help-tile') || 'Category';
        showToast('Opening ' + escapeHtml(name) + '…', 'primary');
      });
    });

    // Article clicks
    articles.forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        var title = a.getAttribute('data-support-help-article') || 'Article';
        showToast('Opening: ' + escapeHtml(title), 'primary');
      });
    });

    // New/updated cards
    document.querySelectorAll('[data-support-help-card]').forEach(function (card) {
      card.addEventListener('click', function (e) {
        e.preventDefault();
        var title = card.getAttribute('data-support-help-card') || 'Article';
        showToast('Opening: ' + escapeHtml(title), 'primary');
      });
    });

    // Video clicks
    document.querySelectorAll('[data-support-help-video]').forEach(function (v) {
      v.addEventListener('click', function (e) {
        e.preventDefault();
        var title = v.getAttribute('data-support-help-video') || 'Video';
        showToast('Playing: ' + escapeHtml(title), 'primary');
      });
    });

    // Contact tile actions
    document.querySelectorAll('[data-support-help-contact]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var type = btn.getAttribute('data-support-help-contact');
        var msg = 'Opening support…';
        if (type === 'chat') msg = 'Starting live chat with an agent…';
        if (type === 'email') msg = 'Opening a new email ticket…';
        if (type === 'call') msg = 'Redirecting to schedule a call…';
        showToast(msg, 'success');
      });
    });

    // Community discussion clicks
    document.querySelectorAll('[data-support-help-discussion]').forEach(function (d) {
      d.addEventListener('click', function (e) {
        e.preventDefault();
        showToast('Opening discussion thread…', 'primary');
      });
    });
  }

  // ---------- Init on ready ----------
  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    initPricing();
    initFaq();
    initHelpCenter();
  });
})();
