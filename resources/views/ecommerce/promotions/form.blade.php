@extends('layouts.admin.main')
@section('title', $coupon->exists ? 'Edit coupon' : 'New coupon')
@section('subtitle', 'E-commerce')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$coupon->exists ? 'Edit coupon' : 'New coupon'" :back="route('ecommerce.promotions.index')" />
    <form method="POST" action="{{ $coupon->exists ? route('ecommerce.promotions.update', $coupon) : route('ecommerce.promotions.store') }}">
        @csrf @if ($coupon->exists)@method('PUT')@endif
        <div class="row g-4"><div class="col-lg-8"><x-admin.card title="Coupon">
            <div class="row g-3">
                <div class="col-md-6"><x-admin.input name="code" label="Code" :value="$coupon->code" required /></div>
                <div class="col-md-3"><x-admin.select name="type" label="Type" :options="['percent' => 'Percent', 'fixed' => 'Fixed']" :value="$coupon->type" required /></div>
                <div class="col-md-3"><x-admin.input name="value" label="Value" type="number" step="any" :value="$coupon->value" required /></div>
                <div class="col-md-4"><x-admin.input name="min_order" label="Min order" type="number" step="any" :value="$coupon->min_order" optional /></div>
                <div class="col-md-4"><x-admin.input name="starts_at" label="Starts" type="date" :value="$coupon->starts_at?->format('Y-m-d')" optional /></div>
                <div class="col-md-4"><x-admin.input name="ends_at" label="Ends" type="date" :value="$coupon->ends_at?->format('Y-m-d')" optional /></div>
                <div class="col-md-4"><x-admin.input name="usage_limit" label="Usage limit" type="number" :value="$coupon->usage_limit" optional /></div>
                <div class="col-12"><label class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($coupon->is_active ?? true)> <span class="form-check-label">Active</span></label></div>
            </div>
            <div class="d-flex gap-2 mt-3"><x-admin.button icon="check-lg">{{ $coupon->exists ? 'Save' : 'Create' }}</x-admin.button><x-admin.button variant="light" :href="route('ecommerce.promotions.index')" type="button">Cancel</x-admin.button></div>
        </x-admin.card></div></div>
    </form>
</div>
@endsection
