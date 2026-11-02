@extends('layouts.admin.main')
@section('title', 'Items')
@section('subtitle', 'Inventory')
@php
    $cur = \App\Support\Settings::get('default_currency', 'PKR');
    $money = fn ($v) => $cur.' '.number_format((float) $v, 2);
    $q = fn ($v) => \App\Support\Inventory\StockService::fmt((float) $v);
@endphp

@section('content')
<div class="app-content">
    <x-admin.page-header title="Items & Services" subtitle="Your catalogue — prices, tax, stock per warehouse and variants.">
        <x-admin.button variant="light" icon="upc-scan" :href="route('inventory.labels')">Print Labels</x-admin.button>
        @if ($canEdit)
            <x-admin.button variant="light" icon="tools" :href="route('inventory.items.create', ['service' => 1])">New Service</x-admin.button>
            <x-admin.button icon="plus-lg" :href="route('inventory.items.create')">New Item</x-admin.button>
        @endif
    </x-admin.page-header>

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3"><x-admin.stat label="Items" :value="number_format($stats['items'])" icon="box-seam" /></div>
        <div class="col-sm-6 col-xl-3"><x-admin.stat label="Low stock" :value="number_format($stats['low'])" icon="exclamation-triangle" tone="warning" :href="route('inventory.items.index', ['stock' => 'low'])" /></div>
        <div class="col-sm-6 col-xl-3"><x-admin.stat label="Out of stock" :value="number_format($stats['out'])" icon="x-octagon" tone="danger" :href="route('inventory.items.index', ['stock' => 'out'])" /></div>
        <div class="col-sm-6 col-xl-3"><x-admin.stat label="Stock value (cost)" :value="$money($stats['value'])" icon="cash-stack" tone="success" /></div>
    </div>

    <form method="POST" action="{{ route('inventory.items.bulk') }}" id="bulkForm">@csrf</form>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:220px;min-height:40px" placeholder="Name, code, barcode, SKU…">
                <select name="category" class="form-select" style="width:150px;min-height:40px" onchange="this.form.submit()"><option value="">All categories</option>@foreach ($categories as $id => $n)<option value="{{ $id }}" @selected(request('category') == $id)>{{ $n }}</option>@endforeach</select>
                <select name="brand" class="form-select" style="width:140px;min-height:40px" onchange="this.form.submit()"><option value="">All brands</option>@foreach ($brands as $id => $n)<option value="{{ $id }}" @selected(request('brand') == $id)>{{ $n }}</option>@endforeach</select>
                <select name="stock" class="form-select" style="width:140px;min-height:40px" onchange="this.form.submit()">
                    <option value="">Any stock</option>
                    @foreach (['low' => 'Low stock', 'out' => 'Out of stock', 'expired' => 'Expired'] as $k => $l)<option value="{{ $k }}" @selected(request('stock') === $k)>{{ $l }}</option>@endforeach
                </select>
                <select name="kind" class="form-select" style="width:120px;min-height:40px" onchange="this.form.submit()"><option value="">Items & services</option><option value="item" @selected(request('kind') === 'item')>Items</option><option value="service" @selected(request('kind') === 'service')>Services</option></select>
                <select name="warehouse" class="form-select" style="width:150px;min-height:40px" onchange="this.form.submit()"><option value="">All warehouses</option>@foreach ($warehouses as $id => $n)<option value="{{ $id }}" @selected($wid == $id)>{{ $n }}</option>@endforeach</select>
                <select name="status" class="form-select" style="width:110px;min-height:40px" onchange="this.form.submit()"><option value="">Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option><option value="all" @selected(request('status') === 'all')>All</option></select>
            </form>
        </x-slot:actions>
        @if ($canEdit)
            <div class="bulk-bar px-4 py-2 d-none" id="bulkBar">
                <span class="small fw-semibold me-2"><span id="bulkCount">0</span> selected</span>
                <button form="bulkForm" name="action" value="activate" class="btn btn-sm btn-light">Activate</button>
                <button form="bulkForm" name="action" value="deactivate" class="btn btn-sm btn-light">Deactivate</button>
                <button form="bulkForm" name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete selected items? Items with transactions are set inactive instead.')">Delete</button>
            </div>
        @endif
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr>
                @if ($canEdit)<th style="width:36px"><input type="checkbox" class="form-check-input" id="checkAll"></th>@endif
                <th>Item</th><th>Category / Brand</th><th class="text-end">Cost</th><th class="text-end">Sale price</th><th>Tax</th><th class="text-end">Stock</th><th>Status</th><th class="text-end"></th>
            </tr></thead>
            <tbody>
            @forelse ($items as $i)
                @php $stock = $i->item_group === 'variant' ? $i->children->sum(fn ($c) => $c->stockIn($wid)) : $i->stockIn($wid); @endphp
                <tr>
                    @if ($canEdit)<td><input type="checkbox" class="form-check-input row-check" form="bulkForm" name="ids[]" value="{{ $i->id }}"></td>@endif
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div class="item-thumb">@if ($i->imageUrl())<img src="{{ $i->imageUrl() }}" alt="">@else<i class="bi bi-{{ $i->is_service ? 'tools' : 'box' }}"></i>@endif</div>
                            <div>
                                <a href="{{ route('inventory.items.show', $i) }}" class="fw-semibold text-decoration-none">{{ $i->name }}</a>
                                @if ($i->item_group === 'variant')<x-admin.badge tone="primary" icon="layers">{{ $i->children->count() }} variants</x-admin.badge>@endif
                                @if ($i->is_service)<x-admin.badge tone="muted">Service</x-admin.badge>@endif
                            </div>
                        </div>
                    </td>
                    <td class="small">{{ $i->category->name ?? '—' }}<div class="text-body-secondary">{{ $i->brand->name ?? '' }}</div></td>
                    <td class="text-end">{{ $money($i->purchase_price) }}</td>
                    <td class="text-end fw-semibold">{{ $money($i->sales_price) }}@if ($i->mrp)<div class="small text-body-secondary">MRP {{ $money($i->mrp) }}</div>@endif</td>
                    <td class="small">{{ $i->tax ? $i->tax->name.' '.rtrim(rtrim(number_format($i->tax->rate, 2), '0'), '.').'%' : '—' }}<div class="text-body-secondary">{{ ucfirst($i->tax_type) }}</div></td>
                    <td class="text-end">
                        @if ($i->is_service)<span class="text-body-secondary">—</span>
                        @else
                            <span class="fw-bold {{ $stock <= 0 ? 'text-danger' : ($i->alert_qty > 0 && $stock <= $i->alert_qty ? 'text-warning' : '') }}">{{ $q($stock) }}</span>
                            <span class="small text-body-secondary">{{ $i->unit->name ?? '' }}</span>
                            @if ($i->expire_date && $i->expire_date->isPast())<div><x-admin.badge tone="danger">Expired</x-admin.badge></div>@endif
                        @endif
                    </td>
                    <td><x-admin.badge :tone="$i->is_active ? 'success' : 'muted'">{{ $i->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('inventory.items.show', $i) }}" class="btn btn-action" title="View"><i class="bi bi-eye"></i></a>
                        @if ($canEdit)
                            <a href="{{ route('inventory.items.edit', $i) }}" class="btn btn-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                            <x-admin.delete-button :action="route('inventory.items.destroy', $i)" :confirm="'Delete '.$i->name.'? Items with transactions are set inactive instead.'" />
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9"><x-admin.empty icon="box-seam" text="No items found." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($items->hasPages())
            <x-slot:footer>{{ $items->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection

@push('styles')
<style>
    .item-thumb { width: 42px; height: 42px; border-radius: 10px; background: var(--app-hover); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; color: var(--app-text-muted); font-size: 1.1rem; }
    .item-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .bulk-bar { background: #eef2ff; border-bottom: 1px solid var(--app-border); }
</style>
@endpush

@push('scripts')
<script>
    (() => {
        const all = document.getElementById('checkAll'), bar = document.getElementById('bulkBar'), cnt = document.getElementById('bulkCount');
        const sync = () => { const n = document.querySelectorAll('.row-check:checked').length; if (bar) { bar.classList.toggle('d-none', !n); cnt.textContent = n; } };
        all?.addEventListener('change', () => { document.querySelectorAll('.row-check').forEach(c => c.checked = all.checked); sync(); });
        document.querySelectorAll('.row-check').forEach(c => c.addEventListener('change', sync));
    })();
</script>
@endpush
