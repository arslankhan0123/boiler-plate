<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — Careers at {{ $tenant->name }}</title>
    <meta name="description" content="Open positions at {{ $tenant->name }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('admin/assets/css/theme.css') }}" rel="stylesheet">
    <style>
        body { font-family: Inter, sans-serif; background: var(--app-bg); }
        .c-hero { background: var(--app-sidebar-grad); color: #fff; padding: 56px 0 96px; position: relative; overflow: hidden; }
        .c-hero::after { content: ''; position: absolute; width: 420px; height: 420px; right: -120px; top: -160px; border-radius: 50%; background: radial-gradient(circle, rgba(99,102,241,.45), transparent 70%); }
        .c-hero h1 { font-weight: 800; letter-spacing: -.02em; }
        .c-hero a { color: #c7d2fe; text-decoration: none; }
        .c-main { margin-top: -64px; position: relative; z-index: 1; padding-bottom: 64px; }
        .c-job { display: flex; gap: 1rem; align-items: center; padding: 1.25rem 1.5rem; text-decoration: none; color: var(--app-text); }
        .c-job:hover { background: var(--app-hover); }
        .c-job + .c-job { border-top: 1px solid var(--app-border); }
        .c-meta { display: flex; flex-wrap: wrap; gap: .4rem .9rem; font-size: .82rem; color: var(--app-text-muted); }
        .c-content { white-space: pre-line; line-height: 1.7; }
    </style>
</head>
<body>
    <header class="c-hero"><div class="container" style="max-width:960px">@yield('hero')</div></header>
    <main class="c-main"><div class="container" style="max-width:960px">@yield('content')</div></main>
    <footer class="text-center small text-body-secondary pb-4">© {{ date('Y') }} {{ $tenant->name }}</footer>
    <x-toast />
</body>
</html>
