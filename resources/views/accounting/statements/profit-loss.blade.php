@extends('layouts.admin.main')
@section('title', 'Profit & Loss')
@section('subtitle', 'Accounting')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Profit & Loss" :subtitle="$from.' → '.$to">
        <x-admin.button variant="light" icon="list-columns" :href="route('accounting.statements.trial')">Trial Balance</x-admin.button>
    </x-admin.page-header>
    <x-admin.card flush>
        <x-slot:actions><form method="GET" class="d-flex gap-2"><input type="date" name="from" value="{{ $from }}" class="form-control" style="min-height:40px"><input type="date" name="to" value="{{ $to }}" class="form-control" style="min-height:40px"><button class="btn btn-light">Go</button></form></x-slot:actions>
        <div class="app-card__body">
            <h3 class="app-aside-title">Income</h3>
            <table class="table app-table align-middle mb-4"><tbody>
                @forelse ($pl['income'] as $r)<tr><td>{{ $r['account']->name }}</td><td class="text-end">{{ number_format($r['amount'], 2) }}</td></tr>@empty<tr><td colspan="2" class="text-body-secondary small">No income.</td></tr>@endforelse
                <tr class="fw-bold border-top"><td>Total income</td><td class="text-end">{{ number_format($pl['total_income'], 2) }}</td></tr>
            </tbody></table>
            <h3 class="app-aside-title">Expenses</h3>
            <table class="table app-table align-middle mb-4"><tbody>
                @forelse ($pl['expense'] as $r)<tr><td>{{ $r['account']->name }}</td><td class="text-end">{{ number_format($r['amount'], 2) }}</td></tr>@empty<tr><td colspan="2" class="text-body-secondary small">No expenses.</td></tr>@endforelse
                <tr class="fw-bold border-top"><td>Total expenses</td><td class="text-end">{{ number_format($pl['total_expense'], 2) }}</td></tr>
            </tbody></table>
            <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background:var(--app-surface-2,#f8f9fb)">
                <span class="h5 mb-0">Net {{ $pl['net'] >= 0 ? 'Profit' : 'Loss' }}</span>
                <span class="h4 mb-0 {{ $pl['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($pl['net'], 2) }}</span>
            </div>
        </div>
    </x-admin.card>
</div>
@endsection
