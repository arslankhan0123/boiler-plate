@extends('layouts.admin.main')
@section('title', 'Balance Sheet')
@section('subtitle', 'Accounting')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Balance Sheet" :subtitle="'As of '.$to">
        <x-admin.button variant="light" icon="list-columns" :href="route('accounting.statements.trial')">Trial Balance</x-admin.button>
    </x-admin.page-header>
    <div class="row g-4">
        <div class="col-lg-6"><x-admin.card title="Assets">
            <table class="table app-table align-middle mb-0"><tbody>
                @forelse ($bs['assets'] as $r)<tr><td>{{ $r['account']->name }}</td><td class="text-end">{{ number_format($r['amount'], 2) }}</td></tr>@empty<tr><td colspan="2" class="text-body-secondary small">—</td></tr>@endforelse
                <tr class="fw-bold border-top"><td>Total assets</td><td class="text-end">{{ number_format($bs['total_assets'], 2) }}</td></tr>
            </tbody></table>
        </x-admin.card></div>
        <div class="col-lg-6">
            <x-admin.card title="Liabilities" class="mb-4">
                <table class="table app-table align-middle mb-0"><tbody>
                    @forelse ($bs['liabilities'] as $r)<tr><td>{{ $r['account']->name }}</td><td class="text-end">{{ number_format($r['amount'], 2) }}</td></tr>@empty<tr><td colspan="2" class="text-body-secondary small">—</td></tr>@endforelse
                    <tr class="fw-bold border-top"><td>Total liabilities</td><td class="text-end">{{ number_format($bs['total_liabilities'], 2) }}</td></tr>
                </tbody></table>
            </x-admin.card>
            <x-admin.card title="Equity">
                <table class="table app-table align-middle mb-0"><tbody>
                    @foreach ($bs['equity'] as $r)<tr><td>{{ $r['account']->name }}</td><td class="text-end">{{ number_format($r['amount'], 2) }}</td></tr>@endforeach
                    <tr><td>Retained earnings (net)</td><td class="text-end">{{ number_format($bs['retained'], 2) }}</td></tr>
                    <tr class="fw-bold border-top"><td>Total equity</td><td class="text-end">{{ number_format($bs['total_equity'], 2) }}</td></tr>
                </tbody></table>
            </x-admin.card>
        </div>
    </div>
    <div class="mt-3 small text-end {{ $bs['balanced'] ? 'text-success' : 'text-danger' }}">
        Assets {{ number_format($bs['total_assets'], 2) }} {{ $bs['balanced'] ? '=' : '≠' }} Liabilities + Equity {{ number_format($bs['total_liabilities'] + $bs['total_equity'], 2) }}
    </div>
</div>
@endsection
