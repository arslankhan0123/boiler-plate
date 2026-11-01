@extends('layouts.admin.main')
@section('title', 'Roles & Permissions')
@section('subtitle', "Control what each role can access")

@section('content')
<div class="app-content">
    <x-admin.page-header title="Roles & Permissions" subtitle="Define roles and control which modules each role can access.">
        @can('roles.create')
            <x-admin.button :href="route('roles.create')" icon="plus-lg">Add Role</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.alerts />

    <x-admin.card flush :title="'All roles ('.$roles->count().')'">
        <div class="table-responsive">
            <table class="table app-table table-hover">
                <thead><tr><th>Role</th><th>Permissions</th><th>Users</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @forelse ($roles as $role)
                    @php $locked = $role->name === config('modules.master_role'); @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <span class="app-tile__icon {{ $locked ? 'tone-warning' : 'tone-primary' }}"><i class="bi bi-{{ $locked ? 'stars' : 'shield-check' }}"></i></span>
                                <div>
                                    <div class="fw-semibold">{{ $role->name }}</div>
                                    @if ($locked)<div class="small text-body-secondary">System role · full access</div>@endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($locked)
                                <x-admin.badge tone="warning" icon="infinity">All</x-admin.badge>
                            @else
                                <x-admin.badge tone="{{ $role->permissions_count ? 'primary' : 'muted' }}">{{ $role->permissions_count }} permissions</x-admin.badge>
                            @endif
                        </td>
                        <td><span class="d-inline-flex align-items-center gap-1 text-body-secondary"><i class="bi bi-people"></i>{{ $role->users_count }}</span></td>
                        <td class="text-body-secondary">{{ $role->created_at?->format('d M Y') }}</td>
                        <td class="text-end text-nowrap">
                            @if ($locked)
                                <span class="text-body-secondary small"><i class="bi bi-lock"></i> Locked</span>
                            @else
                                @can('roles.update')
                                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                @endcan
                                @can('roles.delete')
                                    <x-admin.delete-button :action="route('roles.destroy', $role)" confirm="Delete this role?" />
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-admin.empty icon="shield" text="No roles yet." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
</div>
@endsection
