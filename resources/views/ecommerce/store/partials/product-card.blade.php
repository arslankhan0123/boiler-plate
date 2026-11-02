@once
@push('styles')
<style>
.pcard{position:relative;background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:12px;display:flex;flex-direction:column;height:100%;transition:box-shadow .3s;z-index:1}
.pcard:hover{box-shadow:0 24px 50px -22px rgba(19,78,74,.35);z-index:3}
.pc-img{position:relative;display:block;aspect-ratio:4/3;border-radius:12px;overflow:hidden;background:radial-gradient(120% 90% at 50% 100%,#eef6f4,#fff 70%);margin-bottom:10px;transform-style:preserve-3d}
.pc-img img{width:100%;height:100%;object-fit:cover;border-radius:12px;display:block;transition:transform .6s cubic-bezier(.2,.7,.2,1);transform:translateZ(30px)}
.pcard:hover .pc-img img{transform:translateZ(40px) scale(1.08)}
.pc-ph{width:100%;height:100%;display:grid;place-items:center;font-size:42px;color:#bcd3cf}
.pc-badge{position:absolute;top:10px;left:10px;z-index:2;font-size:11px;font-weight:800;padding:4px 9px;border-radius:8px;color:#fff;background:var(--sale);box-shadow:0 6px 14px -6px rgba(249,115,22,.8)}
.pc-badge.new{background:var(--grad);box-shadow:0 6px 14px -6px rgba(15,118,110,.8)}
.pc-badge.oos{background:#9aa9a6;box-shadow:none}
.pc-quick{position:absolute;right:10px;top:10px;display:flex;flex-direction:column;gap:6px;z-index:2}
.pc-quick a{width:34px;height:34px;border-radius:10px;background:#fff;border:1px solid var(--line);display:grid;place-items:center;color:var(--ink-2);font-size:13px;opacity:0;transform:translateX(14px);transition:.3s;box-shadow:var(--shadow)}
.pcard:hover .pc-quick a{opacity:1;transform:none}
.pc-quick a:hover{background:var(--p);color:#fff;border-color:var(--p)}
.pc-oos{position:absolute;inset:0;background:rgba(255,255,255,.55);z-index:1}
.pc-body{display:flex;flex-direction:column;flex:1}
.pc-cat{font-size:10.5px;font-weight:700;color:var(--p);letter-spacing:.04em;text-transform:uppercase;margin-bottom:2px}
.pc-name{font-size:13.5px;font-weight:700;color:var(--ink);line-height:1.3;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;min-height:18px;margin-bottom:4px}
.pc-name:hover{color:var(--p)}
.pc-rate{display:flex;align-items:center;gap:6px;margin-bottom:2px}
.pc-rate small{font-size:11px;color:var(--faint)}
.pc-meta{font-size:11.5px;color:var(--muted);margin-bottom:8px;display:flex;align-items:center;gap:6px}
.pc-meta .dot{width:7px;height:7px;border-radius:50%;background:var(--ok);box-shadow:0 0 0 3px rgba(34,197,94,.18)}
.pc-meta .dot.low{background:var(--warn);box-shadow:0 0 0 3px rgba(246,166,9,.18)}
.pc-meta .dot.out{background:var(--faint);box-shadow:none}
.pc-foot{margin-top:auto;display:flex;align-items:center;justify-content:space-between;gap:8px}
.pc-price{display:flex;flex-direction:column;line-height:1.15}
.pc-price b{font-size:15px;font-weight:800;color:var(--ink)}
.pc-price s{font-size:12px;color:var(--faint)}
.pc-add{display:inline-flex;align-items:center;gap:6px;background:var(--p-soft);color:var(--p);border:0;border-radius:9px;padding:7px 11px;font-size:12.5px;font-weight:800;cursor:pointer;transition:.2s;white-space:nowrap}
.pc-add:hover{background:var(--grad);color:#fff;transform:translateY(-2px);box-shadow:0 10px 20px -10px rgba(15,118,110,.8)}
.pc-add:disabled{background:var(--surface);color:var(--faint);cursor:not-allowed;transform:none;box-shadow:none}
.pc-add i{font-size:11px}
</style>
@endpush
@endonce

@use('App\Support\Ecommerce\Money')
@php
  $qty = $product->stock();
  $service = (bool) ($product->item?->is_service);
  $inStock = $qty > 0 || $service;
  $compare = $product->compare_at_price;
  $onSale = $product->onSale();
  $disc = $onSale ? (int) round(($compare - $product->price) / max(0.01, $compare) * 100) : 0;
  $isNew = $product->created_at && $product->created_at->gt(now()->subDays(14));
  $reviews = (int) ($product->reviews_count ?? 0);
  $starsN = (int) round((float) ($product->reviews_avg ?? 0));
  $badge = ! $inStock ? 'oos' : ($onSale ? 'sale' : ($isNew ? 'new' : null));
@endphp

<div class="pcard" data-tilt="6">
  <a href="{{ route('store.product', ['tenant'=>$tenant,'slug'=>$product->slug]) }}" class="pc-img">
    @if($product->image_url)<img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
    @else<span class="pc-ph"><i class="fa-solid fa-box-open"></i></span>@endif
    @if(! $inStock)<span class="pc-oos"></span>@endif
    @if($badge === 'oos')<span class="pc-badge oos">SOLD OUT</span>
    @elseif($badge === 'sale')<span class="pc-badge">-{{ $disc }}%</span>
    @elseif($badge === 'new')<span class="pc-badge new">NEW</span>@endif
  </a>
  <div class="pc-quick">
    <a href="{{ route('store.product', ['tenant'=>$tenant,'slug'=>$product->slug]) }}" title="View"><i class="fa-regular fa-eye"></i></a>
  </div>
  <div class="pc-body">
    @if($product->category)<a href="{{ route('store.index', ['tenant'=>$tenant,'category'=>$product->category->slug]) }}" class="pc-cat">{{ $product->category->name }}</a>@endif
    <a href="{{ route('store.product', ['tenant'=>$tenant,'slug'=>$product->slug]) }}" class="pc-name">{{ $product->name }}</a>
    <div class="pc-rate">
      <span class="stars">@for($i=1;$i<=5;$i++)<i class="fa-solid fa-star {{ $i>$starsN?'off':'' }}"></i>@endfor</span>
      <small>({{ $reviews }})</small>
    </div>
    <div class="pc-meta">
      @if(! $inStock)<span class="dot out"></span> Out of stock
      @elseif(! $service && $qty <= 5)<span class="dot low"></span> Only {{ (int) $qty }} left
      @else<span class="dot"></span> In stock @endif
    </div>
    <div class="pc-foot">
      <div class="pc-price">
        <b>{{ Money::fmt($product->price) }}</b>
        @if($onSale)<s>{{ Money::fmt($compare) }}</s>@endif
      </div>
      <form action="{{ route('store.cart.add', $tenant) }}" method="POST" class="js-atc">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="qty" value="1">
        <button type="submit" class="pc-add" {{ $inStock ? '' : 'disabled' }}>Add <i class="fa-solid fa-plus"></i></button>
      </form>
    </div>
  </div>
</div>
