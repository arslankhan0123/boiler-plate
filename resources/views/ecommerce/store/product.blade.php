@extends('ecommerce.store.layout')
@section('title', $product->name)

@use('App\Support\Ecommerce\Money')
@php $compare=$product->compare_at_price; $onSale=$product->onSale(); $disc=$onSale?(int)round(($compare-$product->price)/max(0.01,$compare)*100):0; $qty=$product->stock(); $service=(bool)($product->item?->is_service); @endphp

@push('styles')
<style>
.pd{padding:26px 0 40px}
.pd-grid{display:grid;grid-template-columns:1fr 1fr;gap:38px;align-items:start}
@media(max-width:860px){.pd-grid{grid-template-columns:1fr;gap:22px}}
.pd-media{position:sticky;top:calc(var(--head-h) + 20px)}
@media(max-width:860px){.pd-media{position:static}}
.pd-img{position:relative;border:1px solid var(--line);border-radius:var(--r-lg);overflow:hidden;aspect-ratio:1;background:radial-gradient(120% 90% at 50% 100%,#eef6f4,#fff 70%);display:grid;place-items:center}
.pd-img img{width:100%;height:100%;object-fit:cover}
.pd-img .ph{font-size:90px;color:#cfe0dc}
.pd-img .badge-s{position:absolute;top:16px;left:16px;background:var(--sale);color:#fff;font-weight:800;font-size:13px;padding:6px 12px;border-radius:10px}
.pd-cat{font-size:12px;font-weight:800;color:var(--p);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px}
.pd-title{font-size:30px;font-weight:800;letter-spacing:-.6px;line-height:1.15;margin:0 0 12px}
.pd-rate{display:flex;align-items:center;gap:10px;margin-bottom:16px;color:var(--muted);font-size:13px}
.pd-price{display:flex;align-items:baseline;gap:12px;margin-bottom:16px}
.pd-price b{font-size:32px;font-weight:800;letter-spacing:-1px;color:var(--ink)}
.pd-price s{font-size:18px;color:var(--faint)}
.pd-price .off{background:var(--accent-soft);color:#9a6400;font-weight:800;font-size:13px;padding:3px 10px;border-radius:8px}
.pd-stock{display:inline-flex;align-items:center;gap:8px;font-size:13.5px;font-weight:700;margin-bottom:18px}
.pd-stock.ok{color:var(--ok)}.pd-stock.no{color:var(--sale)}
.pd-desc{color:var(--ink-2);font-size:14.5px;line-height:1.7;margin-bottom:22px;white-space:pre-line}
.pd-buy{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:18px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line);margin-bottom:22px}
.qty{display:inline-flex;align-items:center;border:1.5px solid var(--line-2);border-radius:12px;overflow:hidden;background:#fff}
.qty button{width:42px;height:48px;border:0;background:var(--surface);font-size:18px;cursor:pointer;color:var(--ink)}
.qty button:hover{background:var(--p-soft);color:var(--p)}
.qty input{width:54px;height:48px;border:0;text-align:center;font-weight:800;outline:0;font-size:15px;-moz-appearance:textfield}
.qty input::-webkit-outer-spin-button,.qty input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.pd-buy .btn-g{height:50px;padding:0 30px;font-size:15px}
.pd-trust{display:flex;gap:22px;flex-wrap:wrap;font-size:13px;color:var(--muted)}
.pd-trust div{display:flex;align-items:center;gap:8px}.pd-trust i{color:var(--p)}
/* reviews */
.rv{margin-top:44px}
.rv-head{display:flex;align-items:baseline;gap:14px;margin-bottom:20px}
.rv-head h2{font-size:22px;font-weight:800;margin:0}
.rv-list{display:flex;flex-direction:column;gap:14px;margin-bottom:28px}
.rv-item{background:#fff;border:1px solid var(--line);border-radius:var(--r);padding:16px 18px}
.rv-item .top{display:flex;align-items:center;gap:10px;margin-bottom:6px}
.rv-item .av{width:38px;height:38px;border-radius:50%;background:var(--grad);color:#fff;display:grid;place-items:center;font-weight:800}
.rv-item .nm{font-weight:700}.rv-item .dt{font-size:12px;color:var(--faint);margin-left:auto}
.rv-item .ti{font-weight:700;margin:4px 0 2px}
.rv-item p{margin:0;color:var(--ink-2);font-size:14px}
.rv-form{background:var(--surface);border-radius:var(--r-lg);padding:24px}
.rv-form h3{font-size:17px;font-weight:800;margin:0 0 16px}
.star-pick{display:inline-flex;flex-direction:row-reverse;gap:4px;font-size:26px}
.star-pick input{display:none}
.star-pick label{color:#d1e0dd;cursor:pointer;transition:.1s}
.star-pick label:hover,.star-pick label:hover~label,.star-pick input:checked~label{color:var(--star)}
.rel{margin-top:48px}
.grid{display:grid;grid-template-columns:repeat(5,1fr);gap:16px}
@media(max-width:1200px){.grid{grid-template-columns:repeat(4,1fr)}}
@media(max-width:860px){.grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:560px){.grid{grid-template-columns:repeat(2,1fr);gap:10px}}
</style>
@endpush

@section('content')
@include('ecommerce.store.partials.hero', [
  'crumbs' => array_values(array_filter([
    ['Home', $surl('store.index', $tenant)],
    ['Shop', $surl('store.index', $tenant)],
    $product->category ? [$product->category->name, $surl('store.index', ['tenant'=>$tenant,'category'=>$product->category->slug])] : null,
    [$product->name, null],
  ])),
  'title' => e($product->name),
  'text' => '',
])

<section class="pd wrap">
  <div class="pd-grid">
    <div class="pd-media">
      <div class="pd-img" data-tilt="5">
        @if($product->image_url)<img src="{{ $product->image_url }}" alt="{{ $product->name }}">@else<span class="ph"><i class="fa-solid fa-box-open"></i></span>@endif
        @if($onSale)<span class="badge-s">-{{ $disc }}%</span>@endif
      </div>
    </div>
    <div>
      @if($product->category)<div class="pd-cat">{{ $product->category->name }}</div>@endif
      <h1 class="pd-title">{{ $product->name }}</h1>
      <div class="pd-rate">
        <span class="stars">@for($i=1;$i<=5;$i++)<i class="fa-solid fa-star {{ $i>round($avg)?'off':'' }}"></i>@endfor</span>
        <span>{{ $avg ?: '0.0' }} · {{ $reviews->count() }} review{{ $reviews->count()===1?'':'s' }}</span>
      </div>
      <div class="pd-price">
        <b>{{ Money::fmt($product->price) }}</b>
        @if($onSale)<s>{{ Money::fmt($compare) }}</s><span class="off">Save {{ $disc }}%</span>@endif
      </div>
      @if($inStock)<div class="pd-stock ok"><i class="fa-solid fa-circle-check"></i> In stock @if(!$service && $qty<=5)— only {{ (int)$qty }} left @endif</div>
      @else<div class="pd-stock no"><i class="fa-solid fa-circle-xmark"></i> Out of stock</div>@endif

      @if($product->description)<div class="pd-desc">{{ $product->description }}</div>@endif

      <div class="pd-buy">
        <form action="{{ $surl('store.cart.add', $tenant) }}" method="POST" class="js-atc" style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
          @csrf
          <input type="hidden" name="product_id" value="{{ $product->id }}">
          <div class="qty">
            <button type="button" class="dec" aria-label="Decrease">−</button>
            <input type="number" name="qty" value="1" min="1" max="99" inputmode="numeric">
            <button type="button" class="inc" aria-label="Increase">+</button>
          </div>
          <button type="submit" class="btn-g" {{ $inStock?'':'disabled' }}><i class="fa-solid fa-cart-plus"></i> Add to Cart</button>
        </form>
      </div>

      <div class="pd-trust">
        <div><i class="fa-solid fa-truck-fast"></i> Fast delivery</div>
        <div><i class="fa-solid fa-money-bill-wave"></i> Cash on delivery</div>
        <div><i class="fa-solid fa-rotate-left"></i> Easy returns</div>
        <div><i class="fa-solid fa-shield-halved"></i> Secure checkout</div>
      </div>
    </div>
  </div>

  {{-- Reviews --}}
  <div class="rv">
    <div class="rv-head"><h2>Customer Reviews</h2><span class="stars" style="font-size:14px">@for($i=1;$i<=5;$i++)<i class="fa-solid fa-star {{ $i>round($avg)?'off':'' }}"></i>@endfor</span><span style="color:var(--muted);font-size:14px">{{ $avg ?: '0.0' }} out of 5</span></div>
    @if($reviews->count())
    <div class="rv-list">
      @foreach($reviews as $r)
      <div class="rv-item">
        <div class="top">
          <span class="av">{{ strtoupper(mb_substr($r->customer_name,0,1)) }}</span>
          <span class="nm">{{ $r->customer_name }}</span>
          <span class="stars">@for($i=1;$i<=5;$i++)<i class="fa-solid fa-star {{ $i>$r->rating?'off':'' }}"></i>@endfor</span>
          <span class="dt">{{ $r->created_at->diffForHumans() }}</span>
        </div>
        @if($r->title)<div class="ti">{{ $r->title }}</div>@endif
        @if($r->body)<p>{{ $r->body }}</p>@endif
      </div>
      @endforeach
    </div>
    @else
    <p style="color:var(--muted);margin-bottom:24px">No reviews yet — be the first to share your thoughts.</p>
    @endif

    <div class="rv-form">
      <h3>Write a review</h3>
      @if($errors->any())<div style="color:var(--sale);font-size:13px;margin-bottom:12px">{{ $errors->first() }}</div>@endif
      <form action="{{ $surl('store.review', ['tenant'=>$tenant,'product'=>$product->id]) }}" method="POST">
        @csrf
        <div class="row g-3">
          <div class="col-sm-6 fld"><label>Your name <b>*</b></label><input class="inp" name="customer_name" required value="{{ old('customer_name') }}"></div>
          <div class="col-sm-6 fld"><label>Rating <b>*</b></label>
            <div class="star-pick">
              @for($i=5;$i>=1;$i--)<input type="radio" name="rating" id="st{{ $i }}" value="{{ $i }}" {{ $i===5?'checked':'' }}><label for="st{{ $i }}"><i class="fa-solid fa-star"></i></label>@endfor
            </div>
          </div>
          <div class="col-12 fld"><label>Title</label><input class="inp" name="title" value="{{ old('title') }}" placeholder="Sum it up in a few words"></div>
          <div class="col-12 fld"><label>Review</label><textarea class="inp" name="body" rows="3" placeholder="What did you like or dislike?">{{ old('body') }}</textarea></div>
        </div>
        <button type="submit" class="btn-g" style="margin-top:14px"><i class="fa-solid fa-paper-plane"></i> Submit review</button>
      </form>
    </div>
  </div>

  {{-- Related --}}
  @if($related->count())
  <div class="rel">
    <div class="sec-head"><div><h2 class="sec-title">You may also like</h2><div class="sec-deco"><i></i><b></b></div></div></div>
    <div class="grid">
      @foreach($related as $i => $product)
        <div class="reveal" data-delay="{{ ($i%4)+1 }}">@include('ecommerce.store.partials.product-card', ['product'=>$product])</div>
      @endforeach
    </div>
  </div>
  @endif
</section>
@endsection

@push('scripts')
<script>
(function(){
  document.querySelectorAll('.pd-buy .qty').forEach(q=>{
    const inp=q.querySelector('input');
    q.querySelector('.dec').onclick=()=>inp.value=Math.max(1,(+inp.value||1)-1);
    q.querySelector('.inc').onclick=()=>inp.value=Math.min(99,(+inp.value||1)+1);
  });
})();
</script>
@endpush
