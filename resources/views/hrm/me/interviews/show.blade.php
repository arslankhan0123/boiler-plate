@extends('layouts.admin.main')
@php $a = $application; $results = \App\Models\Hrm\JobApplication::RESULTS; @endphp
@section('title', 'Interview — '.$a->name)
@section('subtitle', 'My HR · My Interviews')

@section('content')
<div class="app-content">
    <x-admin.page-header :back="route('hrm.me.interviews')" :title="$a->name" :subtitle="'Interview for '.($a->job->title ?? '—')">
        @if ($a->resume)<x-admin.button variant="light" :href="route('hrm.applications.resume', $a)" icon="download">Resume</x-admin.button>@endif
    </x-admin.page-header>

    <div class="app-form-grid">
        <div class="d-grid gap-4">
            <x-admin.card title="Interview details">
                <div class="row g-4">
                    <div class="col-sm-4"><div class="small text-body-secondary">When</div><div class="fw-semibold">{{ $a->interview_at?->format('D, d M Y') ?? '—' }}</div>
                        <div class="small">{{ $a->interview_at?->format('h:i A') }}</div></div>
                    <div class="col-sm-4"><div class="small text-body-secondary">Mode</div><div class="fw-semibold">{{ \App\Models\Hrm\JobApplication::MODES[$a->interview_mode] ?? '—' }}</div></div>
                    <div class="col-sm-4"><div class="small text-body-secondary">Location / link</div>
                        @if ($a->interview_location && str_starts_with($a->interview_location, 'http'))
                            <a href="{{ $a->interview_location }}" target="_blank" rel="noopener" class="fw-semibold text-break">Join meeting <i class="bi bi-box-arrow-up-right"></i></a>
                        @else<div class="fw-semibold text-break">{{ $a->interview_location ?: '—' }}</div>@endif
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card title="Candidate">
                <dl class="row mb-0 g-2">
                    <dt class="col-sm-3 text-body-secondary fw-normal">Email</dt><dd class="col-sm-9 mb-0">{{ $a->email }}</dd>
                    <dt class="col-sm-3 text-body-secondary fw-normal">Phone</dt><dd class="col-sm-9 mb-0">{{ $a->phone ?: '—' }}</dd>
                    <dt class="col-sm-3 text-body-secondary fw-normal">Position</dt><dd class="col-sm-9 mb-0">{{ $a->job->title ?? '—' }} <span class="text-body-secondary">· {{ $a->job->department->name ?? '' }}</span></dd>
                    <dt class="col-sm-3 text-body-secondary fw-normal">Applied</dt><dd class="col-sm-9 mb-0">{{ $a->created_at->format('d M Y') }}</dd>
                </dl>
            </x-admin.card>

            <x-admin.card title="Cover letter"><div style="white-space:pre-line">{{ $a->cover_letter ?: 'No cover letter.' }}</div></x-admin.card>

            @if ($a->job?->requirements)
                <x-admin.card title="What the role needs">
                    <ul class="mb-0">@foreach (preg_split('/\r?\n/', trim($a->job->requirements)) as $line)@if (trim($line))<li>{{ ltrim(trim($line), '-•* ') }}</li>@endif @endforeach</ul>
                </x-admin.card>
            @endif
        </div>

        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Your feedback</h3>
                <form method="POST" action="{{ route('hrm.me.interviews.feedback', $a) }}">@csrf @method('PUT')
                    <div class="app-radio-list mb-3" style="max-height:none">
                        @foreach ($results as $k => [$label, $tone])
                            <x-admin.radio-card name="interview_result" :value="$k" :title="$label" :checked="old('interview_result', $a->interview_result) === $k" />
                        @endforeach
                    </div>
                    @error('interview_result')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                    <x-admin.select name="interview_rating" label="Rating" optional :options="[1 => '★', 2 => '★★', 3 => '★★★', 4 => '★★★★', 5 => '★★★★★']" :value="$a->interview_rating" placeholder="—" />
                    <div class="app-field"><label class="form-label">Comments<span class="req">*</span></label>
                        <textarea name="interview_feedback" rows="6" required placeholder="Strengths, concerns, recommendation…" @class(['form-control', 'is-invalid' => $errors->has('interview_feedback')])>{{ old('interview_feedback', $a->interview_feedback) }}</textarea>
                        @error('interview_feedback')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <x-admin.button class="w-100 mt-4" icon="send">{{ $a->feedback_at ? 'Update Feedback' : 'Submit Feedback' }}</x-admin.button>
                </form>
                @if ($a->feedback_at)<p class="form-text mt-3 mb-0">Submitted {{ $a->feedback_at->diffForHumans() }}. You can still update it.</p>@endif
            </div></div>
            <x-admin.tips :items="['HR sees your feedback on the application.', 'Only you and HR can see this candidate.']" />
        </div>
    </div>
</div>
@endsection
