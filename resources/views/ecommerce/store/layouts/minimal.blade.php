{{-- Minimal layout: no hero/top bar. Slim page head, category chips, inline filters, wide clean grid. --}}
@push('styles')
<style>
.mn-head{padding:34px 0 10px}
.mn-head .crumb{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--muted);margin-bottom:8px}
.mn-head .crumb a:hover{color:var(--p)}
.mn-head h1{font-size:clamp(26px,3.4vw,36px);font-weight:800;letter-spacing:-.8px;margin:0}
.mn-head h1 span{color:var(--p)}
.mn-head p{color:var(--muted);margin:6px 0 0;font-size:14px}
.mn-cats{display:flex;gap:8px;overflow-x:auto;padding:18px 0 6px;scrollbar-width:none;-ms-overflow-style:none}
.mn-cats::-webkit-scrollbar{display:none}
.mn-cats a{flex-shrink:0;display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:999px;border:1.5px solid var(--line-2);background:#fff;font-size:13.5px;font-weight:700;color:var(--ink-2);transition:.15s;white-space:nowrap}
.mn-cats a:hover{border-color:var(--p);color:var(--p)}
.mn-cats a.on{background:var(--brand);border-color:var(--brand);color:#fff}
.mn-cats a .n{font-size:11px;font-weight:700;background:rgba(0,0,0,.06);padding:2px 7px;border-radius:999px;color:inherit}
.mn-cats a.on .n{background:rgba(255,255,255,.22)}
.mn-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line);margin:10px 0 22px}
.mn-bar .cnt{font-size:13.5px;color:var(--muted)}
.mn-bar .cnt b{color:var(--ink)}
.mn-filters{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.mn-filters input,.mn-filters select{border:1.5px solid var(--line-2);border-radius:10px;padding:8px 11px;font-size:13px;font-weight:600;background:#fff;outline:0;color:var(--ink);height:38px}
.mn-filters input{width:96px}
.mn-filters input:focus,.mn-filters select:focus{border-color:var(--p)}
.mn-filters .sale{display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:700;color:var(--ink-2);padding:0 4px;cursor:pointer}
.mn-filters .sale input{width:16px;height:16px;accent-color:var(--p)}
.mn-filters .go{border:0;background:var(--brand);color:#fff;border-radius:10px;padding:0 14px;height:38px;font-weight:700;font-size:13px;cursor:pointer}
.mn-filters .go:hover{filter:brightness(1.06)}
.chips{display:flex;gap:8px;flex-wrap:wrap;margin:-8px 0 18px}
.chips a{display:inline-flex;align-items:center;gap:8px;background:var(--p-soft);color:var(--p);padding:6px 12px;border-radius:999px;font-size:12.5px;font-weight:700}
.chips a.clear{background:#fff;border:1px solid var(--line-2);color:var(--muted)}
.grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px}
@media(max-width:1400px){.grid{grid-template-columns:repeat(4,1fr)}}
@media(max-width:1100px){.grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:640px){.grid{grid-template-columns:repeat(2,1fr);gap:10px}}
.grid .pcard{border-color:transparent;background:var(--surface)}
.grid .pcard:hover{background:#fff;border-color:var(--line)}
.empty{text-align:center;padding:70px 20px;background:var(--surface);border-radius:var(--r-lg)}
.empty .e{font-size:60px;margin-bottom:12px;display:inline-block}
.empty h3{font-weight:800;margin-bottom:6px}
.empty p{color:var(--muted);margin-bottom:18px}
.pager{margin:30px 0 10px;display:flex;justify-content:center;align-items:center;gap:10px}
.pager a,.pager span{display:inline-flex;align-items:center;gap:6px;border:1.5px solid var(--line-2);border-radius:10px;padding:9px 16px;font-weight:700;font-size:13.5px;color:var(--ink-2);background:#fff}
.pager a:hover{border-color:var(--p);color:var(--p);background:var(--p-soft)}
.pager .disabled{opacity:.4;pointer-events:none}
.pager .info{border:0;background:none;color:var(--muted)}
</style>
@endpush

@php
  $hasFilters = request()->hasAny(['category','min_price','max_price','q','on_sale']);
  $countFor = function ($c) use ($catCounts) { $n = (int) ($catCounts[$c->id] ?? 0); foreach ($c->children as $s) { $n += (int) ($catCounts[$s->id] ?? 0); } return $n; };
@endphp

<section class="wrap">
  <div class="mn-head">
    <div class="crumb">
      <a href="{{ $surl('store.index', $tenant) }}">Home</a><i class="fa-solid fa-chevron-right" style="font-size:9px"></i>
      @if($selectedCategory)<a href="{{ $surl('store.index', $tenant) }}">Shop</a><i class="fa-solid fa-chevron-right" style="font-size:9px"></i><span>{{ $selectedCategory->name }}</span>@else<span>Shop</span>@endif
    </div>
    <h1>@if($selectedCategory){{ $selectedCategory->name }}@elseif(request('q'))Results for <span>“{{ request('q') }}”</span>@else All <span>Products</span>@endif</h1>
    <p>{{ $products->total() }} products{{ $selectedCategory ? ' in '.$selectedCategory->name : '' }} — simple, fast, no clutter.</p>
  </div>

  <nav class="mn-cats" aria-label="Categories">
    <a href="{{ $surl('store.index', $tenant) }}" class="{{ !request('category') ? 'on' : '' }}"><i class="fa-solid fa-border-all"></i> All</a>
    @foreach($categories as $c)
      <a href="{{ $surl('store.index', ['tenant'=>$tenant,'category'=>$c->slug]) }}" class="{{ request('category')===$c->slug || $c->children->pluck('slug')->contains(request('category')) ? 'on' : '' }}">{{ $c->name }} <span class="n">{{ $countFor($c) }}</span></a>
    @endforeach
    <a href="{{ $surl('store.index', ['tenant'=>$tenant,'on_sale'=>1]) }}" class="{{ request('on_sale') ? 'on' : '' }}"><i class="fa-solid fa-bolt"></i> Deals</a>
  </nav>

  @if($selectedCategory && $selectedCategory->children->count())
  <nav class="mn-cats" style="padding-top:4px">
    @foreach($selectedCategory->children as $sub)
      <a href="{{ $surl('store.index', ['tenant'=>$tenant,'category'=>$sub->slug]) }}" class="{{ request('category')===$sub->slug ? 'on' : '' }}"><i class="fa-solid fa-angle-right"></i> {{ $sub->name }}</a>
    @endforeach
  </nav>
  @endif

  <div class="mn-bar">
    <div class="cnt">Showing <b>{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}</b> of <b>{{ $products->total() }}</b></div>
    <form method="GET" action="{{ $surl('store.index', $tenant) }}" class="mn-filters">
      @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
      @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
      <input type="number" name="min_price" placeholder="Min {{ (int) floor($priceBounds->min_p ?? 0) }}" value="{{ request('min_price') }}" min="0">
      <input type="number" name="max_price" placeholder="Max {{ (int) ceil($priceBounds->max_p ?? 0) }}" value="{{ request('max_price') }}" min="0">
      <label class="sale"><input type="checkbox" name="on_sale" value="1" {{ request('on_sale')?'checked':'' }}> On sale</label>
      <select name="sort">
        <option value="">Newest</option>
        <option value="price_asc" {{ request('sort')==='price_asc'?'selected':'' }}>Price: Low → High</option>
        <option value="price_desc" {{ request('sort')==='price_desc'?'selected':'' }}>Price: High → Low</option>
        <option value="name_asc" {{ request('sort')==='name_asc'?'selected':'' }}>Name A–Z</option>
        <option value="discount" {{ request('sort')==='discount'?'selected':'' }}>Biggest discount</option>
      </select>
      <button type="submit" class="go"><i class="fa-solid fa-filter"></i> Apply</button>
    </form>
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

  @if($products->count())
    <div class="grid">
      @foreach($products as $i => $product)
        <div class="reveal" data-delay="{{ ($i%5)+1 }}">@include('ecommerce.store.partials.product-card', ['product'=>$product])</div>
      @endforeach
    </div>
    @if($products->hasPages())
    <div class="pager">
      @if($products->onFirstPage())<span class="disabled"><i class="fa-solid fa-arrow-left"></i> Prev</span>
      @else<a href="{{ $products->previousPageUrl() }}"><i class="fa-solid fa-arrow-left"></i> Prev</a>@endif
      <span class="info">Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
      @if($products->hasMorePages())<a href="{{ $products->nextPageUrl() }}">Next <i class="fa-solid fa-arrow-right"></i></a>
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
