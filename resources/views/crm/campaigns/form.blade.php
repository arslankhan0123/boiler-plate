@extends('layouts.admin.main')
@section('title', $campaign->exists ? 'Edit campaign' : 'New campaign')
@section('subtitle', 'CRM')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$campaign->exists ? 'Edit campaign' : 'New campaign'" :back="route('crm.campaigns.index')" />
    <form method="POST" action="{{ $campaign->exists ? route('crm.campaigns.update', $campaign) : route('crm.campaigns.store') }}">
        @csrf @if ($campaign->exists)@method('PUT')@endif
        <div class="row g-4">
            <div class="col-lg-8"><x-admin.card title="Campaign">
                <div class="row g-3">
                    <div class="col-12"><x-admin.input name="name" label="Name" :value="$campaign->name" required /></div>
                    <div class="col-md-6"><x-admin.select name="channel" label="Channel" :options="\App\Models\Crm\Campaign::CHANNELS" :value="$campaign->channel" required /></div>
                    <div class="col-md-6"><x-admin.select name="status" label="Status" :options="\App\Models\Crm\Campaign::STATUSES" :value="$campaign->status" required /></div>
                    <div class="col-md-4"><x-admin.input name="start_date" label="Start" type="date" :value="$campaign->start_date?->format('Y-m-d')" optional /></div>
                    <div class="col-md-4"><x-admin.input name="end_date" label="End" type="date" :value="$campaign->end_date?->format('Y-m-d')" optional /></div>
                    <div class="col-md-4"><x-admin.input name="budget" label="Budget" type="number" step="any" :value="$campaign->budget" /></div>
                    <div class="col-12"><label class="form-label">Notes <span class="opt">(optional)</span></label><textarea name="notes" rows="3" class="form-control">{{ old('notes', $campaign->notes) }}</textarea></div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <x-admin.button icon="check-lg">{{ $campaign->exists ? 'Save' : 'Create' }}</x-admin.button>
                    <x-admin.button variant="light" :href="route('crm.campaigns.index')" type="button">Cancel</x-admin.button>
                </div>
            </x-admin.card></div>
        </div>
    </form>
</div>
@endsection
