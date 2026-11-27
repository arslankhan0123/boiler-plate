{{-- Task form fields (create modal + edit). $task optional. --}}
@php $t = $task ?? null; @endphp
<x-admin.input name="title" label="Title" :value="$t?->title" placeholder="What needs to be done?" required />
<div class="app-field">
    <label class="form-label">Description <span class="opt">(optional)</span></label>
    <textarea name="description" rows="4" class="form-control" placeholder="Details, links, acceptance criteria…">{{ old('description', $t?->description) }}</textarea>
</div>
<div class="row g-3 mt-0">
    <div class="col-md-6">
        <x-admin.select name="assignee_id" label="Assign to" optional
            :options="$employees->mapWithKeys(fn ($e) => [$e->id => $e->name.($e->designation ? ' — '.$e->designation->name : '').($e->user_id ? '' : ' (no login)')])"
            :value="$t?->assignee_id" placeholder="— Unassigned —" />
    </div>
    <div class="col-md-6"><x-admin.input name="due_date" type="date" label="Due date" optional :value="$t?->due_date?->format('Y-m-d')" /></div>
    <div class="col-md-6"><x-admin.select name="priority" label="Priority" :options="collect(\App\Models\Projects\Task::PRIORITIES)->map(fn ($p) => $p[0])" :value="$t?->priority ?? 'medium'" required /></div>
    <div class="col-md-6"><x-admin.select name="status" label="Status" :options="collect(\App\Models\Projects\Task::STATUSES)->map(fn ($s) => $s[0])" :value="$t?->status ?? request('status', 'todo')" required /></div>
</div>
