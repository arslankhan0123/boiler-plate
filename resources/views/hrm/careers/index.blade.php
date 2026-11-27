@extends('hrm.careers.layout')
@section('title', 'Open positions')
@section('hero')
    <div class="small text-uppercase fw-semibold opacity-75 mb-2">Careers</div>
    <h1 class="display-6">Join {{ $tenant->name }}</h1>
    <p class="opacity-75 mb-0">{{ $jobs->count() }} open {{ \Illuminate\Support\Str::plural('position', $jobs->count()) }}. Find your next role with us.</p>
@endsection
@section('content')
    <div class="app-card overflow-hidden">
        @forelse ($jobs as $job)
            <a href="{{ route('careers.show', [$tenant->id, $job->slug]) }}" class="c-job">
                <span class="app-tile__icon tone-primary"><i class="bi bi-briefcase"></i></span>
                <div class="flex-grow-1">
                    <div class="fw-semibold fs-6">{{ $job->title }}</div>
                    <div class="c-meta">
                        @if ($job->department)<span><i class="bi bi-diagram-3"></i> {{ $job->department->name }}</span>@endif
                        <span><i class="bi bi-geo-alt"></i> {{ $job->location ?: 'Flexible' }}</span>
                        <span><i class="bi bi-clock"></i> {{ config('hrm.employment_types')[$job->employment_type] ?? '' }}</span>
                        @if ($job->closes_at)<span><i class="bi bi-calendar-event"></i> Apply by {{ $job->closes_at->format('d M') }}</span>@endif
                    </div>
                </div>
                <i class="bi bi-chevron-right text-body-secondary"></i>
            </a>
        @empty
            <x-admin.empty icon="briefcase" text="There are no open positions right now. Please check back soon." />
        @endforelse
    </div>
@endsection
