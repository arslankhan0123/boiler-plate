@extends('layouts.admin.main')
@section('title', 'Stock Ledger')
@section('subtitle', 'Inventory')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Stock Ledger" subtitle="Every stock in and out — opening, purchases, sales, returns, adjustments, damage and transfers." :back="route('inventory.stock')" />
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:200px;min-height:40px" placeholder="Item, code, barcode…">
                <select name="warehouse" class="form-select" style="width:160px;min-height:40px" onchange="this.form.submit()"><option value="">All warehouses</option>@foreach ($warehouses as $id => $n)<option value="{{ $id }}" @selected(request('warehouse') == $id)>{{ $n }}</option>@endforeach</select>
                <select name="type" class="form-select" style="width:160px;min-height:40px" onchange="this.form.submit()"><option value="">All types</option>@foreach ($types as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach</select>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control" style="width:150px;min-height:40px" onchange="this.form.submit()">
                <input type="date" name="to" value="{{ request('to') }}" class="form-control" style="width:150px;min-height:40px" onchange="this.form.submit()">
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table">
            <thead><tr><th>Date</th><th>Item</th><th>Type</th><th>Warehouse</th><th>Note</th><th class="text-end">Qty</th></tr></thead>
            <tbody>
            @forelse ($rows as $m)
                <tr>
                    <td class="small text-nowrap">{{ $m->date->format('d M Y') }}</td>
                    <td><span class="fw-semibold">{{ $m->item?->displayName() ?? '—' }}</span><div class="small text-body-secondary">{{ $m->item->code ?? '' }}</div></td>
                    <td><x-admin.badge :tone="$m->qty >= 0 ? 'success' : 'danger'">{{ $types[$m->type] ?? $m->type }}</x-admin.badge></td>
                    <td class="small">{{ $m->warehouse->name ?? '—' }}</td>
                    <td class="small text-body-secondary">{{ $m->note }}</td>
                    <td class="text-end fw-bold {{ $m->qty >= 0 ? 'text-success' : 'text-danger' }}">{{ $m->qty > 0 ? '+' : '' }}{{ \App\Support\Inventory\StockService::fmt($m->qty) }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><x-admin.empty icon="clock-history" text="No stock movements." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($rows->hasPages())
            <x-slot:footer>{{ $rows->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
