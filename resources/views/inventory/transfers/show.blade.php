@extends('layouts.admin.main')
@section('title', $doc->reference_no)
@section('subtitle', 'Inventory · Stock Transfers')

@section('content')
<div class="app-content">
    <x-admin.page-header :title="'Transfer '.$doc->reference_no" :subtitle="($doc->from->name ?? '').' → '.($doc->to->name ?? '').' · '.$doc->date->format('d M Y')" :back="route('inventory.transfers.index')">
        <x-admin.button variant="light" icon="printer" type="button" onclick="window.print()">Print</x-admin.button>
        @if ($canEdit)<x-admin.button icon="pencil-square" :href="route('inventory.transfers.edit', $doc)">Edit</x-admin.button>@endif
    </x-admin.page-header>
    <div class="row g-4">
        <div class="col-xl-8">
            <x-admin.card flush title="Items">
                <div class="table-responsive"><table class="table app-table">
                    <thead><tr><th>#</th><th>Item</th><th class="text-end">Qty</th></tr></thead>
                    <tbody>
                    @foreach ($doc->items as $i => $l)
                        <tr><td>{{ $i + 1 }}</td><td class="fw-semibold">{{ $l->item->displayName() }}<div class="small text-body-secondary fw-normal">{{ $l->item->code }}</div></td>
                            <td class="text-end fw-bold">{{ \App\Support\Inventory\StockService::fmt($l->qty) }} <span class="small fw-normal text-body-secondary">{{ $l->item->unit->name ?? '' }}</span></td></tr>
                    @endforeach
                    </tbody>
                    <tfoot><tr><th colspan="2" class="text-end">Total</th><th class="text-end">{{ \App\Support\Inventory\StockService::fmt($doc->items->sum('qty')) }}</th></tr></tfoot>
                </table></div>
            </x-admin.card>
        </div>
        <div class="col-xl-4">
            <x-admin.card title="Details">
                <dl class="row small mb-0">
                    <dt class="col-5 text-body-secondary">From</dt><dd class="col-7 text-end">{{ $doc->from->name ?? '—' }}</dd>
                    <dt class="col-5 text-body-secondary">To</dt><dd class="col-7 text-end">{{ $doc->to->name ?? '—' }}</dd>
                    <dt class="col-5 text-body-secondary">Date</dt><dd class="col-7 text-end">{{ $doc->date->format('d M Y') }}</dd>
                    <dt class="col-5 text-body-secondary">Created by</dt><dd class="col-7 text-end">{{ $by ?? '—' }}</dd>
                </dl>
                @if ($doc->note)<p class="small mt-3 mb-0" style="white-space:pre-line">{{ $doc->note }}</p>@endif
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
