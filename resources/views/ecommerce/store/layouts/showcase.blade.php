{{-- Showcase layout: home-page style — hero, category tiles, deals strip, featured picks, then the full catalog. --}}
@push('styles')
<style>
.sc-sec{padding:40px 0 8px}
.sc-sec + .sc-sec{padding-top:26px}
/* category tiles */
.sc-cats{display:grid;grid-template-columns:repeat(6,1fr);gap:14px}
@media(max-width:1300px){.sc-cats{grid-template-columns:repeat(4,1fr)}}
@media(max-width:760px){.sc-cats{grid-template-columns:repeat(2,1fr);gap:10px}}
.sc-cat{position:relative;display:flex;flex-direction:column;align-items:flex-start;gap:10px;padding:20px 18px;border-radius:var(--r-lg);background:linear-gradient(145deg,var(--p-soft),#fff 70%);border:1px solid var(--line);overflow:hidden;transition:transform .25s,box-shadow .25s}
.sc-cat:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg);color:inherit}
.sc-cat .ic{width:46px;height:46px;border-radius:14px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:18px;box-shadow:0 10px 20px -10px rgba(var(--shadow-rgb),.7)}
.sc-cat b{font-size:15px;font-weight:800;color:var(--ink);line-height:1.2}
.sc-cat small{font-size:12px;color:var(--muted);font-weight:600}
.sc-cat .arr{position:absolute;right:14px;bottom:14px;width:30px;height:30px;border-radius:50%;background:#fff;border:1px solid var(--line);display:grid;place-items:center;color:var(--p);font-size:12px;transition:.25s}
.sc-cat:hover .arr{background:var(--p);color:#fff;border-color:var(--p)}
/* deals strip */
.sc-deals{position:relative;border-radius:var(--r-lg);background:linear-gradient(120deg,var(--brand-dark),var(--brand) 60%,var(--brand-light));padding:22px 22px 18px;color:#fff;overflow:hidden}
.sc-deals::after{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.07) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.07) 1px,transparent 1px);background-size:34px 34px;pointer-events:none}
.sc-deals .sec-head{position:relative;z-index:1;margin-bottom:16px}
.sc-deals .sec-title{color:#fff}
.sc-deals .sec-sub{color:rgba(255,255,255,.8)}
.sc-deals .sec-link{color:#fff}
.sc-deals .sec-deco i{background:var(--accent)}.sc-deals .sec-deco b{background:#fff}
.sc-strip{position:relative;z-index:1;display:flex;gap:14px;overflow-x:auto;padding:4px 2px 10px;scroll-snap-type:x mandatory;scrollbar-width:thin}
.sc-strip>div{flex:0 0 240px;scroll-snap-align:start}
@media(max-width:640px){.sc-strip>div{flex-basis:200px}}
/* featured */
.sc-feat{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
@media(max-width:1300px){.sc-feat{grid-template-columns:repeat(3,1fr)}}
@media(max-width:760px){.sc-feat{grid-template-columns:repeat(2,1fr);gap:10px}}
/* promo band */
.sc-band{display:grid;grid-template-columns:1.2fr 1fr;gap:16px}
@media(max-width:900px){.sc-band{grid-template-columns:1fr}}
.sc-band a{position:relative;display:flex;flex-direction:column;justify-content:center;min-height:170px;padding:28px;border-radius:var(--r-lg);color:#fff;overflow:hidden}
.sc-band a.b1{background:linear-gradient(135deg,var(--brand-dark),var(--brand))}
.sc-band a.b2{background:linear-gradient(135deg,var(--accent),var(--sale))}
.sc-band a small{font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;opacity:.85}
.sc-band a b{font-size:clamp(22px,3vw,30px);font-weight:800;line-height:1.1;margin:6px 0 10px}
.sc-band a span{display:inline-flex;align-items:center;gap:8px;background:#fff;color:var(--ink);padding:9px 14px;border-radius:999px;font-size:13px;font-weight:800;width:max-content}
.sc-band a .e{position:absolute;right:18px;bottom:10px;font-size:84px;opacity:.9;transform:rotate(-10deg);animation:floatY 6s ease-in-out infinite}
.sc-band a:hover{color:#fff}
/* catalog */
.toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;background:var(--surface);border-radius:var(--r);padding:12px 16px;margin-bottom:18px}
.toolbar .cnt{font-size:14px;color:var(--muted)}
.toolbar .cnt b{color:var(--ink)}
.toolbar select,.toolbar input{border:1.5px solid var(--line-2);border-radius:10px;padding:8px 12px;font-size:13px;font-weight:600;background:#fff;outline:0;color:var(--ink)}
.toolbar input{width:96px}
.toolbar .go{border:0;background:var(--grad);color:#fff;border-radius:10px;padding:9px 14px;font-weight:700;font-size:13px;cursor:pointer}
.chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.chips a{display:inline-flex;align-items:center;gap:8px;background:var(--p-soft);color:var(--p);padding:6px 12px;border-radius:999px;font-size:12.5px;font-weight:700}
.chips a.clear{background:#fff;border:1px solid var(--line-2);color:var(--muted)}
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
@media(min-width:1600px){.grid{grid-template-columns:repeat(5,1fr)}}
@media(max-width:1300px){.grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:640px){.grid{grid-template-columns:repeat(2,1fr);gap:10px}}
.empty{text-align:center;padding:70px 20px;background:var(--surface);border-radius:var(--r-lg)}
.empty .e{font-size:60px;margin-bottom:12px;display:inline-block}
.empty h3{font-weight:800;margin-bottom:6px}
.empty p{color:var(--muted);margin-bottom:18px}
.pager{margin:28px 0 10px;display:flex;justify-content:center;align-items:center;gap:10px}
.pager a,.pager span{display:inline-flex;align-items:center;gap:6px;border:1.5px solid var(--line-2);border-radius:10px;padding:9px 16px;font-weight:700;font-size:13.5px;color:var(--ink-2);background:#fff}
.pager a:hover{border-color:var(--p);color:var(--p);background:var(--p-soft)}
.pager .disabled{opacity:.4;pointer-events:none}
.pager .info{border:0;background:none;color:var(--muted)}
</style>
@endpush

@php
  $hasFilters = request()->hasAny(['category','min_price','max_price','q','on_sale']);
  $isHome = ! $hasFilters && ! request('page');
  $countFor = function ($c) use ($catCounts) { $n = (int) ($catCounts[$c->id] ?? 0); foreach ($c->children as $s) { $n += (int) ($catCounts[$s->id] ?? 0); } return $n; };
  $icons = ['fa-tag','fa-shirt','fa-mobile-screen','fa-couch','fa-gem','fa-basket-shopping','fa-gift','fa-star','fa-leaf','fa-bolt'];
@endphp

@include('ecommerce.store.partials.hero', [
  'crumbs' => $isHome ? [] : array_values(array_filter([
    ['Home', $surl('store.index', $tenant)],
    ['Shop', $selectedCategory ? $surl('store.index', $tenant) : null],
    $selectedCategory ? [$selectedCategory->name, null] : null,
  ])),
  'title' => $selectedCategory ? e($selectedCategory->name) : (request('q') ? 'Search: <span>'.e(request('q')).'</span>' : 'Discover <span>'.e($storeName).'</span>'),
  'text' => $selectedCategory
      ? 'Everything in '.$selectedCategory->name.', hand-picked and ready to ship.'
      : ($tagline ?: 'Fresh arrivals, honest prices and fast delivery — explore our collections below.'),
])

@if($isHome)
  {{-- Shop by category --}}
  @if($categories->count())
  <section class="sc-sec wrap reveal">
    <div class="sec-head">
      <div><div class="sec-deco"><i></i><b></b></div><h2 class="sec-title">Shop by category</h2><p class="sec-sub">Jump straight to what you're looking for.</p></div>
      <a href="{{ $surl('store.index', ['tenant'=>$tenant,'category'=>'all']) }}" class="sec-link">All products <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <div class="sc-cats">
      @foreach($categories->take(12) as $i => $c)
        <a href="{{ $surl('store.index', ['tenant'=>$tenant,'category'=>$c->slug]) }}" class="sc-cat reveal" data-delay="{{ ($i%4)+1 }}">
          <span class="ic"><i class="fa-solid {{ $icons[$i % count($icons)] }}"></i></span>
          <b>{{ $c->name }}</b>
          <small>{{ $countFor($c) }} {{ \Illuminate\Support\Str::plural('product', $countFor($c)) }}</small>
          <span class="arr"><i class="fa-solid fa-arrow-right"></i></span>
        </a>
      @endforeach
    </div>
  </section>
  @endif

  {{-- Deals strip --}}
  @if($deals->count())
  <section class="sc-sec wrap reveal">
    <div class="sc-deals">
      <div class="sec-head">
        <div><div class="sec-deco"><i></i><b></b></div><h2 class="sec-title"><i class="fa-solid fa-bolt" style="color:var(--accent)"></i> Deals of the day</h2><p class="sec-sub">Limited-time prices on {{ $deals->count() }} picks.</p></div>
        <a href="{{ $surl('store.index', ['tenant'=>$tenant,'on_sale'=>1]) }}" class="sec-link">See all deals <i class="fa-solid fa-arrow-right"></i></a>
      </div>
      <div class="sc-strip">
        @foreach($deals as $product)
          <div>@include('ecommerce.store.partials.product-card', ['product'=>$product])</div>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  {{-- Featured picks --}}
  @if($featured->count())
  <section class="sc-sec wrap reveal">
    <div class="sec-head">
      <div><div class="sec-deco"><i></i><b></b></div><h2 class="sec-title">Featured picks</h2><p class="sec-sub">Our favourites this season.</p></div>
    </div>
    <div class="sc-feat">
      @foreach($featured as $i => $product)
        <div class="reveal" data-delay="{{ ($i%4)+1 }}">@include('ecommerce.store.partials.product-card', ['product'=>$product])</div>
      @endforeach
    </div>
  </section>
  @endif

  {{-- Promo band --}}
  <section class="sc-sec wrap reveal">
    <div class="sc-band">
      <a href="{{ $surl('store.index', ['tenant'=>$tenant,'on_sale'=>1]) }}" class="b1"><small>Special offers</small><b>Save more on<br>selected items</b><span>Shop deals <i class="fa-solid fa-arrow-right"></i></span><em class="e">⚡</em></a>
      <a href="{{ $surl('store.index', ['tenant'=>$tenant,'sort'=>'']) }}" class="b2"><small>Just landed</small><b>New arrivals<br>every week</b><span>Browse new <i class="fa-solid fa-arrow-right"></i></span><em class="e">✨</em></a>
    </div>
  </section>
@endif

{{-- Catalog --}}
<section class="sc-sec wrap" id="catalog">
  <div class="sec-head">
    <div><div class="sec-deco"><i></i><b></b></div><h2 class="sec-title">{{ $selectedCategory->name ?? (request('q') ? 'Search results' : 'All products') }}</h2><p class="sec-sub">{{ $products->total() }} products{{ $selectedCategory ? ' in '.$selectedCategory->name : '' }}</p></div>
  </div>

  @if($hasFilters)
  <div class="chips">
    @if(request('q'))<a href="{{ $surl('store.index', ['tenant'=>$tenant] + request()->except(['q','page'])) }}">“{{ request('q') }}” <i class="fa-solid fa-xmark"></i></a>@endif
    @if($selectedCategory)<a href="{{ $surl('store.index', ['tenant'=>$tenant] + request()->except(['category','page'])) }}">{{ $selectedCategory->name }} <i class="fa-solid fa-xmark"></i></a>@endif
    @if(request('min_price')||request('max_price'))<a href="{{ $surl('store.index', ['tenant'=>$tenant] + request()->except(['min_price','max_price','page'])) }}">{{ $curSymbol }}{{ request('min_price',0) }} – {{ request('max_price','∞') }} <i class="fa-solid fa-xmark"></i></a>@endif
    @if(request('on_sale'))<a href="{{ $surl('store.index', ['tenant'=>$tenant] + request()->except(['on_sale','page'])) }}">On sale <i class="fa-solid fa-xmark"></i></a>@endif
    <a href="{{ $surl('store.index', $tenant) }}" class="clear">Clear all</a>
  </div>
  @endif

  <div class="toolbar">
    <div class="cnt">Showing <b>{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}</b> of <b>{{ $products->total() }}</b></div>
    <form method="GET" action="{{ $surl('store.index', $tenant) }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
      @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
      <input type="number" name="min_price" placeholder="Min" value="{{ request('min_price') }}" min="0">
      <input type="number" name="max_price" placeholder="Max" value="{{ request('max_price') }}" min="0">
      <select name="sort">
        <option value="">Sort: Newest</option>
        <option value="price_asc" {{ request('sort')==='price_asc'?'selected':'' }}>Price: Low to High</option>
        <option value="price_desc" {{ request('sort')==='price_desc'?'selected':'' }}>Price: High to Low</option>
        <option value="name_asc" {{ request('sort')==='name_asc'?'selected':'' }}>Name A–Z</option>
        <option value="discount" {{ request('sort')==='discount'?'selected':'' }}>Biggest Discount</option>
      </select>
      <button type="submit" class="go"><i class="fa-solid fa-filter"></i> Apply</button>
    </form>
  </div>

  @if($products->count())
    <div class="grid">
      @foreach($products as $i => $product)
        <div class="reveal" data-delay="{{ ($i%4)+1 }}">@include('ecommerce.store.partials.product-card', ['product'=>$product])</div>
      @endforeach
    </div>
    @if($products->hasPages())
    <div class="pager">
      @if($products->onFirstPage())<span class="disabled"><i class="fa-solid fa-arrow-left"></i> Prev</span>
      @else<a href="{{ $products->previousPageUrl() }}#catalog"><i class="fa-solid fa-arrow-left"></i> Prev</a>@endif
      <span class="info">Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
      @if($products->hasMorePages())<a href="{{ $products->nextPageUrl() }}#catalog">Next <i class="fa-solid fa-arrow-right"></i></a>
      @else<span class="disabled">Next <i class="fa-solid fa-arrow-right"></i></span>@endif
    </div>
    @endif
  @else
    <div class="empty">
      <span class="e">🔍</span>
      <h3>No products match these filters</h3>
      <p>Try a different category, widen the price range, or clear the search.</p>
      <a href="{{ $surl('store.index', $tenant) }}" class="btn-g">Show all products</a>
    </div>
  @endif
</section>
