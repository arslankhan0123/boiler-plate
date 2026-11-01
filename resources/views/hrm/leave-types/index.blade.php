@extends('layouts.admin.main')
@section('title', 'Leave Types')
@section('subtitle', 'HRM · Leave')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Leave Types" subtitle="Annual, sick, casual… and how many days each employee gets per year.">
        <x-admin.button variant="light" :href="route('hrm.allocations.index')" icon="calendar-plus">Yearly Allocation</x-admin.button>
    </x-admin.page-header>

    <div class="app-form-grid">
        <x-admin.card flush :title="'Leave types ('.$types->count().')'">
            <div class="table-responsive"><table class="table app-table table-hover">
                <thead><tr><th>Type</th><th>Days / year</th><th>Paid</th><th>Carry forward</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse ($types as $t)
                    <tr>
                        <td><span class="d-inline-block rounded-circle me-2" style="width:10px;height:10px;background:{{ $t->color }}"></span><span class="fw-semibold">{{ $t->name }}</span>
                            <span class="small text-body-secondary">{{ $t->code }}</span></td>
                        <td>{{ $t->days_per_year }}</td>
                        <td>{!! $t->is_paid ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<span class="text-body-secondary small">Unpaid</span>' !!}</td>
                        <td>{{ $t->carry_forward ? 'Up to '.$t->max_carry_forward.' days' : '—' }}</td>
                        <td><x-admin.badge :tone="$t->is_active ? 'success' : 'muted'">{{ $t->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('hrm.leave-types.index', ['edit' => $t->id]) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                            <x-admin.delete-button :action="route('hrm.leave-types.destroy', $t)" confirm="Delete this leave type?" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-admin.empty icon="calendar2-week" text="No leave types. Add Annual, Sick and Casual leave to start." /></td></tr>
                @endforelse
                </tbody></table></div>
        </x-admin.card>

        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">{{ $editing ? 'Edit leave type' : 'New leave type' }}</h3>
                <form method="POST" action="{{ $editing ? route('hrm.leave-types.update', $editing) : route('hrm.leave-types.store') }}">
                    @csrf @if ($editing) @method('PUT') @endif
                    <x-admin.input name="name" label="Name" :value="$editing?->name" placeholder="e.g. Annual Leave" required />
                    <div class="row g-3 mt-0">
                        <div class="col-6"><x-admin.input name="days_per_year" type="number" step="0.5" min="0" label="Days / year" :value="$editing?->days_per_year ?? 0" required /></div>
                        <div class="col-6"><x-admin.input name="code" label="Code" optional :value="$editing?->code" placeholder="AL" /></div>
                    </div>
                    <div class="app-field"><label class="form-label">Color</label>
                        <input type="color" name="color" class="form-control form-control-color w-100" value="{{ old('color', $editing?->color ?? '#6366f1') }}"></div>
                    <div class="d-grid gap-2 mt-3 small">
                        <label class="d-flex gap-2 align-items-center"><input type="checkbox" class="form-check-input mt-0" name="is_paid" value="1" @checked(old('is_paid', $editing?->is_paid ?? true))> Paid leave (counts against balance)</label>
                        <label class="d-flex gap-2 align-items-center"><input type="checkbox" class="form-check-input mt-0" name="carry_forward" value="1" id="cf" @checked(old('carry_forward', $editing?->carry_forward))> Carry unused days to next year</label>
                        <label class="d-flex gap-2 align-items-center"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input mt-0" name="is_active" value="1" @checked(old('is_active', $editing?->is_active ?? true))> Active</label>
                    </div>
                    <x-admin.input name="max_carry_forward" type="number" step="0.5" min="0" label="Max days carried" optional :value="$editing?->max_carry_forward ?? 0" />
                    <div class="d-flex gap-2 mt-4">
                        <x-admin.button icon="check2">{{ $editing ? 'Save' : 'Add Type' }}</x-admin.button>
                        @if ($editing)<x-admin.button variant="light" :href="route('hrm.leave-types.index')">Cancel</x-admin.button>@endif
                    </div>
                </form>
            </div></div>
        </div>
    </div>
</div>
@endsection
