@extends('layouts.admin.main')
@section('title', 'Monthly Attendance')
@section('subtitle', 'HRM · Attendance')
@php
    $days = $month->daysInMonth;
    $codes = ['present' => ['P', '#059669'], 'late' => ['L', '#d97706'], 'half_day' => ['H', '#d97706'], 'absent' => ['A', '#e11d48'], 'on_leave' => ['LV', '#6366f1'], 'holiday' => ['HD', '#94a3b8']];
@endphp

@section('content')
<div class="app-content">
    <x-admin.page-header title="Monthly Attendance" :subtitle="$month->format('F Y')" :back="route('hrm.attendance.index')">
        <form method="GET" class="d-flex gap-2">
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control" style="width:170px" onchange="this.form.submit()">
            <select name="department" class="form-select" style="width:170px" onchange="this.form.submit()">
                <option value="">All departments</option>
                @foreach ($departments as $id => $n)<option value="{{ $id }}" @selected(request('department') == $id)>{{ $n }}</option>@endforeach
            </select>
        </form>
    </x-admin.page-header>

    <div class="d-flex gap-3 flex-wrap small mb-3">
        @foreach ($codes as $k => [$c, $color])<span><b style="color:{{ $color }}">{{ $c }}</b> {{ \App\Models\Hrm\Attendance::STATUSES[$k][0] }}</span>@endforeach
    </div>

    <x-admin.card flush>
        <div class="table-responsive"><table class="table app-table table-sm att-grid mb-0">
            <thead><tr><th class="sticky-col">Employee</th>
                @for ($d = 1; $d <= $days; $d++)
                    @php $date = $month->copy()->day($d); $off = in_array($date->dayOfWeek, config('hrm.weekend_days')) || $holidays->has($d); @endphp
                    <th class="text-center {{ $off ? 'is-off' : '' }}" title="{{ $holidays->get($d)?->name }}">{{ $d }}<div class="fw-normal" style="font-size:.6rem">{{ $date->format('D')[0] }}</div></th>
                @endfor
                <th class="text-center">P</th><th class="text-center">A</th><th class="text-center">LV</th></tr></thead>
            <tbody>
            @forelse ($employees as $e)
                @php $row = $records->get($e->id, collect()); @endphp
                <tr>
                    <td class="sticky-col fw-semibold text-nowrap">{{ $e->name }}</td>
                    @for ($d = 1; $d <= $days; $d++)
                        @php $r = $row->get($d); $date = $month->copy()->day($d); $off = in_array($date->dayOfWeek, config('hrm.weekend_days')) || $holidays->has($d); @endphp
                        <td class="text-center {{ $off ? 'is-off' : '' }}">@if ($r)<b style="color:{{ $codes[$r->status][1] ?? '#999' }}" title="{{ $r->check_in }} – {{ $r->check_out }}">{{ $codes[$r->status][0] ?? '?' }}</b>@endif</td>
                    @endfor
                    <td class="text-center fw-semibold text-success">{{ $row->whereIn('status', ['present', 'late', 'half_day'])->count() }}</td>
                    <td class="text-center fw-semibold text-danger">{{ $row->where('status', 'absent')->count() }}</td>
                    <td class="text-center fw-semibold" style="color:#6366f1">{{ $row->where('status', 'on_leave')->count() }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $days + 4 }}"><x-admin.empty icon="people" text="No employees." /></td></tr>
            @endforelse
            </tbody></table></div>
    </x-admin.card>
</div>
@endsection

@push('styles')
<style>
    .att-grid th, .att-grid td { padding: .5rem .35rem !important; font-size: .78rem; }
    .att-grid .is-off { background: #f1f5f9 !important; }
    .att-grid .sticky-col { position: sticky; left: 0; background: var(--app-surface); z-index: 1; padding-left: 1rem !important; min-width: 170px; }
</style>
@endpush
