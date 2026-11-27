@extends('layouts.admin.main')
@section('title', 'Pipeline')
@section('subtitle', 'CRM')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Pipeline" :subtitle="'Open value: '.number_format($openValue, 2)">
        <x-admin.button variant="light" icon="list-ul" :href="route('crm.opportunities.index', ['view' => 'list'])">List view</x-admin.button>
        @if ($canEdit)<x-admin.button icon="plus-lg" :href="route('crm.opportunities.create')">New opportunity</x-admin.button>@endif
    </x-admin.page-header>

    <div class="d-flex gap-3 overflow-auto pb-3" style="scroll-snap-type:x proximity">
        @foreach ($stages as $key => $label)
            <div style="min-width:280px;flex:1 0 280px;scroll-snap-align:start">
                <div class="app-card h-100">
                    <div class="app-card__header">
                        <div><h2 class="app-card__title" style="font-size:.95rem">{{ $label }}</h2>
                        <p class="app-card__subtitle mb-0">{{ $totals[$key]['count'] }} · {{ number_format($totals[$key]['amount'], 0) }}</p></div>
                    </div>
                    <div class="app-card__body d-grid gap-2" style="min-height:120px">
                        @forelse (($cards[$key] ?? collect()) as $o)
                            <div class="app-card" style="box-shadow:none;border:1px solid rgba(0,0,0,.08)">
                                <div class="app-card__body p-2">
                                    <a href="{{ route('crm.opportunities.show', $o) }}" class="fw-semibold text-decoration-none d-block">{{ $o->title }}</a>
                                    <div class="small text-body-secondary">{{ $o->company->name ?? $o->contact->name ?? '—' }}</div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="fw-bold">{{ number_format($o->amount, 0) }}</span>
                                        <span class="small text-body-secondary">{{ $o->probability }}%</span>
                                    </div>
                                    @if ($canEdit)
                                        <form method="POST" action="{{ route('crm.opportunities.stage', $o) }}" class="mt-2">@csrf @method('PATCH')
                                            <select name="stage" class="form-select form-select-sm" onchange="this.form.submit()">
                                                @foreach ($stages as $sk => $sl)<option value="{{ $sk }}" @selected($o->stage === $sk)>{{ $sl }}</option>@endforeach
                                            </select>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="small text-body-secondary text-center py-3">—</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
