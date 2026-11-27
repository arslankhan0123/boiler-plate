@extends('layouts.admin.main')
@section('title', 'Users')
@section('subtitle', "Manage user accounts and roles")

@section('content')
<div class="app-content">
    <x-admin.page-header title="Users" subtitle="Manage user accounts and assign roles.">
        @can('users.create')
            <x-admin.button :href="route('users.create')" icon="plus-lg">Add User</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.alerts />

    <x-admin.card flush :title="'All users ('.$users->total().')'">
        <x-slot:actions>
            <form method="GET" class="input-icon" style="width:260px">
                <i class="bi bi-search"></i>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search name or email…">
            </form>
        </x-slot:actions>

        <div class="table-responsive">
            <table class="table app-table table-hover">
                <thead>
                    <tr><th>User</th><th>Phone</th><th>Role</th><th>Created</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <span class="app-avatar">{{ collect(explode(' ', $u->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->join('') }}</span>
                                <div>
                                    <div class="fw-semibold">{{ $u->name }}</div>
                                    <div class="small text-body-secondary">{{ $u->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-body-secondary">{{ $u->phone ?: '—' }}</td>
                        <td>
                            @forelse ($u->roles as $r)
                                <x-admin.badge :tone="$r->name === config('modules.master_role') ? 'warning' : 'primary'">{{ $r->name }}</x-admin.badge>
                            @empty
                                <x-admin.badge tone="muted">No role</x-admin.badge>
                            @endforelse
                        </td>
                        <td class="text-body-secondary">{{ $u->created_at?->format('d M Y') }}</td>
                        <td class="text-end text-nowrap">
                            @can('users.update')
                                <a href="{{ route('users.edit', $u) }}" class="btn btn-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                            @endcan
                            @can('users.delete')
                                @if ($u->id !== auth()->id())
                                    <x-admin.delete-button :action="route('users.destroy', $u)" confirm="Delete this user?" />
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-admin.empty icon="people" text="No users found." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <x-slot:footer>{{ $users->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
