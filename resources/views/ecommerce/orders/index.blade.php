@extends('layouts.admin.main')
@section('title', 'Online Orders')
@section('subtitle', 'E-commerce')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Online Orders" :subtitle="'Revenue: '.number_format($revenue, 2)" />
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2">
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:200px;min-height:40px" placeholder="Order #, customer…">
                <select name="status" class="form-select" style="width:150px;min-height:40px" onchange="this.form.submit()"><option value="">All statuses</option>@foreach ($statuses as $k => $l)<option value="{{ $k }}" @selected(request('status')===$k)>{{ $l }} ({{ $counts[$k] ?? 0 }})</option>@endforeach</select>
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Order</th><th>Customer</th><th class="text-center">Items</th><th class="text-end">Total</th><th>Payment</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($orders as $o)
                <tr>
                    <td><a href="{{ route('ecommerce.orders.show', $o) }}" class="fw-semibold text-decoration-none">{{ $o->order_no }}</a><div class="small text-body-secondary">{{ $o->placed_at?->format('d M, H:i') }}</div></td>
                    <td class="small">{{ $o->customer_name }}<div class="text-body-secondary">{{ $o->customer_phone }}</div></td>
                    <td class="text-center">{{ $o->items_count }}</td>
                    <td class="text-end fw-semibold">{{ number_format($o->grand_total, 2) }}</td>
                    <td><x-admin.badge :tone="$o->payTone()">{{ ucfirst($o->payment_status) }}</x-admin.badge></td>
                    <td><x-admin.badge :tone="$o->statusTone()">{{ $o->statusLabel() }}</x-admin.badge>@if ($o->isConverted())<i class="bi bi-receipt text-success ms-1" title="Converted to sale"></i>@endif</td>
                    <td class="text-end"><a href="{{ route('ecommerce.orders.show', $o) }}" class="btn btn-action"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-admin.empty icon="cart" text="No orders yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($orders->hasPages())
        <x-slot:footer>{{ $orders->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
