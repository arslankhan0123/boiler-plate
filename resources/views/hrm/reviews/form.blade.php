@extends('layouts.admin.main')
@php $editing = $review->exists; $scores = old('scores', $review->scores ?? []); @endphp
@section('title', ($editing ? 'Edit' : 'New').' Review — '.$employee->name)
@section('subtitle', 'HRM · Performance')

@section('content')
<div class="app-content">
    <x-admin.page-header :back="route('hrm.reviews.index')" :title="'Review: '.$employee->name" :subtitle="$employee->designation->name ?? ''" />

    <form method="POST" action="{{ $editing ? route('hrm.reviews.update', $review) : route('hrm.reviews.store') }}" class="app-form-grid">
        @csrf @if ($editing) @method('PUT') @endif
        <input type="hidden" name="employee_id" value="{{ $employee->id }}">

        <div class="app-card app-form-card">
            <x-admin.input name="period" label="Review period" :value="$review->period" placeholder="e.g. H2 2026, Q4 2026" required />

            <div class="form-section-title mt-4">Scores</div>
            @error('scores.*')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
            <div class="d-grid gap-3">
                @foreach (\App\Models\Hrm\Review::CRITERIA as $k => $label)
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="fw-semibold" style="width:170px">{{ $label }}</span>
                        <div class="btn-group" role="group">
                            @for ($i = 1; $i <= 5; $i++)
                                <input type="radio" class="btn-check" name="scores[{{ $k }}]" id="s-{{ $k }}-{{ $i }}" value="{{ $i }}" @checked((int) ($scores[$k] ?? 0) === $i) required>
                                <label class="btn btn-light" for="s-{{ $k }}-{{ $i }}" style="min-width:46px">{{ $i }}</label>
                            @endfor
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="form-text">1 = needs improvement · 3 = meets expectations · 5 = outstanding</p>

            <div class="app-field"><label class="form-label">Strengths</label><textarea name="strengths" rows="4" class="form-control">{{ old('strengths', $review->strengths) }}</textarea></div>
            <div class="app-field"><label class="form-label">Areas to improve</label><textarea name="improvements" rows="4" class="form-control">{{ old('improvements', $review->improvements) }}</textarea></div>
            <div class="app-field"><label class="form-label">Goals for next period</label><textarea name="goals" rows="4" class="form-control">{{ old('goals', $review->goals) }}</textarea></div>

            <div class="app-form-actions">
                <x-admin.button class="btn-lg-app" name="share" value="1" icon="send">Save & Share</x-admin.button>
                <x-admin.button variant="light" class="btn-lg-app" icon="floppy">Save Draft</x-admin.button>
            </div>
        </div>
        <div class="app-aside">
            <x-admin.tips :items="['Drafts are private to you and HR.', 'Once shared, the employee sees it under My HR → My Reviews and can acknowledge it.', 'Shared reviews can no longer be edited.']" />
        </div>
    </form>
</div>
@endsection
