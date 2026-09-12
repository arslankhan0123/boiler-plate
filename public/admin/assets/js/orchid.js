// =====================================================
// Orchid - App bootstrap (miscellaneous UI wiring)
// =====================================================

document.addEventListener('DOMContentLoaded', () => {
  // Dynamic footer year
  document.querySelectorAll('[data-orchid-year]').forEach((el) => {
    el.textContent = String(new Date().getFullYear());
  });

  // Animate pipeline progress bars to their target width
  requestAnimationFrame(() => {
    document.querySelectorAll('[data-orchid-progress]').forEach((bar) => {
      const value = Math.min(100, Math.max(0, Number(bar.dataset.orchidProgress) || 0));
      bar.style.width = `${value}%`;
      const wrap = bar.closest('.progress');
      if (wrap) wrap.setAttribute('aria-valuenow', String(value));
    });
  });

  // Prevent search form submission (client-side only demo)
  document.querySelectorAll('[data-orchid-search-form]').forEach((form) => {
    form.addEventListener('submit', (e) => e.preventDefault());
  });

  // ⌘K / Ctrl+K → focus search
  const search = document.getElementById('orchidSearch');
  document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      search?.focus();
    }
  });

  // Mobile search trigger scrolls-to & focuses search
  document.querySelectorAll('[data-orchid-search-open]').forEach((btn) => {
    btn.addEventListener('click', () => {
      search?.classList.add('d-flex');
      search?.classList.remove('d-none');
      search?.focus();
    });
  });

  // Task list line-through toggle
  document.querySelectorAll('.orchid-task-list__item .form-check-input').forEach((cb) => {
    cb.addEventListener('change', () => {
      const label = cb.closest('.form-check')?.querySelector('.form-check-label');
      if (!label) return;
      label.classList.toggle('text-decoration-line-through', cb.checked);
      label.classList.toggle('text-body-secondary', cb.checked);
    });
  });
});
