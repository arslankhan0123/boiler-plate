;(function () {
  function enhance(sel) {
    if (sel.dataset.ssDone || sel.multiple || sel.size > 1 || sel.hasAttribute('data-no-ss')) return;
    sel.dataset.ssDone = '1';
    var wrap = document.createElement('div');
    wrap.className = 'ss';
    if (sel.style.width) wrap.style.width = sel.style.width;
    if (sel.style.minWidth) wrap.style.minWidth = sel.style.minWidth;
    if (sel.style.maxWidth) wrap.style.maxWidth = sel.style.maxWidth;
    sel.parentNode.insertBefore(wrap, sel);
    wrap.appendChild(sel);
    sel.classList.add('ss-native');
    sel.removeAttribute('style');
    var display = document.createElement('input');
    display.type = 'text';
    display.readOnly = true;
    display.className = 'form-control ss-display' + (sel.classList.contains('is-invalid') ? ' is-invalid' : '');
    if (sel.disabled) display.disabled = true;
    wrap.appendChild(display);
    var menu = document.createElement('div');
    menu.className = 'ss-menu';
    menu.hidden = true;
    menu.innerHTML = '<div class="ss-search-wrap"><input type="text" class="form-control form-control-sm ss-search" placeholder="Search…" autocomplete="off"></div><div class="ss-options"></div>';
    wrap.appendChild(menu);
    var optWrap = menu.querySelector('.ss-options');
    var search = menu.querySelector('.ss-search');
    var items = [];
    Array.prototype.forEach.call(sel.options, function (o) {
      var d = document.createElement('div');
      d.className = 'ss-opt' + (o.disabled ? ' ss-disabled' : '');
      var lbl = (o.textContent || '').trim();
      d.setAttribute('data-value', o.value);
      d.setAttribute('data-label', lbl);
      d.textContent = lbl;
      optWrap.appendChild(d);
      items.push({ el: d, opt: o });
    });
    var empty = document.createElement('div');
    empty.className = 'ss-empty';
    empty.textContent = 'No matches';
    empty.hidden = true;
    optWrap.appendChild(empty);
    function placeholderLabel() {
      var f = sel.options[0];
      return (f && f.value === '') ? (f.textContent || '').trim() : 'Select…';
    }
    function sync() {
      var o = sel.options[sel.selectedIndex];
      var isPh = !o || o.value === '';
      display.value = isPh ? '' : (o.textContent || '').trim();
      display.placeholder = placeholderLabel();
      items.forEach(function (x) { x.el.classList.toggle('ss-selected', x.opt.selected); });
    }
    function filter(q) {
      q = (q || '').toLowerCase();
      var n = 0;
      items.forEach(function (x) {
        var m = x.el.getAttribute('data-label').toLowerCase().indexOf(q) > -1;
        x.el.style.display = m ? '' : 'none';
        if (m) n++;
      });
      empty.hidden = n > 0;
    }
    function vis() {
      return items.filter(function (x) { return x.el.style.display !== 'none'; }).map(function (x) { return x.el; });
    }
    function setActive(el) {
      var a = optWrap.querySelector('.ss-active');
      if (a) a.classList.remove('ss-active');
      if (el) { el.classList.add('ss-active'); el.scrollIntoView({ block: 'nearest' }); }
    }
    function open() {
      if (sel.disabled) return;
      menu.hidden = false;
      wrap.classList.add('is-open');
      search.value = '';
      filter('');
      var s = items.filter(function (x) { return x.opt.selected; })[0];
      setActive(s && s.el.style.display !== 'none' ? s.el : vis()[0]);
      setTimeout(function () { search.focus(); }, 0);
    }
    function close() {
      menu.hidden = true;
      wrap.classList.remove('is-open');
    }
    function choose(el) {
      if (el.classList.contains('ss-disabled')) return;
      sel.value = el.getAttribute('data-value');
      sync();
      close();
      sel.dispatchEvent(new Event('change', { bubbles: true }));
    }
    display.addEventListener('click', function () { menu.hidden ? open() : close(); });
    display.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') { e.preventDefault(); open(); }
    });
    search.addEventListener('input', function () { filter(search.value); setActive(vis()[0]); });
    search.addEventListener('keydown', function (e) {
      var v = vis(), cur = optWrap.querySelector('.ss-active'), i = v.indexOf(cur);
      if (e.key === 'ArrowDown') { e.preventDefault(); setActive(v[Math.min(v.length - 1, i + 1)] || v[0]); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(v[Math.max(0, i - 1)] || v[0]); }
      else if (e.key === 'Enter') { e.preventDefault(); var a = optWrap.querySelector('.ss-active'); if (a) choose(a); }
      else if (e.key === 'Escape') { e.preventDefault(); close(); display.focus(); }
    });
    items.forEach(function (x) { x.el.addEventListener('click', function () { choose(x.el); }); });
    document.addEventListener('click', function (e) { if (!wrap.contains(e.target)) close(); });
    sel.addEventListener('change', function () { sync(); });
    sync();
  }
  function run(root) {
    (root || document).querySelectorAll('select.form-select:not([data-ss-done]):not([multiple]):not([data-no-ss])').forEach(enhance);
  }
  if (document.readyState !== 'loading') run();
  else document.addEventListener('DOMContentLoaded', function () { run(); });
  window.enhanceSelects = run;
})();
