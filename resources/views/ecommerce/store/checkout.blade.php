@extends('ecommerce.store.layout')
@section('title', 'Checkout')
@use('App\Support\Ecommerce\Money')

@php
  $rows = $totals['rows'];
  $subtotal = $totals['subtotal'];
  $tax = $totals['tax'];
  $firstShip = $shippingMethods->first();
  $ship0 = $totals['shipping'];
  $discount0 = $totals['discount'];
  $grand0 = $totals['grand'];
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
.payopt .brands{display:flex;gap:4px;margin-top:6px}
.payopt .brands span{font-size:10px;font-weight:800;border:1px solid var(--line);border-radius:5px;padding:1px 6px;color:var(--ink-2);background:#fff}
.testmode{display:inline-flex;align-items:center;gap:6px;background:#fff5e0;color:#b36b00;font-size:11px;font-weight:800;padding:3px 9px;border-radius:999px;margin-left:8px;vertical-align:middle}
.paybox{display:none;margin:-2px 0 14px;padding:18px;border:1.5px solid var(--p);border-top:0;border-radius:0 0 14px 14px;background:#fff}
.payopt.has-form:has(input:checked){border-radius:14px 14px 0 0;margin-bottom:0}
.payopt.has-form:has(input:checked)+.paybox{display:block}
.paybox .pe-load{display:flex;align-items:center;justify-content:center;gap:10px;color:var(--muted);font-size:13.5px;min-height:40px}
.paybox .pe-err{display:none;margin-top:12px;background:#fdecef;border:1px solid #ffc7d4;color:#b4124b;border-radius:12px;padding:10px 14px;font-size:13px}
.paybox .pe-note{display:flex;align-items:center;gap:8px;margin-top:12px;font-size:12px;color:var(--faint)}
.paybox .pe-note b{color:var(--ink-2)}
#paypalButtons{min-height:48px}
.bankbox{display:none;margin:-2px 0 14px;padding:16px 18px;border:1.5px solid var(--p);border-top:0;border-radius:0 0 14px 14px;background:#fff;font-size:13.5px;color:var(--ink-2);white-space:pre-line}
.payopt.has-bank:has(input:checked){border-radius:14px 14px 0 0;margin-bottom:0}
.payopt.has-bank:has(input:checked)+.bankbox{display:block}
.shipopt{display:flex;align-items:center;gap:14px;border:1.5px solid var(--line-2);border-radius:14px;padding:14px 16px;cursor:pointer;margin-bottom:10px;transition:.2s}
.shipopt:has(input:checked){border-color:var(--p);background:var(--p-soft)}
.shipopt input{accent-color:var(--p);width:18px;height:18px}
.shipopt .nm{font-weight:700}
.shipopt .cost{margin-left:auto;font-weight:800}
.sum{background:#fff;border:1px solid var(--line);border-radius:var(--r-lg);padding:24px;position:sticky;top:calc(var(--head-h) + 20px)}
@media(max-width:992px){.sum{position:static}}
.sum h3{font-size:17px;font-weight:800;margin:0 0 16px}
.sitems{max-height:240px;overflow:auto;display:flex;flex-direction:column;gap:12px;padding-right:4px}
.sitem{display:flex;gap:12px;align-items:center}
.sitem .im{width:52px;height:52px;border-radius:12px;background:var(--surface);display:grid;place-items:center;overflow:hidden;flex-shrink:0;position:relative}
.sitem .im img{width:100%;height:100%;object-fit:cover}
.sitem .im i{color:#c8d8d5}
.sitem .im .q{position:absolute;top:-6px;right:-6px;min-width:20px;height:20px;border-radius:10px;background:var(--ink);color:#fff;font-size:11px;font-weight:800;display:grid;place-items:center;padding:0 5px}
.sitem .nm{font-size:13.5px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.coupon2{display:flex;border:1.5px solid var(--line-2);border-radius:10px;overflow:hidden;margin-top:14px}
.coupon2 input{flex:1;border:0;padding:0 12px;height:44px;outline:0;min-width:0;font-size:14px}
.coupon2 button{border:0;background:var(--ink);color:#fff;font-weight:700;padding:0 16px;cursor:pointer}
.coupon-ok{display:none;align-items:center;gap:8px;margin-top:10px;font-size:13px;color:var(--ok);font-weight:700}
.coupon-ok a{color:var(--sale);font-weight:700;margin-left:auto;cursor:pointer}
.sum hr{border:0;border-top:1px dashed var(--line-2);margin:14px 0}
.sum .row2{display:flex;justify-content:space-between;font-size:14px;padding:6px 0;color:var(--muted)}
.sum .row2 b{color:var(--ink)}
.sum .row2.disc b{color:var(--ok)}
.sum .tot{display:flex;justify-content:space-between;align-items:center;padding-top:6px}
.sum .tot span{font-size:16px;font-weight:800}
.sum .tot b{font-size:26px;font-weight:800;letter-spacing:-1px;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.place{width:100%;height:54px;font-size:16px;border-radius:14px;margin-top:16px}
.secure{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:12px;font-size:12px;color:var(--faint)}
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

    @if(session('error'))<div class="alert-z"><i class="fa-solid fa-circle-exclamation"></i><div>{{ session('error') }}</div></div>@endif
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
              <label class="payopt {{ in_array($code,['card','paypal'])?'has-form':'' }} {{ $code==='bank'?'has-bank':'' }}">
                <input type="radio" name="payment_method" value="{{ $code }}" {{ $sel===$code?'checked':'' }} required>
                <span class="ic"><i class="{{ str_starts_with($m['icon'],'fa-brands')?$m['icon']:'fa-solid '.$m['icon'] }}"></i></span>
                <span><b>{{ $m['label'] }}@if($code==='card' && $stripeTest)<span class="testmode"><i class="fa-solid fa-flask"></i> Test mode</span>@endif</b><small>{{ $m['desc'] }}</small>
                  @if($code==='card')<span class="brands"><span>VISA</span><span>Mastercard</span><span>AMEX</span></span>@endif
                </span>
                @if(!empty($m['tag']))<span class="tag">{{ $m['tag'] }}</span>@endif
              </label>
              @if($code==='card')
                <div class="paybox" id="stripeBox">
                  <div class="pe-load" id="peLoad"><i class="fa-solid fa-spinner fa-spin"></i> Loading secure card form…</div>
                  <div id="paymentElement"></div>
                  <div class="pe-err" id="peErr"></div>
                  <div class="pe-note"><i class="fa-solid fa-lock" style="color:var(--ok)"></i> Encrypted by <b>Stripe</b> — your card never reaches our servers.</div>
                </div>
              @elseif($code==='paypal')
                <div class="paybox" id="paypalBox">
                  <div class="pe-load" id="ppLoad"><i class="fa-solid fa-spinner fa-spin"></i> Loading PayPal…</div>
                  <div id="paypalButtons"></div>
                  <div class="pe-err" id="ppErr"></div>
                  <div class="pe-note"><i class="fa-solid fa-lock" style="color:var(--ok)"></i> Fill in your details above first, then approve in the PayPal window.</div>
                </div>
              @elseif($code==='bank' && $bankDetails)
                <div class="bankbox"><b style="color:var(--ink)">Bank details</b><br>{{ $bankDetails }}</div>
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
            <input type="text" id="couponInput" name="coupon_code_display" value="{{ $totals['coupon'] }}" placeholder="Coupon code" autocomplete="off">
            <button type="button" id="couponBtn">Apply</button>
          </div>
          <div class="coupon-ok" id="couponOk" @if($totals['coupon'])style="display:flex"@endif><i class="fa-solid fa-circle-check"></i> Coupon <b style="margin:0 4px">{{ $totals['coupon'] }}</b> applied <a id="couponRemove">Remove</a></div>

          <hr>
          <div class="row2"><span>Subtotal</span><b>{{ Money::fmt($subtotal) }}</b></div>
          @if($tax > 0)<div class="row2"><span>Tax</span><b>{{ Money::fmt($tax) }}</b></div>@endif
          <div class="row2"><span>Shipping</span><b id="sumShip" style="color:{{ $ship0<=0?'var(--ok)':'var(--ink)' }}">{{ $ship0<=0?'Free':Money::fmt($ship0) }}</b></div>
          <div class="row2 disc" id="sumDiscRow" @if($discount0<=0)style="display:none"@endif><span>Discount</span><b id="sumDisc">− {{ Money::fmt($discount0) }}</b></div>
          <hr>
          <div class="tot"><span>Total</span><b id="sumTotal" data-sub="{{ $subtotal }}" data-tax="{{ $tax }}" data-disc="{{ $discount0 }}">{{ Money::fmt($grand0) }}</b></div>

          <button type="submit" class="btn-g place" id="placeBtn"><i class="fa-solid fa-lock"></i> <span id="placeTxt">Place Order</span></button>
          <div id="ppNote" style="display:none;margin-top:14px;padding:12px 14px;border-radius:12px;background:#fff7e6;border:1px solid #ffe3a3;color:#7a5200;font-size:13px;font-weight:600;text-align:center"><i class="fa-brands fa-paypal"></i> Complete your order with the PayPal button above.</div>
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
@if($stripeKey)<script src="https://js.stripe.com/v3/"></script>@endif
@if($paypalClient)<script src="https://www.paypal.com/sdk/js?client-id={{ urlencode($paypalClient) }}&currency={{ $paypalCurrency }}&intent=capture&components=buttons&disable-funding=card,credit,paylater" data-page-type="checkout"></script>@endif
<script>
(function(){
  const form=document.getElementById('coForm');
  const btn=document.getElementById('placeBtn');
  const token=document.querySelector('meta[name=csrf-token]').content;
  const STRIPE_KEY=@json($stripeKey);
  const PAYPAL_ON=@json((bool) $paypalClient);
  const INTENT_URL=@json(route('store.stripe.intent', $tenant));
  const COUPON_URL=@json(route('store.coupon', $tenant));
  const CAPTURE_URL=@json(route('store.paypal.capture', $tenant));
  const CANCEL_BASE=@json(url('store/'.$tenant->id.'/checkout/paypal/cancel'));
  const totalEl=document.getElementById('sumTotal');
  const sub=parseFloat(totalEl.dataset.sub)||0, tax=parseFloat(totalEl.dataset.tax)||0;
  let discount=parseFloat(totalEl.dataset.disc)||0;
  let busy=false, stripe=null, elements=null, mounted=false, mounting=false;
  let ppMounted=false, ppOrderNumber=null;

  const method=()=> (form.querySelector('input[name=payment_method]:checked')||{}).value;
  const shipCost=()=>{ const r=form.querySelector('input[name=shipping_method_id]:checked'); return r?(parseFloat(r.dataset.cost)||0):0; };
  const grand=()=> Math.max(0, sub+tax+shipCost()-discount);

  function renderTotals(){
    const ship=shipCost();
    const se=document.getElementById('sumShip');
    se.textContent = ship<=0?'Free':window.zmoney(ship); se.style.color = ship<=0?'var(--ok)':'var(--ink)';
    const dr=document.getElementById('sumDiscRow');
    if(discount>0){ dr.style.display='flex'; document.getElementById('sumDisc').textContent='− '+window.zmoney(discount); } else dr.style.display='none';
    totalEl.textContent=window.zmoney(grand());
  }

  // ── Coupon ──
  async function applyCoupon(code){
    const b=document.getElementById('couponBtn'); const old=b.textContent; b.disabled=true; b.textContent='…';
    try{
      const fd=new FormData(); fd.append('coupon_code', code);
      const r=await fetch(COUPON_URL,{method:'POST',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:fd});
      const j=await r.json().catch(()=>({}));
      if(!r.ok||!j.ok){ zToast(j.message||'That coupon is not valid.','err'); return; }
      discount=parseFloat(j.discount)||0;
      const ok=document.getElementById('couponOk');
      if(j.removed||discount<=0){ ok.style.display='none'; document.getElementById('couponInput').value=''; if(!j.removed) zToast('Coupon applied, but no discount for this cart','info'); }
      else { ok.style.display='flex'; ok.querySelector('b').textContent=j.coupon||code; zToast('Coupon applied!'); }
      renderTotals();
      if(method()==='card' && mounted){ mountStripe(true); } // refresh intent amount
    }catch(e){ zToast('Network error — please try again','err'); }
    finally{ b.disabled=false; b.textContent=old; }
  }
  document.getElementById('couponBtn').addEventListener('click',()=>applyCoupon(document.getElementById('couponInput').value.trim()));
  document.getElementById('couponRemove')?.addEventListener('click',()=>applyCoupon(''));
  document.getElementById('couponInput').addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); applyCoupon(e.target.value.trim()); } });

  // ── Shipping ──
  form.querySelectorAll('input[name=shipping_method_id]').forEach(r=>r.addEventListener('change',()=>{ renderTotals(); if(method()==='card'&&mounted) mountStripe(true); }));

  // ── Payment method switch ──
  const relabel=()=>{ const t=document.getElementById('placeTxt'); const m=method(); if(t) t.textContent = m==='card'?'Pay now':'Place Order'; btn.style.display = m==='paypal'?'none':''; document.getElementById('ppNote').style.display = m==='paypal'?'block':'none'; };
  const showErr=(id,msg)=>{ const e=document.getElementById(id); if(!e)return; e.textContent=msg||''; e.style.display=msg?'block':'none'; };

  // ── Stripe ──
  async function mountStripe(refresh){
    if(!STRIPE_KEY) return;
    if(refresh){ mounted=false; } else if(mounted||mounting) return;
    mounting=true; showErr('peErr','');
    try{
      const r=await fetch(INTENT_URL,{method:'POST',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
      const j=await r.json().catch(()=>({}));
      if(!r.ok){ throw new Error(j.message||'Could not start card payment.'); }
      if(!stripe) stripe=Stripe(STRIPE_KEY);
      const cs=getComputedStyle(document.documentElement);
      elements=stripe.elements({clientSecret:j.clientSecret,appearance:{theme:'stripe',variables:{colorPrimary:(cs.getPropertyValue('--p')||'#0f766e').trim(),borderRadius:'12px',fontFamily:'inherit'}}});
      const pe=elements.create('payment',{layout:'tabs'});
      pe.on('ready',()=>{ document.getElementById('peLoad').style.display='none'; mounted=true; });
      pe.on('loaderror',ev=>{ showErr('peErr',ev.error?.message||'Card form failed to load.'); document.getElementById('peLoad').style.display='none'; });
      document.getElementById('paymentElement').innerHTML='';
      pe.mount('#paymentElement');
    }catch(e){ const l=document.getElementById('peLoad'); if(l) l.innerHTML='<i class="fa-solid fa-triangle-exclamation" style="color:#b4124b"></i> '+e.message; }
    finally{ mounting=false; }
  }

  // ── PayPal ──
  function mountPayPal(){
    if(ppMounted||!PAYPAL_ON) return;
    if(!window.paypal){ const l=document.getElementById('ppLoad'); if(l) l.innerHTML='<i class="fa-solid fa-triangle-exclamation" style="color:#b4124b"></i> PayPal failed to load. Choose another method.'; return; }
    ppMounted=true;
    paypal.Buttons({
      style:{layout:'vertical',color:'gold',shape:'rect',label:'paypal',height:48},
      onInit:()=>{ document.getElementById('ppLoad').style.display='none'; },
      onClick:(d,actions)=>{ showErr('ppErr',''); if(!form.reportValidity()) return actions.reject(); return actions.resolve(); },
      createOrder:async()=>{
        const fd=new FormData(form); fd.set('payment_method','paypal');
        const r=await fetch(form.action,{method:'POST',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:fd});
        const j=await r.json().catch(()=>({}));
        if(!r.ok||!j.ok||!j.paypal_order_id){ const m=j.message||Object.values(j.errors||{}).flat()[0]||'Could not start PayPal.'; showErr('ppErr',m); throw new Error(m); }
        ppOrderNumber=j.order; return j.paypal_order_id;
      },
      onApprove:async(data)=>{
        showErr('ppErr',''); const box=document.getElementById('paypalBox'); box.style.opacity='.6';
        const fd=new FormData(); fd.append('order',ppOrderNumber); fd.append('paypal_order_id',data.orderID);
        const r=await fetch(CAPTURE_URL,{method:'POST',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:fd});
        const j=await r.json().catch(()=>({})); box.style.opacity='';
        if(!r.ok||!j.ok){ showErr('ppErr',j.message||'PayPal payment could not be completed.'); return; }
        location.href=j.redirect;
      },
      onCancel:()=>{ if(ppOrderNumber) fetch(CANCEL_BASE+'/'+ppOrderNumber,{headers:{'Accept':'application/json'}}).catch(()=>{}); ppOrderNumber=null; showErr('ppErr','PayPal payment was cancelled — nothing was charged.'); },
      onError:(err)=>{ console.error(err); if(!document.getElementById('ppErr').textContent) showErr('ppErr','PayPal ran into a problem. Please try again.'); }
    }).render('#paypalButtons');
  }

  form.querySelectorAll('input[name=payment_method]').forEach(r=>r.addEventListener('change',()=>{ relabel(); if(method()==='card') mountStripe(false); if(method()==='paypal') mountPayPal(); }));
  relabel(); renderTotals();
  if(method()==='card') mountStripe(false);
  if(method()==='paypal') mountPayPal();

  // ── Submit ──
  const setBusy=(on,label)=>{ busy=on; btn.disabled=on; btn.innerHTML = on ? '<i class="fa-solid fa-spinner fa-spin"></i> '+label : '<i class="fa-solid fa-lock"></i> <span id="placeTxt">'+(method()==='card'?'Pay now':'Place Order')+'</span>'; };

  form.addEventListener('submit',async function(ev){
    const m=method();
    if(m==='paypal'){ ev.preventDefault(); showErr('ppErr','Use the PayPal button above to pay.'); return; }
    if(m!=='card'){ setBusy(true,'Placing order…'); return; } // COD / bank → normal POST
    ev.preventDefault(); if(busy) return;
    if(!mounted||!stripe||!elements){ showErr('peErr','The card form is still loading — one moment.'); return; }
    showErr('peErr',''); setBusy(true,'Checking details…');
    try{
      const v=await elements.submit(); if(v.error){ throw new Error(v.error.message); }
      const fd=new FormData(form);
      const r=await fetch(form.action,{method:'POST',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:fd});
      const j=await r.json().catch(()=>({}));
      if(!r.ok||!j.ok){ throw new Error(j.message||Object.values(j.errors||{}).flat()[0]||'Could not place the order.'); }
      if(j.redirect){ location.href=j.redirect; return; }
      setBusy(true,'Confirming payment…');
      const {error}=await stripe.confirmPayment({elements,confirmParams:{return_url:j.return_url,payment_method_data:{billing_details:{name:fd.get('customer_name'),email:fd.get('customer_email')||undefined,phone:fd.get('customer_phone')}}}});
      if(error){ showErr('peErr',error.message||'Payment failed.'); setBusy(false); return; }
    }catch(e){ showErr('peErr',e.message); setBusy(false); }
  });
})();
</script>
@endpush
