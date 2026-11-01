@extends('layouts.admin.main')
@section('title', 'Branches / Locations')
@section('subtitle', 'Settings')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Branches / Locations" subtitle="Offices, shops and warehouses. Used by POS, inventory and reports." />
    <div class="app-form-grid">
        <x-admin.card flush :title="'Branches ('.$branches->count().')'">
            <div class="table-responsive"><table class="table app-table table-hover">
                <thead><tr><th>Branch</th><th>Contact</th><th>Manager</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse ($branches as $b)
                    <tr>
                        <td><div class="fw-semibold">{{ $b->name }} @if ($b->is_head_office)<x-admin.badge tone="primary" icon="star-fill">Head office</x-admin.badge>@endif</div>
                            <div class="small text-body-secondary">{{ $b->code }} {{ trim($b->address.', '.$b->city, ', ') }}</div></td>
                        <td><div class="small">{{ $b->phone ?: '—' }}</div><div class="small text-body-secondary">{{ $b->email }}</div></td>
                        <td>{{ $b->manager->name ?? '—' }}</td>
                        <td><x-admin.badge :tone="$b->is_active ? 'success' : 'muted'">{{ $b->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></td>
                        <td class="text-end text-nowrap">@if ($canEdit)
                            <a href="{{ route('settings.branches', ['edit' => $b->id]) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                            <x-admin.delete-button :action="route('settings.branches.destroy', $b)" confirm="Remove this branch?" />@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-admin.empty icon="geo-alt" text="No branches yet — add your head office first." /></td></tr>
                @endforelse
                </tbody></table></div>
        </x-admin.card>
        @if ($canEdit)
            <div class="app-aside">
                <div class="app-card"><div class="app-card__body">
                    <h3 class="app-aside-title">{{ $editing ? 'Edit branch' : 'New branch' }}</h3>
                    <form method="POST" action="{{ $editing ? route('settings.branches.update', $editing) : route('settings.branches.store') }}">@csrf @if ($editing) @method('PUT') @endif
                        <x-admin.input name="name" label="Name" :value="$editing?->name" placeholder="e.g. Lahore — Gulberg" required />
                        <div class="row g-3 mt-0">
                            <div class="col-6"><x-admin.input name="code" label="Code" optional :value="$editing?->code" placeholder="LHR-01" /></div>
                            <div class="col-6"><x-admin.input name="city" label="City" optional :value="$editing?->city" /></div>
                        </div>
                        <x-admin.input name="address" label="Address" optional :value="$editing?->address" />
                        <div class="row g-3 mt-0">
                            <div class="col-6"><x-admin.input name="phone" label="Phone" optional :value="$editing?->phone" /></div>
                            <div class="col-6"><x-admin.input name="email" type="email" label="Email" optional :value="$editing?->email" /></div>
                        </div>
                        @if ($employees->isNotEmpty())<x-admin.select name="manager_id" label="Branch manager" optional :options="$employees" :value="$editing?->manager_id" placeholder="—" />@endif
                        <div class="d-grid gap-2 mt-3 small">
                            <label class="d-flex gap-2 align-items-center"><input type="checkbox" class="form-check-input mt-0" name="is_head_office" value="1" @checked(old('is_head_office', $editing?->is_head_office))> Head office</label>
                            <label class="d-flex gap-2 align-items-center"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input mt-0" name="is_active" value="1" @checked(old('is_active', $editing?->is_active ?? true))> Active</label>
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <x-admin.button icon="check2">{{ $editing ? 'Save' : 'Add Branch' }}</x-admin.button>
                            @if ($editing)<x-admin.button variant="light" :href="route('settings.branches')">Cancel</x-admin.button>@endif
                        </div>
                    </form>
                </div></div>
            </div>
        @endif
    </div>
</div>
@endsection
