@extends('layouts.admin.main')
@section('title', 'Opportunities')
@section('subtitle', 'CRM')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Opportunities" subtitle="Pipeline list view">
        <x-admin.button variant="light" icon="kanban" :href="route('crm.opportunities.index')">Board view</x-admin.button>
        @if ($canEdit)<x-admin.button icon="plus-lg" :href="route('crm.opportunities.create')">New opportunity</x-admin.button>@endif
    </x-admin.page-header>
    <x-admin.card flush>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Title</th><th>Company / Contact</th><th>Stage</th><th class="text-end">Amount</th><th class="text-center">Prob.</th><th>Close</th><th>Owner</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($rows as $o)
                <tr>
                    <td><a href="{{ route('crm.opportunities.show', $o) }}" class="fw-semibold text-decoration-none">{{ $o->title }}</a></td>
                    <td class="small">{{ $o->company->name ?? $o->contact->name ?? '—' }}</td>
                    <td><x-admin.badge :tone="$o->stageTone()">{{ $o->stageLabel() }}</x-admin.badge></td>
                    <td class="text-end">{{ number_format($o->amount, 0) }}</td>
                    <td class="text-center small">{{ $o->probability }}%</td>
                    <td class="small">{{ $o->close_date?->format('d M Y') ?? '—' }}</td>
                    <td class="small">{{ $o->owner->name ?? '—' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('crm.opportunities.show', $o) }}" class="btn btn-action"><i class="bi bi-eye"></i></a>
                        @if ($canEdit)<a href="{{ route('crm.opportunities.edit', $o) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><x-admin.empty icon="graph-up-arrow" text="No opportunities yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        <x-slot:footer>{{ $rows->links('pagination::bootstrap-5') }}</x-slot:footer>
    </x-admin.card>
</div>
@endsection
