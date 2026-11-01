@extends('layouts.admin.main')
@php $money = fn ($v) => \App\Support\Hrm\Hrm::money($v); @endphp
@section('title', 'Payroll')
@section('subtitle', 'HRM · Payroll')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Payroll" subtitle="Generate monthly salaries from basic pay, allowances, unpaid leave and absences.">
        <form method="POST" action="{{ route('hrm.payroll.generate') }}" class="d-flex gap-2">@csrf
            <input type="month" name="month" value="{{ now()->format('Y-m') }}" class="form-control" style="width:170px" required>
            <x-admin.button icon="lightning-charge">Generate Payroll</x-admin.button>
        </form>
    </x-admin.page-header>

    <x-admin.card flush title="Payroll runs">
        <div class="table-responsive"><table class="table app-table table-hover">
            <thead><tr><th>Month</th><th>Employees</th><th>Working days</th><th>Gross</th><th>Deductions</th><th>Net pay</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($runs as $run)
                <tr>
                    <td class="fw-semibold">{{ $run->label() }}</td>
                    <td>{{ $run->payslips_count }}</td>
                    <td>{{ $run->working_days }}</td>
                    <td>{{ $money($run->total_gross) }}</td>
                    <td class="text-danger">{{ $money($run->total_deductions) }}</td>
                    <td class="fw-bold">{{ $money($run->total_net) }}</td>
                    <td><x-admin.badge :tone="\App\Models\Hrm\PayrollRun::STATUSES[$run->status]">{{ ucfirst($run->status) }}</x-admin.badge></td>
                    <td class="text-end"><a href="{{ route('hrm.payroll.show', $run) }}" class="btn btn-sm btn-light">Open <i class="bi bi-arrow-right"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="8"><x-admin.empty icon="cash-stack" text="No payroll yet. Pick a month and click Generate." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($runs->hasPages())
            <x-slot:footer>{{ $runs->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
