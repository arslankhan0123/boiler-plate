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

            @can('hrm.process_payroll')
                <x-admin.card flush title="Salary components" subtitle="Recurring monthly earnings / deductions — added to every payroll.">
                    <div class="table-responsive"><table class="table app-table">
                        <tbody>
                        @forelse ($components as $c)
                            <tr><td class="fw-semibold">{{ $c->name }}</td>
                                <td><x-admin.badge :tone="$c->type === 'earning' ? 'success' : 'danger'">{{ ucfirst($c->type) }}</x-admin.badge></td>
                                <td class="fw-semibold {{ $c->type === 'earning' ? 'text-success' : 'text-danger' }}">{{ $c->type === 'earning' ? '+' : '−' }}{{ $money($c->amount) }}</td>
                                <td class="text-end"><x-admin.delete-button :action="route('hrm.components.destroy', [$employee, $c])" confirm="Remove this component?" /></td></tr>
                        @empty
                            <tr><td><x-admin.empty icon="plus-slash-minus" text="No recurring components (e.g. house rent, tax, EOBI)." /></td></tr>
                        @endforelse
                        </tbody></table></div>
                    <x-slot:footer>
                        <form method="POST" action="{{ route('hrm.components.store', $employee) }}" class="d-flex flex-wrap gap-2 w-100 align-items-center">@csrf
                            <input name="name" class="form-control" style="max-width:220px;min-height:42px" placeholder="e.g. House rent" required>
                            <select name="type" class="form-select" style="max-width:150px;min-height:42px"><option value="earning">Earning</option><option value="deduction">Deduction</option></select>
                            <input name="amount" type="number" step="0.01" min="0.01" class="form-control" style="max-width:150px;min-height:42px" placeholder="Amount" required>
                            <x-admin.button size="sm" icon="plus-lg">Add</x-admin.button>
                        </form>
                    </x-slot:footer>
                </x-admin.card>

                @if ($loans->isNotEmpty())
                    <x-admin.card flush title="Loans & advances">
                        <div class="table-responsive"><table class="table app-table"><thead><tr><th>Type</th><th>Amount</th><th>Monthly</th><th>Balance</th><th>Status</th></tr></thead><tbody>
                            @foreach ($loans as $l)
                                <tr><td>{{ ucfirst($l->type) }}<div class="small text-body-secondary">{{ $l->reason }}</div></td><td>{{ $money($l->amount) }}</td><td>{{ $money($l->installment) }}</td>
                                    <td class="fw-semibold">{{ $money($l->balance()) }}</td><td><x-admin.badge :tone="$l->status === 'active' ? 'warning' : 'success'">{{ ucfirst($l->status) }}</x-admin.badge></td></tr>
                            @endforeach
                        </tbody></table></div>
                    </x-admin.card>
                @endif
            @endcan

            @if ($reviews->isNotEmpty() && (auth()->user()->can('hrm.manage_performance') || \App\Support\Hrm\Hrm::isManagerOf($employee)))
                <x-admin.card flush title="Performance reviews">
                    <div class="table-responsive"><table class="table app-table"><tbody>
                        @foreach ($reviews as $r)
                            <tr><td class="fw-semibold">{{ $r->period }}</td><td>{{ $r->overall ? number_format($r->overall, 1).' / 5' : '—' }}</td>
                                <td><x-admin.badge :tone="\App\Models\Hrm\Review::STATUSES[$r->status]">{{ ucfirst($r->status) }}</x-admin.badge></td>
                                <td class="text-end"><a href="{{ route('hrm.reviews.show', $r) }}" class="btn btn-sm btn-light">Open</a></td></tr>
                        @endforeach
                    </tbody></table></div>
                </x-admin.card>
            @endif

            @can('hrm.manage_lifecycle')
                @foreach (['onboarding' => 'Onboarding checklist', 'offboarding' => 'Offboarding checklist'] as $type => $label)
                    @php $items = $checklists->get($type, collect()); @endphp
                    @continue($items->isEmpty())
                    <x-admin.card :title="$label" :subtitle="$items->whereNotNull('done_at')->count().' of '.$items->count().' done'">
                        <div class="d-grid gap-2">
                            @foreach ($items as $item)
                                <form method="POST" action="{{ route('hrm.lifecycle.items.toggle', $item) }}">@csrf
                                    <button class="btn btn-light w-100 justify-content-start text-start {{ $item->done_at ? 'text-body-secondary' : '' }}">
                                        <i class="bi {{ $item->done_at ? 'bi-check-circle-fill text-success' : 'bi-circle' }}"></i>
                                        <span class="{{ $item->done_at ? 'text-decoration-line-through' : '' }}">{{ $item->title }}</span>
                                        @if ($item->done_at)<span class="ms-auto small">{{ $item->done_by }} · {{ $item->done_at->format('d M') }}</span>@endif
                                    </button>
                                </form>
                            @endforeach
                        </div>
                        <x-slot:footer>
                            <form method="POST" action="{{ route('hrm.lifecycle.items.store', $employee) }}" class="d-flex gap-2 w-100">@csrf
                                <input type="hidden" name="type" value="{{ $type }}">
                                <input name="title" class="form-control" style="min-height:42px" placeholder="Add a step…" required>
                                <x-admin.button size="sm" icon="plus-lg">Add</x-admin.button>
                            </form>
                        </x-slot:footer>
                    </x-admin.card>
                @endforeach
            @endcan
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

            <x-admin.card title="Shift">
                <div class="fw-semibold">{{ $shift?->label() ?? 'Office hours ('.config('hrm.office_start').')' }}</div>
                <div class="small text-body-secondary">{{ $employee->shift_id ? 'Assigned' : 'Default shift' }}{{ $shift ? ' · '.$shift->grace_minutes.' min grace' : '' }}</div>
            </x-admin.card>

            @can('hrm.manage_lifecycle')
                <x-admin.card title="Employment status">
                    @if ($exit && $exit->status === 'in_progress')
                        <x-admin.badge tone="warning" icon="box-arrow-right">{{ \App\Models\Hrm\EmployeeExit::TYPES[$exit->type] }} in progress</x-admin.badge>
                        <div class="small mt-2">Last working day: <b>{{ $exit->last_working_day->format('d M Y') }}</b></div>
                        <a href="{{ route('hrm.lifecycle.index') }}" class="btn btn-sm btn-light mt-3 w-100">Manage exit</a>
                    @elseif ($exit && $exit->status === 'completed')
                        <x-admin.badge tone="danger">Left on {{ $exit->last_working_day->format('d M Y') }}</x-admin.badge>
                    @elseif ($employee->is_active)
                        <x-admin.badge tone="success">Active</x-admin.badge>
                        <button type="button" class="btn btn-sm btn-light text-danger w-100 mt-3" data-bs-toggle="modal" data-bs-target="#exitModal"><i class="bi bi-box-arrow-right"></i> Start exit / resignation</button>
                    @endif
                </x-admin.card>
            @endcan

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
@can('hrm.manage_lifecycle')
<div class="modal fade" id="exitModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:16px">
    <form method="POST" action="{{ route('hrm.lifecycle.exit.start', $employee) }}">@csrf
        <div class="modal-header"><h5 class="modal-title fw-bold">Start exit — {{ $employee->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <x-admin.select name="type" label="Type" :options="\App\Models\Hrm\EmployeeExit::TYPES" value="resignation" required />
            <div class="row g-3 mt-0">
                <div class="col-6"><x-admin.input name="notice_date" type="date" label="Notice date" :value="today()->toDateString()" required /></div>
                <div class="col-6"><x-admin.input name="last_working_day" type="date" label="Last working day" :value="today()->addMonth()->toDateString()" required /></div>
            </div>
            <div class="app-field"><label class="form-label">Reason <span class="opt">(optional)</span></label><textarea name="reason" class="form-control" rows="3"></textarea></div>
            <p class="form-text">An offboarding checklist is created. The login is removed when you complete the exit.</p>
        </div>
        <div class="modal-footer"><x-admin.button variant="light" type="button" data-bs-dismiss="modal">Cancel</x-admin.button><x-admin.button variant="danger" icon="box-arrow-right">Start Exit</x-admin.button></div>
    </form>
</div></div></div>
@endcan
@endsection
