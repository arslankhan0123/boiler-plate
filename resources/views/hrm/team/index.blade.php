@extends('layouts.admin.main')
@section('title', 'My Team')
@section('subtitle', 'My HR · People who report to you')

@section('content')
<div class="app-content">
    <x-admin.page-header title="My Team" :subtitle="$team->count().' direct reports — from the organization chart.'">
        <x-admin.button variant="light" :href="route('hrm.reviews.index')" icon="graph-up-arrow">Reviews</x-admin.button>
    </x-admin.page-header>

    <div class="app-form-grid">
        <div class="d-grid gap-4">
            <x-admin.card flush :title="'Leave requests to approve ('.$pending->count().')'">
                <div class="table-responsive"><table class="table app-table">
                    <thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Days</th><th class="text-end"></th></tr></thead>
                    <tbody>
                    @forelse ($pending as $r)
                        <tr>
                            <td>@include('hrm.partials.person', ['e' => $r->employee, 'sub' => \Illuminate\Support\Str::limit($r->reason, 50)])</td>
                            <td>{{ $r->type->name ?? '—' }}</td>
                            <td class="text-nowrap">{{ $r->start_date->format('d M') }} – {{ $r->end_date->format('d M') }}</td>
                            <td>{{ $r->days }}</td>
                            <td class="text-end text-nowrap">
                                <form method="POST" action="{{ route('hrm.team.leaves.decide', [$r, 'approve']) }}" class="d-inline">@csrf
                                    <button class="btn btn-sm btn-primary"><i class="bi bi-check2"></i> Approve</button></form>
                                <form method="POST" action="{{ route('hrm.team.leaves.decide', [$r, 'reject']) }}" class="d-inline" onsubmit="const n = prompt('Reason (optional)'); if (n === null) return false; this.note.value = n;">@csrf
                                    <input type="hidden" name="note"><button class="btn btn-sm btn-light text-danger"><i class="bi bi-x-lg"></i></button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty icon="check2-all" text="Nothing waiting for you." /></td></tr>
                    @endforelse
                    </tbody></table></div>
            </x-admin.card>

            <x-admin.card flush title="Team today">
                <div class="table-responsive"><table class="table app-table">
                    <thead><tr><th>Employee</th><th>Status</th><th>In</th><th>Out</th><th>Last review</th><th class="text-end"></th></tr></thead>
                    <tbody>
                    @foreach ($team as $e)
                        @php $a = $today->get($e->id); $last = $reviews->get($e->id)?->first(); @endphp
                        <tr>
                            <td>@include('hrm.partials.person', ['e' => $e])</td>
                            <td>@if ($a)@include('hrm.partials.att-badge', ['status' => $a->status])@else<span class="small text-body-secondary">Not marked</span>@endif</td>
                            <td>{{ $a?->check_in ? \Illuminate\Support\Carbon::parse($a->check_in)->format('h:i A') : '—' }}</td>
                            <td>{{ $a?->check_out ? \Illuminate\Support\Carbon::parse($a->check_out)->format('h:i A') : '—' }}</td>
                            <td>{{ $last ? $last->period.' · '.number_format($last->overall ?? 0, 1) : '—' }}</td>
                            <td class="text-end"><a href="{{ route('hrm.reviews.create', ['employee' => $e->id]) }}" class="btn btn-sm btn-light"><i class="bi bi-star"></i> Review</a></td>
                        </tr>
                    @endforeach
                    </tbody></table></div>
            </x-admin.card>
        </div>
        <div class="app-aside">
            <x-admin.card title="Upcoming time off">
                <div class="d-grid gap-3">
                    @forelse ($upcoming as $r)
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div><div class="fw-semibold small">{{ $r->employee->name }}</div><div class="small text-body-secondary">{{ $r->type->name ?? '' }}</div></div>
                            <x-admin.badge tone="primary">{{ $r->start_date->format('d M') }} – {{ $r->end_date->format('d M') }}</x-admin.badge>
                        </div>
                    @empty
                        <div class="small text-body-secondary">No upcoming leave.</div>
                    @endforelse
                </div>
            </x-admin.card>
            <x-admin.tips :items="['You see people whose designation reports to yours in the org chart.', 'Approving leave marks their attendance automatically.']" />
        </div>
    </div>
</div>
@endsection
