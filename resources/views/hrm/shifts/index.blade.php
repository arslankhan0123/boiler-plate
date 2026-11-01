@extends('layouts.admin.main')
@section('title', 'Shifts')
@section('subtitle', 'HRM · Attendance')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Shifts" subtitle="Working hours per shift. Late and half day are worked out from the employee's shift." />
    <div class="app-form-grid">
        <x-admin.card flush :title="'Shifts ('.$shifts->count().')'">
            <div class="table-responsive"><table class="table app-table table-hover">
                <thead><tr><th>Shift</th><th>Hours</th><th>Grace</th><th>Half day under</th><th>Employees</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse ($shifts as $s)
                    <tr>
                        <td class="fw-semibold">{{ $s->name }} @if ($s->is_default)<x-admin.badge tone="primary">Default</x-admin.badge>@endif</td>
                        <td>{{ substr($s->start_time, 0, 5) }} – {{ substr($s->end_time, 0, 5) }}
                            @if ($s->end_time < $s->start_time)<x-admin.badge tone="muted" icon="moon">Night</x-admin.badge>@endif</td>
                        <td>{{ $s->grace_minutes }} min</td>
                        <td>{{ intdiv($s->half_day_minutes, 60) }}h {{ $s->half_day_minutes % 60 }}m</td>
                        <td>{{ $counts[$s->id] ?? 0 }}@if ($s->is_default && $unassigned) <span class="small text-body-secondary">+{{ $unassigned }} unassigned</span>@endif</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('hrm.shifts.index', ['edit' => $s->id]) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                            <x-admin.delete-button :action="route('hrm.shifts.destroy', $s)" confirm="Delete this shift?" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-admin.empty icon="clock" :text="'No shifts yet — everyone uses office hours ('.config('hrm.office_start').').'" /></td></tr>
                @endforelse
                </tbody></table></div>
        </x-admin.card>
        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">{{ $editing ? 'Edit shift' : 'New shift' }}</h3>
                <form method="POST" action="{{ $editing ? route('hrm.shifts.update', $editing) : route('hrm.shifts.store') }}">@csrf @if ($editing) @method('PUT') @endif
                    <x-admin.input name="name" label="Name" :value="$editing?->name" placeholder="e.g. Morning, Night" required />
                    <div class="row g-3 mt-0">
                        <div class="col-6"><x-admin.input name="start_time" type="time" label="Starts" :value="$editing ? substr($editing->start_time, 0, 5) : '09:00'" required /></div>
                        <div class="col-6"><x-admin.input name="end_time" type="time" label="Ends" :value="$editing ? substr($editing->end_time, 0, 5) : '18:00'" required /></div>
                        <div class="col-6"><x-admin.input name="grace_minutes" type="number" min="0" label="Grace (min)" :value="$editing?->grace_minutes ?? 15" required /></div>
                        <div class="col-6"><x-admin.input name="half_day_minutes" type="number" min="0" label="Half day under (min)" :value="$editing?->half_day_minutes ?? 240" required /></div>
                    </div>
                    <label class="d-flex align-items-center gap-2 small mt-3"><input type="checkbox" class="form-check-input mt-0" name="is_default" value="1" @checked(old('is_default', $editing?->is_default))> Default shift (for employees without one)</label>
                    <div class="d-flex gap-2 mt-4">
                        <x-admin.button icon="check2">{{ $editing ? 'Save' : 'Add Shift' }}</x-admin.button>
                        @if ($editing)<x-admin.button variant="light" :href="route('hrm.shifts.index')">Cancel</x-admin.button>@endif
                    </div>
                </form>
                <p class="form-text mt-3 mb-0">Assign shifts on each employee's profile. A shift that ends before it starts runs overnight.</p>
            </div></div>
        </div>
    </div>
</div>
@endsection
