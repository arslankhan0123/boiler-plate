@extends('layouts.admin.main')
@section('title', $order->order_no)
@section('subtitle', 'Online order')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$order->order_no" :subtitle="$order->placed_at?->format('d M Y, H:i')" :back="route('ecommerce.orders.index')">
        @if ($canManage && !$order->isConverted())
            <form method="POST" action="{{ route('ecommerce.orders.convert', $order) }}" onsubmit="return confirm('Convert this order to a sales invoice? Stock will be deducted.')">@csrf<x-admin.button icon="receipt">Convert to sale</x-admin.button></form>
        @endif
    </x-admin.page-header>
    <div class="row g-4">
        <div class="col-lg-8"><x-admin.card title="Items">
            <div class="table-responsive"><table class="table app-table align-middle">
                <thead><tr><th>Product</th><th class="text-end">Price</th><th class="text-center">Qty</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                @foreach ($order->items as $i)
                    <tr><td>{{ $i->name }}</td><td class="text-end">{{ number_format($i->price, 2) }}</td><td class="text-center">{{ (int) $i->qty }}</td><td class="text-end">{{ number_format($i->line_total, 2) }}</td></tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end">{{ number_format($order->subtotal, 2) }}</td></tr>
                    @if ($order->tax > 0)<tr><td colspan="3" class="text-end">Tax</td><td class="text-end">{{ number_format($order->tax, 2) }}</td></tr>@endif
                    @if ($order->shipping_cost > 0)<tr><td colspan="3" class="text-end">Shipping</td><td class="text-end">{{ number_format($order->shipping_cost, 2) }}</td></tr>@endif
                    @if ($order->discount > 0)<tr><td colspan="3" class="text-end text-success">Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</td><td class="text-end text-success">− {{ number_format($order->discount, 2) }}</td></tr>@endif
                    <tr class="fw-bold"><td colspan="3" class="text-end">Grand total</td><td class="text-end">{{ number_format($order->grand_total, 2) }}</td></tr>
                </tfoot>
            </table></div>
            @if ($saleDoc)<p class="small text-success mb-0"><i class="bi bi-receipt me-1"></i>Converted to sales invoice <a href="{{ route('sales.invoices.show', $saleDoc) }}">{{ $saleDoc->number }}</a></p>@endif
        </x-admin.card></div>
        <div class="col-lg-4">
            <x-admin.card title="Customer" class="mb-4">
                <dl class="row small mb-0">
                    <dt class="col-4 text-body-secondary">Name</dt><dd class="col-8">{{ $order->customer_name }}</dd>
                    <dt class="col-4 text-body-secondary">Phone</dt><dd class="col-8">{{ $order->customer_phone ?: '—' }}</dd>
                    <dt class="col-4 text-body-secondary">Email</dt><dd class="col-8">{{ $order->customer_email ?: '—' }}</dd>
                    <dt class="col-4 text-body-secondary">Address</dt><dd class="col-8">{{ $order->shipping_address ?: '—' }}</dd>
                    <dt class="col-4 text-body-secondary">Payment</dt><dd class="col-8">{{ $order->payment_method }}</dd>
                </dl>
            </x-admin.card>
            @if ($canManage)
            <x-admin.card title="Update status">
                <form method="POST" action="{{ route('ecommerce.orders.status', $order) }}">@csrf @method('PATCH')
                    <x-admin.select name="status" label="Order status" :options="$statuses" :value="$order->status" />
                    <x-admin.select name="payment_status" label="Payment" :options="['unpaid' => 'Unpaid', 'paid' => 'Paid', 'refunded' => 'Refunded']" :value="$order->payment_status" />
                    <div class="d-grid mt-2"><x-admin.button icon="check-lg">Update</x-admin.button></div>
                </form>
            </x-admin.card>
            @endif
        </div>
    </div>
</div>
@endsection
