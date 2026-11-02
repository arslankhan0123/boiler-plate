@extends('layouts.admin.main')
@section('title', $doc->exists ? 'Edit transfer' : 'New transfer')
@section('subtitle', 'Inventory · Stock Transfers')

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$doc->exists ? 'Edit transfer' : 'New transfer'" subtitle="Stock leaves the source warehouse and arrives in the destination." :back="route('inventory.transfers.index')" />
    <form method="POST" action="{{ $doc->exists ? route('inventory.transfers.update', $doc) : route('inventory.transfers.store') }}" class="app-form-grid">@csrf @if ($doc->exists) @method('PUT') @endif
        <div>@include('inventory.partials.qty-lines', ['signed' => false, 'warehouseField' => 'f_from_warehouse_id'])</div>
        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <x-admin.input name="reference_no" label="Reference no." :value="$doc->reference_no" required />
                <x-admin.input name="date" type="date" label="Date" :value="$doc->date?->toDateString()" required />
                <x-admin.select name="from_warehouse_id" label="From warehouse" :options="$warehouses" :value="$doc->from_warehouse_id" required />
                <x-admin.select name="to_warehouse_id" label="To warehouse" :options="$warehouses" :value="$doc->to_warehouse_id" placeholder="Choose…" required />
                <div class="mb-3"><label class="form-label">Note <span class="opt">(optional)</span></label><textarea name="note" rows="3" class="form-control">{{ old('note', $doc->note) }}</textarea></div>
                <x-admin.button icon="arrow-left-right" class="w-100">{{ $doc->exists ? 'Save transfer' : 'Transfer stock' }}</x-admin.button>
            </div></div>
            <x-admin.tips :items="['You cannot transfer more than the source warehouse has.', 'Stock shown next to each item is for the source warehouse.']" />
        </div>
    </form>
</div>
@endsection
