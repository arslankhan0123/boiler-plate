@extends('layouts.admin.main')
@php [$one, $plural, $parentType, $icon] = $meta; @endphp
@section('title', $plural)
@section('subtitle', 'Inventory · Masters')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Item Masters" subtitle="Departments, categories, brands, units, variants and storage locations used on items." />

    <div class="master-tabs mb-4">
        @foreach (\App\Models\Inventory\Attribute::TYPES as $t => [$l, $p, , $ic])
            <a href="{{ route('inventory.masters.type', $t) }}" class="master-tab @if ($t === $type) is-active @endif">
                <i class="bi bi-{{ $ic }}"></i> {{ $p }} <span class="master-count">{{ $counts[$t] ?? 0 }}</span></a>
        @endforeach
    </div>

    <div class="app-form-grid">
        <x-admin.card flush :title="$plural.' ('.$rows->total().')'">
            <x-slot:actions>
                <form method="GET" class="d-flex gap-2"><input name="q" value="{{ request('q') }}" class="form-control" style="width:220px;min-height:40px" placeholder="Search {{ strtolower($plural) }}…"></form>
            </x-slot:actions>
            <div class="table-responsive"><table class="table app-table table-hover">
                <thead><tr><th>Name</th>@if ($parentLabel)<th>{{ $parentLabel }}</th>@endif<th>Code</th><th>Items</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse ($rows as $r)
                    <tr>
                        <td><div class="fw-semibold">{{ $r->name }}</div>@if ($r->description)<div class="small text-body-secondary text-truncate" style="max-width:280px">{{ $r->description }}</div>@endif</td>
                        @if ($parentLabel)<td>{{ $r->parent->name ?? '—' }}</td>@endif
                        <td class="small">{{ $r->code ?: '—' }}</td>
                        <td>{{ $usage[$r->id] ?? 0 }}</td>
                        <td><x-admin.badge :tone="$r->is_active ? 'success' : 'muted'">{{ $r->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></td>
                        <td class="text-end text-nowrap">@if ($canEdit)
                            <a href="{{ route('inventory.masters.type', [$type, 'edit' => $r->id]) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                            <x-admin.delete-button :action="route('inventory.masters.destroy', [$type, $r])" :confirm="'Delete '.$r->name.'?'" />@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-admin.empty :icon="$icon" :text="'No '.strtolower($plural).' yet.'" /></td></tr>
                @endforelse
                </tbody></table></div>
            @if ($rows->hasPages())
                <x-slot:footer>{{ $rows->links('pagination::bootstrap-5') }}</x-slot:footer>
            @endif
        </x-admin.card>

        @if ($canEdit)
            <div class="app-aside">
                <div class="app-card"><div class="app-card__body">
                    <h3 class="app-aside-title">{{ $editing ? 'Edit '.strtolower($one) : 'New '.strtolower($one) }}</h3>
                    <form method="POST" action="{{ $editing ? route('inventory.masters.update', [$type, $editing]) : route('inventory.masters.store', $type) }}">@csrf @if ($editing) @method('PUT') @endif
                        @if ($parentLabel)
                            <x-admin.select name="parent_id" :label="$parentLabel" :options="$parents" :value="$editing?->parent_id" placeholder="—" optional />
                        @endif
                        <x-admin.input name="name" label="Name" :value="$editing?->name" required />
                        <x-admin.input name="code" label="Code" :value="$editing?->code" optional />
                        <div class="mb-3"><label class="form-label">Description <span class="opt">(optional)</span></label>
                            <textarea name="description" rows="2" class="form-control">{{ old('description', $editing?->description) }}</textarea></div>
                        <label class="d-flex gap-2 align-items-center small"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input mt-0" name="is_active" value="1" @checked(old('is_active', $editing?->is_active ?? true))> Active</label>
                        <div class="d-flex gap-2 mt-4">
                            <x-admin.button icon="check2">{{ $editing ? 'Save' : 'Add '.$one }}</x-admin.button>
                            @if ($editing)<x-admin.button variant="light" :href="route('inventory.masters.type', $type)">Cancel</x-admin.button>@endif
                        </div>
                    </form>
                </div></div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .master-tabs { display: flex; flex-wrap: wrap; gap: .5rem; }
    .master-tab { display: inline-flex; align-items: center; gap: .45rem; padding: .5rem .9rem; border-radius: 10px; background: #fff; border: 1.5px solid var(--app-border); color: var(--app-text); font-weight: 600; font-size: .85rem; text-decoration: none; }
    .master-tab:hover { border-color: #c7d2fe; color: var(--app-primary, #4f46e5); }
    .master-tab.is-active { background: var(--app-primary, #4f46e5); border-color: var(--app-primary, #4f46e5); color: #fff; }
    .master-count { font-size: .72rem; background: rgba(0,0,0,.07); border-radius: 20px; padding: 0 .45rem; }
    .master-tab.is-active .master-count { background: rgba(255,255,255,.25); }
</style>
@endpush
