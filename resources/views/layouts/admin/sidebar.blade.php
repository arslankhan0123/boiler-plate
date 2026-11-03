<aside class="orchid-sidebar" id="orchidSidebar" aria-label="Primary navigation">
    <div class="orchid-sidebar__brand">
        <a href="{{ route('dashboard') }}" class="orchid-brand" aria-label="{{ \App\Support\Settings::companyName() }} dashboard home">
            <span class="orchid-brand__logo" aria-hidden="true">
                <img src="{{ \App\Support\Settings::brandLogoUrl() }}" alt="{{ \App\Support\Settings::companyName() }}" style="width:100%;height:100%;object-fit:contain;border-radius:8px">
            </span>
            <span class="orchid-brand__text">{{ \App\Support\Settings::companyName() }}</span>
        </a>
        <button class="btn btn-sm btn-icon orchid-sidebar__close d-lg-none" type="button" data-orchid-sidebar-close
            aria-label="Close sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    @php
        // Built from the module registry: only modules enabled for this
        // user's tenant AND features the user has permission for.
        $reg = \App\Support\ModuleRegistry::class;
        $mods = auth()->check() ? \App\Support\ModuleAccess::visibleModules(auth()->user()) : [];
        $groupNames = $reg::groups();
        // Which module is open: the one the current page belongs to.
        $activeCode = collect($mods)->keys()->first(fn ($c) => $reg::isActive($c));
        $short = fn ($name) => \Illuminate\Support\Str::of($name)->before(' &')->before(' (')->limit(11, '…');
    @endphp

    <div class="app-nav2">
        {{-- ── Icon rail: one icon per module ── --}}
        <nav class="app-rail" aria-label="Modules">
            <a href="{{ route('dashboard') }}" class="app-rail__item {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" data-label="Dashboard" aria-label="Dashboard">
                <i class="bi bi-speedometer2"></i><span class="visually-hidden">Dashboard</span>
            </a>
            @if (auth()->check() && auth()->user()->tenant_id !== null)
                <a href="{{ route('billing.index') }}" class="app-rail__item {{ request()->routeIs('billing.*') ? 'is-active' : '' }}" data-label="Billing & Plans" aria-label="Billing & Plans">
                    <i class="bi bi-credit-card-2-front"></i><span class="visually-hidden">Billing & Plans</span>
                </a>
            @endif
            @foreach (array_keys($groupNames) as $group)
                @php $inGroup = collect($mods)->where('group', $group); @endphp
                @continue($inGroup->isEmpty())
                <div class="app-rail__sep" title="{{ $groupNames[$group] }}"></div>
                @foreach ($inGroup as $code => $m)
                    @php $hasPanel = ! $m['route'] && count($m['visible_features']) > 0; @endphp
                    @if ($hasPanel)
                        <button type="button" class="app-rail__item {{ $activeCode === $code ? 'is-active is-open' : '' }}" data-panel="{{ $code }}" data-label="{{ $m['name'] }}" aria-label="{{ $m['name'] }}">
                            <i class="bi {{ $m['icon'] }}"></i><span class="visually-hidden">{{ $m['name'] }}</span>
                        </button>
                    @else
                        <a href="{{ $reg::url($code) }}" class="app-rail__item {{ $activeCode === $code ? 'is-active' : '' }}" data-label="{{ $m['name'] }}" aria-label="{{ $m['name'] }}">
                            <i class="bi {{ $m['icon'] }}"></i><span class="visually-hidden">{{ $m['name'] }}</span>
                        </a>
                    @endif
                @endforeach
            @endforeach
        </nav>

        {{-- ── Panel: the open module's screens ── --}}
        <div class="app-panel {{ $activeCode && ! ($mods[$activeCode]['route'] ?? null) ? '' : 'is-empty' }}">
            <div class="app-panel__home {{ $activeCode && ! ($mods[$activeCode]['route'] ?? null) ? 'd-none' : '' }}" data-panel-home>
                <div class="app-panel__title">Menu</div>
                <p class="app-panel__hint">Choose a module on the left to see its screens.</p>
                <ul class="app-panel__list">
                    <li><a href="{{ route('dashboard') }}" class="app-panel__link {{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
                    @if (auth()->check() && auth()->user()->tenant_id !== null)
                        <li><a href="{{ route('billing.index') }}" class="app-panel__link {{ request()->routeIs('billing.*') ? 'active' : '' }}"><i class="bi bi-credit-card-2-front"></i>Billing & Plans</a></li>
                    @endif
                    @foreach ($mods as $code => $m)
                        @if ($m['route'] || ! count($m['visible_features']))
                            <li><a href="{{ $reg::url($code) }}" class="app-panel__link {{ $reg::isActive($code) ? 'active' : '' }}"><i class="bi {{ $m['icon'] }}"></i>{{ $m['name'] }}</a></li>
                        @endif
                    @endforeach
                </ul>
            </div>

            @foreach ($mods as $code => $m)
                @continue($m['route'] || ! count($m['visible_features']))
                <div class="app-panel__section {{ $activeCode === $code ? '' : 'd-none' }}" data-panel-for="{{ $code }}">
                    <div class="app-panel__title"><i class="bi {{ $m['icon'] }}"></i>{{ $m['name'] }}</div>
                    @if ($m['description'])<p class="app-panel__hint">{{ $m['description'] }}</p>@endif
                    @php
                        // HRM: split self-service from HR screens.
                        $mine = collect($m['visible_features'])->filter(fn ($f, $k) => ! empty($f['employee_only']));
                        $rest = collect($m['visible_features'])->reject(fn ($f, $k) => ! empty($f['employee_only']));
                    @endphp
                    @foreach (array_filter(['My HR' => $mine, ($mine->isNotEmpty() ? 'Manage' : '') => $rest]) as $label => $items)
                        @continue($items->isEmpty())
                        @if ($mine->isNotEmpty() && $label)<div class="app-panel__group">{{ $label }}</div>@endif
                        <ul class="app-panel__list">
                            @foreach ($items as $fkey => $f)
                                <li><a href="{{ $reg::url($code, $fkey) }}" class="app-panel__link {{ $reg::isActive($code, $fkey) ? 'active' : '' }}">{{ $f['name'] }}</a></li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

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

<script>
// Sidebar: clicking a module icon opens its screens in the panel.
document.addEventListener('DOMContentLoaded', () => {
    // Hover tooltips for the icon rail.
    const tip = Object.assign(document.createElement('div'), { className: 'app-rail-tip' });
    document.body.appendChild(tip);
    document.querySelectorAll('.app-rail__item[data-label]').forEach(el => {
        const show = () => {
            const r = el.getBoundingClientRect();
            tip.textContent = el.dataset.label;
            tip.style.left = (r.right + 14) + 'px';
            tip.style.top = (r.top + r.height / 2) + 'px';
            tip.classList.add('is-on');
        };
        el.addEventListener('mouseenter', show);
        el.addEventListener('focus', show);
        ['mouseleave', 'blur', 'click'].forEach(ev => el.addEventListener(ev, () => tip.classList.remove('is-on')));
    });
    document.querySelector('.app-rail')?.addEventListener('scroll', () => tip.classList.remove('is-on'));

    const rail = document.querySelectorAll('.app-rail__item[data-panel]');
    const home = document.querySelector('[data-panel-home]');
    rail.forEach(btn => btn.addEventListener('click', () => {
        const code = btn.dataset.panel;
        const already = btn.classList.contains('is-open');
        document.body.classList.remove('orchid-sidebar-collapsed');
        rail.forEach(b => b.classList.remove('is-open'));
        document.querySelectorAll('[data-panel-for]').forEach(p => p.classList.add('d-none'));
        if (already) { home?.classList.remove('d-none'); return; } // second click → back to menu
        btn.classList.add('is-open');
        home?.classList.add('d-none');
        document.querySelector(`[data-panel-for="${code}"]`)?.classList.remove('d-none');
    }));
});
</script>
