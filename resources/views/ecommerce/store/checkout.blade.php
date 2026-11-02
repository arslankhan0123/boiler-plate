@extends('ecommerce.store.layout')
@section('title', 'Checkout')

@use('App\Support\Ecommerce\Money')
@php
  $rows = $priced['rows'];
  $subtotal = $priced['subtotal'];
  $tax = $priced['tax'];
  $itemsTotal = round($subtotal + $tax, 2);
  $firstShip = $shippingMethods->first();
  $ship0 = $firstShip ? (float) $firstShip->costFor($subtotal) : 0.0;
  $grand0 = $itemsTotal + $ship0;
@endphp

@push('styles')
<style>
.co{padding:30px 0 60px;background:var(--surface)}
.co-grid{display:grid;grid-template-columns:1fr 400px;gap:26px;align-items:start}
@media(max-width:992px){.co-grid{grid-template-columns:1fr}}
.steps{display:flex;align-items:center;gap:10px;margin-bottom:24px;flex-wrap:wrap}
.step{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:var(--faint)}
.step i{width:30px;height:30px;border-radius:50%;background:#fff;display:grid;place-items:center;font-size:12px;font-style:normal}
.step.on{color:var(--p)}.step.on i{background:var(--grad);color:#fff}
.step.done{color:var(--ok)}.step.done i{background:var(--ok);color:#fff}
.step-line{flex:1;max-width:70px;height:2px;background:var(--line-2)}
.card-z{background:#fff;border:1px solid var(--line);border-radius:var(--r-lg);padding:24px;margin-bottom:18px}
.card-z h3{font-size:17px;font-weight:800;margin:0 0 20px;display:flex;align-items:center;gap:10px}
.card-z h3 i{width:34px;height:34px;border-radius:10px;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:14px}
.alert-z{background:#fdecef;border:1px solid #ffc7d4;color:#b4124b;border-radius:14px;padding:14px 18px;font-size:14px;margin-bottom:18px;display:flex;gap:10px;align-items:flex-start}
.payopt{display:flex;align-items:flex-start;gap:14px;border:1.5px solid var(--line-2);border-radius:14px;padding:16px;cursor:pointer;transition:.2s;margin-bottom:10px;position:relative;background:#fff}
.payopt:has(input:checked){border-color:var(--p);background:var(--p-soft);box-shadow:var(--glow)}
.payopt input{accent-color:var(--p);margin-top:3px;width:18px;height:18px}
.payopt .ic{width:44px;height:44px;border-radius:12px;background:var(--surface);display:grid;place-items:center;color:var(--p);font-size:18px;flex-shrink:0}
.payopt:has(input:checked) .ic{background:var(--grad);color:#fff}
.payopt b{font-size:14.5px;display:block}
.payopt small{color:var(--muted);font-size:13px}
.payopt .tag{position:absolute;right:14px;top:14px;font-size:11px;font-weight:800;background:#e6f9f2;color:#0d8f6a;padding:3px 9px;border-radius:999px}
.bankbox{display:none;margin:-2px 0 14px;padding:16px 18px;border:1.5px solid var(--p);border-top:0;border-radius:0 0 14px 14px;background:#fff;font-size:13.5px;color:var(--ink-2);white-space:pre-line}
.shipopt{display:flex;align-items:center;gap:14px;border:1.5px solid var(--line-2);border-radius:14px;padding:14px 16px;cursor:pointer;margin-bottom:10px;transition:.2s}
.shipopt:has(input:checked){border-color:var(--p);background:var(--p-soft)}
.shipopt input{accent-color:var(--p);width:18px;height:18px}
.shipopt .nm{font-weight:700}
.shipopt .cost{margin-left:auto;font-weight:800}
.sum{background:#fff;border:1px solid var(--line);border-radius:var(--r-lg);padding:24px;position:sticky;top:calc(var(--head-h) + 20px)}
@media(max-width:992px){.sum{position:static}}
.sum h3{font-size:17px;font-weight:800;margin:0 0 16px}
.sitems{max-height:280px;overflow:auto;display:flex;flex-direction:column;gap:12px;padding-right:4px}
.sitem{display:flex;gap:12px;align-items:center}
.sitem .im{width:52px;height:52px;border-radius:12px;background:var(--surface);display:grid;place-items:center;overflow:hidden;flex-shrink:0;position:relative}
.sitem .im img{width:100%;height:100%;object-fit:cover}
.sitem .im i{color:#c8d8d5}
.sitem .im .q{position:absolute;top:-6px;right:-6px;min-width:20px;height:20px;border-radius:10px;background:var(--ink);color:#fff;font-size:11px;font-weight:800;display:grid;place-items:center;padding:0 5px}
.sitem .nm{font-size:13.5px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.sum hr{border:0;border-top:1px dashed var(--line-2);margin:14px 0}
.sum .row2{display:flex;justify-content:space-between;font-size:14px;padding:6px 0;color:var(--muted)}
.sum .row2 b{color:var(--ink)}
.sum .tot{display:flex;justify-content:space-between;align-items:center;padding-top:6px}
.sum .tot span{font-size:16px;font-weight:800}
.sum .tot b{font-size:26px;font-weight:800;letter-spacing:-1px;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.place{width:100%;height:54px;font-size:16px;border-radius:14px;margin-top:16px}
.secure{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:12px;font-size:12px;color:var(--faint)}
.coupon2{display:flex;border:1.5px solid var(--line-2);border-radius:10px;overflow:hidden;margin-top:14px}
.coupon2 input{flex:1;border:0;padding:0 12px;height:42px;outline:0;min-width:0}
</style>
@endpush

@section('content')
<section class="co">
  <div class="wrap">
    <div class="steps">
      <div class="step done"><i><i class="fa-solid fa-check"></i></i> Cart</div><span class="step-line"></span>
      <div class="step on"><i>2</i> Checkout</div><span class="step-line"></span>
      <div class="step"><i>3</i> Done</div>
    </div>

    @if($errors->any())<div class="alert-z"><i class="fa-solid fa-circle-exclamation"></i><div><b>Please fix the following:</b><ul style="margin:6px 0 0;padding-left:18px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div></div>@endif

    <form action="{{ route('store.place', $tenant) }}" method="POST" id="coForm">
      @csrf
      <input type="text" name="website" value="" style="position:absolute;left:-9999px" tabindex="-1" autocomplete="off" aria-hidden="true">
      <div class="co-grid">
        <div>
          <div class="card-z reveal">
            <h3><i class="fa-solid fa-user"></i> Contact &amp; Delivery</h3>
            <div class="row g-3">
              <div class="col-sm-6 fld"><label>Full name <b>*</b></label><input class="inp {{ $errors->has('customer_name')?'err':'' }}" name="customer_name" value="{{ old('customer_name') }}" required placeholder="Your full name"></div>
              <div class="col-sm-6 fld"><label>Phone <b>*</b></label><input class="inp {{ $errors->has('customer_phone')?'err':'' }}" type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required placeholder="03xx xxxxxxx"></div>
              <div class="col-12 fld"><label>Email <span style="color:var(--faint);font-weight:600">(optional)</span></label><input class="inp {{ $errors->has('customer_email')?'err':'' }}" type="email" name="customer_email" value="{{ old('customer_email') }}" placeholder="you@example.com"></div>
              <div class="col-12 fld"><label>Delivery address <b>*</b></label><textarea class="inp {{ $errors->has('shipping_address')?'err':'' }}" name="shipping_address" rows="3" required placeholder="House, street, area, city…">{{ old('shipping_address') }}</textarea></div>
              <div class="col-12 fld"><label>Order notes <span style="color:var(--faint);font-weight:600">(optional)</span></label><textarea class="inp" name="notes" rows="2" placeholder="Delivery instructions, landmark…">{{ old('notes') }}</textarea></div>
            </div>
          </div>

          @if($shippingMethods->count())
          <div class="card-z reveal" data-delay="1">
            <h3><i class="fa-solid fa-truck-fast"></i> Delivery Method</h3>
            @foreach($shippingMethods as $i => $sm)
              @php $c=(float)$sm->costFor($subtotal); @endphp
              <label class="shipopt">
                <input type="radio" name="shipping_method_id" value="{{ $sm->id }}" data-cost="{{ $c }}" {{ $i===0?'checked':'' }}>
                <span class="nm">{{ $sm->name }}</span>
                <span class="cost">{{ $c<=0?'Free':Money::fmt($c) }}</span>
              </label>
            @endforeach
          </div>
          @endif

          <div class="card-z reveal" data-delay="2">
            <h3><i class="fa-solid fa-wallet"></i> Payment Method</h3>
            @php $sel = old('payment_method', array_key_first($methods)); @endphp
            @foreach($methods as $code => $m)
              <label class="payopt">
                <input type="radio" name="payment_method" value="{{ $code }}" {{ $sel===$code?'checked':'' }} required>
                <span class="ic"><i class="fa-solid {{ $m['icon'] }}"></i></span>
                <span><b>{{ $m['label'] }}</b><small>{{ $m['desc'] }}</small></span>
                @if(!empty($m['tag']))<span class="tag">{{ $m['tag'] }}</span>@endif
              </label>
              @if($code==='bank' && $bankDetails)
                <div class="bankbox" id="bankBox"><b style="color:var(--ink)">Bank details</b><br>{{ $bankDetails }}</div>
              @endif
            @endforeach
          </div>
        </div>

        <aside class="sum reveal" data-delay="2">
          <h3>Order Summary</h3>
          <div class="sitems">
            @foreach($rows as $r)
            <div class="sitem">
              <div class="im">@if($r['image_url'])<img src="{{ $r['image_url'] }}" alt="">@else<i class="fa-solid fa-box-open"></i>@endif<span class="q">{{ (int)$r['qty'] }}</span></div>
              <div style="flex:1;min-width:0"><div class="nm">{{ $r['name'] }}</div><small style="color:var(--muted)">{{ Money::fmt($r['price']) }} × {{ (int)$r['qty'] }}</small></div>
              <div style="font-weight:800">{{ Money::fmt($r['line_total']) }}</div>
            </div>
            @endforeach
          </div>
          <div class="coupon2">
            <input name="coupon_code" value="{{ old('coupon_code') }}" placeholder="Coupon code (optional)">
          </div>
          <hr>
          <div class="row2"><span>Subtotal</span><b>{{ Money::fmt($subtotal) }}</b></div>
          @if($tax > 0)<div class="row2"><span>Tax</span><b>{{ Money::fmt($tax) }}</b></div>@endif
          <div class="row2"><span>Shipping</span><b id="sumShip" style="color:{{ $ship0<=0?'var(--ok)':'var(--ink)' }}">{{ $ship0<=0?'Free':Money::fmt($ship0) }}</b></div>
          <hr>
          <div class="tot"><span>Total</span><b id="sumTotal" data-items="{{ $itemsTotal }}">{{ Money::fmt($grand0) }}</b></div>
          <button type="submit" class="btn-g place"><i class="fa-solid fa-lock"></i> Place Order</button>
          <div class="secure"><i class="fa-solid fa-shield-halved"></i> Prices &amp; stock are re-verified on our server</div>
          <div style="display:flex;justify-content:center;margin-top:12px">@include('ecommerce.store.partials.payment-icons', ['size'=>'sm'])</div>
          <a href="{{ route('store.cart', $tenant) }}" style="display:block;text-align:center;margin-top:14px;font-size:13px;font-weight:700;color:var(--muted)"><i class="fa-solid fa-arrow-left"></i> Back to cart</a>
        </aside>
      </div>
    </form>
  </div>
</section>
@endsection

@push('scripts')
<script>
(function(){
  const form=document.getElementById('coForm');
  const itemsTotal=parseFloat(document.getElementById('sumTotal').dataset.items)||0;
  function recalc(){
    const sm=form.querySelector('input[name=shipping_method_id]:checked');
    const ship = sm ? (parseFloat(sm.dataset.cost)||0) : 0;
    const se=document.getElementById('sumShip');
    se.textContent = ship<=0 ? 'Free' : window.zmoney(ship);
    se.style.color = ship<=0 ? 'var(--ok)' : 'var(--ink)';
    document.getElementById('sumTotal').textContent = window.zmoney(itemsTotal+ship);
  }
  form.querySelectorAll('input[name=shipping_method_id]').forEach(r=>r.addEventListener('change',recalc));
  function togglePay(){
    const v=(form.querySelector('input[name=payment_method]:checked')||{}).value;
    const box=document.getElementById('bankBox'); if(box) box.style.display = v==='bank' ? 'block' : 'none';
  }
  form.querySelectorAll('input[name=payment_method]').forEach(r=>r.addEventListener('change',togglePay));
  recalc(); togglePay();
  form.addEventListener('submit',()=>{ const b=form.querySelector('.place'); b.disabled=true; b.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Placing order…'; });
})();
</script>
@endpush
