<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $storeName) — {{ $storeName }}</title>
    <meta name="description" content="{{ $tagline ?: $storeName }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body{font-family:Inter,sans-serif;background:#f6f7fb;color:#1e293b}
        .s-nav{background:#fff;border-bottom:1px solid #e9ecf3;position:sticky;top:0;z-index:10}
        .s-brand{font-weight:800;letter-spacing:-.02em;text-decoration:none;color:#1e293b;font-size:1.25rem}
        .s-cart{position:relative;color:#1e293b;text-decoration:none}
        .s-cart__badge{position:absolute;top:-8px;right:-10px;background:#6366f1;color:#fff;font-size:.65rem;font-weight:700;min-width:18px;height:18px;border-radius:999px;display:flex;align-items:center;justify-content:center;padding:0 4px}
        .p-card{background:#fff;border:1px solid #eef0f6;border-radius:14px;overflow:hidden;transition:box-shadow .15s,transform .15s;height:100%}
        .p-card:hover{box-shadow:0 12px 32px rgba(17,24,39,.1);transform:translateY(-2px)}
        .p-thumb{aspect-ratio:1;background:#f1f3f9 center/cover no-repeat;display:flex;align-items:center;justify-content:center;color:#c3c9d6;font-size:2.5rem}
        .p-price{font-weight:700}
        .p-was{text-decoration:line-through;color:#9aa3b2;font-size:.85rem}
        .badge-sale{background:#fee2e2;color:#dc2626;font-weight:700;font-size:.7rem;border-radius:6px;padding:.15rem .4rem}
        .s-card{background:#fff;border:1px solid #eef0f6;border-radius:14px}
    </style>
</head>
<body>
    <nav class="s-nav"><div class="container py-3 d-flex align-items-center justify-content-between" style="max-width:1080px">
        <a href="{{ route('store.index', $tenant->id) }}" class="s-brand">{{ $storeName }}</a>
        <a href="{{ route('store.cart', $tenant->id) }}" class="s-cart"><i class="bi bi-bag" style="font-size:1.4rem"></i>@if ($cartCount > 0)<span class="s-cart__badge">{{ $cartCount }}</span>@endif</a>
    </div></nav>
    <main class="container py-4" style="max-width:1080px">
        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
    <footer class="text-center small text-body-secondary py-4">© {{ date('Y') }} {{ $storeName }} — Powered by BoilerPlate</footer>
</body>
</html>
