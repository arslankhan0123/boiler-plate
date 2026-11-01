@extends('layouts.admin.main')
@php $editing = $job->exists; @endphp
@section('title', $editing ? 'Edit Job' : 'Post a Job')
@section('subtitle', 'HRM · Recruitment')

@section('content')
<div class="app-content">
    <x-admin.page-header :back="route('hrm.jobs.index')" :title="$editing ? 'Edit Job' : 'Post a Job'" subtitle="Describe the role — this is what candidates see.">
        <x-admin.button variant="light" :href="route('hrm.jobs.index')" icon="list-ul">All Jobs</x-admin.button>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('hrm.jobs.update', $job) : route('hrm.jobs.store') }}" class="app-form-grid">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="app-card app-form-card">
            <x-admin.input name="title" label="Job Title" :value="$job->title" placeholder="e.g. Senior Laravel Developer" required />
            <div class="row g-4 mt-0">
                <div class="col-md-6"><x-admin.select name="department_id" label="Department" optional :options="$departments" :value="$job->department_id" placeholder="—" /></div>
                <div class="col-md-6"><x-admin.select name="designation_id" label="Designation" optional :options="$designations" :value="$job->designation_id" placeholder="—" hint="Used when the candidate is hired." /></div>
                <div class="col-md-6"><x-admin.select name="employment_type" label="Employment Type" :options="config('hrm.employment_types')" :value="$job->employment_type" required /></div>
                <div class="col-md-6"><x-admin.input name="location" label="Location" optional :value="$job->location" placeholder="Lahore / Remote" /></div>
                <div class="col-md-4"><x-admin.input name="positions" type="number" min="1" label="Openings" :value="$job->positions" required /></div>
                <div class="col-md-4"><x-admin.input name="salary_min" type="number" min="0" step="0.01" label="Salary from" optional :value="$job->salary_min" /></div>
                <div class="col-md-4"><x-admin.input name="salary_max" type="number" min="0" step="0.01" label="Salary to" optional :value="$job->salary_max" /></div>
            </div>
            <div class="app-field"><label class="form-label">Description</label>
                <textarea name="description" rows="7" class="form-control" placeholder="About the role, responsibilities…">{{ old('description', $job->description) }}</textarea></div>
            <div class="app-field"><label class="form-label">Requirements</label>
                <textarea name="requirements" rows="6" class="form-control" placeholder="One requirement per line">{{ old('requirements', $job->requirements) }}</textarea></div>
            <div class="app-form-actions">
                <x-admin.button class="btn-lg-app" icon="check2">{{ $editing ? 'Save Changes' : 'Create Job' }}</x-admin.button>
                <x-admin.button variant="light" class="btn-lg-app" :href="route('hrm.jobs.index')">Cancel</x-admin.button>
            </div>
        </div>
        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Status</h3>
                @php $st = old('status', $job->status); @endphp
                <x-admin.radio-card name="status" value="open" title="Open" desc="Visible on careers page" :checked="$st === 'open'" badge="Open" tone="success" />
                <x-admin.radio-card name="status" value="draft" title="Draft" desc="Only visible to HR" :checked="$st === 'draft'" />
                <x-admin.radio-card name="status" value="closed" title="Closed" desc="No new applications" :checked="$st === 'closed'" badge="Closed" tone="danger" />
                <x-admin.input name="closes_at" type="date" label="Application deadline" optional :value="$job->closes_at?->format('Y-m-d')" />
            </div></div>
        </div>
    </form>
</div>
@endsection
