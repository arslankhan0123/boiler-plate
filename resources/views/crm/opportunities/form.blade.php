@extends('layouts.admin.main')
@section('title', $opp->exists ? 'Edit opportunity' : 'New opportunity')
@section('subtitle', 'CRM')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$opp->exists ? 'Edit opportunity' : 'New opportunity'" :back="route('crm.opportunities.index')" />
    <form method="POST" action="{{ $opp->exists ? route('crm.opportunities.update', $opp) : route('crm.opportunities.store') }}">
        @csrf @if ($opp->exists)@method('PUT')@endif
        <div class="row g-4">
            <div class="col-lg-8"><x-admin.card title="Opportunity">
                <div class="row g-3">
                    <div class="col-12"><x-admin.input name="title" label="Title" :value="$opp->title" required /></div>
                    <div class="col-md-6"><x-admin.select name="company_id" label="Company" :options="$companies" :value="$opp->company_id" placeholder="— None —" /></div>
                    <div class="col-md-6"><x-admin.select name="contact_id" label="Contact" :options="$contacts" :value="$opp->contact_id" placeholder="— None —" /></div>
                    <div class="col-md-4"><x-admin.input name="amount" label="Amount" type="number" step="any" :value="$opp->amount" /></div>
                    <div class="col-md-4"><x-admin.input name="probability" label="Probability %" type="number" min="0" max="100" :value="$opp->probability" /></div>
                    <div class="col-md-4"><x-admin.input name="close_date" label="Close date" type="date" :value="$opp->close_date?->format('Y-m-d')" optional /></div>
                    <div class="col-md-6"><x-admin.input name="source" label="Source" :value="$opp->source" optional /></div>
                    <div class="col-12"><label class="form-label">Notes <span class="opt">(optional)</span></label><textarea name="notes" rows="3" class="form-control">{{ old('notes', $opp->notes) }}</textarea></div>
                </div>
            </x-admin.card></div>
            <div class="col-lg-4"><x-admin.card title="Stage & owner">
                <x-admin.select name="stage" label="Stage" :options="$stages" :value="$opp->stage" required />
                <x-admin.select name="owner_id" label="Owner" :options="$owners" :value="$opp->owner_id" placeholder="Unassigned" />
                <div class="d-grid gap-2 mt-3">
                    <x-admin.button icon="check-lg">{{ $opp->exists ? 'Save' : 'Create' }}</x-admin.button>
                    <x-admin.button variant="light" :href="route('crm.opportunities.index')" type="button">Cancel</x-admin.button>
                </div>
            </x-admin.card></div>
        </div>
    </form>
</div>
@endsection
