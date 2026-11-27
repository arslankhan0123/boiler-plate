@extends('layouts.admin.main')
@section('title', $doc->reference_no)
@section('subtitle', 'Inventory · '.$meta['title'])
@php $q = fn ($v) => \App\Support\Inventory\StockService::fmt((float) $v); @endphp

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$meta['one'].' '.$doc->reference_no" :subtitle="$doc->date->format('d M Y').' · '.($doc->warehouse->name ?? '')" :back="route($meta['route'].'.index')">
        <x-admin.button variant="light" icon="printer" type="button" onclick="window.print()">Print</x-admin.button>
        @if ($canEdit)<x-admin.button icon="pencil-square" :href="route($meta['route'].'.edit', $doc)">Edit</x-admin.button>@endif
    </x-admin.page-header>
    <div class="row g-4">
        <div class="col-xl-8">
            <x-admin.card flush title="Items">
                <div class="table-responsive"><table class="table app-table">
                    <thead><tr><th>#</th><th>Item</th><th>Note</th><th class="text-end">Qty</th></tr></thead>
                    <tbody>
                    @foreach ($doc->items as $i => $l)
                        <tr><td>{{ $i + 1 }}</td><td class="fw-semibold">{{ $l->item->displayName() }}<div class="small text-body-secondary fw-normal">{{ $l->item->code }}</div></td>
                            <td class="small text-body-secondary">{{ $l->description }}</td>
                            <td class="text-end fw-bold {{ $kind === 'damage' || $l->qty < 0 ? 'text-danger' : 'text-success' }}">{{ $kind === 'damage' ? '−' : ($l->qty > 0 ? '+' : '') }}{{ $q($l->qty) }} <span class="small fw-normal text-body-secondary">{{ $l->item->unit->name ?? '' }}</span></td></tr>
                    @endforeach
                    </tbody>
                </table></div>
            </x-admin.card>
        </div>
        <div class="col-xl-4">
            <x-admin.card title="Details">
                <dl class="row small mb-0">
                    <dt class="col-5 text-body-secondary">Reference</dt><dd class="col-7 text-end">{{ $doc->reference_no }}</dd>
                    <dt class="col-5 text-body-secondary">Date</dt><dd class="col-7 text-end">{{ $doc->date->format('d M Y') }}</dd>
                    <dt class="col-5 text-body-secondary">Warehouse</dt><dd class="col-7 text-end">{{ $doc->warehouse->name ?? '—' }}</dd>
                    <dt class="col-5 text-body-secondary">Items</dt><dd class="col-7 text-end">{{ $doc->items->count() }}</dd>
                    <dt class="col-5 text-body-secondary">Created by</dt><dd class="col-7 text-end">{{ $by ?? '—' }}</dd>
                </dl>
                @if ($doc->note)<p class="small mt-3 mb-0" style="white-space:pre-line">{{ $doc->note }}</p>@endif
                @if ($canEdit)<div class="mt-3"><x-admin.delete-button :action="route($meta['route'].'.destroy', $doc)" confirm="Delete this entry? Stock will be restored." /> <span class="small text-body-secondary">Delete & restore stock</span></div>@endif
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
