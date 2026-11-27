{{-- Check-in / check-out card. Needs $today (Attendance|null). --}}
<div class="app-card checkin-card"><div class="app-card__body d-flex flex-wrap align-items-center gap-3">
    <div class="app-stat__icon tone-primary" style="width:52px;height:52px"><i class="bi bi-clock-history"></i></div>
    <div class="flex-grow-1">
        <div class="small text-body-secondary">{{ now()->format('l, d F') }}</div>
        @if (! $today || ! $today->check_in)
            <div class="fw-bold fs-5">{{ $today?->status === 'on_leave' ? 'You are on leave today' : 'Not checked in yet' }}</div>
        @elseif (! $today->check_out)
            <div class="fw-bold fs-5">Checked in at {{ \Illuminate\Support\Carbon::parse($today->check_in)->format('h:i A') }}</div>
        @else
            <div class="fw-bold fs-5">Done for today — {{ $today->workedLabel() }}</div>
        @endif
        @if ($today)<div class="mt-1">@include('hrm.partials.att-badge', ['status' => $today->status])</div>@endif
    </div>
    @if ((! $today || ! $today->check_in) && $today?->status !== 'on_leave')
        <form method="POST" action="{{ route('hrm.me.check-in') }}">@csrf<x-admin.button class="btn-lg-app" icon="box-arrow-in-right">Check In</x-admin.button></form>
    @elseif ($today && $today->check_in && ! $today->check_out)
        <form method="POST" action="{{ route('hrm.me.check-out') }}">@csrf<x-admin.button class="btn-lg-app" variant="danger" icon="box-arrow-right">Check Out</x-admin.button></form>
    @endif
</div></div>
