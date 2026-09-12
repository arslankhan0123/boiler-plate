/* =====================================================
   Orchid — Advanced table (page-specific)
   Grouping, aggregations, reorder, resize, pin, pivot, export, bulk
   ===================================================== */
(function () {
  'use strict';

  function toast(msg, type) {
    if (window.OrchidForms && window.OrchidForms.toast) return window.OrchidForms.toast(msg, type);
    console.log('[toast:' + type + ']', msg);
  }

  // ---------- Data ----------
  var CATS = ['Electronics', 'Furniture', 'Apparel', 'Books'];
  var REGIONS = ['NA', 'EU', 'APAC', 'LATAM'];
  var STATUSES = ['Won', 'Lost', 'Pending'];
  var PRODUCTS = ['Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon', 'Zeta', 'Theta', 'Iota'];

  function seed() {
    var rng = 1;
    function rand() { rng = (rng * 9301 + 49297) % 233280; return rng / 233280; }
    var rows = [];
    for (var i = 1; i <= 60; i++) {
      var revenue = Math.round((200 + rand() * 4800) * 100) / 100;
      var cost = Math.round(revenue * (0.3 + rand() * 0.4) * 100) / 100;
      rows.push({
        id: i,
        product: PRODUCTS[i % PRODUCTS.length] + '-' + (100 + i),
        category: CATS[Math.floor(rand() * CATS.length)],
        region: REGIONS[Math.floor(rand() * REGIONS.length)],
        status: STATUSES[Math.floor(rand() * STATUSES.length)],
        units: Math.floor(1 + rand() * 40),
        revenue: revenue,
        cost: cost,
        margin: Math.round((revenue - cost) * 100) / 100
      });
    }
    return rows;
  }

  var COLUMNS = [
    { field: 'product', label: 'Product', type: 'text' },
    { field: 'category', label: 'Category', type: 'text' },
    { field: 'region', label: 'Region', type: 'text' },
    { field: 'status', label: 'Status', type: 'text' },
    { field: 'units', label: 'Units', type: 'number' },
    { field: 'revenue', label: 'Revenue', type: 'currency' },
    { field: 'cost', label: 'Cost', type: 'currency' },
    { field: 'margin', label: 'Margin', type: 'currency' }
  ];

  var state = {
    rows: seed(),
    columnOrder: COLUMNS.map(function (c) { return c.field; }),
    pinned: {}, // field -> 'left'|'right'
    widths: {}, // field -> px
    groupBy: 'none',
    aggregation: 'sum'
  };

  function col(field) { return COLUMNS.filter(function (c) { return c.field === field; })[0]; }

  function fmt(val, type) {
    if (val == null || val === '') return '';
    if (type === 'currency') return '$' + Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (type === 'number') return Number(val).toLocaleString();
    return val;
  }

  function aggregate(rows, field, method) {
    var vals = rows.map(function (r) { return Number(r[field]) || 0; });
    if (!vals.length) return 0;
    if (method === 'sum') return vals.reduce(function (a, b) { return a + b; }, 0);
    if (method === 'avg') return vals.reduce(function (a, b) { return a + b; }, 0) / vals.length;
    if (method === 'min') return Math.min.apply(null, vals);
    if (method === 'max') return Math.max.apply(null, vals);
    if (method === 'count') return vals.length;
    return 0;
  }

  function render() {
    var table = document.getElementById('advTable');
    var thead = table.querySelector('thead');
    var tbody = table.querySelector('tbody');
    var tfoot = table.querySelector('tfoot');

    // Header
    var trh = document.createElement('tr');
    state.columnOrder.forEach(function (field) {
      var c = col(field);
      var th = document.createElement('th');
      th.setAttribute('data-field', field);
      th.setAttribute('draggable', 'true');
      if (state.pinned[field] === 'left') th.classList.add('pin-left');
      if (state.pinned[field] === 'right') th.classList.add('pin-right');
      if (state.widths[field]) th.style.width = state.widths[field] + 'px';
      th.innerHTML = '<span>' + c.label + ' <button type="button" class="btn btn-sm btn-icon p-0 ms-1 tables-adv__col-btn" data-field="' + field + '" aria-label="Column menu"><i class="bi bi-three-dots-vertical"></i></button></span><span class="tables-adv__resize" data-resize="' + field + '"></span>';
      trh.appendChild(th);
    });
    thead.innerHTML = '';
    thead.appendChild(trh);

    // Body
    tbody.innerHTML = '';
    if (state.groupBy === 'none') {
      state.rows.forEach(function (r) { tbody.appendChild(rowEl(r)); });
    } else {
      var groups = {};
      state.rows.forEach(function (r) {
        var key = r[state.groupBy];
        (groups[key] = groups[key] || []).push(r);
      });
      Object.keys(groups).sort().forEach(function (key) {
        var group = groups[key];
        var trg = document.createElement('tr');
        trg.className = 'tables-adv__group-header';
        var revSum = aggregate(group, 'revenue', 'sum');
        trg.innerHTML = '<td colspan="' + state.columnOrder.length + '"><i class="bi bi-chevron-down tables-adv__group-caret"></i>' + state.groupBy + ': <strong>' + key + '</strong> <span class="text-body-secondary ms-2">(' + group.length + ' rows · ' + fmt(revSum, 'currency') + ' revenue)</span></td>';
        tbody.appendChild(trg);
        var childRows = [];
        group.forEach(function (r) {
          var el = rowEl(r);
          el.setAttribute('data-group-child', key);
          tbody.appendChild(el);
          childRows.push(el);
        });
        trg.addEventListener('click', function () {
          trg.classList.toggle('is-collapsed');
          var hidden = trg.classList.contains('is-collapsed');
          childRows.forEach(function (r) { r.style.display = hidden ? 'none' : ''; });
        });
      });
    }

    // Footer
    tfoot.innerHTML = '';
    var trf = document.createElement('tr');
    state.columnOrder.forEach(function (field) {
      var c = col(field);
      var td = document.createElement('td');
      if (state.pinned[field] === 'left') td.classList.add('pin-left');
      if (state.pinned[field] === 'right') td.classList.add('pin-right');
      if (c.type === 'number' || c.type === 'currency') {
        td.textContent = fmt(aggregate(state.rows, field, state.aggregation), c.type);
      } else if (field === state.columnOrder[0]) {
        td.textContent = state.aggregation.toUpperCase();
      }
      trf.appendChild(td);
    });
    tfoot.appendChild(trf);

    bindHeader(thead);
  }

  function rowEl(r) {
    var tr = document.createElement('tr');
    tr.setAttribute('data-id', r.id);
    state.columnOrder.forEach(function (field) {
      var c = col(field);
      var td = document.createElement('td');
      td.setAttribute('data-field', field);
      if (state.pinned[field] === 'left') td.classList.add('pin-left');
      if (state.pinned[field] === 'right') td.classList.add('pin-right');
      td.textContent = fmt(r[field], c.type);
      tr.appendChild(td);
    });
    return tr;
  }

  // ---------- Column reorder ----------
  var dragField = null;
  function bindHeader(thead) {
    thead.querySelectorAll('th').forEach(function (th) {
      th.addEventListener('dragstart', function (e) {
        dragField = th.getAttribute('data-field');
        th.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', dragField); } catch (er) {}
      });
      th.addEventListener('dragend', function () { th.classList.remove('is-dragging'); });
      th.addEventListener('dragover', function (e) { e.preventDefault(); });
      th.addEventListener('drop', function (e) {
        e.preventDefault();
        var target = th.getAttribute('data-field');
        if (!dragField || dragField === target) return;
        var arr = state.columnOrder;
        arr.splice(arr.indexOf(dragField), 1);
        arr.splice(arr.indexOf(target), 0, dragField);
        render();
      });
    });
    // Resize
    thead.querySelectorAll('.tables-adv__resize').forEach(function (h) {
      h.addEventListener('mousedown', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var field = h.getAttribute('data-resize');
        var th = h.parentElement;
        var startX = e.clientX;
        var startW = th.offsetWidth;
        h.classList.add('is-active');
        function move(ev) {
          var newW = Math.max(60, startW + ev.clientX - startX);
          th.style.width = newW + 'px';
          state.widths[field] = newW;
        }
        function up() {
          document.removeEventListener('mousemove', move);
          document.removeEventListener('mouseup', up);
          h.classList.remove('is-active');
        }
        document.addEventListener('mousemove', move);
        document.addEventListener('mouseup', up);
      });
    });
    // Column menu
    thead.querySelectorAll('.tables-adv__col-btn').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var field = btn.getAttribute('data-field');
        openColMenu(btn, field);
      });
    });
  }

  var openMenu = null;
  function openColMenu(anchor, field) {
    closeColMenu();
    var m = document.createElement('div');
    m.className = 'tables-adv__col-menu is-open';
    m.innerHTML =
      '<button type="button" data-act="pin-left"><i class="bi bi-pin-angle"></i> Pin left</button>' +
      '<button type="button" data-act="pin-right"><i class="bi bi-pin-angle-fill"></i> Pin right</button>' +
      '<button type="button" data-act="unpin"><i class="bi bi-x-circle"></i> Unpin</button>' +
      '<button type="button" data-act="hide"><i class="bi bi-eye-slash"></i> Hide column</button>';
    document.body.appendChild(m);
    var rect = anchor.getBoundingClientRect();
    m.style.left = (rect.left + window.scrollX) + 'px';
    m.style.top = (rect.bottom + window.scrollY + 4) + 'px';
    openMenu = m;
    m.addEventListener('click', function (e) {
      var b = e.target.closest('button');
      if (!b) return;
      var act = b.getAttribute('data-act');
      if (act === 'pin-left') state.pinned[field] = 'left';
      if (act === 'pin-right') state.pinned[field] = 'right';
      if (act === 'unpin') delete state.pinned[field];
      if (act === 'hide') { state.columnOrder = state.columnOrder.filter(function (f) { return f !== field; }); }
      closeColMenu();
      render();
    });
    setTimeout(function () {
      document.addEventListener('click', closeColMenu, { once: true });
    }, 10);
  }
  function closeColMenu() {
    if (openMenu) { openMenu.remove(); openMenu = null; }
  }

  // ---------- Export ----------
  function exportCSV() {
    var head = state.columnOrder.map(function (f) { return col(f).label; }).join(',');
    var body = state.rows.map(function (r) {
      return state.columnOrder.map(function (f) {
        var v = r[f]; if (typeof v === 'string' && v.indexOf(',') !== -1) v = '"' + v + '"';
        return v;
      }).join(',');
    }).join('\n');
    downloadFile('orchid-sales.csv', 'text/csv', head + '\n' + body);
  }
  function exportJSON() { downloadFile('orchid-sales.json', 'application/json', JSON.stringify(state.rows, null, 2)); }
  function downloadFile(name, mime, content) {
    var blob = new Blob([content], { type: mime });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = name;
    a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); }, 100);
    toast('Exported ' + name, 'success');
  }

  // ---------- Pivot lite ----------
  function initPivot() {
    var pivotWrap = document.getElementById('advPivotZones');
    var pivotResult = document.getElementById('advPivotResult');
    var chipsRows = pivotWrap.querySelector('[data-zone="rows"]');
    var chipsVals = pivotWrap.querySelector('[data-zone="values"]');

    document.querySelectorAll('#advPivotSource .tables-adv__chip').forEach(function (chip) {
      chip.setAttribute('draggable', 'true');
      chip.addEventListener('dragstart', function (e) {
        e.dataTransfer.setData('text/plain', chip.getAttribute('data-field'));
      });
    });

    pivotWrap.querySelectorAll('.tables-adv__zone').forEach(function (zone) {
      zone.addEventListener('dragover', function (e) { e.preventDefault(); zone.classList.add('is-dragover'); });
      zone.addEventListener('dragleave', function () { zone.classList.remove('is-dragover'); });
      zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('is-dragover');
        var f = e.dataTransfer.getData('text/plain');
        if (!f || zone.querySelector('[data-field="' + f + '"]')) return;
        var chip = document.createElement('span');
        chip.className = 'tables-adv__chip';
        chip.setAttribute('data-field', f);
        chip.innerHTML = f + ' <button type="button" class="btn-close" aria-label="Remove"></button>';
        chip.querySelector('button').addEventListener('click', function () { chip.remove(); renderPivot(); });
        zone.appendChild(chip);
        renderPivot();
      });
    });

    function renderPivot() {
      var rows = Array.prototype.map.call(chipsRows.querySelectorAll('[data-field]'), function (c) { return c.getAttribute('data-field'); });
      var vals = Array.prototype.map.call(chipsVals.querySelectorAll('[data-field]'), function (c) { return c.getAttribute('data-field'); });
      if (!rows.length || !vals.length) { pivotResult.innerHTML = '<p class="text-body-secondary small mb-0">Drop a field into <strong>Rows</strong> and <strong>Values</strong> to build a pivot.</p>'; return; }
      var groups = {};
      state.rows.forEach(function (r) {
        var key = rows.map(function (rf) { return r[rf]; }).join(' · ');
        (groups[key] = groups[key] || []).push(r);
      });
      var html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>' + rows.join(' · ') + '</th>';
      vals.forEach(function (v) { html += '<th class="text-end">' + v + ' (sum)</th>'; });
      html += '</tr></thead><tbody>';
      Object.keys(groups).sort().forEach(function (k) {
        html += '<tr><td>' + k + '</td>';
        vals.forEach(function (v) { html += '<td class="text-end">' + fmt(aggregate(groups[k], v, 'sum'), col(v) ? col(v).type : 'number') + '</td>'; });
        html += '</tr>';
      });
      html += '</tbody></table></div>';
      pivotResult.innerHTML = html;
    }
    renderPivot();
  }

  // ---------- Boot ----------
  function init() {
    render();

    document.getElementById('advGroupBy').addEventListener('change', function (e) {
      state.groupBy = e.target.value;
      render();
    });
    document.getElementById('advAggregation').addEventListener('change', function (e) {
      state.aggregation = e.target.value;
      render();
    });
    document.getElementById('advExportCSV').addEventListener('click', exportCSV);
    document.getElementById('advExportJSON').addEventListener('click', exportJSON);
    document.getElementById('advExportPDF').addEventListener('click', function () { toast('PDF export would use a library like jsPDF', 'primary'); });
    document.getElementById('advExportExcel').addEventListener('click', function () { toast('Excel export would use SheetJS', 'primary'); });
    document.getElementById('advBulkUpdate').addEventListener('click', function () {
      var modal = new bootstrap.Modal(document.getElementById('advBulkModal'));
      modal.show();
    });
    document.getElementById('advBulkApply').addEventListener('click', function () {
      var field = document.getElementById('advBulkField').value;
      var val = document.getElementById('advBulkValue').value;
      if (!field || !val) return;
      state.rows.forEach(function (r) { r[field] = val; });
      render();
      bootstrap.Modal.getInstance(document.getElementById('advBulkModal')).hide();
      toast('Bulk-updated ' + state.rows.length + ' rows', 'success');
    });

    initPivot();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
