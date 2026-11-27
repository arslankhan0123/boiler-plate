@extends('layouts.admin.main')
@section('title', 'Chart of Accounts')
@section('subtitle', 'Accounting')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Chart of Accounts" subtitle="Your ledger accounts">
        @if ($canEdit)
            @if ($accounts->isEmpty())
                <form method="POST" action="{{ route('accounting.chart.seed') }}">@csrf<x-admin.button variant="light" icon="stars">Load standard chart</x-admin.button></form>
            @endif
            <x-admin.button icon="plus-lg" :href="route('accounting.chart.create')">New account</x-admin.button>
        @endif
    </x-admin.page-header>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2">
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:200px;min-height:40px" placeholder="Code or name…">
                <select name="type" class="form-select" style="width:150px;min-height:40px" onchange="this.form.submit()"><option value="">All types</option>@foreach ($types as $k => $l)<option value="{{ $k }}" @selected(request('type')===$k)>{{ $l }}</option>@endforeach</select>
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Flags</th><th class="text-end">Balance</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($accounts as $a)
                <tr>
                    <td class="fw-semibold">{{ $a->code }}</td>
                    <td>{{ $a->name }}@if (!$a->is_active)<span class="small text-body-secondary"> (inactive)</span>@endif</td>
                    <td><x-admin.badge :tone="$a->typeTone()">{{ $a->typeLabel() }}</x-admin.badge></td>
                    <td class="small">@if ($a->is_bank)<x-admin.badge tone="info">Bank</x-admin.badge>@endif @if ($a->control_type)<x-admin.badge tone="muted">{{ ucfirst($a->control_type) }}</x-admin.badge>@endif</td>
                    <td class="text-end fw-semibold">{{ number_format($balances[$a->id] ?? 0, 2) }}</td>
                    <td class="text-end text-nowrap">
                        @if ($canEdit)<a href="{{ route('accounting.chart.edit', $a) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                        <x-admin.delete-button :action="route('accounting.chart.destroy', $a)" :confirm="'Delete '.$a->name.'?'" />@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-admin.empty icon="journal-text" text="No accounts yet. Load the standard chart to get started." /></td></tr>
            @endforelse
            </tbody></table></div>
    </x-admin.card>
</div>
@endsection
