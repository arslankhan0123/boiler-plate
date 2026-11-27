@extends('ecommerce.store.layout')
@section('title', 'Order Confirmed')

@use('App\Support\Ecommerce\Money')

@push('styles')
<style>
.cf{padding:40px 0 60px}
.cf-wrap{max-width:720px;margin:0 auto}
.cf-top{text-align:center;margin-bottom:28px}
.cf-tick{width:84px;height:84px;border-radius:50%;background:var(--grad);color:#fff;display:grid;place-items:center;font-size:38px;margin:0 auto 18px;box-shadow:0 20px 44px -16px rgba(15,118,110,.8);animation:floatY 5s ease-in-out infinite}
.cf-top h1{font-size:28px;font-weight:800;letter-spacing:-.6px;margin:0 0 6px}
.cf-top p{color:var(--muted);margin:0}
.cf-no{display:inline-flex;align-items:center;gap:8px;background:var(--p-soft);color:var(--p);font-weight:800;padding:8px 16px;border-radius:999px;margin-top:14px;font-size:15px}
.cf-card{background:#fff;border:1px solid var(--line);border-radius:var(--r-lg);padding:26px;margin-bottom:18px}
.cf-card h3{font-size:16px;font-weight:800;margin:0 0 16px;display:flex;align-items:center;gap:10px}
.cf-card h3 i{color:var(--p)}
.cf-row{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px dashed var(--line);font-size:14px}
.cf-row:last-child{border-bottom:0}
.cf-row .k{color:var(--muted)}.cf-row .v{font-weight:700;text-align:right}
.cf-item{display:flex;gap:12px;align-items:center;padding:10px 0;border-bottom:1px solid var(--surface)}
.cf-item:last-child{border-bottom:0}
.cf-item .im{width:48px;height:48px;border-radius:10px;background:var(--surface);display:grid;place-items:center;color:#c8d8d5;flex-shrink:0;position:relative}
.cf-item .q{position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;border-radius:9px;background:var(--ink);color:#fff;font-size:10px;font-weight:800;display:grid;place-items:center;padding:0 4px}
.cf-tot{display:flex;justify-content:space-between;align-items:center;padding-top:12px;margin-top:6px;border-top:2px solid var(--line)}
.cf-tot span{font-weight:800;font-size:16px}
.cf-tot b{font-size:24px;font-weight:800;background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.cf-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:8px}
</style>
@endpush

@section('content')
<section class="cf wrap">
  <div class="cf-wrap">
    <div class="cf-top">
      <div class="cf-tick"><i class="fa-solid fa-check"></i></div>
      <h1>Thank you{{ $order->customer_name ? ', '.$order->customer_name : '' }}!</h1>
      <p>Your order has been placed successfully. We'll be in touch soon.</p>
      <div class="cf-no"><i class="fa-solid fa-receipt"></i> Order {{ $order->order_no }}</div>
    </div>

    <div class="cf-card">
      <h3><i class="fa-solid fa-box"></i> Order details</h3>
      <div class="cf-row"><span class="k">Status</span><span class="v">{{ $order->statusLabel() }}</span></div>
      <div class="cf-row"><span class="k">Payment</span><span class="v">{{ $order->payment_method }}</span></div>
      @if($order->customer_phone)<div class="cf-row"><span class="k">Phone</span><span class="v">{{ $order->customer_phone }}</span></div>@endif
      @if($order->shipping_address)<div class="cf-row"><span class="k">Delivery to</span><span class="v">{{ $order->shipping_address }}</span></div>@endif
    </div>

    <div class="cf-card">
      <h3><i class="fa-solid fa-bag-shopping"></i> Items</h3>
      @foreach($order->items as $it)
      <div class="cf-item">
        <div class="im"><i class="fa-solid fa-box-open"></i><span class="q">{{ (int)$it->qty }}</span></div>
        <div style="flex:1;min-width:0"><div style="font-weight:700">{{ $it->name }}</div><small style="color:var(--muted)">{{ Money::fmt($it->price) }} × {{ (int)$it->qty }}</small></div>
        <div style="font-weight:800">{{ Money::fmt($it->line_total) }}</div>
      </div>
      @endforeach
      <div style="margin-top:14px">
        <div class="cf-row"><span class="k">Subtotal</span><span class="v">{{ Money::fmt($order->subtotal) }}</span></div>
        @if($order->tax > 0)<div class="cf-row"><span class="k">Tax</span><span class="v">{{ Money::fmt($order->tax) }}</span></div>@endif
        <div class="cf-row"><span class="k">Shipping</span><span class="v">{{ $order->shipping_cost>0?Money::fmt($order->shipping_cost):'Free' }}</span></div>
        @if($order->discount > 0)<div class="cf-row"><span class="k">Discount</span><span class="v" style="color:var(--ok)">− {{ Money::fmt($order->discount) }}</span></div>@endif
      </div>
      <div class="cf-tot"><span>Total</span><b>{{ Money::fmt($order->grand_total) }}</b></div>
    </div>

    <div class="cf-actions">
      <a href="{{ $surl('store.index', $tenant) }}" class="btn-g"><i class="fa-solid fa-bag-shopping"></i> Continue shopping</a>
      <a href="{{ $surl('store.index', $tenant) }}" class="btn-o"><i class="fa-solid fa-house"></i> Back to store</a>
    </div>
  </div>
</section>
@endsection
