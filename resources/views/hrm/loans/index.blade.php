@extends('layouts.admin.main')
@php $money = fn ($v) => \App\Support\Hrm\Hrm::money($v); @endphp
@section('title', 'Loans & Advances')
@section('subtitle', 'HRM · Payroll')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Loans & Advances" subtitle="Installments are deducted automatically in payroll; the balance updates when payroll is finalized.">
        <x-admin.button type="button" icon="plus-lg" data-bs-toggle="modal" data-bs-target="#loanModal">New Loan / Advance</x-admin.button>
    </x-admin.page-header>

    <div class="row g-4 mb-4">
        <div class="col-md-4"><x-admin.stat label="Outstanding" :value="$money($totals['outstanding'])" icon="cash-stack" tone="warning" /></div>
        <div class="col-md-4"><x-admin.stat label="Monthly recovery" :value="$money($totals['monthly'])" icon="arrow-repeat" tone="primary" /></div>
        <div class="col-md-4"><x-admin.stat label="Active loans" :value="$totals['active']" icon="people" tone="info" /></div>
    </div>

    <x-admin.card flush>
        <x-slot:actions>
            @foreach (['active' => 'Active', 'closed' => 'Closed', 'all' => 'All'] as $k => $l)
                <a href="{{ route('hrm.loans.index', ['status' => $k]) }}" class="btn btn-sm {{ $status === $k ? 'btn-primary' : 'btn-light' }}">{{ $l }}</a>
            @endforeach
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover">
            <thead><tr><th>Employee</th><th>Type</th><th>Amount</th><th>Monthly</th><th>Repaid</th><th>Balance</th><th>From</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($loans as $l)
                @php $pct = $l->amount > 0 ? min(100, $l->repaid / $l->amount * 100) : 0; @endphp
                <tr>
                    <td>@include('hrm.partials.person', ['e' => $l->employee, 'link' => true, 'sub' => $l->reason ?: ($l->employee->department->name ?? '')])</td>
                    <td><x-admin.badge :tone="$l->type === 'loan' ? 'primary' : 'info'">{{ ucfirst($l->type) }}</x-admin.badge></td>
                    <td>{{ $money($l->amount) }}</td>
                    <td>{{ $money($l->installment) }}</td>
                    <td style="min-width:140px">{{ $money($l->repaid) }}<div class="progress mt-1" style="height:5px"><div class="progress-bar bg-success" style="width:{{ $pct }}%"></div></div></td>
                    <td class="fw-bold">{{ $money($l->balance()) }}</td>
                    <td>{{ $l->start_month->format('M Y') }}</td>
                    <td class="text-end">
                        @if ($l->status === 'active')
                            <form method="POST" action="{{ route('hrm.loans.close', $l) }}" onsubmit="return confirm('Close this loan? No more installments will be deducted.')">@csrf
                                <button class="btn btn-sm btn-light">Close</button></form>
                        @else<x-admin.badge tone="success">Closed</x-admin.badge>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><x-admin.empty icon="cash-coin" text="No loans or advances." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($loans->hasPages())
            <x-slot:footer>{{ $loans->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>

<div class="modal fade" id="loanModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:16px">
    <form method="POST" action="{{ route('hrm.loans.store') }}">@csrf
        <div class="modal-header"><h5 class="modal-title fw-bold">New loan / advance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <x-admin.select name="employee_id" label="Employee" :options="$employees" placeholder="Select employee" required />
            <x-admin.select name="type" label="Type" :options="['loan' => 'Loan', 'advance' => 'Salary advance']" value="loan" required />
            <div class="row g-3 mt-0">
                <div class="col-6"><x-admin.input name="amount" type="number" step="0.01" min="1" label="Amount" required /></div>
                <div class="col-6"><x-admin.input name="installment" type="number" step="0.01" min="1" label="Monthly installment" required /></div>
            </div>
            <x-admin.input name="start_month" type="month" label="First deduction month" :value="now()->format('Y-m')" required />
            <x-admin.input name="reason" label="Reason" optional />
        </div>
        <div class="modal-footer"><x-admin.button variant="light" type="button" data-bs-dismiss="modal">Cancel</x-admin.button><x-admin.button icon="check2">Save</x-admin.button></div>
    </form>
</div></div></div>
@if ($errors->any())<script>document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal('#loanModal').show());</script>@endif
@endsection
