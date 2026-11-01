@extends('layouts.admin.main')
@php
    $editing = $role->exists;
    $checked = old('permissions', $assigned);
    $actions = config('module_permissions.actions');
    $actionIcons = ['view' => 'eye', 'create' => 'plus-circle', 'update' => 'pencil', 'delete' => 'trash3'];
@endphp
@section('title', $editing ? 'Edit Role' : 'Create Role')
@section('subtitle', 'Define a role and its module permissions')

@section('content')
<div class="app-content">
    <x-admin.page-header :back="route('roles.index')" :title="$editing ? 'Edit Role' : 'Create Role'"
        subtitle="Name the role and choose what it can do in each module">
        <x-admin.button variant="light" :href="route('roles.index')" icon="list-ul">All Roles</x-admin.button>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('roles.update', $role) : route('roles.store') }}" class="app-form-grid">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="app-card app-form-card">
            <x-admin.input name="name" label="Role Name" :value="$role->name" placeholder="e.g. Store Manager, Cashier, Accountant" required />

            <div class="app-field">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">Permissions <span class="opt">“View” shows the module in the sidebar</span></label>
                    <label class="d-flex align-items-center gap-2 mb-0 small fw-semibold" style="cursor:pointer">
                        <input class="form-check-input mt-0" type="checkbox" id="checkAll"> Select all
                    </label>
                </div>
                <div class="table-responsive border rounded-3">
                    <table class="table app-table perm-matrix">
                        <thead>
                            <tr>
                                <th>Module</th>
                                @foreach ($actions as $a)<th><i class="bi bi-{{ $actionIcons[$a] ?? 'dot' }} me-1"></i>{{ ucfirst($a) }}</th>@endforeach
                                <th>All</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach (config('module_permissions.modules') as $key => $m)
                            <tr>
                                <td>
                                    <div class="perm-module">
                                        <span class="app-tile__icon tone-primary"><i class="bi {{ $m['icon'] }}"></i></span>
                                        <div>{{ $m['label'] }}<div class="small text-body-secondary fw-normal">{{ $m['group'] }}</div></div>
                                    </div>
                                </td>
                                @foreach ($actions as $a)
                                    @php $perm = "$key.$a"; @endphp
                                    <td>
                                        @can($perm)
                                            <input class="form-check-input perm perm-{{ $key }}" type="checkbox" name="permissions[]"
                                                   value="{{ $perm }}" @checked(in_array($perm, $checked))>
                                        @else
                                            <i class="bi bi-lock text-body-tertiary" title="You don't have this permission"></i>
                                        @endcan
                                    </td>
                                @endforeach
                                <td><input class="form-check-input row-toggle" type="checkbox" data-row="{{ $key }}"></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="app-form-actions">
                <x-admin.button class="btn-lg-app" icon="check2">{{ $editing ? 'Save Changes' : 'Create Role' }}</x-admin.button>
                <x-admin.button variant="light" class="btn-lg-app" :href="route('roles.index')">Cancel</x-admin.button>
            </div>
        </div>

        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Summary</h3>
                <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-body-secondary">Permissions selected</span><strong id="permCount">0</strong></div>
                <div class="d-flex justify-content-between py-2"><span class="text-body-secondary">Modules visible</span><strong id="modCount">0</strong></div>
            </div></div>

            <x-admin.tips :items="[
                'Give each role only the access it really needs.',
                'Ticking Create, Update or Delete also ticks View.',
                'Unticking View removes the module completely.',
                'Users get the new permissions on their next page load.',
            ]" />
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const perms = [...document.querySelectorAll('.perm')];
    const rows = [...document.querySelectorAll('.row-toggle')];
    const all = document.getElementById('checkAll');
    const sync = () => {
        rows.forEach(r => {
            const cbs = perms.filter(p => p.classList.contains('perm-' + r.dataset.row));
            r.checked = cbs.length && cbs.every(c => c.checked);
        });
        all.checked = perms.length && perms.every(c => c.checked);
        document.getElementById('permCount').textContent = perms.filter(c => c.checked).length;
        document.getElementById('modCount').textContent = perms.filter(c => c.checked && c.value.endsWith('.view')).length;
    };
    all.addEventListener('change', () => { perms.forEach(c => c.checked = all.checked); sync(); });
    rows.forEach(r => r.addEventListener('change', () => {
        perms.filter(p => p.classList.contains('perm-' + r.dataset.row)).forEach(c => c.checked = r.checked); sync();
    }));
    perms.forEach(cb => cb.addEventListener('change', () => {
        const [mod, act] = cb.value.split('.');
        if (cb.checked && act !== 'view') { const v = perms.find(p => p.value === mod + '.view'); if (v) v.checked = true; }
        if (!cb.checked && act === 'view') perms.filter(p => p.value.startsWith(mod + '.')).forEach(p => p.checked = false);
        sync();
    }));
    sync();
})();
</script>
@endpush
