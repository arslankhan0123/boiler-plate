@extends('layouts.admin.main')
@section('title', 'Open Register')
@section('subtitle', 'POS')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Open Cash Register" subtitle="Count the cash you start the shift with (the float)." :back="route('pos.registers.index')" />
    <div class="row justify-content-center"><div class="col-lg-6">
        <div class="app-card"><div class="app-card__body">
            <form method="POST" action="{{ route('pos.registers.open.store') }}">@csrf
                <x-admin.select name="warehouse_id" label="Warehouse / counter" :options="$warehouses" :value="$default" required />
                <x-admin.input name="opening_cash" type="number" step="any" min="0" label="Opening cash (float)" value="0" required autofocus />
                <div class="mb-3"><label class="form-label">Note <span class="opt">(optional)</span></label><textarea name="open_note" rows="2" class="form-control"></textarea></div>
                <x-admin.button icon="unlock" class="btn-lg-app w-100">Open Register</x-admin.button>
            </form>
        </div></div>
    </div></div>
</div>
@endsection
