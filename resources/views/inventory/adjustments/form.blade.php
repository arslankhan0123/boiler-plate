@extends('layouts.admin.main')
@section('title', ($doc->exists ? 'Edit ' : 'New ').strtolower($meta['one']))
@section('subtitle', 'Inventory · '.$meta['title'])

@section('content')
<div class="app-content">
    <x-admin.page-header :title="($doc->exists ? 'Edit ' : 'New ').strtolower($meta['one'])" :subtitle="$kind === 'damage' ? 'These quantities will be removed from stock.' : 'Positive qty adds stock, negative removes it.'" :back="route($meta['route'].'.index')" />
    <form method="POST" action="{{ $doc->exists ? route($meta['route'].'.update', $doc) : route($meta['route'].'.store') }}" class="app-form-grid">@csrf @if ($doc->exists) @method('PUT') @endif
        <div>@include('inventory.partials.qty-lines', ['signed' => $kind === 'adjustment', 'warehouseField' => 'f_warehouse_id', 'showDesc' => true])</div>
        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <x-admin.input name="reference_no" label="Reference no." :value="$doc->reference_no" required />
                <x-admin.input name="date" type="date" label="Date" :value="$doc->date?->toDateString()" required />
                <x-admin.select name="warehouse_id" label="Warehouse" :options="$warehouses" :value="$doc->warehouse_id" required />
                <div class="mb-3"><label class="form-label">Note <span class="opt">(optional)</span></label><textarea name="note" rows="3" class="form-control">{{ old('note', $doc->note) }}</textarea></div>
                <x-admin.button icon="check2" class="w-100">Save</x-admin.button>
            </div></div>
            <x-admin.tips :items="$kind === 'damage' ? ['Damaged stock is removed from the selected warehouse.', 'Deleting the entry puts the stock back.'] : ['Use + to add stock found in a count, − to remove missing stock.', 'Editing or deleting reverses the old quantities first.']" />
        </div>
    </form>
</div>
@endsection
