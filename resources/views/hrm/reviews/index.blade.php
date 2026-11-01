@extends('layouts.admin.main')
@section('title', 'Performance Reviews')
@section('subtitle', 'HRM · Performance')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Performance Reviews" subtitle="Score employees, share the review, and they acknowledge it from My HR.">
        <form method="GET" action="{{ route('hrm.reviews.create') }}" class="d-flex gap-2">
            <select name="employee" class="form-select" style="width:240px" required>
                <option value="">Review an employee…</option>
                @foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach
            </select>
            <x-admin.button icon="plus-lg">New Review</x-admin.button>
        </form>
    </x-admin.page-header>

    <x-admin.card flush>
        <x-slot:actions>
            @foreach (['' => 'All', 'draft' => 'Draft', 'shared' => 'Awaiting acknowledgement', 'acknowledged' => 'Acknowledged'] as $k => $l)
                <a href="{{ route('hrm.reviews.index', array_filter(['status' => $k])) }}" class="btn btn-sm {{ request('status', '') === $k ? 'btn-primary' : 'btn-light' }}">{{ $l }}</a>
            @endforeach
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover">
            <thead><tr><th>Employee</th><th>Period</th><th>Overall</th><th>Reviewer</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($reviews as $r)
                <tr>
                    <td>@include('hrm.partials.person', ['e' => $r->employee, 'link' => true, 'sub' => $r->employee->designation->name ?? ''])</td>
                    <td class="fw-semibold">{{ $r->period }}</td>
                    <td>@if ($r->overall)<span class="text-warning">{!! str_repeat('<i class="bi bi-star-fill"></i>', (int) round($r->overall)) !!}</span> <b>{{ number_format($r->overall, 1) }}</b>@else — @endif</td>
                    <td class="text-body-secondary">{{ $r->reviewer_name }}</td>
                    <td><x-admin.badge :tone="\App\Models\Hrm\Review::STATUSES[$r->status]">{{ ucfirst($r->status) }}</x-admin.badge></td>
                    <td class="text-end"><a href="{{ route('hrm.reviews.show', $r) }}" class="btn btn-sm btn-light">Open <i class="bi bi-arrow-right"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-admin.empty icon="graph-up-arrow" text="No reviews yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($reviews->hasPages())
            <x-slot:footer>{{ $reviews->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
