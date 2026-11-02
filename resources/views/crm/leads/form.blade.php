@extends('layouts.admin.main')
@section('title', $lead->exists ? 'Edit lead' : 'New lead')
@section('subtitle', 'CRM')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$lead->exists ? 'Edit lead' : 'New lead'" :back="route('crm.leads.index')" />
    <form method="POST" action="{{ $lead->exists ? route('crm.leads.update', $lead) : route('crm.leads.store') }}">
        @csrf @if ($lead->exists)@method('PUT')@endif
        <div class="row g-4">
            <div class="col-lg-8">
                <x-admin.card title="Lead details">
                    <div class="row g-3">
                        <div class="col-md-6"><x-admin.input name="name" label="Name" :value="$lead->name" required /></div>
                        <div class="col-md-6"><x-admin.input name="company" label="Company" :value="$lead->company" optional /></div>
                        <div class="col-md-6"><x-admin.input name="email" label="Email" type="email" :value="$lead->email" optional /></div>
                        <div class="col-md-6"><x-admin.input name="phone" label="Phone" :value="$lead->phone" optional /></div>
                        <div class="col-md-6"><x-admin.input name="source" label="Source" :value="$lead->source" optional hint="website, referral, call, ad…" /></div>
                        <div class="col-md-6"><x-admin.input name="value" label="Estimated value" type="number" step="any" :value="$lead->value" optional /></div>
                        <div class="col-12"><label class="form-label">Notes <span class="opt">(optional)</span></label><textarea name="notes" rows="3" class="form-control">{{ old('notes', $lead->notes) }}</textarea></div>
                    </div>
                </x-admin.card>
            </div>
            <div class="col-lg-4">
                <x-admin.card title="Assignment">
                    <x-admin.select name="status" label="Status" :options="\App\Models\Crm\Lead::STATUSES" :value="$lead->status" required />
                    <x-admin.select name="owner_id" label="Owner" :options="$owners" :value="$lead->owner_id" placeholder="Unassigned" />
                    <div class="d-grid gap-2 mt-3">
                        <x-admin.button icon="check-lg">{{ $lead->exists ? 'Save lead' : 'Create lead' }}</x-admin.button>
                        <x-admin.button variant="light" :href="route('crm.leads.index')" type="button">Cancel</x-admin.button>
                    </div>
                </x-admin.card>
            </div>
        </div>
    </form>
</div>
@endsection
