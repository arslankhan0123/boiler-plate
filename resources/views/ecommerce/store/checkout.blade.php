@extends('ecommerce.store.layout')
@section('title', 'Checkout')
@section('content')
    <h1 class="fw-bold mb-4">Checkout</h1>
    <form method="POST" action="{{ route('store.place', $tenant->id) }}">@csrf
        <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="s-card p-4 mb-3">
                    <h5 class="fw-bold mb-3">Delivery details</h5>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Full name *</label><input name="customer_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Phone *</label><input name="customer_phone" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Email</label><input name="customer_email" type="email" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Shipping address *</label><textarea name="shipping_address" rows="2" class="form-control" required></textarea></div>
                    </div>
                </div>
                <div class="s-card p-4 mb-3">
                    <h5 class="fw-bold mb-3">Shipping</h5>
                    @forelse ($shippingMethods as $m)
                        <label class="form-check d-flex justify-content-between"><span><input type="radio" name="shipping_method_id" value="{{ $m->id }}" class="form-check-input me-2" @checked($loop->first)>{{ $m->name }}</span><span>{{ $m->costFor($priced['subtotal']) > 0 ? $currency.' '.number_format($m->costFor($priced['subtotal']), 2) : 'Free' }}</span></label>
                    @empty
                        <p class="small text-body-secondary mb-0">No shipping methods configured.</p>
                    @endforelse
                </div>
                <div class="s-card p-4">
                    <h5 class="fw-bold mb-3">Payment</h5>
                    @if ($cod)<label class="form-check"><input type="radio" name="payment_method" value="Cash on Delivery" class="form-check-input" checked> Cash on Delivery</label>@endif
                    @if ($bank)<label class="form-check"><input type="radio" name="payment_method" value="Bank Transfer" class="form-check-input"> Bank Transfer</label>@endif
                </div>
            </div>
            <div class="col-lg-5">
                <div class="s-card p-4">
                    <h5 class="fw-bold mb-3">Order summary</h5>
                    @foreach ($priced['rows'] as $r)
                        <div class="d-flex justify-content-between small py-1"><span>{{ $r['name'] }} × {{ (int) $r['qty'] }}</span><span>{{ number_format($r['line_total'], 2) }}</span></div>
                    @endforeach
                    <hr>
                    <div class="d-flex justify-content-between"><span>Subtotal</span><span>{{ number_format($priced['subtotal'], 2) }}</span></div>
                    @if ($priced['tax'] > 0)<div class="d-flex justify-content-between small text-body-secondary"><span>Tax</span><span>{{ number_format($priced['tax'], 2) }}</span></div>@endif
                    <div class="input-group input-group-sm my-2"><input name="coupon_code" class="form-control" placeholder="Coupon code"></div>
                    <div class="d-flex justify-content-between fw-bold h5 mt-2"><span>Total</span><span>{{ $currency }} {{ number_format($priced['subtotal'] + $priced['tax'], 2) }}+</span></div>
                    <p class="small text-body-secondary">Shipping & discount applied on the next step.</p>
                    <button class="btn btn-dark btn-lg w-100"><i class="bi bi-check2-circle me-1"></i>Place order</button>
                </div>
            </div>
        </div>
    </form>
@endsection
