@extends('layouts.admin.main')
@section('title', 'New Stock Count')
@section('subtitle', 'Inventory')

@section('content')
<div class="app-content">
    <x-admin.page-header title="New Stock Count" subtitle="Pick the warehouse and which items to count. A sheet snapshots the current system stock." :back="route('inventory.counts.index')" />
    <div class="row justify-content-center"><div class="col-lg-7">
        <div class="app-card"><div class="app-card__body">
            <form method="POST" action="{{ route('inventory.counts.store') }}">@csrf
                <div class="row g-3">
                    <div class="col-md-6"><x-admin.select name="warehouse_id" label="Warehouse" :options="$warehouses" :value="$default" required /></div>
                    <div class="col-md-6"><x-admin.input name="date" type="date" label="Date" :value="today()->toDateString()" required /></div>
                </div>
                <label class="form-label mt-3">Which items?</label>
                <div class="d-grid gap-2">
                    <label class="d-flex gap-2 align-items-center border rounded px-3 py-2"><input type="radio" class="form-check-input mt-0" name="scope" value="all" checked> All items</label>
                    <label class="d-flex gap-2 align-items-center border rounded px-3 py-2"><input type="radio" class="form-check-input mt-0" name="scope" value="in_stock"> Only items currently in stock</label>
                    <label class="d-flex gap-2 align-items-center border rounded px-3 py-2"><input type="radio" class="form-check-input mt-0" name="scope" value="category"> By category
                        <select name="category_id" class="form-select form-select-sm ms-2" style="width:auto">@foreach ($categories as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></label>
                    <label class="d-flex gap-2 align-items-center border rounded px-3 py-2"><input type="radio" class="form-check-input mt-0" name="scope" value="brand"> By brand
                        <select name="brand_id" class="form-select form-select-sm ms-2" style="width:auto">@foreach ($brands as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></label>
                </div>
                <div class="mb-3 mt-3"><label class="form-label">Note <span class="opt">(optional)</span></label><textarea name="note" rows="2" class="form-control"></textarea></div>
                <x-admin.button icon="clipboard-plus" class="btn-lg-app w-100">Create count sheet</x-admin.button>
            </form>
        </div></div>
    </div></div>
</div>
@endsection
