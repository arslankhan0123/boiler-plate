@use('App\Support\Ecommerce\Money')
@use('App\Support\Settings')
@php $logo = Settings::brandLogoUrl(); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Shop') · {{ $storeName }}</title>
<meta name="description" content="@yield('meta_description', $storeName.($tagline ? ' — '.$tagline : '').'. Curated products, honest prices, fast delivery.')">
<script>
window.MONEY = { symbol: @json($curSymbol), decimals: {{ (int) $curDecimals }} };
window.zmoney = function(n){ var m=window.MONEY, v=(Number(n)||0), s=v.toLocaleString('en-US',{minimumFractionDigits:m.decimals,maximumFractionDigits:m.decimals}); return m.symbol+s; };
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
<style>
:root{
  --brand:#0f766e;--brand-dark:#134e4a;--brand-light:#14b8a6;--brand-soft:#e7f6f4;
  --accent:#f59e0b;--accent-soft:#fef3c7;
  --grad:linear-gradient(135deg,#134e4a 0%,#0f766e 55%,#14b8a6 100%);
  --grad-h:linear-gradient(135deg,#0f766e 0%,#119184 55%,#2dd4bf 100%);
  --p:var(--brand);--p-dark:var(--brand-dark);--p-soft:var(--brand-soft);--p2:var(--accent);
  --sale:#f97316;--ok:#22c55e;--warn:#f6a609;--danger:#dc2626;--star:#ffb400;
  --ink:#0c2a27;--ink-2:#1e3d39;--muted:#5b7370;--faint:#8fa6a2;
  --surface:#f3f8f7;--line:#e1ecea;--line-2:#d1e0dd;--shadow-rgb:19,78,74;
  --white:#fff;--r:14px;--r-lg:22px;
  --shadow:0 10px 30px -12px rgba(var(--shadow-rgb),.18);
  --shadow-lg:0 30px 60px -20px rgba(var(--shadow-rgb),.3);
  --glow:0 0 0 4px rgba(var(--shadow-rgb),.14);
  --head-h:70px;
}
*{box-sizing:border-box}
html{scroll-behavior:smooth;overflow-x:clip}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;color:var(--ink);background:#fff;margin:0;-webkit-font-smoothing:antialiased;overflow-x:clip}
a{color:inherit;text-decoration:none}
a:hover{color:var(--p)}
img{max-width:100%}
button,input,select,textarea{font-family:inherit}
::selection{background:var(--p);color:#fff}
.wrap{max-width:1420px;margin:0 auto;padding:0 24px}
@media(max-width:576px){.wrap{padding:0 16px}}
.grad-text{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.btn-g{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--grad);color:#fff;border:0;border-radius:12px;padding:12px 22px;font-weight:700;font-size:14px;cursor:pointer;position:relative;overflow:hidden;transition:transform .2s,box-shadow .2s;box-shadow:0 10px 24px -10px rgba(15,118,110,.6)}
.btn-g::after{content:"";position:absolute;inset:0;background:linear-gradient(120deg,transparent 30%,rgba(255,255,255,.35) 50%,transparent 70%);transform:translateX(-120%);transition:transform .6s}
.btn-g:hover{color:#fff;transform:translateY(-2px);box-shadow:0 16px 30px -10px rgba(15,118,110,.7)}
.btn-g:hover::after{transform:translateX(120%)}
.btn-o{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:#fff;color:var(--ink);border:1.5px solid var(--line-2);border-radius:12px;padding:11px 20px;font-weight:700;font-size:14px;cursor:pointer;transition:.2s}
.btn-o:hover{border-color:var(--p);color:var(--p);background:var(--p-soft)}
.sec{padding-top:48px;padding-bottom:48px}
.sec-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:22px;flex-wrap:wrap}
.sec-title{font-size:24px;font-weight:800;letter-spacing:-.4px;margin:0 0 6px;color:var(--ink)}
.sec-deco{display:flex;align-items:center;gap:6px}
.sec-deco i{width:52px;height:4px;border-radius:4px;background:var(--accent)}
.sec-deco b{width:8px;height:4px;border-radius:4px;background:var(--brand)}
.sec-sub{color:var(--muted);font-size:14px;margin:6px 0 0}
.sec-link{font-weight:700;font-size:14px;color:var(--p);display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
.sec-link i{transition:transform .2s}
.sec-link:hover i{transform:translateX(4px)}
.stars{display:inline-flex;gap:2px;color:var(--star);font-size:11px}
.stars .off{color:#d1e0dd}
/* tilt + reveal */
[data-tilt]{transform-style:preserve-3d;transform:perspective(900px) rotateX(var(--rx,0deg)) rotateY(var(--ry,0deg));transition:transform .55s cubic-bezier(.2,.7,.2,1),box-shadow .3s;will-change:transform}
[data-tilt].tilting{transition:transform .08s linear,box-shadow .3s}
.tilt-glare{position:absolute;inset:0;pointer-events:none;border-radius:inherit;background:radial-gradient(400px circle at var(--gx,50%) var(--gy,50%),rgba(255,255,255,.35),transparent 45%);opacity:0;transition:opacity .3s;z-index:3}
[data-tilt].tilting .tilt-glare{opacity:1}
.reveal{opacity:0;transform:translateY(26px)}
.reveal.in{opacity:1;transform:none;transition:opacity .7s ease,transform .8s cubic-bezier(.2,.7,.2,1)}
.reveal[data-delay="1"].in{transition-delay:.08s}.reveal[data-delay="2"].in{transition-delay:.16s}.reveal[data-delay="3"].in{transition-delay:.24s}.reveal[data-delay="4"].in{transition-delay:.32s}
@media(prefers-reduced-motion:reduce){.reveal{opacity:1;transform:none}[data-tilt]{transform:none!important}*,*::before,*::after{animation-duration:.001ms!important;transition-duration:.001ms!important}}
@keyframes floatY{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
@keyframes marquee{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
@keyframes cartBump{0%{transform:scale(1)}40%{transform:scale(1.35) rotate(-8deg)}100%{transform:scale(1)}}
@keyframes toastIn{from{opacity:0;transform:translateX(30px)}to{opacity:1;transform:none}}
/* topbar */
.topbar{background:linear-gradient(90deg,#0b2b28,#134e4a 50%,#0f3d33);color:rgba(255,255,255,.85);font-size:12.5px;height:36px;display:flex;align-items:center;position:relative;overflow:hidden}
.topbar .wrap{display:flex;align-items:center;justify-content:space-between;gap:16px;position:relative}
.topbar-loc{display:flex;align-items:center;gap:7px;white-space:nowrap}
.topbar-loc i{color:var(--p2)}
.topbar-marquee{flex:1;overflow:hidden;position:relative;mask-image:linear-gradient(90deg,transparent,#000 8%,#000 92%,transparent)}
.topbar-marquee span{display:inline-block;white-space:nowrap;animation:marquee 34s linear infinite}
.topbar-marquee b{color:#fff}.topbar-marquee em{font-style:normal;color:var(--p2);font-weight:700}
.topbar-marquee .sep{display:inline-block;width:6px;height:6px;border-radius:50%;background:var(--p2);margin:0 30px;vertical-align:middle;opacity:.8}
@media(max-width:768px){.topbar-loc span{display:none}}
/* header */
.header{position:sticky;top:0;z-index:1000;background:#fff;box-shadow:0 1px 0 var(--line)}
.header.scrolled{box-shadow:0 8px 30px -14px rgba(19,78,74,.25)}
.hdr-main{height:var(--head-h);display:flex;align-items:center;gap:20px}
.logo{display:flex;align-items:center;flex-shrink:0;gap:10px;font-weight:800;font-size:22px;letter-spacing:-.5px;color:var(--ink)}
.logo .mk{width:40px;height:40px;border-radius:12px;background:var(--grad);display:grid;place-items:center;color:#fff;font-size:18px;box-shadow:0 10px 22px -8px rgba(15,118,110,.7)}
.logo img{height:44px;width:auto;object-fit:contain}
.logo span{color:var(--p)}
.search{flex:1;display:flex;align-items:center;gap:0;max-width:640px;margin:0 auto}
.search form{display:flex;width:100%;border:1.5px solid var(--line-2);border-radius:12px;overflow:hidden;background:#fff}
.search input{flex:1;border:0;padding:0 16px;height:46px;outline:0;font-size:14px;min-width:0}
.search button{border:0;background:var(--grad);color:#fff;width:52px;cursor:pointer;font-size:15px}
.burger{display:none;width:44px;height:44px;border-radius:12px;border:1px solid var(--line);background:#fff;font-size:17px;color:var(--ink);cursor:pointer}
.hdr-actions{display:flex;align-items:center;gap:14px;flex-shrink:0}
.icon-btn{position:relative;width:46px;height:46px;border-radius:12px;border:1px solid var(--line);background:#fff;display:grid;place-items:center;color:var(--ink);font-size:17px;cursor:pointer;transition:.2s}
.icon-btn:hover{border-color:var(--p);color:var(--p);background:var(--p-soft)}
.icon-btn.bump{animation:cartBump .5s}
.icon-btn .cnt{position:absolute;top:-7px;right:-7px;min-width:20px;height:20px;border-radius:10px;background:var(--sale);color:#fff;font-size:11px;font-weight:800;display:grid;place-items:center;padding:0 5px;box-shadow:0 4px 10px -3px rgba(249,115,22,.8)}
.hdr-cart{display:flex;align-items:center;gap:10px}
.hdr-cart>span:last-child{display:flex;flex-direction:column;line-height:1.15}
.hdr-cart small{font-size:11px;color:var(--faint);font-weight:600}
.hdr-cart em{font-weight:800;font-size:14px}
.hdr-call{display:flex;align-items:center;gap:9px;color:var(--ink)}
.hdr-call i{width:40px;height:40px;border-radius:12px;background:var(--p-soft);color:var(--p);display:grid;place-items:center;font-size:15px}
.hdr-call small{display:block;font-size:11px;color:var(--faint);font-weight:600}
.hdr-call strong{display:block;font-size:13px}
@media(max-width:992px){.hdr-call,.hdr-cart>span:last-child{display:none}}
@media(max-width:860px){.search{display:none}.burger{display:grid;place-items:center}}
/* navband */
.navband{border-top:1px solid var(--line);background:#fff}
.navrow{display:flex;align-items:center;gap:18px;height:52px}
.allcat{position:relative}
.allcat-btn{display:inline-flex;align-items:center;gap:10px;background:var(--grad);color:#fff;border:0;border-radius:10px;padding:10px 16px;font-weight:700;font-size:13.5px;cursor:pointer;white-space:nowrap}
.allcat .dd{position:absolute;top:calc(100% + 8px);left:0;width:270px;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 24px 60px -18px rgba(19,78,74,.35);padding:8px;opacity:0;visibility:hidden;transform:translateY(8px);transition:.2s;z-index:1200}
.allcat.open .dd{opacity:1;visibility:visible;transform:none}
.allcat .dd a{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:9px;font-size:13.5px;font-weight:600;color:var(--ink-2)}
.allcat .dd a:hover{background:var(--p-soft);color:var(--p)}
.allcat .dd .sub a{padding-left:30px;font-size:12.5px;color:var(--muted)}
.nav-main{display:flex;align-items:center;gap:4px;list-style:none;margin:0;padding:0}
.nav-main a{display:inline-block;padding:10px 14px;font-weight:700;font-size:14px;color:var(--ink-2);border-radius:9px}
.nav-main a:hover,.nav-main a.active{color:var(--p);background:var(--p-soft)}
.deal-btn{margin-left:auto;display:inline-flex;align-items:center;gap:8px;background:var(--sale);color:#fff;border-radius:10px;padding:9px 16px;font-weight:700;font-size:13.5px;box-shadow:0 10px 22px -10px rgba(249,115,22,.7)}
.deal-btn:hover{color:#fff;background:#ea580c}
@media(max-width:860px){.navband{display:none}}
/* drawer */
.drawer-bg{position:fixed;inset:0;background:rgba(10,30,28,.5);opacity:0;visibility:hidden;transition:.3s;z-index:2000}
.drawer{position:fixed;top:0;left:0;width:300px;max-width:85vw;height:100%;background:#fff;z-index:2001;transform:translateX(-100%);transition:transform .3s cubic-bezier(.2,.7,.2,1);overflow-y:auto;box-shadow:0 0 60px rgba(0,0,0,.2)}
body.drawer-open .drawer-bg{opacity:1;visibility:visible}
body.drawer-open .drawer{transform:none}
.drawer-head{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border-bottom:1px solid var(--line)}
.drawer form{display:flex;margin:14px 18px;border:1.5px solid var(--line-2);border-radius:10px;overflow:hidden}
.drawer form input{flex:1;border:0;padding:0 12px;height:42px;outline:0}
.drawer form button{border:0;background:var(--grad);color:#fff;width:46px}
.drawer nav a{display:flex;align-items:center;gap:12px;padding:13px 18px;font-weight:700;font-size:15px;border-bottom:1px solid var(--surface);color:var(--ink)}
.drawer nav a i{width:22px;color:var(--p)}
.drawer nav a:hover{background:var(--p-soft);color:var(--p)}
/* toasts */
.toasts{position:fixed;top:20px;right:20px;z-index:4000;display:flex;flex-direction:column;gap:10px;max-width:360px}
.toast-z{display:flex;align-items:flex-start;gap:12px;background:#fff;border:1px solid var(--line);border-left:4px solid var(--ok);border-radius:12px;padding:14px 16px;box-shadow:0 18px 40px -16px rgba(19,78,74,.4);font-size:14px;animation:toastIn .4s cubic-bezier(.2,.7,.2,1)}
.toast-z.err{border-left-color:var(--sale)}.toast-z.info{border-left-color:var(--p)}
.toast-z .ic{font-size:17px;color:var(--ok);margin-top:1px}
.toast-z.err .ic{color:var(--sale)}.toast-z.info .ic{color:var(--p)}
.toast-z a{color:var(--p);font-weight:700}
.toast-z .x{margin-left:auto;border:0;background:none;color:var(--faint);cursor:pointer;font-size:14px}
/* forms */
.fld label{display:block;font-size:13px;font-weight:700;color:var(--ink-2);margin-bottom:6px}
.fld label b{color:var(--sale)}
.inp{width:100%;border:1.5px solid var(--line-2);border-radius:12px;padding:12px 14px;font-size:14px;outline:0;color:var(--ink);transition:.2s;background:#fff}
.inp:focus{border-color:var(--p);box-shadow:var(--glow)}
.inp.err{border-color:var(--sale)}
.ferr{font-size:12px;color:var(--sale);margin-top:5px}
/* page hero */
.phero{position:relative;background:var(--grad);color:#fff;overflow:hidden}
.phero::before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.06) 1px,transparent 1px);background-size:40px 40px;transform:perspective(500px) rotateX(55deg) translateY(90px) scale(2);transform-origin:center bottom;opacity:.5}
.phero .wrap{position:relative;z-index:2;padding:34px 24px 38px}
.phero .crumb{display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,.75);margin-bottom:10px}
.phero .crumb a:hover{color:#fff}
.phero h1{font-size:clamp(26px,4vw,40px);font-weight:800;letter-spacing:-1px;margin:0 0 6px}
.phero h1 span{background:linear-gradient(90deg,#5eead4,#f59e0b);-webkit-background-clip:text;background-clip:text;color:transparent}
.phero p{color:rgba(255,255,255,.8);max-width:560px;margin:0;font-size:15px}
/* footer */
.footer{background:#0b2b28;color:#cfe5e1;margin-top:40px}
.footer .wrap{padding:44px 24px 20px}
.f-feats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;padding-bottom:30px;border-bottom:1px solid rgba(255,255,255,.1)}
.f-feat{display:flex;align-items:center;gap:12px;font-weight:700;font-size:14px;color:#fff}
.f-feat i{width:42px;height:42px;border-radius:12px;background:rgba(255,255,255,.08);color:var(--accent);display:grid;place-items:center;font-size:17px}
@media(max-width:768px){.f-feats{grid-template-columns:1fr 1fr}}
.f-main{display:grid;grid-template-columns:1.6fr 1fr 1fr 1.2fr;gap:28px;padding:34px 0}
@media(max-width:860px){.f-main{grid-template-columns:1fr 1fr}}
@media(max-width:560px){.f-main{grid-template-columns:1fr}}
.f-brand .logo{color:#fff;margin-bottom:14px}
.f-brand p{font-size:13.5px;line-height:1.6;color:#a9ccc6;margin-bottom:14px}
.f-brand .fl{font-size:13px;margin-bottom:6px;color:#cfe5e1}
.f-brand .fl i{color:var(--accent);width:18px}
.f-col h5{font-size:15px;font-weight:800;color:#fff;margin-bottom:16px}
.f-col a{display:block;font-size:13.5px;color:#a9ccc6;padding:5px 0}
.f-col a:hover{color:#fff}
.f-bottom{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:18px 0;border-top:1px solid rgba(255,255,255,.1);font-size:13px;color:#a9ccc6}
.socials{display:flex;gap:8px;align-items:center}
.socials a{width:34px;height:34px;border-radius:10px;background:rgba(255,255,255,.08);display:grid;place-items:center;color:#fff}
.socials a:hover{background:var(--accent);color:#0b2b28}
/* payment icons */
.payrow{display:flex;gap:6px;flex-wrap:wrap}
.payrow .pm{width:42px;height:28px;border-radius:5px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.15);background:#fff}
.payrow.sm .pm{width:36px;height:24px}
.payrow .pm svg{width:100%;height:100%;display:block}
/* mobile bottom nav */
.mnav{display:none;position:fixed;bottom:0;left:0;right:0;z-index:1500;background:#fff;border-top:1px solid var(--line);padding:6px 4px;grid-template-columns:repeat(4,1fr)}
.mnav a{display:flex;flex-direction:column;align-items:center;gap:3px;font-size:10.5px;font-weight:700;color:var(--muted);padding:6px 0;position:relative}
.mnav a.active{color:var(--p)}
.mnav a i{font-size:17px}
.mnav a .cnt{position:absolute;top:0;right:28%;min-width:16px;height:16px;border-radius:8px;background:var(--sale);color:#fff;font-size:9px;display:grid;place-items:center;padding:0 3px}
@media(max-width:860px){.mnav{display:grid}main{padding-bottom:64px}}
.totop{position:fixed;right:18px;bottom:80px;z-index:1400;width:46px;height:46px;border-radius:50%;border:0;background:var(--grad);color:#fff;font-size:16px;cursor:pointer;opacity:0;visibility:hidden;transition:.3s;box-shadow:0 12px 26px -8px rgba(15,118,110,.8)}
.totop.show{opacity:1;visibility:visible}
@media(max-width:860px){.totop{bottom:76px}}
</style>
@stack('styles')
<style id="store-theme-vars">{!! $themeCss ?? '' !!}</style>
</head>
<body class="{{ $themeBodyClass ?? 'tl-classic' }}">

<div class="topbar">
  <div class="wrap">
    <div class="topbar-loc"><i class="fa-solid fa-location-dot"></i><span>{{ $storeAddress ?: $storeName }}</span></div>
    <div class="topbar-marquee"><span>
      <b>Welcome to {{ $storeName }}!</b> {{ $freeOver > 0 ? 'Free delivery on orders over '.Money::fmt($freeOver) : 'Fast, reliable delivery' }}<i class="sep"></i><em>New arrivals</em> added regularly<i class="sep"></i>Cash on delivery available<i class="sep"></i>Easy returns<i class="sep"></i>
      <b>Welcome to {{ $storeName }}!</b> {{ $freeOver > 0 ? 'Free delivery on orders over '.Money::fmt($freeOver) : 'Fast, reliable delivery' }}<i class="sep"></i><em>New arrivals</em> added regularly<i class="sep"></i>Cash on delivery available<i class="sep"></i>Easy returns<i class="sep"></i>
    </span></div>
  </div>
</div>

<header class="header" id="header">
  <div class="wrap">
    <div class="hdr-main">
      <button class="burger" id="burger" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
      <a href="{{ $surl('store.index', $tenant) }}" class="logo" aria-label="{{ $storeName }} home">
        @if($logo)<img src="{{ $logo }}" alt="{{ $storeName }}">@else<span class="mk"><i class="fa-solid fa-bag-shopping"></i></span>{{ $storeName }}@endif
      </a>
      <div class="search">
        <form action="{{ $surl('store.index', $tenant) }}" method="GET">
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Search for products…" autocomplete="off">
          <button type="submit" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
      </div>
      <div class="hdr-actions">
        @if($storePhone)<a href="tel:{{ preg_replace('/[^0-9+]/','',$storePhone) }}" class="hdr-call"><i class="fa-solid fa-phone"></i><div><small>Hotline</small><strong>{{ $storePhone }}</strong></div></a>@endif
        <a href="{{ $surl('store.cart', $tenant) }}" class="hdr-cart" id="hdrCart">
          <span class="icon-btn" id="cartIcon"><i class="fa-solid fa-cart-shopping"></i><b class="cnt" id="cartCount">{{ $cartCount }}</b></span>
          <span><small>My cart</small><em id="cartTotalTxt">{{ Money::fmt($cartTotal) }}</em></span>
        </a>
      </div>
    </div>
  </div>
  <div class="navband"><div class="wrap">
    <div class="navrow">
      <div class="allcat" id="allcat">
        <button class="allcat-btn" type="button"><i class="fa-solid fa-table-cells-large"></i> All Categories <i class="fa-solid fa-chevron-down"></i></button>
        <div class="dd">
          <a href="{{ $surl('store.index', $tenant) }}"><i class="fa-solid fa-border-all"></i> All products</a>
          @foreach($navCategories as $c)
            <a href="{{ $surl('store.index', ['tenant'=>$tenant,'category'=>$c->slug]) }}"><i class="fa-solid fa-tag"></i> {{ $c->name }}</a>
            @foreach($c->children as $sub)
              <a href="{{ $surl('store.index', ['tenant'=>$tenant,'category'=>$sub->slug]) }}" class="sub"><i class="fa-solid fa-angle-right"></i> {{ $sub->name }}</a>
            @endforeach
          @endforeach
        </div>
      </div>
      <ul class="nav-main">
        <li><a href="{{ $surl('store.index', $tenant) }}" class="{{ request()->routeIs('store.index') && !request('category') && !request('on_sale') ? 'active' : '' }}">Home</a></li>
        <li><a href="{{ $surl('store.index', $tenant) }}" class="{{ request('category') ? 'active' : '' }}">Shop</a></li>
        <li><a href="{{ $surl('store.cart', $tenant) }}" class="{{ request()->routeIs('store.cart') ? 'active' : '' }}">Cart</a></li>
      </ul>
      <a href="{{ $surl('store.index', ['tenant'=>$tenant,'on_sale'=>1]) }}" class="deal-btn"><i class="fa-solid fa-bolt"></i> Deals</a>
    </div>
  </div></div>
</header>

<div class="drawer-bg" id="drawerBg"></div>
<aside class="drawer" id="drawer">
  <div class="drawer-head">
    <a href="{{ $surl('store.index', $tenant) }}" class="logo" style="font-size:18px">@if($logo)<img src="{{ $logo }}" alt="" style="height:34px">@else<span class="mk" style="width:32px;height:32px;font-size:15px"><i class="fa-solid fa-bag-shopping"></i></span>{{ $storeName }}@endif</a>
    <button class="icon-btn" id="drawerClose" style="width:40px;height:40px"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <form action="{{ $surl('store.index', $tenant) }}" method="GET"><input type="text" name="q" placeholder="Search products…"><button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button></form>
  <nav>
    <a href="{{ $surl('store.index', $tenant) }}"><i class="fa-solid fa-house"></i> Home</a>
    <a href="{{ $surl('store.index', $tenant) }}"><i class="fa-solid fa-bag-shopping"></i> Shop</a>
    <a href="{{ $surl('store.index', ['tenant'=>$tenant,'on_sale'=>1]) }}"><i class="fa-solid fa-bolt"></i> Deals</a>
    <a href="{{ $surl('store.cart', $tenant) }}"><i class="fa-solid fa-cart-shopping"></i> Cart ({{ $cartCount }})</a>
    @foreach($navCategories as $c)
      <a href="{{ $surl('store.index', ['tenant'=>$tenant,'category'=>$c->slug]) }}"><i class="fa-solid fa-tag"></i> {{ $c->name }}</a>
    @endforeach
  </nav>
</aside>

<div class="toasts" id="toasts">
  @if(session('success'))<div class="toast-z"><i class="ic fa-solid fa-circle-check"></i><div>{{ session('success') }} <a href="{{ $surl('store.cart', $tenant) }}">View cart</a></div><button class="x" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button></div>@endif
  @if(session('error'))<div class="toast-z err"><i class="ic fa-solid fa-circle-exclamation"></i><div>{{ session('error') }}</div><button class="x" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button></div>@endif
  @if(session('info'))<div class="toast-z info"><i class="ic fa-solid fa-circle-info"></i><div>{{ session('info') }}</div><button class="x" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button></div>@endif
</div>

<main>
  @yield('content')
</main>

<footer class="footer">
  <div class="wrap">
    <div class="f-feats">
      <div class="f-feat"><i class="fa-solid fa-gem"></i><span>Curated Quality</span></div>
      <div class="f-feat"><i class="fa-solid fa-truck-fast"></i><span>{{ $freeOver > 0 ? 'Free Delivery Over '.Money::fmt($freeOver) : 'Fast Delivery' }}</span></div>
      <div class="f-feat"><i class="fa-solid fa-money-bill-wave"></i><span>Cash on Delivery</span></div>
      <div class="f-feat"><i class="fa-solid fa-rotate-left"></i><span>Easy Returns</span></div>
    </div>
    <div class="f-main">
      <div class="f-brand">
        <a href="{{ $surl('store.index', $tenant) }}" class="logo">@if($logo)<img src="{{ $logo }}" alt="" style="height:40px">@else<span class="mk"><i class="fa-solid fa-bag-shopping"></i></span>{{ $storeName }}@endif</a>
        <p>{{ $tagline ? $tagline.'. ' : '' }}We curate quality products, keep prices honest, and deliver fast — with cash on delivery available.</p>
        @if($storeAddress)<div class="fl"><i class="fa-solid fa-location-dot"></i> {{ $storeAddress }}</div>@endif
        @if($storeEmail)<div class="fl"><i class="fa-solid fa-envelope"></i> {{ $storeEmail }}</div>@endif
        @if($storePhone)<div class="fl"><i class="fa-solid fa-phone"></i> {{ $storePhone }}</div>@endif
      </div>
      <div class="f-col">
        <h5>Categories</h5>
        @forelse($navCategories->take(6) as $c)<a href="{{ $surl('store.index', ['tenant'=>$tenant,'category'=>$c->slug]) }}">{{ $c->name }}</a>@empty<a href="{{ $surl('store.index', $tenant) }}">All products</a>@endforelse
      </div>
      <div class="f-col">
        <h5>Useful Links</h5>
        <a href="{{ $surl('store.index', $tenant) }}">Shop</a>
        <a href="{{ $surl('store.index', ['tenant'=>$tenant,'on_sale'=>1]) }}">Deals</a>
        <a href="{{ $surl('store.cart', $tenant) }}">Cart</a>
        <a href="{{ $surl('store.checkout', $tenant) }}">Checkout</a>
      </div>
      <div class="f-col">
        <h5>We Accept</h5>
        @include('ecommerce.store.partials.payment-icons', ['size'=>'sm'])
        <div style="margin-top:16px">
          <small style="color:#a9ccc6;font-size:12px">Stay connected</small>
          <div class="socials" style="margin-top:8px">
            @if($social['facebook'])<a href="{{ $social['facebook'] }}" target="_blank" rel="noopener"><i class="fa-brands fa-facebook-f"></i></a>@endif
            @if($social['instagram'])<a href="{{ $social['instagram'] }}" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i></a>@endif
            @if($social['twitter'])<a href="{{ $social['twitter'] }}" target="_blank" rel="noopener"><i class="fa-brands fa-x-twitter"></i></a>@endif
            @if($social['whatsapp'])<a href="{{ $social['whatsapp'] }}" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i></a>@endif
          </div>
        </div>
      </div>
    </div>
    <div class="f-bottom">
      <div>©{{ date('Y') }} {{ $storeName }}. All rights reserved.</div>
      @include('ecommerce.store.partials.payment-icons')
    </div>
  </div>
</footer>

<nav class="mnav">
  <a href="{{ $surl('store.index', $tenant) }}" class="{{ request()->routeIs('store.index') && !request('category') ? 'active' : '' }}"><i class="fa-solid fa-house"></i>Home</a>
  <a href="{{ $surl('store.index', ['tenant'=>$tenant,'category'=>'all']) }}" class="{{ request('category') ? 'active' : '' }}"><i class="fa-solid fa-bag-shopping"></i>Shop</a>
  <a href="{{ $surl('store.cart', $tenant) }}" class="{{ request()->routeIs('store.cart') ? 'active' : '' }}"><i class="fa-solid fa-cart-shopping"></i>Cart<b class="cnt js-cart-count">{{ $cartCount }}</b></a>
  <a href="{{ $surl('store.checkout', $tenant) }}" class="{{ request()->routeIs('store.checkout') ? 'active' : '' }}"><i class="fa-solid fa-credit-card"></i>Pay</a>
</nav>

<button class="totop" id="toTop" aria-label="Back to top"><i class="fa-solid fa-arrow-up"></i></button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const fine = matchMedia('(hover:hover) and (pointer:fine)').matches;
  const rm   = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const token = document.querySelector('meta[name=csrf-token]').content;

  /* 3D tilt */
  window.zTilt = function(root){
    if(!fine||rm) return;
    (root||document).querySelectorAll('[data-tilt]:not(.tilt-ready)').forEach(el=>{
      el.classList.add('tilt-ready');
      if(!el.querySelector('.tilt-glare')){ const g=document.createElement('span'); g.className='tilt-glare'; el.appendChild(g); }
      const max=parseFloat(el.dataset.tilt)||8;
      el.addEventListener('mousemove',e=>{ const r=el.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5; el.style.setProperty('--rx',(-y*max)+'deg'); el.style.setProperty('--ry',(x*max)+'deg'); el.style.setProperty('--gx',(x*100+50)+'%'); el.style.setProperty('--gy',(y*100+50)+'%'); el.classList.add('tilting'); });
      el.addEventListener('mouseleave',()=>{ el.style.setProperty('--rx','0deg'); el.style.setProperty('--ry','0deg'); el.classList.remove('tilting'); });
    });
  };
  /* Reveal */
  const io=new IntersectionObserver(es=>es.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target); } }),{rootMargin:'0px 0px -8% 0px',threshold:.08});
  window.zReveal=function(root){ (root||document).querySelectorAll('.reveal:not(.in)').forEach(el=>io.observe(el)); };
  /* Toast */
  window.zToast=function(msg,type){
    const t=document.createElement('div'); t.className='toast-z '+(type||'');
    const ic=type==='err'?'fa-circle-exclamation':type==='info'?'fa-circle-info':'fa-circle-check';
    t.innerHTML='<i class="ic fa-solid '+ic+'"></i><div>'+msg+'</div><button class="x"><i class="fa-solid fa-xmark"></i></button>';
    t.querySelector('.x').onclick=()=>t.remove();
    document.getElementById('toasts').appendChild(t);
    setTimeout(()=>{ t.style.transition='opacity .4s'; t.style.opacity='0'; setTimeout(()=>t.remove(),400); },4200);
  };
  document.querySelectorAll('#toasts .toast-z').forEach(t=>setTimeout(()=>{ t.style.transition='opacity .4s'; t.style.opacity='0'; setTimeout(()=>t.remove(),400); },5000));

  /* AJAX add-to-cart with fly animation */
  function fly(from){
    const to=document.getElementById('cartIcon'); if(!from||!to||rm) return;
    const a=from.getBoundingClientRect(),b=to.getBoundingClientRect();
    const d=document.createElement('div');
    d.style.cssText='position:fixed;z-index:3000;left:'+(a.left+a.width/2-14)+'px;top:'+(a.top+a.height/2-14)+'px;width:28px;height:28px;border-radius:50%;background:var(--grad);box-shadow:0 10px 24px -8px rgba(15,118,110,.8);transition:all .8s cubic-bezier(.2,.8,.2,1);pointer-events:none';
    document.body.appendChild(d);
    requestAnimationFrame(()=>{ d.style.left=(b.left+b.width/2-6)+'px'; d.style.top=(b.top+b.height/2-6)+'px'; d.style.width='12px'; d.style.height='12px'; d.style.opacity='.2'; });
    setTimeout(()=>{ d.remove(); to.classList.add('bump'); setTimeout(()=>to.classList.remove('bump'),500); },800);
  }
  const CART_URL=@json($surl('store.cart', $tenant));
  function showGoToCart(btn){
    if(!btn) return;
    const a=document.createElement('a'); a.href=CART_URL; a.className=btn.className+' go-cart'; a.innerHTML='<i class="fa-solid fa-cart-shopping"></i> Go to Cart';
    a.style.cssText='background:var(--grad);color:#fff;box-shadow:0 10px 20px -10px rgba(15,118,110,.8);text-decoration:none';
    btn.style.display='none'; btn.insertAdjacentElement('afterend',a);
    setTimeout(()=>{ a.remove(); btn.style.display=''; btn.disabled=false; },2000);
  }
  document.addEventListener('submit',async e=>{
    const f=e.target; if(!f.classList.contains('js-atc')) return;
    e.preventDefault();
    const btn=f.querySelector('button[type=submit]'); const old=btn?btn.innerHTML:''; if(btn){ btn.disabled=true; btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i>'; }
    const restore=()=>{ if(btn){ btn.innerHTML=old; btn.disabled=false; } };
    try{
      const r=await fetch(f.action,{method:'POST',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:new FormData(f)});
      if(r.ok){
        const j=await r.json();
        document.querySelectorAll('#cartCount,.js-cart-count').forEach(el=>el.textContent=j.count);
        if(j.total!==undefined){ const tt=document.getElementById('cartTotalTxt'); if(tt) tt.textContent=window.zmoney(j.total); }
        fly(btn); zToast('<b>Added to cart!</b> '+(j.name?('"'+j.name+'" is in your cart. '):'')+'<a href="'+CART_URL+'">View cart</a>');
        if(btn){ btn.innerHTML=old; showGoToCart(btn); }
      } else {
        let msg='Could not add to cart';
        try{ const j=await r.json(); msg=Object.values(j.errors||{}).flat()[0]||j.message||msg; }catch(_){}
        zToast(msg,'err'); restore();
      }
    }catch(_){ zToast('Network error — please try again','err'); restore(); }
  });

  /* header scroll / to-top / drawer / dropdown */
  const header=document.getElementById('header');
  const onScroll=()=>{ header.classList.toggle('scrolled',scrollY>10); document.getElementById('toTop').classList.toggle('show',scrollY>500); };
  addEventListener('scroll',onScroll,{passive:true}); onScroll();
  document.getElementById('toTop').onclick=()=>scrollTo({top:0,behavior:'smooth'});
  const open=()=>document.body.classList.add('drawer-open'), close=()=>document.body.classList.remove('drawer-open');
  document.getElementById('burger').onclick=open; document.getElementById('drawerClose').onclick=close; document.getElementById('drawerBg').onclick=close;
  const allcat=document.getElementById('allcat');
  if(allcat){ allcat.querySelector('.allcat-btn').addEventListener('click',e=>{ e.stopPropagation(); allcat.classList.toggle('open'); }); document.addEventListener('click',e=>{ if(!e.target.closest('#allcat')) allcat.classList.remove('open'); }); }

  document.addEventListener('DOMContentLoaded',()=>{ zTilt(); zReveal(); });
})();
</script>
@stack('scripts')
</body>
</html>
