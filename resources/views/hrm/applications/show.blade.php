@extends('layouts.admin.main')
@php $statuses = config('hrm.application_statuses'); $a = $application; @endphp
@section('title', $a->name)
@section('subtitle', 'HRM · Application for '.($a->job->title ?? ''))

@section('content')
<div class="app-content">
    <x-admin.page-header :back="route('hrm.applications.index', ['job' => $a->job_id])" :title="$a->name" :subtitle="'Applied for '.($a->job->title ?? '—').' · '.$a->created_at->format('d M Y')">
        @if ($a->resume)<x-admin.button variant="light" :href="route('hrm.applications.resume', $a)" icon="download">Resume</x-admin.button>@endif
        <form method="POST" action="{{ route('hrm.applications.destroy', $a) }}" onsubmit="return confirm('Delete this application and resume?')">@csrf @method('DELETE')
            <x-admin.button variant="light" icon="trash3" class="text-danger">Delete</x-admin.button></form>
    </x-admin.page-header>

    <div class="app-form-grid">
        <div class="d-grid gap-4">
            <x-admin.card title="Candidate">
                <dl class="row mb-0 g-2">
                    <dt class="col-sm-3 text-body-secondary fw-normal">Email</dt><dd class="col-sm-9 mb-0"><a href="mailto:{{ $a->email }}">{{ $a->email }}</a></dd>
                    <dt class="col-sm-3 text-body-secondary fw-normal">Phone</dt><dd class="col-sm-9 mb-0">{{ $a->phone ?: '—' }}</dd>
                    <dt class="col-sm-3 text-body-secondary fw-normal">Job</dt><dd class="col-sm-9 mb-0">{{ $a->job->title ?? '—' }} <span class="text-body-secondary">· {{ $a->job->department->name ?? '' }}</span></dd>
                </dl>
            </x-admin.card>
            @if ($a->interviewer_id)
                <x-admin.card title="Interview">
                    <div class="d-flex flex-wrap gap-4 align-items-start">
                        <div>
                            <div class="small text-body-secondary mb-2">Interviewer</div>
                            @if ($a->interviewer)@include('hrm.partials.person', ['e' => $a->interviewer, 'link' => true, 'sub' => $a->interviewer->designation->name ?? ''])@else<span class="text-body-secondary">Removed employee</span>@endif
                        </div>
                        <div><div class="small text-body-secondary mb-2">When</div><div class="fw-semibold">{{ $a->interview_at?->format('D, d M Y · h:i A') ?? '—' }}</div></div>
                        <div><div class="small text-body-secondary mb-2">Mode</div><div class="fw-semibold">{{ \App\Models\Hrm\JobApplication::MODES[$a->interview_mode] ?? '—' }}</div>
                            @if ($a->interview_location)<div class="small text-body-secondary text-break" style="max-width:260px">{{ $a->interview_location }}</div>@endif</div>
                    </div>
                    <hr>
                    @if ($a->feedback_at)
                        @php [$rl, $rt] = \App\Models\Hrm\JobApplication::RESULTS[$a->interview_result] ?? [$a->interview_result, 'muted']; @endphp
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <x-admin.badge :tone="$rt" icon="clipboard-check">{{ $rl }}</x-admin.badge>
                            @if ($a->interview_rating)<span class="text-warning">{!! str_repeat('<i class="bi bi-star-fill"></i>', $a->interview_rating) !!}</span>@endif
                            <span class="small text-body-secondary ms-auto">{{ $a->feedback_at->diffForHumans() }}</span>
                        </div>
                        <div style="white-space:pre-line">{{ $a->interview_feedback }}</div>
                    @else
                        <div class="text-body-secondary small"><i class="bi bi-hourglass-split"></i> Waiting for the interviewer's feedback.</div>
                    @endif
                </x-admin.card>
            @endif

            <x-admin.card title="Cover letter">
                <div style="white-space:pre-line">{{ $a->cover_letter ?: 'No cover letter.' }}</div>
            </x-admin.card>
        </div>

        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Pipeline</h3>
                <form method="POST" action="{{ route('hrm.applications.update', $a) }}">@csrf @method('PUT')
                    <div class="app-radio-list mb-3" style="max-height:none">
                        @foreach ($statuses as $k => [$label, $tone])
                            <x-admin.radio-card name="status" :value="$k" :title="$label" :checked="$a->status === $k" />
                        @endforeach
                    </div>
                    <x-admin.select name="rating" label="Rating" optional :options="[1 => '★', 2 => '★★', 3 => '★★★', 4 => '★★★★', 5 => '★★★★★']" :value="$a->rating" placeholder="—" />
                    <div class="form-section-title mt-4">Interview</div>
                    <x-admin.select name="interviewer_id" label="Interviewer" optional
                        :options="$interviewers->mapWithKeys(fn ($e) => [$e->id => $e->name.($e->designation ? ' — '.$e->designation->name : '')])"
                        :value="$a->interviewer_id" placeholder="— Not assigned —"
                        hint="Only employees with a login are listed. They'll see it under My HR → My Interviews." />
                    <x-admin.input name="interview_at" type="datetime-local" label="Date & time" optional :value="$a->interview_at?->format('Y-m-d\TH:i')" />
                    <x-admin.select name="interview_mode" label="Mode" optional :options="\App\Models\Hrm\JobApplication::MODES" :value="$a->interview_mode" placeholder="—" />
                    <x-admin.input name="interview_location" label="Location / meeting link" optional :value="$a->interview_location" placeholder="Room 2 or https://meet…" />
                    <div class="app-field"><label class="form-label">Notes <span class="opt">(internal)</span></label>
                        <textarea name="notes" class="form-control" rows="4">{{ old('notes', $a->notes) }}</textarea></div>
                    <x-admin.button class="w-100 mt-4" icon="check2">Save</x-admin.button>
                </form>
                @if ($a->employee_id)<div class="small text-success mt-3"><i class="bi bi-check-circle-fill"></i> Hired — <a href="{{ route('hrm.employees.show', $a->employee_id) }}">view employee</a></div>@endif
            </div></div>
            <x-admin.tips :items="['Choosing “Hired” opens the new-employee form pre-filled with this candidate.', 'Assigning an interviewer moves the candidate to the Interview stage.', 'Notes and rating are only visible to your HR team.']" />
        </div>
    </div>
</div>
@endsection
