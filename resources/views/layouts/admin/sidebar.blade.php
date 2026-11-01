<aside class="orchid-sidebar" id="orchidSidebar" aria-label="Primary navigation">
    <div class="orchid-sidebar__brand">
        <a href="{{ route('dashboard') }}" class="orchid-brand" aria-label="Orchid dashboard home">
            <span class="orchid-brand__logo" aria-hidden="true">
                <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Orchid logo">
                    <defs>
                        <linearGradient id="orchidGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#6366f1" />
                            <stop offset="100%" stop-color="#22d3ee" />
                        </linearGradient>
                    </defs>
                    <circle cx="16" cy="16" r="6" fill="url(#orchidGrad)" />
                    <ellipse cx="16" cy="16" rx="14" ry="6" fill="none" stroke="url(#orchidGrad)" stroke-width="2"
                        transform="rotate(-30 16 16)" />
                </svg>
            </span>
            <span class="orchid-brand__text">{{ config('app.name') }}</span>
        </a>
        <button class="btn btn-sm btn-icon orchid-sidebar__close d-lg-none" type="button" data-orchid-sidebar-close
            aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <nav class="orchid-sidebar__nav" aria-label="Main">
        @php
            $navGroups = collect(config('module_permissions.modules'))
                ->filter(fn ($m, $key) => auth()->user()?->can("$key.view"))
                ->groupBy('group', true);
            $routeFor = fn ($key) => match ($key) {
                'users' => route('users.index'),
                'roles' => route('roles.index'),
                'tenants' => route('tenants.index'),
                default => route('modules.show', $key),
            };
            $isActive = fn ($key) => match ($key) {
                'users' => request()->routeIs('users.*'),
                'roles' => request()->routeIs('roles.*'),
                'tenants' => request()->routeIs('tenants.*'),
                default => request()->routeIs('modules.show') && request()->route('module') === $key,
            };
        @endphp

        <div class="orchid-nav-section">
            <span class="orchid-nav-section__label">Main</span>
            <ul class="orchid-nav">
                <li>
                    <a href="{{ route('dashboard') }}" class="orchid-nav__link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        @foreach ($navGroups as $group => $items)
            <div class="orchid-nav-section">
                <span class="orchid-nav-section__label">{{ $group }}</span>
                <ul class="orchid-nav">
                    @foreach ($items as $key => $item)
                        <li>
                            <a href="{{ $routeFor($key) }}" class="orchid-nav__link {{ $isActive($key) ? 'active' : '' }}">
                                <i class="bi {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    @auth
        <div class="app-sidebar-user">
            <span class="app-avatar">{{ collect(explode(' ', auth()->user()->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->join('') }}</span>
            <div class="overflow-hidden">
                <strong class="text-truncate">{{ auth()->user()->name }}</strong>
                <small class="d-block text-truncate">{{ auth()->user()->email }}</small>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="ms-auto">
                @csrf
                <button type="submit" class="app-sidebar-user__logout" title="Sign out"><i class="bi bi-box-arrow-right"></i></button>
            </form>
        </div>
    @endauth
</aside>
