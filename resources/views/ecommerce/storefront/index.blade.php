@extends('layouts.admin.main')
@section('title', 'Storefront')
@section('subtitle', 'E-commerce')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Storefront & Payments" subtitle="Your public online store settings" />
    <div class="row g-4">
        <div class="col-lg-8"><x-admin.card title="Store">
            <form method="POST" action="{{ route('ecommerce.storefront.update') }}">@csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6"><x-admin.input name="store_name" label="Store name" :value="$store['store_name']" required /></div>
                    <div class="col-md-6"><x-admin.input name="store_tagline" label="Tagline" :value="$store['store_tagline']" optional /></div>
                </div>
                <label class="form-check mt-3"><input type="checkbox" name="store_enabled" value="1" class="form-check-input" @checked($store['store_enabled'] === '1')> <span class="form-check-label">Store is open (publicly accessible)</span></label>
                <h3 class="app-aside-title mt-3">Payment methods</h3>
                <label class="form-check"><input type="checkbox" name="store_cod" value="1" class="form-check-input" @checked($store['store_cod'] === '1')> <span class="form-check-label">Cash on Delivery</span></label>
                <label class="form-check"><input type="checkbox" name="store_bank_transfer" value="1" class="form-check-input" @checked($store['store_bank_transfer'] === '1')> <span class="form-check-label">Bank Transfer</span></label>
                <div class="mt-3"><x-admin.button icon="check-lg">Save settings</x-admin.button></div>
            </form>
        </x-admin.card></div>
        <div class="col-lg-4"><x-admin.card title="Your store link">
            <p class="small text-body-secondary">Share this public link with customers:</p>
            <div class="input-group">
                <input type="text" readonly class="form-control" value="{{ url('/store/'.$tenantId) }}" id="storeUrl">
                <button class="btn btn-light" type="button" onclick="navigator.clipboard.writeText(document.getElementById('storeUrl').value)"><i class="bi bi-clipboard"></i></button>
            </div>
            <a href="{{ url('/store/'.$tenantId) }}" target="_blank" class="btn btn-primary w-100 mt-2"><i class="bi bi-box-arrow-up-right me-1"></i>Open store</a>
        </x-admin.card></div>
    </div>
</div>
@endsection
