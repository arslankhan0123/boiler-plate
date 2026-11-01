@extends('layouts.admin.main')
@php $money = fn ($v) => \App\Support\Hrm\Hrm::money($v); $draft = $run->status === 'draft'; @endphp
@section('title', 'Payroll — '.$run->label())
@section('subtitle', 'HRM · Payroll')

@section('content')
<div class="app-content">
    <x-admin.page-header :back="route('hrm.payroll.index')" :title="'Payroll — '.$run->label()" :subtitle="$run->working_days.' working days · '.ucfirst($run->status)">
        @if ($draft)
            <form method="POST" action="{{ route('hrm.payroll.generate') }}">@csrf <input type="hidden" name="month" value="{{ $run->month }}">
                <x-admin.button variant="light" icon="arrow-repeat">Recalculate</x-admin.button></form>
            <form method="POST" action="{{ route('hrm.payroll.destroy', $run) }}" onsubmit="return confirm('Delete this draft payroll?')">@csrf @method('DELETE')
                <x-admin.button variant="light" icon="trash3" class="text-danger">Delete</x-admin.button></form>
            <form method="POST" action="{{ route('hrm.payroll.finalize', $run) }}" onsubmit="return confirm('Finalize? Payslips become visible to employees and can no longer be edited.')">@csrf
                <x-admin.button icon="lock">Finalize</x-admin.button></form>
        @elseif ($run->status === 'finalized')
            <form method="POST" action="{{ route('hrm.payroll.paid', $run) }}" onsubmit="return confirm('Mark all salaries as paid?')">@csrf
                <x-admin.button icon="check2-circle">Mark as Paid</x-admin.button></form>
        @endif
    </x-admin.page-header>

    <div class="row g-4 mb-4">
        <div class="col-md-4"><x-admin.stat label="Gross pay" :value="$money($run->total_gross)" icon="cash" tone="primary" /></div>
        <div class="col-md-4"><x-admin.stat label="Deductions" :value="$money($run->total_deductions)" icon="dash-circle" tone="warning" /></div>
        <div class="col-md-4"><x-admin.stat label="Net pay" :value="$money($run->total_net)" icon="wallet2" tone="success" /></div>
    </div>

    <x-admin.card flush :title="'Payslips ('.$payslips->count().')'">
        <div class="table-responsive"><table class="table app-table align-middle">
            <thead><tr><th>Employee</th><th>Basic</th><th>Allowances</th><th>Bonus</th><th>Unpaid days</th><th>Deductions</th><th>Net</th><th class="text-end"></th></tr></thead>
            <tbody>
            @foreach ($payslips as $p)
                <tr>
                    <td>@include('hrm.partials.person', ['e' => $p->employee, 'sub' => $p->number])</td>
                    <td>{{ $money($p->basic_salary) }}</td>
                    <td>{{ $money($p->allowances) }}</td>
                    @if ($draft)
                        <td><input form="ps-{{ $p->id }}" type="number" step="0.01" min="0" name="bonus" value="{{ $p->bonus }}" class="form-control" style="width:120px;min-height:38px"></td>
                        <td>{{ $p->unpaid_days }}<div class="small text-danger">−{{ number_format($p->unpaid_deduction, 2) }}</div></td>
                        <td><input form="ps-{{ $p->id }}" type="number" step="0.01" min="0" name="other_deductions" value="{{ $p->other_deductions }}" class="form-control" style="width:120px;min-height:38px" title="Other deductions (tax, advance…)"></td>
                    @else
                        <td>{{ $money($p->bonus) }}</td>
                        <td>{{ $p->unpaid_days }}</td>
                        <td class="text-danger">{{ $money($p->totalDeductions()) }}</td>
                    @endif
                    <td class="fw-bold">{{ $money($p->net) }}
                        @if ($p->component_earnings || $p->component_deductions || $p->loan_deductions)
                            <div class="small text-body-secondary fw-normal">
                                @if ($p->component_earnings)+{{ number_format($p->component_earnings) }} @endif
                                @if ($p->component_deductions)−{{ number_format($p->component_deductions) }} @endif
                                @if ($p->loan_deductions)<span title="Loan installment">−{{ number_format($p->loan_deductions) }} loan</span>@endif
                            </div>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        @if ($draft)
                            <form id="ps-{{ $p->id }}" method="POST" action="{{ route('hrm.payslips.update', $p) }}" class="d-inline">@csrf @method('PUT')
                                <button class="btn btn-action" title="Save"><i class="bi bi-check2"></i></button></form>
                        @endif
                        <a href="{{ route('hrm.payslips.show', $p) }}" class="btn btn-action" title="Payslip" target="_blank"><i class="bi bi-file-earmark-text"></i></a>
                    </td>
                </tr>
            @endforeach
            </tbody></table></div>
    </x-admin.card>
    <p class="form-text mt-3">Unpaid days = unpaid leave + absences + ½ per half day + days before joining. Deduction = basic ÷ working days × unpaid days. Recurring salary components and loan installments are added automatically; loans are repaid when you Finalize.</p>
</div>
@endsection
