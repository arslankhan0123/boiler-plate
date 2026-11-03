@extends('layouts.admin.main')
@section('title', 'HR Report')
@section('subtitle', 'Reports & Analytics')
@section('content')
<div class="app-content">
    <x-admin.page-header title="HR" subtitle="People, leave and recruitment at a glance" />

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><x-admin.stat label="Employees" :value="number_format($employees)" icon="people" tone="primary" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Present today" :value="number_format($presentToday)" icon="person-check" tone="success" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Pending leave" :value="number_format($pendingLeave)" icon="hourglass-split" tone="warning" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Approved leave" :value="number_format($approvedLeave)" icon="calendar-check" tone="info" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Open jobs" :value="number_format($openJobs)" icon="briefcase" tone="primary" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Applications" :value="number_format($applications)" icon="file-person" tone="muted" /></div>
        <div class="col-6 col-xl-3"><x-admin.stat label="Payroll runs" :value="number_format($payrollRuns)" icon="cash-stack" tone="success" /></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5"><x-admin.card title="Leave by status">
            <table class="table app-table align-middle mb-0"><tbody>
                @forelse ($leaveByStatus as $status => $n)
                    <tr><td class="text-capitalize">{{ $status }}</td><td class="text-end">{{ $n }}</td></tr>
                @empty<tr><td class="text-body-secondary small">No leave requests yet.</td></tr>@endforelse
            </tbody></table>
        </x-admin.card></div>
        <div class="col-lg-7"><x-admin.card title="Recent leave requests">
            <table class="table app-table align-middle mb-0"><thead><tr><th>From</th><th>To</th><th class="text-center">Days</th><th class="text-end">Status</th></tr></thead><tbody>
                @forelse ($recentLeaves as $l)
                    <tr><td>{{ \Illuminate\Support\Carbon::parse($l->start_date)->format('d M') }}</td><td>{{ \Illuminate\Support\Carbon::parse($l->end_date)->format('d M') }}</td><td class="text-center">{{ $l->days }}</td><td class="text-end text-capitalize">{{ $l->status }}</td></tr>
                @empty<tr><td colspan="4" class="text-body-secondary small">No leave requests yet.</td></tr>@endforelse
            </tbody></table>
        </x-admin.card></div>
    </div>
</div>
@endsection
