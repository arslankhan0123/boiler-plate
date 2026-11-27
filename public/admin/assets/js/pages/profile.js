/* =====================================================
   Orchid - Profile & Account Settings interactions
   Namespaces: data-profile-*  data-settings-*
   ===================================================== */
(function () {
  'use strict';

  // ---------- Toast helper ----------
  function ensureToastContainer() {
    var existing = document.querySelector('.profile-toast-container');
    if (existing) return existing;
    var container = document.createElement('div');
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3 profile-toast-container';
    document.body.appendChild(container);
    return container;
  }

  function showToast(message, variant) {
    if (!window.bootstrap || !window.bootstrap.Toast) return;
    var container = ensureToastContainer();
    var v = variant || 'primary';
    var iconMap = {
      primary: 'bi-info-circle-fill',
      success: 'bi-check-circle-fill',
      danger:  'bi-exclamation-octagon-fill',
      warning: 'bi-exclamation-triangle-fill',
      info:    'bi-bell-fill'
    };
    var icon = iconMap[v] || iconMap.primary;
    var el = document.createElement('div');
    el.className = 'toast align-items-center border-0 text-bg-' + v;
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'polite');
    el.setAttribute('aria-atomic', 'true');
    el.innerHTML =
      '<div class="d-flex">' +
        '<div class="toast-body d-flex align-items-center gap-2">' +
          '<i class="bi ' + icon + '"></i>' +
          '<span></span>' +
        '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
      '</div>';
    el.querySelector('.toast-body span').textContent = message;
    container.appendChild(el);
    var t = new window.bootstrap.Toast(el, { delay: 3200 });
    t.show();
    el.addEventListener('hidden.bs.toast', function () { el.remove(); });
  }

  // ---------- Profile page: Follow toggle ----------
  document.querySelectorAll('[data-profile-follow]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var following = btn.classList.toggle('is-following');
      var label = btn.querySelector('[data-profile-follow-label]');
      if (label) label.textContent = following ? 'Following' : 'Follow';
      showToast(following ? 'You are now following Alex Kim.' : 'Unfollowed Alex Kim.', following ? 'success' : 'primary');
    });
  });

  // ---------- Profile page: More menu items ----------
  document.querySelectorAll('[data-profile-more]').forEach(function (item) {
    item.addEventListener('click', function (e) {
      e.preventDefault();
      var action = item.getAttribute('data-profile-more');
      var messages = {
        share:  'Profile link copied to clipboard.',
        report: 'Profile reported to the moderation team.',
        block:  'User blocked. They can no longer contact you.'
      };
      showToast(messages[action] || 'Action executed.', action === 'block' ? 'danger' : 'primary');
    });
  });

  // ---------- Profile page: Message CTA ----------
  document.querySelectorAll('[data-profile-message]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      showToast('Opening conversation with Alex Kim…', 'info');
    });
  });

  // ---------- Profile page: Edit cover ----------
  document.querySelectorAll('[data-profile-cover]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      showToast('Cover image editor coming soon.', 'info');
    });
  });

  // ---------- Avatar upload (works on both pages) ----------
  document.querySelectorAll('[data-profile-avatar]').forEach(function (wrap) {
    var input = wrap.querySelector('input[type="file"]');
    var chip = wrap.querySelector('[data-profile-avatar-trigger]');
    var img = wrap.querySelector('.profile-avatar__img');
    if (!input || !chip) return;
    chip.addEventListener('click', function () { input.click(); });
    chip.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
    });
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file) return;
      if (!/^image\//.test(file.type)) {
        showToast('Please choose an image file.', 'danger');
        return;
      }
      var reader = new FileReader();
      reader.onload = function (ev) {
        if (img) {
          img.src = ev.target.result;
          wrap.classList.add('has-image');
        }
        showToast('Avatar updated. Save changes to apply.', 'success');
      };
      reader.readAsDataURL(file);
    });
  });

  // ---------- Settings: forms ----------
  document.querySelectorAll('[data-settings-form]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      e.stopPropagation();
      form.classList.add('was-validated');
      if (!form.checkValidity()) {
        showToast('Please fix the highlighted fields.', 'warning');
        return;
      }
      var name = form.getAttribute('data-settings-form') || 'Changes';
      showToast(name + ' saved successfully.', 'success');
    });
    var resetBtn = form.querySelector('[data-settings-reset]');
    if (resetBtn) {
      resetBtn.addEventListener('click', function () {
        form.reset();
        form.classList.remove('was-validated');
        showToast('Changes discarded.', 'primary');
      });
    }
  });

  // ---------- Password strength ----------
  function scorePassword(pw) {
    if (!pw) return 0;
    var score = 0;
    if (pw.length >= 8) score++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
    if (/\d/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw) && pw.length >= 10) score++;
    return score;
  }
  var strengthLabels = ['Too weak', 'Weak', 'Fair', 'Strong', 'Excellent'];
  document.querySelectorAll('[data-settings-password]').forEach(function (input) {
    var meter = document.querySelector('[data-settings-strength]');
    var label = document.querySelector('[data-settings-strength-label]');
    if (!meter) return;
    input.addEventListener('input', function () {
      var score = scorePassword(input.value);
      meter.classList.remove('is-1', 'is-2', 'is-3', 'is-4');
      if (score > 0) meter.classList.add('is-' + score);
      if (label) label.textContent = strengthLabels[score] || strengthLabels[0];
    });
  });

  // ---------- Session revoke ----------
  document.querySelectorAll('[data-settings-revoke]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var row = btn.closest('.settings-session');
      if (!row) return;
      var title = row.querySelector('.settings-session__title');
      var name = title ? title.textContent : 'Session';
      row.remove();
      showToast(name + ' revoked.', 'success');
    });
  });

  // ---------- Team invite ----------
  var teamForm = document.querySelector('[data-settings-invite-form]');
  var teamTableBody = document.querySelector('[data-settings-team-body]');
  if (teamForm && teamTableBody) {
    teamForm.addEventListener('submit', function (e) {
      e.preventDefault();
      e.stopPropagation();
      teamForm.classList.add('was-validated');
      if (!teamForm.checkValidity()) {
        showToast('Please enter a valid email address.', 'warning');
        return;
      }
      var emailInput = teamForm.querySelector('input[type="email"]');
      var roleSelect = teamForm.querySelector('select');
      var email = emailInput.value.trim();
      var role = roleSelect ? roleSelect.value : 'Member';
      var initials = email.split('@')[0].slice(0, 2).toUpperCase();
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td><div class="d-flex align-items-center gap-2">' +
          '<span class="avatar avatar-sm bg-info-subtle text-info fw-semibold"></span>' +
          '<div><p class="mb-0 fw-medium"></p><small class="text-body-secondary"></small></div>' +
        '</div></td>' +
        '<td><select class="form-select form-select-sm settings-role-select">' +
          '<option>Owner</option><option>Admin</option><option>Editor</option><option>Member</option><option>Viewer</option>' +
        '</select></td>' +
        '<td><span class="badge bg-warning-subtle text-warning">Invited</span></td>' +
        '<td class="text-end">' +
          '<button class="btn btn-sm btn-outline-secondary me-1" type="button" data-settings-resend>Resend</button>' +
          '<button class="btn btn-sm btn-outline-danger" type="button" data-settings-remove>Remove</button>' +
        '</td>';
      tr.querySelector('.avatar').textContent = initials;
      tr.querySelector('.fw-medium').textContent = email.split('@')[0];
      tr.querySelector('small').textContent = email;
      var select = tr.querySelector('select');
      Array.prototype.forEach.call(select.options, function (o) {
        if (o.value === role || o.textContent === role) o.selected = true;
      });
      teamTableBody.appendChild(tr);
      bindTeamRow(tr);
      teamForm.reset();
      teamForm.classList.remove('was-validated');
      showToast('Invite sent to ' + email + '.', 'success');
    });
  }

  function bindTeamRow(row) {
    var resend = row.querySelector('[data-settings-resend]');
    var remove = row.querySelector('[data-settings-remove]');
    var roleSel = row.querySelector('select');
    if (resend) resend.addEventListener('click', function () {
      var name = row.querySelector('.fw-medium');
      showToast('Invite resent to ' + (name ? name.textContent : 'member') + '.', 'primary');
    });
    if (remove) remove.addEventListener('click', function () {
      var name = row.querySelector('.fw-medium');
      row.remove();
      showToast((name ? name.textContent : 'Member') + ' removed from team.', 'danger');
    });
    if (roleSel) roleSel.addEventListener('change', function () {
      showToast('Role updated to ' + roleSel.value + '.', 'success');
    });
  }
  document.querySelectorAll('[data-settings-team-body] tr').forEach(bindTeamRow);

  // ---------- Delete account confirmation ----------
  var deleteModalEl = document.getElementById('deleteAccountModal');
  if (deleteModalEl) {
    var confirmInput = deleteModalEl.querySelector('[data-settings-delete-input]');
    var confirmBtn = deleteModalEl.querySelector('[data-settings-delete-confirm]');
    var expected = deleteModalEl.getAttribute('data-settings-expected') || 'ACCOUNT_NAME';
    if (confirmInput && confirmBtn) {
      confirmInput.addEventListener('input', function () {
        confirmBtn.disabled = confirmInput.value.trim() !== expected;
      });
      confirmBtn.addEventListener('click', function () {
        showToast('Account deletion scheduled. You will be signed out.', 'danger');
        var modal = window.bootstrap.Modal.getInstance(deleteModalEl);
        if (modal) modal.hide();
        confirmInput.value = '';
        confirmBtn.disabled = true;
      });
      deleteModalEl.addEventListener('hidden.bs.modal', function () {
        confirmInput.value = '';
        confirmBtn.disabled = true;
      });
    }
  }

  // ---------- Danger zone secondary actions ----------
  document.querySelectorAll('[data-settings-danger]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var action = btn.getAttribute('data-settings-danger');
      var messages = {
        export:     'Export started. You will receive an email when ready.',
        deactivate: 'Account deactivated. Sign back in anytime to reactivate.'
      };
      showToast(messages[action] || 'Action executed.', 'warning');
    });
  });

  // ---------- 2FA toggle ----------
  document.querySelectorAll('[data-settings-2fa]').forEach(function (chk) {
    chk.addEventListener('change', function () {
      showToast(chk.checked ? 'Two-factor authentication enabled.' : 'Two-factor authentication disabled.', chk.checked ? 'success' : 'warning');
    });
  });

  // ---------- Notification toggle feedback ----------
  document.querySelectorAll('[data-settings-notify]').forEach(function (chk) {
    chk.addEventListener('change', function () {
      var name = chk.getAttribute('data-settings-notify') || 'Notification';
      showToast(name + (chk.checked ? ' enabled.' : ' disabled.'), 'primary');
    });
  });

  // ---------- Download invoice ----------
  document.querySelectorAll('[data-settings-invoice]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var id = btn.getAttribute('data-settings-invoice');
      showToast('Downloading invoice ' + id + '.pdf', 'info');
    });
  });

  // ---------- Upgrade CTA ----------
  document.querySelectorAll('[data-settings-upgrade]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      showToast('Redirecting to plan comparison…', 'info');
    });
  });

})();
