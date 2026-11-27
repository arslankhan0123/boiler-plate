@extends('layouts.admin.main')
@section('title', 'Trial Balance')
@section('subtitle', 'Accounting')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Trial Balance" :subtitle="$to ? 'As of '.$to : 'As of today'">
        <x-admin.button variant="light" icon="graph-up" :href="route('accounting.statements.pl')">P&amp;L</x-admin.button>
        <x-admin.button variant="light" icon="clipboard-data" :href="route('accounting.statements.bs')">Balance Sheet</x-admin.button>
    </x-admin.page-header>
    <x-admin.card flush>
        <x-slot:actions><form method="GET" class="d-flex gap-2"><input type="date" name="to" value="{{ $to }}" class="form-control" style="min-height:40px"><button class="btn btn-light">Go</button></form></x-slot:actions>
        <div class="table-responsive"><table class="table app-table align-middle">
            <thead><tr><th>Code</th><th>Account</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
            <tbody>
            @forelse ($tb['rows'] as $r)
                <tr><td class="small">{{ $r['account']->code }}</td><td>{{ $r['account']->name }}</td><td class="text-end">{{ $r['debit'] > 0 ? number_format($r['debit'], 2) : '' }}</td><td class="text-end">{{ $r['credit'] > 0 ? number_format($r['credit'], 2) : '' }}</td></tr>
            @empty
                <tr><td colspan="4"><x-admin.empty icon="journal-text" text="No balances yet." /></td></tr>
            @endforelse
            </tbody>
            <tfoot><tr class="fw-bold"><td colspan="2" class="text-end">Totals</td><td class="text-end">{{ number_format($tb['total_debit'], 2) }}</td><td class="text-end">{{ number_format($tb['total_credit'], 2) }}</td></tr>
            <tr><td colspan="4" class="text-end small {{ $tb['balanced'] ? 'text-success' : 'text-danger' }}">{{ $tb['balanced'] ? 'In balance' : 'Out of balance' }}</td></tr></tfoot>
        </table></div>
    </x-admin.card>
</div>
@endsection
