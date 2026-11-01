@extends('layouts.admin.main')
@section('title', 'Tenants')
@section('subtitle', 'Each tenant has its own database and modules')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Tenants" subtitle="Create tenants, give them modules and manage their access.">
        @can('tenants.create')
            <x-admin.button :href="route('tenants.create')" icon="plus-lg">Add Tenant</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.alerts />

    <x-admin.card flush :title="'All tenants ('.$tenants->total().')'">
        <x-slot:actions>
            <form method="GET" class="input-icon" style="width:260px">
                <i class="bi bi-search"></i>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search tenants…">
            </form>
        </x-slot:actions>

        <div class="table-responsive">
            <table class="table app-table table-hover">
                <thead><tr><th>Tenant</th><th>Database</th><th>Modules</th><th>Users</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @forelse ($tenants as $t)
                    @php $mods = collect((array) ($t->modules ?? [])); $all = \App\Support\ModuleAccess::assignable(); @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <span class="app-tile__icon tone-primary"><i class="bi bi-building"></i></span>
                                <div>
                                    <div class="fw-semibold">{{ $t->name }}</div>
                                    <div class="small text-body-secondary">ID {{ $t->id }} · {{ $t->created_at?->format('d M Y') }}</div>
                                </div>
                            </div>
                        </td>
                        <td><code class="small">{{ $t->databaseName() }}</code></td>
                        <td style="max-width:320px">
                            @forelse ($mods->take(4) as $m)
                                <x-admin.badge tone="primary" class="mb-1">{{ $all[$m]['label'] ?? $m }}</x-admin.badge>
                            @empty
                                <x-admin.badge tone="muted">None</x-admin.badge>
                            @endforelse
                            @if ($mods->count() > 4)<x-admin.badge tone="muted">+{{ $mods->count() - 4 }}</x-admin.badge>@endif
                        </td>
                        <td><span class="d-inline-flex align-items-center gap-1 text-body-secondary"><i class="bi bi-people"></i>{{ $t->users_count }}</span></td>
                        <td>
                            @if ($t->is_active)
                                <x-admin.badge tone="success">Active</x-admin.badge>
                            @else
                                <x-admin.badge tone="danger">Inactive</x-admin.badge>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @can('tenants.update')
                                <a href="{{ route('tenants.edit', $t) }}" class="btn btn-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                            @endcan
                            @can('tenants.delete')
                                <x-admin.delete-button :action="route('tenants.destroy', $t)"
                                    :confirm="'Delete tenant '.$t->name.'? Its database '.$t->databaseName().' and all its users will be permanently deleted.'" />
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-admin.empty icon="buildings" text="No tenants yet." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($tenants->hasPages())
            <x-slot:footer>{{ $tenants->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
