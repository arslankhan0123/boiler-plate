@extends('layouts.admin.main')
@section('title', 'My Payslips')
@section('subtitle', 'My HR')

@section('content')
<div class="app-content">
    <x-admin.page-header title="My Payslips" subtitle="Your monthly salary slips. Open one to print or save as PDF." />
    <x-admin.card flush>
        <div class="table-responsive"><table class="table app-table table-hover">
            <thead><tr><th>Month</th><th>Number</th><th>Gross</th><th>Deductions</th><th>Net pay</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($payslips as $p)
                <tr><td class="fw-semibold">{{ $p->run->label() }}</td><td>{{ $p->number }}</td>
                    <td>{{ \App\Support\Hrm\Hrm::money($p->gross) }}</td>
                    <td class="text-danger">{{ \App\Support\Hrm\Hrm::money($p->unpaid_deduction + $p->other_deductions) }}</td>
                    <td class="fw-bold">{{ \App\Support\Hrm\Hrm::money($p->net) }}</td>
                    <td><x-admin.badge :tone="\App\Models\Hrm\PayrollRun::STATUSES[$p->run->status]">{{ ucfirst($p->run->status) }}</x-admin.badge></td>
                    <td class="text-end"><a href="{{ route('hrm.payslips.show', $p) }}" class="btn btn-sm btn-light"><i class="bi bi-file-earmark-text"></i> View</a></td></tr>
            @empty
                <tr><td colspan="7"><x-admin.empty icon="receipt" text="No payslips yet." /></td></tr>
            @endforelse
            </tbody></table></div>
    </x-admin.card>
</div>
@endsection
