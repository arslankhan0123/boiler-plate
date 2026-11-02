@extends('ecommerce.store.layout')
@section('title', $selectedCategory->name ?? (request('q') ? 'Search: '.request('q') : 'Shop'))

@use('App\Support\Ecommerce\Money')

@push('styles')
<style>
.shop{padding:30px 0 20px}
.shop-grid{display:grid;grid-template-columns:270px 1fr;gap:26px;align-items:start}
@media(max-width:992px){.shop-grid{grid-template-columns:1fr}}
.side{position:sticky;top:calc(var(--head-h) + 20px)}
@media(max-width:992px){.side{position:static;display:none}.side.open{display:block}}
.fcard{background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:18px;margin-bottom:16px}
.fcard h5{font-size:15px;font-weight:800;margin:0 0 14px;display:flex;align-items:center;justify-content:space-between}
.fcard h5 i{color:var(--p)}
.fcat a{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:10px;font-size:14px;color:var(--ink-2);font-weight:600;transition:.15s}
.fcat a:hover{background:var(--p-soft);color:var(--p)}
.fcat a.on{background:var(--grad);color:#fff}
.fcat .sub a{padding-left:26px;font-size:13px}
.price-in{display:flex;gap:10px;align-items:center;margin-bottom:12px}
.price-in input{width:100%;border:1.5px solid var(--line-2);border-radius:10px;padding:9px 10px;font-size:13px;outline:0}
.price-in input:focus{border-color:var(--p)}
.chk{display:flex;align-items:center;gap:10px;padding:8px 0;font-size:14px;color:var(--ink-2);cursor:pointer;font-weight:600}
.chk input{width:18px;height:18px;accent-color:var(--p)}
.side-promo{border-radius:var(--r);background:linear-gradient(160deg,var(--brand-dark),var(--brand) 60%,var(--accent));color:#fff;padding:22px;position:relative;overflow:hidden}
.side-promo b{font-size:22px;display:block;line-height:1.1}
.side-promo span{font-size:13px;opacity:.9}
.side-promo .e{position:absolute;right:-6px;bottom:-10px;font-size:76px;transform:rotate(-12deg);animation:floatY 6s ease-in-out infinite}
.toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;background:var(--surface);border-radius:var(--r);padding:12px 16px;margin-bottom:18px}
.toolbar .cnt{font-size:14px;color:var(--muted)}
.toolbar .cnt b{color:var(--ink)}
.toolbar select{border:1.5px solid var(--line-2);border-radius:10px;padding:8px 12px;font-size:13px;font-weight:600;background:#fff;outline:0;color:var(--ink)}
.chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.chips a{display:inline-flex;align-items:center;gap:8px;background:var(--p-soft);color:var(--p);padding:6px 12px;border-radius:999px;font-size:12.5px;font-weight:700}
.chips a.clear{background:#fff;border:1px solid var(--line-2);color:var(--muted)}
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
@media(min-width:1600px){.grid{grid-template-columns:repeat(5,1fr)}}
@media(max-width:1400px){.grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:640px){.grid{grid-template-columns:repeat(2,1fr);gap:10px}}
.empty{text-align:center;padding:70px 20px;background:var(--surface);border-radius:var(--r-lg)}
.empty .e{font-size:60px;margin-bottom:12px;display:inline-block;animation:floatY 5s ease-in-out infinite}
.empty h3{font-weight:800;margin-bottom:6px}
.empty p{color:var(--muted);margin-bottom:18px}
.pager{margin-top:28px;display:flex;justify-content:center;align-items:center;gap:10px}
.pager a,.pager span{display:inline-flex;align-items:center;gap:6px;border:1.5px solid var(--line-2);border-radius:10px;padding:9px 16px;font-weight:700;font-size:13.5px;color:var(--ink-2);background:#fff}
.pager a:hover{border-color:var(--p);color:var(--p);background:var(--p-soft)}
.pager .disabled{opacity:.4;pointer-events:none}
.pager .info{border:0;background:none;color:var(--muted)}
.filter-toggle{display:none}
@media(max-width:992px){.filter-toggle{display:inline-flex}}
</style>
@endpush

@section('content')
@include('ecommerce.store.partials.hero', [
  'crumbs' => array_values(array_filter([
    ['Home', route('store.index', $tenant)],
    $selectedCategory ? ['Shop', route('store.index', $tenant)] : ['Shop', null],
    $selectedCategory ? [$selectedCategory->name, null] : null,
  ])),
  'title' => $selectedCategory ? e($selectedCategory->name) : (request('q') ? 'Search: <span>'.e(request('q')).'</span>' : 'All <span>Products</span>'),
  'text' => $selectedCategory
      ? 'Everything in '.$selectedCategory->name.', hand-picked and ready to ship.'
      : 'Browse the full catalog — '.$products->total().' products, filtered your way.',
])

<section class="shop wrap">
  <div class="shop-grid">
    <aside class="side" id="side">
      <form method="GET" action="{{ route('store.index', $tenant) }}" id="filterForm">
        @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
        @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
        <input type="hidden" name="category" id="catInput" value="{{ request('category') }}">
        <div class="fcard">
          <h5>Categories <i class="fa-solid fa-layer-group"></i></h5>
          <div class="fcat">
            <a href="{{ route('store.index', $tenant) }}" class="{{ !request('category')?'on':'' }}"><i class="fa-solid fa-border-all"></i> All products</a>
            @foreach($categories as $c)
              <a href="{{ route('store.index', ['tenant'=>$tenant,'category'=>$c->slug]) }}" class="{{ request('category')===$c->slug?'on':'' }}"><i class="fa-solid fa-tag"></i> {{ $c->name }}</a>
              @foreach($c->children as $sub)
                <div class="sub"><a href="{{ route('store.index', ['tenant'=>$tenant,'category'=>$sub->slug]) }}" class="{{ request('category')===$sub->slug?'on':'' }}"><i class="fa-solid fa-angle-right"></i> {{ $sub->name }}</a></div>
              @endforeach
            @endforeach
          </div>
        </div>
        <div class="fcard">
          <h5>Price <i class="fa-solid fa-sliders"></i></h5>
          <div class="price-in">
            <input type="number" name="min_price" placeholder="Min {{ (int) floor($priceBounds->min_p ?? 0) }}" value="{{ request('min_price') }}" min="0">
            <span style="color:var(--faint)">—</span>
            <input type="number" name="max_price" placeholder="Max {{ (int) ceil($priceBounds->max_p ?? 0) }}" value="{{ request('max_price') }}" min="0">
          </div>
          <label class="chk"><input type="checkbox" name="on_sale" value="1" {{ request('on_sale')?'checked':'' }} onchange="this.form.submit()"> On sale only</label>
          <button type="submit" class="btn-g" style="width:100%;margin-top:10px;padding:11px">Apply filters</button>
        </div>
      </form>
      <div class="side-promo"><span>Special offers</span><b>Grab the deals</b><span>on selected items</span><br><a href="{{ route('store.index', ['tenant'=>$tenant,'on_sale'=>1]) }}" style="display:inline-block;margin-top:12px;background:#fff;color:var(--p);padding:8px 14px;border-radius:9px;font-size:13px;font-weight:700">Shop deals</a><span class="e">⚡</span></div>
    </aside>

    <div>
      @php $hasFilters = request()->hasAny(['category','min_price','max_price','q','on_sale']); @endphp
      @if($hasFilters)
      <div class="chips">
        @if(request('q'))<a href="{{ route('store.index', ['tenant'=>$tenant] + request()->except(['q','page'])) }}">Search: "{{ request('q') }}" <i class="fa-solid fa-xmark"></i></a>@endif
        @if($selectedCategory)<a href="{{ route('store.index', ['tenant'=>$tenant] + request()->except(['category','page'])) }}">{{ $selectedCategory->name }} <i class="fa-solid fa-xmark"></i></a>@endif
        @if(request('min_price')||request('max_price'))<a href="{{ route('store.index', ['tenant'=>$tenant] + request()->except(['min_price','max_price','page'])) }}">{{ $curSymbol }}{{ request('min_price',0) }} – {{ request('max_price','∞') }} <i class="fa-solid fa-xmark"></i></a>@endif
        @if(request('on_sale'))<a href="{{ route('store.index', ['tenant'=>$tenant] + request()->except(['on_sale','page'])) }}">On sale <i class="fa-solid fa-xmark"></i></a>@endif
        <a href="{{ route('store.index', $tenant) }}" class="clear">Clear all</a>
      </div>
      @endif

      <div class="toolbar">
        <div class="cnt">Showing <b>{{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }}</b> of <b>{{ $products->total() }}</b> products</div>
        <div style="display:flex;gap:10px;align-items:center">
          <button type="button" class="btn-o filter-toggle" style="padding:8px 12px" onclick="document.getElementById('side').classList.toggle('open')"><i class="fa-solid fa-filter"></i> Filters</button>
          <form method="GET" action="{{ route('store.index', $tenant) }}">
            @foreach(request()->except(['sort','page']) as $k=>$v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <select name="sort" onchange="this.form.submit()">
              <option value="">Sort: Newest</option>
              <option value="price_asc" {{ request('sort')==='price_asc'?'selected':'' }}>Price: Low to High</option>
              <option value="price_desc" {{ request('sort')==='price_desc'?'selected':'' }}>Price: High to Low</option>
              <option value="name_asc" {{ request('sort')==='name_asc'?'selected':'' }}>Name A–Z</option>
              <option value="discount" {{ request('sort')==='discount'?'selected':'' }}>Biggest Discount</option>
            </select>
          </form>
        </div>
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
          <a href="{{ route('store.index', $tenant) }}" class="btn-g">Show all products</a>
        </div>
      @endif
    </div>
  </div>
</section>
@endsection
