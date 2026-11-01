<header class="orchid-header" role="banner">
    <div class="orchid-header__left">
        <button class="btn btn-icon orchid-header__toggle" type="button" data-orchid-sidebar-toggle
            aria-label="Toggle sidebar" aria-controls="orchidSidebar" aria-expanded="false">
            <i class="bi bi-list"></i>
        </button>
        <div class="app-header-title d-none d-md-block">
            <h1>@yield('title', 'Dashboard')</h1>
            @hasSection('subtitle')<p>@yield('subtitle')</p>@endif
        </div>
    </div>

    <div class="orchid-header__right">
        <form class="orchid-search d-none d-md-flex" role="search" data-orchid-search-form>
            <label for="orchidSearch" class="visually-hidden">Search</label>
            <i class="bi bi-search orchid-search__icon" aria-hidden="true"></i>
            <input type="search" id="orchidSearch" class="form-control" placeholder="Search anything…"
                autocomplete="off">
            <kbd class="orchid-search__kbd">⌘K</kbd>
        </form>

        <button class="btn btn-icon d-md-none" type="button" data-orchid-search-open aria-label="Open search">
            <i class="bi bi-search"></i>
        </button>

        <button class="btn btn-icon orchid-theme-toggle" type="button" data-orchid-theme-toggle
            aria-label="Toggle color theme">
            <i class="bi bi-sun-fill orchid-theme-toggle__sun"></i>
            <i class="bi bi-moon-stars-fill orchid-theme-toggle__moon"></i>
        </button>


        @php
            $user = auth()->user();
            $name = $user ? $user->name : 'User';
            $email = $user ? $user->email : 'user@example.com';
            $nameParts = explode(' ', $name);
            $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
        @endphp
        <div class="dropdown">
            <button class="btn orchid-profile-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                aria-label="User profile">
                <span class="app-avatar">{{ $initials }}</span>
                <span class="orchid-profile-btn__meta d-none d-lg-flex">
                    <span class="orchid-profile-btn__name">{{ $name }}</span>
                    <span class="orchid-profile-btn__role">{{ auth()->user()?->roles->pluck('name')->first() ?? 'User' }}</span>
                </span>
                <i class="bi bi-chevron-down d-none d-lg-inline"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end orchid-dropdown orchid-dropdown--profile">
                <li class="orchid-dropdown__header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="app-avatar app-avatar--lg">{{ $initials }}</span>
                        <div>
                            <p class="mb-0 fw-semibold">{{ $name }}</p>
                            <small class="text-body-secondary">{{ $email }}</small>
                        </div>
                    </div>
                </li>
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person"></i>My Profile</a></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a class="dropdown-item text-danger" href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                            <i class="bi bi-box-arrow-right"></i>Sign out
                        </a>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>