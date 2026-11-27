// =====================================================
// Orchid - Authentication pages shared behavior
// Handles: form validation, password toggle, strength meter,
//          OTP auto-tab, resend timer, toasts, social buttons
// =====================================================

(function () {
  'use strict';

  // ---------- Toast utility ----------
  const ensureToastWrap = () => {
    let wrap = document.querySelector('.auth-toast-wrap');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.className = 'auth-toast-wrap';
      wrap.setAttribute('aria-live', 'polite');
      wrap.setAttribute('aria-atomic', 'true');
      document.body.appendChild(wrap);
    }
    return wrap;
  };

  const iconFor = (variant) => {
    switch (variant) {
      case 'success': return 'bi-check-circle-fill';
      case 'danger':  return 'bi-exclamation-triangle-fill';
      default:        return 'bi-info-circle-fill';
    }
  };

  const showToast = (message, variant = 'info', duration = 3200) => {
    const wrap = ensureToastWrap();
    const toast = document.createElement('div');
    toast.className = 'auth-toast auth-toast--' + variant;
    toast.setAttribute('role', 'status');
    toast.innerHTML =
      '<i class="bi ' + iconFor(variant) + '"></i>' +
      '<span></span>';
    toast.querySelector('span').textContent = message;
    wrap.appendChild(toast);

    setTimeout(() => {
      toast.classList.add('is-leaving');
      toast.addEventListener('animationend', () => toast.remove(), { once: true });
    }, duration);
  };

  // ---------- Password toggle ----------
  const initPasswordToggles = () => {
    document.querySelectorAll('[data-auth-password-toggle]').forEach((btn) => {
      const targetId = btn.getAttribute('data-auth-password-toggle');
      const input = document.getElementById(targetId);
      if (!input) return;
      btn.addEventListener('click', () => {
        const isPwd = input.type === 'password';
        input.type = isPwd ? 'text' : 'password';
        const icon = btn.querySelector('i');
        if (icon) {
          icon.classList.toggle('bi-eye', !isPwd);
          icon.classList.toggle('bi-eye-slash', isPwd);
        }
        btn.setAttribute('aria-label', isPwd ? 'Hide password' : 'Show password');
      });
    });
  };

  // ---------- Password strength ----------
  const scorePassword = (pwd) => {
    if (!pwd) return 0;
    let score = 0;
    if (pwd.length >= 8) score++;
    if (pwd.length >= 12) score++;
    if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) score++;
    if (/\d/.test(pwd)) score++;
    if (/[^A-Za-z0-9]/.test(pwd)) score++;
    // clamp to 1..4 (0 for empty)
    return Math.min(4, Math.max(1, score));
  };

  const labelFor = (level) => {
    switch (level) {
      case 1: return 'Weak';
      case 2: return 'Fair';
      case 3: return 'Good';
      case 4: return 'Strong';
      default: return 'Enter a password';
    }
  };

  const initStrengthMeters = () => {
    document.querySelectorAll('[data-auth-strength-for]').forEach((meter) => {
      const targetId = meter.getAttribute('data-auth-strength-for');
      const input = document.getElementById(targetId);
      if (!input) return;
      const labelEl = meter.querySelector('.auth-strength__label-text');
      const update = () => {
        const level = input.value ? scorePassword(input.value) : 0;
        if (level === 0) {
          meter.removeAttribute('data-level');
        } else {
          meter.setAttribute('data-level', String(level));
        }
        if (labelEl) labelEl.textContent = labelFor(level);
      };
      input.addEventListener('input', update);
      update();
    });
  };

  // ---------- Form validation ----------
  const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  const setInvalid = (input, message) => {
    input.classList.add('is-invalid');
    const feedback = input.closest('.auth-field, .form-check, .mb-3, div')?.querySelector('.invalid-feedback');
    if (feedback && message) feedback.textContent = message;
  };
  const clearInvalid = (input) => input.classList.remove('is-invalid');

  const validateField = (input) => {
    const type = input.dataset.authValidate;
    const value = input.value.trim();

    if (input.hasAttribute('required') && !value) {
      setInvalid(input, 'This field is required.');
      return false;
    }

    if (type === 'email') {
      if (!EMAIL_RE.test(value)) {
        setInvalid(input, 'Enter a valid email address.');
        return false;
      }
    }
    if (type === 'password') {
      if (value.length < 8) {
        setInvalid(input, 'Password must be at least 8 characters.');
        return false;
      }
    }
    if (type === 'name') {
      if (value.length < 2) {
        setInvalid(input, 'Please enter your full name.');
        return false;
      }
    }
    if (type === 'confirm') {
      const matchId = input.dataset.authMatch;
      const other = matchId ? document.getElementById(matchId) : null;
      if (!other || other.value !== input.value) {
        setInvalid(input, 'Passwords do not match.');
        return false;
      }
    }

    clearInvalid(input);
    return true;
  };

  const initFormValidation = () => {
    document.querySelectorAll('[data-auth-form]').forEach((form) => {
      const fields = form.querySelectorAll('[data-auth-validate]');
      fields.forEach((f) => {
        f.addEventListener('blur', () => validateField(f));
        f.addEventListener('input', () => {
          if (f.classList.contains('is-invalid')) validateField(f);
        });
      });

      form.addEventListener('submit', (e) => {
        e.preventDefault();
        let allValid = true;
        fields.forEach((f) => { if (!validateField(f)) allValid = false; });

        // Required checkboxes (terms)
        form.querySelectorAll('[data-auth-required-check]').forEach((chk) => {
          if (!chk.checked) {
            chk.classList.add('is-invalid');
            allValid = false;
          } else {
            chk.classList.remove('is-invalid');
          }
        });

        if (!allValid) {
          showToast('Please fix the errors below.', 'danger');
          return;
        }

        const submitBtn = form.querySelector('[data-auth-submit]');
        if (submitBtn) {
          const originalHtml = submitBtn.innerHTML;
          submitBtn.disabled = true;
          submitBtn.innerHTML =
            '<span class="spinner-border" role="status" aria-hidden="true"></span>' +
            '<span>' + (submitBtn.dataset.loadingText || 'Please wait...') + '</span>';

          setTimeout(() => {
            const success = form.dataset.authSuccess || 'Success!';
            showToast(success, 'success');

            const redirect = form.dataset.authRedirect;
            const successState = form.dataset.authSuccessState;

            if (successState) {
              const stateEl = document.getElementById(successState);
              if (stateEl) {
                form.classList.add('auth-hidden');
                stateEl.classList.remove('auth-hidden');
                // Simulate redirect after showing success
                setTimeout(() => {
                  showToast('Redirecting to sign in...', 'info');
                }, 1400);
                return;
              }
            }

            if (redirect) {
              setTimeout(() => { window.location.href = redirect; }, 900);
            }

            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHtml;
          }, 1200);
        }
      });
    });
  };

  // ---------- OTP inputs ----------
  const initOtp = () => {
    document.querySelectorAll('[data-auth-otp]').forEach((container) => {
      const inputs = Array.from(container.querySelectorAll('.auth-otp__input'));
      const verifyBtn = document.querySelector(container.dataset.authOtpSubmit || '') || null;

      const updateVerifyState = () => {
        const allFilled = inputs.every((i) => i.value.length === 1);
        if (verifyBtn) verifyBtn.disabled = !allFilled;
      };

      inputs.forEach((input, idx) => {
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('maxlength', '1');
        input.setAttribute('autocomplete', 'one-time-code');

        input.addEventListener('input', (e) => {
          // Only digits
          input.value = input.value.replace(/\D/g, '').slice(0, 1);
          if (input.value) {
            input.classList.add('is-filled');
            input.classList.remove('is-invalid');
            if (idx < inputs.length - 1) inputs[idx + 1].focus();
          } else {
            input.classList.remove('is-filled');
          }
          updateVerifyState();
        });

        input.addEventListener('keydown', (e) => {
          if (e.key === 'Backspace' && !input.value && idx > 0) {
            const prev = inputs[idx - 1];
            prev.focus();
            prev.value = '';
            prev.classList.remove('is-filled');
            updateVerifyState();
          }
          if (e.key === 'ArrowLeft' && idx > 0) inputs[idx - 1].focus();
          if (e.key === 'ArrowRight' && idx < inputs.length - 1) inputs[idx + 1].focus();
        });

        input.addEventListener('paste', (e) => {
          e.preventDefault();
          const text = (e.clipboardData || window.clipboardData).getData('text');
          const digits = text.replace(/\D/g, '').slice(0, inputs.length);
          if (!digits) return;
          digits.split('').forEach((d, i) => {
            if (inputs[i]) {
              inputs[i].value = d;
              inputs[i].classList.add('is-filled');
            }
          });
          const nextIdx = Math.min(digits.length, inputs.length - 1);
          inputs[nextIdx].focus();
          updateVerifyState();
        });

        input.addEventListener('focus', () => input.select());
      });

      updateVerifyState();

      // Verify handler
      if (verifyBtn) {
        verifyBtn.addEventListener('click', (e) => {
          e.preventDefault();
          const code = inputs.map((i) => i.value).join('');
          if (code.length !== inputs.length) {
            showToast('Enter all 6 digits.', 'danger');
            return;
          }
          const originalHtml = verifyBtn.innerHTML;
          verifyBtn.disabled = true;
          verifyBtn.innerHTML =
            '<span class="spinner-border" role="status" aria-hidden="true"></span>' +
            '<span>Verifying...</span>';
          setTimeout(() => {
            // Simulate: accept "000000" as invalid for demo purposes; others pass
            if (code === '000000') {
              inputs.forEach((i) => i.classList.add('is-invalid'));
              showToast('Invalid verification code.', 'danger');
              verifyBtn.disabled = false;
              verifyBtn.innerHTML = originalHtml;
              return;
            }
            showToast('Verification successful.', 'success');
            setTimeout(() => { showToast('Redirecting to your dashboard...', 'info'); }, 900);
          }, 1200);
        });
      }
    });
  };

  // ---------- Resend timer ----------
  const initResendTimers = () => {
    document.querySelectorAll('[data-auth-resend]').forEach((btn) => {
      const secondsAttr = parseInt(btn.dataset.authResend, 10);
      const startSeconds = isNaN(secondsAttr) ? 30 : secondsAttr;
      const timerEl = document.querySelector(btn.dataset.authResendTimer || '');
      let remaining = startSeconds;
      let intervalId = null;

      const setLabel = () => {
        if (timerEl) {
          timerEl.textContent = remaining > 0 ? ('Resend in ' + remaining + 's') : 'Didn\'t receive the code?';
        }
      };

      const startCountdown = () => {
        btn.disabled = true;
        remaining = startSeconds;
        setLabel();
        clearInterval(intervalId);
        intervalId = setInterval(() => {
          remaining--;
          setLabel();
          if (remaining <= 0) {
            clearInterval(intervalId);
            btn.disabled = false;
          }
        }, 1000);
      };

      btn.addEventListener('click', (e) => {
        e.preventDefault();
        if (btn.disabled) return;
        showToast('A new code has been sent.', 'success');
        startCountdown();
      });

      startCountdown();
    });
  };

  // ---------- Social sign-in ----------
  const initSocial = () => {
    document.querySelectorAll('[data-auth-social]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const provider = btn.dataset.authSocial;
        showToast('Redirecting to ' + provider + '...', 'info');
      });
    });
  };

  // ---------- Sign out / other action links ----------
  const initActionLinks = () => {
    document.querySelectorAll('[data-auth-action]').forEach((link) => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const action = link.dataset.authAction;
        const msg = link.dataset.authMessage || 'Redirecting...';
        showToast(msg, 'info');
        const redirect = link.dataset.authRedirect;
        if (redirect) setTimeout(() => { window.location.href = redirect; }, 900);
      });
    });
  };

  // ---------- Bootstrap ----------
  document.addEventListener('DOMContentLoaded', () => {
    initPasswordToggles();
    initStrengthMeters();
    initFormValidation();
    initOtp();
    initResendTimers();
    initSocial();
    initActionLinks();
  });
})();
