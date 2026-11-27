/* Orchid — Customer Groups */
(function () {
  'use strict';
  document.addEventListener('DOMContentLoaded', function () {
    var B = window.BizExtras;
    if (!B) return;

    var groups = [
      { id: 'g1', name: 'VIP Customers', desc: 'High-value customers with LTV > $50K', type: 'Dynamic', color: '#4f46e5', members: 486, rules: 2, updated: '2h ago', criteria: [{ field: 'Tier', op: 'is', value: 'VIP', conj: 'AND' }, { field: 'LTV', op: '>', value: '50000' }] },
      { id: 'g2', name: 'Enterprise Accounts', desc: 'All enterprise-tier accounts', type: 'Dynamic', color: '#8b5cf6', members: 214, rules: 1, updated: '1h ago', criteria: [{ field: 'Tier', op: 'is', value: 'Enterprise' }] },
      { id: 'g3', name: 'At Risk', desc: 'Health score < 50, no order in 60d', type: 'Dynamic', color: '#ef4444', members: 87, rules: 2, updated: '4h ago', criteria: [{ field: 'Health', op: '<', value: '50', conj: 'AND' }, { field: 'Last Order', op: '>', value: '60 days ago' }] },
      { id: 'g4', name: 'Trial Users', desc: 'New customers on trial plan', type: 'Dynamic', color: '#0ea5e9', members: 142, rules: 1, updated: '30m ago', criteria: [{ field: 'Status', op: 'is', value: 'Trial' }] },
      { id: 'g5', name: 'Churned — Reengagement', desc: 'Cancelled in last 90d, past LTV > $5K', type: 'Static', color: '#f97316', members: 68, rules: 0, updated: 'Jul 18', criteria: [] },
      { id: 'g6', name: 'Newsletter Subscribers', desc: 'Opted-in to marketing emails', type: 'Static', color: '#22c55e', members: 8940, rules: 0, updated: 'Jul 12', criteria: [] },
      { id: 'g7', name: 'Beta Testers', desc: 'Enrolled in early-access program', type: 'Static', color: '#eab308', members: 124, rules: 0, updated: 'Jul 10', criteria: [] },
      { id: 'g8', name: 'Healthcare Vertical', desc: 'All healthcare industry customers', type: 'Dynamic', color: '#06b6d4', members: 342, rules: 1, updated: '1d ago', criteria: [{ field: 'Industry', op: 'is', value: 'Healthcare' }] },
      { id: 'g9', name: 'European Region', desc: 'Customers in EU/UK', type: 'Dynamic', color: '#7c3aed', members: 968, rules: 1, updated: '2d ago', criteria: [{ field: 'Region', op: 'is', value: 'EU' }] },
      { id: 'g10', name: 'Recent Refunds', desc: 'Filed refund in last 30d', type: 'Static', color: '#dc2626', members: 34, rules: 0, updated: 'Jul 20', criteria: [] }
    ];

    var currentId = 'g1';
    var listEl = document.querySelector('[data-cgrp-list]');
    var searchEl = document.querySelector('[data-cgrp-search]');
    var query = '';

    function renderList() {
      var arr = groups.filter(function (g) { return !query || g.name.toLowerCase().includes(query.toLowerCase()); });
      listEl.innerHTML = arr.map(function (g) {
        return '<div class="cgrp-card ' + (g.id === currentId ? 'is-active' : '') + '" data-cgrp-id="' + g.id + '" style="--group-color:' + g.color + '">' +
          '<div class="cgrp-card__head"><p class="cgrp-card__title">' + B.escapeHtml(g.name) + '</p><span class="biz-pill" style="background:' + g.color + '20;color:' + g.color + '">' + g.type + '</span></div>' +
          '<p class="small text-body-secondary mb-0 mt-1">' + B.escapeHtml(g.desc) + '</p>' +
          '<div class="cgrp-card__meta"><span><i class="bi bi-people"></i> ' + g.members + '</span><span><i class="bi bi-funnel"></i> ' + g.rules + ' rules</span><span><i class="bi bi-clock"></i> ' + g.updated + '</span></div>' +
          '</div>';
      }).join('');
    }

    function tierBadge(t) {
      var map = { 'New': 'bg-secondary-subtle text-secondary', 'Regular': 'bg-info-subtle text-info', 'VIP': 'bg-warning-subtle text-warning', 'Enterprise': 'bg-primary-subtle text-primary' };
      return '<span class="badge ' + (map[t] || 'bg-secondary-subtle text-secondary') + '">' + t + '</span>';
    }

    function renderDetail() {
      var g = groups.find(function (x) { return x.id === currentId; });
      if (!g) return;
      document.querySelector('[data-cgrp-detail-name]').value = g.name;
      document.querySelector('[data-cgrp-detail-desc]').textContent = g.desc;
      document.querySelector('[data-cgrp-detail-count]').textContent = g.members.toLocaleString();
      document.querySelector('[data-cgrp-detail-refreshed]').textContent = g.updated;
      var typeEl = document.querySelector('[data-cgrp-detail-type]');
      typeEl.textContent = g.type;
      typeEl.style.background = g.color + '20';
      typeEl.style.color = g.color;
      var sw = document.querySelector('[data-cgrp-detail-swatch]');
      sw.style.background = g.color;

      // Rules
      var rEl = document.querySelector('[data-cgrp-rules]');
      var fields = ['Tier', 'LTV', 'Orders', 'Health', 'Industry', 'Region', 'Status', 'Last Order'];
      if (g.type === 'Static') {
        rEl.innerHTML = '<div class="text-center py-3 small text-body-secondary"><i class="bi bi-hand-index d-block mb-1" style="font-size:1.5rem"></i>Static group · members are added manually.</div>';
      } else if (!g.criteria.length) {
        rEl.innerHTML = '<div class="text-center py-3 small text-body-secondary">No conditions defined yet.</div>';
      } else {
        rEl.innerHTML = g.criteria.map(function (c, i) {
          var conj = (i > 0 && g.criteria[i - 1].conj) ? '<div class="cgrp-rule__conj">' + g.criteria[i - 1].conj + '</div>' : '';
          return conj + '<div class="cgrp-rule">' +
            '<select class="form-select form-select-sm">' + fields.map(function (f) { return '<option ' + (f === c.field ? 'selected' : '') + '>' + f + '</option>'; }).join('') + '</select>' +
            '<span class="small text-body-secondary text-center"></span>' +
            '<select class="form-select form-select-sm"><option ' + (c.op === 'is' ? 'selected' : '') + '>is</option><option ' + (c.op === 'is not' ? 'selected' : '') + '>is not</option><option ' + (c.op === '>' ? 'selected' : '') + '>&gt;</option><option ' + (c.op === '<' ? 'selected' : '') + '>&lt;</option></select>' +
            '<input class="form-control form-control-sm" value="' + B.escapeHtml(c.value) + '">' +
            '<button class="biz-icon-btn" type="button" data-cgrp-rm-rule="' + i + '" aria-label="Remove"><i class="bi bi-x-lg"></i></button>' +
          '</div>';
        }).join('');
      }

      // Members preview — pick some real customers
      var sample = B.customers.filter(function (c) {
        if (g.name.includes('VIP')) return c.tier === 'VIP';
        if (g.name.includes('Enterprise')) return c.tier === 'Enterprise';
        if (g.name.includes('At Risk')) return c.status === 'At Risk';
        if (g.name.includes('Trial')) return c.status === 'Trial';
        if (g.name.includes('Healthcare')) return c.industry === 'Healthcare';
        if (g.name.includes('European')) return ['UK', 'DE', 'IE', 'ES', 'FR'].includes(c.country);
        return true;
      }).slice(0, 6);
      var mEl = document.querySelector('[data-cgrp-members]');
      mEl.innerHTML = sample.map(function (c) {
        return '<tr><td><div class="d-flex align-items-center gap-2">' + B.renderAvatar(c.name, 'xs') + '<div><p class="mb-0 small fw-semibold">' + c.name + '</p><small class="text-body-secondary">' + c.company + '</small></div></div></td><td>' + tierBadge(c.tier) + '</td><td>' + B.fmtMoney(c.ltv) + '</td><td>' + c.country + '</td><td class="text-end"><button class="biz-icon-btn" type="button" aria-label="Remove"><i class="bi bi-x-lg"></i></button></td></tr>';
      }).join('');
      B.bindAvatarColors(mEl);
    }

    // Event delegation for group cards
    listEl.addEventListener('click', function (e) {
      var card = e.target.closest('[data-cgrp-id]');
      if (!card) return;
      currentId = card.getAttribute('data-cgrp-id');
      renderList();
      renderDetail();
    });

    searchEl.addEventListener('input', B.debounce(function (e) { query = e.target.value; renderList(); }, 200));

    // Rule builder actions
    document.querySelector('[data-cgrp-add-rule]').addEventListener('click', function () {
      var g = groups.find(function (x) { return x.id === currentId; });
      if (!g) return;
      if (g.type === 'Static') { B.toast("Static groups don't use rules", 'warning'); return; }
      if (g.criteria.length) g.criteria[g.criteria.length - 1].conj = 'AND';
      g.criteria.push({ field: 'Tier', op: 'is', value: 'Regular' });
      g.rules = g.criteria.length;
      renderDetail();
      renderList();
    });

    document.querySelector('[data-cgrp-rules]').addEventListener('click', function (e) {
      var rm = e.target.closest('[data-cgrp-rm-rule]');
      if (!rm) return;
      var g = groups.find(function (x) { return x.id === currentId; });
      g.criteria.splice(+rm.getAttribute('data-cgrp-rm-rule'), 1);
      g.rules = g.criteria.length;
      renderDetail();
      renderList();
    });

    document.querySelector('[data-cgrp-refresh]').addEventListener('click', function () {
      var g = groups.find(function (x) { return x.id === currentId; });
      g.updated = 'just now';
      g.members = Math.floor(g.members * (0.98 + Math.random() * 0.04));
      renderDetail();
      renderList();
      B.toast('Group members refreshed', 'success');
    });

    document.querySelector('[data-cgrp-save]').addEventListener('click', function () {
      var g = groups.find(function (x) { return x.id === currentId; });
      g.name = document.querySelector('[data-cgrp-detail-name]').value;
      renderList();
      B.toast('Group saved', 'success');
    });

    // New group form
    var newForm = document.querySelector('[data-cgrp-new-form]');
    if (newForm) newForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var name = document.querySelector('[data-cgrp-new-name]').value.trim();
      var desc = document.querySelector('[data-cgrp-new-desc]').value.trim();
      var color = document.querySelector('[data-cgrp-new-color]').value;
      var type = document.querySelector('input[name="cgrpNewType"]:checked').value;
      var g = { id: 'g' + (groups.length + 1), name: name, desc: desc || '—', type: type, color: color, members: 0, rules: 0, updated: 'just now', criteria: [] };
      groups.unshift(g);
      currentId = g.id;
      renderList(); renderDetail();
      bootstrap.Modal.getInstance(document.getElementById('cgrpNewModal')).hide();
      newForm.reset();
      B.toast('Group "' + name + '" created', 'success');
    });

    // Add manual list
    var addSearch = document.querySelector('[data-cgrp-add-search]');
    var addList = document.querySelector('[data-cgrp-add-list]');
    function renderAddList() {
      var q = (addSearch.value || '').toLowerCase();
      var arr = B.customers.filter(function (c) { return !q || c.name.toLowerCase().includes(q) || c.company.toLowerCase().includes(q); }).slice(0, 20);
      addList.innerHTML = arr.map(function (c) {
        return '<label class="list-group-item d-flex gap-2 align-items-center"><input type="checkbox" class="form-check-input" value="' + c.id + '">' + B.renderAvatar(c.name, 'xs') + '<span class="small">' + c.name + ' <small class="text-body-secondary">· ' + c.company + '</small></span></label>';
      }).join('');
      B.bindAvatarColors(addList);
    }
    if (addSearch) {
      addSearch.addEventListener('input', B.debounce(renderAddList, 180));
      var modal = document.getElementById('cgrpAddManualModal');
      if (modal) modal.addEventListener('shown.bs.modal', renderAddList);
    }
    document.querySelector('[data-cgrp-add-confirm]').addEventListener('click', function () {
      var n = addList.querySelectorAll('input[type="checkbox"]:checked').length;
      var g = groups.find(function (x) { return x.id === currentId; });
      g.members += n;
      renderList(); renderDetail();
      B.toast(n + ' members added', 'success');
    });

    // Paint KPI icons
    document.querySelectorAll('.biz-kpi__icon[data-bg]').forEach(function (el) {
      el.style.background = el.getAttribute('data-bg');
      el.style.color = el.getAttribute('data-fg');
    });

    renderList();
    renderDetail();
  });
})();
