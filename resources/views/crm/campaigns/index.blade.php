@extends('layouts.admin.main')
@section('title', 'Campaigns')
@section('subtitle', 'CRM')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Campaigns" :subtitle="'Total budget: '.number_format($spend, 2)">
        @if ($canEdit)<x-admin.button icon="plus-lg" :href="route('crm.campaigns.create')">New campaign</x-admin.button>@endif
    </x-admin.page-header>
    <x-admin.card flush>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Name</th><th>Channel</th><th>Status</th><th>Dates</th><th class="text-end">Budget</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($campaigns as $c)
                <tr>
                    <td class="fw-semibold">{{ $c->name }}</td>
                    <td class="small">{{ $c->channelLabel() }}</td>
                    <td><x-admin.badge :tone="$c->statusTone()">{{ $c->statusLabel() }}</x-admin.badge></td>
                    <td class="small">{{ $c->start_date?->format('d M') ?? '—' }} – {{ $c->end_date?->format('d M Y') ?? '—' }}</td>
                    <td class="text-end">{{ number_format($c->budget, 0) }}</td>
                    <td class="text-end text-nowrap">
                        @if ($canEdit)<a href="{{ route('crm.campaigns.edit', $c) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                        <x-admin.delete-button :action="route('crm.campaigns.destroy', $c)" :confirm="'Delete '.$c->name.'?'" />@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-admin.empty icon="megaphone" text="No campaigns yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        <x-slot:footer>{{ $campaigns->links('pagination::bootstrap-5') }}</x-slot:footer>
    </x-admin.card>
</div>
@endsection
