@extends('layouts.admin.main')
@section('title', 'HR Dashboard')
@section('subtitle', 'HRM · '.now()->format('l, d F Y'))

@section('content')
<div class="app-content">
    <x-admin.page-header title="HR Dashboard" subtitle="Today at a glance — attendance, leave, people and hiring.">
        @can('hrm.manage_employees')<x-admin.button :href="route('hrm.employees.create')" icon="person-plus">Add Employee</x-admin.button>@endcan
    </x-admin.page-header>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-2"><x-admin.stat label="Employees" :value="$stats['employees']" icon="people" tone="primary" :href="route('hrm.employees.index')" /></div>
        <div class="col-6 col-xl-2"><x-admin.stat label="Present" :value="$stats['present']" icon="person-check" tone="success" /></div>
        <div class="col-6 col-xl-2"><x-admin.stat label="Late" :value="$stats['late']" icon="alarm" tone="warning" /></div>
        <div class="col-6 col-xl-2"><x-admin.stat label="On leave" :value="$stats['on_leave']" icon="airplane" tone="info" /></div>
        <div class="col-6 col-xl-2"><x-admin.stat label="Not marked" :value="$stats['not_marked']" icon="question-circle" tone="danger" /></div>
        <div class="col-6 col-xl-2"><x-admin.stat label="Joined this month" :value="$stats['joined_month']" icon="stars" tone="primary" /></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8 d-grid gap-4">
            @if ($pendingLeaves->isNotEmpty())
                <x-admin.card flush :title="'Pending leave ('.$pendingLeaves->count().')'">
                    <x-slot:actions><a href="{{ route('hrm.leaves.index') }}" class="small">View all →</a></x-slot:actions>
                    <div class="table-responsive"><table class="table app-table"><tbody>
                        @foreach ($pendingLeaves as $r)
                            <tr><td>@include('hrm.partials.person', ['e' => $r->employee])</td><td>{{ $r->type->name ?? '' }}</td>
                                <td class="text-nowrap">{{ $r->start_date->format('d M') }} – {{ $r->end_date->format('d M') }} · {{ $r->days }}d</td>
                                <td class="text-end"><form method="POST" action="{{ route('hrm.leaves.approve', $r) }}">@csrf<button class="btn btn-sm btn-primary">Approve</button></form></td></tr>
                        @endforeach
                    </tbody></table></div>
                </x-admin.card>
            @endif

            <div class="row g-4">
                <div class="col-md-6">
                    <x-admin.card title="🎂 Upcoming birthdays" class="h-100">
                        <div class="d-grid gap-3">
                            @forelse ($birthdays as $e)
                                <div class="d-flex justify-content-between align-items-center">@include('hrm.partials.person', ['e' => $e, 'sub' => $e->department->name ?? ''])
                                    <x-admin.badge :tone="$e->next_date->isToday() ? 'success' : 'muted'">{{ $e->next_date->isToday() ? 'Today!' : $e->next_date->format('d M') }}</x-admin.badge></div>
                            @empty<div class="small text-body-secondary">None in the next 30 days.</div>@endforelse
                        </div>
                    </x-admin.card>
                </div>
                <div class="col-md-6">
                    <x-admin.card title="🎉 Work anniversaries" class="h-100">
                        <div class="d-grid gap-3">
                            @forelse ($anniversaries as $e)
                                <div class="d-flex justify-content-between align-items-center">@include('hrm.partials.person', ['e' => $e, 'sub' => $e->years.' '.\Illuminate\Support\Str::plural('year', $e->years)])
                                    <x-admin.badge :tone="$e->next_date->isToday() ? 'success' : 'muted'">{{ $e->next_date->isToday() ? 'Today!' : $e->next_date->format('d M') }}</x-admin.badge></div>
                            @empty<div class="small text-body-secondary">None in the next 30 days.</div>@endforelse
                        </div>
                    </x-admin.card>
                </div>
            </div>

            @if ($interviews->isNotEmpty())
                <x-admin.card flush title="Interviews this week">
                    <div class="table-responsive"><table class="table app-table"><tbody>
                        @foreach ($interviews as $a)
                            <tr><td class="fw-semibold"><a href="{{ route('hrm.applications.show', $a) }}" class="text-body text-decoration-none">{{ $a->name }}</a>
                                <div class="small text-body-secondary">{{ $a->job->title ?? '' }}</div></td>
                                <td>{{ $a->interview_at->format('D d M, h:i A') }}</td><td class="small">{{ $a->interviewer->name ?? 'No interviewer' }}</td></tr>
                        @endforeach
                    </tbody></table></div>
                </x-admin.card>
            @endif

            @if ($announcements->isNotEmpty())@include('hrm.announcements._feed', ['notices' => $announcements])@endif
        </div>

        <div class="col-xl-4 d-grid gap-4 align-content-start">
            <x-admin.card title="Out today">
                <div class="d-grid gap-3">
                    @forelse ($onLeaveToday as $r)
                        <div class="d-flex justify-content-between align-items-center">@include('hrm.partials.person', ['e' => $r->employee, 'sub' => $r->type->name ?? ''])
                            <span class="small text-body-secondary">until {{ $r->end_date->format('d M') }}</span></div>
                    @empty<div class="small text-body-secondary">Everyone is in today.</div>@endforelse
                </div>
            </x-admin.card>

            @if ($expiringDocs->isNotEmpty())
                <x-admin.card title="⚠ Documents expiring">
                    <div class="d-grid gap-2">
                        @foreach ($expiringDocs as $d)
                            <a href="{{ route('hrm.employees.show', $d->employee) }}" class="d-flex justify-content-between text-decoration-none text-body small">
                                <span><b>{{ $d->employee->name }}</b> · {{ $d->name }}</span>
                                <x-admin.badge :tone="\Illuminate\Support\Carbon::parse($d->expiry_date)->isPast() ? 'danger' : 'warning'">{{ \Illuminate\Support\Carbon::parse($d->expiry_date)->format('d M Y') }}</x-admin.badge></a>
                        @endforeach
                    </div>
                </x-admin.card>
            @endif

            <x-admin.card title="Hiring">
                <div class="d-flex justify-content-between mb-2"><span class="text-body-secondary">Open jobs</span><b>{{ $recruitment['open_jobs'] }}</b></div>
                <div class="d-flex justify-content-between"><span class="text-body-secondary">New applications</span><b>{{ $recruitment['new_applications'] }}</b></div>
                @can('hrm.manage_recruitment')<a href="{{ route('hrm.applications.index', ['status' => 'new']) }}" class="btn btn-sm btn-light w-100 mt-3">Review applications</a>@endcan
            </x-admin.card>

            @if ($exits->isNotEmpty() || $reviewsPending)
                <x-admin.card title="Lifecycle">
                    @foreach ($exits as $x)
                        <div class="d-flex justify-content-between small mb-2"><span><b>{{ $x->employee->name ?? '' }}</b> leaving</span><span>{{ $x->last_working_day->format('d M') }}</span></div>
                    @endforeach
                    @if ($reviewsPending)<div class="small text-body-secondary">{{ $reviewsPending }} reviews awaiting acknowledgement</div>@endif
                </x-admin.card>
            @endif

            <x-admin.card title="Headcount by department">
                @php $max = max(1, $byDepartment->max()); @endphp
                @foreach ($byDepartment as $dept => $n)
                    <div class="mb-2"><div class="d-flex justify-content-between small"><span>{{ $dept }}</span><b>{{ $n }}</b></div>
                        <div class="progress" style="height:6px"><div class="progress-bar" style="width:{{ $n / $max * 100 }}%;background:var(--app-btn-grad)"></div></div></div>
                @endforeach
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
