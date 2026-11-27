@extends('layouts.admin.main')
@php
    $cur = \App\Support\Settings::get('default_currency', 'PKR');
    $money = fn ($v) => $cur.' '.number_format((float) $v, 2);
    $q = fn ($v) => \App\Support\Inventory\StockService::fmt((float) $v);
    $rows = $item->item_group === 'variant' ? $item->children : collect([$item]);
    $attr = fn ($rel) => $item->{$rel}->name ?? null;
@endphp
@section('title', $item->name)
@section('subtitle', 'Inventory · Items')

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$item->name" :subtitle="$item->code.($item->is_service ? ' · Service' : '')" :back="route('inventory.items.index')">
        @unless ($item->is_service)<x-admin.button variant="light" icon="upc-scan" :href="route('inventory.labels', ['item' => $rows->pluck('id')->all()])">Labels</x-admin.button>@endunless
        @if ($canEdit)
            <form method="POST" action="{{ route('inventory.items.toggle', $item) }}" class="d-inline">@csrf @method('PATCH')
                <x-admin.button variant="light" :icon="$item->is_active ? 'pause-circle' : 'play-circle'">{{ $item->is_active ? 'Deactivate' : 'Activate' }}</x-admin.button></form>
            <x-admin.button icon="pencil-square" :href="route('inventory.items.edit', $item)">Edit</x-admin.button>
        @endif
    </x-admin.page-header>

    <div class="row g-4">
        <div class="col-xl-8 d-grid gap-4 align-content-start">
            <div class="app-card"><div class="app-card__body">
                <div class="d-flex gap-4 flex-wrap">
                    <div class="show-img">@if ($item->imageUrl())<img src="{{ $item->imageUrl() }}" alt="">@else<i class="bi bi-{{ $item->is_service ? 'tools' : 'box-seam' }}"></i>@endif</div>
                    <div class="flex-grow-1">
                        <div class="d-flex gap-2 flex-wrap mb-2">
                            <x-admin.badge :tone="$item->is_active ? 'success' : 'muted'">{{ $item->is_active ? 'Active' : 'Inactive' }}</x-admin.badge>
                            @if ($item->item_group === 'variant')<x-admin.badge tone="primary" icon="layers">{{ $item->children->count() }} variants</x-admin.badge>@endif
                            @if ($item->isLow())<x-admin.badge tone="warning" icon="exclamation-triangle">Low stock</x-admin.badge>@endif
                            @if ($item->expire_date && $item->expire_date->isPast())<x-admin.badge tone="danger">Expired {{ $item->expire_date->format('d M Y') }}</x-admin.badge>@endif
                        </div>
                        <div class="price-grid">
                            <div><span>Cost</span><b>{{ $money($item->price) }}</b></div>
                            <div><span>Purchase price</span><b>{{ $money($item->purchase_price) }}</b></div>
                            <div><span>Margin</span><b>{{ $item->profit_margin !== null ? rtrim(rtrim(number_format($item->profit_margin, 2), '0'), '.').'%' : '—' }}</b></div>
                            <div><span>Sales price</span><b class="text-success">{{ $money($item->sales_price) }}</b></div>
                            <div><span>MRP</span><b>{{ $item->mrp ? $money($item->mrp) : '—' }}</b></div>
                            <div><span>Tax</span><b>{{ $item->tax ? $item->tax->name.' '.rtrim(rtrim(number_format($item->tax->rate, 2), '0'), '.').'%' : 'None' }}</b><small>{{ ucfirst($item->tax_type) }}</small></div>
                            <div><span>Discount</span><b>{{ $item->discount > 0 ? ($item->discount_type === 'percentage' ? $q($item->discount).'%' : $money($item->discount)) : '—' }}</b></div>
                            @unless ($item->is_service)<div><span>Total stock</span><b>{{ $q($item->stock) }} {{ $item->unit->name ?? '' }}</b></div>@endunless
                        </div>
                    </div>
                </div>
            </div></div>

            @unless ($item->is_service)
                <x-admin.card flush :title="$item->item_group === 'variant' ? 'Variants & stock' : 'Stock by warehouse'">
                    <div class="table-responsive"><table class="table app-table">
                        <thead><tr>@if ($item->item_group === 'variant')<th>Variant</th><th>Code / Barcode</th><th class="text-end">Sales price</th>@endif<th>Warehouse stock</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                @if ($item->item_group === 'variant')
                                    <td class="fw-semibold">{{ $r->variant->name ?? '—' }} @unless ($r->is_active)<x-admin.badge tone="muted">Off</x-admin.badge>@endunless</td>
                                    <td class="small">{{ $r->code }}<div class="text-body-secondary">{{ $r->barcode }}</div></td>
                                    <td class="text-end">{{ $money($r->sales_price) }}</td>
                                @endif
                                <td class="small">
                                    @forelse ($r->stocks->where('qty', '!=', 0) as $s)<span class="wh-chip">{{ $s->warehouse->name ?? '#' }}: <b>{{ $q($s->qty) }}</b></span>@empty<span class="text-body-secondary">No stock</span>@endforelse
                                </td>
                                <td class="text-end fw-bold {{ $r->stock <= 0 ? 'text-danger' : '' }}">{{ $q($r->stock) }}</td>
                            </tr>
                        @endforeach
                        </tbody></table></div>
                </x-admin.card>

                <x-admin.card flush title="Stock history">
                    <x-slot:actions><a href="{{ route('inventory.stock.ledger', ['q' => $item->code]) }}" class="btn btn-sm btn-light">Full ledger</a></x-slot:actions>
                    <div class="table-responsive"><table class="table app-table">
                        <thead><tr><th>Date</th><th>Type</th>@if ($item->item_group === 'variant')<th>Variant</th>@endif<th>Warehouse</th><th>Note</th><th class="text-end">Qty</th></tr></thead>
                        <tbody>
                        @forelse ($movements as $m)
                            <tr>
                                <td class="small text-nowrap">{{ $m->date->format('d M Y') }}</td>
                                <td><x-admin.badge :tone="$m->qty >= 0 ? 'success' : 'danger'">{{ \App\Models\Inventory\StockMovement::TYPES[$m->type] ?? $m->type }}</x-admin.badge></td>
                                @if ($item->item_group === 'variant')<td class="small">{{ $m->item->variant->name ?? '' }}</td>@endif
                                <td class="small">{{ $m->warehouse->name ?? '—' }}</td>
                                <td class="small text-body-secondary">{{ $m->note }}</td>
                                <td class="text-end fw-bold {{ $m->qty >= 0 ? 'text-success' : 'text-danger' }}">{{ $m->qty > 0 ? '+' : '' }}{{ $q($m->qty) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-admin.empty icon="clock-history" text="No stock movements yet." /></td></tr>
                        @endforelse
                        </tbody></table></div>
                    @if ($movements->hasPages())
                        <x-slot:footer>{{ $movements->links('pagination::bootstrap-5') }}</x-slot:footer>
                    @endif
                </x-admin.card>
            @endunless
        </div>

        <div class="col-xl-4 d-grid gap-4 align-content-start">
            <x-admin.card title="Details">
                <dl class="detail-list">
                    @foreach ([
                        'Item code' => $item->code, 'Barcode' => $item->barcode, 'SKU' => $item->sku, 'HSN' => $item->hsn, 'Unit' => $attr('unit'),
                        'Department' => $attr('department'), 'Category' => $attr('category'), 'Sub category' => $attr('subcategory'), 'Brand' => $attr('brand'),
                        'Color' => $attr('color'), 'Size' => $attr('size'), 'Supplier' => $item->supplier->company_name ?? null,
                        'Rack' => $attr('rack'), 'Bin' => $attr('bin'), 'Sub bin' => $attr('sub_bin'),
                        'Alert qty' => $item->alert_qty > 0 ? $q($item->alert_qty) : null, 'Min / Max' => ($item->min_qty || $item->max_qty) ? $q($item->min_qty).' / '.$q($item->max_qty) : null,
                        'Reorder qty' => $item->reorder_qty ? $q($item->reorder_qty) : null, 'Lot no.' => $item->lot_number, 'Expiry' => $item->expire_date?->format('d M Y'),
                        'Seller points' => $item->seller_points > 0 ? $q($item->seller_points) : null, 'Opening stock' => $item->is_service ? null : $q($item->opening_stock),
                    ] as $k => $v)
                        @if ($v !== null && $v !== '')<dt>{{ $k }}</dt><dd>{{ $v }}</dd>@endif
                    @endforeach
                </dl>
                @if ($item->description)<p class="small text-body-secondary mt-3 mb-0" style="white-space:pre-line">{{ $item->description }}</p>@endif
            </x-admin.card>
            @if ($item->barcode && ! $item->is_service && $item->item_group === 'single')
                <x-admin.card title="Barcode"><div class="text-center"><svg id="itemBarcode"></svg></div></x-admin.card>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .show-img { width: 120px; height: 120px; border-radius: 16px; background: var(--app-hover); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
    .show-img img { width: 100%; height: 100%; object-fit: cover; } .show-img i { font-size: 2.4rem; color: var(--app-text-muted); }
    .price-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: .8rem; }
    .price-grid div { display: grid; padding: .6rem .8rem; border-radius: 10px; background: var(--app-hover); }
    .price-grid span { font-size: .72rem; color: var(--app-text-muted); text-transform: uppercase; letter-spacing: .03em; } .price-grid small { font-size: .72rem; color: var(--app-text-muted); }
    .detail-list { display: grid; grid-template-columns: auto 1fr; gap: .45rem 1rem; margin: 0; font-size: .875rem; }
    .detail-list dt { color: var(--app-text-muted); font-weight: 500; } .detail-list dd { margin: 0; font-weight: 600; text-align: right; }
    .wh-chip { display: inline-block; margin: 0 .35rem .25rem 0; padding: .1rem .5rem; border-radius: 6px; background: var(--app-hover); }
</style>
@endpush

@push('scripts')
@if ($item->barcode && ! $item->is_service && $item->item_group === 'single')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>try { JsBarcode('#itemBarcode', @js($item->barcode), { height: 60, fontSize: 14, margin: 6 }); } catch (e) {}</script>
@endif
@endpush
