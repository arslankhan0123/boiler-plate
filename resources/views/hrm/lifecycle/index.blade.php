@extends('layouts.admin.main')
@section('title', 'Onboarding & Exits')
@section('subtitle', 'HRM · Employee lifecycle')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Onboarding & Exits" subtitle="New joiners' onboarding and leavers' offboarding — open an employee to tick steps." />

    <div class="row g-4">
        <div class="col-xl-6">
            <x-admin.card flush :title="'Exits in progress ('.$exits->count().')'">
                <div class="table-responsive"><table class="table app-table align-middle">
                    <tbody>
                    @forelse ($exits as $x)
                        @php $p = $offboarding->get($x->employee_id); @endphp
                        <tr>
                            <td>@include('hrm.partials.person', ['e' => $x->employee, 'link' => true, 'sub' => \App\Models\Hrm\EmployeeExit::TYPES[$x->type]])</td>
                            <td><div class="small text-body-secondary">Last day</div><b class="{{ $x->last_working_day->isPast() ? 'text-danger' : '' }}">{{ $x->last_working_day->format('d M Y') }}</b></td>
                            <td style="min-width:120px"><div class="small text-body-secondary">Checklist</div>
                                @if ($p)<b>{{ $p->done }}/{{ $p->total }}</b>@else<x-admin.badge tone="success">Done</x-admin.badge>@endif</td>
                            <td class="text-end text-nowrap">
                                <form method="POST" action="{{ route('hrm.lifecycle.exit.complete', $x) }}" class="d-inline" onsubmit="return confirm('Complete the exit? The employee becomes inactive and their login is removed.')">@csrf
                                    @if (session('confirmExit') === $x->id)<input type="hidden" name="force" value="1">@endif
                                    <button class="btn btn-sm btn-primary">{{ session('confirmExit') === $x->id ? 'Complete anyway' : 'Complete' }}</button></form>
                                <form method="POST" action="{{ route('hrm.lifecycle.exit.withdraw', $x) }}" class="d-inline" onsubmit="return confirm('Withdraw this exit?')">@csrf
                                    <button class="btn btn-sm btn-light">Withdraw</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td><x-admin.empty icon="door-open" text="No exits in progress. Start one from an employee's profile." /></td></tr>
                    @endforelse
                    </tbody></table></div>
            </x-admin.card>
        </div>

        <div class="col-xl-6">
            <x-admin.card flush :title="'Onboarding in progress ('.$onboarding->count().')'">
                <div class="table-responsive"><table class="table app-table align-middle"><tbody>
                    @forelse ($onboarding as $e)
                        @php $pct = $e->progress->total ? $e->progress->done / $e->progress->total * 100 : 0; @endphp
                        <tr>
                            <td>@include('hrm.partials.person', ['e' => $e, 'link' => true])</td>
                            <td style="min-width:160px"><div class="small mb-1">{{ $e->progress->done }}/{{ $e->progress->total }} steps</div>
                                <div class="progress" style="height:6px"><div class="progress-bar bg-success" style="width:{{ $pct }}%"></div></div></td>
                            <td class="text-end"><a href="{{ route('hrm.employees.show', $e) }}" class="btn btn-sm btn-light">Open</a></td>
                        </tr>
                    @empty
                        <tr><td><x-admin.empty icon="person-check" text="Everyone is fully onboarded." /></td></tr>
                    @endforelse
                </tbody></table></div>
            </x-admin.card>

            @if ($newJoiners->isNotEmpty())
                <x-admin.card flush title="Recent joiners without a checklist" class="mt-4">
                    <div class="table-responsive"><table class="table app-table align-middle"><tbody>
                        @foreach ($newJoiners as $e)
                            <tr><td>@include('hrm.partials.person', ['e' => $e, 'sub' => 'Joined '.\Illuminate\Support\Carbon::parse($e->join_date)->format('d M Y')])</td>
                                <td class="text-end"><form method="POST" action="{{ route('hrm.lifecycle.onboarding', $e) }}">@csrf<button class="btn btn-sm btn-light"><i class="bi bi-list-check"></i> Start onboarding</button></form></td></tr>
                        @endforeach
                    </tbody></table></div>
                </x-admin.card>
            @endif
        </div>

        @if ($recentExits->isNotEmpty())
            <div class="col-12">
                <x-admin.card flush title="Recent exits">
                    <div class="table-responsive"><table class="table app-table"><thead><tr><th>Employee</th><th>Type</th><th>Last day</th><th>Status</th></tr></thead><tbody>
                        @foreach ($recentExits as $x)
                            <tr><td class="fw-semibold">{{ $x->employee->name ?? '—' }}</td><td>{{ \App\Models\Hrm\EmployeeExit::TYPES[$x->type] }}</td>
                                <td>{{ $x->last_working_day->format('d M Y') }}</td>
                                <td><x-admin.badge :tone="$x->status === 'completed' ? 'danger' : 'muted'">{{ ucfirst($x->status) }}</x-admin.badge></td></tr>
                        @endforeach
                    </tbody></table></div>
                </x-admin.card>
            </div>
        @endif
    </div>
</div>
@endsection
