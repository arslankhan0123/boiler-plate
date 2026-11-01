@extends('layouts.admin.main')
@php $statuses = config('hrm.application_statuses'); @endphp
@section('title', 'Applications')
@section('subtitle', 'HRM · Recruitment')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Applications" subtitle="Candidates from your careers page — move them through the pipeline.">
        <x-admin.button variant="light" :href="route('hrm.jobs.index')" icon="briefcase">Jobs</x-admin.button>
    </x-admin.page-header>

    <div class="row g-3 mb-4">
        @foreach ($statuses as $k => [$label, $tone])
            <div class="col-6 col-md-4 col-xl-2">
                <a href="{{ route('hrm.applications.index', array_filter(['status' => $k, 'job' => request('job')])) }}" class="app-card d-block text-decoration-none {{ request('status') === $k ? 'border-primary' : '' }}">
                    <div class="app-card__body py-3"><div class="small text-body-secondary">{{ $label }}</div><div class="h4 fw-bold mb-0 text-body">{{ $counts[$k] ?? 0 }}</div></div>
                </a>
            </div>
        @endforeach
    </div>

    <x-admin.card flush :title="'Candidates ('.$applications->total().')'">
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <select name="job" class="form-select" style="width:220px;min-height:42px" onchange="this.form.submit()">
                    <option value="">All jobs</option>
                    @foreach ($jobs as $id => $t)<option value="{{ $id }}" @selected(request('job') == $id)>{{ $t }}</option>@endforeach
                </select>
                <div class="input-icon" style="width:220px"><i class="bi bi-search"></i>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control" style="min-height:42px" placeholder="Name or email…"></div>
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover">
            <thead><tr><th>Candidate</th><th>Job</th><th>Applied</th><th>Rating</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($applications as $a)
                <tr>
                    <td><div class="d-flex align-items-center gap-3"><span class="app-avatar">{{ \App\Support\Hrm\Hrm::initials($a->name) }}</span>
                        <div><a href="{{ route('hrm.applications.show', $a) }}" class="fw-semibold text-body text-decoration-none">{{ $a->name }}</a><div class="small text-body-secondary">{{ $a->email }}</div></div></div></td>
                    <td>{{ $a->job->title ?? '—' }}</td>
                    <td class="text-body-secondary">{{ $a->created_at->diffForHumans() }}</td>
                    <td class="text-warning">{!! $a->rating ? str_repeat('<i class="bi bi-star-fill"></i>', $a->rating) : '<span class="text-body-secondary">—</span>' !!}</td>
                    <td>@php [$l, $t] = $statuses[$a->status] ?? [$a->status, 'muted']; @endphp<x-admin.badge :tone="$t">{{ $l }}</x-admin.badge></td>
                    <td class="text-end"><a href="{{ route('hrm.applications.show', $a) }}" class="btn btn-sm btn-light">Review <i class="bi bi-arrow-right"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-admin.empty icon="inbox" text="No applications yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($applications->hasPages())
            <x-slot:footer>{{ $applications->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
