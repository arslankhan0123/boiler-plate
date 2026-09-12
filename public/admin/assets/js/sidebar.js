// =====================================================
// Orchid - Sidebar (mobile off-canvas + desktop collapse)
// =====================================================

const MOBILE_BP = 992;
const COLLAPSED_KEY = 'orchid-sidebar-collapsed';

const sidebar = document.getElementById('orchidSidebar');
const backdrop = document.querySelector('.orchid-backdrop');
const body = document.body;

const isMobile = () => window.innerWidth < MOBILE_BP;

const openMobile = () => {
  sidebar?.classList.add('is-open');
  backdrop?.classList.add('is-visible');
  document.querySelectorAll('[data-orchid-sidebar-toggle]').forEach((t) => t.setAttribute('aria-expanded', 'true'));
};

const closeMobile = () => {
  sidebar?.classList.remove('is-open');
  backdrop?.classList.remove('is-visible');
  document.querySelectorAll('[data-orchid-sidebar-toggle]').forEach((t) => t.setAttribute('aria-expanded', 'false'));
};

const toggleDesktopCollapse = () => {
  body.classList.toggle('orchid-sidebar-collapsed');
  localStorage.setItem(COLLAPSED_KEY, body.classList.contains('orchid-sidebar-collapsed') ? '1' : '0');
};

// Restore desktop state
if (localStorage.getItem(COLLAPSED_KEY) === '1') {
  body.classList.add('orchid-sidebar-collapsed');
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-orchid-sidebar-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (isMobile()) {
        sidebar?.classList.contains('is-open') ? closeMobile() : openMobile();
      } else {
        toggleDesktopCollapse();
      }
    });
  });

  document.querySelectorAll('[data-orchid-sidebar-close]').forEach((el) => {
    el.addEventListener('click', closeMobile);
  });

  // Leaf-link clicks (both flat top-level links and sub-links)
  sidebar?.querySelectorAll('.orchid-nav__link, .orchid-nav__sublink').forEach((link) => {
    link.addEventListener('click', (e) => {
      // Skip collapse toggles — Bootstrap handles them
      if (link.hasAttribute('data-bs-toggle')) return;

      const targetHref = link.getAttribute('href');
      if (targetHref === '#' || targetHref === null) e.preventDefault();
      if (isMobile()) closeMobile();

      // Clear previous active + trail states
      sidebar.querySelectorAll('.orchid-nav__link.active, .orchid-nav__sublink.active, .orchid-nav__link.trail-active').forEach((a) => {
        a.classList.remove('active', 'trail-active');
        a.removeAttribute('aria-current');
      });

      link.classList.add('active');
      link.setAttribute('aria-current', 'page');

      // If it's a sub-link, mark its parent trigger as a trail
      const parentSub = link.closest('.orchid-nav__sub');
      if (parentSub) {
        const parentTrigger = sidebar.querySelector(`[aria-controls="${parentSub.id}"]`);
        parentTrigger?.classList.add('trail-active');
      }
    });
  });

  // Escape closes mobile drawer
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar?.classList.contains('is-open')) closeMobile();
  });

  // Cleanup on resize
  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      if (!isMobile()) closeMobile();
    }, 120);
  });
});
