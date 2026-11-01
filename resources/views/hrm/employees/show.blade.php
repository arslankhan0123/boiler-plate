@extends('layouts.admin.main')
@php $money = fn ($v) => \App\Support\Hrm\Hrm::money($v); $d = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('d M Y') : '—'; @endphp
@section('title', $employee->name)
@section('subtitle', 'HRM · Employee profile')

@section('content')
<div class="app-content">
    <div class="app-page-header">
        <div class="app-page-header__lead">
            <a href="{{ route('hrm.employees.index') }}" class="app-back"><i class="bi bi-chevron-left"></i></a>
            <span class="app-avatar app-avatar--lg" style="width:60px;height:60px;font-size:1.2rem">{{ \App\Support\Hrm\Hrm::initials($employee->name) }}</span>
            <div>
                <h1 class="app-page-header__title">{{ $employee->name }}
                    @unless ($employee->is_active)<x-admin.badge tone="danger">Inactive</x-admin.badge>@endunless</h1>
                <p class="app-page-header__subtitle">{{ $employee->designation->name ?? '—' }} · {{ $employee->department->name ?? '—' }}
                    @if ($employee->code) · {{ $employee->code }}@endif</p>
            </div>
        </div>
        @can('hrm.manage_employees')
            <div class="app-page-header__actions">
                <x-admin.button :href="route('hrm.employees.edit', $employee)" icon="pencil">Edit</x-admin.button>
            </div>
        @endcan
    </div>

    <div class="app-form-grid mt-4">
        <div class="d-grid gap-4">
            <x-admin.card :title="'Leave balance '.$year">
                <x-slot:actions>
                    <form method="GET"><select name="year" class="form-select" style="min-height:38px;width:110px" onchange="this.form.submit()">
                        @foreach (range(now()->year + 1, now()->year - 3) as $y)<option @selected($y === $year)>{{ $y }}</option>@endforeach
                    </select></form>
                </x-slot:actions>
                @include('hrm.partials.balances', ['balances' => $balances])
            </x-admin.card>

            <x-admin.card flush title="Recent leave">
                <div class="table-responsive"><table class="table app-table">
                    <thead><tr><th>Type</th><th>Dates</th><th>Days</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($leaves as $l)
                        <tr><td>{{ $l->type->name ?? '—' }}</td><td>{{ $l->start_date->format('d M') }} – {{ $l->end_date->format('d M Y') }}</td><td>{{ $l->days }}</td>
                            <td>@include('hrm.partials.leave-badge', ['status' => $l->status])</td></tr>
                    @empty
                        <tr><td colspan="4"><x-admin.empty icon="calendar" text="No leave requests." /></td></tr>
                    @endforelse
                    </tbody></table></div>
            </x-admin.card>

            <x-admin.card flush :title="'Attendance — '.now()->format('F Y')">
                <div class="table-responsive"><table class="table app-table">
                    <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Worked</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($attendance as $a)
                        <tr><td>{{ $a->date->format('D, d M') }}</td><td>{{ $a->check_in ? substr($a->check_in, 0, 5) : '—' }}</td><td>{{ $a->check_out ? substr($a->check_out, 0, 5) : '—' }}</td>
                            <td>{{ $a->workedLabel() }}</td><td>@include('hrm.partials.att-badge', ['status' => $a->status])</td></tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty icon="clock" text="No attendance this month." /></td></tr>
                    @endforelse
                    </tbody></table></div>
            </x-admin.card>

            @can('hrm.process_payroll')
                <x-admin.card flush title="Payslips">
                    <div class="table-responsive"><table class="table app-table">
                        <thead><tr><th>Month</th><th>Number</th><th>Net pay</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($payslips as $p)
                            <tr><td>{{ $p->run->label() }}</td><td>{{ $p->number }}</td><td class="fw-semibold">{{ $money($p->net) }}</td>
                                <td><x-admin.badge :tone="\App\Models\Hrm\PayrollRun::STATUSES[$p->run->status]">{{ ucfirst($p->run->status) }}</x-admin.badge></td>
                                <td class="text-end"><a href="{{ route('hrm.payslips.show', $p) }}" class="btn btn-action"><i class="bi bi-file-earmark-text"></i></a></td></tr>
                        @empty
                            <tr><td colspan="5"><x-admin.empty icon="cash" text="No payslips yet." /></td></tr>
                        @endforelse
                        </tbody></table></div>
                </x-admin.card>
            @endcan

            <x-admin.card flush title="Documents">
                <div class="table-responsive"><table class="table app-table">
                    <thead><tr><th>Document</th><th>Expiry</th><th class="text-end"></th></tr></thead>
                    <tbody>
                    @forelse ($employee->documents as $doc)
                        <tr>
                            <td class="fw-semibold">{{ $doc->name }}</td>
                            <td>@if ($doc->expiry_date)
                                    <x-admin.badge :tone="\Illuminate\Support\Carbon::parse($doc->expiry_date)->isPast() ? 'danger' : 'muted'">{{ $d($doc->expiry_date) }}</x-admin.badge>
                                @else — @endif</td>
                            <td class="text-end text-nowrap">
                                @if ($doc->file)<a href="{{ route('hrm.employees.documents.download', [$employee, $doc]) }}" class="btn btn-action"><i class="bi bi-download"></i></a>@endif
                                @can('hrm.manage_employees')<x-admin.delete-button :action="route('hrm.employees.documents.destroy', [$employee, $doc])" confirm="Delete this document?" />@endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><x-admin.empty icon="folder" text="No documents." /></td></tr>
                    @endforelse
                    </tbody></table></div>
                @can('hrm.manage_employees')
                    <x-slot:footer>
                        <form method="POST" action="{{ route('hrm.employees.documents.store', $employee) }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 w-100 align-items-center">
                            @csrf
                            <input name="name" class="form-control" style="max-width:220px;min-height:42px" placeholder="Document name" required>
                            <input name="expiry_date" type="date" class="form-control" style="max-width:170px;min-height:42px" title="Expiry (optional)">
                            <input name="file" type="file" class="form-control" style="max-width:260px;min-height:42px" required>
                            <x-admin.button icon="upload" size="sm">Upload</x-admin.button>
                        </form>
                    </x-slot:footer>
                @endcan
            </x-admin.card>
        </div>

        <div class="app-aside">
            <x-admin.card title="Details">
                <dl class="row small mb-0 g-2">
                    <dt class="col-5 text-body-secondary fw-normal">Email</dt><dd class="col-7 mb-0 text-break">{{ $employee->email ?: '—' }}</dd>
                    <dt class="col-5 text-body-secondary fw-normal">Phone</dt><dd class="col-7 mb-0">{{ $employee->phone ?: '—' }}</dd>
                    <dt class="col-5 text-body-secondary fw-normal">Joined</dt><dd class="col-7 mb-0">{{ $d($employee->join_date) }}</dd>
                    <dt class="col-5 text-body-secondary fw-normal">Type</dt><dd class="col-7 mb-0">{{ config('hrm.employment_types')[$employee->employment_type] ?? '—' }}</dd>
                    <dt class="col-5 text-body-secondary fw-normal">Date of birth</dt><dd class="col-7 mb-0">{{ $d($employee->dob) }}</dd>
                    <dt class="col-5 text-body-secondary fw-normal">CNIC</dt><dd class="col-7 mb-0">{{ $employee->national_id ?: '—' }}</dd>
                    <dt class="col-5 text-body-secondary fw-normal">Address</dt><dd class="col-7 mb-0">{{ trim($employee->street.', '.$employee->city, ', ') ?: '—' }}</dd>
                    @can('hrm.process_payroll')
                        <dt class="col-5 text-body-secondary fw-normal">Basic salary</dt><dd class="col-7 mb-0 fw-semibold">{{ $money($employee->basic_salary) }}</dd>
                        <dt class="col-5 text-body-secondary fw-normal">Allowances</dt><dd class="col-7 mb-0">{{ $money($employee->transport_allowance) }}</dd>
                        <dt class="col-5 text-body-secondary fw-normal">Bank</dt><dd class="col-7 mb-0">{{ $employee->bank_name ?: '—' }} {{ $employee->bank_account_no }}</dd>
                    @endcan
                </dl>
            </x-admin.card>

            <x-admin.card title="Reporting line">
                <div class="small text-body-secondary mb-2">Reports to</div>
                @if ($manager)@include('hrm.partials.person', ['e' => $manager, 'link' => true])@else<div class="text-body-secondary small">— Top level —</div>@endif
                <div class="small text-body-secondary mt-3 mb-2">Direct reports ({{ $reports->count() }})</div>
                <div class="d-grid gap-2">
                    @forelse ($reports as $r)@include('hrm.partials.person', ['e' => $r, 'link' => true, 'sub' => $r->designation->name ?? ''])@empty<div class="text-body-secondary small">None</div>@endforelse
                </div>
            </x-admin.card>

            <x-admin.card title="Login">
                @if ($employee->user)
                    <x-admin.badge tone="success" icon="key">Can sign in</x-admin.badge>
                    <div class="small mt-2">{{ $employee->user->email }}</div>
                    <div class="mt-2">@foreach ($employee->user->roles as $r)<x-admin.badge tone="primary" class="me-1">{{ $r->name }}</x-admin.badge>@endforeach</div>
                    @if ($employee->user->permissions->count())<div class="small text-body-secondary mt-2">+ {{ $employee->user->permissions->count() }} extra permissions</div>@endif
                @else
                    <x-admin.badge tone="muted">No login</x-admin.badge>
                @endif
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
