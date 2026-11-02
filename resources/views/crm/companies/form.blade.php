@extends('layouts.admin.main')
@section('title', $company->exists ? 'Edit company' : 'New company')
@section('subtitle', 'CRM')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$company->exists ? 'Edit company' : 'New company'" :back="route('crm.companies.index')" />
    <form method="POST" action="{{ $company->exists ? route('crm.companies.update', $company) : route('crm.companies.store') }}">
        @csrf @if ($company->exists)@method('PUT')@endif
        <div class="row g-4">
            <div class="col-lg-8"><x-admin.card title="Company details">
                <div class="row g-3">
                    <div class="col-md-6"><x-admin.input name="name" label="Company name" :value="$company->name" required /></div>
                    <div class="col-md-6"><x-admin.input name="industry" label="Industry" :value="$company->industry" optional /></div>
                    <div class="col-md-6"><x-admin.input name="email" label="Email" type="email" :value="$company->email" optional /></div>
                    <div class="col-md-6"><x-admin.input name="phone" label="Phone" :value="$company->phone" optional /></div>
                    <div class="col-md-6"><x-admin.input name="website" label="Website" :value="$company->website" optional /></div>
                    <div class="col-md-6"><x-admin.input name="city" label="City" :value="$company->city" optional /></div>
                    <div class="col-12"><x-admin.input name="address" label="Address" :value="$company->address" optional /></div>
                    <div class="col-12"><label class="form-label">Notes <span class="opt">(optional)</span></label><textarea name="notes" rows="3" class="form-control">{{ old('notes', $company->notes) }}</textarea></div>
                </div>
            </x-admin.card></div>
            <div class="col-lg-4"><x-admin.card title="Owner">
                <x-admin.select name="owner_id" label="Account manager" :options="$owners" :value="$company->owner_id" placeholder="Unassigned" />
                <div class="d-grid gap-2 mt-3">
                    <x-admin.button icon="check-lg">{{ $company->exists ? 'Save' : 'Create' }}</x-admin.button>
                    <x-admin.button variant="light" :href="route('crm.companies.index')" type="button">Cancel</x-admin.button>
                </div>
            </x-admin.card></div>
        </div>
    </form>
</div>
@endsection
