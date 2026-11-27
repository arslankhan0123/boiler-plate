/* =====================================================
   Orchid — Tables Data Grid
   Bootstraps every [data-tables-data-grid] container.
   Uses shared helpers from tables-showcase.js:
     window.tablesSample, window.tablesEsc, window.tablesStatusBadge,
     window.tablesMoney, window.orchidToast, window.exportCsv
   ===================================================== */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-tables-data-grid]').forEach(initGrid);
  });

  function initGrid(root) {
    var esc = window.tablesEsc || function (s) { return String(s == null ? '' : s); };
    var money = window.tablesMoney || function (n) { return '$' + Number(n || 0).toFixed(2); };
    var statusBadge = window.tablesStatusBadge || function (s) { return esc(s); };
    var toast = window.orchidToast || function () {};
    var sample = window.tablesSample || function () { return []; };

    /* -------- Column definitions -------- */
    var COLS = [
      { key: '_check',    label: '',            type: 'check',  sortable: false, width: 40 },
      { key: 'id',        label: 'ID',          type: 'num',    sortable: true,  width: 60 },
      { key: 'name',      label: 'Customer',    type: 'text',   sortable: true },
      { key: 'email',     label: 'Email',       type: 'text',   sortable: true },
      { key: 'company',   label: 'Company',     type: 'text',   sortable: true },
      { key: 'region',    label: 'Region',      type: 'select', sortable: true },
      { key: 'category',  label: 'Category',    type: 'select', sortable: true },
      { key: 'stage',     label: 'Deal Stage',  type: 'select', sortable: true, render: statusBadge },
      { key: 'amount',    label: 'Amount',      type: 'num',    sortable: true, render: money, align: 'end' },
      { key: 'deals',     label: 'Deals',       type: 'num',    sortable: true, align: 'end' },
      { key: 'status',    label: 'Status',      type: 'select', sortable: true, render: statusBadge },
      { key: 'joined',    label: 'Joined',      type: 'date',   sortable: true },
      { key: 'lastActive',label: 'Last Active', type: 'date',   sortable: true },
      { key: '_actions',  label: '',            type: 'actions',sortable: false, width: 44 }
    ];

    /* -------- State -------- */
    var state = {
      data: sample(100, 42),
      visible: {}, // colKey -> boolean
      filters: {}, // colKey -> string
      globalSearch: '',
      sortKey: null,
      sortDir: 'asc',
      page: 1,
      per: 25,
      selected: new Set(),
      density: 'comfort'
    };
    COLS.forEach(function (c) { state.visible[c.key] = true; });

    /* -------- Query cache -------- */
    var els = {
      table:      root.querySelector('[data-grid-table]'),
      thead:      root.querySelector('[data-grid-head]'),
      tbody:      root.querySelector('[data-grid-body]'),
      search:     root.querySelector('[data-grid-search]'),
      colsMenu:   root.querySelector('[data-grid-cols]'),
      densities:  root.querySelectorAll('[data-grid-density]'),
      exports:    root.querySelectorAll('[data-grid-export]'),
      bulk:       root.querySelector('[data-grid-bulk]'),
      bulkCount:  root.querySelector('[data-grid-bulk-count]'),
      bulkActions:root.querySelectorAll('[data-grid-bulk-action]'),
      per:        root.querySelector('[data-grid-per]'),
      summary:    root.querySelector('[data-grid-summary]'),
      pager:      root.querySelector('[data-grid-pager]')
    };

    /* -------- Build column visibility menu -------- */
    if (els.colsMenu) {
      COLS.forEach(function (col) {
        if (col.key === '_check' || col.key === '_actions') return;
        var label = document.createElement('label');
        label.className = 'tables-col-toggle__item';
        label.innerHTML =
          '<input type="checkbox" checked>' +
          '<span>' + esc(col.label) + '</span>';
        label.querySelector('input').addEventListener('change', function (e) {
          state.visible[col.key] = e.target.checked;
          render();
        });
        // Prevent Bootstrap from closing the dropdown when clicking inside
        label.addEventListener('click', function (e) { e.stopPropagation(); });
        els.colsMenu.appendChild(label);
      });
    }

    /* -------- Wire density -------- */
    els.densities.forEach(function (r) {
      r.addEventListener('change', function () {
        state.density = r.value;
        root.classList.remove('is-density-compact', 'is-density-comfort', 'is-density-spacious');
        root.classList.add('is-density-' + state.density);
      });
      if (r.checked) state.density = r.value;
    });
    root.classList.add('is-density-' + state.density);

    /* -------- Wire global search (debounced) -------- */
    var searchTimer = null;
    if (els.search) {
      els.search.addEventListener('input', function (e) {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
          state.globalSearch = e.target.value.trim().toLowerCase();
          state.page = 1;
          render();
        }, 180);
      });
    }

    /* -------- Wire per-page -------- */
    if (els.per) {
      els.per.addEventListener('change', function (e) {
        state.per = parseInt(e.target.value, 10) || 25;
        state.page = 1;
        render();
      });
    }

    /* -------- Wire export -------- */
    els.exports.forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        var kind = a.getAttribute('data-grid-export');
        var rows = filteredSortedRows();
        var visibleCols = COLS.filter(function (c) { return state.visible[c.key] && c.key !== '_check' && c.key !== '_actions'; });
        if (kind === 'csv' && typeof window.exportCsv === 'function') {
          var headerKeys = visibleCols.map(function (c) { return c.key; });
          window.exportCsv(rows, headerKeys, 'customers.csv');
          toast('Downloaded customers.csv', 'success');
        } else if (kind === 'json') {
          try {
            var blob = new Blob([JSON.stringify(rows, null, 2)], { type: 'application/json' });
            var url = URL.createObjectURL(blob);
            var a2 = document.createElement('a');
            a2.href = url; a2.download = 'customers.json';
            document.body.appendChild(a2); a2.click(); a2.remove();
            URL.revokeObjectURL(url);
            toast('Downloaded customers.json', 'success');
          } catch (err) { toast('Export failed', 'danger'); }
        } else {
          toast(kind.toUpperCase() + ' export queued (' + rows.length + ' rows)', 'info');
        }
      });
    });

    /* -------- Wire bulk actions -------- */
    els.bulkActions.forEach(function (b) {
      b.addEventListener('click', function () {
        var action = b.getAttribute('data-grid-bulk-action');
        var count = state.selected.size;
        if (action === 'clear') {
          state.selected.clear();
          render(); updateBulkUi();
          return;
        }
        if (action === 'delete') {
          if (!count) return;
          state.data = state.data.filter(function (r) { return !state.selected.has(r.id); });
          state.selected.clear();
          toast(count + ' rows deleted', 'danger');
          render(); updateBulkUi();
          return;
        }
        if (action === 'archive') {
          state.selected.clear();
          toast(count + ' rows archived', 'warning');
          render(); updateBulkUi();
          return;
        }
        if (action === 'export') {
          toast(count + ' rows exported', 'success');
          return;
        }
      });
    });

    /* -------- Filtering + sorting -------- */
    function filteredSortedRows() {
      var q = state.globalSearch;
      var rows = state.data.filter(function (r) {
        if (q) {
          var hay = (r.name + ' ' + r.email + ' ' + r.company + ' ' + r.region + ' ' + r.category + ' ' + r.stage + ' ' + r.status).toLowerCase();
          if (hay.indexOf(q) < 0) return false;
        }
        for (var k in state.filters) {
          var f = (state.filters[k] || '').toString().trim().toLowerCase();
          if (!f) continue;
          var v = (r[k] == null ? '' : String(r[k])).toLowerCase();
          if (v.indexOf(f) < 0) return false;
        }
        return true;
      });
      if (state.sortKey) {
        var key = state.sortKey;
        var dir = state.sortDir === 'desc' ? -1 : 1;
        rows.sort(function (a, b) {
          var va = a[key], vb = b[key];
          if (typeof va === 'number' && typeof vb === 'number') return (va - vb) * dir;
          return String(va).localeCompare(String(vb)) * dir;
        });
      }
      return rows;
    }

    /* -------- Renderers -------- */
    function render() {
      renderHead();
      renderBody();
    }

    function renderHead() {
      if (!els.thead) return;
      els.thead.innerHTML = '';
      COLS.forEach(function (col) {
        if (!state.visible[col.key]) return;
        var th = document.createElement('th');
        th.scope = 'col';
        if (col.width) th.style.width = col.width + 'px';
        if (col.align === 'end') th.classList.add('text-end');

        if (col.type === 'check') {
          var chk = document.createElement('input');
          chk.type = 'checkbox';
          chk.className = 'form-check-input';
          chk.setAttribute('aria-label', 'Select all rows on this page');
          var visibleRows = pagedRows();
          var allSelected = visibleRows.length > 0 && visibleRows.every(function (r) { return state.selected.has(r.id); });
          var someSelected = visibleRows.some(function (r) { return state.selected.has(r.id); });
          chk.checked = allSelected;
          chk.indeterminate = !allSelected && someSelected;
          chk.addEventListener('change', function () {
            visibleRows.forEach(function (r) {
              if (chk.checked) state.selected.add(r.id);
              else state.selected.delete(r.id);
            });
            render(); updateBulkUi();
          });
          th.appendChild(chk);
          els.thead.appendChild(th);
          return;
        }

        var lbl = document.createElement('span');
        lbl.textContent = col.label;

        if (col.sortable) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'tables-data__sort';
          if (state.sortKey === col.key) {
            btn.classList.add(state.sortDir === 'asc' ? 'is-asc' : 'is-desc');
            th.setAttribute('aria-sort', state.sortDir === 'asc' ? 'ascending' : 'descending');
          } else {
            th.setAttribute('aria-sort', 'none');
          }
          btn.appendChild(lbl);
          var chev = document.createElement('i');
          chev.className = 'bi bi-chevron-expand tables-data__sort-ico';
          if (state.sortKey === col.key) {
            chev.className = 'bi ' + (state.sortDir === 'asc' ? 'bi-chevron-up' : 'bi-chevron-down') + ' tables-data__sort-ico';
          }
          btn.appendChild(chev);
          btn.addEventListener('click', function () {
            if (state.sortKey === col.key) {
              if (state.sortDir === 'asc') state.sortDir = 'desc';
              else { state.sortKey = null; state.sortDir = 'asc'; }
            } else { state.sortKey = col.key; state.sortDir = 'asc'; }
            render();
          });
          th.appendChild(btn);
        } else {
          th.appendChild(lbl);
        }

        if (col.type === 'select' || col.type === 'text' || col.type === 'num' || col.type === 'date') {
          var filterBtn = document.createElement('button');
          filterBtn.type = 'button';
          filterBtn.className = 'tables-data__filter-btn' + (state.filters[col.key] ? ' is-active' : '');
          filterBtn.setAttribute('aria-label', 'Filter ' + col.label);
          filterBtn.innerHTML = '<i class="bi bi-funnel' + (state.filters[col.key] ? '-fill' : '') + '"></i>';
          filterBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            openFilterPopover(filterBtn, col);
          });
          th.appendChild(filterBtn);
        }

        els.thead.appendChild(th);
      });
    }

    function pagedRows() {
      var rows = filteredSortedRows();
      var start = (state.page - 1) * state.per;
      return rows.slice(start, start + state.per);
    }

    function renderBody() {
      if (!els.tbody) return;
      var rows = pagedRows();
      var total = filteredSortedRows().length;

      if (!rows.length) {
        var colCount = COLS.filter(function (c) { return state.visible[c.key]; }).length;
        els.tbody.innerHTML =
          '<tr class="tables-data__empty"><td colspan="' + colCount + '">' +
          '<div class="text-center py-5"><i class="bi bi-inbox display-6 text-body-secondary d-block mb-2"></i>' +
          '<p class="mb-1 fw-semibold">No results found</p>' +
          '<p class="text-body-secondary small mb-0">Try changing your search or filters.</p></div></td></tr>';
      } else {
        var html = '';
        rows.forEach(function (r) {
          var checked = state.selected.has(r.id) ? ' checked' : '';
          html += '<tr' + (state.selected.has(r.id) ? ' class="is-selected"' : '') + '>';
          COLS.forEach(function (col) {
            if (!state.visible[col.key]) return;
            var alignCls = col.align === 'end' ? ' class="text-end"' : '';
            if (col.key === '_check') {
              html += '<td><input type="checkbox" class="form-check-input" data-row-check="' + r.id + '"' + checked + ' aria-label="Select row ' + r.id + '"></td>';
              return;
            }
            if (col.key === '_actions') {
              html += '<td class="text-end">' +
                '<div class="dropdown">' +
                '<button class="btn btn-icon btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Row actions"><i class="bi bi-three-dots-vertical"></i></button>' +
                '<ul class="dropdown-menu dropdown-menu-end">' +
                '<li><a class="dropdown-item" href="#" data-row-action="view" data-row-id="' + r.id + '"><i class="bi bi-eye me-2"></i>View</a></li>' +
                '<li><a class="dropdown-item" href="#" data-row-action="edit" data-row-id="' + r.id + '"><i class="bi bi-pencil me-2"></i>Edit</a></li>' +
                '<li><a class="dropdown-item" href="#" data-row-action="duplicate" data-row-id="' + r.id + '"><i class="bi bi-files me-2"></i>Duplicate</a></li>' +
                '<li><hr class="dropdown-divider"></li>' +
                '<li><a class="dropdown-item text-danger" href="#" data-row-action="delete" data-row-id="' + r.id + '"><i class="bi bi-trash me-2"></i>Delete</a></li>' +
                '</ul></div></td>';
              return;
            }
            var val = r[col.key];
            var display = col.render ? col.render(val) : esc(val);
            html += '<td' + alignCls + '>' + display + '</td>';
          });
          html += '</tr>';
        });
        els.tbody.innerHTML = html;

        els.tbody.querySelectorAll('[data-row-check]').forEach(function (cb) {
          cb.addEventListener('change', function () {
            var id = parseInt(cb.getAttribute('data-row-check'), 10);
            if (cb.checked) state.selected.add(id);
            else state.selected.delete(id);
            renderHead();
            updateBulkUi();
            var tr = cb.closest('tr');
            if (tr) tr.classList.toggle('is-selected', cb.checked);
          });
        });
        els.tbody.querySelectorAll('[data-row-action]').forEach(function (a) {
          a.addEventListener('click', function (e) {
            e.preventDefault();
            var action = a.getAttribute('data-row-action');
            var id = a.getAttribute('data-row-id');
            if (action === 'delete') {
              state.data = state.data.filter(function (r) { return String(r.id) !== id; });
              state.selected.delete(parseInt(id, 10));
              toast('Row ' + id + ' deleted', 'danger');
              render(); updateBulkUi();
            } else if (action === 'duplicate') {
              var src = state.data.find(function (r) { return String(r.id) === id; });
              if (src) {
                var nextId = Math.max.apply(null, state.data.map(function (r) { return r.id; })) + 1;
                var copy = Object.assign({}, src, { id: nextId, name: src.name + ' (copy)' });
                state.data.push(copy);
                toast('Row duplicated as #' + nextId, 'success');
                render();
              }
            } else {
              toast('Action "' + action + '" on row ' + id, 'info');
            }
          });
        });
      }

      renderSummary(total);
      renderPager(total);
      updateBulkUi();
    }

    function renderSummary(total) {
      if (!els.summary) return;
      if (!total) { els.summary.textContent = '0 – 0 of 0'; return; }
      var start = (state.page - 1) * state.per + 1;
      var end = Math.min(state.page * state.per, total);
      els.summary.textContent = start + ' – ' + end + ' of ' + total;
    }

    function renderPager(total) {
      if (!els.pager) return;
      var pages = Math.max(1, Math.ceil(total / state.per));
      if (state.page > pages) state.page = pages;
      var html = '';
      html += '<button type="button" class="btn btn-sm btn-outline-secondary" data-page="prev" aria-label="Previous page"' + (state.page === 1 ? ' disabled' : '') + '><i class="bi bi-chevron-left"></i></button>';

      var nums = pageNumbers(state.page, pages);
      nums.forEach(function (n) {
        if (n === '…') { html += '<span class="btn btn-sm btn-outline-secondary disabled" aria-hidden="true">…</span>'; return; }
        var cls = 'btn btn-sm btn-outline-secondary' + (n === state.page ? ' is-active' : '');
        html += '<button type="button" class="' + cls + '" data-page="' + n + '" aria-label="Page ' + n + '"' + (n === state.page ? ' aria-current="page"' : '') + '>' + n + '</button>';
      });
      html += '<button type="button" class="btn btn-sm btn-outline-secondary" data-page="next" aria-label="Next page"' + (state.page === pages ? ' disabled' : '') + '><i class="bi bi-chevron-right"></i></button>';
      els.pager.innerHTML = html;
      els.pager.querySelectorAll('[data-page]').forEach(function (b) {
        b.addEventListener('click', function () {
          if (b.disabled) return;
          var v = b.getAttribute('data-page');
          if (v === 'prev') state.page = Math.max(1, state.page - 1);
          else if (v === 'next') state.page = Math.min(pages, state.page + 1);
          else state.page = parseInt(v, 10);
          render();
        });
      });
    }

    function pageNumbers(current, total) {
      if (total <= 7) { var arr = []; for (var i = 1; i <= total; i++) arr.push(i); return arr; }
      var out = [1];
      if (current > 3) out.push('…');
      var start = Math.max(2, current - 1);
      var end = Math.min(total - 1, current + 1);
      for (var j = start; j <= end; j++) out.push(j);
      if (current < total - 2) out.push('…');
      out.push(total);
      return out;
    }

    function updateBulkUi() {
      if (!els.bulk) return;
      var count = state.selected.size;
      els.bulk.classList.toggle('is-open', count > 0);
      if (els.bulkCount) els.bulkCount.textContent = String(count);
    }

    /* -------- Per-column filter popover -------- */
    var openPopover = null;
    function openFilterPopover(anchor, col) {
      if (openPopover) { openPopover.remove(); openPopover = null; }
      var pop = document.createElement('div');
      pop.className = 'tables-data__filter-pop';
      pop.setAttribute('role', 'dialog');
      pop.setAttribute('aria-label', 'Filter ' + col.label);

      var input;
      if (col.type === 'select') {
        var options = uniqueValues(col.key);
        input = document.createElement('select');
        input.className = 'form-select form-select-sm';
        input.innerHTML = '<option value="">All</option>' + options.map(function (v) {
          return '<option value="' + esc(v) + '"' + (state.filters[col.key] === v ? ' selected' : '') + '>' + esc(v) + '</option>';
        }).join('');
      } else {
        input = document.createElement('input');
        input.type = col.type === 'date' ? 'date' : (col.type === 'num' ? 'number' : 'search');
        input.className = 'form-control form-control-sm';
        input.value = state.filters[col.key] || '';
        input.placeholder = 'Filter ' + col.label + '…';
      }
      pop.appendChild(input);

      var actions = document.createElement('div');
      actions.className = 'tables-data__filter-actions';
      var clr = document.createElement('button');
      clr.type = 'button'; clr.className = 'btn btn-sm btn-outline-secondary'; clr.textContent = 'Clear';
      var apply = document.createElement('button');
      apply.type = 'button'; apply.className = 'btn btn-sm btn-primary'; apply.textContent = 'Apply';
      actions.appendChild(clr); actions.appendChild(apply);
      pop.appendChild(actions);

      document.body.appendChild(pop);
      var r = anchor.getBoundingClientRect();
      pop.style.top = (window.scrollY + r.bottom + 6) + 'px';
      pop.style.left = (window.scrollX + r.left) + 'px';
      openPopover = pop;

      setTimeout(function () { input.focus(); }, 0);

      clr.addEventListener('click', function () {
        delete state.filters[col.key];
        state.page = 1;
        closePopover(); render();
      });
      apply.addEventListener('click', function () {
        var v = input.value.trim();
        if (v) state.filters[col.key] = v; else delete state.filters[col.key];
        state.page = 1;
        closePopover(); render();
      });
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') apply.click();
        if (e.key === 'Escape') closePopover();
      });
      setTimeout(function () {
        document.addEventListener('click', outsideClose, { once: true });
      }, 0);
    }
    function outsideClose(e) {
      if (openPopover && !openPopover.contains(e.target)) closePopover();
    }
    function closePopover() {
      if (openPopover) { openPopover.remove(); openPopover = null; }
    }

    function uniqueValues(key) {
      var set = {};
      state.data.forEach(function (r) { if (r[key] != null) set[r[key]] = 1; });
      return Object.keys(set).sort();
    }

    /* -------- Initial paint -------- */
    render();
  }
})();
