@extends('hrm.careers.layout')
@section('title', $job->title)
@section('hero')
    <a href="{{ route('careers.index', $tenant->id) }}" class="small"><i class="bi bi-arrow-left"></i> All positions</a>
    <h1 class="display-6 mt-2">{{ $job->title }}</h1>
    <div class="c-meta" style="color:rgba(255,255,255,.75)">
        @if ($job->department)<span><i class="bi bi-diagram-3"></i> {{ $job->department->name }}</span>@endif
        <span><i class="bi bi-geo-alt"></i> {{ $job->location ?: 'Flexible' }}</span>
        <span><i class="bi bi-clock"></i> {{ config('hrm.employment_types')[$job->employment_type] ?? '' }}</span>
        @if ($job->salary_min || $job->salary_max)<span><i class="bi bi-cash"></i> {{ config('hrm.currency') }} {{ number_format($job->salary_min ?? 0) }}@if ($job->salary_max) – {{ number_format($job->salary_max) }}@endif</span>@endif
    </div>
@endsection
@section('content')
    <div class="app-form-grid">
        <div class="app-card"><div class="app-card__body p-4">
            @if ($job->description)<h2 class="h5 fw-bold">About the role</h2><div class="c-content mb-4">{{ $job->description }}</div>@endif
            @if ($job->requirements)<h2 class="h5 fw-bold">Requirements</h2>
                <ul class="mb-0">@foreach (preg_split('/\r?\n/', trim($job->requirements)) as $line)@if (trim($line))<li class="mb-1">{{ ltrim(trim($line), '-•* ') }}</li>@endif @endforeach</ul>@endif
        </div></div>

        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                @if ($job->isAcceptingApplications())
                    <h3 class="app-aside-title">Apply now</h3>
                    <form method="POST" action="{{ route('careers.apply', [$tenant->id, $job->slug]) }}" enctype="multipart/form-data">@csrf
                        <x-admin.input name="name" label="Full name" required />
                        <x-admin.input name="email" type="email" label="Email" required />
                        <x-admin.input name="phone" label="Phone" optional />
                        <div class="app-field"><label class="form-label">Resume<span class="req">*</span> <span class="opt">PDF/DOC, max 5 MB</span></label>
                            <input type="file" name="resume" accept=".pdf,.doc,.docx" required @class(['form-control', 'is-invalid' => $errors->has('resume')])>
                            @error('resume')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="app-field"><label class="form-label">Cover letter <span class="opt">(optional)</span></label>
                            <textarea name="cover_letter" rows="4" class="form-control">{{ old('cover_letter') }}</textarea></div>
                        <input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off">
                        <x-admin.button class="w-100 mt-4 btn-lg-app" icon="send">Submit Application</x-admin.button>
                    </form>
                @else
                    <x-admin.empty icon="lock" text="This position is no longer accepting applications." />
                @endif
            </div></div>
        </div>
    </div>
@endsection
