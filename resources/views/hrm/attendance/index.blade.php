@extends('layouts.admin.main')
@section('title', 'Attendance')
@section('subtitle', 'HRM · Daily attendance')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Attendance" :subtitle="$date->format('l, d F Y')">
        <form method="GET" class="d-flex gap-2">
            <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control" style="width:170px" onchange="this.form.submit()">
            <select name="department" class="form-select" style="width:170px" onchange="this.form.submit()">
                <option value="">All departments</option>
                @foreach ($departments as $id => $n)<option value="{{ $id }}" @selected(request('department') == $id)>{{ $n }}</option>@endforeach
            </select>
        </form>
        <x-admin.button variant="light" :href="route('hrm.attendance.month', ['month' => $date->format('Y-m')])" icon="calendar3">Monthly View</x-admin.button>
    </x-admin.page-header>

    @if ($holiday || $isWeekend)
        <div class="app-alert app-alert--success"><i class="bi bi-sun"></i>{{ $holiday ? 'Holiday: '.$holiday->name : 'Weekend' }} — attendance is optional today.</div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach (\App\Models\Hrm\Attendance::STATUSES as $k => [$label, $tone])
            <x-admin.badge :tone="$tone">{{ $label }}: {{ $summary[$k] }}</x-admin.badge>
        @endforeach
        <x-admin.badge tone="muted">Not marked: {{ $employees->count() - $records->count() }}</x-admin.badge>
    </div>

    <form method="POST" action="{{ route('hrm.attendance.store') }}">
        @csrf <input type="hidden" name="date" value="{{ $date->toDateString() }}">
        <x-admin.card flush :title="'Employees ('.$employees->count().')'">
            @if ($canEdit)
                <x-slot:actions>
                    <button type="submit" form="markAll" class="btn btn-light btn-sm"><i class="bi bi-check2-all"></i> Mark unmarked as present</button>
                    <x-admin.button size="sm" icon="save">Save Attendance</x-admin.button>
                </x-slot:actions>
            @endif
            <div class="table-responsive"><table class="table app-table align-middle">
                <thead><tr><th>Employee</th><th style="width:170px">Status</th><th style="width:130px">Check in</th><th style="width:130px">Check out</th><th>Worked</th><th>Note</th></tr></thead>
                <tbody>
                @forelse ($employees as $e)
                    @php $r = $records->get($e->id); @endphp
                    <tr>
                        <td>@include('hrm.partials.person', ['e' => $e, 'link' => true])</td>
                        @if ($canEdit && $r?->status !== 'on_leave')
                            <td><select name="rows[{{ $e->id }}][status]" class="form-select" style="min-height:40px">
                                <option value="">— Not marked —</option>
                                @foreach (\App\Models\Hrm\Attendance::STATUSES as $k => [$label])
                                    @continue($k === 'on_leave')
                                    <option value="{{ $k }}" @selected($r?->status === $k)>{{ $label }}</option>
                                @endforeach
                            </select></td>
                            <td><input type="time" name="rows[{{ $e->id }}][check_in]" value="{{ $r?->check_in ? substr($r->check_in, 0, 5) : '' }}" class="form-control" style="min-height:40px"></td>
                            <td><input type="time" name="rows[{{ $e->id }}][check_out]" value="{{ $r?->check_out ? substr($r->check_out, 0, 5) : '' }}" class="form-control" style="min-height:40px"></td>
                            <td class="text-body-secondary">{{ $r?->workedLabel() ?? '—' }}</td>
                            <td><input name="rows[{{ $e->id }}][note]" value="{{ $r?->note }}" class="form-control" style="min-height:40px" placeholder="—"></td>
                        @else
                            <td>@if ($r)@include('hrm.partials.att-badge', ['status' => $r->status])@else<span class="text-body-secondary small">Not marked</span>@endif</td>
                            <td>{{ $r?->check_in ? substr($r->check_in, 0, 5) : '—' }}</td>
                            <td>{{ $r?->check_out ? substr($r->check_out, 0, 5) : '—' }}</td>
                            <td class="text-body-secondary">{{ $r?->workedLabel() ?? '—' }}</td>
                            <td class="small text-body-secondary">{{ $r?->note }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="6"><x-admin.empty icon="people" text="No employees." /></td></tr>
                @endforelse
                </tbody></table></div>
        </x-admin.card>
    </form>
    <form id="markAll" method="POST" action="{{ route('hrm.attendance.mark-all') }}">@csrf <input type="hidden" name="date" value="{{ $date->toDateString() }}"></form>
    <p class="form-text mt-3">Late and half day are worked out automatically from the times (office starts {{ config('hrm.office_start') }}, {{ config('hrm.late_grace_minutes') }} min grace).</p>
</div>
@endsection
