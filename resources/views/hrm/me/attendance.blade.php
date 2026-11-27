@extends('layouts.admin.main')
@section('title', 'My Attendance')
@section('subtitle', 'My HR')

@section('content')
<div class="app-content">
    <x-admin.page-header title="My Attendance" :subtitle="$month->format('F Y')">
        <form method="GET"><input type="month" name="month" value="{{ $month->format('Y-m') }}" max="{{ now()->format('Y-m') }}" class="form-control" style="width:170px" onchange="this.form.submit()"></form>
    </x-admin.page-header>

    @include('hrm.me._checkin', ['today' => $today])

    <div class="d-flex flex-wrap gap-2 my-4">
        @foreach (\App\Models\Hrm\Attendance::STATUSES as $k => [$label, $tone])<x-admin.badge :tone="$tone">{{ $label }}: {{ $summary[$k] }}</x-admin.badge>@endforeach
    </div>

    <x-admin.card flush>
        <div class="table-responsive"><table class="table app-table">
            <thead><tr><th>Date</th><th>Check in</th><th>Check out</th><th>Worked</th><th>Status</th><th>Note</th></tr></thead>
            <tbody>
            @forelse ($records as $a)
                <tr><td class="fw-semibold">{{ $a->date->format('D, d M') }}</td>
                    <td>{{ $a->check_in ? \Illuminate\Support\Carbon::parse($a->check_in)->format('h:i A') : '—' }}</td>
                    <td>{{ $a->check_out ? \Illuminate\Support\Carbon::parse($a->check_out)->format('h:i A') : '—' }}</td>
                    <td>{{ $a->workedLabel() }}</td><td>@include('hrm.partials.att-badge', ['status' => $a->status])</td>
                    <td class="small text-body-secondary">{{ $a->note }}</td></tr>
            @empty
                <tr><td colspan="6"><x-admin.empty icon="clock" text="No attendance recorded this month." /></td></tr>
            @endforelse
            </tbody></table></div>
    </x-admin.card>
</div>
@endsection
