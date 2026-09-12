/* =====================================================
   Orchid — Editable table (page-specific)
   Namespace: data-tables-edit-*
   ===================================================== */
(function () {
  'use strict';

  function toast(msg, type) {
    if (window.OrchidForms && window.OrchidForms.toast) {
      window.OrchidForms.toast(msg, type);
      return;
    }
    // Fallback tiny inline toast
    var t = document.createElement('div');
    t.textContent = msg;
    t.className = 'position-fixed top-0 end-0 m-3 p-2 rounded shadow bg-' + (type || 'primary') + ' text-white';
    t.style.zIndex = 2000;
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 2500);
  }

  var undoStack = [];
  var redoStack = [];
  var MAX_STACK = 10;

  function snapshot(table) {
    var rows = [];
    table.querySelectorAll('tbody tr').forEach(function (tr) {
      var cells = [];
      tr.querySelectorAll('td[data-field]').forEach(function (td) {
        cells.push({ field: td.getAttribute('data-field'), value: td.getAttribute('data-value') || td.textContent.trim() });
      });
      rows.push({ id: tr.getAttribute('data-id'), cells: cells, dirty: tr.classList.contains('is-dirty') });
    });
    return rows;
  }

  function restore(table, snap) {
    var tbody = table.querySelector('tbody');
    tbody.innerHTML = '';
    snap.forEach(function (row) {
      var tr = buildRow(row.id, {});
      row.cells.forEach(function (c) {
        var td = tr.querySelector('[data-field="' + c.field + '"]');
        if (td) setCellValue(td, c.value);
      });
      if (row.dirty) tr.classList.add('is-dirty');
      tbody.appendChild(tr);
    });
    bindRow(table);
  }

  function pushUndo(table) {
    undoStack.push(snapshot(table));
    if (undoStack.length > MAX_STACK) undoStack.shift();
    redoStack.length = 0;
  }

  var COLS = [
    { field: 'name', label: 'Name', type: 'text' },
    { field: 'email', label: 'Email', type: 'email' },
    { field: 'role', label: 'Role', type: 'select', options: ['Admin', 'Editor', 'Viewer'] },
    { field: 'salary', label: 'Salary', type: 'number' },
    { field: 'start', label: 'Start date', type: 'date' },
    { field: 'active', label: 'Active', type: 'checkbox' }
  ];

  var SEED = [
    { id: 1, name: 'Emma Watson', email: 'emma@nova.io', role: 'Admin', salary: 82000, start: '2024-03-12', active: true },
    { id: 2, name: 'James Doe', email: 'james@acme.com', role: 'Editor', salary: 64000, start: '2024-07-04', active: true },
    { id: 3, name: 'Sarah Miller', email: 'sarah@zen.co', role: 'Admin', salary: 91000, start: '2023-09-22', active: false },
    { id: 4, name: 'Ryan Green', email: 'ryan@peak.io', role: 'Viewer', salary: 48000, start: '2025-01-05', active: true },
    { id: 5, name: 'Ava Lee', email: 'ava@bold.co', role: 'Editor', salary: 71500, start: '2024-11-14', active: true }
  ];

  var nextId = 6;

  function setCellValue(td, value) {
    var type = td.getAttribute('data-type');
    td.setAttribute('data-value', value);
    if (type === 'checkbox') {
      td.innerHTML = value === true || value === 'true' ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-circle text-body-secondary"></i>';
    } else if (type === 'salary' || type === 'number') {
      td.textContent = '$' + Number(value).toLocaleString();
    } else if (type === 'email') {
      td.textContent = value;
      var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      td.classList.toggle('is-error', value && !re.test(value));
    } else {
      td.textContent = value;
    }
  }

  function buildRow(id, data) {
    var tr = document.createElement('tr');
    tr.setAttribute('data-id', id);
    tr.setAttribute('draggable', 'true');
    var drag = document.createElement('td');
    drag.className = 'tables-edit__drag';
    drag.innerHTML = '<i class="bi bi-grip-vertical text-body-secondary"></i>';
    tr.appendChild(drag);
    COLS.forEach(function (c) {
      var td = document.createElement('td');
      td.setAttribute('data-field', c.field);
      td.setAttribute('data-type', c.type);
      td.setAttribute('tabindex', '0');
      var v = data[c.field] !== undefined ? data[c.field] : '';
      setCellValue(td, v);
      tr.appendChild(td);
    });
    var status = document.createElement('td');
    status.className = 'tables-edit__status';
    status.innerHTML = '<span class="badge bg-warning-subtle text-warning tables-edit__pill">Unsaved</span>';
    tr.appendChild(status);
    var actions = document.createElement('td');
    actions.className = 'text-end';
    actions.innerHTML = '<div class="btn-group btn-group-sm" role="group">' +
      '<button type="button" class="btn btn-outline-secondary" data-action="dup" title="Duplicate"><i class="bi bi-files"></i></button>' +
      '<button type="button" class="btn btn-outline-secondary" data-action="discard" title="Discard"><i class="bi bi-arrow-counterclockwise"></i></button>' +
      '<button type="button" class="btn btn-outline-danger" data-action="delete" title="Delete"><i class="bi bi-trash"></i></button>' +
    '</div>';
    tr.appendChild(actions);
    return tr;
  }

  function startEdit(td) {
    if (td.classList.contains('is-editing')) return;
    var type = td.getAttribute('data-type');
    var current = td.getAttribute('data-value') || '';
    var original = current;
    td.classList.add('is-editing');
    td.innerHTML = '';
    var control;
    if (type === 'select') {
      control = document.createElement('select');
      control.className = 'form-select form-select-sm';
      var col = COLS.filter(function (c) { return c.field === td.getAttribute('data-field'); })[0];
      (col.options || []).forEach(function (opt) {
        var o = document.createElement('option');
        o.value = opt; o.textContent = opt;
        if (opt === current) o.selected = true;
        control.appendChild(o);
      });
    } else if (type === 'checkbox') {
      control = document.createElement('input');
      control.type = 'checkbox';
      control.className = 'form-check-input';
      control.checked = current === true || current === 'true';
    } else if (type === 'date') {
      control = document.createElement('input');
      control.type = 'date';
      control.className = 'form-control form-control-sm';
      control.value = current;
    } else if (type === 'number') {
      control = document.createElement('input');
      control.type = 'number';
      control.className = 'form-control form-control-sm';
      control.value = current;
    } else {
      control = document.createElement('input');
      control.type = 'text';
      control.className = 'form-control form-control-sm';
      control.value = current;
    }
    td.appendChild(control);
    control.focus();
    if (control.select) control.select();

    function commit() {
      var val = type === 'checkbox' ? control.checked : control.value;
      td.classList.remove('is-editing');
      setCellValue(td, val);
      if (String(val) !== String(original)) {
        var tr = td.closest('tr');
        tr.classList.add('is-dirty');
      }
    }
    function cancel() {
      td.classList.remove('is-editing');
      setCellValue(td, original);
    }
    control.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); commit(); }
      else if (e.key === 'Escape') { cancel(); }
    });
    control.addEventListener('blur', commit);
  }

  function bindRow(table) {
    table.querySelectorAll('tbody tr').forEach(function (tr) {
      if (tr.dataset.bound) return;
      tr.dataset.bound = '1';

      tr.querySelectorAll('td[data-field]').forEach(function (td) {
        td.addEventListener('dblclick', function () {
          pushUndo(table);
          startEdit(td);
        });
        td.addEventListener('keydown', function (e) {
          if ((e.key === 'Enter' || e.key === 'F2') && !td.classList.contains('is-editing')) {
            e.preventDefault();
            pushUndo(table);
            startEdit(td);
          }
        });
      });

      tr.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;
        var act = btn.getAttribute('data-action');
        pushUndo(table);
        if (act === 'delete') { tr.remove(); toast('Row deleted', 'danger'); }
        else if (act === 'dup') {
          var clone = tr.cloneNode(true);
          clone.setAttribute('data-id', nextId++);
          clone.dataset.bound = '';
          clone.classList.add('is-dirty');
          tr.parentNode.insertBefore(clone, tr.nextSibling);
          bindRow(table);
          toast('Row duplicated', 'success');
        }
        else if (act === 'discard') {
          tr.classList.remove('is-dirty');
          toast('Changes discarded', 'warning');
        }
      });

      // Drag & drop
      tr.addEventListener('dragstart', function (e) {
        tr.classList.add('is-dragging');
        e.dataTransfer.setData('text/plain', tr.getAttribute('data-id'));
        e.dataTransfer.effectAllowed = 'move';
      });
      tr.addEventListener('dragend', function () { tr.classList.remove('is-dragging'); });
      tr.addEventListener('dragover', function (e) { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; });
      tr.addEventListener('drop', function (e) {
        e.preventDefault();
        var srcId = e.dataTransfer.getData('text/plain');
        var src = table.querySelector('tr[data-id="' + srcId + '"]');
        if (!src || src === tr) return;
        pushUndo(table);
        tr.parentNode.insertBefore(src, tr);
      });
    });
  }

  function init() {
    var table = document.getElementById('editableTable');
    if (!table) return;
    var tbody = table.querySelector('tbody');
    SEED.forEach(function (rec) { tbody.appendChild(buildRow(rec.id, rec)); });
    // Seed rows aren't dirty
    tbody.querySelectorAll('tr').forEach(function (tr) { tr.classList.remove('is-dirty'); });
    bindRow(table);

    document.getElementById('editAddRow').addEventListener('click', function () {
      pushUndo(table);
      var tr = buildRow(nextId++, { name: 'New user', email: 'new@orchid.io', role: 'Viewer', salary: 0, start: '', active: true });
      tr.classList.add('is-dirty');
      tbody.appendChild(tr);
      bindRow(table);
      toast('Row added', 'success');
    });

    document.getElementById('editSaveAll').addEventListener('click', function () {
      var dirty = tbody.querySelectorAll('tr.is-dirty');
      if (!dirty.length) { toast('Nothing to save', 'warning'); return; }
      dirty.forEach(function (tr) { tr.classList.remove('is-dirty'); });
      toast('Saved ' + dirty.length + ' change(s)', 'success');
    });

    document.getElementById('editDiscardAll').addEventListener('click', function () {
      tbody.querySelectorAll('tr.is-dirty').forEach(function (tr) { tr.classList.remove('is-dirty'); });
      toast('All changes discarded', 'warning');
    });

    document.addEventListener('keydown', function (e) {
      if (!(e.ctrlKey || e.metaKey)) return;
      if (e.key === 'z' || e.key === 'Z') {
        e.preventDefault();
        var snap = undoStack.pop();
        if (snap) { redoStack.push(snapshot(table)); restore(table, snap); toast('Undo', 'primary'); }
      } else if (e.key === 'y' || e.key === 'Y') {
        e.preventDefault();
        var s2 = redoStack.pop();
        if (s2) { undoStack.push(snapshot(table)); restore(table, s2); toast('Redo', 'primary'); }
      }
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
