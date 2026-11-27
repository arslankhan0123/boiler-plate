@extends('layouts.admin.main')
@section('title', 'Leads')
@section('subtitle', 'CRM')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Leads" subtitle="Capture and qualify new prospects">
        @if ($canEdit)<x-admin.button icon="plus-lg" :href="route('crm.leads.create')">New lead</x-admin.button>@endif
    </x-admin.page-header>

    <div class="row g-3 mb-3">
        @foreach ($statuses as $k => $label)
            <div class="col"><a href="{{ route('crm.leads.index', ['status' => $k]) }}" class="app-card d-block text-decoration-none">
                <div class="app-card__body py-3">
                    <div class="small text-body-secondary">{{ $label }}</div>
                    <div class="h4 mb-0">{{ $counts[$k] ?? 0 }}</div>
                </div></a></div>
        @endforeach
    </div>

    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:230px;min-height:40px" placeholder="Name, company, email, phone…">
                <select name="status" class="form-select" style="width:150px;min-height:40px" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach
                    <option value="deleted" @selected(request('status') === 'deleted')>Deleted</option>
                </select>
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Name</th><th>Company</th><th>Contact</th><th>Source</th><th class="text-end">Value</th><th>Status</th><th>Owner</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($leads as $l)
                <tr>
                    <td><a href="{{ $l->trashed() ? '#' : route('crm.leads.show', $l) }}" class="fw-semibold text-decoration-none">{{ $l->name }}</a></td>
                    <td class="small">{{ $l->company ?: '—' }}</td>
                    <td class="small">{{ $l->phone ?: $l->email ?: '—' }}</td>
                    <td class="small">{{ $l->source ?: '—' }}</td>
                    <td class="text-end">{{ number_format($l->value, 0) }}</td>
                    <td><x-admin.badge :tone="$l->statusTone()">{{ $l->statusLabel() }}</x-admin.badge></td>
                    <td class="small">{{ $l->owner->name ?? '—' }}</td>
                    <td class="text-end text-nowrap">
                        @if ($l->trashed())
                            @if ($canEdit)<form method="POST" action="{{ route('crm.leads.restore', $l->id) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Restore</button></form>@endif
                        @else
                            <a href="{{ route('crm.leads.show', $l) }}" class="btn btn-action"><i class="bi bi-eye"></i></a>
                            @if ($canEdit)<a href="{{ route('crm.leads.edit', $l) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                            <x-admin.delete-button :action="route('crm.leads.destroy', $l)" :confirm="'Delete '.$l->name.'?'" />@endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><x-admin.empty icon="person-plus" text="No leads yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        <x-slot:footer>{{ $leads->links('pagination::bootstrap-5') }}</x-slot:footer>
    </x-admin.card>
</div>
@endsection
