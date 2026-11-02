@extends('layouts.admin.main')
@php $c = $side === 'customer'; $r = $c ? 'sales.customers' : 'purchase.suppliers'; $editing = $party->exists; @endphp
@section('title', ($editing ? 'Edit ' : 'New ').($c ? 'customer' : 'supplier'))
@section('subtitle', $c ? 'Sales · Customers' : 'Purchase · Suppliers')

@section('content')
<div class="app-content">
    <x-admin.page-header :title="($editing ? 'Edit ' : 'New ').($c ? 'customer' : 'supplier')" :back="$editing ? route($r.'.show', $party) : route($r.'.index')" />
    <form method="POST" action="{{ $editing ? route($r.'.update', $party) : route($r.'.store') }}" class="app-form-grid">@csrf @if ($editing) @method('PUT') @endif
        <div class="app-card app-form-card">
            <div class="form-section-title">Basic</div>
            <div class="row g-4">
                <div class="col-md-8"><x-admin.input name="company_name" label="Name" :value="$party->company_name" required /></div>
                @if ($c)<div class="col-md-4"><x-admin.input name="code" label="Code" :value="$party->code" optional /></div>@endif
                <div class="col-md-6"><x-admin.input name="contact_person" label="Contact person" :value="$party->contact_person" optional /></div>
                @if ($c)<div class="col-md-6"><x-admin.input name="mobile" label="Mobile" :value="$party->mobile" optional /></div>@endif
                <div class="col-md-6"><x-admin.input name="phone" label="Phone" :value="$party->phone" optional /></div>
                <div class="col-md-6"><x-admin.input name="whatsapp" label="WhatsApp" :value="$party->whatsapp" optional /></div>
                <div class="col-md-6"><x-admin.input name="email" type="email" label="Email" :value="$party->email" optional /></div>
                <div class="col-md-6"><x-admin.input name="vat_number" label="Tax / NTN no." :value="$party->vat_number" optional /></div>
                @unless ($c)<div class="col-md-6"><x-admin.input name="website" label="Website" :value="$party->website" optional /></div>@endunless
            </div>
            <div class="form-section-title mt-5">Address</div>
            <div class="row g-4">
                <div class="col-md-12"><x-admin.input :name="$c ? 'address' : 'street'" label="Address" :value="$c ? $party->address : $party->street" optional /></div>
                <div class="col-md-6"><x-admin.input name="city" label="City" :value="$party->city" optional /></div>
                <div class="col-md-6"><x-admin.input name="zip" label="Postal code" :value="$party->zip" optional /></div>
                @if ($c)
                    <div class="col-md-12"><x-admin.input name="location_url" label="Location link (map)" :value="$party->location_url" optional /></div>
                    <div class="col-md-12"><x-admin.input name="ship_address" label="Shipping address" :value="$party->ship_address" optional hint="Leave empty if same as above." /></div>
                    <div class="col-md-6"><x-admin.input name="ship_city" label="Shipping city" :value="$party->ship_city" optional /></div>
                    <div class="col-md-6"><x-admin.input name="ship_zip" label="Shipping postal code" :value="$party->ship_zip" optional /></div>
                @endif
            </div>
        </div>
        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Balance</h3>
                <x-admin.input name="opening_balance" type="number" step="any" label="Opening balance" :value="$party->opening_balance ?: ''" optional :hint="$c ? 'What the customer already owes you.' : 'What you already owe the supplier.'" />
                @if ($c)
                    <x-admin.input name="credit_limit" type="number" step="any" min="0" label="Credit limit" :value="$party->credit_limit ?: ''" optional hint="0 = no limit. Credit sales above this are blocked." />
                    <div class="row g-2">
                        <div class="col-6"><x-admin.select name="price_level_type" label="Price level" :options="['increase' => 'Increase', 'decrease' => 'Decrease']" :value="$party->price_level_type" placeholder="None" optional /></div>
                        <div class="col-6"><x-admin.input name="price_level" type="number" step="any" min="0" max="100" label="%" :value="$party->price_level ?: ''" /></div>
                    </div>
                    <div class="form-text mb-3">Sales prices for this customer change by this %.</div>
                @endif
                <label class="d-flex gap-2 align-items-center small"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input mt-0" name="is_active" value="1" @checked(old('is_active', $party->is_active ?? true))> Active</label>
                <x-admin.button icon="check2" class="w-100 mt-4">Save</x-admin.button>
            </div></div>
        </div>
    </form>
</div>
@endsection
