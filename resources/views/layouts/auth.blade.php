<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') - {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('admin/assets/icons/favicon.svg') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('admin/assets/css/theme.css') }}?v={{ @filemtime(public_path('admin/assets/css/theme.css')) }}" rel="stylesheet">
    <style>
        body { font-family: Inter, sans-serif; background: var(--app-bg); min-height: 100vh; }
        .auth { min-height: 100vh; display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr); }
        @media (max-width: 991.98px) { .auth { grid-template-columns: 1fr; } .auth-hero { display: none !important; } }

        /* Left brand panel — same gradient as the sidebar */
        .auth-hero { position: relative; overflow: hidden; background: var(--app-sidebar-grad); color: #fff;
            display: flex; flex-direction: column; justify-content: space-between; padding: 3rem 3.5rem; }
        .auth-hero::before, .auth-hero::after { content: ''; position: absolute; border-radius: 50%; filter: blur(10px); }
        .auth-hero::before { width: 420px; height: 420px; right: -140px; top: -120px; background: radial-gradient(circle, rgba(99,102,241,.45), transparent 70%); }
        .auth-hero::after { width: 360px; height: 360px; left: -120px; bottom: -120px; background: radial-gradient(circle, rgba(139,92,246,.35), transparent 70%); }
        .auth-hero > * { position: relative; z-index: 1; }
        .auth-brand { display: flex; align-items: center; gap: .75rem; font-size: 1.35rem; font-weight: 800; color: #fff; text-decoration: none; }
        .auth-brand__logo { width: 44px; height: 44px; border-radius: 12px; background: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(0,0,0,.35); }
        .auth-brand__logo svg { width: 28px; height: 28px; }
        .auth-hero h2 { font-size: 2.35rem; font-weight: 800; line-height: 1.15; letter-spacing: -.02em; margin-bottom: 1rem; }
        .auth-hero h2 span { background: linear-gradient(90deg, #a5b4fc, #c4b5fd); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .auth-hero p.lead { color: rgba(255,255,255,.7); font-size: 1rem; max-width: 460px; }
        .auth-features { display: grid; gap: 1rem; margin-top: 2.25rem; max-width: 460px; }
        .auth-feature { display: flex; gap: .9rem; align-items: flex-start; padding: 1rem; border-radius: 14px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.08); backdrop-filter: blur(6px); }
        .auth-feature i { width: 38px; height: 38px; flex-shrink: 0; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(129,140,248,.2); color: #c7d2fe; font-size: 1.05rem; }
        .auth-feature strong { display: block; font-size: .925rem; }
        .auth-feature small { color: rgba(255,255,255,.6); font-size: .8rem; }
        .auth-hero__foot { color: rgba(255,255,255,.45); font-size: .8rem; }

        /* Right form panel */
        .auth-main { display: flex; align-items: center; justify-content: center; padding: 2.5rem 1.5rem; }
        .auth-card { width: 100%; max-width: 440px; }
        .auth-card__mobile-brand { display: none; margin-bottom: 2rem; }
        @media (max-width: 991.98px) { .auth-card__mobile-brand { display: flex; } .auth-card__mobile-brand.auth-brand { color: var(--app-text); } }
        .auth-card h1 { font-size: 1.75rem; font-weight: 800; letter-spacing: -.02em; margin: 0 0 .4rem; color: var(--app-text); }
        .auth-card .sub { color: var(--app-text-muted); margin-bottom: 2rem; }
        .auth-card .btn-primary { width: 100%; min-height: 50px; font-size: .95rem; }
        .auth-pw { position: relative; }
        .auth-pw .toggle { position: absolute; right: .6rem; top: 50%; transform: translateY(-50%); border: 0; background: none; color: var(--app-text-muted); padding: .35rem .5rem; border-radius: 8px; z-index: 3; }
        .auth-pw .toggle:hover { color: var(--app-primary); background: var(--app-primary-soft); }
        .auth-pw .form-control { padding-right: 2.75rem; }
        .auth-pw .invalid-feedback { position: static; }
        .auth-link { color: var(--app-primary); font-weight: 600; text-decoration: none; }
        .auth-link:hover { text-decoration: underline; }
        .auth-divider { text-align: center; color: var(--app-text-muted); font-size: .85rem; margin-top: 1.75rem; }
        .auth-status { border-radius: 10px; padding: .75rem 1rem; font-size: .875rem; background: var(--app-success-soft); color: #166534; margin-bottom: 1.25rem; }
    </style>
</head>
<body>
    <div class="auth">
        <aside class="auth-hero">
            <a href="{{ url('/') }}" class="auth-brand">
                <span class="auth-brand__logo">
                    <svg viewBox="0 0 32 32"><defs><linearGradient id="ag" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#4338ca"/><stop offset="100%" stop-color="#8b5cf6"/></linearGradient></defs>
                        <circle cx="16" cy="16" r="6" fill="url(#ag)"/><ellipse cx="16" cy="16" rx="14" ry="6" fill="none" stroke="url(#ag)" stroke-width="2" transform="rotate(-30 16 16)"/></svg>
                </span>
                {{ config('app.name') }}
            </a>

            <div>
                <h2>Run your whole business<br>from <span>one place.</span></h2>
                <p class="lead">Accounting, inventory, POS, purchases and sales — with secure, role-based access for every member of your team.</p>
                <div class="auth-features">
                    <div class="auth-feature"><i class="bi bi-buildings"></i><div><strong>Multi-tenant by design</strong><small>Every organization gets its own isolated database.</small></div></div>
                    <div class="auth-feature"><i class="bi bi-shield-lock"></i><div><strong>Roles &amp; permissions</strong><small>Show each user only the modules they need.</small></div></div>
                    <div class="auth-feature"><i class="bi bi-grid-1x2"></i><div><strong>Modular</strong><small>Turn modules on or off per organization.</small></div></div>
                </div>
            </div>

            <div class="auth-hero__foot">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</div>
        </aside>

        <main class="auth-main">
            <div class="auth-card">
                <a href="{{ url('/') }}" class="auth-brand auth-card__mobile-brand">
                    <span class="auth-brand__logo" style="background:var(--app-grad)"><i class="bi bi-hexagon-fill text-white"></i></span>
                    {{ config('app.name') }}
                </a>
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        // Show / hide password
        document.querySelectorAll('[data-toggle-password]').forEach(btn => btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.togglePassword);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = `<i class="bi bi-eye${show ? '-slash' : ''}"></i>`;
        }));
        // Prevent double submit + loading state
        document.querySelectorAll('form[data-loading]').forEach(f => f.addEventListener('submit', () => {
            const b = f.querySelector('button[type=submit]');
            if (b) { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm"></span> ' + f.dataset.loading; }
        }));
    </script>
    <x-toast />
</body>
</html>
