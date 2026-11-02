@extends('layouts.admin.main')
@section('title', $lead->name)
@section('subtitle', 'Lead')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$lead->name" :subtitle="$lead->company" :back="route('crm.leads.index')">
        @if ($canEdit && $lead->status !== 'converted')<x-admin.button variant="light" icon="pencil-square" :href="route('crm.leads.edit', $lead)">Edit</x-admin.button>@endif
    </x-admin.page-header>
    <div class="row g-4">
        <div class="col-lg-7">
            <x-admin.card title="Details">
                <dl class="row small mb-0">
                    <dt class="col-4 text-body-secondary">Status</dt><dd class="col-8"><x-admin.badge :tone="$lead->statusTone()">{{ $lead->statusLabel() }}</x-admin.badge></dd>
                    <dt class="col-4 text-body-secondary">Email</dt><dd class="col-8">{{ $lead->email ?: '—' }}</dd>
                    <dt class="col-4 text-body-secondary">Phone</dt><dd class="col-8">{{ $lead->phone ?: '—' }}</dd>
                    <dt class="col-4 text-body-secondary">Source</dt><dd class="col-8">{{ $lead->source ?: '—' }}</dd>
                    <dt class="col-4 text-body-secondary">Estimated value</dt><dd class="col-8">{{ number_format($lead->value, 2) }}</dd>
                    <dt class="col-4 text-body-secondary">Owner</dt><dd class="col-8">{{ $lead->owner->name ?? '—' }}</dd>
                    @if ($lead->notes)<dt class="col-4 text-body-secondary">Notes</dt><dd class="col-8">{{ $lead->notes }}</dd>@endif
                </dl>
            </x-admin.card>
        </div>
        <div class="col-lg-5">
            @if ($lead->status === 'converted')
                <x-admin.card title="Converted">
                    <p class="small text-body-secondary mb-2">This lead has been converted.</p>
                    @if ($opportunity)<x-admin.button variant="light" icon="graph-up-arrow" :href="route('crm.opportunities.show', $opportunity)">View opportunity</x-admin.button>@endif
                </x-admin.card>
            @elseif ($canPipeline)
                <x-admin.card title="Convert to opportunity">
                    <form method="POST" action="{{ route('crm.leads.convert', $lead) }}">@csrf
                        <x-admin.input name="title" label="Opportunity title" :value="$lead->company ?: $lead->name" required />
                        <div class="row g-2">
                            <div class="col-6"><x-admin.input name="amount" label="Amount" type="number" step="any" :value="$lead->value" /></div>
                            <div class="col-6"><x-admin.input name="close_date" label="Close date" type="date" /></div>
                        </div>
                        <div class="d-grid mt-2"><x-admin.button icon="arrow-right-circle">Convert lead</x-admin.button></div>
                    </form>
                </x-admin.card>
            @endif
        </div>
    </div>
</div>
@endsection
