<header class="orchid-header" role="banner">
      <div class="orchid-header__left">
        <button class="btn btn-icon orchid-header__toggle" type="button" data-orchid-sidebar-toggle aria-label="Toggle sidebar" aria-controls="orchidSidebar" aria-expanded="false">
          <i class="bi bi-list"></i>
        </button>
        <nav aria-label="breadcrumb" class="orchid-header__breadcrumb d-none d-md-block">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="#"><i class="bi bi-house-door"></i></a></li>
            <li class="breadcrumb-item"><a href="#">Overview</a></li>
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
          </ol>
        </nav>
      </div>

      <div class="orchid-header__right">
        <form class="orchid-search d-none d-md-flex" role="search" data-orchid-search-form>
          <label for="orchidSearch" class="visually-hidden">Search</label>
          <i class="bi bi-search orchid-search__icon" aria-hidden="true"></i>
          <input type="search" id="orchidSearch" class="form-control" placeholder="Search anything…" autocomplete="off">
          <kbd class="orchid-search__kbd">⌘K</kbd>
        </form>

        <button class="btn btn-icon d-md-none" type="button" data-orchid-search-open aria-label="Open search">
          <i class="bi bi-search"></i>
        </button>

        <button class="btn btn-icon orchid-theme-toggle" type="button" data-orchid-theme-toggle aria-label="Toggle color theme">
          <i class="bi bi-sun-fill orchid-theme-toggle__sun"></i>
          <i class="bi bi-moon-stars-fill orchid-theme-toggle__moon"></i>
        </button>

        <div class="dropdown">
          <button class="btn btn-icon position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
            <i class="bi bi-bell"></i>
            <span class="orchid-badge-dot bg-danger"></span>
          </button>
          <div class="dropdown-menu dropdown-menu-end orchid-dropdown">
            <div class="orchid-dropdown__header">
              <h6 class="mb-0">Notifications</h6>
              <span class="badge bg-primary-subtle text-primary">5 New</span>
            </div>
            <ul class="orchid-dropdown__list list-unstyled mb-0">
              <li>
                <a href="#" class="orchid-dropdown__item">
                  <span class="avatar avatar-sm bg-primary-subtle text-primary"><i class="bi bi-person-plus"></i></span>
                  <div>
                    <p class="mb-0">New user registration</p>
                    <small class="text-body-secondary">2 minutes ago</small>
                  </div>
                </a>
              </li>
              <li>
                <a href="#" class="orchid-dropdown__item">
                  <span class="avatar avatar-sm bg-success-subtle text-success"><i class="bi bi-cash-coin"></i></span>
                  <div>
                    <p class="mb-0">Payment received — $2,400</p>
                    <small class="text-body-secondary">14 minutes ago</small>
                  </div>
                </a>
              </li>
              <li>
                <a href="#" class="orchid-dropdown__item">
                  <span class="avatar avatar-sm bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></span>
                  <div>
                    <p class="mb-0">Server load reached 78%</p>
                    <small class="text-body-secondary">1 hour ago</small>
                  </div>
                </a>
              </li>
              <li>
                <a href="#" class="orchid-dropdown__item">
                  <span class="avatar avatar-sm bg-info-subtle text-info"><i class="bi bi-chat-left-text"></i></span>
                  <div>
                    <p class="mb-0">New comment on report</p>
                    <small class="text-body-secondary">3 hours ago</small>
                  </div>
                </a>
              </li>
            </ul>
            <div class="orchid-dropdown__footer">
              <a href="#">View all notifications</a>
            </div>
          </div>
        </div>

        <div class="dropdown d-none d-sm-block">
          <button class="btn btn-icon position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Messages">
            <i class="bi bi-chat-dots"></i>
            <span class="orchid-badge-count bg-success">3</span>
          </button>
          <div class="dropdown-menu dropdown-menu-end orchid-dropdown">
            <div class="orchid-dropdown__header">
              <h6 class="mb-0">Messages</h6>
              <a href="#" class="small">Mark all read</a>
            </div>
            <ul class="orchid-dropdown__list list-unstyled mb-0">
              <li>
                <a href="#" class="orchid-dropdown__item">
                  <span class="avatar avatar-sm bg-primary text-white">SM</span>
                  <div>
                    <p class="mb-0"><strong>Sarah Miller</strong></p>
                    <small class="text-body-secondary">Loved your latest design mock…</small>
                  </div>
                </a>
              </li>
              <li>
                <a href="#" class="orchid-dropdown__item">
                  <span class="avatar avatar-sm bg-info text-white">JD</span>
                  <div>
                    <p class="mb-0"><strong>James Doe</strong></p>
                    <small class="text-body-secondary">The report is ready to review.</small>
                  </div>
                </a>
              </li>
              <li>
                <a href="#" class="orchid-dropdown__item">
                  <span class="avatar avatar-sm bg-warning text-white">AL</span>
                  <div>
                    <p class="mb-0"><strong>Ava Lee</strong></p>
                    <small class="text-body-secondary">Meeting rescheduled to Friday.</small>
                  </div>
                </a>
              </li>
            </ul>
            <div class="orchid-dropdown__footer"><a href="#">Open inbox</a></div>
          </div>
        </div>

        <div class="dropdown d-none d-sm-block">
          <button class="btn btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Apps">
            <i class="bi bi-grid-3x3-gap"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end orchid-dropdown orchid-dropdown--apps">
            <div class="orchid-dropdown__header"><h6 class="mb-0">Apps</h6></div>
            <div class="orchid-apps-grid">
              <a href="#" class="orchid-apps-grid__item"><i class="bi bi-envelope text-primary"></i><span>Mail</span></a>
              <a href="#" class="orchid-apps-grid__item"><i class="bi bi-calendar3 text-danger"></i><span>Calendar</span></a>
              <a href="#" class="orchid-apps-grid__item"><i class="bi bi-file-earmark-text text-warning"></i><span>Docs</span></a>
              <a href="#" class="orchid-apps-grid__item"><i class="bi bi-image text-info"></i><span>Photos</span></a>
              <a href="#" class="orchid-apps-grid__item"><i class="bi bi-cloud text-success"></i><span>Drive</span></a>
              <a href="#" class="orchid-apps-grid__item"><i class="bi bi-camera-video text-primary"></i><span>Meet</span></a>
            </div>
          </div>
        </div>

        <button class="btn btn-icon d-none d-lg-inline-flex" type="button" aria-label="Calendar">
          <i class="bi bi-calendar3"></i>
        </button>

        <div class="dropdown">
          <button class="btn orchid-profile-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User profile">
            <span class="avatar avatar-sm bg-primary-subtle text-primary fw-semibold">AK</span>
            <span class="orchid-profile-btn__meta d-none d-lg-flex">
              <span class="orchid-profile-btn__name">Alex Kim</span>
              <span class="orchid-profile-btn__role">Administrator</span>
            </span>
            <i class="bi bi-chevron-down d-none d-lg-inline"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end orchid-dropdown orchid-dropdown--profile">
            <li class="orchid-dropdown__header">
              <div class="d-flex align-items-center gap-2">
                <span class="avatar bg-primary-subtle text-primary fw-semibold">AK</span>
                <div>
                  <p class="mb-0 fw-semibold">Alex Kim</p>
                  <small class="text-body-secondary">alex@orchid.io</small>
                </div>
              </div>
            </li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i>Profile</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i>Settings</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-wallet2"></i>Billing</a></li>
            <li><a class="dropdown-item" href="#"><i class="bi bi-life-preserver"></i>Help Center</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-box-arrow-right"></i>Sign out</a></li>
          </ul>
        </div>
      </div>
    </header>