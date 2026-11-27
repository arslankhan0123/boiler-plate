@extends('layouts.admin.main')
@section('title', 'My Interviews')
@section('subtitle', 'My HR')

@section('content')
<div class="app-content">
    <x-admin.page-header title="My Interviews" subtitle="Candidates HR has asked you to interview. Review them and share your feedback." />

    <div class="d-flex gap-2 mb-3">
        <a href="{{ route('hrm.me.interviews', ['tab' => 'upcoming']) }}" class="btn btn-sm {{ $tab === 'upcoming' ? 'btn-primary' : 'btn-light' }}">To do <span class="opacity-75">({{ $counts['upcoming'] }})</span></a>
        <a href="{{ route('hrm.me.interviews', ['tab' => 'done']) }}" class="btn btn-sm {{ $tab === 'done' ? 'btn-primary' : 'btn-light' }}">Feedback given <span class="opacity-75">({{ $counts['done'] }})</span></a>
    </div>

    <x-admin.card flush>
        <div class="table-responsive"><table class="table app-table table-hover">
            <thead><tr><th>Candidate</th><th>Position</th><th>When</th><th>Mode</th><th>{{ $tab === 'done' ? 'Your result' : '' }}</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($interviews as $a)
                @php $soon = $a->interview_at && ! $a->feedback_at && $a->interview_at->isToday(); $overdue = $a->interview_at && ! $a->feedback_at && $a->interview_at->isPast(); @endphp
                <tr>
                    <td><div class="d-flex align-items-center gap-3"><span class="app-avatar">{{ \App\Support\Hrm\Hrm::initials($a->name) }}</span>
                        <div><a href="{{ route('hrm.me.interviews.show', $a) }}" class="fw-semibold text-body text-decoration-none">{{ $a->name }}</a>
                            <div class="small text-body-secondary">{{ $a->email }}</div></div></div></td>
                    <td>{{ $a->job->title ?? '—' }}<div class="small text-body-secondary">{{ $a->job->department->name ?? '' }}</div></td>
                    <td class="text-nowrap">{{ $a->interview_at?->format('D, d M · h:i A') ?? '—' }}
                        @if ($soon)<x-admin.badge tone="warning">Today</x-admin.badge>@elseif ($overdue)<x-admin.badge tone="danger">Feedback due</x-admin.badge>@endif</td>
                    <td>{{ \App\Models\Hrm\JobApplication::MODES[$a->interview_mode] ?? '—' }}</td>
                    <td>@if ($a->feedback_at)@php [$rl, $rt] = \App\Models\Hrm\JobApplication::RESULTS[$a->interview_result] ?? [$a->interview_result, 'muted']; @endphp<x-admin.badge :tone="$rt">{{ $rl }}</x-admin.badge>@endif</td>
                    <td class="text-end"><a href="{{ route('hrm.me.interviews.show', $a) }}" class="btn btn-sm btn-light">{{ $a->feedback_at ? 'View' : 'Open' }} <i class="bi bi-arrow-right"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-admin.empty icon="calendar2-check" :text="$tab === 'done' ? 'No feedback given yet.' : 'No interviews assigned to you.'" /></td></tr>
            @endforelse
            </tbody></table></div>
    </x-admin.card>
</div>
@endsection
