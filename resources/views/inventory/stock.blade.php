@extends('layouts.admin.main')
@section('title', 'Stock Levels')
@section('subtitle', 'Inventory')
@php
    $cur = \App\Support\Settings::get('default_currency', 'PKR');
    $money = fn ($v) => $cur.' '.number_format((float) $v, 2);
    $q = fn ($v) => \App\Support\Inventory\StockService::fmt((float) $v);
@endphp

@section('content')
<div class="app-content">
    <x-admin.page-header title="Stock Levels" subtitle="Every item's quantity in every warehouse.">
        <x-admin.button variant="light" icon="clock-history" :href="route('inventory.stock.ledger')">Stock ledger</x-admin.button>
    </x-admin.page-header>
    <div class="row g-4 mb-4">
        <div class="col-md-4"><x-admin.stat label="Total quantity" :value="$q($totals['qty'])" icon="boxes" /></div>
        <div class="col-md-4"><x-admin.stat label="Value at cost" :value="$money($totals['cost'])" icon="cash-stack" tone="warning" /></div>
        <div class="col-md-4"><x-admin.stat label="Value at sale price" :value="$money($totals['sale'])" icon="graph-up-arrow" tone="success" /></div>
    </div>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:200px;min-height:40px" placeholder="Item, code, barcode…">
                <select name="category" class="form-select" style="width:150px;min-height:40px" onchange="this.form.submit()"><option value="">All categories</option>@foreach ($categories as $id => $n)<option value="{{ $id }}" @selected(request('category') == $id)>{{ $n }}</option>@endforeach</select>
                <select name="brand" class="form-select" style="width:140px;min-height:40px" onchange="this.form.submit()"><option value="">All brands</option>@foreach ($brands as $id => $n)<option value="{{ $id }}" @selected(request('brand') == $id)>{{ $n }}</option>@endforeach</select>
                <select name="only" class="form-select" style="width:160px;min-height:40px" onchange="this.form.submit()">
                    <option value="">All items</option>
                    @foreach (['in' => 'In stock', 'low' => 'Low (≤ alert qty)', 'reorder' => 'Needs reorder (≤ min)', 'out' => 'Out of stock'] as $k => $l)<option value="{{ $k }}" @selected(request('only') === $k)>{{ $l }}</option>@endforeach
                </select>
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover">
            <thead><tr><th>Item</th><th>Category</th>@foreach ($warehouses as $w)<th class="text-end">{{ $w->name }}</th>@endforeach<th class="text-end">Total</th><th class="text-end">Alert / Min</th><th class="text-end">Value</th></tr></thead>
            <tbody>
            @forelse ($items as $i)
                <tr>
                    <td><a href="{{ route('inventory.items.show', $i->parent_id ?: $i) }}" class="fw-semibold text-decoration-none">{{ $i->displayName() }}</a><div class="small text-body-secondary">{{ $i->code }}</div></td>
                    <td class="small">{{ $i->category->name ?? '—' }}</td>
                    @foreach ($warehouses as $w)@php $v = $i->stockIn($w->id); @endphp<td class="text-end {{ $v < 0 ? 'text-danger' : ($v == 0 ? 'text-body-secondary' : '') }}">{{ $q($v) }}</td>@endforeach
                    <td class="text-end fw-bold {{ $i->stock <= 0 ? 'text-danger' : ($i->isLow() ? 'text-warning' : '') }}">{{ $q($i->stock) }} <span class="small fw-normal text-body-secondary">{{ $i->unit->name ?? '' }}</span></td>
                    <td class="text-end small text-body-secondary">{{ $i->alert_qty > 0 ? $q($i->alert_qty) : '—' }} / {{ $i->min_qty ? $q($i->min_qty) : '—' }}</td>
                    <td class="text-end">{{ $money(max(0, $i->stock) * $i->purchase_price) }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 5 + $warehouses->count() }}"><x-admin.empty icon="boxes" text="No items match." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($items->hasPages())
            <x-slot:footer>{{ $items->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
