@extends('ecommerce.store.layout')
@section('title', 'Order placed')
@section('content')
    <div class="s-card p-5 text-center" style="max-width:620px;margin:2rem auto">
        <div style="width:80px;height:80px;border-radius:50%;background:#dcfce7;color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:2.5rem;margin:0 auto 1rem"><i class="bi bi-check-lg"></i></div>
        <h1 class="fw-bold">Thank you!</h1>
        <p class="text-body-secondary">Your order <strong>{{ $order->order_no }}</strong> has been placed. We'll be in touch soon.</p>
        <div class="text-start s-card p-3 mt-3">
            @foreach ($order->items as $i)
                <div class="d-flex justify-content-between small py-1"><span>{{ $i->name }} × {{ (int) $i->qty }}</span><span>{{ number_format($i->line_total, 2) }}</span></div>
            @endforeach
            <hr>
            <div class="d-flex justify-content-between fw-bold"><span>Total</span><span>{{ $currency }} {{ number_format($order->grand_total, 2) }}</span></div>
        </div>
        <a href="{{ route('store.index', $tenant->id) }}" class="btn btn-dark mt-3">Continue shopping</a>
    </div>
@endsection
