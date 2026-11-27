@extends('layouts.admin.main')
@section('title', $opp->title)
@section('subtitle', 'Opportunity')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$opp->title" :back="route('crm.opportunities.index')">
        @if ($canEdit)<x-admin.button variant="light" icon="pencil-square" :href="route('crm.opportunities.edit', $opp)">Edit</x-admin.button>@endif
    </x-admin.page-header>
    <div class="row g-4">
        <div class="col-lg-7"><x-admin.card title="Details">
            <dl class="row small mb-0">
                <dt class="col-4 text-body-secondary">Stage</dt><dd class="col-8"><x-admin.badge :tone="$opp->stageTone()">{{ $opp->stageLabel() }}</x-admin.badge></dd>
                <dt class="col-4 text-body-secondary">Company</dt><dd class="col-8">{{ $opp->company->name ?? '—' }}</dd>
                <dt class="col-4 text-body-secondary">Contact</dt><dd class="col-8">{{ $opp->contact->name ?? '—' }}</dd>
                <dt class="col-4 text-body-secondary">Amount</dt><dd class="col-8">{{ number_format($opp->amount, 2) }}</dd>
                <dt class="col-4 text-body-secondary">Probability</dt><dd class="col-8">{{ $opp->probability }}% (weighted {{ number_format($opp->weighted(), 2) }})</dd>
                <dt class="col-4 text-body-secondary">Close date</dt><dd class="col-8">{{ $opp->close_date?->format('d M Y') ?? '—' }}</dd>
                <dt class="col-4 text-body-secondary">Owner</dt><dd class="col-8">{{ $opp->owner->name ?? '—' }}</dd>
                @if ($opp->lost_reason)<dt class="col-4 text-body-secondary">Lost reason</dt><dd class="col-8">{{ $opp->lost_reason }}</dd>@endif
            </dl>
        </x-admin.card></div>
        <div class="col-lg-5">
            @if ($canEdit && $opp->isOpen())
                <x-admin.card title="Move stage">
                    <div class="d-flex gap-2 flex-wrap">
                        <form method="POST" action="{{ route('crm.opportunities.stage', $opp) }}">@csrf @method('PATCH')<input type="hidden" name="stage" value="won"><button class="btn btn-success"><i class="bi bi-trophy me-1"></i>Mark Won</button></form>
                        <form method="POST" action="{{ route('crm.opportunities.stage', $opp) }}" onsubmit="this.lost_reason.value=prompt('Reason for losing?')||''">@csrf @method('PATCH')<input type="hidden" name="stage" value="lost"><input type="hidden" name="lost_reason"><button class="btn btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Mark Lost</button></form>
                    </div>
                </x-admin.card>
            @endif
        </div>
    </div>
    <div class="row g-4 mt-1">
        <div class="col-lg-8">
            @include('crm.partials.activities', ['activities' => $activities, 'canLog' => $canLog, 'relatedType' => 'opportunity', 'relatedId' => $opp->id])
        </div>
    </div>
</div>
@endsection
