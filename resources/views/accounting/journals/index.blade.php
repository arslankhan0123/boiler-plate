@extends('layouts.admin.main')
@section('title', 'Journal Entries')
@section('subtitle', 'Accounting')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Journal Entries" subtitle="Manual double-entry transactions">
        @if ($canPost)<x-admin.button icon="plus-lg" :href="route('accounting.journals.create')">New entry</x-admin.button>@endif
    </x-admin.page-header>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2">
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:200px;min-height:40px" placeholder="Reference, memo…">
                <select name="status" class="form-select" style="width:140px;min-height:40px" onchange="this.form.submit()"><option value="">All</option><option value="draft" @selected(request('status')==='draft')>Draft</option><option value="posted" @selected(request('status')==='posted')>Posted</option></select>
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Reference</th><th>Date</th><th>Memo</th><th class="text-center">Lines</th><th class="text-end">Amount</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($journals as $j)
                <tr>
                    <td><a href="{{ route('accounting.journals.show', $j) }}" class="fw-semibold text-decoration-none">{{ $j->reference }}</a></td>
                    <td class="small">{{ $j->date->format('d M Y') }}</td>
                    <td class="small">{{ $j->memo ?: '—' }}</td>
                    <td class="text-center">{{ $j->lines_count }}</td>
                    <td class="text-end">{{ number_format($j->total ?? 0, 2) }}</td>
                    <td><x-admin.badge :tone="$j->status === 'posted' ? 'success' : 'muted'">{{ ucfirst($j->status) }}</x-admin.badge></td>
                    <td class="text-end"><a href="{{ route('accounting.journals.show', $j) }}" class="btn btn-action"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-admin.empty icon="journal-text" text="No journal entries yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        <x-slot:footer>{{ $journals->links('pagination::bootstrap-5') }}</x-slot:footer>
    </x-admin.card>
</div>
@endsection
