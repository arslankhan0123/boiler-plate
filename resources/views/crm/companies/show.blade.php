@extends('layouts.admin.main')
@section('title', $company->name)
@section('subtitle', 'Company')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$company->name" :subtitle="$company->industry" :back="route('crm.companies.index')">
        @if ($canEdit)<x-admin.button variant="light" icon="person-plus" :href="route('crm.contacts.create', ['company_id' => $company->id])">Add contact</x-admin.button>
        <x-admin.button variant="light" icon="pencil-square" :href="route('crm.companies.edit', $company)">Edit</x-admin.button>@endif
    </x-admin.page-header>
    <div class="row g-4">
        <div class="col-lg-5"><x-admin.card title="Details">
            <dl class="row small mb-0">
                <dt class="col-4 text-body-secondary">Email</dt><dd class="col-8">{{ $company->email ?: '—' }}</dd>
                <dt class="col-4 text-body-secondary">Phone</dt><dd class="col-8">{{ $company->phone ?: '—' }}</dd>
                <dt class="col-4 text-body-secondary">Website</dt><dd class="col-8">{{ $company->website ?: '—' }}</dd>
                <dt class="col-4 text-body-secondary">City</dt><dd class="col-8">{{ $company->city ?: '—' }}</dd>
                <dt class="col-4 text-body-secondary">Owner</dt><dd class="col-8">{{ $company->owner->name ?? '—' }}</dd>
            </dl>
        </x-admin.card></div>
        <div class="col-lg-7">
            <x-admin.card title="Contacts" class="mb-4">
                @forelse ($company->contacts as $ct)
                    <div class="d-flex justify-content-between py-2 border-bottom"><div><strong>{{ $ct->name }}</strong> <span class="small text-body-secondary">{{ $ct->title }}</span></div><div class="small text-body-secondary">{{ $ct->phone ?: $ct->email }}</div></div>
                @empty<x-admin.empty icon="people" text="No contacts linked." />@endforelse
            </x-admin.card>
            <x-admin.card title="Opportunities">
                @forelse ($company->opportunities as $o)
                    <div class="d-flex justify-content-between py-2 border-bottom"><a href="{{ route('crm.opportunities.show', $o) }}" class="text-decoration-none">{{ $o->title }}</a><x-admin.badge :tone="$o->stageTone()">{{ $o->stageLabel() }}</x-admin.badge></div>
                @empty<x-admin.empty icon="graph-up" text="No opportunities." />@endforelse
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
