@extends('layouts.admin.main')
@section('title', 'Designations')
@section('subtitle', 'HRM · Organization')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Designations" subtitle="Job titles and who they report to — this builds the organization chart.">
        <x-admin.button variant="light" :href="route('hrm.org-chart')" icon="diagram-3">View Org Chart</x-admin.button>
    </x-admin.page-header>

    <div class="app-form-grid">
        <x-admin.card flush :title="'All designations ('.$designations->count().')'">
            <div class="table-responsive">
                <table class="table app-table table-hover">
                    <thead><tr><th>Designation</th><th>Department</th><th>Reports to</th><th>Employees</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    @forelse ($designations as $d)
                        <tr>
                            <td><div class="fw-semibold">{{ $d->name }}</div><div class="small text-body-secondary">{{ $d->code }}</div></td>
                            <td>{{ $d->department->name ?? '—' }}</td>
                            <td>@if ($d->parent)<x-admin.badge tone="primary" icon="arrow-up">{{ $d->parent->name }}</x-admin.badge>@else<span class="text-body-secondary small">Top level</span>@endif</td>
                            <td>{{ $d->employees_count }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('hrm.designations.index', ['edit' => $d->id]) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                                <x-admin.delete-button :action="route('hrm.designations.destroy', $d)" confirm="Delete this designation?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty icon="person-vcard" text="No designations yet. Add a department first." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>

        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">{{ $editing ? 'Edit designation' : 'New designation' }}</h3>
                <form method="POST" action="{{ $editing ? route('hrm.designations.update', $editing) : route('hrm.designations.store') }}">
                    @csrf
                    @if ($editing) @method('PUT') @endif
                    <x-admin.input name="name" label="Title" :value="$editing?->name" placeholder="e.g. Finance Manager" required />
                    <x-admin.input name="code" label="Code" optional :value="$editing?->code" />
                    <x-admin.select name="department_id" label="Department" :options="$departments" :value="$editing?->department_id" placeholder="Select department" required />
                    <x-admin.select name="parent_id" label="Reports to" optional :options="$designations->where('id', '!=', $editing?->id)->pluck('name', 'id')"
                        :value="$editing?->parent_id" placeholder="— Top level (no manager) —" hint="Used for the organization chart." />
                    <div class="d-flex gap-2 mt-4">
                        <x-admin.button icon="check2">{{ $editing ? 'Save' : 'Add Designation' }}</x-admin.button>
                        @if ($editing)<x-admin.button variant="light" :href="route('hrm.designations.index')">Cancel</x-admin.button>@endif
                    </div>
                </form>
            </div></div>
        </div>
    </div>
</div>
@endsection
