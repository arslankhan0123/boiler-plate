@extends('layouts.admin.main')
@section('title', 'Leave Requests')
@section('subtitle', 'HRM · Leave')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Leave Requests" subtitle="Review and approve your team's time off.">
        <x-admin.button type="button" icon="plus-lg" data-bs-toggle="modal" data-bs-target="#addLeave">Record Leave</x-admin.button>
    </x-admin.page-header>

    <div class="d-flex gap-2 flex-wrap mb-3">
        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled', 'all' => 'All'] as $k => $label)
            <a href="{{ route('hrm.leaves.index', ['status' => $k]) }}" class="btn btn-sm {{ $status === $k ? 'btn-primary' : 'btn-light' }}">
                {{ $label }} @if ($k !== 'all')<span class="opacity-75">({{ $counts[$k] ?? 0 }})</span>@endif</a>
        @endforeach
    </div>

    <x-admin.card flush>
        <div class="table-responsive"><table class="table app-table table-hover">
            <thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Days</th><th>Reason</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($requests as $r)
                <tr>
                    <td>@include('hrm.partials.person', ['e' => $r->employee, 'link' => true, 'sub' => $r->employee->department->name ?? ''])</td>
                    <td><span class="d-inline-block rounded-circle me-1" style="width:8px;height:8px;background:{{ $r->type->color ?? '#999' }}"></span>{{ $r->type->name ?? '—' }}</td>
                    <td class="text-nowrap">{{ $r->start_date->format('d M') }} – {{ $r->end_date->format('d M Y') }}</td>
                    <td>{{ $r->days }}</td>
                    <td class="small text-body-secondary" style="max-width:240px">{{ \Illuminate\Support\Str::limit($r->reason, 80) }}</td>
                    <td>@include('hrm.partials.leave-badge', ['status' => $r->status])
                        @if ($r->review_note)<div class="small text-body-secondary mt-1">{{ $r->review_note }}</div>@endif</td>
                    <td class="text-end text-nowrap">
                        @if ($r->status === 'pending')
                            <form method="POST" action="{{ route('hrm.leaves.approve', $r) }}" class="d-inline">@csrf
                                <button class="btn btn-sm btn-primary"><i class="bi bi-check2"></i> Approve</button></form>
                            <form method="POST" action="{{ route('hrm.leaves.reject', $r) }}" class="d-inline" onsubmit="const n = prompt('Reason for rejection (optional)'); if (n === null) return false; this.note.value = n;">@csrf
                                <input type="hidden" name="note"><button class="btn btn-sm btn-light text-danger"><i class="bi bi-x-lg"></i> Reject</button></form>
                        @elseif ($r->status === 'approved')
                            <form method="POST" action="{{ route('hrm.leaves.reject', $r) }}" class="d-inline" onsubmit="return confirm('Revoke this approved leave?')">@csrf
                                <button class="btn btn-sm btn-light">Revoke</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><x-admin.empty icon="calendar-check" text="Nothing here." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($requests->hasPages())
            <x-slot:footer>{{ $requests->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>

<div class="modal fade" id="addLeave" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:16px">
    <form method="POST" action="{{ route('hrm.leaves.store') }}">@csrf
        <div class="modal-header"><h5 class="modal-title fw-bold">Record leave for an employee</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <x-admin.select name="employee_id" label="Employee" :options="$employees" placeholder="Select employee" required />
            <x-admin.select name="leave_type_id" label="Leave type" :options="$types->pluck('name', 'id')" placeholder="Select type" required />
            <div class="row g-3 mt-0"><div class="col-6"><x-admin.input name="start_date" type="date" label="From" required /></div>
                <div class="col-6"><x-admin.input name="end_date" type="date" label="To" required /></div></div>
            <div class="app-field"><label class="form-label">Reason <span class="opt">(optional)</span></label><textarea name="reason" class="form-control" rows="2"></textarea></div>
            <p class="form-text">Recorded leave is approved immediately and marked on attendance.</p>
        </div>
        <div class="modal-footer"><x-admin.button variant="light" type="button" data-bs-dismiss="modal">Cancel</x-admin.button><x-admin.button icon="check2">Save</x-admin.button></div>
    </form>
</div></div></div>
@if ($errors->any())<script>document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal('#addLeave').show());</script>@endif
@endsection
