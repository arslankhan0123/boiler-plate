@extends('layouts.admin.main')
@section('title', 'Inventory Report')
@section('subtitle', 'Reports & Analytics')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Inventory" subtitle="Stock levels, valuation and where it sits" />

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-admin.stat label="Items" :value="number_format($itemCount)" icon="box-seam" tone="primary" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Units in stock" :value="number_format($totalQty)" icon="stack" tone="info" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Stock value (cost)" :value="$cur.' '.number_format($stockValue, 0)" icon="cash-coin" tone="success" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Retail value" :value="$cur.' '.number_format($retailValue, 0)" icon="tags" tone="primary" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Low stock (≤5)" :value="number_format($lowStock)" icon="exclamation-triangle" tone="warning" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Out of stock" :value="number_format($outOfStock)" icon="x-octagon" tone="danger" /></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7"><x-admin.card title="Top items by stock value">
            <table class="table app-table align-middle mb-0"><thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Value</th></tr></thead><tbody>
                @forelse ($topByValue as $r)
                    <tr><td>{{ $r->name }}@if($r->sku)<small class="text-body-secondary"> · {{ $r->sku }}</small>@endif</td><td class="text-center">{{ number_format($r->qty) }}</td><td class="text-end">{{ $cur }} {{ number_format($r->qty * $r->purchase_price, 0) }}</td></tr>
                @empty<tr><td colspan="3" class="text-body-secondary small">No stock records yet.</td></tr>@endforelse
            </tbody></table>
        </x-admin.card></div>
        <div class="col-lg-5"><x-admin.card title="Stock by warehouse">
            <table class="table app-table align-middle mb-0"><thead><tr><th>Warehouse</th><th class="text-center">Qty</th><th class="text-end">Value</th></tr></thead><tbody>
                @forelse ($byWarehouse as $w)
                    <tr><td>{{ $w->name }}</td><td class="text-center">{{ number_format($w->qty) }}</td><td class="text-end">{{ $cur }} {{ number_format($w->value, 0) }}</td></tr>
                @empty<tr><td colspan="3" class="text-body-secondary small">No warehouses yet.</td></tr>@endforelse
            </tbody></table>
        </x-admin.card></div>
    </div>
</div>
@endsection
