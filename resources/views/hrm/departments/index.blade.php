@extends('layouts.admin.main')
@section('title', 'Departments')
@section('subtitle', 'HRM · Organization')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Departments" subtitle="Organize employees into departments." />

    <div class="app-form-grid">
        <x-admin.card flush :title="'All departments ('.$departments->count().')'">
            <div class="table-responsive">
                <table class="table app-table table-hover">
                    <thead><tr><th>Department</th><th>Designations</th><th>Employees</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @forelse ($departments as $d)
                        <tr>
                            <td><div class="fw-semibold">{{ $d->name }}</div><div class="small text-body-secondary">{{ $d->code }}</div></td>
                            <td>{{ $d->designations_count }}</td>
                            <td>{{ $d->employees_count }}</td>
                            <td><x-admin.badge :tone="$d->is_active ? 'success' : 'muted'">{{ $d->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('hrm.departments.index', ['edit' => $d->id]) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                                <x-admin.delete-button :action="route('hrm.departments.destroy', $d)" confirm="Delete this department?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty icon="diagram-3" text="No departments yet." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>

        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">{{ $editing ? 'Edit department' : 'New department' }}</h3>
                <form method="POST" action="{{ $editing ? route('hrm.departments.update', $editing) : route('hrm.departments.store') }}">
                    @csrf
                    @if ($editing) @method('PUT') @endif
                    <x-admin.input name="name" label="Name" :value="$editing?->name" placeholder="e.g. Finance" required />
                    <x-admin.input name="code" label="Code" optional :value="$editing?->code" placeholder="FIN" />
                    <div class="app-field">
                        <label class="form-label">Description <span class="opt">(optional)</span></label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $editing?->description) }}</textarea>
                    </div>
                    <label class="d-flex align-items-center gap-2 mt-3 small"><input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="form-check-input mt-0" name="is_active" value="1" @checked(old('is_active', $editing?->is_active ?? true))> Active</label>
                    <div class="d-flex gap-2 mt-4">
                        <x-admin.button icon="check2">{{ $editing ? 'Save' : 'Add Department' }}</x-admin.button>
                        @if ($editing)<x-admin.button variant="light" :href="route('hrm.departments.index')">Cancel</x-admin.button>@endif
                    </div>
                </form>
            </div></div>
        </div>
    </div>
</div>
@endsection
