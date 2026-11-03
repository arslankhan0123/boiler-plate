@extends('layouts.admin.main')
@section('title', 'Accounting Report')
@section('subtitle', 'Reports & Analytics')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Accounting" subtitle="Profit &amp; loss and balance sheet summary" />
    <p class="text-body-secondary small mb-4"><i class="bi bi-calendar3 me-1"></i>Profit &amp; loss for {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}; balance sheet as of {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}.</p>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-4"><x-admin.stat label="Income" :value="$cur.' '.number_format($income, 0)" icon="arrow-down-circle" tone="success" /></div>
        <div class="col-6 col-xl-4"><x-admin.stat label="Expense" :value="$cur.' '.number_format($expense, 0)" icon="arrow-up-circle" tone="danger" /></div>
        <div class="col-6 col-xl-4"><x-admin.stat :label="$net >= 0 ? 'Net profit' : 'Net loss'" :value="$cur.' '.number_format(abs($net), 0)" icon="graph-up-arrow" :tone="$net >= 0 ? 'primary' : 'warning'" /></div>
        <div class="col-6 col-xl-4"><x-admin.stat label="Assets" :value="$cur.' '.number_format($assets, 0)" icon="bank" tone="info" /></div>
        <div class="col-6 col-xl-4"><x-admin.stat label="Liabilities" :value="$cur.' '.number_format($liabilities, 0)" icon="credit-card" tone="warning" /></div>
        <div class="col-6 col-xl-4"><x-admin.stat label="Equity" :value="$cur.' '.number_format($equity, 0)" icon="pie-chart" tone="primary" /></div>
    </div>

    @unless($balanced)<div class="alert alert-warning small"><i class="bi bi-exclamation-triangle me-1"></i>The balance sheet is not balanced — check unposted or draft journals.</div>@endunless

    <div class="row g-4">
        <div class="col-lg-6"><x-admin.card title="Income">
            <table class="table app-table align-middle mb-0"><tbody>
                @forelse ($incomeRows as $r)
                    <tr><td>{{ $r['account']->name }}</td><td class="text-end">{{ $cur }} {{ number_format($r['amount'], 0) }}</td></tr>
                @empty<tr><td class="text-body-secondary small">No income recorded.</td></tr>@endforelse
                <tr class="fw-bold"><td>Total income</td><td class="text-end">{{ $cur }} {{ number_format($income, 0) }}</td></tr>
            </tbody></table>
        </x-admin.card></div>
        <div class="col-lg-6"><x-admin.card title="Expenses">
            <table class="table app-table align-middle mb-0"><tbody>
                @forelse ($expenseRows as $r)
                    <tr><td>{{ $r['account']->name }}</td><td class="text-end">{{ $cur }} {{ number_format($r['amount'], 0) }}</td></tr>
                @empty<tr><td class="text-body-secondary small">No expenses recorded.</td></tr>@endforelse
                <tr class="fw-bold"><td>Total expenses</td><td class="text-end">{{ $cur }} {{ number_format($expense, 0) }}</td></tr>
            </tbody></table>
        </x-admin.card></div>
    </div>
</div>
@endsection
