@extends('layouts.admin.main')
@section('title', $warehouse->name)
@section('subtitle', 'Inventory · Warehouse stock')
@php $money = fn ($v) => \App\Support\Settings::get('default_currency', 'PKR').' '.number_format((float) $v, 2); @endphp

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$warehouse->name" subtitle="Items available in this warehouse." :back="route('inventory.warehouses.index')">
        @can('inventory.transfer')<x-admin.button variant="light" icon="arrow-left-right" :href="route('inventory.transfers.create')">Transfer stock</x-admin.button>@endcan
    </x-admin.page-header>
    <x-admin.card flush>
        <x-slot:actions><form method="GET"><input name="q" value="{{ request('q') }}" class="form-control" style="width:260px;min-height:40px" placeholder="Search item, code or barcode…"></form></x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover">
            <thead><tr><th>Item</th><th>Category</th><th class="text-end">Qty</th><th class="text-end">Cost</th><th class="text-end">Value</th></tr></thead>
            <tbody>
            @forelse ($stocks as $s)
                <tr>
                    <td><a href="{{ route('inventory.items.show', $s->item->parent_id ?: $s->item) }}" class="fw-semibold text-decoration-none">{{ $s->item->displayName() }}</a><div class="small text-body-secondary">{{ $s->item->code }}</div></td>
                    <td>{{ $s->item->category->name ?? '—' }}</td>
                    <td class="text-end fw-bold {{ $s->qty < 0 ? 'text-danger' : '' }}">{{ \App\Support\Inventory\StockService::fmt($s->qty) }} <span class="small text-body-secondary fw-normal">{{ $s->item->unit->name ?? '' }}</span></td>
                    <td class="text-end">{{ $money($s->item->purchase_price) }}</td>
                    <td class="text-end fw-semibold">{{ $money($s->qty * $s->item->purchase_price) }}</td>
                </tr>
            @empty
                <tr><td colspan="5"><x-admin.empty icon="box-seam" text="No stock in this warehouse." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($stocks->hasPages())
            <x-slot:footer>{{ $stocks->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
