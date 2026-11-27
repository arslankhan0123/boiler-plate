@extends('layouts.admin.main')
@section('title', $contact->name)
@section('subtitle', 'History')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$contact->name" :subtitle="$company->name ?? null" :back="route('crm.history.index')" />
    <div class="row g-4">
        <div class="col-lg-4"><x-admin.card title="Contact">
            <dl class="row small mb-0">
                <dt class="col-4 text-body-secondary">Title</dt><dd class="col-8">{{ $contact->title ?: '—' }}</dd>
                <dt class="col-4 text-body-secondary">Email</dt><dd class="col-8">{{ $contact->email ?: '—' }}</dd>
                <dt class="col-4 text-body-secondary">Phone</dt><dd class="col-8">{{ $contact->phone ?: '—' }}</dd>
                <dt class="col-4 text-body-secondary">Owner</dt><dd class="col-8">{{ $contact->owner->name ?? '—' }}</dd>
            </dl>
        </x-admin.card></div>
        <div class="col-lg-8"><x-admin.card title="Timeline">
            @forelse ($timeline as $t)
                <div class="d-flex gap-3 py-2 border-bottom">
                    <div class="app-stat__icon tone-{{ $t['tone'] }}" style="width:38px;height:38px;flex:0 0 38px"><i class="bi bi-{{ $t['icon'] }}"></i></div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $t['title'] }}</div>
                        <div class="small text-body-secondary">{{ $t['meta'] }} · {{ $t['when']?->format('d M Y') }}</div>
                    </div>
                </div>
            @empty
                <x-admin.empty icon="clock-history" text="No history yet for this contact." />
            @endforelse
        </x-admin.card></div>
    </div>
</div>
@endsection
