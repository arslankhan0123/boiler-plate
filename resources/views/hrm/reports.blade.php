@extends('layouts.admin.main')
@php $money = fn ($v) => \App\Support\Hrm\Hrm::money($v); @endphp
@section('title', 'HR Reports')
@section('subtitle', 'HRM · Reports')

@section('content')
<div class="app-content">
    <x-admin.page-header title="HR Reports" :subtitle="'Year '.$year.' · attendance for '.$month->format('F Y')">
        <form method="GET" class="d-flex gap-2">
            <select name="year" class="form-select" style="width:110px" onchange="this.form.submit()">@foreach (range(now()->year, now()->year - 4) as $y)<option @selected($y === $year)>{{ $y }}</option>@endforeach</select>
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control" style="width:170px" onchange="this.form.submit()">
        </form>
    </x-admin.page-header>

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3"><x-admin.stat label="Active employees" :value="$totalEmployees" icon="people" tone="primary" /></div>
        <div class="col-sm-6 col-xl-3"><x-admin.stat :label="'Joined in '.$year" :value="$joinedThisYear" icon="person-plus" tone="success" /></div>
        <div class="col-sm-6 col-xl-3"><x-admin.stat :label="'Left in '.$year" :value="$leftThisYear" icon="person-dash" tone="warning" /></div>
        <div class="col-sm-6 col-xl-3"><x-admin.stat :label="'Applications '.$year" :value="$applications->sum()" icon="inbox" tone="info" /></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <x-admin.card title="Headcount by department">
                @php $max = max(1, $headcount->max('employees_count')); @endphp
                @forelse ($headcount as $d)
                    <div class="mb-3"><div class="d-flex justify-content-between small mb-1"><span>{{ $d->name }}</span><b>{{ $d->employees_count }}</b></div>
                        <div class="progress" style="height:8px"><div class="progress-bar" style="width:{{ $d->employees_count / $max * 100 }}%;background:var(--app-btn-grad)"></div></div></div>
                @empty<x-admin.empty icon="diagram-3" text="No departments." />@endforelse
            </x-admin.card>
        </div>
        <div class="col-lg-6">
            <x-admin.card :title="'Leave taken in '.$year">
                @forelse ($leaveByType as $l)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span><span class="d-inline-block rounded-circle me-2" style="width:10px;height:10px;background:{{ $l->color }}"></span>{{ $l->name }}</span>
                        <span><b>{{ (float) $l->days }}</b> days <span class="text-body-secondary small">({{ $l->requests }} requests)</span></span></div>
                @empty<x-admin.empty icon="calendar" text="No approved leave." />@endforelse
            </x-admin.card>
        </div>
        <div class="col-lg-6">
            <x-admin.card :title="'Attendance — '.$month->format('F Y')">
                <div class="row g-3">
                    @foreach (\App\Models\Hrm\Attendance::STATUSES as $k => [$label, $tone])
                        <div class="col-4"><div class="small text-body-secondary">{{ $label }}</div><div class="h4 fw-bold mb-0">{{ $attendance[$k] ?? 0 }}</div></div>
                    @endforeach
                </div>
            </x-admin.card>
        </div>
        <div class="col-lg-6">
            <x-admin.card flush :title="'Payroll '.$year">
                <div class="table-responsive"><table class="table app-table mb-0">
                    <thead><tr><th>Month</th><th>Net</th><th>Status</th></tr></thead><tbody>
                    @forelse ($payroll as $r)
                        <tr><td>{{ $r->label() }}</td><td class="fw-semibold">{{ $money($r->total_net) }}</td><td><x-admin.badge :tone="\App\Models\Hrm\PayrollRun::STATUSES[$r->status]">{{ ucfirst($r->status) }}</x-admin.badge></td></tr>
                    @empty<tr><td colspan="3"><x-admin.empty icon="cash" text="No payroll this year." /></td></tr>@endforelse
                    @if ($payroll->count())<tr><th>Total</th><th>{{ $money($payroll->sum('total_net')) }}</th><th></th></tr>@endif
                    </tbody></table></div>
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
