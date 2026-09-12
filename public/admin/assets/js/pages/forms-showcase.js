/* =====================================================
   Orchid — Forms Showcase shared toolkit
   Namespace: data-forms-* / window.OrchidForms
   ===================================================== */
(function () {
  'use strict';

  // ------------- Toast -------------
  function ensureToastRegion() {
    var r = document.querySelector('.forms-toast-region');
    if (!r) {
      r = document.createElement('div');
      r.className = 'forms-toast-region';
      r.setAttribute('aria-live', 'polite');
      r.setAttribute('aria-atomic', 'true');
      document.body.appendChild(r);
    }
    return r;
  }
  function orchidToast(msg, type) {
    var region = ensureToastRegion();
    var t = document.createElement('div');
    t.className = 'forms-toast' + (type ? ' forms-toast--' + type : '');
    var icon = 'bi-info-circle';
    if (type === 'success') icon = 'bi-check-circle-fill';
    else if (type === 'danger') icon = 'bi-exclamation-octagon-fill';
    else if (type === 'warning') icon = 'bi-exclamation-triangle-fill';
    var i = document.createElement('i');
    i.className = 'bi ' + icon;
    var span = document.createElement('span');
    span.textContent = msg;
    t.appendChild(i);
    t.appendChild(span);
    region.appendChild(t);
    setTimeout(function () {
      t.style.opacity = '0';
      t.style.transform = 'translateX(20px)';
      t.style.transition = 'all 250ms';
      setTimeout(function () { t.remove(); }, 260);
    }, 3200);
  }

  // ------------- Copy to clipboard -------------
  function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    return new Promise(function (resolve, reject) {
      try {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        resolve();
      } catch (e) { reject(e); }
    });
  }

  // ------------- Code preview toggle & copy -------------
  function initCodePreview() {
    document.querySelectorAll('[data-forms-code-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = document.getElementById(btn.getAttribute('data-forms-code-toggle'));
        if (!target) return;
        var open = target.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', open);
        btn.innerHTML = open
          ? '<i class="bi bi-eye-slash"></i> Hide code'
          : '<i class="bi bi-code-slash"></i> Show code';
      });
    });
    document.querySelectorAll('[data-forms-copy]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var t = btn.getAttribute('data-forms-copy');
        var text = '';
        if (t.charAt(0) === '#') {
          var el = document.querySelector(t);
          if (el) text = el.textContent || '';
        } else {
          text = t;
        }
        copyToClipboard(text).then(function () {
          orchidToast('Copied to clipboard', 'success');
        }).catch(function () {
          orchidToast('Copy failed', 'danger');
        });
      });
    });
  }

  // ------------- TOC scroll spy -------------
  function initTocSpy() {
    var links = document.querySelectorAll('.forms-toc__list a');
    if (!links.length) return;
    var map = {};
    links.forEach(function (a) {
      var id = a.getAttribute('href');
      if (id && id.charAt(0) === '#') {
        var s = document.querySelector(id);
        if (s) map[id.slice(1)] = a;
      }
    });
    var obs = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          links.forEach(function (l) { l.classList.remove('is-active'); });
          if (map[en.target.id]) map[en.target.id].classList.add('is-active');
        }
      });
    }, { rootMargin: '-40% 0px -50% 0px' });
    Object.keys(map).forEach(function (id) {
      var el = document.getElementById(id);
      if (el) obs.observe(el);
    });
  }

  // ------------- Wizard controller -------------
  function initWizard(root) {
    if (!root) return;
    var steps = root.querySelectorAll('.forms-wizard__step');
    var panels = root.querySelectorAll('.forms-wizard__panel');
    var progress = root.querySelector('.forms-wizard__progress');
    var progressFill = root.querySelector('.forms-wizard__progress-fill');
    var progressLabel = root.querySelector('[data-forms-progress-label]');
    var prevBtn = root.querySelector('[data-forms-wizard-prev]');
    var nextBtn = root.querySelector('[data-forms-wizard-next]');
    var submitBtn = root.querySelector('[data-forms-wizard-submit]');
    var total = panels.length;
    var current = 0;
    var canTabs = root.classList.contains('forms-wizard--tabs') || root.classList.contains('forms-wizard--vertical');

    function validate(idx) {
      var panel = panels[idx];
      if (!panel) return true;
      var inputs = panel.querySelectorAll('input, select, textarea');
      var ok = true;
      inputs.forEach(function (input) {
        if (input.hasAttribute('required') && !input.value.trim()) {
          input.classList.add('is-invalid');
          ok = false;
        } else if (input.type === 'email' && input.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
          input.classList.add('is-invalid');
          ok = false;
        } else {
          input.classList.remove('is-invalid');
        }
      });
      return ok;
    }

    function render() {
      steps.forEach(function (s, i) {
        s.classList.remove('is-active', 'is-done');
        if (i < current) s.classList.add('is-done');
        else if (i === current) s.classList.add('is-active');
      });
      panels.forEach(function (p, i) {
        p.classList.toggle('is-active', i === current);
      });
      if (progress) {
        var pct = total > 1 ? (current / (total - 1)) * 100 : 100;
        var trackWidth = 'calc(' + pct + '% - ' + (pct * 0.48) + 'px)';
        progress.style.width = trackWidth;
      }
      if (progressFill) {
        progressFill.style.width = ((current + 1) / total) * 100 + '%';
      }
      if (progressLabel) {
        progressLabel.textContent = 'Step ' + (current + 1) + ' of ' + total;
      }
      if (prevBtn) prevBtn.disabled = current === 0;
      if (nextBtn && submitBtn) {
        var isLast = current === total - 1;
        nextBtn.classList.toggle('d-none', isLast);
        submitBtn.classList.toggle('d-none', !isLast);
      }
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        if (!validate(current)) {
          orchidToast('Please complete the required fields', 'warning');
          return;
        }
        if (current < total - 1) { current++; render(); }
      });
    }
    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        if (current > 0) { current--; render(); }
      });
    }
    if (submitBtn) {
      submitBtn.addEventListener('click', function () {
        if (!validate(current)) {
          orchidToast('Please review required fields', 'warning');
          return;
        }
        orchidToast('Wizard completed successfully', 'success');
        var successEl = root.querySelector('[data-forms-wizard-success]');
        if (successEl) {
          panels.forEach(function (p) { p.classList.remove('is-active'); });
          successEl.classList.add('is-active');
          fireConfetti();
        }
      });
    }
    if (canTabs) {
      steps.forEach(function (s, i) {
        s.addEventListener('click', function () {
          if (i <= current || i === current + 1) {
            if (i > current && !validate(current)) return;
            current = i;
            render();
          }
        });
      });
    }
    render();
  }

  // Confetti
  function fireConfetti() {
    var c = document.createElement('div');
    c.className = 'forms-confetti';
    document.body.appendChild(c);
    var colors = ['#4f46e5', '#22d3ee', '#f59e0b', '#22c55e', '#ec4899', '#f43f5e'];
    for (var i = 0; i < 80; i++) {
      var p = document.createElement('span');
      p.style.left = Math.random() * 100 + '%';
      p.style.background = colors[Math.floor(Math.random() * colors.length)];
      p.style.animationDuration = 1.5 + Math.random() * 1.5 + 's';
      p.style.animationDelay = Math.random() * 0.3 + 's';
      p.style.transform = 'rotate(' + Math.random() * 360 + 'deg)';
      c.appendChild(p);
    }
    setTimeout(function () { c.remove(); }, 3500);
  }

  // ------------- Custom select with search -------------
  function initSelectSearch(root) {
    var toggle = root.querySelector('.forms-select-search__toggle');
    var panel = root.querySelector('.forms-select-search__panel');
    var search = root.querySelector('.forms-select-search__search');
    var list = root.querySelector('.forms-select-search__list');
    var hidden = root.querySelector('input[type="hidden"]');
    if (!toggle || !list) return;

    function close() { root.classList.remove('is-open'); toggle.setAttribute('aria-expanded', 'false'); }
    function open() {
      root.classList.add('is-open');
      toggle.setAttribute('aria-expanded', 'true');
      if (search) { search.value = ''; filter(''); setTimeout(function(){ search.focus(); }, 30); }
    }
    function filter(q) {
      var qq = (q || '').toLowerCase();
      list.querySelectorAll('.forms-select-search__opt').forEach(function (o) {
        o.style.display = o.textContent.toLowerCase().indexOf(qq) === -1 ? 'none' : '';
      });
    }
    toggle.addEventListener('click', function () {
      if (root.classList.contains('is-open')) close(); else open();
    });
    if (search) search.addEventListener('input', function () { filter(search.value); });
    list.querySelectorAll('.forms-select-search__opt').forEach(function (o) {
      o.addEventListener('click', function () {
        list.querySelectorAll('.is-selected').forEach(function (x) { x.classList.remove('is-selected'); });
        o.classList.add('is-selected');
        toggle.querySelector('.forms-select-search__label').textContent = o.textContent;
        if (hidden) hidden.value = o.getAttribute('data-value') || o.textContent;
        close();
      });
    });
    document.addEventListener('click', function (e) {
      if (!root.contains(e.target)) close();
    });
  }

  // ------------- Password strength -------------
  function scorePassword(pw) {
    if (!pw) return 0;
    var s = 0;
    if (pw.length >= 8) s++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) s++;
    if (/\d/.test(pw)) s++;
    if (/[^A-Za-z0-9]/.test(pw)) s++;
    return s;
  }
  function initPasswordStrength() {
    document.querySelectorAll('[data-forms-strength]').forEach(function (input) {
      var wrap = document.getElementById(input.getAttribute('data-forms-strength'));
      if (!wrap) return;
      var label = wrap.querySelector('.forms-strength__label');
      input.addEventListener('input', function () {
        var s = scorePassword(input.value);
        wrap.setAttribute('data-strength', s);
        if (label) {
          var text = ['Too weak', 'Weak', 'Okay', 'Strong', 'Very strong'][s];
          label.textContent = input.value ? text : 'Enter a password';
        }
      });
    });
  }

  // ------------- Async username -------------
  var taken = ['admin', 'alex', 'root', 'test', 'orchid'];
  function initUsernameCheck() {
    var input = document.querySelector('[data-forms-username-check]');
    if (!input) return;
    var feedback = document.getElementById(input.getAttribute('aria-describedby'));
    var timer = null;
    input.addEventListener('input', function () {
      if (timer) clearTimeout(timer);
      var v = input.value.trim();
      if (!v) { if (feedback) feedback.textContent = ''; input.classList.remove('is-valid', 'is-invalid'); return; }
      if (feedback) { feedback.textContent = 'Checking availability…'; feedback.className = 'form-text'; }
      input.classList.remove('is-valid', 'is-invalid');
      timer = setTimeout(function () {
        var isTaken = taken.indexOf(v.toLowerCase()) !== -1;
        if (isTaken) {
          input.classList.add('is-invalid');
          input.classList.remove('is-valid');
          if (feedback) { feedback.textContent = 'Username "' + v + '" is taken.'; feedback.className = 'invalid-feedback d-block'; }
        } else {
          input.classList.add('is-valid');
          input.classList.remove('is-invalid');
          if (feedback) { feedback.textContent = 'Username "' + v + '" is available!'; feedback.className = 'valid-feedback d-block'; }
        }
      }, 800);
    });
  }

  // ------------- Bootstrap validation -------------
  function initFormValidation() {
    document.querySelectorAll('.needs-validation').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) {
          e.preventDefault(); e.stopPropagation();
        } else {
          e.preventDefault();
          var btn = form.querySelector('button[type="submit"]');
          if (btn) {
            var orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting…';
            setTimeout(function () {
              btn.disabled = false;
              btn.innerHTML = orig;
              orchidToast('Form submitted successfully', 'success');
            }, 1100);
          } else {
            orchidToast('Form submitted successfully', 'success');
          }
        }
        form.classList.add('was-validated');
      });
    });
  }

  // Password match
  function initPasswordMatch() {
    document.querySelectorAll('[data-forms-match]').forEach(function (input) {
      var target = document.getElementById(input.getAttribute('data-forms-match'));
      function chk() {
        if (!target) return;
        if (input.value && input.value !== target.value) {
          input.setCustomValidity('Passwords do not match');
        } else {
          input.setCustomValidity('');
        }
      }
      input.addEventListener('input', chk);
      if (target) target.addEventListener('input', chk);
    });
  }

  // ------------- Drag & drop file zone -------------
  function initDropZones() {
    document.querySelectorAll('[data-forms-drop]').forEach(function (zone) {
      var input = zone.querySelector('input[type="file"]');
      var listEl = document.querySelector(zone.getAttribute('data-forms-drop-list') || '') || zone.parentElement.querySelector('.forms-filelist');
      var maxSize = parseInt(zone.getAttribute('data-forms-max-size') || '0', 10);
      var accept = zone.getAttribute('data-forms-accept') || '';

      function handleFiles(fileList) {
        Array.from(fileList).forEach(function (file) {
          if (accept === 'image/*' && !/^image\//.test(file.type)) {
            orchidToast('Only image files are allowed', 'danger');
            return;
          }
          if (maxSize && file.size > maxSize) {
            orchidToast(file.name + ' exceeds ' + Math.round(maxSize / 1048576) + 'MB limit', 'danger');
            return;
          }
          addFileRow(listEl, file);
        });
      }

      zone.addEventListener('click', function () { if (input) input.click(); });
      zone.addEventListener('dragover', function (e) { e.preventDefault(); zone.classList.add('is-dragover'); });
      zone.addEventListener('dragleave', function () { zone.classList.remove('is-dragover'); });
      zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('is-dragover');
        if (e.dataTransfer && e.dataTransfer.files) handleFiles(e.dataTransfer.files);
      });
      if (input) input.addEventListener('change', function () { if (input.files) handleFiles(input.files); });
    });
  }
  function addFileRow(listEl, file) {
    if (!listEl) return;
    var li = document.createElement('li');
    li.className = 'forms-filelist__item';
    var iconClass = 'bi-file-earmark';
    if (/^image\//.test(file.type)) iconClass = 'bi-image';
    else if (/pdf/.test(file.type)) iconClass = 'bi-file-earmark-pdf';
    else if (/zip|rar|tar/.test(file.type)) iconClass = 'bi-file-zip';
    li.innerHTML =
      '<span class="forms-filelist__icon"><i class="bi ' + iconClass + '"></i></span>' +
      '<div class="forms-filelist__meta">' +
        '<p class="forms-filelist__name">' + escapeHtml(file.name) + '</p>' +
        '<span class="forms-filelist__size">' + formatBytes(file.size) + '</span>' +
        '<div class="forms-filelist__bar"><span></span></div>' +
      '</div>' +
      '<button type="button" class="btn btn-sm btn-icon" aria-label="Remove"><i class="bi bi-x-lg"></i></button>';
    listEl.appendChild(li);
    var bar = li.querySelector('.forms-filelist__bar > span');
    var p = 0;
    var timer = setInterval(function () {
      p += Math.random() * 18;
      if (p >= 100) { p = 100; clearInterval(timer); }
      bar.style.width = p + '%';
    }, 220);
    li.querySelector('button').addEventListener('click', function () {
      clearInterval(timer);
      li.remove();
    });
  }
  function formatBytes(b) {
    if (b < 1024) return b + ' B';
    if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
    return (b / 1048576).toFixed(2) + ' MB';
  }
  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  // ------------- Image preview -------------
  function initImagePreviews() {
    document.querySelectorAll('[data-forms-image-preview]').forEach(function (input) {
      var target = document.getElementById(input.getAttribute('data-forms-image-preview'));
      input.addEventListener('change', function () {
        var f = input.files && input.files[0];
        if (!f || !target) return;
        var reader = new FileReader();
        reader.onload = function (e) {
          var img = target.querySelector('img');
          if (img) img.src = e.target.result;
          target.classList.add('is-visible', 'has-image');
        };
        reader.readAsDataURL(f);
      });
    });
    document.querySelectorAll('[data-forms-avatar]').forEach(function (avatar) {
      var input = avatar.querySelector('input[type="file"]');
      if (!input) return;
      avatar.addEventListener('click', function () { input.click(); });
      input.addEventListener('change', function () {
        var f = input.files && input.files[0];
        if (!f) return;
        var r = new FileReader();
        r.onload = function (e) {
          var img = avatar.querySelector('img');
          if (img) img.src = e.target.result;
          avatar.classList.add('has-image');
        };
        r.readAsDataURL(f);
      });
    });
  }

  // ------------- Chunked upload simulator -------------
  function initChunked() {
    document.querySelectorAll('[data-forms-chunk-start]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var wrap = document.querySelector(btn.getAttribute('data-forms-chunk-start'));
        if (!wrap) return;
        var bar = wrap.querySelector('.progress-bar');
        var label = wrap.querySelector('[data-forms-chunk-label]');
        var chunks = 12;
        var done = 0;
        btn.disabled = true;
        var iv = setInterval(function () {
          done++;
          var pct = Math.round((done / chunks) * 100);
          if (bar) { bar.style.width = pct + '%'; bar.textContent = pct + '%'; }
          if (label) label.textContent = 'Uploading chunk ' + done + ' / ' + chunks;
          if (done >= chunks) {
            clearInterval(iv);
            if (label) label.textContent = 'Upload complete';
            btn.disabled = false;
            orchidToast('Chunked upload finished', 'success');
          }
        }, 350);
      });
    });
  }

  // ============ Rich editor ============
  function initRichEditor(root) {
    if (!root) return;
    var content = root.querySelector('.forms-editor__content');
    var source = root.querySelector('.forms-editor__source');
    var wordEl = root.querySelector('[data-forms-word]');
    var charEl = root.querySelector('[data-forms-char]');
    if (!content) return;

    function updateCounts() {
      var text = content.innerText || '';
      var words = text.trim() ? text.trim().split(/\s+/).length : 0;
      if (wordEl) wordEl.textContent = words;
      if (charEl) charEl.textContent = text.length;
    }
    content.addEventListener('input', updateCounts);
    updateCounts();

    function exec(cmd, val) {
      content.focus();
      try { document.execCommand(cmd, false, val || null); } catch (e) { /* noop */ }
      updateCounts();
      syncActiveButtons();
    }

    function syncActiveButtons() {
      root.querySelectorAll('[data-forms-edcmd]').forEach(function (b) {
        var c = b.getAttribute('data-forms-edcmd');
        var val = b.getAttribute('data-forms-edval');
        try {
          if (val) {
            b.classList.toggle('is-active', document.queryCommandValue(c) === val);
          } else {
            b.classList.toggle('is-active', document.queryCommandState(c));
          }
        } catch (e) { /* noop */ }
      });
    }
    content.addEventListener('keyup', syncActiveButtons);
    content.addEventListener('mouseup', syncActiveButtons);

    root.querySelectorAll('[data-forms-edcmd]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var cmd = btn.getAttribute('data-forms-edcmd');
        var val = btn.getAttribute('data-forms-edval');
        exec(cmd, val);
      });
    });

    var headSelect = root.querySelector('[data-forms-edheading]');
    if (headSelect) {
      headSelect.addEventListener('change', function () {
        exec('formatBlock', headSelect.value);
        headSelect.value = '';
      });
    }
    var linkBtn = root.querySelector('[data-forms-edlink]');
    if (linkBtn) linkBtn.addEventListener('click', function () {
      var url = prompt('Enter URL', 'https://');
      if (url) exec('createLink', url);
    });
    var imgBtn = root.querySelector('[data-forms-edimage]');
    if (imgBtn) imgBtn.addEventListener('click', function () {
      var url = prompt('Enter image URL', 'https://');
      if (url) exec('insertImage', url);
    });
    var clearBtn = root.querySelector('[data-forms-edclear]');
    if (clearBtn) clearBtn.addEventListener('click', function () { exec('removeFormat'); });

    var srcBtn = root.querySelector('[data-forms-edsource]');
    if (srcBtn) srcBtn.addEventListener('click', function () {
      if (root.classList.contains('is-source')) {
        content.innerHTML = source.textContent;
        root.classList.remove('is-source');
      } else {
        source.textContent = content.innerHTML;
        root.classList.add('is-source');
      }
      updateCounts();
    });

    var fsBtn = root.querySelector('[data-forms-edfull]');
    if (fsBtn) fsBtn.addEventListener('click', function () {
      root.classList.toggle('forms-editor--fullscreen');
    });

    var saveBtn = root.querySelector('[data-forms-edsave]');
    if (saveBtn) saveBtn.addEventListener('click', function () {
      console.log('[Editor content saved]', content.innerHTML);
      orchidToast('Content saved', 'success');
    });

    // emoji
    var emojiBtn = root.querySelector('[data-forms-edemoji]');
    var emojiPop = root.querySelector('.forms-editor__emoji');
    if (emojiBtn && emojiPop) {
      var emojis = ['😀','😄','😊','😍','🥰','😎','🤔','😴','🎉','🚀','💡','⭐','✅','❌','❤️','🔥','👍','👏','💯','📌','📝','📅','⚡','🎯'];
      emojis.forEach(function (e) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = e;
        b.addEventListener('click', function () {
          content.focus();
          try { document.execCommand('insertText', false, e); } catch (er) { /* noop */ }
          emojiPop.style.display = 'none';
          updateCounts();
        });
        emojiPop.appendChild(b);
      });
      emojiPop.style.display = 'none';
      emojiBtn.addEventListener('click', function (ev) {
        ev.stopPropagation();
        var rect = emojiBtn.getBoundingClientRect();
        var wrapRect = root.getBoundingClientRect();
        emojiPop.style.left = (rect.left - wrapRect.left) + 'px';
        emojiPop.style.top = (rect.bottom - wrapRect.top + 4) + 'px';
        emojiPop.style.display = emojiPop.style.display === 'none' ? 'grid' : 'none';
      });
      document.addEventListener('click', function (e) {
        if (!emojiPop.contains(e.target) && e.target !== emojiBtn) emojiPop.style.display = 'none';
      });
    }
  }

  // ============ Date picker ============
  var DAYS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
  var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  function pad(n) { return n < 10 ? '0' + n : '' + n; }
  function fmtDate(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function parseDate(s) {
    if (!s) return null;
    var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s);
    if (!m) { var d = new Date(s); return isNaN(d) ? null : d; }
    return new Date(+m[1], +m[2] - 1, +m[3]);
  }

  function initDatePicker(root, opts) {
    if (!root || root.dataset.formsDpInit) return;
    root.dataset.formsDpInit = '1';
    opts = opts || {};
    var input = root.querySelector('input[type="text"], input[type="date"], input.forms-datepicker__input');
    var popup = document.createElement('div');
    popup.className = 'forms-datepicker__popup';
    root.appendChild(popup);

    var view = new Date();
    var selected = input && input.value ? parseDate(input.value) : null;
    if (selected) view = new Date(selected);
    var mode = 'month';
    var focusDate = new Date(view);
    var events = opts.events || [];
    var minDate = opts.minDate ? parseDate(opts.minDate) : null;
    var disablePast = opts.disablePast === true;
    var presets = opts.presets;
    var onSelect = opts.onSelect;

    function render() {
      popup.innerHTML = '';
      var head = document.createElement('div');
      head.className = 'forms-calendar__head';
      head.innerHTML =
        '<button type="button" class="forms-calendar__nav" data-nav="-1" aria-label="Previous"><i class="bi bi-chevron-left"></i></button>' +
        '<button type="button" class="forms-calendar__title">' + MONTHS[view.getMonth()] + ' ' + view.getFullYear() + '</button>' +
        '<button type="button" class="forms-calendar__nav" data-nav="1" aria-label="Next"><i class="bi bi-chevron-right"></i></button>';
      popup.appendChild(head);

      head.querySelector('[data-nav="-1"]').addEventListener('click', function () {
        if (mode === 'year') { view.setFullYear(view.getFullYear() - 12); } else { view.setMonth(view.getMonth() - 1); }
        render();
      });
      head.querySelector('[data-nav="1"]').addEventListener('click', function () {
        if (mode === 'year') { view.setFullYear(view.getFullYear() + 12); } else { view.setMonth(view.getMonth() + 1); }
        render();
      });
      head.querySelector('.forms-calendar__title').addEventListener('click', function () {
        mode = mode === 'month' ? 'year' : 'month';
        render();
      });

      if (mode === 'year') {
        var yearGrid = document.createElement('div');
        yearGrid.className = 'forms-datepicker__year';
        var startYear = view.getFullYear() - 6;
        for (var y = startYear; y < startYear + 12; y++) {
          var yb = document.createElement('button');
          yb.type = 'button';
          yb.textContent = y;
          if (selected && selected.getFullYear() === y) yb.classList.add('is-selected');
          (function (year) {
            yb.addEventListener('click', function () {
              view.setFullYear(year); mode = 'month'; render();
            });
          })(y);
          yearGrid.appendChild(yb);
        }
        popup.appendChild(yearGrid);
      } else {
        var grid = document.createElement('div');
        grid.className = 'forms-calendar__grid';
        DAYS.forEach(function (d) {
          var e = document.createElement('div');
          e.className = 'forms-calendar__dow';
          e.textContent = d;
          grid.appendChild(e);
        });
        var firstDow = new Date(view.getFullYear(), view.getMonth(), 1).getDay();
        var daysInMonth = new Date(view.getFullYear(), view.getMonth() + 1, 0).getDate();
        var daysInPrev = new Date(view.getFullYear(), view.getMonth(), 0).getDate();
        var today = new Date(); today.setHours(0,0,0,0);

        for (var i = 0; i < 42; i++) {
          var cell = document.createElement('button');
          cell.type = 'button';
          cell.className = 'forms-calendar__cell';
          var day, cellDate;
          if (i < firstDow) {
            day = daysInPrev - firstDow + 1 + i;
            cellDate = new Date(view.getFullYear(), view.getMonth() - 1, day);
            cell.classList.add('is-outside');
          } else if (i >= firstDow + daysInMonth) {
            day = i - firstDow - daysInMonth + 1;
            cellDate = new Date(view.getFullYear(), view.getMonth() + 1, day);
            cell.classList.add('is-outside');
          } else {
            day = i - firstDow + 1;
            cellDate = new Date(view.getFullYear(), view.getMonth(), day);
          }
          cell.textContent = day;

          if (cellDate.getTime() === today.getTime()) cell.classList.add('is-today');
          if (selected && cellDate.toDateString() === selected.toDateString()) cell.classList.add('is-selected');
          if ((disablePast && cellDate < today) || (minDate && cellDate < minDate)) cell.classList.add('is-disabled');
          if (events.indexOf(fmtDate(cellDate)) !== -1) cell.classList.add('has-event');

          (function (dt, isDisabled) {
            cell.addEventListener('click', function () {
              if (isDisabled) return;
              selected = new Date(dt);
              if (input) input.value = fmtDate(selected);
              if (onSelect) onSelect(selected);
              root.classList.remove('is-open');
              render();
            });
          })(cellDate, cell.classList.contains('is-disabled'));

          grid.appendChild(cell);
        }
        popup.appendChild(grid);
      }

      if (presets) {
        var pre = document.createElement('div');
        pre.className = 'forms-datepicker__presets';
        presets.forEach(function (p) {
          var b = document.createElement('button');
          b.type = 'button';
          b.textContent = p.label;
          b.addEventListener('click', function () {
            var d = p.date();
            selected = d; view = new Date(d);
            if (input) input.value = fmtDate(d);
            if (onSelect) onSelect(d);
            render();
            root.classList.remove('is-open');
          });
          pre.appendChild(b);
        });
        popup.appendChild(pre);
      }
    }
    render();

    if (input && !root.classList.contains('forms-datepicker--inline')) {
      input.addEventListener('focus', function () { root.classList.add('is-open'); });
      input.addEventListener('click', function () { root.classList.add('is-open'); });
      input.addEventListener('keydown', function (e) {
        if (!root.classList.contains('is-open')) return;
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.key === 'ArrowUp' || e.key === 'ArrowDown') {
          e.preventDefault();
          if (!selected) selected = new Date();
          var d = new Date(selected);
          if (e.key === 'ArrowLeft') d.setDate(d.getDate() - 1);
          if (e.key === 'ArrowRight') d.setDate(d.getDate() + 1);
          if (e.key === 'ArrowUp') d.setDate(d.getDate() - 7);
          if (e.key === 'ArrowDown') d.setDate(d.getDate() + 7);
          selected = d; view = new Date(d);
          if (input) input.value = fmtDate(d);
          render();
        } else if (e.key === 'Enter') {
          root.classList.remove('is-open');
        } else if (e.key === 'Escape') {
          root.classList.remove('is-open');
        }
      });
      document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) root.classList.remove('is-open');
      });
    }

    return {
      setDate: function (d) { selected = d; view = new Date(d); if (input) input.value = fmtDate(d); render(); },
      getDate: function () { return selected; },
      render: render
    };
  }

  // ============ Time picker ============
  function initTimePicker(root, opts) {
    if (!root || root.dataset.formsTpInit) return;
    root.dataset.formsTpInit = '1';
    opts = opts || {};
    var input = root.querySelector('input');
    var popup = document.createElement('div');
    popup.className = 'forms-timepicker__popup';

    var hour12 = opts.hour12 !== false;
    var hourCol = document.createElement('div'); hourCol.className = 'forms-timepicker__col';
    var minCol = document.createElement('div'); minCol.className = 'forms-timepicker__col';
    var segCol = document.createElement('div'); segCol.className = 'forms-timepicker__seg';

    var state = { h: 9, m: 0, ap: 'AM' };
    if (input && input.value) {
      var mm = /^(\d{1,2}):(\d{2})(?:\s*(AM|PM))?$/i.exec(input.value.trim());
      if (mm) { state.h = +mm[1]; state.m = +mm[2]; if (mm[3]) state.ap = mm[3].toUpperCase(); }
    }

    var hStart = hour12 ? 1 : 0;
    var hEnd = hour12 ? 12 : 23;
    for (var h = hStart; h <= hEnd; h++) {
      (function (hv) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = pad(hv);
        b.addEventListener('click', function () {
          state.h = hv;
          hourCol.querySelectorAll('.is-selected').forEach(function (x) { x.classList.remove('is-selected'); });
          b.classList.add('is-selected');
          commit();
        });
        hourCol.appendChild(b);
      })(h);
    }
    for (var m = 0; m < 60; m += 5) {
      (function (mv) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = pad(mv);
        b.addEventListener('click', function () {
          state.m = mv;
          minCol.querySelectorAll('.is-selected').forEach(function (x) { x.classList.remove('is-selected'); });
          b.classList.add('is-selected');
          commit();
        });
        minCol.appendChild(b);
      })(m);
    }
    popup.appendChild(hourCol);
    popup.appendChild(minCol);
    if (hour12) {
      ['AM', 'PM'].forEach(function (ap) {
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = ap;
        b.addEventListener('click', function () {
          state.ap = ap;
          segCol.querySelectorAll('.is-selected').forEach(function (x) { x.classList.remove('is-selected'); });
          b.classList.add('is-selected');
          commit();
        });
        if (state.ap === ap) b.classList.add('is-selected');
        segCol.appendChild(b);
      });
      popup.appendChild(segCol);
    }

    function commit() {
      if (input) input.value = hour12 ? pad(state.h) + ':' + pad(state.m) + ' ' + state.ap
                                       : pad(state.h) + ':' + pad(state.m);
    }
    root.appendChild(popup);
    if (input) {
      input.addEventListener('focus', function () { root.classList.add('is-open'); });
      input.addEventListener('click', function () { root.classList.add('is-open'); });
      document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) root.classList.remove('is-open');
      });
    }
  }

  // ============ Wheel time picker ============
  function initWheelTime(root) {
    if (!root) return;
    var cols = root.querySelectorAll('.forms-timewheel__col');
    var output = root.querySelector('[data-forms-wheel-out]');
    var values = { hour: 9, minute: 0, ap: 'AM' };

    cols.forEach(function (col) {
      var kind = col.getAttribute('data-kind');
      var items = [];
      if (kind === 'hour') { for (var i = 1; i <= 12; i++) items.push(pad(i)); }
      else if (kind === 'minute') { for (var m = 0; m < 60; m += 5) items.push(pad(m)); }
      else if (kind === 'ap') { items = ['AM', 'PM']; }
      col.innerHTML = '<div class="forms-timewheel__pad"></div>' +
        items.map(function (v) { return '<div class="forms-timewheel__item" data-v="' + v + '">' + v + '</div>'; }).join('') +
        '<div class="forms-timewheel__pad"></div>';

      col.addEventListener('scroll', function () {
        var itemsEls = col.querySelectorAll('.forms-timewheel__item');
        var mid = col.scrollTop + col.clientHeight / 2;
        var best = null, dist = Infinity;
        itemsEls.forEach(function (it) {
          var mid2 = it.offsetTop + it.offsetHeight / 2;
          var d = Math.abs(mid - mid2);
          if (d < dist) { dist = d; best = it; }
        });
        itemsEls.forEach(function (it) { it.classList.remove('is-selected'); });
        if (best) {
          best.classList.add('is-selected');
          values[kind] = best.getAttribute('data-v');
          if (output) output.textContent = values.hour + ':' + values.minute + ' ' + values.ap;
        }
      });
      setTimeout(function () { col.scrollTop = 12; col.scrollTop = 0; col.dispatchEvent(new Event('scroll')); }, 30);
    });
  }

  // ============ Analog clock ============
  function initAnalogClock(root) {
    if (!root) return;
    var svg = root.querySelector('svg');
    var hourHand = svg.querySelector('.forms-clock__hand-hour');
    var minHand = svg.querySelector('.forms-clock__hand-min');
    var out = root.querySelector('[data-forms-clock-out]');
    var mode = 'hour';
    var h = 10, m = 10;
    function render() {
      var hAng = (h % 12) * 30 + (m / 60) * 30;
      var mAng = m * 6;
      hourHand.setAttribute('x2', (100 + 32 * Math.sin(hAng * Math.PI / 180)).toFixed(2));
      hourHand.setAttribute('y2', (100 - 32 * Math.cos(hAng * Math.PI / 180)).toFixed(2));
      minHand.setAttribute('x2', (100 + 46 * Math.sin(mAng * Math.PI / 180)).toFixed(2));
      minHand.setAttribute('y2', (100 - 46 * Math.cos(mAng * Math.PI / 180)).toFixed(2));
      if (out) out.textContent = pad(h) + ':' + pad(m);
    }
    svg.addEventListener('click', function (e) {
      var rect = svg.getBoundingClientRect();
      var x = (e.clientX - rect.left) / rect.width * 200 - 100;
      var y = (e.clientY - rect.top) / rect.height * 200 - 100;
      var ang = Math.atan2(x, -y) * 180 / Math.PI;
      if (ang < 0) ang += 360;
      if (mode === 'hour') {
        h = Math.round(ang / 30) || 12;
        mode = 'minute';
      } else {
        m = Math.round(ang / 6) % 60;
        mode = 'hour';
      }
      render();
    });
    root.querySelectorAll('[data-forms-clock-mode]').forEach(function (b) {
      b.addEventListener('click', function () {
        mode = b.getAttribute('data-forms-clock-mode');
        root.querySelectorAll('[data-forms-clock-mode]').forEach(function (x) { x.classList.remove('active'); });
        b.classList.add('active');
      });
    });
    render();
  }

  // ============ Color picker ============
  function hexToRgb(hex) {
    var m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    if (!m) return { r: 0, g: 0, b: 0 };
    return { r: parseInt(m[1], 16), g: parseInt(m[2], 16), b: parseInt(m[3], 16) };
  }
  function rgbToHex(r, g, b) {
    return '#' + [r, g, b].map(function (x) { return pad(Math.max(0, Math.min(255, x)).toString(16)); }).join('');
  }
  function rgbToHsl(r, g, b) {
    r /= 255; g /= 255; b /= 255;
    var max = Math.max(r, g, b), min = Math.min(r, g, b);
    var h, s, l = (max + min) / 2;
    if (max === min) { h = s = 0; }
    else {
      var d = max - min;
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
      switch (max) {
        case r: h = (g - b) / d + (g < b ? 6 : 0); break;
        case g: h = (b - r) / d + 2; break;
        case b: h = (r - g) / d + 4; break;
      }
      h /= 6;
    }
    return { h: Math.round(h * 360), s: Math.round(s * 100), l: Math.round(l * 100) };
  }
  function hslToRgb(h, s, l) {
    h /= 360; s /= 100; l /= 100;
    var r, g, b;
    if (s === 0) { r = g = b = l; }
    else {
      function hue2rgb(p, q, t) {
        if (t < 0) t += 1; if (t > 1) t -= 1;
        if (t < 1 / 6) return p + (q - p) * 6 * t;
        if (t < 1 / 2) return q;
        if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
        return p;
      }
      var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
      var p = 2 * l - q;
      r = hue2rgb(p, q, h + 1 / 3);
      g = hue2rgb(p, q, h);
      b = hue2rgb(p, q, h - 1 / 3);
    }
    return { r: Math.round(r * 255), g: Math.round(g * 255), b: Math.round(b * 255) };
  }

  var PALETTE = [
    '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16', '#22c55e', '#10b981', '#14b8a6',
    '#06b6d4', '#0ea5e9', '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899',
    '#f43f5e', '#78716c', '#64748b', '#374151', '#111827', '#ffffff', '#fef3c7', '#fce7f3'
  ];
  var recentColors = [];

  function initColorPicker(root, opts) {
    if (!root || root.dataset.formsCpInit) return;
    root.dataset.formsCpInit = '1';
    opts = opts || {};
    var swatch = root.querySelector('.forms-colorpicker__swatch');
    var hexInput = root.querySelector('.forms-colorpicker__hex');
    var popup = document.createElement('div');
    popup.className = 'forms-colorpicker__popup';

    var swatchGrid = document.createElement('div');
    swatchGrid.className = 'forms-swatch-grid';
    PALETTE.forEach(function (c) {
      var b = document.createElement('button');
      b.type = 'button';
      b.style.background = c;
      b.setAttribute('aria-label', c);
      b.addEventListener('click', function () { setColor(c); });
      swatchGrid.appendChild(b);
    });
    popup.appendChild(swatchGrid);

    var recentTitle = document.createElement('p');
    recentTitle.style.cssText = 'margin:.55rem 0 .2rem; font-size:.72rem; color:var(--orchid-text-muted); text-transform:uppercase; letter-spacing:.06em; font-weight:600;';
    recentTitle.textContent = 'Recent';
    popup.appendChild(recentTitle);
    var recentEl = document.createElement('div');
    recentEl.className = 'forms-recent';
    popup.appendChild(recentEl);

    var inputs = document.createElement('div');
    inputs.className = 'forms-color-inputs';
    inputs.innerHTML =
      '<input type="text" data-i="hex" placeholder="Hex">' +
      '<input type="text" data-i="rgb" placeholder="RGB">' +
      '<input type="text" data-i="hsl" placeholder="HSL">';
    popup.appendChild(inputs);

    var opacity = document.createElement('input');
    opacity.type = 'range'; opacity.min = 0; opacity.max = 100; opacity.value = 100;
    opacity.className = 'form-range mt-2';
    opacity.setAttribute('aria-label', 'Opacity');
    popup.appendChild(opacity);

    var eyeBtn = document.createElement('button');
    eyeBtn.type = 'button';
    eyeBtn.className = 'btn btn-sm btn-outline-secondary w-100 mt-2';
    eyeBtn.innerHTML = '<i class="bi bi-eyedropper"></i> Eyedropper';
    eyeBtn.addEventListener('click', function () {
      if (window.EyeDropper) {
        try {
          new window.EyeDropper().open().then(function (r) { setColor(r.sRGBHex); }).catch(function () {});
        } catch (er) { orchidToast('Eyedropper unavailable', 'warning'); }
      } else {
        orchidToast('Eyedropper API not supported in this browser', 'warning');
      }
    });
    popup.appendChild(eyeBtn);

    root.appendChild(popup);

    function renderRecent() {
      recentEl.innerHTML = '';
      recentColors.slice(0, 8).forEach(function (c) {
        var b = document.createElement('button');
        b.type = 'button';
        b.style.background = c;
        b.setAttribute('aria-label', c);
        b.addEventListener('click', function () { setColor(c); });
        recentEl.appendChild(b);
      });
    }
    renderRecent();

    function setColor(hex) {
      if (swatch) swatch.style.background = hex;
      if (hexInput) hexInput.value = hex;
      var rgb = hexToRgb(hex);
      var hsl = rgbToHsl(rgb.r, rgb.g, rgb.b);
      inputs.querySelector('[data-i="hex"]').value = hex;
      inputs.querySelector('[data-i="rgb"]').value = rgb.r + ',' + rgb.g + ',' + rgb.b;
      inputs.querySelector('[data-i="hsl"]').value = hsl.h + ',' + hsl.s + ',' + hsl.l;
      swatchGrid.querySelectorAll('.is-selected').forEach(function (x) { x.classList.remove('is-selected'); });
      if (recentColors.indexOf(hex) === -1) {
        recentColors.unshift(hex);
        if (recentColors.length > 12) recentColors.pop();
        renderRecent();
      }
      if (opts.onChange) opts.onChange(hex);
    }

    inputs.querySelector('[data-i="hex"]').addEventListener('change', function (e) {
      setColor(e.target.value);
    });
    inputs.querySelector('[data-i="rgb"]').addEventListener('change', function (e) {
      var m = e.target.value.split(',').map(function (x) { return parseInt(x.trim(), 10); });
      if (m.length === 3) setColor(rgbToHex(m[0], m[1], m[2]));
    });
    inputs.querySelector('[data-i="hsl"]').addEventListener('change', function (e) {
      var m = e.target.value.split(',').map(function (x) { return parseInt(x.trim(), 10); });
      if (m.length === 3) {
        var rgb = hslToRgb(m[0], m[1], m[2]);
        setColor(rgbToHex(rgb.r, rgb.g, rgb.b));
      }
    });

    if (hexInput) {
      hexInput.addEventListener('focus', function () { root.classList.add('is-open'); });
      hexInput.addEventListener('click', function () { root.classList.add('is-open'); });
      hexInput.addEventListener('change', function () { setColor(hexInput.value); });
    }
    document.addEventListener('click', function (e) {
      if (!root.contains(e.target)) root.classList.remove('is-open');
    });
    setColor(opts.initial || hexInput && hexInput.value || '#4f46e5');
  }

  // ============ HSL sliders ============
  function initHslSlider(root) {
    if (!root) return;
    var h = root.querySelector('[data-hsl="h"]');
    var s = root.querySelector('[data-hsl="s"]');
    var l = root.querySelector('[data-hsl="l"]');
    var preview = root.querySelector('[data-hsl-preview]');
    var out = root.querySelector('[data-hsl-out]');
    function update() {
      var rgb = hslToRgb(+h.value, +s.value, +l.value);
      var hex = rgbToHex(rgb.r, rgb.g, rgb.b);
      if (preview) preview.style.background = hex;
      if (out) out.textContent = hex + ' · hsl(' + h.value + ', ' + s.value + '%, ' + l.value + '%)';
    }
    [h, s, l].forEach(function (i) { if (i) i.addEventListener('input', update); });
    update();
  }

  // ============ Gradient picker ============
  function initGradientPicker(root) {
    if (!root) return;
    var c1 = root.querySelector('[data-g="c1"]');
    var c2 = root.querySelector('[data-g="c2"]');
    var ang = root.querySelector('[data-g="ang"]');
    var preview = root.querySelector('[data-g-preview]');
    var out = root.querySelector('[data-g-out]');
    function update() {
      var css = 'linear-gradient(' + ang.value + 'deg, ' + c1.value + ', ' + c2.value + ')';
      if (preview) preview.style.background = css;
      if (out) out.textContent = css;
    }
    [c1, c2, ang].forEach(function (i) { if (i) i.addEventListener('input', update); });
    update();
  }

  // ============ Theme palette ============
  function initThemePalette(root) {
    if (!root) return;
    root.querySelectorAll('.forms-theme-palette__swatch').forEach(function (sw) {
      // Paint the swatch with its declared color
      var initial = sw.getAttribute('data-color');
      if (initial) sw.style.background = initial;
      sw.addEventListener('click', function () {
        var input = document.createElement('input');
        input.type = 'color';
        input.value = sw.getAttribute('data-color') || '#4f46e5';
        input.style.position = 'fixed'; input.style.left = '-9999px';
        document.body.appendChild(input);
        input.click();
        input.addEventListener('change', function () {
          sw.style.background = input.value;
          sw.setAttribute('data-color', input.value);
          var val = sw.parentElement.querySelector('.forms-theme-palette__value');
          if (val) val.textContent = input.value;
          input.remove();
        }, { once: true });
      });
    });
  }

  // ============ Dual range ============
  function initDualRange(root) {
    if (!root) return;
    var min = root.querySelector('[data-range="min"]');
    var max = root.querySelector('[data-range="max"]');
    var fill = root.querySelector('.forms-range-dual__fill');
    var out = root.querySelector('[data-range-out]');
    function update() {
      var lo = Math.min(+min.value, +max.value);
      var hi = Math.max(+min.value, +max.value);
      var span = +min.max - +min.min;
      var leftPct = ((lo - +min.min) / span) * 100;
      var rightPct = ((hi - +min.min) / span) * 100;
      if (fill) {
        fill.style.left = leftPct + '%';
        fill.style.width = (rightPct - leftPct) + '%';
      }
      if (out) out.textContent = '$' + lo + ' – $' + hi;
    }
    if (min) min.addEventListener('input', update);
    if (max) max.addEventListener('input', update);
    update();
  }

  // ============ Indeterminate checkbox ============
  function initIndeterminate() {
    document.querySelectorAll('[data-forms-indeterminate]').forEach(function (cb) {
      cb.indeterminate = true;
    });
  }

  // ============ Simple range value output ============
  function initRangeOut() {
    document.querySelectorAll('[data-forms-range-out]').forEach(function (input) {
      var out = document.querySelector(input.getAttribute('data-forms-range-out'));
      function update() { if (out) out.textContent = input.value; }
      input.addEventListener('input', update);
      update();
    });
  }

  // ============ Textarea auto-grow ============
  function initAutoGrow() {
    document.querySelectorAll('[data-forms-autogrow]').forEach(function (ta) {
      function grow() {
        ta.style.height = 'auto';
        ta.style.height = ta.scrollHeight + 'px';
      }
      ta.addEventListener('input', grow);
      grow();
    });
  }

  // ============ Boot ============
  function boot() {
    initCodePreview();
    initTocSpy();
    initPasswordStrength();
    initPasswordMatch();
    initUsernameCheck();
    initFormValidation();
    initDropZones();
    initImagePreviews();
    initChunked();
    initIndeterminate();
    initAutoGrow();
    initRangeOut();

    document.querySelectorAll('.forms-select-search').forEach(initSelectSearch);
    document.querySelectorAll('[data-forms-wizard]').forEach(initWizard);
    document.querySelectorAll('.forms-editor').forEach(initRichEditor);

    document.querySelectorAll('[data-forms-datepicker]').forEach(function (el) {
      var opts = {};
      if (el.hasAttribute('data-forms-events')) {
        opts.events = el.getAttribute('data-forms-events').split(',');
      }
      if (el.hasAttribute('data-forms-presets')) {
        var today = new Date();
        opts.presets = [
          { label: 'Today', date: function () { return new Date(); } },
          { label: 'Yesterday', date: function () { var d = new Date(); d.setDate(d.getDate() - 1); return d; } },
          { label: 'Last 7 days', date: function () { var d = new Date(); d.setDate(d.getDate() - 7); return d; } },
          { label: 'Last 30 days', date: function () { var d = new Date(); d.setDate(d.getDate() - 30); return d; } },
          { label: 'This month', date: function () { var d = new Date(); d.setDate(1); return d; } },
          { label: 'Last month', date: function () { var d = new Date(); d.setMonth(d.getMonth() - 1); d.setDate(1); return d; } }
        ];
      }
      if (el.hasAttribute('data-forms-disable-past')) opts.disablePast = true;
      initDatePicker(el, opts);
    });

    document.querySelectorAll('[data-forms-timepicker]').forEach(function (el) {
      initTimePicker(el, { hour12: el.getAttribute('data-forms-timepicker') !== '24' });
    });
    document.querySelectorAll('[data-forms-wheeltime]').forEach(initWheelTime);
    document.querySelectorAll('[data-forms-analog]').forEach(initAnalogClock);

    document.querySelectorAll('[data-forms-colorpicker]').forEach(function (el) { initColorPicker(el); });
    document.querySelectorAll('[data-forms-hsl]').forEach(initHslSlider);
    document.querySelectorAll('[data-forms-gradient]').forEach(initGradientPicker);
    document.querySelectorAll('[data-forms-theme-palette]').forEach(initThemePalette);
    document.querySelectorAll('[data-forms-dual-range]').forEach(initDualRange);

    // Swatch grid quick picker
    document.querySelectorAll('[data-forms-swatch-grid]').forEach(function (grid) {
      var out = document.querySelector(grid.getAttribute('data-forms-swatch-grid'));
      grid.querySelectorAll('button').forEach(function (b) {
        // Paint the swatch button with its declared color
        var color = b.getAttribute('data-color');
        if (color) b.style.background = color;
        b.addEventListener('click', function () {
          var c = b.getAttribute('data-color');
          grid.querySelectorAll('.is-selected').forEach(function (x) { x.classList.remove('is-selected'); });
          b.classList.add('is-selected');
          if (out) {
            out.style.background = c;
            out.textContent = c;
          }
        });
      });
    });
  }

  window.OrchidForms = {
    toast: orchidToast,
    copy: copyToClipboard,
    initWizard: initWizard,
    initDatePicker: initDatePicker,
    initTimePicker: initTimePicker,
    initColorPicker: initColorPicker,
    initRichEditor: initRichEditor
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
