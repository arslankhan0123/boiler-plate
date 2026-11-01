<style>
    .err-wrap{min-height:calc(100vh - 160px);display:flex;align-items:center;justify-content:center;padding:2rem 1rem}
    .err-card{max-width:520px;width:100%;text-align:center;background:var(--app-surface,#fff);border-radius:1.25rem;
        padding:3rem 2rem;box-shadow:0 10px 40px rgba(17,24,39,.08);border:1px solid rgba(0,0,0,.05)}
    .err-icon{width:96px;height:96px;margin:0 auto 1.5rem;border-radius:50%;display:flex;align-items:center;justify-content:center;
        font-size:2.75rem;color:{{ $c1 }};background:{{ $c2 }};box-shadow:0 0 0 12px {{ $c2 }}66}
    .err-code{font-size:4.5rem;font-weight:800;line-height:1;letter-spacing:-2px;
        background:linear-gradient(135deg,var(--app-primary,#2563eb),{{ $c1 }});-webkit-background-clip:text;background-clip:text;color:transparent}
    .err-title{font-size:1.35rem;font-weight:700;margin:.75rem 0 .5rem}
    .err-msg{color:#6b7280;margin-bottom:2rem}
</style>
<div class="err-wrap">
    <div class="err-card">
        <div class="err-icon"><i class="bi {{ $icon }}"></i></div>
        <div class="err-code">{{ $code }}</div>
        <div class="err-title">{{ $title }}</div>
        <p class="err-msg">{{ $message }}</p>
        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <a href="javascript:history.back()" class="btn btn-outline-secondary px-4"><i class="bi bi-arrow-left me-1"></i>Go Back</a>
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary px-4"><i class="bi bi-house me-1"></i>Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary px-4"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a>
            @endauth
        </div>
    </div>
</div>
