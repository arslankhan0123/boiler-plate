@extends('ecommerce.store.layout')
@section('title', 'Cart')
@section('content')
    <h1 class="fw-bold mb-4">Your Cart</h1>
    @if (count($priced['rows']))
        <form method="POST" action="{{ route('store.cart.update', $tenant->id) }}">@csrf @method('PUT')
        <div class="s-card">
            <table class="table align-middle mb-0">
                <thead><tr><th class="ps-3">Product</th><th class="text-end">Price</th><th class="text-center">Qty</th><th class="text-end">Total</th><th></th></tr></thead>
                <tbody>
                @foreach ($priced['rows'] as $r)
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $r['name'] }}</td>
                        <td class="text-end">{{ $currency }} {{ number_format($r['price'], 2) }}</td>
                        <td class="text-center"><input type="number" name="qty[{{ $r['product_id'] }}]" value="{{ (int) $r['qty'] }}" min="1" class="form-control form-control-sm d-inline" style="width:70px"></td>
                        <td class="text-end">{{ number_format($r['line_total'], 2) }}</td>
                        <td class="text-end pe-3"><a href="#" onclick="event.preventDefault();document.getElementById('rm{{ $r['product_id'] }}').submit();" class="text-danger"><i class="bi bi-trash"></i></a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <button class="btn btn-light">Update cart</button>
            <div class="text-end">
                <div class="text-body-secondary small">Subtotal</div>
                <div class="h4 mb-2">{{ $currency }} {{ number_format($priced['subtotal'] + $priced['tax'], 2) }}</div>
                <a href="{{ route('store.checkout', $tenant->id) }}" class="btn btn-dark btn-lg"><i class="bi bi-credit-card me-1"></i>Checkout</a>
            </div>
        </div>
        </form>
        @foreach ($priced['rows'] as $r)
            <form method="POST" action="{{ route('store.cart.remove', [$tenant->id, $r['product_id']]) }}" id="rm{{ $r['product_id'] }}">@csrf @method('DELETE')</form>
        @endforeach
    @else
        <div class="text-center text-body-secondary py-5"><i class="bi bi-bag" style="font-size:2.5rem"></i><p class="mt-2">Your cart is empty.</p><a href="{{ route('store.index', $tenant->id) }}" class="btn btn-dark">Continue shopping</a></div>
    @endif
@endsection
