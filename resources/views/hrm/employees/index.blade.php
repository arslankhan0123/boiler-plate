@extends('layouts.admin.main')
@section('title', 'Employees')
@section('subtitle', 'HRM · Employee directory')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Employees" subtitle="Your team — profiles, logins and access.">
        @can('hrm.view_employees')
            <x-admin.button variant="light" :href="route('hrm.org-chart')" icon="diagram-3">Org Chart</x-admin.button>
        @endcan
        @can('hrm.manage_employees')
            <x-admin.button :href="route('hrm.employees.create')" icon="person-plus">Add Employee</x-admin.button>
        @endcan
    </x-admin.page-header>

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3"><x-admin.stat label="Active employees" :value="$stats['total']" icon="people" tone="primary" /></div>
        <div class="col-sm-6 col-xl-3"><x-admin.stat label="With login" :value="$stats['with_login']" icon="key" tone="success" /></div>
        <div class="col-sm-6 col-xl-3"><x-admin.stat label="On leave today" :value="$stats['on_leave']" icon="airplane" tone="warning" /></div>
        <div class="col-sm-6 col-xl-3"><x-admin.stat label="Departments" :value="$stats['departments']" icon="diagram-3" tone="info" /></div>
    </div>

    <x-admin.card flush :title="'Directory ('.$employees->total().')'">
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <select name="department" class="form-select" style="width:180px;min-height:42px" onchange="this.form.submit()">
                    <option value="">All departments</option>
                    @foreach ($departments as $id => $name)<option value="{{ $id }}" @selected(request('department') == $id)>{{ $name }}</option>@endforeach
                </select>
                <select name="status" class="form-select" style="width:130px;min-height:42px" onchange="this.form.submit()">
                    @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'all' => 'All'] as $k => $v)<option value="{{ $k }}" @selected(request('status', 'active') === $k)>{{ $v }}</option>@endforeach
                </select>
                <div class="input-icon" style="width:220px"><i class="bi bi-search"></i>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control" style="min-height:42px" placeholder="Name, email, code…"></div>
            </form>
        </x-slot:actions>

        <div class="table-responsive">
            <table class="table app-table table-hover">
                <thead><tr><th>Employee</th><th>Code</th><th>Contact</th><th>Joined</th><th>Login</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @forelse ($employees as $e)
                    <tr>
                        <td>@include('hrm.partials.person', ['e' => $e, 'link' => true])</td>
                        <td class="text-body-secondary">{{ $e->code ?: '—' }}</td>
                        <td><div class="small">{{ $e->email ?: '—' }}</div><div class="small text-body-secondary">{{ $e->phone }}</div></td>
                        <td class="text-body-secondary">{{ $e->join_date ? \Illuminate\Support\Carbon::parse($e->join_date)->format('d M Y') : '—' }}</td>
                        <td>
                            @if ($e->user_id)<x-admin.badge tone="success" icon="key">Enabled</x-admin.badge>@else<x-admin.badge tone="muted">No login</x-admin.badge>@endif
                            @unless ($e->is_active)<x-admin.badge tone="danger">Inactive</x-admin.badge>@endunless
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('hrm.employees.show', $e) }}" class="btn btn-action" title="View"><i class="bi bi-eye"></i></a>
                            @can('hrm.manage_employees')
                                <a href="{{ route('hrm.employees.edit', $e) }}" class="btn btn-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                <x-admin.delete-button :action="route('hrm.employees.destroy', $e)" confirm="Remove this employee? Their login will be disabled." />
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-admin.empty icon="people" text="No employees found." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($employees->hasPages())
            <x-slot:footer>{{ $employees->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
