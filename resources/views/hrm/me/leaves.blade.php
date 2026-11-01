@extends('layouts.admin.main')
@section('title', 'My Leaves')
@section('subtitle', 'My HR')

@section('content')
<div class="app-content">
    <x-admin.page-header title="My Leaves" :subtitle="'Balance and requests for '.$year">
        <form method="GET"><select name="year" class="form-select" style="width:110px" onchange="this.form.submit()">
            @foreach (range(now()->year + 1, now()->year - 3) as $y)<option @selected($y === $year)>{{ $y }}</option>@endforeach</select></form>
    </x-admin.page-header>

    @include('hrm.partials.balances', ['balances' => $balances])

    <div class="app-form-grid mt-4">
        <x-admin.card flush title="My requests">
            <div class="table-responsive"><table class="table app-table">
                <thead><tr><th>Type</th><th>Dates</th><th>Days</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse ($requests as $r)
                    <tr><td>{{ $r->type->name ?? '—' }}<div class="small text-body-secondary">{{ \Illuminate\Support\Str::limit($r->reason, 50) }}</div></td>
                        <td class="text-nowrap">{{ $r->start_date->format('d M') }} – {{ $r->end_date->format('d M') }}</td>
                        <td>{{ $r->days }}</td>
                        <td>@include('hrm.partials.leave-badge', ['status' => $r->status])@if ($r->review_note)<div class="small text-body-secondary">{{ $r->review_note }}</div>@endif</td>
                        <td class="text-end">
                            @if ($r->status === 'pending' || ($r->status === 'approved' && $r->start_date->isFuture()))
                                <form method="POST" action="{{ route('hrm.me.leaves.cancel', $r) }}" onsubmit="return confirm('Cancel this leave request?')">@csrf
                                    <button class="btn btn-sm btn-light">Cancel</button></form>
                            @endif
                        </td></tr>
                @empty
                    <tr><td colspan="5"><x-admin.empty icon="calendar" text="No leave requests yet." /></td></tr>
                @endforelse
                </tbody></table></div>
        </x-admin.card>

        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Apply for leave</h3>
                <form method="POST" action="{{ route('hrm.me.leaves.apply') }}">@csrf
                    <x-admin.select name="leave_type_id" label="Leave type" :options="$types->pluck('name', 'id')" placeholder="Select type" required />
                    <div class="row g-3 mt-0">
                        <div class="col-6"><x-admin.input name="start_date" type="date" label="From" :min="today()->toDateString()" required /></div>
                        <div class="col-6"><x-admin.input name="end_date" type="date" label="To" :min="today()->toDateString()" required /></div>
                    </div>
                    <label class="d-flex align-items-center gap-2 small mt-3"><input type="checkbox" class="form-check-input mt-0" name="half_day" value="1" @checked(old('half_day'))> Half day (single date only)</label>
                    <div class="app-field"><label class="form-label">Reason <span class="opt">(optional)</span></label>
                        <textarea name="reason" class="form-control" rows="3">{{ old('reason') }}</textarea></div>
                    <x-admin.button class="w-100 mt-4" icon="send">Submit Request</x-admin.button>
                </form>
                <p class="form-text mt-3 mb-0">Weekends and public holidays are not counted.</p>
            </div></div>
        </div>
    </div>
</div>
@endsection
