@extends('layouts.admin.main')
@section('title', 'Jobs & Careers')
@section('subtitle', 'HRM · Recruitment')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Jobs & Careers" subtitle="Post openings — open jobs appear on your public careers page.">
        <x-admin.button variant="light" :href="$careersUrl" icon="box-arrow-up-right" target="_blank">Careers Page</x-admin.button>
        <x-admin.button :href="route('hrm.jobs.create')" icon="plus-lg">Post a Job</x-admin.button>
    </x-admin.page-header>

    <div class="app-card mb-4"><div class="app-card__body d-flex flex-wrap align-items-center gap-3">
        <i class="bi bi-link-45deg fs-4 text-primary"></i>
        <div class="flex-grow-1"><div class="fw-semibold">Your public careers page</div><div class="small text-body-secondary">Share this link — candidates apply without an account.</div></div>
        <input class="form-control" style="max-width:420px" value="{{ $careersUrl }}" readonly id="careersUrl">
        <button class="btn btn-light" type="button" onclick="navigator.clipboard.writeText(document.getElementById('careersUrl').value); Toast.success('Link copied')"><i class="bi bi-clipboard"></i> Copy</button>
    </div></div>

    <x-admin.card flush :title="'Jobs ('.$jobs->total().')'">
        <x-slot:actions>
            @foreach (['' => 'All', 'open' => 'Open', 'draft' => 'Draft', 'closed' => 'Closed'] as $k => $l)
                <a href="{{ route('hrm.jobs.index', array_filter(['status' => $k])) }}" class="btn btn-sm {{ request('status', '') === $k ? 'btn-primary' : 'btn-light' }}">{{ $l }}</a>
            @endforeach
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover">
            <thead><tr><th>Job</th><th>Type</th><th>Positions</th><th>Applications</th><th>Closes</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($jobs as $job)
                <tr>
                    <td><div class="fw-semibold">{{ $job->title }}</div><div class="small text-body-secondary">{{ $job->department->name ?? '—' }} · {{ $job->location ?: 'Any location' }}</div></td>
                    <td>{{ config('hrm.employment_types')[$job->employment_type] ?? $job->employment_type }}</td>
                    <td>{{ $job->positions }}</td>
                    <td><a href="{{ route('hrm.applications.index', ['job' => $job->id]) }}" class="text-decoration-none">{{ $job->applications_count }}</a>
                        @if ($job->new_count)<x-admin.badge tone="primary">{{ $job->new_count }} new</x-admin.badge>@endif</td>
                    <td>{{ $job->closes_at?->format('d M Y') ?? '—' }}</td>
                    <td><x-admin.badge :tone="\App\Models\Hrm\Job::STATUSES[$job->status]">{{ ucfirst($job->status) }}</x-admin.badge></td>
                    <td class="text-end text-nowrap">
                        @if ($job->status === 'open')<a href="{{ route('careers.show', [tenant('id'), $job->slug]) }}" target="_blank" class="btn btn-action" title="Public page"><i class="bi bi-box-arrow-up-right"></i></a>@endif
                        <a href="{{ route('hrm.jobs.edit', $job) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                        <x-admin.delete-button :action="route('hrm.jobs.destroy', $job)" confirm="Delete this job?" />
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><x-admin.empty icon="briefcase" text="No jobs posted yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($jobs->hasPages())
            <x-slot:footer>{{ $jobs->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
